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
    <?php include_once "theme.php"; ?>
    <style>
        body { background: var(--bg); color: var(--text); font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card-login { background: var(--surface); padding: 36px; border-radius: 12px; box-shadow: 0 20px 50px rgba(15,23,42,0.08); border: 1px solid var(--border); width: 360px; text-align: center; }
        .input-grupo input { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid #dbe6f8; box-sizing: border-box; background: var(--surface-alt); color: var(--text); }
        .btn-entrar { background-color: var(--accent); color: white; border: none; padding: 12px; width: 100%; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 12px; }
        .brand { font-weight: 700; margin-bottom: 6px; }
    </style>
</head>
<body>

    <div class="card-login">
        <h2 class="brand">Área do Aluno</h2>
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
