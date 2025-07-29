<?php
// File: /_html/_dashboard/index.php

// 1) Verifica sessão — ajustado para apontar à pasta _php
require __DIR__ . '/../../_php/shared/verify_session.php';

// 2) Inclui o menu a partir de _php
include __DIR__ . '/../../_php/_menu/menu.php';

// 3) Carrega dados do dashboard
$stats = include __DIR__ . '/../../_php/_dashboard/dashboard_data.php';
$role = $_SESSION['user_papel'] ?? 'vendedor';
$isManager = in_array($role, ['admin','gerente'], true);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard — consórcioBCC</title>
  <link rel="stylesheet" href="/_consorcioBcc/_css/_dashboard/style.css">
  <link rel="stylesheet" href="/_consorcioBcc/_css/_menu/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <main class="dashboard-wrapper">

    <!-- Filtro de período -->
    <form id="filter-form" method="get" class="filter-form">
      <label for="month">Mês:
        <input id="month" type="number" name="month" min="1" max="12"
               value="<?= htmlspecialchars($stats['filterMonth']) ?>">
      </label>
      <label for="year">Ano:
        <input id="year" type="number" name="year" min="2000" max="2100"
               value="<?= htmlspecialchars($stats['filterYear']) ?>">
      </label>
      <button type="submit">Aplicar</button>
    </form>

    <!-- Cards de Resumo -->
    <section class="dashboard-cards">
      <div class="card">
        <h2>Clientes (mês)</h2>
        <span><?= $stats['countClientsMonth'] ?></span>
      </div>
      <div class="card">
        <h2>Vendas (mês)</h2>
        <span><?= $stats['countSalesMonth'] ?></span>
      </div>
      <div class="card">
        <h2>Comissão paga</h2>
        <span>R$ <?= number_format($stats['sumCommissionMonth'], 2, ',', '.') ?></span>
      </div>
    </section>

    <div class="divider"></div>

    <!-- Gráficos -->
    <section class="dashboard-graphs">
      <div class="graph-card">
        <h3>Clientes por vendedor</h3>
        <canvas id="chart-clients"></canvas>
      </div>
      <div class="graph-card">
        <h3>Vendas semanais</h3>
        <canvas id="chart-sales"></canvas>
      </div>
      <div class="graph-card">
        <h3>Parcelas pendentes</h3>
        <canvas id="chart-parcels"></canvas>
      </div>
    </section>

    <div class="divider"></div>

    <!-- Tabelas de Dados -->
    <section class="dashboard-analytics">
      <!-- Parcelas pendentes -->
      <article class="analytics-group">
        <h3>Parcelas pendentes (mês)</h3>
        <div class="table-responsive">
          <table class="analytics-table">
            <thead>
              <tr>
                <th>Parcela</th>
                <th>Contrato</th>
                <th>Vencimento</th>
                <th>Ação</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($stats['pendingParcels'])): ?>
                <tr>
                  <td colspan="4" class="text-center">Nenhuma parcela pendente.</td>
                </tr>
              <?php else: foreach ($stats['pendingParcels'] as $p): ?>
                <tr>
                  <td><?= $p['numero_parcela'] ?></td>
                  <td><?= htmlspecialchars($p['numero_contrato']) ?></td>
                  <td><?= date('d/m/Y', strtotime($p['data_prevista'])) ?></td>
                  <td>
                    <?php if ($isManager): ?>
                      <a href="/_consorcioBcc/_php/_parcelas_comissao/list.php?venda=<?= $p['id_venda'] ?>"
                         class="btn-link">Ver</a>
                    <?php else: ?>
                      — <!-- ou deixe em branco, ou exiba “Não permitido” -->
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </article>

      <!-- Vendas filtradas -->
      <article class="analytics-group filter-sales">
        <h3>Vendas por período</h3>
        <div class="table-responsive">
          <table class="analytics-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Contrato</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Valor</th>
                <th>Data</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($stats['filteredSales'])): ?>
                <tr>
                  <td colspan="6" class="text-center">Nenhuma venda.</td>
                </tr>
              <?php else: foreach ($stats['filteredSales'] as $s): ?>
                <tr>
                  <td><?= $s['id_venda'] ?></td>
                  <td><?= htmlspecialchars($s['numero_contrato']) ?></td>
                  <td><?= htmlspecialchars($s['cliente']) ?></td>
                  <td><?= htmlspecialchars($s['vendedor']) ?></td>
                  <td>R$ <?= number_format($s['valor_total'], 2, ',', '.') ?></td>
                  <td><?= date('d/m/Y', strtotime($s['data_venda'])) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </article>
    </section>

  </main>

  <script id="chart-data" type="application/json"
          data-clients-labels='<?= json_encode(array_column($stats["clientsByUser"], "nome")) ?>'
          data-clients-data='<?= json_encode(array_column($stats["clientsByUser"], "qtd")) ?>'
          data-sales-labels='<?= json_encode(array_column($stats["salesWeekByUser"], "nome")) ?>'
          data-sales-data='<?= json_encode(array_column($stats["salesWeekByUser"], "vendas")) ?>'
          data-parcels-count='<?= count($stats["pendingParcels"]) ?>'>
  </script>
  <script src="/_consorcioBcc/_js/_dashboard/dashboard.js"></script>
</body>
</html>
