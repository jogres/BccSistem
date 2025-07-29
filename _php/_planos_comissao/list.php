<?php
// /_consorcioBcc/_php/_planos_comissao/list.php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../_php/shared/verify_session.php';
if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

// Parâmetros
$search    = trim($_GET['search']    ?? '');
$page      = isset($_GET['page'])     ? max(1, (int) $_GET['page'])      : 1;
$perPage   = isset($_GET['per_page']) ? max(1, min(100, (int) $_GET['per_page'])) : 10;
$offset    = ($page - 1) * $perPage;
$term1     = "%{$search}%";
$term2     = "%{$search}%";

$pdo = getPDO();

// Conta total
$countSql = "
  SELECT COUNT(*) 
    FROM planos_comissao pc
    JOIN administradoras a USING(id_administradora)
   WHERE a.nome       LIKE :term1
      OR pc.nome_plano LIKE :term2
";
$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(':term1', $term1, PDO::PARAM_STR);
$countStmt->bindValue(':term2', $term2, PDO::PARAM_STR);
$countStmt->execute();
$total = (int) $countStmt->fetchColumn();

// Busca paginada
$listSql = "
  SELECT
    pc.id_plano_comissao,
    a.nome             AS administradora,
    pc.nome_plano,
    pc.num_parcelas_comiss,
    pc.modalidade
  FROM planos_comissao pc
  JOIN administradoras a USING(id_administradora)
 WHERE a.nome       LIKE :term1
    OR pc.nome_plano LIKE :term2
 ORDER BY a.nome, pc.nome_plano
 LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($listSql);
$stmt->bindValue(':term1',  $term1,    PDO::PARAM_STR);
$stmt->bindValue(':term2',  $term2,    PDO::PARAM_STR);
$stmt->bindValue(':limit',  $perPage,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = $perPage ? (int) ceil($total / $perPage) : 1;
// Chama view
include __DIR__ . '/../../_html/_planos_comissao/list.php';
?>