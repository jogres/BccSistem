<?php
// File: /_php/_dashboard/dashboard_data.php

require __DIR__ . '/../../config/database.php';

$pdo       = getPDO();
$userId    = $_SESSION['user_id']    ?? 0;
$userRole  = $_SESSION['user_papel'] ?? 'vendedor';
$isManager = in_array($userRole, ['admin','gerente'], true);

// Helper para adicionar cláusula AND somente quando já existe WHERE
function restrictByUser(string &$sql, array &$params, string $field, int $userId) {
    $sql .= " AND {$field} = :uid";
    $params[':uid'] = $userId;
}

// Captura filtro de período (ou usa mês/ano atuais)
$filterMonth = (int)($_GET['month'] ?? date('m'));
$filterYear  = (int)($_GET['year']  ?? date('Y'));
$monthStart  = sprintf('%04d-%02d-01', $filterYear, $filterMonth);
$weekStart   = date('Y-m-d', strtotime('monday this week', strtotime($monthStart)));

// 1) Clientes (vendas) no mês
$sql     = "SELECT COUNT(*) FROM vendas WHERE data_venda >= :dt";
$params  = [':dt' => $monthStart];
if (! $isManager) {
    restrictByUser($sql, $params, 'id_vendedor', $userId);
}
$stmt                = $pdo->prepare($sql);
$stmt->execute($params);
$countClientsMonth   = (int)$stmt->fetchColumn();

// 2) Clientes (vendas) na semana
$sql     = "SELECT COUNT(*) FROM vendas WHERE data_venda >= :dt";
$params  = [':dt' => $weekStart];
if (! $isManager) {
    restrictByUser($sql, $params, 'id_vendedor', $userId);
}
$stmt                = $pdo->prepare($sql);
$stmt->execute($params);
$countClientsWeek    = (int)$stmt->fetchColumn();

// 3) Total de vendas no mês (mesmo de clientes)
$countSalesMonth     = $countClientsMonth;

// 4) Comissão paga no mês
$sql = "
  SELECT COALESCE(SUM(p.valor),0)
    FROM parcelas_comissao p
    JOIN vendas v ON p.id_venda = v.id_venda
   WHERE p.status = 'paga'
     AND MONTH(p.data_realizacao_cliente) = :m
     AND YEAR(p.data_realizacao_cliente)  = :y
";
$params = [':m'=>$filterMonth, ':y'=>$filterYear];
if (! $isManager) {
    restrictByUser($sql, $params, 'v.id_vendedor', $userId);
}
$stmt                  = $pdo->prepare($sql);
$stmt->execute($params);
$sumCommissionMonth    = (float)$stmt->fetchColumn();

// 5) Clientes por vendedor no mês (gráfico)
$sql = "
  SELECT f.nome, COUNT(v.id_cliente) AS qtd
    FROM vendas v
    JOIN funcionarios f ON v.id_vendedor = f.id_funcionario
   WHERE MONTH(v.data_venda) = :m
     AND YEAR(v.data_venda)  = :y
";
$params = [':m'=>$filterMonth, ':y'=>$filterYear];
if (! $isManager) {
    restrictByUser($sql, $params, 'v.id_vendedor', $userId);
}
$sql   .= " GROUP BY f.id_funcionario";
$stmt   = $pdo->prepare($sql);
$stmt->execute($params);
$clientsByUser = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6) Vendas semanais por vendedor
$sql = "
  SELECT f.nome, COUNT(v.id_venda) AS vendas
    FROM vendas v
    JOIN funcionarios f ON v.id_vendedor = f.id_funcionario
   WHERE v.data_venda >= :ws
     AND MONTH(v.data_venda) = :m
     AND YEAR(v.data_venda)  = :y
";
$params = [':ws'=>$weekStart, ':m'=>$filterMonth, ':y'=>$filterYear];
if (! $isManager) {
    restrictByUser($sql, $params, 'v.id_vendedor', $userId);
}
$sql   .= " GROUP BY f.id_funcionario";
$stmt   = $pdo->prepare($sql);
$stmt->execute($params);
$salesWeekByUser = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7) Parcelas pendentes do mês
$sql = "
  SELECT p.numero_parcela, v.numero_contrato, p.data_prevista, p.id_venda
    FROM parcelas_comissao p
    JOIN vendas v ON p.id_venda = v.id_venda
   WHERE p.status = 'pendente'
     AND MONTH(p.data_prevista) = :m
     AND YEAR(p.data_prevista)  = :y
   ORDER BY p.numero_parcela
";
$params = [':m'=>$filterMonth, ':y'=>$filterYear];
if (! $isManager) {
    restrictByUser($sql, $params, 'v.id_vendedor', $userId);
}
$stmt            = $pdo->prepare($sql);
$stmt->execute($params);
$pendingParcels  = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 8) Vendas detalhadas para tabela
$sql = "
  SELECT
    v.id_venda,
    v.numero_contrato,
    c.nome AS cliente,
    f.nome AS vendedor,
    v.valor_total,
    v.data_venda
  FROM vendas v
  JOIN clientes c     ON v.id_cliente  = c.id_cliente
  JOIN funcionarios f ON v.id_vendedor = f.id_funcionario
  WHERE MONTH(v.data_venda) = :m
    AND YEAR(v.data_venda)  = :y
";
$params = [':m'=>$filterMonth, ':y'=>$filterYear];
if (! $isManager) {
    restrictByUser($sql, $params, 'v.id_vendedor', $userId);
}
$sql .= " ORDER BY v.data_venda DESC";
$stmt           = $pdo->prepare($sql);
$stmt->execute($params);
$filteredSales  = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retorna todas as métricas
return compact(
  'filterMonth',
  'filterYear',
  'countClientsMonth',
  'countClientsWeek',
  'countSalesMonth',
  'sumCommissionMonth',
  'clientsByUser',
  'salesWeekByUser',
  'pendingParcels',
  'filteredSales'
);
