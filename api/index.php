<?php
// 1. Captura a rota real digitada no navegador
$requisicao = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = trim($requisicao, '/');
$partes = explode('/', $url);

// Identifica os parâmetros da URL
$instituicao = (!empty($partes[0])) ? $partes[0] : 'Nenhuma';
$parametro2  = (!empty($partes[1])) ? $partes[1] : '';
$parametro3  = (!empty($partes[2])) ? $partes[2] : '';

// 2. Define a identidade visual baseado na instituição
switch (strtolower($instituicao)) {
    case 'fecap':
        $nome_faculdade = "FECAP";
        $cor_fundo      = "#004d3d"; // Verde FECAP
        $cor_botao      = "#deff9a"; // Amarelo/Verde claro
        $cor_texto_btn  = "#004d3d";
        break;
        
    case 'uninove':
        $nome_faculdade = "UNINOVE";
        $cor_fundo      = "#002d62"; // Azul UNINOVE
        $cor_botao      = "#fbb034"; // Amarelo/Laranja
        $cor_texto_btn  = "#ffffff";
        break;
        
    default:
        $nome_faculdade = "Portal de Provas";
        $cor_fundo      = "#1a1a1a"; // Cinza escuro neutro
        $cor_botao      = "#0070f3"; // Azul padrão
        $cor_texto_btn  = "#ffffff";
        break;
}

// 3. Lógica de Roteamento: É tela de Login ou de Prova?
$mostrar_login = false;

if ($parametro2 === 'login' || empty($parametro2)) {
    // Se digitou /fecap/login ou apenas /fecap
    $mostrar_login = true;
} else {
    // Se digitou /fecap/ads-3a/prova1 (3 parâmetros)
    $turma = $parametro2;
    $prova = $parametro3;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo $nome_faculdade; ?> - Sistema de Avaliação</title>
    <style>
        body {
            background-color: <?php echo $cor_fundo; ?>;
            color: #ffffff;
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            transition: background 0.5s ease; /* Transição suave de cor */
        }
        .card-sistema {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 350px;
            text-align: center;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }
        .input-grupo input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(0,0,0,0.2);
            color: #fff;
            box-sizing: border-box;
        }
        .input-grupo input::placeholder { color: rgba(255,255,255,0.6); }
        .btn-acao {
            background-color: <?php echo $cor_botao; ?>;
            color: <?php echo $cor_texto_btn; ?>;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
            font-size: 16px;
        }
        .destaque { color: #deff9a; font-weight: bold; }
    </style>
</head>
<body>

    <div class="card-sistema">
        
        <?php if ($mostrar_login): ?>
            <h2>🎓 Área do Aluno</h2>
            <h3><?php echo $nome_faculdade; ?></h3>
            <p>Insira as suas credenciais institucionais.</p>
            
            <form action="" method="POST">
                <div class="input-grupo">
                    <input type="email" placeholder="E-mail Institucional" required>
                </div>
                <div class="input-grupo">
                    <input type="password" placeholder="Senha" required>
                </div>
                <button type="button" class="btn-acao" onclick="alert('Funcionalidade de Login será conectada ao Supabase em breve!')">Acessar Sistema</button>
            </form>

        <?php else: ?>
            <h2>📝 Prova Ativa</h2>
            <h3><?php echo $nome_faculdade; ?></h3>
            <div style="text-align: left; margin-top: 20px; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px;">
                <p>👥 Turma: <span class="destaque"><?php echo htmlspecialchars($turma); ?></span></p>
                <p>📄 Avaliação: <span class="destaque"><?php echo htmlspecialchars($prova); ?></span></p>
            </div>
            <button class="btn-acao">Iniciar Prova</button>
        <?php endif; ?>

    </div>

</body>
</html>
