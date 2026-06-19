<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['professor_logado']) || $_SESSION['professor_logado'] !== true) {
    header("Location: ../index.php");
    exit;
}
require_once "conexao.php";

// 1. CAPTURA O FILTRO DA TURMA/PROVA SE O PROFESSOR SELECIONAR
$filtro_prova = isset($_GET['prova']) ? $_GET['prova'] : '';

// 2. MONTA O ENDPOINT PARA O SUPABASE
// Ordena por padrão os registros do mais recente para o mais antigo (created_at.desc)
$endpoint = "historico_provas?order=created_at.desc";

// Se o professor escolheu uma turma específica, adiciona o filtro na query do Supabase
if (!empty($filtro_prova)) {
    $endpoint .= "&codigo_prova=eq." . urlencode($filtro_prova);
}

// Busca os dados do banco
$resultados = consultarSupabase($endpoint);
$resultados = is_array($resultados) ? $resultados : [];

// 3. CALCULA ESTATÍSTICAS RÁPIDAS (APENAS SE FOR UM ARRAY DE REGISTROS VÁLIDO)
$total_provas = 0;
$soma_notas = 0;
$provas_por_turma = [];

if (is_array($resultados) && !empty($resultados) && isset($resultados[0]) && is_array($resultados[0])) {
    $total_provas = count($resultados);
    foreach ($resultados as $res) {
        if (is_array($res)) {
            $soma_notas += floatval($res['nota_final'] ?? 0);
            
            $cod = $res['codigo_prova'] ?? $res['turma'] ?? 'Geral';
            if (!in_array($cod, $provas_por_turma)) {
                $provas_por_turma[] = $cod;
            }
        }
    }
} else {
    // Se o banco retornou um erro em texto, esvaziamos os resultados para não quebrar a tabela abaixo
    $resultados = [];
}

$media_geral = $total_provas > 0 ? ($soma_notas / $total_provas) : 0;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Professor - Central de Notas</title>
    <style>
        body { background-color: #121214; color: #e1e1e6; font-family: sans-serif; margin: 0; padding: 40px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #ffffff; margin-bottom: 30px; border-bottom: 2px solid #29292e; padding-bottom: 10px; }
        
        /* CARDS DE ESTATÍSTICAS */
        .dashboard-cards { display: flex; gap: 20px; margin-bottom: 30px; }
        .card { flex: 1; background: #202024; padding: 20px; border-radius: 8px; border: 1px solid #29292e; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .card h3 { margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; color: #8d8d99; letter-spacing: 1px; }
        .card p { margin: 0; font-size: 28px; font-weight: bold; color: #00b37e; }
        .card p.media { color: #fba034; }

        /* FILTRO */
        .filtro-bloco { background: #202024; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #29292e; display: flex; align-items: center; gap: 15px; }
        .filtro-bloco label { font-weight: bold; font-size: 14px; }
        .filtro-bloco select { background: #121214; color: #fff; border: 1px solid #29292e; padding: 8px 12px; border-radius: 4px; font-size: 14px; cursor: pointer; }
        .btn-filtrar { background: #00875f; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-filtrar:hover { background: #00b37e; }
        .btn-limpar { color: #8d8d99; text-decoration: none; font-size: 14px; }
        .btn-limpar:hover { color: #fff; }

        /* TABELA DE NOTAS */
        .tabela-wrapper { background: #202024; border-radius: 8px; border: 1px solid #29292e; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #29292e; color: #e1e1e6; padding: 14px 20px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 14px 20px; border-bottom: 1px solid #29292e; font-size: 15px; color: #c4c4cc; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #26262a; color: #fff; }
        
        /* BADGES */
        .badge-status { background: #111c15; color: #00b37e; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; border: 1px solid #004d36; }
        .badge-prova { background: #29292e; color: #e1e1e6; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-family: monospace; }
        .nota-destaque { font-weight: bold; font-size: 16px; }
        .nota-alta { color: #00b37e; }
        .nota-baixa { color: #f75a68; }
        
        .sem-dados { padding: 40px; text-align: center; color: #8d8d99; font-style: italic; }
    </style>
</head>
<body>

    <div class="container">
        <h1>📊 Painel do Professor</h1>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Total de Entregas</h3>
                <p><?php echo $total_provas; ?> alunos</p>
            </div>
            <div class="card">
                <h3>Média Geral da Turma</h3>
                <p class="media"><?php echo number_format($media_geral, 2, ',', '.'); ?></p>
            </div>
        </div>

        <div class="filtro-bloco">
            <form action="" method="GET" style="display: flex; align-items: center; gap: 15px; margin: 0; width: 100%;">
                <label for="prova">Filtrar por Avaliação:</label>
                <select name="prova" id="prova">
                    <option value="">-- Mostrar Todas as Turmas --</option>
                    <?php 
                    // Garante que a turma testada anteriormente apareça mesmo se o banco estiver vazio
                    if (!in_array('DBDSQL_6a_M_atv1', $provas_por_turma)) {
                        $provas_por_turma[] = 'DBDSQL_6a_M_atv1';
                    }
                    foreach ($provas_por_turma as $p): 
                    ?>
                        <option value="<?php echo $p; ?>" <?php echo $filtro_prova === $p ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filtrar">Aplicar Filtro</button>
                <?php if (!empty($filtro_prova)): ?>
                    <a href="dashboard.php" class="btn-limpar">Limpar Filtros</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="tabela-wrapper">
            <?php if (!empty($resultados)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Estudante</th>
                            <th>RA</th>
                            <th>Código da Prova</th>
                            <th>Status</th>
                            <th>Nota Final</th>
                            <th>AÇÃO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $linha): 
                            $data_formatada = isset($linha['created_at']) ? date('d/m/Y H:i', strtotime($linha['created_at'] . ' -3 hours')) : '-';
                            $nota = floatval($linha['nota_final'] ?? 0);
                            $classe_nota = $nota >= 6.0 ? 'nota-alta' : 'nota-baixa';
                        ?>
                            <tr>
                                <td><?php echo $data_formatada; ?></td>
                                <td><strong><?php echo htmlspecialchars($linha['aluno_nome'] ?? 'Anônimo'); ?></strong></td>
                                <td><?php echo htmlspecialchars($linha['aluno_ra'] ?? '-'); ?></td>
                                <td><span class="badge-prova"><?php echo htmlspecialchars($linha['codigo_prova'] ?? $linha['turma'] ?? '-'); ?></span></td>
                                <td><span class="badge-status">Concluída</span></td>
                                <td class="nota-destaque <?php echo $classe_nota; ?>">
                                    <?php echo number_format($nota, 2, ',', '.'); ?>
                                </td>
                                <td>
           <a href="/ver_prova?id=<?php echo $linha['id']; ?>" style="color: #04d361; text-decoration: none; font-weight: bold;">
        <?php echo htmlspecialchars($linha['aluno_nome']); ?> 🔍
        </a>
        </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="sem-dados">
                    Nenhum aluno realizou esta avaliação ainda ou os filtros não retornaram dados.
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>