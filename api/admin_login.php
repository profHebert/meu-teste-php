<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Suas definições diretas que funcionaram perfeitamente
// include_once "config.php";
$supabase_url = "https://vxkxptbrfbqygpisggjm.supabase.co";
$supabase_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZ4a3hwdGJyZmJxeWdwaXNnZ2ptIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODEwMzkxMjUsImV4cCI6MjA5NjYxNTEyNX0.dEW3a_-Tgr-ufM3LLtzx1cuX1G4rMC_uK8lsJruGYt0";
define('SUPABASE_URL', $supabase_url);
define('SUPABASE_KEY', $supabase_key);
$erro_login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_login'])) {
    $usuario_digitado = trim($_POST['usuario'] ?? '');
    $senha_digitada   = $_POST['senha'] ?? '';

    $url_base = rtrim(SUPABASE_URL, '/');
    $url = $url_base . "/rest/v1/professores?usuario=eq." . urlencode($usuario_digitado);
    
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
    $senha_criptografada = password_hash("professor123", PASSWORD_BCRYPT);
    echo "<h2>$senha_criptografada</h2>";
    echo "<pre>";print_r($professores);echo "</pre>";

    if (!empty($professores) && isset($professores[0])) {
        $professor = $professores[0];
        
        if (password_verify($senha_digitada, $professor['senha_hash'])) {
            $_SESSION['professor_logado'] = true;
            $_SESSION['professor_nome']   = $professor['nome'];
            
            // header("Location: ambiente_professor.php");
            echo"<h2>Senha ok</h2>";

            exit;
        } else {
            $erro_login = "Senha incorreta.";
        }
    } else {
        $erro_login = "Usuário não encontrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Área do Docente - Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0ebf8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; border-top: 8px solid #673ab7; }
        h2 { margin-top: 0; color: #202124; }
        .input-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #5f6368; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #dadce0; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #673ab7; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        button:hover { background-color: #512da8; }
        .erro { color: #c5221f; background-color: #fce8e6; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Acesso do Professor</h2>
    
    <?php if (!empty($erro_login)): ?>
        <div class="erro"><?php echo htmlspecialchars($erro_login); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-group">
            <label for="usuario">Usuário</label>
            <input type="text" id="usuario" name="usuario" required autocomplete="off">
        </div>
        
        <div class="input-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        
        <button type="submit" name="acao_login">Entrar</button>
    </form>
</div>

</body>
</html>