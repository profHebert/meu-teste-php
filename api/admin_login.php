<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erro_login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_login'])) {
    $usuario_digitado = trim($_POST['usuario'] ?? '');
    $senha_digitada   = $_POST['senha'] ?? '';

   include_once "config.php"; // Puxa as chaves centrais
    // Credenciais centrais (Ajuste com seus dados reais)
    // $supabase_url = "https://vxkxptbrfbqygpisggjm.supabase.co"; 
    // $supabase_key = "SUA_ANON_KEY_REAL_AQUI"; // <-- Coloque sua Anon Key real aqui

    $supabase_url = rtrim($supabase_url, '/');
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
    curl_close($ch);
    
    $professores = json_decode($resposta, true) ?: [];

    // LÓGICA CORRIGIDA: Usa estrutura estruturada de IF / ELSE
    if (!empty($professores) && isset($professores[0])) {
        $professor = $professores[0];
        
        // Testa a senha contra o hash do banco
        if (password_verify($senha_digitada, $professor['senha_hash'])) {
            $_SESSION['professor_logado'] = true;
            $_SESSION['professor_nome']   = $professor['nome'];
            
            // Força a interrupção e vai para o ambiente
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