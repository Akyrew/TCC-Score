<?php
// publicar_chave.php
header('Content-Type: application/json'); // sempre no topo!

session_start();
require 'db.php'; // cria $pdo (PDO)

$rm = $_SESSION['RM'] ?? null;
$chavePublica = $_POST['chavePublica'] ?? '';

if (!$rm || !$chavePublica) {
    http_response_code(400);
    echo json_encode(['erro' => 'RM ou chave pública ausentes']);
    exit;
}

$stmt = $pdo->prepare('UPDATE usuario SET ChavePublica = :pub WHERE RM = :rm');
$stmt->execute([':pub' => $chavePublica, ':rm' => $rm]);

echo json_encode(['ok' => true]);
exit;
