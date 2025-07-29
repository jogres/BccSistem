<?php
// /_consorcioBcc/_php/_administradoras/list.php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../_php/shared/verify_session.php';
if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

// Parâmetros
$search  = trim($_GET['search']   ?? '');
$page    = max(1, (int)($_GET['page']     ?? 1));
$perPage = max(1, min(100, (int)($_GET['per_page'] ?? 10)));
$offset  = ($page - 1) * $perPage;

$pdo = getPDO();

// Total filtrado
$count = $pdo->prepare("
  SELECT COUNT(*) FROM administradoras
   WHERE nome LIKE :s
");
$count->execute([':s'=>"%{$search}%"]);
$total = (int)$count->fetchColumn();

// Dados da página
$stmt = $pdo->prepare("
  SELECT id_administradora, nome, cnpj
    FROM administradoras
   WHERE nome LIKE :s
   ORDER BY nome
   LIMIT :l OFFSET :o
");
$stmt->bindValue(':s', "%{$search}%", PDO::PARAM_STR);
$stmt->bindValue(':l', $perPage,           PDO::PARAM_INT);
$stmt->bindValue(':o', $offset,            PDO::PARAM_INT);
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = $perPage ? (int)ceil($total / $perPage) : 1;
include __DIR__ . '/../../_html/_administradoras/list.php';
?>