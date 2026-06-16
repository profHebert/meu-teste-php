<?php
// Captura o que foi digitado na URL
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$partes = explode('/', $url);

// Preenche as variáveis baseado nas posições da URL
$instituicao = $partes[0] ?? 'Nenhuma';
$turma       = $partes[1] ?? 'Nenhuma';
$prova       = $partes[2] ?? 'Nenhuma';
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
