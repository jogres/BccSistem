<?php
// /_consorcioBcc/_php/_niveis_comissao/list.php

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../_php/shared/verify_session.php';
if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

$search   = trim($_GET['search']   ?? '');
$page     = max(1, (int)($_GET['page']     ?? 1));
$perPage  = max(1, min(100, (int)($_GET['per_page'] ?? 10)));
$offset   = ($page - 1) * $perPage;
$searchParam = "%{$search}%";

$pdo = getPDO();

// 1) Contagem total com placeholders distintos
$countSql = "
  SELECT COUNT(*) 
    FROM administradora_nivel_comissao anc
    JOIN administradoras a    USING(id_administradora)
   WHERE a.nome LIKE :search1
      OR CAST(anc.nivel AS CHAR) LIKE :search2
";
$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(':search1', $searchParam, PDO::PARAM_STR);
$countStmt->bindValue(':search2', $searchParam, PDO::PARAM_STR);
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();

// 2) Busca paginada idem
$listSql = "
  SELECT anc.id_adm_nivel,
         a.nome           AS administradora,
         nc.id,
         nc.nivel,
         nc.vendas_min,
         nc.vendas_max,
         anc.percentual
    FROM administradora_nivel_comissao anc
    JOIN administradoras a  USING(id_administradora)
    JOIN niveis_comissao nc ON nc.id = anc.nivel
   WHERE a.nome LIKE :search1
      OR CAST(anc.nivel AS CHAR) LIKE :search2
   ORDER BY a.nome, nc.nivel
   LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($listSql);
$stmt->bindValue(':search1', $searchParam, PDO::PARAM_STR);
$stmt->bindValue(':search2', $searchParam, PDO::PARAM_STR);
$stmt->bindValue(':limit',   $perPage,     PDO::PARAM_INT);
$stmt->bindValue(':offset',  $offset,      PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = $perPage ? (int)ceil($total / $perPage) : 1;
include __DIR__ . '/../../_html/_niveis_comissao/list.php';
?>