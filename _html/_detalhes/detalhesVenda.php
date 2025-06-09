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
        <form action="../../_php/_login/deslogar.php" method="post">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>
    <div class="detalhes-container">
      <h2>Detalhes da Venda</h2>
      <p><strong>Contrato:</strong> <?= htmlspecialchars($venda['num_contrato']) ?></p>
      <p><strong>Tipo:</strong> <?= htmlspecialchars($venda['tipo']) ?></p>
      <p><strong>Valor:</strong> R$ <?= number_format($venda['valor'],2,',','.') ?></p>
      <p><strong>Data:</strong> <?= date('d/m/Y',strtotime($venda['dataV'])) ?></p>
      <p><strong>Administradora:</strong> <?= htmlspecialchars($venda['nome_adm']) ?></p>
      <p><strong>Segmento:</strong> <?= htmlspecialchars($venda['segmento']) ?></p>
      <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente) ?></p>
      <p><strong>Funcionários:</strong>
        <?php
          echo $funcionarios
            ? htmlspecialchars(implode(', ', array_column($funcionarios,'nome')))
            : '—';
        ?>
      </p>
      <p><strong>Status Global:</strong>
        <?= $venda['confirmada']
            ? '<span class="status-confirmada">Confirmada</span>'
            : '<span class="status-pendente">Pendente</span>' ?>
      </p>

      <h3>Parcelas</h3>
      <?php if ($notificacoes): ?>
      <table class="parcelas-table">
        <thead>
          <tr><th>Parcela</th><th>Data</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
          <?php foreach ($notificacoes as $not): ?>
          <tr>
            <td><?= $not['parcela'] ?></td>
            <td><?= date('d/m/Y',strtotime($not['data_criacao'])) ?></td>
            <td><?= $not['lida']
                  ? '<span class="status-confirmada">Confirmada</span>'
                  : '<span class="status-pendente">Pendente</span>' ?></td>
            <td>
              <?php if (!$not['lida'] && canConfirm($not['parcela'],$acesso)): ?>
              <form action="../../_php/_confirmar/confirmaVenda.php" method="post">
                <input type="hidden" name="idVenda"  value="<?= $venda['id'] ?>">
                <input type="hidden" name="parcela"  value="<?= $not['parcela'] ?>">
                <input type="hidden" name="idFun"    value="<?= $not['idFun'] ?>">
                <button class="btn-confirmar-parcela" type="submit">Confirmar</button>
              </form>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p>Sem parcelas registradas.</p>
      <?php endif; ?>

      <?php if (!$venda['confirmada']): ?>
      <form action="../../_php/_confirmar/recusarVenda.php" method="post">
        <input type="hidden" name="idVenda" value="<?= $venda['id'] ?>">
        <button type="submit">Recusar Venda</button>
      </form>
      <?php endif; ?>

      <a href="../../_html/_lista/listaVenda.php" class="btn-voltar">Voltar à Lista</a>
    </div>
  </div>
  <script src="../../_js/_menu/menu.js"></script>
</body>
</html>