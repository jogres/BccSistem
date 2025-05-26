<?php
  include('../../_php/_login/logado.php');
  include('../../_php/_dashboard/dashboard.php');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="../../_css/_menu/menu.css">
  <link rel="stylesheet" href="../../_css/_dashboard/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="container">
    <nav class="main-nav">
      <button class="menu-toggle" aria-label="Abrir menu">&#9776;</button>
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

    <main class="dashboard">
      <section class="stats">
        <div class="stat-card">
          <h3>Total de Vendas (Mês)</h3>
          <p>R$ <?= number_format($totalVendas, 2, ',', '.') ?></p>
        </div>
        <?php if (!$isAdmin): ?>
        <div class="stat-card">
          <h3>Total Clientes (Mês)</h3>
          <p><?= array_sum(array_values($clientesData)) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
        <div class="stat-card">
          <h3>Total Clientes Cadastrados</h3>
          <p><?= $totalClientes ?></p>
        </div>
        <?php endif; ?>
        <div class="stat-card">
          <h3>Total Comissão (Mês)</h3>
          <p>R$ <?= number_format($totalComissao, 2, ',', '.') ?></p>
        </div>
      </section>

      <section class="charts">
        <div class="chart-container">
          <h4><?= $isAdmin ? 'Vendas Totais por Dia' : 'Minhas Vendas por Dia' ?></h4>
          <canvas id="salesChart"></canvas>
        </div>
        <?php if (!$isAdmin): ?>
        <div class="chart-container">
          <h4>Meus Clientes por Dia</h4>
          <canvas id="clientsChart"></canvas>
        </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
    document.querySelector('.menu-toggle').addEventListener('click', () => {
      document.querySelector('.nav-links').classList.toggle('open');
    });

    const dias = <?= json_encode($dias) ?>;
    const vendasData = <?= json_encode($vendasData) ?>;

    new Chart(document.getElementById('salesChart'), {
      type: 'line',
      data: { labels: dias, datasets:[{ label:'Vendas', data:vendasData, fill:false, tension:0.3 }] },
      options:{ scales:{ x:{ title:{ display:true,text:'Dia do Mês' }}, y:{ beginAtZero:true } } }
    });

    <?php if (!$isAdmin): ?>
    const clientesData = <?= json_encode($clientesData) ?>;
    new Chart(document.getElementById('clientsChart'), {
      type:'bar',
      data:{ labels:dias, datasets:[{ label:'Clientes', data:clientesData, barPercentage:0.6 }] },
      options:{ scales:{ x:{ title:{ display:true,text:'Dia do Mês' }}, y:{ beginAtZero:true } } }
    });
    <?php endif; ?>
  </script>
</body>
</html>
