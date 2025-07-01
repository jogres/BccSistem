<?php
require_once __DIR__ . '/../../config/db.php';

// Captura ano/mês via GET ou usa atual
$currentYear  = (isset($_GET['year']) && preg_match('/^\d{4}$/', $_GET['year']))
               ? (int) $_GET['year']
               : (int) date('Y');
$currentMonth = (isset($_GET['month']) && preg_match('/^(0[1-9]|1[0-2])$/', $_GET['month']))
               ? (int) $_GET['month']
               : (int) date('m');

$isAdmin = ($acesso === 'admin');

// Descobre quantos dias tem o mês selecionado
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
$dias        = range(1, $daysInMonth);

if ($isAdmin) {
    // 1) Soma total de vendas de todos usuários no mês selecionado
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(valor), 0)
          FROM venda
         WHERE YEAR(dataV)  = ?
           AND MONTH(dataV) = ?
    ");
    $stmt->execute([$currentYear, $currentMonth]);
    $totalVendas = (float) $stmt->fetchColumn();

    // 2) Soma total de comissões no mês selecionado
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(totalC), 0)
          FROM comissao
         WHERE YEAR(mesC)  = ?
           AND MONTH(mesC) = ?
    ");
    $stmt->execute([$currentYear, $currentMonth]);
    $totalComissao = (float) $stmt->fetchColumn();

    // 3) Total de clientes cadastrados no mês selecionado
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
          FROM cad_cli
         WHERE YEAR(cadDT)  = ?
           AND MONTH(cadDT) = ?
    ");
    $stmt->execute([$currentYear, $currentMonth]);
    $totalClientes = (int) $stmt->fetchColumn();

    // 4) Vendas diárias agregadas para o mês selecionado
    $stmt = $pdo->prepare("
        SELECT DAY(dataV) AS dia, COUNT(*) AS qtd
          FROM venda
         WHERE YEAR(dataV)  = ?
           AND MONTH(dataV) = ?
         GROUP BY dia
         ORDER BY dia
    ");
    $stmt->execute([$currentYear, $currentMonth]);
    $vendasPorDia = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 5) Clientes diários cadastrados no mês selecionado
    $stmt = $pdo->prepare("
        SELECT DAY(cadDT) AS dia, COUNT(*) AS qtd
          FROM cad_cli
         WHERE YEAR(cadDT)  = ?
           AND MONTH(cadDT) = ?
         GROUP BY dia
         ORDER BY dia
    ");
    $stmt->execute([$currentYear, $currentMonth]);
    $clientesPorDia = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Prepara arrays para o gráfico, agora do dia 1 até o último do mês
    $vendasData   = [];
    $clientesData = [];
    foreach ($dias as $d) {
      $vendasData[]   = isset($vendasPorDia[$d])   ? (int)$vendasPorDia[$d]   : 0;
      $clientesData[] = isset($clientesPorDia[$d]) ? (int)$clientesPorDia[$d] : 0;
    }

} else {
    // USER VIEW: estatísticas pessoais no mês selecionado
    $userId = (int) $_SESSION['user_id'];

    // 1) Total de vendas do usuário
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(v.valor), 0)
          FROM venda v
          JOIN venda_fun vf ON vf.idVenda = v.id
         WHERE vf.idFun = ?
           AND YEAR(v.dataV)  = ?
           AND MONTH(v.dataV) = ?
    ");
    $stmt->execute([$userId, $currentYear, $currentMonth]);
    $totalVendas = (float) $stmt->fetchColumn();

    // 2) Total de comissão pessoal
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(totalC), 0)
          FROM comissao
         WHERE idFun = ?
           AND YEAR(mesC)  = ?
           AND MONTH(mesC) = ?
    ");
    $stmt->execute([$userId, $currentYear, $currentMonth]);
    $totalComissao = (float) $stmt->fetchColumn();

    // 3) Vendas diárias do usuário
    $stmt = $pdo->prepare("
        SELECT DAY(v.dataV) AS dia, COUNT(*) AS qtd
          FROM venda v
          JOIN venda_fun vf ON vf.idVenda = v.id
         WHERE vf.idFun = ?
           AND YEAR(v.dataV)  = ?
           AND MONTH(v.dataV) = ?
         GROUP BY dia
         ORDER BY dia
    ");
    $stmt->execute([$userId, $currentYear, $currentMonth]);
    $vendasPorDia = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 4) Clientes distintos cadastrados pelo usuário no mês
    $stmt = $pdo->prepare("
        SELECT DAY(cadDT) AS dia, COUNT(*) AS qtd
          FROM cad_cli
         WHERE idFun = ?
           AND YEAR(cadDT)  = ?
           AND MONTH(cadDT) = ?
         GROUP BY dia
         ORDER BY dia
    ");
    $stmt->execute([$userId, $currentYear, $currentMonth]);
    $clientesPorDia = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Prepara arrays para o gráfico pessoal
    $vendasData   = [];
    $clientesData = [];
    foreach ($dias as $d) {
      $vendasData[]   = isset($vendasPorDia[$d])   ? (int)$vendasPorDia[$d]   : 0;
      $clientesData[] = isset($clientesPorDia[$d]) ? (int)$clientesPorDia[$d] : 0;
    }
}
?>
