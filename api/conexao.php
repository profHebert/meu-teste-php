<?php
if (!file_exists(__DIR__ . "/config.php")) {
    die("Erro Crítico: O arquivo config.php não foi encontrado em api/.");
}
require_once __DIR__ . "/config.php";

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
