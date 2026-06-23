<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . "/config.php")) {
    require_once __DIR__ . "/config.php";
} elseif (file_exists(__DIR__ . "/../config.php")) {
    require_once __DIR__ . "/../config.php";
} else {
    die("Erro Crítico: O arquivo config.php não foi encontrado!");
}

require_once "conexao.php";

// Validação de acesso
if (!isset($_SESSION['professor_logado']) || $_SESSION['professor_logado'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$nome_professor = $_SESSION['professor_nome'] ?? 'Professor';

// Busca estatísticas do banco
$disciplinas = consultarSupabase("disciplinas");
$disciplinas = is_array($disciplinas) ? $disciplinas : [];

$questoes = consultarSupabase("questoes?select=count(*)");
$total_questoes = is_array($questoes) && isset($questoes[0]['count']) ? $questoes[0]['count'] : 0;

$provas_recentes = consultarSupabase("historico_provas?order=created_at.desc&limit=5");
$provas_recentes = is_array($provas_recentes) ? $provas_recentes : [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Sistema de Provas</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <?php include_once "theme.php"; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: var(--bg, #f5f7fa);
            color: var(--text, #333);
        }
        .theme-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .theme-controls .btn-secondary {
            background: rgba(255,255,255,0.9);
            border-color: rgba(226,232,240,0.9);
            color: #333;
        }

        /* ========== LAYOUT PRINCIPAL ========== */
        .layout-container {
            display: flex;
            height: 100vh;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow-y: auto;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: width 0.3s ease;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .sidebar.collapsed .sidebar-logo-text {
            display: none;
        }

        .sidebar-nav {
            list-style: none;
            margin-top: 20px;
        }

        .sidebar-nav li {
            margin: 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }

        .sidebar-nav a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: #ffc107;
        }

        .sidebar-nav i {
            width: 24px;
            margin-right: 15px;
            text-align: center;
            font-size: 18px;
        }

        .sidebar.collapsed .sidebar-nav span {
            display: none;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }

        .layout-container.sidebar-collapsed .main-content {
            margin-left: 80px;
        }

        /* ========== TOPBAR ========== */
        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 900;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #667eea;
            transition: color 0.3s ease;
        }

        .menu-toggle:hover {
            color: #764ba2;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .professor-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .professor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .logout-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #d32f2f;
        }

        /* ========== PAGE CONTENT ========== */
        .page-content {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 500;
            color: #333;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #666;
            font-size: 14px;
        }

        /* ========== CARDS GRID ========== */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-icon.purple {
            background: #667eea;
            color: white;
        }

        .stat-icon.blue {
            background: #2196f3;
            color: white;
        }

        .stat-icon.green {
            background: #4caf50;
            color: white;
        }

        .stat-icon.orange {
            background: #ff9800;
            color: white;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        /* ========== TABLE ========== */
        .content-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 20px;
        }

        .content-card-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .content-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f5f7fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            border-bottom: 2px solid #eee;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        table tr:hover {
            background: #f9f9f9;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-pending {
            background: #fff3e0;
            color: #e65100;
        }

        /* ========== ACTION BUTTONS ========== */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f5f7fa;
            color: #333;
            border: 1px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #eeeff3;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                left: -280px;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                padding: 15px;
            }

            .page-content {
                padding: 15px;
            }
        }

        /* ========== SCROLLBAR ========== */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <div class="layout-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-logo-text">Provas</div>
            </div>
            <nav class="sidebar-nav">
                <li>
                    <a href="admin_dashboard.php" class="active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="disciplina_gestao.php">
                        <i class="fas fa-book"></i>
                        <span>Disciplinas</span>
                    </a>
                </li>
                <li>
                    <a href="questao_gestao.php">
                        <i class="fas fa-question-circle"></i>
                        <span>Questões</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Relatórios</span>
                    </a>
                </li>
                <li>
                    <a href="disciplina_gestao.php">
                        <i class="fas fa-book"></i>
                        <span>Disciplinas</span>
                    </a>
                </li>
                <li>
                    <a href="questao_gestao.php">
                        <i class="fas fa-question-circle"></i>
                        <span>Questões</span>
                    </a>
                </li>
                <li>
                    <a href="criar_turma.php">
                        <i class="fas fa-users"></i>
                        <span>Turmas</span>
                    </a>
                </li>
                <li>
                    <a href="cadastro_professor.php">
                        <i class="fas fa-user-plus"></i>
                        <span>Professores</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="logout()">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Sair</span>
                    </a>
                </li>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- TOPBAR -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumb">
                        <span>Dashboard</span> / <span>Início</span>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="theme-controls">
                        <button class="btn btn-secondary" type="button" onclick="setSistemaTema('light')">🌞 Claro</button>
                        <button class="btn btn-secondary" type="button" onclick="setSistemaTema('dark')">🌙 Escuro</button>
                    </div>
                    <div class="professor-info">
                        <div class="professor-avatar">
                            <?php echo strtoupper(substr($nome_professor, 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 14px;">
                                <?php echo htmlspecialchars($nome_professor); ?>
                            </div>
                            <div style="color: #999; font-size: 12px;">Professor</div>
                        </div>
                    </div>
                    <form method="GET" style="margin: 0;">
                        <button type="submit" name="logout" value="1" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </button>
                    </form>
                </div>
            </header>

            <!-- PAGE CONTENT -->
            <main class="page-content">
                <div class="page-header">
                    <h1 class="page-title">Bem-vindo ao Painel Administrativo</h1>
                    <p class="page-subtitle">Gerencie suas disciplinas, questões e acompanhe o desempenho dos alunos</p>
                </div>

                <!-- STAT CARDS -->
                <div class="cards-grid">
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-label">Disciplinas</div>
                        <div class="stat-value"><?php echo count($disciplinas); ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="stat-label">Total de Questões</div>
                        <div class="stat-value"><?php echo $total_questoes; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-label">Provas Finalizadas</div>
                        <div class="stat-value"><?php echo count($provas_recentes); ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-label">Alunos Ativos</div>
                        <div class="stat-value">--</div>
                    </div>
                </div>

                <!-- RECENT SUBMISSIONS -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Últimas Submissões</h2>
                    </div>

                    <?php if (!empty($provas_recentes)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Aluno</th>
                                    <th>Disciplina</th>
                                    <th>Prova</th>
                                    <th>Nota</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($provas_recentes, 0, 10) as $prova): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($prova['aluno_nome'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(substr($prova['codigo_prova'] ?? 'N/A', 0, 10)); ?></td>
                                        <td><?php echo htmlspecialchars($prova['codigo_prova'] ?? 'N/A'); ?></td>
                                        <td><strong><?php echo number_format($prova['nota_final'] ?? 0, 2, ',', '.'); ?></strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($prova['created_at'] ?? 'now')); ?></td>
                                        <td>
                                            <span class="badge badge-success">Finalizada</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #999; padding: 40px 20px;">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px;"></i><br>
                            Nenhuma submissão encontrada
                        </p>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Ver Relatórios Completos
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const layoutContainer = document.querySelector('.layout-container');
        const sidebar = document.querySelector('.sidebar');

        menuToggle.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                layoutContainer.classList.toggle('sidebar-collapsed');
            }
        });

        // Logout Handler
        function logout() {
            if (confirm('Tem certeza que deseja sair?')) {
                window.location.href = '?logout=1';
            }
        }

        // Close sidebar on mobile when clicking a link
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>
