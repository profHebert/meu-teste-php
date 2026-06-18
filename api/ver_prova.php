<?php
// api/ver_prova.php - VISUALIZADOR DE PROVA CORRIGIDA
require_once "conexao.php";

// 1. Captura e validação do ID enviado via URL
$id_historico = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($id_historico)) {
    die("<h3>Erro: ID do histórico não fornecido.</h3>");
}

// 2. Montagem da URL limpa e requisição cURL com cabeçalhos explícitos para o Supabase
$url_historico = rtrim($supabase_url, '/') . "/rest/v1/historico_provas?id=eq." . $id_historico;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_historico);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: " . $supabase_key,
    "Authorization: Bearer " . $supabase_key,
    "Content-Type: application/json",
    "Accept: application/json"
]);

$resposta = curl_exec($ch);
curl_close($ch);

// 3. Decodificação dos dados do histórico do aluno
$dados_aluno = json_decode($resposta, true);

if (empty($dados_aluno) || isset($dados_aluno['code'])) {
    echo "<h3>Erro ao buscar os dados da prova.</h3>";
    echo "Retorno do banco: <pre>"; print_r($dados_aluno); echo "</pre>";
    exit;
}

$prova = $dados_aluno[0];
$respostas_aluno = json_decode($prova['respostas_aluno'], true) ?: [];

// 4. Identificação da disciplina a partir do código da prova para buscar o gabarito das questões
$prova_parts = explode('_', $prova['codigo_prova']);
$disciplina_sigla = $prova_parts[0] ?? '';

$url_questoes = rtrim($supabase_url, '/') . "/rest/v1/questoes?disciplina=eq." . urlencode($disciplina_sigla);

$ch_questoes = curl_init();
curl_setopt($ch_questoes, CURLOPT_URL, $url_questoes);
curl_setopt($ch_questoes, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_questoes, CURLOPT_HTTPHEADER, [
    "apikey: " . $supabase_key,
    "Authorization: Bearer " . $supabase_key,
    "Content-Type: application/json",
    "Accept: application/json"
]);

$resposta_questoes = curl_exec($ch_questoes);
curl_close($ch_questoes);

$todas_questoes = json_decode($resposta_questoes, true) ?: [];

// 5. Indexação das questões pelo ID para busca rápida e renderização do HTML
$questoes_indexadas = [];
foreach ($todas_questoes as $q) {
    $questoes_indexadas[$q['id']] = $q;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Correção Detalhada - <?php echo htmlspecialchars($prova['aluno_nome']); ?></title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #121214; color: #e1e1e6; padding: 40px; margin: 0; }
        .container { max-width: 800px; margin: 0 auto; background: #202024; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.5); }
        .header { border-bottom: 2px solid #29292e; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #04d361; font-size: 24px; }
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; font-size: 14px; color: #a8a8b3; }
        .badge-nota { font-size: 28px; font-weight: bold; color: #04d361; text-align: right; }
        .questao-box { background: #29292e; padding: 20px; border-radius: 6px; margin-bottom: 25px; border-left: 6px solid #48484a; }
        .questao-box.correta { border-left-color: #04d361; }
        .questao-box.errada { border-left-color: #f75a68; }
        .opcao { padding: 10px; margin: 8px 0; border-radius: 4px; background: #202024; font-size: 15px; }
        .opcao.marcada-errada { background: #f75a68; color: #fff; font-weight: bold; }
        .opcao.gabarito { background: #04d361; color: #fff; font-weight: bold; }
        .btn-voltar { display: inline-block; background: #48484a; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-bottom: 20px; }
        .btn-voltar:hover { background: #5a5a5c; }
        .status-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-badge.c { background: #123924; color: #04d361; }
        .status-badge.e { background: #411a1d; color: #f75a68; }
    </style>
</head>
<body>

<div class="container">
    <a href="javascript:history.back()" class="btn-voltar">← Voltar ao Painel</a>
    
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Prova Corrigida de <?php echo htmlspecialchars($prova['aluno_nome']); ?></h1>
                <div class="meta-grid">
                    <div><strong>RA:</strong> <?php echo htmlspecialchars($prova['aluno_ra']); ?></div>
                    <div><strong>Avaliação:</strong> <?php echo htmlspecialchars($prova['codigo_prova']); ?></div>
                    <div><strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i', strtotime($prova['created_at'])); ?></div>
                </div>
            </div>
            <div class="badge-nota">
                Nota: <?php echo number_format($prova['nota_final'], 2, ',', '.'); ?>
            </div>
        </div>
    </div>

    <h2>Questões Respondidas</h2>

    <?php 
    $num = 1;
    foreach ($respostas_aluno as $id_questao => $resposta_marcada): 
        if (!isset($questoes_indexadas[$id_questao])) continue;
        
        $q = $questoes_indexadas[$id_questao];
        $gabarito = intval($q['resposta_correta']);
        $resp_aluno = intval($resposta_marcada);
        $acertou = ($resp_aluno === $gabarito);
        
        $opcoes = is_string($q['opcoes']) ? json_decode($q['opcoes'], true) : $q['opcoes'];
    ?>
        <div class="questao-box <?php echo $acertou ? 'correta' : 'errada'; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <strong>Questão <?php echo $num++; ?></strong>
                <span class="status-badge <?php echo $acertou ? 'c' : 'e'; ?>">
                    <?php echo $acertou ? 'Acertou' : 'Errou'; ?>
                </span>
            </div>
            <p style="margin: 0 0 15px 0; font-size: 16px;"><?php echo htmlspecialchars($q['enunciado']); ?></p>

            <?php if (is_array($opcoes)): ?>
                <?php foreach ($opcoes as $idx => $texto_opcao): 
                    $classe_opcao = '';
                    if ($idx === $gabarito) {
                        $classe_opcao = 'gabarito';
                    } elseif ($idx === $resp_aluno && !$acertou) {
                        $classe_opcao = 'marcada-errada';
                    }
                ?>
                    <div class="opcao <?php echo $classe_opcao; ?>">
                        <?php 
                        if ($idx === $resp_aluno) echo "🔵 ";
                        echo htmlspecialchars($texto_opcao); 
                        if ($idx === $gabarito) echo " ✔ (Gabarito)";
                        if ($idx === $resp_aluno && !$acertou) echo " ❌ (Marcada pelo aluno)";
                        ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>