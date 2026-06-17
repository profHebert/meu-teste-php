<?php
// salvahistorico.php - TESTE ISOLADO DE INSERÇÃO DIRETA
require_once "api/conexao.php";

echo "<h2>🧪 Iniciando Teste de Inserção Isolado...</h2>";

// 1. Dados fictícios para o teste estático
$dados_teste = [
    "aluno_nome" => "Professor Teste Estrutural",
    "aluno_ra" => "999999",
    "aluno_email" => "teste@uninove.com",
    "instituicao" => "UNINOVE",
    "turma" => "DBDSQL_6a_M_atv1",
    "codigo_prova" => "DBDSQL_6a_M_atv1",
    "numero_aula" => 1,
    "nota_final" => 7.50,
    "status" => "finalizada",
    "respostas_aluno" => json_encode(["q1" => "0", "q2" => "1"])
];

// 2. Configura a URL de destino da API do seu Supabase
$url_destino = $GLOBALS['supabase_url'] . "/rest/v1/historico_provas";

echo "<b>URL de Destino:</b> " . $url_destino . "<br><br>";

// 3. Prepara e executa o cURL
$ch = curl_init($url_destino);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados_teste));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: " . $GLOBALS['supabase_key'],
    "Authorization: Bearer " . $GLOBALS['supabase_key'],
    "Content-Type: application/json",
    "Prefer: return=representation" // Obriga o Supabase a responder com os dados ou o erro real
]);

$resposta = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 4. Exibe o Diagnóstico na Tela
echo "<h3>--- RESULTADO DO BANCO ---</h3>";
echo "<b>Código HTTP:</b> " . $http_code . " (Esperado: 201 Created)<br><br>";

if ($http_code >= 200 && $http_code < 300) {
    echo "<span style='color: green; font-weight: bold;'>✅ SUCESSO! O Supabase aceitou os dados.</span><br>";
    echo "<b>Dados gravados retornados pelo banco:</b><br>";
    echo "<pre>";
    print_r(json_decode($resposta, true) ?: $resposta);
    echo "</pre>";
} else {
    echo "<span style='color: red; font-weight: bold;'>❌ FALHA! O Supabase rejeitou a inserção.</span><br>";
    echo "<b>Mensagem detalhada do erro:</b><br>";
    echo "<pre>";
    print_r(json_decode($resposta, true) ?: $resposta);
    echo "</pre>";
}
?>