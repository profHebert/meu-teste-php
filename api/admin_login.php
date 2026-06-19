<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🌟 O JUÍZO FINAL DA TELA BRANCA: Força o caminho correto a partir da raiz do servidor
// if (file_exists(__DIR__ . "/config.php")) {
//     include_once __DIR__ . "/config.php";
// } elseif (file_exists(__DIR__ . "/../config.php")) {
//     // Caso o login esteja dentro de uma pasta /api ou /admin
//     include_once __DIR__ . "/../config.php";
// } else {
//     die("Erro Crítico: O arquivo config.php não foi encontrado na raiz do projeto!");
// }
define('SUPABASE_URL', 'https://vxkxptbrfbqygpisggjm.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.SuaChaveRealAqui...');
$erro_login = '';
// ... resto do seu código cURL ...

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_login'])) {
    $usuario_digitado = trim($_POST['usuario'] ?? '');
    $senha_digitada   = $_POST['senha'] ?? '';

    // 2. Garante que a URL vinda do config não termine com barra para não quebrar o caminho
    $url_base = rtrim(SUPABASE_URL, '/');
    $url = $url_base . "/rest/v1/professores?usuario=eq." . urlencode($usuario_digitado);
    
    // 3. Configura o cURL injetando as constantes centralizadas
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY,
        "Content-Type: application/json"
    ]);
    
    $resposta = curl_exec($ch);
    curl_close($ch);
    
    $professores = json_decode($resposta, true) ?: [];

    if (!empty($professores) && isset($professores[0])) {
        $professor = $professores[0];
        
        // Testa a senha contra o hash seguro do banco
        if (password_verify($senha_digitada, $professor['senha_hash'])) {
            $_SESSION['professor_logado'] = true;
            $_SESSION['professor_nome']   = $professor['nome'];
            
            header("Location: ambiente_professor.php");
            exit;
        } else {
            $erro_login = "Senha incorreta.";
        }
    } else {
        $erro_login = "Usuário não encontrado.";
    }
}
?>