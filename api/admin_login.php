<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erro_login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_login'])) {
    $usuario_digitado = $_POST['usuario'] ?? '';
    $senha_digitada   = $_POST['senha'] ?? '';

    // INCLUA O SEU ARQUIVO DE CONFIGURAÇÃO AQUI (ou cole as variáveis direto)
    // Exemplo se usar variáveis direto para testar:
    // $supabase_url = "https://sua-url-do-supabase.supabase.co"; 
    // $supabase_key = "SUA_ANON_KEY_REAL_AQUI";
    include_once "config.php"; // Puxa as chaves centrais

    // Garante que a URL não termine com barra para não quebrar o caminho
    $supabase_url = rtrim($supabase_url, '/');

    // 1. Monta a URL exata de consulta
    $url = $supabase_url . "/rest/v1/professores?usuario=eq." . urlencode($usuario_digitado);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: " . $supabase_key,
        "Authorization: Bearer " . $supabase_key,
        "Content-Type: application/json"
    ]);
    
    $resposta = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Captura o código HTTP (Ex: 200, 401, 404)
    curl_close($ch);
    
    // ==========================================
    // 🚨 BLOCO DE DIAGNÓSTICO ULTRA SEGURO (MÁGICA DO DEBUG)
    // ==========================================
    echo "<div style='background:#fff; color:#000; padding:15px; border:3px solid #ff9800; margin:20px; font-family:monospace; text-align:left; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.2);'>";
    echo "<h3 style='margin-top:0; color:#d32f2f;'>🔍 Diagnóstico de Conexão Supabase</h3>";
    echo "<strong>URL Chamada:</strong> " . htmlspecialchars($url) . "<br>";
    echo "<strong>Código HTTP da Resposta:</strong> <span style='background:#eee; padding:2px 6px; border-radius:4px; font-weight:bold;'>" . $http_code . "</span><br>";
    echo "<strong>Resposta Bruta do Banco:</strong> <pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc; max-height:150px; overflow:auto;'>" . htmlspecialchars($resposta) . "</pre>";
    
    $professores = json_decode($resposta, true) ?: [];
    echo "<strong>Registros decodificados pelo PHP:</strong> " . count($professores) . "<br>";
    echo "</div>";
    // ==========================================

    if (!empty($professores) && isset($professores[0])) {
        $professor = $professores[0];
        
        if (password_verify($senha_digitada, $professor['senha_hash'])) {
            $_SESSION['professor_logado'] = true;
            $_SESSION['professor_nome']   = $professor['nome'];
            header("Location: ambiente_professor.php");
            exit;
        }
    }
    
    $erro_login = "Usuário ou senha incorretos.";
}
?>