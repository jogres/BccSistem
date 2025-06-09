<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

// Cálculos gerais do dashboard (totais, vendas, clientes)
include('../../_php/_dashboard/dashboard.php');

// Inicializações
$notificacoes    = [];
$oldNotificacoes = [];
$clientesHoje    = 0;
$clientesSemana  = 0;
$aniversariantes = [];
$vendasPorFunc   = [];
$clientesPorFunc = [];

if ($isAdmin) {
    // 1) Notificações pendentes: apenas 1 por venda, com contagem de pendentes
    $stmt = $pdo->prepare(
        "SELECT
            n.idVenda,
            MAX(n.data_criacao) AS data_criacao,
            COUNT(*)          AS pendentes,
            SUBSTRING_INDEX(GROUP_CONCAT(n.mensagem ORDER BY n.data_criacao DESC SEPARATOR '||'),'||',1) AS mensagem,
            SUBSTRING_INDEX(GROUP_CONCAT(n.link    ORDER BY n.data_criacao DESC SEPARATOR '||'),'||',1) AS link
         FROM notificacoes n
         INNER JOIN venda v ON n.idVenda = v.id
         WHERE n.idFun = ? AND n.lida = 0 AND v.confirmada = 0
         GROUP BY n.idVenda
         ORDER BY data_criacao DESC"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2) Notificações antigas (lidas ou vendas confirmadas): 1 por venda, últimas 10
    $stmt2 = $pdo->prepare(
        "SELECT
            n.idVenda,
            MAX(n.data_criacao) AS data_criacao,
            SUBSTRING_INDEX(GROUP_CONCAT(n.mensagem ORDER BY n.data_criacao DESC SEPARATOR '||'),'||',1) AS mensagem,
            SUBSTRING_INDEX(GROUP_CONCAT(n.link    ORDER BY n.data_criacao DESC SEPARATOR '||'),'||',1) AS link
         FROM notificacoes n
         INNER JOIN venda v ON n.idVenda = v.id
         WHERE n.idFun = ? AND (n.lida = 1 OR v.confirmada = 1)
         GROUP BY n.idVenda
         ORDER BY data_criacao DESC
         LIMIT 10"
    );
    $stmt2->execute([$_SESSION['user_id']]);
    $oldNotificacoes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // 3) Clientes cadastrados hoje
    $clientesHoje = (int) $pdo->query(
        "SELECT COUNT(*) FROM cad_cli WHERE DATE(cadDT) = CURDATE()"
    )->fetchColumn();

    // 4) Clientes cadastrados esta semana
    $clientesSemana = (int) $pdo->query(
        "SELECT COUNT(*) FROM cad_cli WHERE YEARWEEK(cadDT,1) = YEARWEEK(CURDATE(),1)"
    )->fetchColumn();

    // 5) Aniversariantes do dia
    $stmtA = $pdo->query(
        "SELECT nome FROM cad_fun WHERE DAY(dataN)=DAY(CURDATE()) AND MONTH(dataN)=MONTH(CURDATE())"
    );
    $aniversariantes = $stmtA->fetchAll(PDO::FETCH_COLUMN);

    // 6) Desempenho: vendas por funcionário
    $stmtV = $pdo->query(
        "SELECT f.nome AS funcionario, COUNT(vf.idVenda) AS total_vendas
           FROM venda_fun vf
           JOIN cad_fun f ON vf.idFun = f.idFun
          GROUP BY vf.idFun"
    );
    $vendasPorFunc = $stmtV->fetchAll(PDO::FETCH_ASSOC);

    // 7) Desempenho: clientes por funcionário
    $stmtC = $pdo->query(
        "SELECT f.nome AS funcionario, COUNT(c.idCli) AS total_clientes
           FROM cad_cli c
           JOIN cad_fun f ON c.idFun = f.idFun
          GROUP BY c.idFun"
    );
    $clientesPorFunc = $stmtC->fetchAll(PDO::FETCH_ASSOC);
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

    <main class="dashboard">
      <h2>Bem-vindo, <?= htmlspecialchars($nomeP) ?></h2>

      <?php if ($isAdmin): ?>
      <section class="notificacoes">
        <h3>Notificações Recentes</h3>
        <?php if (!empty($notificacoes)): ?>
          <?php foreach ($notificacoes as $notif): ?>
            <div class="notificacao" data-id-venda="<?= $notif['idVenda'] ?>">
              <a href="<?= htmlspecialchars($notif['link']) ?>">
                <?= htmlspecialchars($notif['mensagem']) ?>
                <?php if ($notif['pendentes'] > 1): ?>
                  <span class="badge"><?= $notif['pendentes'] ?></span>
                <?php endif; ?>
              </a>
              <small><?= date('d/m/Y H:i', strtotime($notif['data_criacao'])) ?></small>
              <button class="btn-marcar-lida" data-id-venda="<?= $notif['idVenda'] ?>">Marcar tudo como lida</button>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>Sem notificações pendentes.</p>
        <?php endif; ?>

        <?php if (!empty($oldNotificacoes)): ?>
        <details class="old-notifs">
          <summary>Notificações Antigas</summary>
          <div class="old-list">
            <?php foreach ($oldNotificacoes as $old): ?>
              <div class="notificacao antiga">
                <a href="<?= htmlspecialchars($old['link']) ?>"><?= htmlspecialchars($old['mensagem']) ?></a>
                <small><?= date('d/m/Y H:i', strtotime($old['data_criacao'])) ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        </details>
        <?php endif; ?>
      </section>
      <?php endif; ?>

      <div class="stats">
        <div class="stat-card">
          <h3>Clientes cadastrados hoje</h3>
          <p><?= $clientesHoje ?></p>
        </div>
        <div class="stat-card">
          <h3>Clientes cadastrados esta semana</h3>
          <p><?= $clientesSemana ?></p>
        </div>
        <div class="stat-card">
          <h3>Total de Vendas (Mês)</h3>
          <p>R$ <?= number_format($totalVendas, 2, ',', '.') ?></p>
        </div>
        <div class="stat-card">
          <h3>Total Comissão (Mês)</h3>
          <p>R$ <?= number_format($totalComissao, 2, ',', '.') ?></p>
        </div>
        <div class="stat-card aniversariantes">
          <h3>Aniversariantes do Dia</h3>
          <?php if (!empty($aniversariantes)): ?>
            <?php foreach ($aniversariantes as $nomeAniv): ?>
              <p><?= htmlspecialchars($nomeAniv) ?></p>
            <?php endforeach; ?>
          <?php else: ?>
            <p>Nenhum aniversariante hoje</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="charts">
        <div class="chart-container">
          <h4>Comparativo: Clientes cadastrados vs Vendas realizadas</h4>
          <canvas id="perfChart"></canvas>
        </div>
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
  <script><?php include('../../_js/_dashboard/dashboard.php'); ?></script>
  <script src="../../_js/_dashboard/notificacao.js"></script>
</body>
</html>
