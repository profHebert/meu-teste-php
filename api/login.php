<?php
// 1. Captura a instituição da URL (ex: /fecap/login)
$requisicao = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = trim($requisicao, '/');
$partes = explode('/', $url);

$instituicao = (!empty($partes[0])) ? $partes[0] : 'default';

// 2. Define as cores e detalhes baseado na instituição
switch ($instituicao) {
    case 'fecap':
        $nome_faculdade = "FECAP";
        $cor_fundo      = "#004d3d"; // Verde FECAP
        $cor_botao      = "#deff9a"; // Amarelo/Verde claro de destaque
        $cor_texto_btn  = "#004d3d";
        break;
        
    case 'uninove':
        $nome_faculdade = "UNINOVE";
        $cor_fundo      = "#002d62"; // Azul UNINOVE
        $cor_botao      = "#fbb034"; // Amarelo/Laranja de destaque
        $cor_texto_btn  = "#ffffff";
        break;
        
    default:
        // Caso acesse apenas /login sem especificar a faculdade
        $nome_faculdade = "Portal Global de Provas";
        $cor_fundo      = "#1a1a1a"; // Cinza escuro neutro
        $cor_botao      = "#0070f3"; // Azul Vercel/Padrão
        $cor_texto_btn  = "#ffffff";
        break;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - <?php echo $nome_faculdade; ?></title>
    <style>
        /* 3. Injeta as variáveis do PHP direto no CSS! */
        body {
            background-color: <?php echo $cor_fundo; ?>;
            color: #ffffff;
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card-login {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 300px;
            text-align: center;
        }
        .input-grupo input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
            border: none;
            box-sizing: border-box;
        }
        .btn-entrar {
            background-color: <?php echo $cor_botao; ?>;
            color: <?php echo $cor_texto_btn; ?>;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <div class="card-login">
        <h2>Área do Aluno</h2>
        <h3><?php echo $nome_faculdade; ?></h3>
        
        <form action="" method="POST">
            <div class="input-grupo">
                <input type="email" placeholder="E-mail Institucional" required>
            </div>
            <div class="input-grupo">
                <input type="password" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn-entrar">Acessar Prova</button>
        </form>
    </div>

</body>
</html>
