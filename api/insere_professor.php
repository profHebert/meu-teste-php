<?php
// insere_professor.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Configurações do seu Supabase (Mantenha sua chave real completa)
$supabase_url = "https://vxkxptbrfbqygpisggjm.supabase.co";
$supabase_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZ4a3hwdGJyZmJxeWdwaXNnZ2ptIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODEwMzkxMjUsImV4cCI6MjA5NjYxNTEyNX0.dEW3a_-Tgr-ufM3LLtzx1cuX1G4rMC_uK8lsJruGYt0";
define('SUPABASE_URL', $supabase_url);
define('SUPABASE_KEY', $supabase_key);
// 2. Dados do novo professor que você pediu
$novo_usuario = 'prof';
$senha_pura   = 'prof123';
$novo_nome    = 'Professor Adjunto';

// 3. O PHP gera o Hash perfeito de 60 caracteres aqui
$senha_criptografada = password_hash($senha_pura, PASSWORD_BCRYPT);

// 4. Monta o JSON com os dados para enviar ao Supabase
$dados_professor = [
    "usuario"    => $novo_usuario,
    "senha_hash" => $senha_criptografada,
    "nome"       => $novo_nome
];

$payload = json_encode($dados_professor);

// 5. Prepara a requisição POST para inserir no banco
$url = rtrim(SUPABASE_URL, '/') . "/rest/v1/professores";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); // Método POST insere dados
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: " . SUPABASE_KEY,
    "Authorization: Bearer " . SUPABASE_KEY,
    "Content-Type: application/json",
    "Prefer: return=representation" // Pede para o Supabase devolver o dado inserido como confirmação
]);

$resposta = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6. Exibe o resultado na tela para sabermos se deu certo
echo "<div style='font-family:sans-serif; padding:20px; max-width:600px; margin:20px auto; border:1px solid #ccc; border-radius:5px;'>";
echo "<h2>🛠️ Cadastro de Professor via PHP</h2>";
echo "<p><strong>Usuário criado:</strong> " . htmlspecialchars($novo_usuario) . "</p>";
echo "<p><strong>Hash gerado pelo PHP:</strong> <code style='background:#eee; padding:3px;'>" . htmlspecialchars($senha_criptografada) . "</code></p>";
echo "<p><strong>Código HTTP do Supabase:</strong> " . $http_code . "</p>";
echo "<p><strong>Resposta do Banco:</strong></p>";
echo "<pre style='background:#f4f4f4; padding:10px;