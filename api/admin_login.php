<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erro_login = '';

// Definição de credencial (Em produção, ideal ler do Supabase ou variáveis de ambiente)
$professor_usuario = "admin";
$professor_senha   = "professor123"; // Escolha sua senha segura

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_login'])) {
    $usuario = $_POST['usuario'] ?? '';
    $senha   = $_POST['senha'] ?? '';

    if ($usuario === $professor_usuario && $senha === $professor_senha) {
        $_SESSION['professor_logado'] = true;
        header("Location: ambiente_professor.php");
        exit;
    } else {
        $erro_login = "Usuário ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Acesso do Professor</title>
    <link rel="stylesheet" href="css/global.css">
</head>
<body>

    <div class="card-sistema">
        <h2>Área do Docente</h2>
        <p class="subtitulo">Identifique-se para gerenciar o portal</p>

        <?php if ($erro_login): ?>
            <div class="erro"><?php echo $erro_login; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="acao_login" value="1">
            <div class="input-grupo">
                <label style="display:block; text-align:left; font-size:14px;">Usuário</label>
                <input type="text" name="usuario" required placeholder="Ex: admin">
            </div>
            <div class="input-grupo">
                <label style="display:block; text-align:left; font-size:14px;">Senha de Acesso</label>
                <input type="password" name="senha" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-acao">Entrar no Painel</button>
        </form>
    </div>

</body>
</html>