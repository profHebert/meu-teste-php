<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<script>
(function() {
    var storedTheme = localStorage.getItem('sistema_tema') || 'light';
    document.documentElement.dataset.theme = storedTheme;
    window.setSistemaTema = function(theme) {
        if (theme !== 'light' && theme !== 'dark') return;
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('sistema_tema', theme);
    };
})();
</script>
<style>
:root {
    color-scheme: light;
    --bg: #f5f7fa;
    --surface: #ffffff;
    --surface-alt: #eef2ff;
    --text: #1e293b;
    --muted: #64748b;
    --accent: #475be8;
    --accent-strong: #4338ca;
    --border: #dbeafe;
    --card: #ffffff;
    --shadow: 0 20px 50px rgba(71, 91, 232, 0.12);
}
html[data-theme='dark'] {
    color-scheme: dark;
    --bg: #0b1120;
    --surface: #111827;
    --surface-alt: #1f2937;
    --text: #e2e8f0;
    --muted: #94a3b8;
    --accent: #38bdf8;
    --accent-strong: #0284c7;
    --border: #334155;
    --card: #111827;
    --shadow: 0 20px 50px rgba(15, 23, 42, 0.45);
}
body {
    background: var(--bg);
    color: var(--text);
    transition: background 0.25s ease, color 0.25s ease;
}
a { color: var(--accent); }
.btn-theme {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text);
    text-decoration: none;
    cursor: pointer;
    font-weight: 600;
}
.btn-theme:hover {
    background: var(--surface-alt);
}
.theme-switcher {
    margin-top: 18px;
    padding: 16px;
    border-radius: 14px;
    background: var(--surface-alt);
    border: 1px solid var(--border);
}
.theme-switcher span {
    display: block;
    margin-bottom: 10px;
    color: var(--muted);
    font-size: 14px;
}
.theme-switcher a {
    display: inline-block;
    margin-right: 10px;
}
</style>
