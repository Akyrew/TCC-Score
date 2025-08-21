<?php
session_start();

/* ====== CONEXÃO COM O BANCO ====== */
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "score";
$conexao = new mysqli($host, $usuario, $senha, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

/* ====== GARANTE LOGIN ====== */
if (!isset($_SESSION['rm'])) {
    die("Acesso negado: usuário não logado.");
}
$rmLogado = intval($_SESSION['rm']);

/* ====== LISTA DE USUÁRIOS (para a coluna da esquerda) ====== */
$stmtUsers = $conexao->prepare("SELECT RM, Nome FROM usuario WHERE RM <> ? ORDER BY Nome ASC");
$stmtUsers->bind_param("i", $rmLogado);
$stmtUsers->execute();
$usuarios = $stmtUsers->get_result()->fetch_all(MYSQLI_ASSOC);

/* ====== CONVERSA ATUAL (se um destino foi escolhido) ====== */
$rmDestino = isset($_GET['destino']) ? intval($_GET['destino']) : 0;
$mensagens = [];
if ($rmDestino) {
    $stmtMsg = $conexao->prepare("
        SELECT ID, RM, RMDestinatario, MensagemCifrada, ChaveAESCriptografada, IV, DataEnvio
        FROM mensagem
        WHERE (RM = ? AND RMDestinatario = ?) OR (RM = ? AND RMDestinatario = ?)
        ORDER BY DataEnvio ASC
    ");
    $stmtMsg->bind_param("iiii", $rmLogado, $rmDestino, $rmDestino, $rmLogado);
    $stmtMsg->execute();
    $mensagens = $stmtMsg->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ====== PEGA NOME DO DESTINO (para o header da conversa) ====== */
$nomeDestino = "";
if ($rmDestino) {
    $stmtNome = $conexao->prepare("SELECT Nome FROM usuario WHERE RM = ? LIMIT 1");
    $stmtNome->bind_param("i", $rmDestino);
    $stmtNome->execute();
    $rowNome = $stmtNome->get_result()->fetch_assoc();
    $nomeDestino = $rowNome ? $rowNome['Nome'] : "Usuário $rmDestino";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Chat E2EE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="style.css" />
  <script src="script.js"></script>
  <style>
    /* Ajustes mínimos caso seu style.css não tenha */
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial}
    .container{display:grid;grid-template-columns:280px 1fr;height:100vh}
    .sidebar{border-right:1px solid #eee;display:flex;flex-direction:column}
    .sidebar-header{display:flex;gap:.5rem;align-items:center;padding:.75rem;border-bottom:1px solid #eee}
    .campo-busca{flex:1;padding:.5rem;border:1px solid #ddd;border-radius:.5rem}
    .lista-usuarios{overflow:auto;padding:.5rem}
    .btn-usuario{width:100%;text-align:left;padding:.6rem .8rem;border:0;background:#fff;border-radius:.6rem;margin:.25rem 0;cursor:pointer}
    .btn-usuario:hover{background:#f5f5f5}
    .chat{display:flex;flex-direction:column;height:100vh}
    .chat-header{padding:.9rem 1rem;border-bottom:1px solid #eee;display:flex;align-items:center;gap:.75rem}
    .chat-body{flex:1;overflow:auto;padding:1rem;background:#fafafa}
    .mensagem{max-width:70%;padding:.6rem .8rem;border-radius:1rem;margin:.25rem 0;word-wrap:break-word}
    .minha{background:#dff6dd;margin-left:auto}
    .deles{background:#fff}
    .mensagem small{display:block;opacity:.6;font-size:.75rem;margin-top:.25rem}
    .chat-footer{border-top:1px solid #eee;padding:.75rem;background:#fff;display:flex;gap:.5rem}
    .chat-footer textarea{flex:1;resize:none;padding:.6rem;border:1px solid #ddd;border-radius:.6rem;height:48px}
    .chat-footer button{padding:.6rem .9rem;border:0;border-radius:.6rem;background:#0d6efd;color:#fff;cursor:pointer}
    .muted{opacity:.6}
    .cadeado{margin-right:.4rem}
    .vazio{display:flex;align-items:center;justify-content:center;color:#888}
  </style>
</head>
<body>
<div class="container">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <input type="text" id="filtroUsuario" class="campo-busca" placeholder="Buscar pessoa..." />
    </div>
    <div class="lista-usuarios" id="listaUsuarios">
      <?php foreach ($usuarios as $u): ?>
        <button class="btn-usuario"
                onclick="window.location.href='chat.php?destino=<?= $u['RM'] ?>'"
                data-rm="<?= $u['RM'] ?>">
          <?= htmlspecialchars($u['Nome']) ?> <small class="muted">RM <?= $u['RM'] ?></small>
        </button>
      <?php endforeach; ?>
    </div>
  </aside>

  <!-- Área de chat -->
  <main class="chat">
    <div class="chat-header">
      <?php if ($rmDestino): ?>
        <strong>Conversando com <?= htmlspecialchars($nomeDestino) ?></strong>
        <span class="muted">· RM <?= $rmDestino ?></span>
      <?php else: ?>
        <strong>Selecione alguém para conversar</strong>
      <?php endif; ?>
      <span style="margin-left:auto" class="muted">Você: RM <?= $rmLogado ?></span>
    </div>

    <div class="chat-body" id="chatBody">
      <?php if (!$rmDestino): ?>
        <div class="vazio">Nenhuma conversa aberta</div>
      <?php else: ?>
        <?php foreach ($mensagens as $m):
          $souEu = ($m['RM'] == $rmLogado);
          $classe = $souEu ? 'minha' : 'deles';
        ?>
          <div class="mensagem <?= $classe ?>"
               data-id="<?= $m['ID'] ?>"
               data-rm="<?= $m['RM'] ?>"
               data-rm-destino="<?= $m['RMDestinatario'] ?>"
               data-ciphertext="<?= htmlspecialchars($m['MensagemCifrada']) ?>"
               data-chave="<?= htmlspecialchars($m['ChaveAESCriptografada']) ?>"
               data-iv="<?= htmlspecialchars($m['IV']) ?>">
            <span class="cadeado">🔒</span> mensagem cifrada
            <?php if (!empty($m['DataEnvio'])): ?>
              <small><?= htmlspecialchars($m['DataEnvio']) ?></small>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="chat-footer">
      <?php if ($rmDestino): ?>
        <form id="formEnvio" style="display:contents">
  <textarea id="texto" placeholder="Escreva..." <?= $rmDestino ? '' : 'disabled' ?>></textarea>
  <button id="btnEnviar" type="submit" <?= $rmDestino ? '' : 'disabled' ?>>Enviar</button>
</form>


      <?php else: ?>
        <textarea disabled class="muted" placeholder="Abra uma conversa para digitar..."></textarea>
        <button disabled>Enviar</button>
      <?php endif; ?>
    </div>
  </main>
</div>

<script>
  // Filtro da lista de usuários
  document.getElementById('filtroUsuario')?.addEventListener('input', function () {
    const termo = this.value.toLowerCase();
    document.querySelectorAll('.btn-usuario').forEach(btn => {
      btn.style.display = btn.textContent.toLowerCase().includes(termo) ? 'block' : 'none';
    });
  });

  // Exponho algumas infos para o script.js
  window.__CHAT__ = {
    rmLogado: <?= json_encode($rmLogado) ?>,
    rmDestino: <?= json_encode($rmDestino) ?>,
    temDestino: <?= $rmDestino ? 'true' : 'false' ?>
  };
</script>

</body>
</html>
