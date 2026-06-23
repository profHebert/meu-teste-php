<?php
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

if (!isset($_SESSION['professor_logado']) || $_SESSION['professor_logado'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_turma'])) {
    $codigo = trim($_POST['codigo'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $disciplina = trim($_POST['disciplina'] ?? '');

    if (empty($codigo) || empty($nome)) {
        $erro = 'Os campos Código e Nome são obrigatórios.';
    } else {
        $payload = json_encode([
            'codigo' => $codigo,
            'nome' => $nome,
            'disciplina' => $disciplina
        ]);

        $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/turmas';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json'
        ]);

        $resposta = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 201 || $http_code === 200) {
            $sucesso = 'Turma cadastrada com sucesso.';
        } else {
            $erro = 'Falha ao cadastrar turma. Código HTTP: ' . $http_code;
        }
    }
}

$disciplinas = [];
$ch = curl_init(rtrim(SUPABASE_URL, '/') . '/rest/v1/disciplinas?select=sigla,nome');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Content-Type: application/json'
]);
$resposta = curl_exec($ch);
curl_close($ch);
$disciplinas = json_decode($resposta, true) ?: [];

$turmas = [];
$ch = curl_init(rtrim(SUPABASE_URL, '/') . '/rest/v1/turmas?select=*&order=nome.asc');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Content-Type: application/json'
]);
$resposta = curl_exec($ch);
curl_close($ch);
$turmas = json_decode($resposta, true) ?: [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Turma</title>
    <?php include_once "theme.php"; ?>
    <style>
        body { font-family: Arial, sans-serif; background-color: var(--bg, #f0ebf8); color: var(--text, #202124); margin: 0; padding: 40px; }
        .container { background: var(--surface, #ffffff); padding: 28px; border-radius: 12px; max-width: 820px; margin: 0 auto 20px; border: 1px solid var(--border, #dadce0); box-shadow: var(--shadow, 0 10px 30px rgba(15,23,42,0.08)); }
        h2 { margin-top: 0; color: var(--text, #202124); }
        .form-grid { display: grid; gap: 16px; margin-bottom: 24px; }
        .form-group { display: grid; gap: 6px; }
        label { font-weight: 600; color: var(--text, #202124); }
        input, select { width: 100%; padding: 12px 14px; border: 1px solid var(--border, #dadce0); border-radius: 10px; background: var(--surface-alt, #f8f9fa); color: var(--text, #202124); }
        .btn-primary { background: var(--accent, #475be8); color: white; border: none; padding: 12px 20px; border-radius: 10px; cursor: pointer; font-weight: 700; }
        .btn-primary:hover { opacity: 0.95; }
        .alert { padding: 14px; border-radius: 10px; margin-bottom: 20px; }
        .alert.error { background: #fde8e8; color: #b31217; border: 1px solid #f5c0c0; }
        .alert.success { background: #e6f8ee; color: #0f5132; border: 1px solid #b7eb8f; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 12px 14px; border-bottom: 1px solid var(--border, #e2e8f0); text-align: left; }
        th { background: var(--surface-alt, #f8f9fa); color: var(--muted, #64748b); }
    </style>
</head>
<body>

<div class="layout-admin" style="display:flex; gap:24px; align-items:flex-start;">
    <aside style="width:260px; flex:0 0 260px;">
        <?php include_once "professor_menu.php"; ?>
    </aside>

    <main style="flex:1;">
        <div class="container">
            <h2>👥 Cadastrar Turma</h2>

            <?php if (!empty($erro)): ?>
                <div class="alert error"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            <?php if (!empty($sucesso)): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="codigo">Código da Turma</label>
                        <input type="text" id="codigo" name="codigo" required placeholder="Ex: DBDSQL_6a_M_atv1">
                    </div>
                    <div class="form-group">
                        <label for="nome">Nome da Turma</label>
                        <input type="text" id="nome" name="nome" required placeholder="Ex: 6º Ano - Matematica">
                    </div>
                    <div class="form-group">
                        <label for="disciplina">Disciplina</label>
                        <select id="disciplina" name="disciplina">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($disciplinas as $disciplina): ?>
                                <option value="<?= htmlspecialchars($disciplina['sigla'] ?? '') ?>"><?= htmlspecialchars(($disciplina['sigla'] ?? '') . ' - ' . ($disciplina['nome'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="salvar_turma" class="btn-primary">Cadastrar Turma</button>
            </form>
        </div>

        <div class="container">
            <h2>Turmas Cadastradas</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Código</th><th>Nome</th><th>Disciplina</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($turmas)): ?>
                            <tr><td colspan="3">Nenhuma turma encontrada ou tabela não existe.</td></tr>
                        <?php else: ?>
                            <?php foreach ($turmas as $turma): ?>
                                <tr>
                                    <td><?= htmlspecialchars($turma['codigo'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($turma['nome'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($turma['disciplina'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>
