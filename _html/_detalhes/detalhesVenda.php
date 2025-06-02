<?php
include('../../_php/_login/logado.php');
include('../../_php/_detalhe/detalhesVenda.php');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Detalhes da Venda</title>
  <link rel="stylesheet" href="../../_css/_detalhes/detalhesVenda.css">
  <link rel="stylesheet" href="../../_css/_menu/menu.css">
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
    <div class="detalhes-container"> <!-- TROQUEI form-container por detalhes-container -->
          <h2>Detalhes da Venda</h2>

          <p><strong>Nº do Contrato:</strong> <?= htmlspecialchars($venda['num_contrato']) ?></p>
          <p><strong>Tipo:</strong> <?= htmlspecialchars($venda['tipo']) ?></p>
          <p><strong>Valor:</strong> R$ <?= number_format($venda['valor'], 2, ',', '.') ?></p>
          <p><strong>Data da Venda:</strong> <?= date('d/m/Y', strtotime($venda['dataV'])) ?></p>
          <p><strong>Administradora:</strong> <?= htmlspecialchars($venda['nome_adm']) ?></p>
          <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente) ?></p>
          <p><strong>Funcionário(s):</strong> <?= htmlspecialchars(implode(', ', $funcionarios)) ?></p>

          <p><strong>Status:</strong>
            <?= $venda['confirmada'] ? '<span class="status-confirmada">Venda Confirmada</span>' : '<span class="status-pendente">Pendente de Confirmação</span>' ?>
          </p>

          <?php if (!$venda['confirmada']): ?>
            <form action="../../_php/_confirmar/confirmaVenda.php" method="post" >
              <input type="hidden" name="idVenda" value="<?= (int) $venda['id'] ?>">
              <button type="submit" class="btn-confirmar">Confirmar Venda</button>
            </form>
          
            <form action="../../_php/_confirmar/recusarVenda.php" method="post" >
              <input type="hidden" name="idVenda" value="<?= (int) $venda['id'] ?>">
              <button type="submit" class="btn-recusar">Recusar Venda</button>
            </form>
          <?php endif; ?>
    </div>
  </div>
  <script src="../../_js/_menu/menu.js"></script>
</body>
</html>
