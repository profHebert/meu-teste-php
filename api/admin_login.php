<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erro_login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_login'])) {
    $usuario_digitado = $_POST['usuario'] ?? '';
    $senha_digitada   = $_POST['senha'] ?? '';

    // 1. Consulta o Supabase via cURL filtrando pelo usuário digitado
    $url = "https://sua-url-do-supabase.supabase.co/rest/v1/professores?usuario=eq." . urlencode($usuario_digitado);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: SEU_ANON_KEY",
        "Authorization: Bearer SEU_ANON_KEY",
        "Content-Type: application/json"
    ]);
    
    $resposta = curl_exec($ch);
    curl_close($ch);
    
    $professores = json_decode($resposta, true) ?: [];

    // 2. Se o usuário existir, verifica a senha usando hash seguro
    if (!empty($professores) && isset($professores[0])) {
        $professor = $professores[0];
        
        // Compara a senha digitada com o hash do banco (Supabase usa padrão compatível com password_verify)
        if (password_verify($senha_digitada, $professor['senha_hash'])) {
            $_SESSION['professor_logado'] = true;
            $_SESSION['professor_nome']   = $professor['nome'];
            header("Location: ambiente_professor.php");
            exit;
        }
    }
    
    // Se falhar em qualquer etapa, exibe erro genérico (boa prática de segurança)
    $erro_login = "Usuário ou senha incorretos.";
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