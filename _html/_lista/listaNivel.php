<?php
  include('../../_php/_login/logado.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../_css/_menu/menu.css">
  <link rel="stylesheet" href="../../_css/_menu/paginacao.css">
  <link rel="stylesheet" href="../../_css/_listas/listas.css">
  <title>Lista de Niveis</title>
</head>
<body>
  <div class="container">
    <button class="menu-toggle float" aria-label="Abrir menu">&#9776;</button>
    <nav class="main-nav">
      <button class="menu-toggle inmenu" aria-label="Fechar menu">&#9776;</button>
      <ul class="nav-links">
        <?php foreach ($menu as $link => $nome): ?>
          <li class="nav-item"><a href="<?= $link ?>" class="nav-link"><?= $nome ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-user-actions">
        <span class="user-name"><?= htmlspecialchars($nomeP) ?></span>
        <form action="../../_php/_login/deslogar.php" method="post" class="logout-form">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>
    <div class="lista-container">
          <?php
            include('../../_php/_lista/_listaComum/listaNivel.php');
          ?>
    </div>
  </div>
  <script src="../../_js/_menu/menu.js"></script>
</body>
</html>