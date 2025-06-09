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
        <?php foreach ($menu ?? [] as $link => $nome): ?>
          <li class="nav-item"><a href="<?= $link ?>" class="nav-link"><?= $nome ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-user-actions">
        <span class="user-name"><?= htmlspecialchars($nomeP ?? '') ?></span>
        <form action="../../_php/_login/deslogar.php" method="post" class="logout-form">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>
    <div class="detalhes-container">
      <h2>Detalhes da Venda</h2>

      <p><strong>Nº do Contrato:</strong> <?= htmlspecialchars($venda['num_contrato']) ?></p>
      <p><strong>Tipo:</strong> <?= htmlspecialchars($venda['tipo']) ?></p>
      <p><strong>Valor:</strong> R$ <?= number_format($venda['valor'], 2, ',', '.') ?></p>
      <p><strong>Data da Venda:</strong> <?= date('d/m/Y', strtotime($venda['dataV'])) ?></p>
      <p><strong>Administradora:</strong> <?= htmlspecialchars($venda['nome_adm']) ?></p>
      <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente) ?></p>
      <p><strong>Funcionário(s):</strong>
        <?php
          if (count($funcionarios) === 0) {
            echo '—';
          } else {
            echo htmlspecialchars(implode(', ', array_column($funcionarios, 'nome')));
          }
        ?>
      </p>
      <p><strong>Status Global:</strong>
        <?= $venda['confirmada']
            ? '<span class="status-confirmada">Venda Confirmada</span>'
            : '<span class="status-pendente">Pendente de Confirmação</span>' ?>
      </p>

      <h3>Status das Parcelas</h3>
      <?php if (!empty($notificacoes)): ?>
      <table class="parcelas-table">
        <thead>
          <tr>
            <th>Parcela</th>
            <th>Data Prevista</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($notificacoes as $not): ?>
          <tr>
            <td><?= $not['parcela'] ?></td>
            <td><?= date('d/m/Y', strtotime($not['data_criacao'])) ?></td>
            <td>
              <?= $not['lida']
                  ? '<span class="status-confirmada">Confirmada</span>'
                  : '<span class="status-pendente">Pendente</span>' ?>
            </td>
            <td>
              <?php if (!$not['lida'] && canConfirm((int)$not['parcela'], $acesso)): ?>
                <form action="../../_php/_confirmar/confirmaVenda.php" method="post" class="form-parcela">
                  <input type="hidden" name="idVenda" value="<?= (int)$venda['id'] ?>">
                  <input type="hidden" name="parcela"  value="<?= (int)$not['parcela'] ?>">
                  <input type="hidden" name="idFun"    value="<?= (int)$_SESSION['user_id'] ?>">
                  <button type="submit" class="btn-confirmar-parcela">Confirmar</button>
                </form>
              <?php else: ?>
                &mdash;
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p>Não há notificações de parcelas para esta venda.</p>
      <?php endif; ?>

      <?php if (!$venda['confirmada']): ?>
      <form action="../../_php/_confirmar/recusarVenda.php" method="post">
        <input type="hidden" name="idVenda" value="<?= (int)$venda['id'] ?>">
        <button type="submit" class="btn-recusar-venda">Recusar Venda</button>
      </form>
      <?php endif; ?>

      <a href="../../_html/_lista/listaVenda.php" class="btn-voltar">Voltar à Lista</a>
    </div>
  </div>
  <script src="../../_js/_menu/menu.js"></script>
</body>
</html>
