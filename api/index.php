<?php
require_once "conexao.php";

// 1. CAPTURA E PARSE DA URL PROTEGIDO PARA VERCEL
$requisicao = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = trim($requisicao, '/');

// Filtra elementos vazios causados por barras duplicadas
$partes = array_values(array_filter(explode('/', $url)));

// Define valores padrão seguros caso a URL venha incompleta
$instituicao  = (isset($partes[0]) && !empty($partes[0])) ? strtolower($partes[0]) : 'portal';
$codigo_prova = (isset($partes[1]) && !empty($partes[1])) ? $partes[1] : 'GERAL_atv1';

// Extrai a disciplina do código da prova de forma segura (Ex: DBDSQL_6a_M_atv1 -> DBDSQL)
$partes_codigo = explode('_', $codigo_prova);
$disciplina_url = (isset($partes_codigo[0]) && !empty($partes_codigo[0])) ? strtoupper($partes_codigo[0]) : 'DBDSQL';

// 2. CONFIGURAÇÃO DE IDENTIDADE VISUAL
switch ($instituicao) {
    case 'fecap':
        $nome_faculdade = "FECAP"; $cor_fundo = "#004d3d"; $cor_botao = "#deff9a"; $cor_texto_btn = "#004d3d"; break;
    case 'uninove':
        $nome_faculdade = "UNINOVE"; $cor_fundo = "#002d62"; $cor_botao = "#fbb034"; $cor_texto_btn = "#ffffff"; break;
    default:
        $nome_faculdade = "Portal de Provas"; $cor_fundo = "#1a1a1a"; $cor_botao = "#0070f3"; $cor_texto_btn = "#ffffff"; break;
}

// 3. CONTROLE DE FLUXO DE TELAS
$tela = 'identificacao'; 
$questoes_prova = [];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    //---------------------------------------------------------
    // AÇÃO 1: ALUNO DIGITOU OS DADOS E CLICOU EM INICIAR
    //---------------------------------------------------------
    if ($acao === 'iniciar') {
        // Passo A: Verificar se este RA já tem uma prova iniciada ou concluída para este código de prova
        $endpoint_checa = "historico_provas?aluno_ra=eq." . urlencode($aluno_ra) . "&codigo_prova=eq." . urlencode($codigo_prova);
        $historico = consultarSupabase($endpoint_checa);
        
        if (is_array($historico) && !empty($historico)) {
            $registro = $historico[0];
            
            if ($registro['status'] === 'concluida') {
                $tela = 'resultado_ja_feito';
                $nota_final = $registro['nota_final'];
            } else {
                // ANTI-FRAUDE: Aluno deu F5. Recupera as mesmas questões que foram sorteadas antes
                $tela = 'prova';
                $dados_salvos = is_string($registro['respostas_aluno']) ? json_decode($registro['respostas_aluno'], true) : $registro['respostas_aluno'];
                $ids_sorteados = $dados_salvos['questoes_sorteadas'] ?? [];
                
                if (!empty($ids_sorteados)) {
                    // Busca exatamente os IDs que tinham sido sorteados
                    $ids_string = implode(',', $ids_sorteados);
                    $questoes_prova = consultarSupabase("questoes?id=in.(" . $ids_string . ")");
                }
            }
        } else {
            // Passo B: Primeira vez acessando. Vamos criar as regras de filtro baseadas na atividade
            // Exemplo: se for atv1, filtra aulas de 1 a 4 (ou 5 se for o caso do seu banco atual)
            $aulas_filtro = "in.(1,2,3,4,5)"; 
            if (strpos($codigo_prova, 'atv2') !== false) $aulas_filtro = "in.(5,6,7,8)";
            
            // Busca o universo de questões da disciplina e aulas permitidas
            $endpoint_questoes = "questoes?disciplina=eq." . $disciplina_url . "&numero_aula=" . $aulas_filtro . "&ativa=eq.true";
            $universo_questoes = consultarSupabase($endpoint_questoes);
            
            if (is_array($universo_questoes) && !empty($universo_questoes)) {
                // Sorteia e limita a quantidade (ex: pegar até 20 questões, ou o total disponível)
                shuffle($universo_questoes);
                $limite = min(20, count($universo_questoes));
                $questoes_prova = array_slice($universo_questoes, 0, $limite);
                
                // Salva os IDs sorteados para travar o F5
                $ids_sorteados = array_column($questoes_prova, 'id');
                
                // Registra o início da prova no Supabase como 'em_andamento'
                $dados_insert = [
                    "aluno_nome" => $aluno_nome,
                    "aluno_ra" => $aluno_ra,
                    "aluno_email" => $aluno_email,
                    "instituicao" => $instituicao,
                    "turma" => $codigo_prova,
                    "codigo_prova" => $codigo_prova,
                    "numero_aula" => 0, // Controlado pelo escopo global da prova
                    "nota_final" => 0.00,
                    "status" => "em_andamento",
                    "respostas_aluno" => json_encode(["questoes_sorteadas" => $ids_sorteados])
                ];
                
                // Envia comando INSERT para o Supabase
                $ch = curl_init($GLOBALS['supabase_url'] . "/rest/v1/historico_provas");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados_insert));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "apikey: " . $GLOBALS['supabase_key'],
                    "Authorization: Bearer " . $GLOBALS['supabase_key'],
                    "Content-Type: application/json"
                ]);
                curl_exec($ch);
                curl_close($ch);
                
                $tela = 'prova';
            } else {
                $erro = "Nenhuma questão cadastrada para a disciplina " . $disciplina_url . " nestas aulas.";
                $tela = 'identificacao';
            }
        }
    }
    
    //---------------------------------------------------------
    // AÇÃO 2: ALUNO MARCOU AS RESPOSTAS E CLICOU EM FINALIZAR
    //---------------------------------------------------------
    if ($acao === 'finalizar') {
        $ids_enviados = $_POST['questoes_ids'] ?? [];
        $respostas_aluno = $_POST['respostas'] ?? [];
        
        $total_questoes = count($ids_enviados);
        $acertos = 0;
        
        if ($total_questoes > 0) {
            $ids_string = implode(',', $ids_enviados);
            $questoes_originais = consultarSupabase("questoes?id=in.(" . $ids_string . ")");
            
            // Indexa as questões originais pelo ID para conferência rápida
            $questoes_focadas = [];
            foreach ($questoes_originais as $qo) {
                $questoes_focadas[$qo['id']] = $qo;
            }
            
            // Correção da prova
            foreach ($ids_enviados as $qid) {
                $resposta_dada = isset($respostas_aluno[$qid]) ? intval($respostas_aluno[$qid]) : -1;
                $resposta_certa = intval($questoes_focadas[$qid]['resposta_correta']);
                
                if ($resposta_dada === $resposta_certa) {
                    $acertos++;
                }
            }
            
            // Calcula nota de 0 a 10
            $nota_final = ($acertos / $total_questoes) * 10;
            
            // Atualiza o registro no Supabase para 'concluida' e grava a nota real
            $dados_update = [
                "nota_final" => $nota_final,
                "status" => "concluida",
                "respostas_aluno" => json_encode([
                    "questoes_sorteadas" => $ids_enviados,
                    "escolhas_aluno" => $respostas_aluno
                ])
            ];
            
            // Requisição PATCH para atualizar a linha do aluno pelo RA e Prova
            $url_update = $GLOBALS['supabase_url'] . "/rest/v1/historico_provas?aluno_ra=eq." . urlencode($aluno_ra) . "&codigo_prova=eq." . urlencode($codigo_prova);
            $ch = curl_init($url_update);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados_update));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "apikey: " . $GLOBALS['supabase_key'],
                "Authorization: Bearer " . $GLOBALS['supabase_key'],
                "Content-Type: application/json"
            ]);
            curl_exec($ch);
            curl_close($ch);
            
            $tela = 'resultado_final';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo $nome_faculdade; ?> - Avaliação</title>
    <style>
        body { background-color: <?php echo $cor_fundo; ?>; color: #ffffff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .card-sistema { background: rgba(255, 255, 255, 0.1); padding: 30px; border-radius: 12px; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); width: 100%; max-width: 600px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); }
        .input-grupo input { width: 100%; padding: 12px; margin: 8px 0; border-radius: 6px; border: 1px solid rgba(255,255,255,0.3); background: rgba(0,0,0,0.4); color: #fff; box-sizing: border-box; }
        .btn-acao { background-color: <?php echo $cor_botao; ?>; color: <?php echo $cor_texto_btn; ?>; border: none; padding: 14px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 20px; font-size: 16px; }
        .questao-bloco { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: left; border: 1px solid rgba(255,255,255,0.05); }
        .opcao-item { display: block; margin: 10px 0; cursor: pointer; padding: 8px; border-radius: 4px; background: rgba(255,255,255,0.02); }
        .opcao-item:hover { background: rgba(255,255,255,0.1); }
        .erro { background: #ff4a4a; padding: 10px; border-radius: 6px; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>

    <div class="card-sistema">
        <h2><?php echo $nome_faculdade; ?></h2>
        <p style="font-size:13px; opacity:0.7;">Prova Ativa: <?php echo htmlspecialchars($codigo_prova); ?></p>
        
        <?php if ($erro): ?><div class="erro"><?php echo $erro; ?></div><?php endif; ?>

        <?php if ($tela === 'identificacao'): ?>
            <h3>Identificação do Estudante</h3>
            <form action="" method="POST">
                <input type="hidden" name="acao" value="iniciar">
                <div class="input-grupo"><input type="text" name="nome" placeholder="Nome Completo" required></div>
                <div class="input-grupo"><input type="text" name="ra" placeholder="RA" required></div>
                <div class="input-grupo"><input type="email" name="email" placeholder="E-mail Institucional" required></div>
                <button type="submit" class="btn-acao">Iniciar Avaliação</button>
            </form>

        <?php elseif ($tela === 'prova'): ?>
            <div style="background: rgba(0,0,0,0.3); padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: left; font-size: 14px;">
                🧑‍🎓 Aluno: <b><?php echo htmlspecialchars($aluno_nome); ?></b> | RA: <b><?php echo htmlspecialchars($aluno_ra); ?></b>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="acao" value="finalizar">
                <input type="hidden" name="nome" value="<?php echo htmlspecialchars($aluno_nome); ?>">
                <input type="hidden" name="ra" value="<?php echo htmlspecialchars($aluno_ra); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($aluno_email); ?>">

                <?php foreach ($questoes_prova as $index => $q): ?>
                    <input type="hidden" name="questoes_ids[]" value="<?php echo $q['id']; ?>">
                    <div class="questao-bloco">
                        <p><strong>Questão <?php echo $index + 1; ?>:</strong></p>
                        <p><?php echo htmlspecialchars($q['enunciado']); ?></p>
                        
                        <?php 
                        $opcoes = is_string($q['opcoes']) ? json_decode($q['opcoes'], true) : $q['opcoes'];
                        if (is_array($opcoes)): 
                            $opcoes_mapeadas = [];
                            foreach ($opcoes as $i => $texto) {
                                $opcoes_mapeadas[] = ['id_original' => $i, 'texto' => $texto];
                            }
                            shuffle($opcoes_mapeadas); // Embaralha as alternativas para este aluno
                            
                            foreach ($opcoes_mapeadas as $opt): 
                        ?>
                            <label class="opcao-item">
                                <input type="radio" name="respostas[<?php echo $q['id']; ?>]" value="<?php echo $opt['id_original']; ?>" required>
                                <?php echo htmlspecialchars($opt['texto']); ?>
                            </label>
                        <?php 
                            endforeach;
                        endif; 
                        ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn-acao">Finalizar e Enviar Prova</button>
            </form>

        <?php elseif ($tela === 'resultado_final'): ?>
            <h3>Avaliação Concluída!</h3>
            <p>Sua prova foi processada e as respostas foram salvas com sucesso no banco de dados.</p>
            <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; font-size: 20px; margin: 20px 0;">
                Sua Nota Final: <b style="color: #deff9a;"><?php echo number_format($nota_final, 2, ',', '.'); ?></b>
            </div>
            <p style="font-size:12px; opacity:0.6;">O gabarito detalhado será liberado pelo professor posteriormente.</p>

        <?php elseif ($tela === 'resultado_ja_feito'): ?>
            <h3>Acesso Negado</h3>
            <p>Identificamos que o RA <b><?php echo htmlspecialchars($aluno_ra); ?></b> já concluiu esta avaliação anteriormente.</p>
            <div style="background: rgba(255,0,0,0.2); padding: 15px; border-radius: 6px; margin: 20px 0;">
                Nota Registrada: <b><?php echo number_format($nota_final, 2, ',', '.'); ?></b>
            </div>
            <p style="font-size:13px;">Não é permitido refazer a prova ou submeter novas respostas.</p>
        <?php endif; ?>

    </div>

</body>
</html>
