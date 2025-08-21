<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "score";

$conexao = new mysqli($host, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    echo json_encode(["sucesso" => false, "erro" => "Falha na conexão"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rm = intval($_POST['rm']);
    $senha = $_POST['senha'];

    $sql = "SELECT COUNT(*) FROM usuario WHERE RM = ? AND Senha = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("is", $rm, $senha);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();

    if ($total > 0) {
        $_SESSION['rm'] = $rm;
        echo json_encode(["sucesso" => true]);
    } else {
        echo json_encode(["sucesso" => false, "erro" => "RM ou senha incorretos"]);
    }
}
?>
