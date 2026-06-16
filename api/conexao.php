<?php
// Credenciais do seu projeto Supabase
$supabase_url = "https://vxkxptbrfbqygpisggjm.supabase.co";
$supabase_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZ4a3hwdGJyZmJxeWdwaXNnZ2ptIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODEwMzkxMjUsImV4cCI6MjA5NjYxNTEyNX0.dEW3a_-Tgr-ufM3LLtzx1cuX1G4rMC_uK8lsJruGYt0";

/**
 * Função global para buscar dados no Supabase via cURL (API REST)
 */
function consultarSupabase($endpoint) {
    global $supabase_url, $supabase_key;
    
    // Monta a URL completa da API para a tabela desejada
    $url_completa = $supabase_url . "/rest/v1/" . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_completa);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Passa os cabeçalhos de segurança que o Supabase exige
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: " . $supabase_key,
        "Authorization: Bearer " . $supabase_key,
        "Content-Type: application/json",
        "Prefer: return=representation"
    ]);
    
    $resposta = curl_exec($ch);
    curl_close($ch);
    
    // Transforma o texto JSON que o Supabase devolve em um Array do PHP
    return json_decode($resposta, true);
}
