<?php
if (file_exists(__DIR__ . "/config.php")) {
    require_once __DIR__ . "/config.php";
} elseif (file_exists(__DIR__ . "/../config.php")) {
    require_once __DIR__ . "/../config.php";
} else {
    die("Erro Crítico: O arquivo config.php não foi encontrado!");
}

require_once "conexao.php";

// Função isolada para disparar o cURL direto para a tabela do histórico
function salvarNoHistorico($dados, $metodo = "POST", $ra_aluno = "") {
    $url = $GLOBALS['supabase_url'] . "/rest/v1/historico_provas";
    
    // Se for PATCH (finalizar), adiciona o filtro do RA na URL
    if (strtoupper($metodo) === "PATCH") {
        $url .= "?aluno_ra=eq." . urlencode($ra_aluno);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($metodo));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: " . $GLOBALS['supabase_key'],
        "Authorization: Bearer " . $GLOBALS['supabase_key'],
        "Content-Type: application/json",
        "Prefer: return=representation"
    ]);

    $resposta = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        "codigo" => $http_code,
        "resposta" => json_decode($resposta, true) ?: $resposta
    ];
}