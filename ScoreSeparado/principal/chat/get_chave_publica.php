<?php
// get_chave_publica.php
header('Content-Type: application/json'); // Sempre primeiro!

require 'db.php';

$rm = isset($_GET['rm']) ? (int)$_GET['rm'] : 0;

$stmt = $pdo->prepare('SELECT ChavePublica FROM usuario WHERE RM = :rm LIMIT 1');
$stmt->execute([':rm' => $rm]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['ChavePublica'])) {
    http_response_code(404);
    echo json_encode(['erro' => 'Chave pública não encontrada']);
    exit;
}

echo json_encode(['chavePublica' => $row['ChavePublica']]);
exit;
