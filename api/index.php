<?php
// =========================================================================
// api/index.php - CONTROLADOR CENTRAL DA AVALIAÇÃO (VERSÃO POST DEFINITIVO)
// =========================================================================

// 0. DESVIO DE ROTA: SE FOR O DASHBOARD, CARREGA IMEDIATAMENTE
if (strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false) {
    require_once "conexao.php";
    include_once "dashboard.php";
    exit;
}

require_once "conexao.php";
require_once "gravar_historico.php";

session_start();

// 1. CAPTURA DOS PARÂMETROS DA URL
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/uninove/';
$pos = strpos($request_uri, $base_path);

if ($pos !== false) {
    $param_str = substr($request_uri, $pos + strlen($base_path));
    $parts = explode('/', $param_str);
    $codigo_prova = clean_input($parts[0] ?? '');
} else {
    $codigo_prova = clean_input($_GET['prova'] ?? '');
}

if (empty($codigo_prova)) {
    die("<h3>Erro: Código da prova não fornecido na URL. Exemplo correto: /uninove/DBDSQL_6a_M_atv1</h3>");
}

// Quebra o código da prova para identificar a disciplina (ex: DBDSQL)
$prova_parts = explode('_', $codigo_prova);
$disciplina_sigla = $prova_parts[0] ?? '';

// 2. CONTROLE DE TELAS (FLUXO DO ALUNO)
$tela = 'identificacao';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // AÇÃO: O ALUNO PREENCHEU OS DADOS E CLICOU EM "INICIAR"
    if (isset($_POST['acao']) && $_POST['acao'] === 'iniciar_prova') {
        $aluno_nome  = clean_input($_POST['aluno_nome'] ?? '');
        $aluno_ra    = clean_input($_POST['aluno_ra'] ?? '');
        $aluno_email = clean_input($_POST['aluno_email'] ?? '');
        $instituicao = clean_input($_POST['instituicao'] ?? 'UNINOVE');

        if (empty($aluno_nome) || empty($aluno_ra)) {
            die("<h3>Erro: Nome e RA são obrigatórios para iniciar a avaliação.</h3>");
        }

        // Salva os dados do aluno temporariamente na Sessão PHP (Não grava no banco ainda)
        $_SESSION['aluno_nome']  = $aluno_nome;
        $_SESSION['aluno_ra']    = $aluno_ra;
        $_SESSION['aluno_email'] = $aluno_email;
        $_SESSION['instituicao'] = $instituicao;

        // Busca o universo de questões da disciplina no Supabase
        $url_questoes = $GLOBALS['supabase_url'] . "/rest/v1/questoes?disciplina=eq." . urlencode($disciplina_sigla) . "&ativa=eq.true";
        $universo_questoes = consultarSupabase($url_questoes);

        if (!is_array($universo_questoes) || empty($universo_questoes)) {
            die("<h3>Erro: Nenhuma questão ativa foi encontrada para a disciplina '$disciplina_sigla' no banco de dados.</h3>");
        }

        // Sorteia e limita dinamicamente a 5 questões para testes ágeis
        shuffle($universo_questoes);
        $limite = min(5, count($universo_questoes));
        $questoes_prova = array_slice($universo_questoes, 0, $limite);

        // Guarda as questões sorteadas na sessão do aluno
        $_SESSION['questoes_prova'] = $questoes_prova;
        
        $tela = 'prova';
    }
    
    // AÇÃO: O ALUNO RESPONDEU E CLICOU EM "FINALIZAR E ENVIAR"
    elseif (isset($_POST['acao']) && $_POST['acao'] === 'finalizar_prova') {
        $aluno_nome  = $_SESSION['aluno_nome'] ?? 'Aluno Anonimo';
        $aluno_ra    = $_SESSION['aluno_ra'] ?? '0000';
        $aluno_email = $_SESSION['aluno_email'] ?? '';
        $instituicao = $_SESSION['instituicao'] ?? 'UNINOVE';
        $questoes_prova = $_SESSION['questoes_prova'] ?? [];

        if (empty($questoes_prova)) {
            die("<h3>Erro de Sessão: Sessão expirada ou questões não encontradas. Abra o link novamente.</h3>");
        }

        // Processa as respostas e calcula a nota
        $respostas_enviadas = $_POST['respostas'] ?? [];
        $total_questoes = count($questoes_prova);
        $acertos = 0;

        foreach ($questoes_prova as $questao) {
            $id_q = $questao['id'];
            $resposta_correta = intval($questao['resposta_correta']);
            $resposta_aluno = isset($respostas_enviadas[$id_q]) ? intval($respostas_enviadas[$id_q]) : -1;

            if ($resposta_aluno === $resposta_correta) {
                $acertos++;
            }
        }

        // Cálculo da Nota de 0.00 a 10.00
        $nota_final = $total_questoes > 0 ? (($acertos / $total_questoes) * 10) : 0;
        $_SESSION['nota_final'] = number_format($nota_final, 2, ',', '.');

        // MONTA O REGISTRO COMPLETO PARA GRAVAÇÃO DEFINITIVA (MÉTODO POST)
        $dados_completos = [
            "aluno_nome"      => $aluno_nome,
            "aluno_ra"        => $aluno_ra,
            "aluno_email"     => $aluno_email,
            "instituicao"     => $instituicao,
            "turma"           => $codigo_prova,
            "codigo_prova"    => $codigo_prova,
            "numero_aula"     => 1,
            "nota_final"      => floatval($nota_final),
            "status"          => "finalizada",
            "respostas_aluno" => json_encode($respostas_enviadas)
        ];

        // Dispara a inserção única e definitiva no Supabase
        $retorno_post = salvarNoHistorico($dados_completos, "POST");

        // Se o Supabase recusar a criação por inconsistência de colunas, estoura o erro na tela
        if ($retorno_post['codigo'] >= 400) {
            die("Erro fatal ao salvar no banco (HTTP " . $retorno_post['codigo'] . "): " . print_r($retorno_post['resposta'], true));
        }

        // Limpa a sessão para evitar reenvios acidentais
        session_destroy();
        
        $tela = 'resultado_final';
    }
}

// 3. LIMPEZA DE INPUTS CONTRA SQL INJECTION / XSS
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliação Online - UNINOVE</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0d2347; color: #fff; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background-color: #1a365d; padding: 40px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.3); width: 100%; max-width: 650px; border: 1px solid #2b4c7e; }
        h2 { margin-top: 0; color: #fff; border-bottom: 2px solid #2b4c7e; padding-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .sub { font-size: 14px; color: #a0aec0; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #cbd5e0; }
        input[type="text"], input[type="email"] { width: 100%; padding: 12px; border: 1px solid #2b4c7e; background-color: #0d2347; border-radius: 6px; color: #fff; box-sizing: border-box; font-size: 16px; }
        input:focus { border-color: #4299e1; outline: none; }
        .questao-box { background-color: #0d2347; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-left: 5px solid #3182ce; }
        .opcao-item { display: flex; align-items: center; margin: 12px 0; padding: 10px; background-color: #1a365d; border: 1px solid #2b4c7e; border-radius: 6px; cursor: pointer; transition: background 0.2s; }
        .opcao-item:hover { background-color: #2b4c7e; }
        .opcao-item input { margin-right: 15px; transform: scale(1.2); }
        .btn { background-color: #3182ce; color: white; padding: 14px 28px; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%; transition: background 0.2s; text-transform: uppercase; }
        .btn:hover { background-color: #2b6cb0; }
        .btn-success { background-color: #38a169; }
        .btn-success:hover { background-color: #2f855a; }
        .alerta-nota { background-color: #2b4c7e; border-radius: 8px; padding: 20px; text-align: center; margin-top: 20px; }
        .nota-num { font-size: 36px; font-weight: bold; color: #48bb78; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    
    <?php if ($tela === 'identificacao'): ?>
        <h2>UNINOVE</h2>
        <div class="sub">Prova Ativa: <?php echo htmlspecialchars($codigo_prova); ?></div>
        
        <form method="POST">
            <input type="hidden" name="acao" value="iniciar_prova">
            
            <div class="form-group">
                <label for="aluno_nome">Nome Completo:</label>
                <input type="text" id="aluno_nome" name="aluno_nome" placeholder="Digite seu nome completo" required>
            </div>
            
            <div class="form-group">
                <label for="aluno_ra">Registro Acadêmico (RA):</label>
                <input type="text" id="aluno_ra" name="aluno_ra" placeholder="Digite seu RA" required>
            </div>
            
            <div class="form-group">
                <label for="aluno_email">E-mail (Opcional):</label>
                <input type="email" id="aluno_email" name="aluno_email" placeholder="seu.email@uninove.edu.br">
            </div>
            
            <button type="submit" class="btn">Iniciar Avaliação</button>
        </form>

    <?php elseif ($tela === 'prova'): ?>
        <h2>AVALIAÇÃO EM ANDAMENTO</h2>
        <div class="sub">Aluno: <?php echo htmlspecialchars($_SESSION['aluno_nome']); ?> | RA: <?php echo htmlspecialchars($_SESSION['aluno_ra']); ?></div>
        
        <form method="POST">
            <input type="hidden" name="acao" value="finalizar_prova">
            
            <?php foreach ($_SESSION['questoes_prova'] as $index => $q): ?>
                <div class="questao-box">
                    <p><strong>Questão <?php echo ($index + 1); ?>:</strong> <?php echo htmlspecialchars($q['enunciated'] ?? $q['enunciado']); ?></p>
                    
                    <?php 
                    $opcoes = is_string($q['opcoes']) ? json_decode($q['opcoes'], true) : $q['opcoes'];
                    if (is_array($opcoes)):
                        foreach ($opcoes as $idx_opcao => $opcao): 
                    ?>
                        <label class="opcao-item">
                            <input type="radio" name="respostas[<?php echo $q['id']; ?>]" value="<?php echo $idx_opcao; ?>" required>
                            <?php echo htmlspecialchars($opcao); ?>
                        </label>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn btn-success" onclick="return confirm('Deseja realmente finalizar e enviar suas respostas?');">Finalizar e Enviar Prova</button>
        </form>

    <?php elseif ($tela === 'resultado_final'): ?>
        <h2>UNINOVE</h2>
        <div class="sub">Prova Ativa: <?php echo htmlspecialchars($codigo_prova); ?></div>
        
        <h3>Avaliação Concluída!</h3>
        <p>Sua prova foi processada e as respostas foram salvas com sucesso no banco de dados.</p>
        
        <div class="alerta-nota">
            <div style="font-size: 18px;">Sua Nota Final:</div>
            <div class="nota-num"><?php echo $_SESSION['nota_final'] ?? '0,00'; ?></div>
        </div>
        
        <p style="margin-top: 30px; font-size: 13px; color: #a0aec0; text-align: center;">O gabarito detalhado será liberado pelo professor posteriormente.</p>
    <?php endif; ?>

</div>

</body>
</html>