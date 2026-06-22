<?php
// questao_gestao.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . "/config.php")) {
    require_once __DIR__ . "/config.php";
} elseif (file_exists(__DIR__ . "/../config.php")) {
    require_once __DIR__ . "/../config.php";
} else {
    die("Erro Crítico: O arquivo config.php não foi encontrado!");
}

$url_base = rtrim(SUPABASE_URL, '/');
$erro = '';
$sucesso = '';

$id_form = '';
$enunciado_form = '';
$imagem_url_form = '';
$numero_aula_form = '';
$disciplina_form = '';
$resposta_correta_form = 0;
$opcoes_form = ["", "", "", "", ""];
$modo_edicao = false;

// =========================================================================
// GATILHO DE EDIÇÃO (GET)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['editar_id']) && !empty($_GET['editar_id'])) {
    $id_busca = trim($_GET['editar_id']);
    
    $url_busca = $url_base . "/rest/v1/questoes?id=eq." . urlencode($id_busca);
    $ch = curl_init($url_busca);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: " . SUPABASE_KEY, "Authorization: Bearer " . SUPABASE_KEY]);
    $res_busca = curl_exec($ch);
    curl_close($ch);
    
    $dados_questao = json_decode($res_busca, true) ?: [];
    if (!empty($dados_questao)) {
        $id_form               = $dados_questao[0]['id'];
        $enunciado_form        = $dados_questao[0]['enunciado'];
        // 🌟 CORREÇÃO DO ERRO: Garante que se for null, vira uma string vazia ''
        $imagem_url_form       = $dados_questao[0]['imagem_url'] ?? ''; 
        $numero_aula_form      = $dados_questao[0]['numero_aula'];
        $disciplina_form       = $dados_questao[0]['disciplina'];
        $resposta_correta_form = (int)$dados_questao[0]['resposta_correta'];
        
        if (is_array($dados_questao[0]['opcoes'])) {
            $opcoes_form = $dados_questao[0]['opcoes'];
        } elseif (is_string($dados_questao[0]['opcoes'])) {
            $opcoes_form = json_decode($dados_questao[0]['opcoes'], true) ?: ["", "", "", "", ""];
        }
        $modo_edicao = true;
    }
}

// =========================================================================
// PROCESSAMENTO DO FORMULÁRIO (POST)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_questao'])) {
    $enunciado        = $_POST['enunciado'] ?? '';
    $numero_aula      = (int)($_POST['numero_aula'] ?? 0);
    $disciplina       = $_POST['disciplina'] ?? null;
    $resposta_correta = (int)($_POST['resposta_correta'] ?? 0);
    $opcoes           = $_POST['opcoes'] ?? ["", "", "", "", ""];
    $imagem_url       = $_POST['imagem_url_atual'] ?? null; 
    $is_update        = $_POST['is_update'] === '1';
    $id_questao       = $_POST['id_questao'] ?? '';

    // 📸 TRATAMENTO DE UPLOAD DE IMAGEM PARA O STORAGE DO SUPABASE
    if (isset($_FILES['foto_questao']) && $_FILES['foto_questao']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['foto_questao']['tmp_name'];
        $file_name = $_FILES['foto_questao']['name'];
        $file_ext  = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // Cria um nome único para o arquivo não sobrescrever outros
        $novo_nome_arquivo = uniqid("img_", true) . "." . $file_ext;
        
        // URL de Upload do Storage do Supabase
        $url_upload = $url_base . "/storage/v1/object/questoes_imagens/" . $novo_nome_arquivo;
        
        $file_data = file_get_contents($file_tmp);
        $mime_type = mime_content_type($file_tmp);

        $ch = curl_init($url_upload);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "apikey: " . SUPABASE_KEY,
            "Authorization: Bearer " . SUPABASE_KEY,
            "Content-Type: " . $mime_type
        ]);
        
        $res_upload = curl_exec($ch);
        $http_upload_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_upload_code == 200) {
            // Monta a URL pública definitiva da imagem hospedada no Supabase
            $imagem_url = $url_base . "/storage/v1/object/public/questoes_imagens/" . $novo_nome_arquivo;
        } else {
            $erro = "A imagem falhou ao subir para o Storage. Código: " . $http_upload_code;
        }
    }

    if (empty($erro) && (empty($enunciado) || empty($disciplina))) {
        $erro = "Por favor, preencha o enunciado e a disciplina.";
    }

    if (empty($erro)) {
        $dados_post = [
            "enunciado"        => $enunciado,
            "numero_aula"      => $numero_aula,
            "disciplina"       => $disciplina,
            "resposta_correta" => $resposta_correta,
            "opcoes"           => $opcoes,
            "imagem_url"       => !empty($imagem_url) ? $imagem_url : null,
            "ativa"            => true
        ];

        $payload = json_encode($dados_post);

        if (!$is_update) {
            $url_salvar = $url_base . "/rest/v1/questoes";
            $ch = curl_init($url_salvar);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        } else {
            $url_salvar = $url_base . "/rest/v1/questoes?id=eq." . urlencode($id_questao);
            $ch = curl_init($url_salvar);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "apikey: " . SUPABASE_KEY,
            "Authorization: Bearer " . SUPABASE_KEY,
            "Content-Type: application/json"
        ]);
        
        $resposta = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (in_array($http_code, [200, 201, 204])) {
            header("Location: questao_gestao.php?sucesso=1");
            exit;
        } else {
            $erro = "Falha ao gravar no banco. Código: " . $http_code;
        }
    }
}

if (isset($_GET['sucesso'])) {
    $sucesso = "Questão salva com sucesso!";
}

// BUSCA DE DISCIPLINAS
$ch = curl_init($url_base . "/rest/v1/disciplinas?select=sigla,nome&order=sigla.asc");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: " . SUPABASE_KEY, "Authorization: Bearer " . SUPABASE_KEY]);
$disciplinas = json_decode(curl_exec($ch), true) ?: [];
curl_close($ch);

// BUSCA QUESTÕES
$ch = curl_init($url_base . "/rest/v1/questoes?select=*&order=created_at.desc");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: " . SUPABASE_KEY, "Authorization: Bearer " . SUPABASE_KEY]);
$questoes = json_decode(curl_exec($ch), true) ?: [];
curl_close($ch);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Questões</title>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 30px; display: flex; flex-direction: column; align-items: center; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); width: 100%; max-width: 850px; border-top: 8px solid #005088; margin-bottom: 25px; box-sizing: border-box; }
        h2, h3 { color: #005088; margin-top: 0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .input-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; color: #4a5568; font-weight: bold; font-size: 14px; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        
        /* Ajuste do Editor Quill */
        #editor-container { height: 150px; background: white; border-radius: 0 0 4px 4px; }
        .ql-toolbar { border-radius: 4px 4px 0 0; background: #edf2f7; }

        .opcao-item { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .opcao-radio { width: 20px; height: 20px; cursor: pointer; }
        .btn-salvar { background-color: #005088; color: white; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 15px; }
        .btn-salvar:hover { background-color: #003a63; }
        .btn-cancelar { background-color: #e2e8f0; color: #4a5568; text-decoration: none; padding: 12px 25px; border-radius: 4px; font-size: 15px; font-weight: bold; display: inline-block; margin-left: 10px; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .error { color: #c5221f; background-color: #fce8e6; border: 1px solid #f2b8b5; }
        .success { color: #137333; background-color: #e6f4ea; border: 1px solid #b7e1cd; }
        .q-card { background: #fff; border: 1px solid #e2e8f0; border-left: 5px solid #11caa0; border-radius: 6px; padding: 15px; margin-bottom: 15px; }
        .q-card-header { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; color: #718096; font-weight: bold; }
        .q-badge { background: #e2e8f0; padding: 2px 8px; border-radius: 12px; }
        .q-body { font-size: 15px; color: #2d3748; margin-bottom: 15px; line-height: 1.5; }
        .btn-edit-item { background: #11caa0; color: white; text-decoration: none; padding: 6px 14px; border-radius: 4px; font-size: 13px; font-weight: bold; display: inline-block; }
        .img-preview { max-width: 180px; border-radius: 4px; display: block; margin-top: 8px; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>

<div class="container">
    <h2>📝 Central de Gestão de Questões</h2>
    
    <?php if(!empty($erro)): ?> <div class="alert error"><?= $erro ?></div> <?php endif; ?>
    <?php if(!empty($sucesso)): ?> <div class="alert success"><?= $sucesso ?></div> <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" onsubmit="prepararEnvio();">
        <input type="hidden" name="is_update" value="<?= $modo_edicao ? '1' : '0' ?>">
        <input type="hidden" name="id_questao" value="<?= htmlspecialchars($id_form) ?>">
        <input type="hidden" name="imagem_url_atual" value="<?= htmlspecialchars($imagem_url_form) ?>">
        <input type="hidden" id="enunciado_hidden" name="enunciado">

        <h3><?= $modo_edicao ? '✏️ Editando Questão Selecionada' : '✨ Criar Nova Questão' ?></h3>

        <div class="grid-2">
            <div class="input-group">
                <label for="disciplina">Disciplina Associada:</label>
                <select id="disciplina" name="disciplina" required>
                    <option value="">-- Escolha a matéria --</option>
                    <?php foreach($disciplinas as $d): ?>
                        <option value="<?= htmlspecialchars($d['sigla']) ?>" <?= ($disciplina_form === $d['sigla']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['sigla']) ?> - <?= htmlspecialchars($d['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <label for="numero_aula">Número da Aula:</label>
                <input type="number" id="numero_aula" name="numero_aula" value="<?= htmlspecialchars($numero_aula_form) ?>" required min="1">
            </div>
        </div>

        <div class="input-group">
            <label>Enunciado da Questão (Use o painel para formatar):</label>
            <div id="editor-container"></div>
        </div>

        <div class="input-group">
            <label for="foto_questao">Selecione uma Imagem Ilustrativa (Upload Direto para o Supabase):</label>
            <input type="file" id="foto_questao" name="foto_questao" accept="image/*">
            
            <?php if(!empty($imagem_url_form)): ?>
                <p style="font-size:12px;margin:10px 0 2px 0; font-weight:bold; color:#4a5568;">Imagem atualmente ativa nesta questão:</p>
                <img src="<?= htmlspecialchars($imagem_url_form) ?>" class="img-preview" alt="Preview da questão">
            <?php endif; ?>
        </div>

        <div class="input-group">
            <label style="margin-bottom:12px;">Alternativas (Marque a bolinha ao lado da CORRETA):</label>
            <?php for($i = 0; $i < 5; $i++): ?>
                <div class="opcao-item">
                    <input type="radio" name="resposta_correta" value="<?= $i ?>" class="opcao-radio" <?= ($resposta_correta_form === $i) ? 'checked' : '' ?>>
                    <input type="text" name="opcoes[]" value="<?= htmlspecialchars($opcoes_form[$i] ?? '') ?>" required placeholder="Texto da alternativa <?= chr(65 + $i) ?>">
                </div>
            <?php endfor; ?>
        </div>

        <button type="submit" name="salvar_questao" class="btn-salvar">
            <?= $modo_edicao ? 'Salvar Alterações' : 'Publicar Questão' ?>
        </button>

        <?php if($modo_edicao): ?>
            <a href="questao_gestao.php" class="btn-cancelar">Cancelar Edição</a>
        <?php endif; ?>
    </form>
</div>

<div class="container" style="border-top: 8px solid #11caa0;">
    <h3>📚 Banco de Questões Cadastradas</h3>
    <?php if(empty($questoes)): ?>
        <p style="text-align:center; color:#a0aec0;">Nenhuma questão cadastrada ainda.</p>
    <?php else: ?>
        <?php foreach($questoes as $q): ?>
            <div class="q-card">
                <div class="q-card-header">
                    <div>
                        <span class="q-badge" style="background:#005088; color:white;"><?= htmlspecialchars($q['disciplina']) ?></span>
                        <span class="q-badge">Aula: <?= htmlspecialchars($q['numero_aula']) ?></span>
                    </div>
                    <div>Cadastrada em: <?= date('d/m/Y', strtotime($q['created_at'])) ?></div>
                </div>
                <div class="q-body">
                    <?= $q['enunciado'] ?>
                    <?php if(!empty($q['imagem_url'])): ?>
                        <img src="<?= htmlspecialchars($q['imagem_url']) ?>" class="img-preview" alt="Imagem">
                    <?php endif; ?>
                </div>
                <div style="text-align: right;">
                    <a href="?editar_id=<?= $q['id'] ?>" class="btn-edit-item">✏️ Editar Questão</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
    // Inicializa o editor visual apenas com o botão de Negrito (pode adicionar 'italic', 'underline' se quiser)
    var quill = new Quill('#editor-container', {
        modules: { toolbar: [['bold']] },
        theme: 'snow'
    });

    // Se estiver editando, coloca o texto vindo do banco dentro do editor visual de forma automática
    <?php if(!empty($enunciado_form)): ?>
        quill.clipboard.dangerouslyPasteHTML(0, `<?= $enunciado_form ?>`);
    <?php endif; ?>

    // Função executada no clique do botão enviar: copia o HTML do editor para o input hidden antes do POST
    function prepararEnvio() {
        var htmlDoEditor = quill.getSemanticHTML();
        document.getElementById('enunciado_hidden').value = htmlDoEditor;
    }
</script>
</body>
</html>