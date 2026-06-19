<?php
// =========================================================================
// api/index.php - CONTROLADOR CENTRAL COM ROTEAMENTO CENTRALIZADO (SWITCH)
// =========================================================================

require_once "conexao.php";
require_once "gravar_historico.php";

// ... suas requisições de conexão anteriores ...

// 1. CAPTURA A PÁGINA SOLICITADA NA URL PARA O SWITCH ROTEADOR
$request_uri = $_SERVER['REQUEST_URI'];
$pagina_alvo = parse_url($request_uri, PHP_URL_PATH);
$pagina_nome = basename($pagina_alvo);

// Roteador de arquivos ADM e scripts diretos
switch ($pagina_nome) {
    case 'dashboard.php':
        include_once "dashboard.php";
        exit;
    case 'ver_prova':
    case 'ver_prova.php':
        include_once "ver_prova.php";
        exit;
    case 'ambiente_professor.php':
        include_once "ambiente_professor.php";
        exit;
}

// 🔥 NOVO: SE ACESSAR APENAS O DOMÍNIO / INDEX SEM ROTAS DE ALUNO, VAI PARA O LOGIN
$url_limpa = trim($pagina_alvo, '/');
if (empty($url_limpa) || $url_limpa === 'index.php') {
    include_once "admin_login.php";
    exit;
}

// ... Resto do fluxo normal de geração de prova de alunos (fecap, uninove, etc) ...

// =========================================================================
// 3. SE NÃO FOR UMA PÁGINA ADM, SEGUE O FLUXO NORMAL DE GERAÇÃO DA PROVA
// =========================================================================

// 1. CAPTURA DA URL COMPATÍVEL COM REQUISIÇÕES GET E POST NA VERCEL
$url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url_limpa = trim($url_path, '/');
$partes = array_values(array_filter(explode('/', $url_limpa)));

// Define a instituição e o código de prova garantindo que NUNCA fiquem nulos
$instituicao  = 'portal';
$codigo_prova = 'GERAL_atv1';

if (isset($partes[0]) && !empty($partes[0])) {
    $instituicao = strtolower($partes[0]);
}
if (isset($partes[1]) && !empty($partes[1])) {
    $codigo_prova = $partes[1];
}

// Extrai a sigla da disciplina (Ex: DBDSQL_6a_M_atv1 -> DBDSQL)
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

// ... resto do seu código (session_start, HTML, blocos de telas, etc.) ...


// // 0. DESVIO DE ROTA: CARREGA A CONEXÃO E O DASHBOARD IMEDIATAMENTE
// if (strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false) {
//     require_once "conexao.php";
//     include_once "dashboard.php";
//     exit;
// }

// require_once "conexao.php";
// require_once "gravar_historico.php"; // <--- ADICIONE ESTA LINHA AQUI

// // 1. CAPTURA DA URL COMPATÍVEL COM REQUISIÇÕES GET E POST NA VERCEL
// $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// $url_limpa = trim($url_path, '/');
// $partes = array_values(array_filter(explode('/', $url_limpa)));

// // Define a instituição e o código de prova garantindo que NUNCA fiquem nulos
// $instituicao  = 'portal';
// $codigo_prova = 'GERAL_atv1';

// if (isset($partes[0]) && !empty($partes[0])) {
//     $instituicao = strtolower($partes[0]);
// }
// if (isset($partes[1]) && !empty($partes[1])) {
//     $codigo_prova = $partes[1];
// }

// // Extrai a sigla da disciplina (Ex: DBDSQL_6a_M_atv1 -> DBDSQL)
// $partes_codigo = explode('_', $codigo_prova);
// $disciplina_url = (isset($partes_codigo[0]) && !empty($partes_codigo[0])) ? strtoupper($partes_codigo[0]) : 'DBDSQL';

// 2. CONFIGURAÇÃO DE IDENTIDADE VISUAL
switch ($instituicao) {
    case 'fecap':
        $nome_faculdade = "FECAP"; $cor_fundo = "#004d3d"; $cor_botao = "#deff9a"; $cor_texto_btn = "#004d3d"; break;
    case 'uninove':
        $nome_faculdade = "UNINOVE"; $cor_fundo = "#002d62"; $cor_botao = "#fbb034"; $cor_texto_btn = "#ffffff"; break;
    default:
        $nome_faculdade = "Portal de Provas"; $cor_fundo = "#1a1a1a"; $cor_botao = "#0070f3"; $cor_texto_btn = "#ffffff"; break;
}

// 3. BLINDAGEM DAS VARIÁVEIS DE CONTROLE DO FORMULÁRIO
$aluno_nome  = isset($_POST['nome']) ? $_POST['nome'] : '';
$aluno_ra    = isset($_POST['ra']) ? $_POST['ra'] : '';
$aluno_email = isset($_POST['email']) ? $_POST['email'] : '';
$acao        = isset($_POST['acao']) ? $_POST['acao'] : '';

$tela = 'identificacao'; 
$questoes_prova = [];
$erro = '';

// 4. PROCESSAMENTO DAS AÇÕES (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // AÇÃO 1: ALUNO DIGITOU OS DADOS E CLICOU EM INICIAR
    if ($acao === 'iniciar') {
        $endpoint_checa = "historico_provas?aluno_ra=eq." . urlencode($aluno_ra) . "&codigo_prova=eq." . urlencode($codigo_prova);
        $historico = consultarSupabase($endpoint_checa);
        
        // Verifica se o histórico retornou dados válidos
        if (is_array($historico) && !empty($historico) && isset($historico[0])) {
            $registro = $historico[0];
            
            if (isset($registro['status']) && $registro['status'] === 'concluida') {
                $tela = 'resultado_ja_feito';
                $nota_final = isset($registro['nota_final']) ? $registro['nota_final'] : 0;
            } else {
                // ANTI-FRAUDE: Aluno deu F5. Recupera as mesmas questões
                $tela = 'prova';
                $dados_salvos = (isset($registro['respostas_aluno']) && is_string($registro['respostas_aluno'])) ? json_decode($registro['respostas_aluno'], true) : ($registro['respostas_aluno'] ?? []);
                $ids_sorteados = $dados_salvos['questoes_sorteadas'] ?? [];
                
                if (!empty($ids_sorteados)) {
                    $ids_string = implode(',', $ids_sorteados);
                    $resultado_busca = consultarSupabase("questoes?id=in.(" . $ids_string . ")");
                    $questoes_prova = is_array($resultado_busca) ? $resultado_busca : [];
                }
            }
        } else {
            // Primeira vez acessando. Define filtros de aulas baseados na sigla da atividade
            $aulas_filtro = "in.(1,2,3,4,5,6,7)"; // Expandido para abranger mais aulas do seu banco
            if (strpos($codigo_prova, 'atv2') !== false) $aulas_filtro = "in.(5,6,7,8)";
            
            $endpoint_questoes = "questoes?disciplina=eq." . $disciplina_url . "&numero_aula=" . $aulas_filtro . "&ativa=eq.true";
            $universo_questoes = consultarSupabase($endpoint_questoes);
            
            if (is_array($universo_questoes) && !empty($universo_questoes)) {
                shuffle($universo_questoes);
                $limite = min(5, count($universo_questoes));
                $questoes_prova = array_slice($universo_questoes, 0, $limite);
                
                $ids_sorteados = array_column($questoes_prova, 'id');
                
                
                // Registra o início da prova no Supabase como 'em_andamento'
                $dados_insert = [
                    "aluno_nome" => $aluno_nome,
                    "aluno_ra" => $aluno_ra,
                    "aluno_email" => $aluno_email,
                    "instituicao" => $instituicao,
                    "turma" => $codigo_prova,        // <-- Força o código da URL (ex: DBDSQL_6a_M_atv1)
                    "codigo_prova" => $codigo_prova, // <-- Garante a mesma coisa aqui
                    "numero_aula" => 1,              // Mudado de 0 para 1 para não sumir nos filtros
                    "nota_final" => 0.00,
                    "status" => "em_andamento",
                    "respostas_aluno" => json_encode(["questoes_sorteadas" => $ids_sorteados])
                ];
                
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
                $erro = "Nenhuma questão encontrada para a disciplina " . $disciplina_url . " com os filtros aplicados.";
                $tela = 'identificacao';
            }
        }
    }
    
    // AÇÃO 2: ALUNO MARCOU AS RESPOSTAS E CLICOU EM FINALIZAR
    if ($acao === 'finalizar') {
        $ids_enviados = $_POST['questoes_ids'] ?? [];
        $respostas_aluno = $_POST['respostas'] ?? [];
        
        $total_questoes = count($ids_enviados);
        $acertos = 0;
        
        if ($total_questoes > 0) {
            $ids_string = implode(',', $ids_enviados);
            $questoes_originais = consultarSupabase("questoes?id=in.(" . $ids_string . ")");
            
            $questoes_focadas = [];
            if (is_array($questoes_originais)) {
                foreach ($questoes_originais as $qo) {
                    $questoes_focadas[$qo['id']] = $qo;
                }
            }
            
            foreach ($ids_enviados as $qid) {
                if (isset($questoes_focadas[$qid])) {
                    $resposta_dada = isset($respostas_aluno[$qid]) ? intval($respostas_aluno[$qid]) : -1;
                    $resposta_certa = intval($questoes_focadas[$qid]['resposta_correta']);
                    
                    if ($resposta_dada === $resposta_certa) {
                        $acertos++;
                    }
                }
            }
            
            $nota_final = ($acertos / $total_questoes) * 10;
            
            // $dados_update = [
            //     "nota_final" => $nota_final,
            //     "status" => "concluida",
            //     "respostas_aluno" => json_encode([
            //         "questoes_sorteadas" => $ids_enviados,
            //         "escolhas_aluno" => $respostas_aluno
            //     ])
            // ];

            // $dados_update = [
            //     "nota_final" => floatval($nota_final),
            //     "status" => "finalizada"
            // ];

            // // Dispara a atualização isolada passando o RA
            // $retorno_update = salvarNoHistorico($dados_update, "PATCH", $aluno_ra);

            $nota_final = ($acertos / $total_questoes) * 10;
            
            // 🔥 SOLUÇÃO: Junta as questões sorteadas e o que o aluno clicou no mesmo JSON
            $respostas_completas = [
                "questoes_sorteadas" => $ids_enviados,
                "alternativas_aluno" => $respostas_aluno // Salva o array [id_da_questao => opcao_marcada]
            ];

            $dados_update = [
                "nota_final" => floatval($nota_final),
                "status" => "concluida", // Alterado para bater com a verificação de 'concluida' do início do arquivo
                "respostas_aluno" => json_encode($respostas_completas, JSON_UNESCAPED_UNICODE) // Grava o objeto completo
            ];

            // Dispara a atualização unificada passando o RA para o seu arquivo auxiliar
            $retorno_update = salvarNoHistorico($dados_update, "PATCH", $aluno_ra);

            // Se o Supabase recusar a atualização da nota, avisa na tela
            if ($retorno_update['codigo'] >= 400) {
                die("Erro ao FINALIZAR histórico (HTTP " . $retorno_update['codigo'] . "): " . print_r($retorno_update['resposta'], true));
            }

            $tela = 'resultado_final';
            
            // Nota: Removido o segundo bloco cURL duplicado que rodava logo abaixo 
            // para evitar o envio de requisições redundantes ao Supabase.

            // Se o Supabase recusar a atualização da nota, avisa na tela
            if ($retorno_update['codigo'] >= 400) {
                die("Erro ao FINALIZAR histórico (HTTP " . $retorno_update['codigo'] . "): " . print_r($retorno_update['resposta'], true));
            }

            // $tela = 'resultado_final';
            $tela = 'prova';
            
            //$url_update = $GLOBALS['supabase_url'] . "/rest/v1/historico_provas?aluno_ra=eq." . urlencode($aluno_ra) . "&codigo_prova=eq." . urlencode($codigo_prova);
            
            // Filtra puramente pelo RA do aluno que iniciou a sessão para aplicar a nota
            $url_update = $GLOBALS['supabase_url'] . "/rest/v1/historico_provas?aluno_ra=eq." . urlencode($aluno_ra);
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

                <?php if (!empty($questoes_prova)): ?>
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
                                shuffle($opcoes_mapeadas); 
                                
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
                <?php else: ?>
                    <p>Nenhuma questão foi carregada. Verifique os filtros de disciplina e aulas.</p>
                <?php endif; ?>
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