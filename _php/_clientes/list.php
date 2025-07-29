<?php
// /_consorcioBcc/_php/_clientes/list.php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../_php/shared/verify_session.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

// Parâmetros de busca/paginação
$search   = trim($_GET['search']    ?? '');
$page     = isset($_GET['page'])     ? max(1, (int) $_GET['page'])      : 1;
$perPage  = isset($_GET['per_page']) ? max(1, min(100, (int) $_GET['per_page'])) : 10;
$offset   = ($page - 1) * $perPage;
$term     = "%{$search}%";

$pdo = getPDO();

// Total de clientes filtrados
$countSql = "
  SELECT COUNT(*) 
    FROM clientes
   WHERE nome LIKE :t1 OR telefone LIKE :t2 OR motivo LIKE :t3
";
$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(':t1', $term, PDO::PARAM_STR);
$countStmt->bindValue(':t2', $term, PDO::PARAM_STR);
$countStmt->bindValue(':t3', $term, PDO::PARAM_STR);
$countStmt->execute();
$total = (int) $countStmt->fetchColumn();

// Busca paginada
$listSql = "
  SELECT id_cliente, nome, telefone, motivo
    FROM clientes
   WHERE nome LIKE :t1 OR telefone LIKE :t2 OR motivo LIKE :t3
   ORDER BY nome
   LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($listSql);
$stmt->bindValue(':t1',     $term,    PDO::PARAM_STR);
$stmt->bindValue(':t2',     $term,    PDO::PARAM_STR);
$stmt->bindValue(':t3',     $term,    PDO::PARAM_STR);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = $perPage ? (int) ceil($total / $perPage) : 1;
// Chama a view
include __DIR__ . '/../../_html/_clientes/list.php';
?>