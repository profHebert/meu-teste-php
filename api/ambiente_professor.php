<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Trava de segurança contra acessos diretos por URL
if (!isset($_SESSION['professor_logado']) || $_SESSION['professor_logado'] !== true) {
    header("Location: index.php");
    exit;
}

// Logoff do sistema
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ambiente do Professor</title>
    <?php include_once "theme.php"; ?>
    <link rel="stylesheet" href="css/global.css">
    <style>
        .menu-professor {
            margin-top: 20px;
            text-align: left;
        }
        .link-menu {
            display: flex;
            align-items: center;
            padding: 14px;
            background: #f8f9fa;
            border: 1px solid #dadce0;
            border-radius: 6px;
            margin-bottom: 12px;
            color: #1a73e8;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .link-menu:hover {
            background: #f1f3f4;
            border-color: #1a73e8;
            transform: translateX(4px);
        }
        .link-menu span {
            margin-right: 12px;
            font-size: 20px;
        }
        .btn-logout {
            background: none;
            border: none;
            color: #5f6368;
            cursor: pointer;
            font-size: 14px;
            text-decoration: underline;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <div class="card-sistema" style="max-width: 600px;">
        <h2>Portal do Professor 🏫</h2>
        <p class="subtitulo">Gerenciamento e Configuração de Avaliações</p>

        <?php include_once "professor_menu.php"; ?>

        <form action="" method="GET">
            <button type="submit" name="logout" value="1" class="btn-logout">Sair do sistema</button>
        </form>
    </div>

</body>
</html>