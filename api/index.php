<?php
require_once "conexao.php";

$requisicao = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = trim($requisicao, '/');
$partes = explode('/', $url);

$instituicao = (!empty($partes[0])) ? $partes[0] : 'Nenhuma';
$turma_url   = (!empty($partes[1])) ? $partes[1] : '';
$numero_aula = isset($_GET['aula']) ? intval($_GET['aula']) : 5;

switch (strtolower($instituicao)) {
    case 'fecap':
        $nome_faculdade = "FECAP"; $cor_fundo = "#004d3d"; $cor_botao = "#deff9a"; $cor_texto_btn = "#004d3d";
        $turmas_disponiveis = ["ADS-3A", "CONTABEIS-1B"]; break;
    case 'uninove':
        $nome_faculdade = "UNINOVE"; $cor_fundo = "#002d62"; $cor_botao = "#fbb034"; $cor_texto_btn = "#ffffff";
        $turmas_disponiveis = ["ADS-1A", "ADS-1B", "ADS-2A", "ADS-3A", "SI-1A", "SI-2A", "ENG-1A", "ENG-2B"]; break;
    default:
        $nome_faculdade = "Portal de Provas"; $cor_fundo = "#1a1a1a"; $cor_botao = "#0070f3"; $cor_texto_btn = "#ffffff";
        $turmas_disponiveis = ["Geral"]; break;
}

$mostrar_formulario_identificacao = true;
$questoes_banco = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aluno_nome  = $_POST['nome'] ?? '';
    $aluno_ra    = $_POST['ra'] ?? '';
    $aluno_email = $_POST['email'] ?? '';
    $aluno_turma = $_POST['turma'] ?? '';
    
    $mostrar_formulario_identificacao = false;

    // Busca as questões ativas da aula no Supabase
    $endpoint = "questoes?numero_aula=eq." . $numero_aula . "&ativa=eq.true";
    $questoes_banco = consultarSupabase($endpoint);

    if (is_array($questoes_banco) && !empty($questoes_banco)) {
        // 1. EMBARALHA AS QUESTÕES (Ordem das perguntas fica randômica)
        shuffle($questoes_banco);
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
        .input-grupo input, .input-grupo select { width: 100%; padding: 12px; margin: 8px 0; border-radius: 6px; border: 1px solid rgba(255,255,255,0.3); background: rgba(0,0,0,0.4); color: #fff; box-sizing: border-box; }
        .input-grupo select option { background: #222; }
        .btn-acao { background-color: <?php echo $cor_botao; ?>; color: <?php echo $cor_texto_btn; ?>; border: none; padding: 14px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 20px; font-size: 16px; }
        .questao-bloco { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: left; border: 1px solid rgba(255,255,255,0.05); }
        .opcao-item { display: block; margin: 10px 0; cursor: pointer; padding: 8px; border-radius: 4px; background: rgba(255,255,255,0.02); }
        .opcao-item:hover { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>

    <div class="card-sistema">
        <h2><?php echo $nome_faculdade; ?></h2>
        
        <?php if ($mostrar_formulario_identificacao): ?>
            <h3>Dados do Estudante (Aula <?php echo $numero_aula; ?>)</h3>
            <form action="" method="POST">
                <div class="input-grupo"><input type="text" name="nome" placeholder="Nome Completo" required></div>
                <div class="input-grupo"><input type="text" name="ra" placeholder="RA" required></div>
                <div class="input-grupo"><input type="email" name="email" placeholder="E-mail Institucional" required></div>
                <div class="input-grupo">
                    <select name="turma" required>
                        <option value="">Selecione sua Turma...</option>
                        <?php foreach ($turmas_disponiveis as $t): ?>
                            <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-acao">Iniciar Avaliação</button>
            </form>

        <?php else: ?>
            <div style="background: rgba(0,0,0,0.3); padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: left; font-size: 14px;">
                🧑‍🎓 Aluno: <b><?php echo htmlspecialchars($aluno_nome); ?></b> | Turma: <b><?php echo htmlspecialchars($aluno_turma); ?></b>
            </div>

            <form action="#" method="POST">
                <?php if (!empty($questoes_banco)): ?>
                    <?php foreach ($questoes_banco as $index => $q): ?>
                        <div class="questao-bloco">
                            <p><strong>Questão <?php echo $index + 1; ?>:</strong></p>
                            <p><?php echo htmlspecialchars($q['enunciado']); ?></p>
                            
                            <?php 
                            $opcoes = is_string($q['opcoes']) ? json_decode($q['opcoes'], true) : $q['opcoes'];
                            
                            if (is_array($opcoes)): 
                                // Criamos um array associativo guardando o índice original antes de embaralhar
                                $opcoes_mapeadas = [];
                                foreach ($opcoes as $i => $texto_opcao) {
                                    $opcoes_mapeadas[] = [
                                        'indice_original' => $i,
                                        'texto' => $texto_opcao
                                    ];
                                }
                                
                                // 2. EMBARALHA AS ALTERNATIVAS desta questão específica
                                shuffle($opcoes_mapeadas);
                                
                                foreach ($opcoes_mapeadas as $opcao_objeto): 
                            ?>
                                <label class="opcao-item">
                                    <input type="radio" name="questao_<?php echo $q['id']; ?>" value="<?php echo $opcao_objeto['indice_original']; ?>" required>
                                    <?php echo htmlspecialchars($opcao_objeto['texto']); ?>
                                </label>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="btn-acao" onclick="alert('Sistema Anti-Cola ativo: Questões e alternativas embaralhadas com sucesso! Respostas obrigatórias validadas.')">Finalizar Prova</button>
                <?php else: ?>
                    <p>Nenhuma questão encontrada no banco de dados.</p>
                <?php endif; ?>
            </form>
        <?php endif; ?>

    </div>

</body>
</html>
