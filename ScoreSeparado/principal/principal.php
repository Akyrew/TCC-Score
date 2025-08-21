<?php
session_start();
include("../conexao.php"); // ajuste caminho se necessário

// Consulta os locais dos computadores
$sql = "SELECT DISTINCT Local FROM computador";
$result = $conexao->query($sql);
$locais = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locais[] = $row['Local'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Principal</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <header>
        <h1>Painel Principal</h1>
        <p>Escolha o local desejado</p>
    </header>

    <main class="botoes-container">
        <?php foreach ($locais as $local): ?>
            <button class="btn-local" data-local="<?= htmlspecialchars($local) ?>">
                <?= htmlspecialchars($local) ?>
            </button>
        <?php endforeach; ?>
    </main>
</body>
</html>
