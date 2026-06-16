<?php
// Captura a rota real digitada no navegador (ex: /fecap/ads-3a/prova1)
$requisicao = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Limpa as barras das pontas e quebra o texto
$url = trim($requisicao, '/');
$partes = explode('/', $url);

// Preenche as variáveis baseado nas posições da URL
$instituicao = (!empty($partes[0])) ? $partes[0] : 'Nenhuma';
$turma       = (!empty($partes[1])) ? $partes[1] : 'Nenhuma';
$prova       = (!empty($partes[2])) ? $partes[2] : 'Nenhuma';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Teste do Professor</title>
    <style>
        body { background: #004d3d; color: #fff; font-family: sans-serif; text-align: center; padding-top: 50px; }
        .painel { background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px; display: inline-block; text-align: left; border: 1px solid rgba(255,255,255,0.2); }
        .destaque { color: #deff9a; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🎓 Portal de Provas do Professor</h1>
    <p>Se você está vendo esta tela, o PHP funcionou na Vercel!</p>

    <div class="painel">
        <p>🏫 Faculdade: <span class="destaque"><?php echo htmlspecialchars($instituicao); ?></span></p>
        <p>👥 Turma: <span class="destaque"><?php echo htmlspecialchars($turma); ?></span></p>
        <p>📝 Prova: <span class="destaque"><?php echo htmlspecialchars($prova); ?></span></p>
    </div>
</body>
</html>
