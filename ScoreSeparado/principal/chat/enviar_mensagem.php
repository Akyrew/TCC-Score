<?php
// enviar_mensagem.php
session_start();
require 'db.php'; // certifique-se de que não dá 'echo' aqui

// Sempre definir o tipo de retorno como JSON antes de qualquer coisa
header('Content-Type: application/json'); 

$rmOrigem = $_SESSION['RM'] ?? null;
$rmDestino = isset($_POST['rmDestino']) ? (int)$_POST['rmDestino'] : null;
$msg      = $_POST['mensagem'] ?? '';
$chave    = $_POST['chave'] ?? '';
$iv       = $_POST['iv'] ?? '';

if (!$rmOrigem || !$rmDestino || !$msg || !$chave || !$iv) {
    http_response_code(400);
    echo json_encode(['erro' => 'Campos obrigatórios ausentes']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        INSERT INTO mensagem (MensagemCifrada, ChaveAESCriptografada, IV, RM, RMDestinatario)
        VALUES (:m, :k, :iv, :rm, :dest)
    ');
    $stmt->execute([
        ':m'   => $msg,
        ':k'   => $chave,
        ':iv'  => $iv,
        ':rm'  => $rmOrigem,
        ':dest'=> $rmDestino
    ]);

    echo json_encode([
        'ok' => true, 
        'id' => $pdo->lastInsertId()
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Erro interno ao salvar',
        'detalhe' => $e->getMessage()
    ]);
    exit;
}
