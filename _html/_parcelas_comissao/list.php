<?php include __DIR__ . '/../../_php/_menu/menu.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Parcelas da Venda #<?= htmlspecialchars($contrato) ?></title>
  <link rel="stylesheet" href="/BccSistem/_css/_menu/style.css">
  <link rel="stylesheet" href="/BccSistem/_css/_dashboard/style.css">
  <link rel="stylesheet" href="/BccSistem/_css/_parcelas_comissao/style.css">

</head>
<body>


  <main class="dashboard-wrapper">
    <section class="dashboard-cards">
      <div class="card">
        <h2>Contrato</h2>
        <span>#<?= htmlspecialchars($contrato) ?></span>
      </div>
      <div class="card">
        <h2>Total de Parcelas</h2>
        <span><?= count($parcelas) ?></span>
      </div>
    </section>

    <div class="divider"></div>

    <section class="dashboard-analytics">
      <article class="analytics-group">
        <h3>Parcelas de Comissão</h3>
        <div class="table-responsive">
          <table class="analytics-table">
            <thead>
              <tr>
                <th>Parcela Nº</th>
                <th>Valor (R$)</th>
                <th>Vencimento</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($parcelas)): ?>
                <tr>
                  <td colspan="5" class="text-center">Nenhuma parcela encontrada.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($parcelas as $p): ?>
                  <tr>
                    <td><?= $p['numero_parcela'] ?></td>
                    <td><?= number_format($p['valor'], 2, ',', '.') ?></td>
                    <td><?= $p['vencimento'] ?></td>
                    <td class="<?= $p['status']==='paga'?'status-paid':'status-pending' ?>">
                      <?= ucfirst($p['status']) ?>
                    </td>
                    <td>
                      <?php if ($p['status'] !== 'paga'): ?>
                        <a href="/BccSistem/_php/_parcelas_comissao/process_confirm.php?venda=<?= $id_venda ?>&parcela=<?= $p['numero_parcela'] ?>"
                           class="btn-link">Confirmar</a>
                      <?php else: ?>
                        —
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
