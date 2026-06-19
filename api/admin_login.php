<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erro_login = '';

// Definição de credencial 
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso do Professor</title>
    
    <style>
        body {
            background-color: #f0ebf8; /* Lavanda claro clássico do Forms */
            color: #202124;
            font-family: 'Roboto', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .card-sistema {
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 8px;
            border: 1px solid #dadce0;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 1px 3px 0 rgba(60,64,67,0.3);
            box-sizing: border-box;
        }

        h2 {
            color: #202124;
            font-size: 28px;
            margin: 0 0 10px 0;
            font-weight: 400;
            text-align: center;
        }

        .subtitulo {
            font-size: 15px;
            color: #5f6368;
            margin: 0 0 30px 0;
            text-align: center;
        }

        .input-grupo {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-grupo label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #202124;
        }

        .input-grupo input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 4px;
            border: 1px solid #dadce0;
            background: #ffffff;
            color: #202124;
            box-sizing: border-box;
            font-size: 15px;
            transition: border-color 0.2s;
        }

        .input-grupo input:focus {
            border-color: #4285f4; /* Azul Google focus */
            outline: none;
        }

        .btn-acao {
            background-color: #673ab7; /* Roxo clássico do Google Forms */
            color: #ffffff;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            font-size: 15px;
            margin-top: 10px;
            transition: background 0.2s;
            box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3);
        }

        .btn-acao:hover {
            background-color: #512da8;
        }

        .erro {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #fad2cf;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>
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
                <label>Usuário</label>
                <input type="text" name="usuario" required placeholder="Ex: admin">
            </div>
            
            <div class="input-grupo">
                <label>Senha de Acesso</label>
                <input type="password" name="senha" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn-acao">Entrar no Painel</button>
        </form>
    </div>

</body>
</html>