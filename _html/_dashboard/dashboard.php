<?php
  include('../../_php/_login/logado.php');
  include('../../_php/_dashboard/dashboard.php');
  require_once __DIR__ . '/../../config/db.php';

$notificacoes = [];
if ($isAdmin) {
  $stmt = $pdo->prepare("
    SELECT n.id, n.mensagem, n.link, n.data_criacao
    FROM notificacoes n
    INNER JOIN venda v ON n.idVenda = v.id
    WHERE n.idFun = ? AND n.lida = 0 AND v.confirmada = 0
    ORDER BY n.data_criacao DESC
  ");
  $stmt->execute([$_SESSION['user_id']]);
  $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
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
  <style>
    .notificacoes { margin: 20px 0; padding: 10px; background: #f9f9f9; border: 1px solid #ccc; }
    .notificacoes h3 { margin-bottom: 10px; }
    .notificacao { margin-bottom: 8px; }
    .notificacao a { color: #0077cc; text-decoration: none; }
    .notificacao small { color: #888; font-size: 0.9em; }
  </style>
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

    <main class="dashboard">
      <h2>Bem-vindo, <?= htmlspecialchars($nomeP) ?></h2>

      <?php if ($isAdmin && !empty($notificacoes)): ?>
        <section class="notificacoes">
          <h3>Notificações Recentes</h3>
          <?php foreach ($notificacoes as $notif): ?>
            <div class="notificacao" id="notificacao-<?= $notif['id'] ?>">
              <a href="<?= htmlspecialchars($notif['link']) ?>"><?= htmlspecialchars($notif['mensagem']) ?></a><br>
              <small><?= date('d/m/Y H:i', strtotime($notif['data_criacao'])) ?></small>
              <button class="btn-marcar-lida" data-id="<?= $notif['id'] ?>">Marcar como lida</button>
            </div>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>
          
      <!-- Grid dos cards principais (dois grandes em cima, dois menores embaixo) -->
      <div class="stats">
        <div class="stat-card">
          <h3>Total de Vendas (Mês)</h3>
          <p>R$ <?= number_format($totalVendas, 2, ',', '.') ?></p>
        </div>
        <div class="stat-card">
          <h3>Total Comissão (Mês)</h3>
          <p>R$ <?= number_format($totalComissao, 2, ',', '.') ?></p>
        </div>
        <div class="stat-card">
          <h3>
            <?php if ($isAdmin): ?>
              Total Clientes Cadastrados
            <?php else: ?>
              Total Clientes (Mês)
            <?php endif; ?>
          </h3>
          <p>
            <?php if ($isAdmin): ?>
              <?= $totalClientes ?>
            <?php else: ?>
              <?= array_sum(array_values($clientesData)) ?>
            <?php endif; ?>
          </p>
        </div>
        <div class="stat-card aniversariantes">
          <h3>Aniversariantes do Dia</h3>
          <p>
            <?php
              // Exemplo de array de aniversariantes
              if (!empty($aniversariantes)) {
                foreach ($aniversariantes as $aniv) {
                  echo htmlspecialchars($aniv) . '<br>';
                }
              } else {
                echo "Nenhum aniversariante hoje";
              }
            ?>
          </p>
        </div>
      </div>
            
      <div class="charts">
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
      </div>
    </main>
        
  </div>
  <script src="../../_js/_menu/menu.js"></script>  
  <script ><?php include('../../_js/_dashboard/dashboard.php'); ?></script>
  <script src="../../_js/_dashboard/notificacao.js"></script>


</body>
</html>
