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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_professor'])) {
    $usuario = trim($_POST['usuario'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmacao = $_POST['senha_confirmacao'] ?? '';

    if (empty($usuario) || empty($nome) || empty($senha) || empty($senha_confirmacao)) {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif ($senha !== $senha_confirmacao) {
        $erro = 'As senhas não coincidem.';
    } else {
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT);
        $payload = json_encode([
            'usuario' => $usuario,
            'nome' => $nome,
            'senha_hash' => $senha_hash
        ]);

        $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/professores';
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
            $sucesso = 'Professor cadastrado com sucesso.';
        } else {
            $erro = 'Falha ao cadastrar professor. Código HTTP: ' . $http_code;
        }
    }
}

$professores = [];
$ch = curl_init(rtrim(SUPABASE_URL, '/') . '/rest/v1/professores?select=usuario,nome');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Content-Type: application/json'
]);
$resposta = curl_exec($ch);
curl_close($ch);
$professores = json_decode($resposta, true) ?: [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Professores</title>
    <?php include_once "theme.php"; ?>
    <style>
        body { font-family: Arial, sans-serif; background-color: var(--bg, #f0ebf8); color: var(--text, #202124); margin: 0; padding: 40px; }
        .container { background: var(--surface, #ffffff); padding: 28px; border-radius: 12px; max-width: 780px; margin: 0 auto 20px; border: 1px solid var(--border, #dadce0); box-shadow: var(--shadow, 0 10px 30px rgba(15,23,42,0.08)); }
        h2 { margin-top: 0; color: var(--text, #202124); }
        .alert { padding: 14px; border-radius: 10px; margin-bottom: 20px; }
        .alert.error { background: #fde8e8; color: #b31217; border: 1px solid #f5c0c0; }
        .alert.success { background: #e6f8ee; color: #0f5132; border: 1px solid #b7eb8f; }
        .form-grid { display: grid; gap: 16px; margin-bottom: 24px; }
        .form-group { display: grid; gap: 6px; }
        label { font-weight: 600; color: var(--text, #202124); }
        input { width: 100%; padding: 12px 14px; border: 1px solid var(--border, #dadce0); border-radius: 10px; background: var(--surface-alt, #f8f9fa); color: var(--text, #202124); }
        .btn-primary { background: var(--accent, #475be8); color: white; border: none; padding: 12px 20px; border-radius: 10px; cursor: pointer; font-weight: 700; }
        .btn-primary:hover { opacity: 0.95; }
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
            <h2>👩‍🏫 Cadastro de Professores</h2>

            <?php if (!empty($erro)): ?>
                <div class="alert error"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            <?php if (!empty($sucesso)): ?>
                <div class="alert success"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="usuario">Usuário</label>
                        <input type="text" id="usuario" name="usuario" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>
                    <div class="form-group">
                        <label for="senha_confirmacao">Confirmar Senha</label>
                        <input type="password" id="senha_confirmacao" name="senha_confirmacao" required>
                    </div>
                </div>
                <button type="submit" name="salvar_professor" class="btn-primary">Cadastrar Professor</button>
            </form>
        </div>

        <div class="container">
            <h2>Professores Cadastrados</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Usuário</th><th>Nome</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($professores)): ?>
                            <tr><td colspan="2">Nenhum professor encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($professores as $prof): ?>
                                <tr>
                                    <td><?= htmlspecialchars($prof['usuario'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($prof['nome'] ?? '-') ?></td>
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
