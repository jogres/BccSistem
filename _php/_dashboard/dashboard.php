<?php
  require_once __DIR__ . '/../../config/db.php';

  $isAdmin = ($acesso === 'admin');
  $currentYear  = date('Y');
  $currentMonth = date('m');

  if ($isAdmin) {
    // ADMIN VIEW: totais gerais
    // 1) Soma total de vendas de todos usuários no mês atual
    $stmt = $pdo->prepare("SELECT IFNULL(SUM(valor),0) FROM venda WHERE YEAR(dataV)=? AND MONTH(dataV)=?");
    $stmt->execute([$currentYear, $currentMonth]);
    $totalVendas = $stmt->fetchColumn();

    // 2) Soma total de comissões no mês atual
    $stmt = $pdo->prepare("SELECT IFNULL(SUM(totalC),0) FROM comissao WHERE YEAR(mesC)=? AND MONTH(mesC)=?");
    $stmt->execute([$currentYear, $currentMonth]);
    $totalComissao = $stmt->fetchColumn();

    // 3) Total de clientes cadastrados (sem filtro de data, pois não há campo de criação)
    $stmt = $pdo->query("SELECT COUNT(*) FROM cad_cli");
    $totalClientes = $stmt->fetchColumn();

    // 4) Vendas diárias agregadas para todo o sistema
    $stmt = $pdo->prepare("SELECT DAY(dataV) AS dia, COUNT(*) AS qtd FROM venda WHERE YEAR(dataV)=? AND MONTH(dataV)=? GROUP BY dia ORDER BY dia");
    $stmt->execute([$currentYear, $currentMonth]);
    $vendasPorDia = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $dias = range(1, (int)date('d'));
    $vendasData = [];
    foreach ($dias as $d) {
      $vendasData[] = isset($vendasPorDia[$d]) ? (int)$vendasPorDia[$d] : 0;
    }

  } else {
    // USER VIEW: estatísticas pessoais
    $userId = (int)$_SESSION['user_id'];

    // 1) Total de vendas do usuário no mês
    $stmt = $pdo->prepare("SELECT IFNULL(SUM(v.valor),0) FROM venda v JOIN venda_fun vf ON vf.idVenda=v.id WHERE vf.idFun=? AND YEAR(v.dataV)=? AND MONTH(v.dataV)=?");
    $stmt->execute([$userId, $currentYear, $currentMonth]);
    $totalVendas = $stmt->fetchColumn();

    // 2) Total de comissão pessoal no mês
    $stmt = $pdo->prepare("SELECT IFNULL(SUM(totalC),0) FROM comissao WHERE idFun=? AND YEAR(mesC)=? AND MONTH(mesC)=?");
    $stmt->execute([$userId, $currentYear, $currentMonth]);
    $totalComissao = $stmt->fetchColumn();

    // 3) Vendas diárias do usuário
    $stmt = $pdo->prepare("SELECT DAY(v.dataV) AS dia, COUNT(*) AS qtd FROM venda v JOIN venda_fun vf ON vf.idVenda=v.id WHERE vf.idFun=? AND YEAR(v.dataV)=? AND MONTH(v.dataV)=? GROUP BY dia ORDER BY dia");
    $stmt->execute([$userId, $currentYear, $currentMonth]);
    $vendasPorDia = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 4) Clientes distintos por dia do usuário
    $stmt = $pdo->prepare("SELECT DAY(v.dataV) AS dia, COUNT(DISTINCT vc.idCli) AS qtd FROM venda v JOIN venda_fun vf ON vf.idVenda=v.id JOIN venda_cli vc ON vc.idVenda=v.id WHERE vf.idFun=? AND YEAR(v.dataV)=? AND MONTH(v.dataV)=? GROUP BY dia ORDER BY dia");
    $stmt->execute([$userId, $currentYear, $currentMonth]);
    $clientesPorDia = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $dias = range(1, (int)date('d'));
    $vendasData = [];
    $clientesData = [];
    foreach ($dias as $d) {
      $vendasData[]   = isset($vendasPorDia[$d])   ? (int)$vendasPorDia[$d]   : 0;
      $clientesData[] = isset($clientesPorDia[$d]) ? (int)$clientesPorDia[$d] : 0;
    }
  }
?>