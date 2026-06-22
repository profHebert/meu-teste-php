<?php
// disciplina_gestao.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. CONFIGURAÇÕES DO COFRE DO SUPABASE (Traga suas chaves aqui)
// define('SUPABASE_URL', 'https://vxkxptbrfbqygpisggjm.supabase.co');
// define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.SuaChaveRealAqui...'); 
require_once __DIR__ . "/config.php";
//include_once "config.php";

$url_base = rtrim(SUPABASE_URL, '/');
$erro = '';
$sucesso = '';

// Variáveis para preenchimento do formulário (Modo Inserção padrão)
$sigla_form = '';
$nome_form = '';
$modo_edicao = false;

// =========================================================================
// GATILHO DE EDIÇÃO (GET): Se o professor escolher uma sigla no select de alteração
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['editar_sigla']) && !empty($_GET['editar_sigla'])) {
    $sigla_busca = trim($_GET['editar_sigla']);
    
    $url_busca = $url_base . "/rest/v1/disciplina?sigla=eq." . urlencode($sigla_busca);
    $ch = curl_init($url_busca);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY
    ]);
    $res_busca = curl_exec($ch);
    curl_close($ch);
    
    $dados_disciplina = json_decode($res_busca, true) ?: [];
    if (!empty($dados_disciplina)) {
        $sigla_form = $dados_disciplina[0]['sigla'];
        $nome_form  = $dados_disciplina[0]['nome'];
        $modo_edicao = true; // Ativa o modo alteração
    }
}

// =========================================================================
// PROCESSAMENTO DO FORMULÁRIO (POST): Salvar novo ou Alterar existente
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_disciplina'])) {
    $sigla = strtoupper(trim($_POST['sigla'] ?? ''));
    $nome  = trim($_POST['nome'] ?? '');
    $is_update = $_POST['is_update'] === '1';

    if (empty($sigla) || empty($nome)) {
        $erro = "Todos os campos são obrigatórios.";
    } else {
        $payload = json_encode(["sigla" => $sigla, "nome" => $nome]);
        
        if (!$is_update) {
            // 🚀 INCLUSÃO (POST)
            $url_salvar = $url_base . "/rest/v1/disciplina";
            $ch = curl_init($url_salvar);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        } else {
            // ✏️ ALTERAÇÃO (PATCH) - Filtra pela sigla passada no campo escondido
            $sigla_antiga = $_POST['sigla_antiga'] ?? $sigla;
            $url_salvar = $url_base . "/rest/v1/disciplina?sigla=eq." . urlencode($sigla_antiga);
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

        if ($http_code == 200 || $http_code == 201 || $http_code == 204) {
            header("Location: disciplina_gestao.php?sucesso=1");
            exit;
        } else {
            $erro = "Erro ao salvar no banco de dados. Código HTTP: " . $http_code;
        }
    }
}

if (isset($_GET['sucesso'])) {
    $sucesso = "Operação realizada com sucesso absoluto!";
}

// =========================================================================
// BUSCA DAS DISCIPLINAS PARA A LISTAGEM E PARA O SELECT
// =========================================================================
$url_lista = $url_base . "/rest/v1/disciplina?select=*&order=sigla.asc";
$ch = curl_init($url_lista);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: " . SUPABASE_KEY,
    "Authorization: Bearer " . SUPABASE_KEY
]);
$res_lista = curl_exec($ch);
curl_close($ch);
$disciplinas = json_decode($res_lista, true) ?: [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Disciplinas</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0ebf8; margin: 0; padding: 40px; display: flex; flex-direction: column; align-items: center; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 700px; border-top: 8px solid #673ab7; margin-bottom: 20px; }
        h2, h3 { color: #202124; margin-top: 0; }
        .input-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #5f6368; font-weight: bold; font-size: 14px; }
        input[type="text"], select { width: 100%; padding: 10px; border: 1px solid #dadce0; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .btn { background-color: #673ab7; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn:hover { background-color: #512da8; }
        .btn-secondary { background-color: #f1f3f4; color: #3c4043; border: 1px solid #dadce0; margin-left: 10px; text-decoration: none; padding: 10px 20px; border-radius: 4px; font-size: 14px; font-weight: bold; display: inline-block; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .error { color: #c5221f; background-color: #fce8e6; }
        .success { color: #137333; background-color: #e6f4ea; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #dadce0; }
        th { background-color: #f8f9fa; color: #5f6368; }
        .modo-badge { background-color: #e8f0fe; color: #1a73e8; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>📚 Gestão de Disciplinas</h2>
    
    <?php if(!empty($erro)): ?> <div class="alert error"><?= $erro ?></div> <?php endif; ?>
    <?php if(!empty($sucesso)): ?> <div class="alert success"><?= $sucesso ?></div> <?php endif; ?>

    <div class="input-group" style="background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px id #dadce0;">
        <label for="carregar_disciplina">📝 Para ALTERAR uma disciplina, selecione-a abaixo:</label>
        <select id="carregar_disciplina" onchange="if(this.value) window.location.href='?editar_sigla='+this.value;">
            <option value="">-- Escolha uma disciplina para editar --</option>
            <?php foreach($disciplinas as $d): ?>
                <option value="<?= htmlspecialchars($d['sigla']) ?>" <?= ($sigla_form === $d['sigla']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['sigla']) ?> - <?= htmlspecialchars($d['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <hr style="border: 0; border-top: 1px solid #dadce0; margin: 20px 0;">

    <form method="POST" action="">
        <input type="hidden" name="is_update" value="<?= $modo_edicao ? '1' : '0' ?>">
        <input type="hidden" name="sigla_antiga" value="<?= htmlspecialchars($sigla_form) ?>">

        <h3>
            <?= $modo_edicao ? '✏️ Modo Edição' : '✨ Modo Nova Inclusão' ?>
        </h3>

        <div class="input-group">
            <label for="sigla">Sigla da Disciplina (Ex: MAT, PORT, FIS)</label>
            <input type="text" id="sigla" name="sigla" value="<?= htmlspecialchars($sigla_form) ?>" required placeholder="Ex: MAT" <?= $modo_edicao ? 'readonly style="background:#f1f3f4;"' : '' ?>>
        </div>

        <div class="input-group">
            <label for="nome">Nome Completo da Disciplina</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome_form) ?>" required placeholder="Ex: Matemática Aplicada">
        </div>

        <button type="submit" name="salvar_disciplina" class="btn">
            <?= $modo_edicao ? 'Atualizar Disciplina' : 'Cadastrar Disciplina' ?>
        </button>

        <?php if($modo_edicao): ?>
            <a href="disciplina_gestao.php" class="btn-secondary">Cancelar Edição</a>
        <?php endif; ?>
    </form>
</div>

<div class="container">
    <h3>📋 Disciplinas Ativas no Banco</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 30%;">Sigla</th>
                <th>Nome da Disciplina</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($disciplinas)): ?>
                <tr><td colspan="2" style="text-align: center; color: #a0a0a0;">Nenhuma disciplina cadastrada ainda.</td></tr>
            <?php else: ?>
                <?php foreach($disciplinas as $d): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['sigla']) ?></strong></td>
                        <td><?= htmlspecialchars($d['nome']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>