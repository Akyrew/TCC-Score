<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "score"; // nome do seu banco

$conexao = new mysqli($host, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    echo json_encode(["sucesso" => false, "erro" => "Falha na conexão: " . $conexao->connect_error]);
    exit;
}

// Puxa os locais da tabela 'computador'
$sql = "SELECT DISTINCT Local FROM computador ORDER BY Local ASC";
$result = $conexao->query($sql);

if (!$result) {
    echo json_encode(["erro" => "Erro na consulta: " . $conexao->error]);
    exit;
}

$locais = [];
while ($row = $result->fetch_assoc()) {
    $locais[] = $row['Local'];
}

echo json_encode($locais);
