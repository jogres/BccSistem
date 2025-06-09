<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';
// Inclui funções e cálculos do dashboard (totais, vendas, clientes)
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
    // Notificações Pendentes (agrupadas por idVenda)
    $stmt = $pdo->prepare(
        "SELECT n.idVenda, MAX(n.data_criacao) AS data_criacao, COUNT(*) AS pendentes
         FROM notificacoes n
         INNER JOIN venda v ON n.idVenda = v.id
         WHERE n.idFun = ? AND n.lida = 0 AND v.confirmada = 0
         GROUP BY n.idVenda
         ORDER BY data_criacao DESC"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($groups as $grp) {
        $sub = $pdo->prepare(
            "SELECT mensagem, link FROM notificacoes
             WHERE idFun = ? AND idVenda = ? AND lida = 0
             ORDER BY data_criacao DESC LIMIT 1"
        );
        $sub->execute([$_SESSION['user_id'], $grp['idVenda']]);
        $row = $sub->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $notificacoes[] = [
                'idVenda'      => $grp['idVenda'],
                'mensagem'     => $row['mensagem'],
                'link'         => $row['link'],
                'pendentes'    => $grp['pendentes'],
                'data_criacao' => $grp['data_criacao'],
            ];
        }
    }
    // Notificações Antigas
    $stmt2 = $pdo->prepare(
        "SELECT n.id, n.mensagem, n.link, n.data_criacao
         FROM notificacoes n
         INNER JOIN venda v ON n.idVenda = v.id
         WHERE n.idFun = ? AND (n.lida = 1 OR v.confirmada = 1)
         ORDER BY n.data_criacao DESC LIMIT 10"
    );
    $stmt2->execute([$_SESSION['user_id']]);
    $oldNotificacoes = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    // Clientes cadastrados hoje
    $stmtC1 = $pdo->prepare("SELECT COUNT(*) FROM cad_cli WHERE DATE(cadDT) = CURDATE()");
    $stmtC1->execute();
    $clientesHoje = (int)$stmtC1->fetchColumn();
    // Clientes cadastrados esta semana
    $stmtC2 = $pdo->prepare(
        "SELECT COUNT(*) FROM cad_cli
         WHERE YEARWEEK(cadDT,1) = YEARWEEK(CURDATE(),1)"
    );
    $stmtC2->execute();
    $clientesSemana = (int)$stmtC2->fetchColumn();
    // Aniversariantes
    $stmtA = $pdo->prepare(
        "SELECT nome FROM cad_fun
         WHERE DAY(dataN)=DAY(CURDATE()) AND MONTH(dataN)=MONTH(CURDATE())"
    );
    $stmtA->execute();
    $aniversariantes = $stmtA->fetchAll(PDO::FETCH_COLUMN);
    // Vendas por funcionário
    $stmtV = $pdo->prepare(
        "SELECT f.nome AS funcionario, COUNT(vf.idVenda) AS total_vendas
         FROM venda_fun vf
         JOIN cad_fun f ON vf.idFun = f.idFun
         GROUP BY vf.idFun"
    );
    $stmtV->execute();
    $vendasPorFunc = $stmtV->fetchAll(PDO::FETCH_ASSOC);
    // Clientes por funcionário
    $stmtC = $pdo->prepare(
        "SELECT f.nome AS funcionario, COUNT(c.idCli) AS total_clientes
         FROM cad_cli c
         JOIN cad_fun f ON c.idFun = f.idFun
         GROUP BY c.idFun"
    );
    $stmtC->execute();
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
    <button class="menu-toggle float">&#9776;</button>
    <nav class="main-nav">
      <button class="menu-toggle inmenu">&#9776;</button>
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
        <?php if ($notificacoes): foreach ($notificacoes as $notif): ?>
          <div class="notificacao" data-id-venda="<?= $notif['idVenda'] ?>">
            <a href="<?= htmlspecialchars($notif['link']) ?>"><?= htmlspecialchars($notif['mensagem']) ?>
            <?php if ($notif['pendentes']>1): ?><span class="badge"><?= $notif['pendentes'] ?></span><?php endif; ?></a>
            <small><?= date('d/m/Y H:i',strtotime($notif['data_criacao'])) ?></small>
            <button class="btn-marcar-lida" data-id-venda="<?= $notif['idVenda'] ?>">Marcar tudo como lida</button>
          </div>
        <?php endforeach; else: ?>
          <p>Sem notificações pendentes.</p>
        <?php endif; ?>
        <?php if ($oldNotificacoes): ?>
        <details><summary>Notificações Antigas</summary>
          <?php foreach ($oldNotificacoes as $old): ?>
            <div class="notificacao antiga">
              <a href="<?= htmlspecialchars($old['link']) ?>"><?= htmlspecialchars($old['mensagem']) ?></a>
              <small><?= date('d/m/Y H:i',strtotime($old['data_criacao'])) ?></small>
            </div>
          <?php endforeach; ?>
        </details>
        <?php endif; ?>
      </section>
      <?php endif; ?>
      <div class="stats">
        <div class="stat-card"><h3>Clientes cadastrados hoje</h3><p><?= $clientesHoje ?></p></div>
        <div class="stat-card"><h3>Clientes cadastrados esta semana</h3><p><?= $clientesSemana ?></p></div>
        <div class="stat-card"><h3>Total de Vendas (Mês)</h3><p>R$ <?= number_format($totalVendas,2,',','.') ?></p></div>
        <div class="stat-card"><h3>Total Comissão (Mês)</h3><p>R$ <?= number_format($totalComissao,2,',','.') ?></p></div>
        <div class="stat-card aniversariantes">
          <h3>Aniversariantes do Dia</h3>
          <?php if ($aniversariantes): foreach ($aniversariantes as $nomeAniv): ?>
            <p><?= htmlspecialchars($nomeAniv) ?></p>
          <?php endforeach; else: ?>
            <p>Nenhum aniversariante hoje</p>
          <?php endif; ?>
        </div>
      </div>
      <div class="charts">
        <div class="chart-container"><h4>Comparativo: Clientes vs Vendas</h4><canvas id="perfChart"></canvas></div>
        <div class="chart-container"><h4><?= $isAdmin?'Vendas Totais por Dia':'Minhas Vendas por Dia' ?></h4><canvas id="salesChart"></canvas></div>
        <?php if (!$isAdmin): ?><div class="chart-container"><h4>Meus Clientes por Dia</h4><canvas id="clientsChart"></canvas></div><?php endif; ?>
      </div>
    </main>
  </div>
  <script src="../../_js/_menu/menu.js"></script>
  <script>
    <?php include('../../_js/_dashboard/dashboard.php'); ?>
  </script>
  <script src="../../_js/_dashboard/notificacao.js"></script>
</body>
</html>
