<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPath = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$menuItems = [
    ['href' => 'ambiente_professor.php', 'icon' => '🏠', 'label' => 'Início'],
    ['href' => 'admin_dashboard.php', 'icon' => '📋', 'label' => 'Painel Administrativo'],
    ['href' => 'dashboard.php', 'icon' => '📊', 'label' => 'Relatórios'],
    ['href' => 'disciplina_gestao.php', 'icon' => '📚', 'label' => 'Disciplinas'],
    ['href' => 'questao_gestao.php', 'icon' => '📝', 'label' => 'Questões'],
    ['href' => 'criar_turma.php', 'icon' => '👥', 'label' => 'Turmas'],
    ['href' => 'cadastro_professor.php', 'icon' => '👤', 'label' => 'Professores'],
];
?>
<style>
.professor-nav { display: flex; flex-direction: column; gap: 10px; margin: 20px 0; }
.professor-nav a, .professor-nav span { display: flex; align-items: center; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: 600; }
.professor-nav a { background: #f8f9fa; border: 1px solid #dedeed; color: #202124; transition: transform 0.15s ease, background 0.15s ease; }
.professor-nav a:hover { background: #e8eef8; transform: translateX(2px); }
.professor-nav .active { background: #d4e3ff; border-color: #8bb0ff; color: #0f2f74; }
.professor-nav .disabled { background: #f3f3f3; border-color: #dedede; color: #8a8a8a; cursor: not-allowed; }
.professor-nav .item-icon { margin-right: 10px; font-size: 18px; }
.theme-switcher { margin-top: 24px; padding: 16px; border-radius: 12px; background: #ffffff; border: 1px solid #e4e7f1; }
.theme-switcher span { display: block; font-size: 13px; color: #5f6368; margin-bottom: 10px; }
.theme-switcher a { display: inline-block; margin-right: 10px; margin-bottom: 6px; }
.btn-theme-small { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 12px; border-radius: 8px; border: 1px solid #dfe3ea; background: #f8f9fa; color: #202124; text-decoration: none; font-size: 13px; transition: background 0.2s ease; }
.btn-theme-small:hover { background: #eef2ff; }
</style>
<div class="professor-nav">
    <?php foreach ($menuItems as $item): ?>
        <?php $isActive = ($currentPath === basename($item['href'])); ?>
        <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $isActive ? 'active' : '' ?>">
            <span class="item-icon"><?= $item['icon'] ?></span>
            <?= $item['label'] ?>
        </a>
    <?php endforeach; ?>

    <div class="theme-switcher">
        <span>Alternar tema:</span>
        <a href="#" class="btn-theme-small" onclick="setSistemaTema('light'); return false;">🌞 Claro</a>
        <a href="#" class="btn-theme-small" onclick="setSistemaTema('dark'); return false;">🌙 Escuro</a>
    </div>
</div>
