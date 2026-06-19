<?php
//===================================================================
// CONFIGURAÇÃO DOS VALORES DE PONTUAÇÃO (PREPARAÇÃO PARA O FUTURO)
//===================================================================
$total_questoes_sorteadas = count($sorteadas) > 0 ? count($sorteadas) : 5;
$valor_por_questao = 10 / $total_questoes_sorteadas; // Ex: 10 pontos / 5 questões = 2 pontos cada
?>

<style>
    body {
        background-color: #f0ebf8; /* Fundo lavanda bem claro clássico do Forms */
        color: #202124;
        font-family: 'Roboto', Helvetica, Arial, sans-serif;
    }
    .titulo-secao {
        color: #202124;
        font-size: 22px;
        margin: 30px 0 15px 0;
        font-weight: 400;
    }
    /* Container base da questão */
    .questao-box {
        background: #ffffff;
        border: 1px solid #dadce0;
        border-radius: 8px;
        margin-bottom: 20px;
        padding: 24px;
        position: relative;
        box-shadow: 0 1px 3px 0 rgba(60,64,67,0.3);
        overflow: hidden;
    }
    /* Faixas Laterais de Feedback */
    .questao-box.correta {
        border-left: 5px solid #137333; /* Verde Google */
        background-color: #f1f8f5;     /* Fundo verde suave */
    }
    .questao-box.errada {
        border-left: 5px solid #c5221f;  /* Vermelho Google */
        background-color: #fce8e6;      /* Fundo vermelho suave */
    }
    /* Badge de Pontos Superior Direito */
    .pontuacao-badge {
        position: absolute;
        top: 24px;
        right: 24px;
        font-size: 14px;
        color: #5f6368;
    }
    /* Enunciado */
    .enunciado-texto {
        font-size: 16px;
        line-height: 24px;
        margin: 0 0 20px 0;
        max-width: 85%;
        color: #202124;
    }
    /* Opções */
    .opcao-container {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        margin: 8px 0;
        border-radius: 4px;
        font-size: 14px;
    }
    /* Estilização dos inputs simulados */
    .radio-simulado {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 2px solid #5f6368;
        margin-right: 14px;
        display: inline-block;
        flex-shrink: 0;
        position: relative;
    }
    /* Marcador quando a opção foi a escolhida pelo aluno */
    .opcao-marcada .radio-simulado {
        border-color: #202124;
        background: radial-gradient(circle, #202124 40%, transparent 50%);
    }
    /* Destaques visuais das alternativas com base nos acertos/erros */
    .opcao-correta-aluno {
        background-color: #e6f4ea; /* Verde mais forte para o preenchimento da linha certa */
        font-weight: 500;
    }
    .opcao-errada-aluno {
        background-color: #fce8e6; /* Vermelho mais forte para a linha errada do aluno */
        border: 1px solid #fad2cf;
    }
    /* Bloco Inferior de Resposta Correta (Exclusivo para quando o aluno erra) */
    .feedback-resposta-correta {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #dadce0;
        font-size: 14px;
        color: #202124;
    }
    .feedback-resposta-correta strong {
        color: #c5221f;
        display: block;
        margin-bottom: 5px;
    }
    .texto-gabarito-revelado {
        display: flex;
        align-items: center;
        color: #137333;
        font-weight: 500;
        background: #ffffff;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #ceead6;
        margin-top: 5px;
    }
</style>

<h2 class="titulo-secao">Questões Respondidas</h2>

<?php 
$num = 1;

// 1. Limpeza e decodificação do JSON vindo do banco
$json_bruto = $prova['respostas_aluno'] ?? '';
if (is_array($json_bruto)) {
    $json_bruto = json_encode($json_bruto);
}
$json_limpo = stripslashes(trim($json_bruto, '"'));
$dados_resposta = json_decode($json_limpo, true) ?: [];

$sorteadas = $dados_resposta['questoes_sorteadas'] ?? [];
$alternativas_aluno = $dados_resposta['alternativas_aluno'] ?? [];

// 2. Loop pelas questões
foreach ($todas_questoes as $q): 
    $uuid_questao = trim($q['id']);

    // Se a questão não fez parte do sorteio do aluno, ignora
    if (!in_array($uuid_questao, $sorteadas)) {
        continue;
    }
    
    // Captura o voto do aluno
    $resposta_marcada = isset($alternativas_aluno[$uuid_questao]) ? intval($alternativas_aluno[$uuid_questao]) : -1;
    $gabarito = intval($q['resposta_correta']);
    $acertou = ($resposta_marcada === $gabarito);
    
    $opcoes = is_string($q['opcoes']) ? json_decode($q['opcoes'], true) : $q['opcoes'];
    
    // Formata os pontos atuais da questão (Pronto para o futuro!)
    $pontos_ganhos = $acertou ? number_format($valor_por_questao, 0) : '0';
    $pontos_maximos = number_format($valor_por_questao, 0);
?>
    <div class="questao-box <?php echo $acertou ? 'correta' : 'errada'; ?>">
        
        <div class="pontuacao-badge">
            <strong><?php echo $pontos_ganhos; ?></strong> / <?php echo $pontos_maximos; ?> pontos
        </div>

        <p class="enunciado-texto">
            <strong><?php echo $num++; ?>. </strong>
            <?php echo htmlspecialchars($q['enunciado']); ?>
        </p>

        <?php if (is_array($opcoes)): ?>
            <?php foreach ($opcoes as $idx => $texto_opcao): 
                $classe_opcao = '';
                
                // Se foi a alternativa que o aluno de fato marcou
                if ($idx === $resposta_marcada) {
                    $classe_opcao = $acertou ? 'opcao-correta-aluno opcao-marcada' : 'opcao-errada-aluno opcao-marcada';
                }
            ?>
                <div class="opcao-container <?php echo $classe_opcao; ?>">
                    <span class="radio-simulado"></span>
                    <span style="flex-grow: 1;"><?php echo htmlspecialchars($texto_opcao); ?></span>
                    
                    <?php if ($idx === $resposta_marcada): ?>
                        <span style="font-size: 12px; margin-left: 10px; opacity: 0.8;">
                            <?php echo $acertou ? '✔️ Resposta do aluno' : '❌ Resposta do aluno'; ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!$acertou && isset($opcoes[$gabarito])): ?>
            <div class="feedback-resposta-correta">
                <strong>Resposta correta</strong>
                <div class="texto-gabarito-revelado">
                    <span class="radio-simulado" style="border-color: #137333; background: radial-gradient(circle, #137333 40%, transparent 50%);"></span>
                    <?php echo htmlspecialchars($opcoes[$gabarito]); ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
<?php endforeach; ?>
</div>

</body>
</html>