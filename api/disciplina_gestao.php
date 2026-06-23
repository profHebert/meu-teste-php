<?php
// disciplina_gestao.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🌟 TENTATIVA INTELIGENTE DE ENCONTRAR O CONFIG.PHP NA VERCEL
if (file_exists(__DIR__ . "/config.php")) {
    require_once __DIR__ . "/config.php";
} elseif (file_exists(__DIR__ . "/../config.php")) {
    require_once __DIR__ . "/../config.php";
} else {
    die("<div style='font-family:sans-serif;padding:20px;background:#fce8e6;color:#c5221f;border-radius:4px;'>
            <strong>Erro Crítico:</strong> O arquivo config.php não foi encontrado! Verifique a posição dele no projeto.
         </div>");
}

$url_base = rtrim(SUPABASE_URL, '/');
$erro = '';
$sucesso = '';

$sigla_form = '';
$nome_form = '';
$modo_edicao = false;

// 🌟 CONFIGURAÇÃO DO NOME REAL DA SUA TABELA NO SUPABASE:
define('TABELA_NOME', 'disciplinas'); 

// =========================================================================
// GATILHO DE EDIÇÃO (GET)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['editar_sigla']) && !empty($_GET['editar_sigla'])) {
    $sigla_busca = trim($_GET['editar_sigla']);
    
    // Corrigido para usar TABELA_NOME (plural)
    $url_busca = $url_base . "/rest/v1/" . TABELA_NOME . "?sigla=eq." . urlencode($sigla_busca);
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
        $modo_edicao = true;
    }
}

// =========================================================================
// PROCESSAMENTO DO FORMULÁRIO (POST)
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
            // Corrigido para plural
            $url_salvar = $url_base . "/rest/v1/" . TABELA_NOME;
            $ch = curl_init($url_salvar);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        } else {
            $sigla_antiga = $_POST['sigla_antiga'] ?? $sigla;
            // Corrigido para plural
            $url_salvar = $url_base . "/rest/v1/" . TABELA_NOME . "?sigla=eq." . urlencode($sigla_antiga);
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
            $erro = "Erro no Supabase (Código HTTP: " . $http_code . "). Verifique se os campos batem com o banco.";
        }
    }
}

if (isset($_GET['sucesso'])) {
    $sucesso = "Operação realizada com sucesso!";
}

// =========================================================================
// BUSCA DAS DISCIPLINAS PARA O SELECT (Corrigido para plural)
// =========================================================================
$url_lista = $url_base . "/rest/v1/" . TABELA_NOME . "?select=*&order=sigla.asc";
$ch = curl_init($url_lista);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: " . SUPABASE_KEY,
    "Authorization: Bearer " . SUPABASE_KEY
]);
$res_lista = curl_exec($ch);
$http_code_lista = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$disciplinas = json_decode($res_lista, true) ?: [];

if ($http_code_lista !== 200) {
    $erro = "Não foi possível conectar à tabela '" . TABELA_NOME . "'. Código HTTP: " . $http_code_lista;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Disciplinas</title>
    <?php include_once "theme.php"; ?>
    <style>
        body { font-family: Arial, sans-serif; background-color: var(--bg, #eef2f7); color: var(--text, #202124); margin: 0; padding: 20px; }
        .page-wrapper { max-width: 1300px; margin: 0 auto; }
        .top-links { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px; justify-content: center; }
        .top-links a { display: inline-flex; align-items: center; gap: 8px; padding: 12px 18px; border-radius: 999px; background: var(--surface, #ffffff); color: var(--text, #202124); text-decoration: none; border: 1px solid var(--border, #dce7f8); box-shadow: 0 12px 30px rgba(15,23,42,0.06); transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease; }
        .top-links a:hover { transform: translateY(-2px); background: var(--surface-alt, #f8faff); border-color: var(--accent, #475be8); }
        .top-links a.active { background: var(--accent, #475be8); color: white; border-color: transparent; }
        .dashboard-header { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 20px; background: var(--surface, #ffffff); border: 1px solid var(--border, #dce7f8); border-radius: 20px; padding: 28px 30px; box-shadow: var(--shadow, 0 20px 50px rgba(71,91,232,0.12)); margin-bottom: 24px; }
        .header-info h1 { margin: 0 0 8px; font-size: 32px; letter-spacing: -0.03em; }
        .header-info p { max-width: 720px; line-height: 1.6; color: var(--muted, #64748b); margin: 0; }
        .header-stats { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .header-chip { background: var(--surface-alt, #eef2ff); color: var(--text, #202124); padding: 12px 16px; border-radius: 14px; border: 1px solid var(--border, #dce7f8); font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .section-grid { display: grid; grid-template-columns: 1.28fr 1fr; gap: 24px; margin-bottom: 24px; }
        .container { background: var(--surface, #ffffff); padding: 28px; border-radius: 22px; box-shadow: var(--shadow, 0 20px 50px rgba(71,91,232,0.12)); border: 1px solid var(--border, #dce7f8); }
        .container h2, .container h3 { color: var(--text, #202124); margin-top: 0; }
        .input-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 8px; color: #475569; font-weight: 600; font-size: 14px; }
        input[type="text"], select { width: 100%; padding: 14px 16px; border: 1px solid #d3dce6; border-radius: 14px; box-sizing: border-box; font-size: 15px; background: var(--surface-alt, #f8fbff); color: var(--text, #202124); }
        .btn { background-color: var(--accent, #475be8); color: white; border: none; padding: 13px 22px; border-radius: 14px; cursor: pointer; font-weight: 700; font-size: 15px; box-shadow: 0 16px 36px rgba(71,91,232,0.12); }
        .btn:hover { background-color: #3448c2; }
        .btn-secondary { background-color: #f8fafc; color: #475569; border: 1px solid #d3dce6; text-decoration: none; padding: 12px 22px; border-radius: 14px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .alert { padding: 14px 16px; border-radius: 14px; margin-bottom: 20px; font-size: 14px; line-height: 1.5; }
        .error { color: #b91c1c; background-color: #fee2e2; border: 1px solid #fecaca; }
        .success { color: #166534; background-color: #dcfce7; border: 1px solid #bbf7d0; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; min-width: 520px; }
        th, td { padding: 16px 18px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f8fafc; color: #64748b; font-size: 14px; text-transform: uppercase; letter-spacing: 0.02em; }
        tr:hover td { background: #f8fbff; }
        .status-chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: #eef2ff; color: #1d4ed8; font-weight: 700; font-size: 13px; }
        @media (max-width: 1024px) {
            .section-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .top-links { justify-content: center; }
            .dashboard-header { padding: 22px; }
            .header-info h1 { font-size: 26px; }
        }
    </style>
</head>
<body>

<div class="page-wrapper">
    <div class="top-links">
        <a href="ambiente_professor.php"><span>🏠</span> Início</a>
        <a href="admin_dashboard.php"><span>📋</span> Painel Administrativo</a>
        <a href="dashboard.php"><span>📊</span> Relatórios</a>
        <a href="disciplina_gestao.php" class="active"><span>📚</span> Disciplinas</a>
        <a href="questao_gestao.php"><span>📝</span> Questões</a>
        <a href="criar_turma.php"><span>👥</span> Turmas</a>
        <a href="cadastro_professor.php"><span>👤</span> Professores</a>
    </div>

    <div class="dashboard-header">
        <div class="header-info">
            <h1>Gestão de Disciplinas</h1>
            <p>Organize todas as disciplinas do sistema em um painel que une cadastro, edição e visualização dos cursos ativos.</p>
        </div>
        <div class="header-stats">
            <span class="header-chip">Total de Disciplinas: <?= count($disciplinas) ?></span>
            <span class="header-chip">Atualizado em: <?= date('d/m/Y') ?></span>
        </div>
    </div>

    <div class="section-grid">
        <div class="container">
            <h2>📚 Gestão de Disciplinas</h2>
    
    <?php if(!empty($erro)): ?> <div class="alert error"><?= $erro ?></div> <?php endif; ?>
    <?php if(!empty($sucesso)): ?> <div class="alert success"><?= $sucesso ?></div> <?php endif; ?>

    <div class="input-group" style="background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #dadce0;">
        <label for="carregar_disciplina">📝 Para ALTERAR uma disciplina existente, selecione-a abaixo:</label>
        <select id="carregar_disciplina" onchange="if(this.value) window.location.href='?editar_sigla='+this.value;">
            <option value="">-- Escolha uma disciplina para editar --</option>
            <?php if(!empty($disciplinas)): ?>
                <?php foreach($disciplinas as $d): ?>
                    <option value="<?= htmlspecialchars($d['sigla']) ?>" <?= ($sigla_form === $d['sigla']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['sigla']) ?> - <?= htmlspecialchars($d['nome']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <hr style="border: 0; border-top: 1px solid #dadce0; margin: 25px 0;">

    <form method="POST" action="">
        <input type="hidden" name="is_update" value="<?= $modo_edicao ? '1' : '0' ?>">
        <input type="hidden" name="sigla_antiga" value="<?= htmlspecialchars($sigla_form) ?>">

        <h3><?= $modo_edicao ? '✏️ Alterar Disciplina Selecionada' : '✨ Cadastrar Nova Disciplina' ?></h3>

        <div class="input-group">
            <label for="sigla">Sigla da Disciplina</label>
            <input type="text" id="sigla" name="sigla" value="<?= htmlspecialchars($sigla_form) ?>" required placeholder="Ex: MAT" <?= $modo_edicao ? 'readonly style="background:#f1f3f4;"' : '' ?>>
        </div>

        <div class="input-group">
            <label for="nome">Nome da Disciplina</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome_form) ?>" required placeholder="Ex: Matemática">
        </div>

        <button type="submit" name="salvar_disciplina" class="btn">
            <?= $modo_edicao ? 'Salvar Alterações' : 'Cadastrar Disciplina' ?>
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
                <tr><td colspan="2" style="text-align: center; color: #a0a0a0;">Nenhuma disciplina listada ou conexão pendente.</td></tr>
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