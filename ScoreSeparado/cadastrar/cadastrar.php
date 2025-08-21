<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "score";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$nome = $_POST['nome'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$email = $_POST['email'] ?? '';
$confirmar_email = $_POST['confirmar_email'] ?? '';
$senha = $_POST['senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';

if ($email !== $confirmar_email || $senha !== $confirmar_senha) {
    die("E-mails ou senhas não coincidem!");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("E-mail inválido!");
}
if (strlen($senha) < 6) {
    die("Senha deve ter pelo menos 6 caracteres!");
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// ==== Gera par de chaves RSA ====
$config = [
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
];
$resource = openssl_pkey_new($config);
if (!$resource) {
    die("Erro ao gerar chave: " . openssl_error_string());
}

openssl_pkey_export($resource, $chavePrivada);
$detalhes = openssl_pkey_get_details($resource);
$chavePublica = $detalhes['key'];

// ==== Insere no banco sem RM ====
$stmt = $conn->prepare("INSERT INTO usuario (Nome, CPF, Tipo, Email, Senha, chave_publica, chave_privada) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $nome, $cpf, $tipo, $email, $senhaHash, $chavePublica, $chavePrivada);

if ($stmt->execute()) {
    // Pega o RM gerado automaticamente
    $novoRM = $stmt->insert_id;
    echo "Usuário cadastrado com sucesso! RM: " . $novoRM;
} else {
    echo "Erro ao cadastrar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
