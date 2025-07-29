<?php
// /_consorcioBcc/_php/_percentuais_comissao/list.php

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../_php/shared/verify_session.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

// 1) Parâmetros de busca e paginação
$search   = trim($_GET['search']    ?? '');
$page     = max(1, (int)($_GET['page']     ?? 1));
$perPage  = max(1, min(100, (int)($_GET['per_page'] ?? 10)));
$offset   = ($page - 1) * $perPage;
$term     = "%{$search}%";

// 2) Conexão
$pdo = getPDO();

// 3) Total de registros filtrados (inclui busca por nível agora)
$countSql = "
  SELECT COUNT(*)
    FROM percentuais_comissao pc
    JOIN planos_comissao     pl USING(id_plano_comissao)
    JOIN administradoras      a USING(id_administradora)
    LEFT JOIN niveis_comissao nc ON pc.id_nivel = nc.id
   WHERE pl.nome_plano       LIKE :t1
      OR a.nome              LIKE :t2
      OR CAST(pc.numero_parcela AS CHAR) LIKE :t3
      OR nc.nivel            LIKE :t4
";
$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(':t1', $term, PDO::PARAM_STR);
$countStmt->bindValue(':t2', $term, PDO::PARAM_STR);
$countStmt->bindValue(':t3', $term, PDO::PARAM_STR);
$countStmt->bindValue(':t4', $term, PDO::PARAM_STR);      // search by level name
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();

// 4) Busca paginada, trazendo também o nível e seu nome
$listSql = "
  SELECT
    pc.id_percentual,
    a.nome            AS administradora,
    pl.nome_plano     AS plano,
    pc.numero_parcela,
    pc.percentual,
    pc.id_nivel          AS nivel_id,
    nc.nivel          AS nivel_nome
  FROM percentuais_comissao pc
  JOIN planos_comissao     pl USING(id_plano_comissao)
  JOIN administradoras      a USING(id_administradora)
  LEFT JOIN niveis_comissao nc ON pc.id_nivel = nc.id
 WHERE pl.nome_plano       LIKE :t1
    OR a.nome              LIKE :t2
    OR CAST(pc.numero_parcela AS CHAR) LIKE :t3
    OR nc.nivel            LIKE :t4
 ORDER BY a.nome, pl.nome_plano, pc.numero_parcela
 LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($listSql);
$stmt->bindValue(':t1',     $term,     PDO::PARAM_STR);
$stmt->bindValue(':t2',     $term,     PDO::PARAM_STR);
$stmt->bindValue(':t3',     $term,     PDO::PARAM_STR);
$stmt->bindValue(':t4',     $term,     PDO::PARAM_STR);
$stmt->bindValue(':limit',  $perPage,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5) Cálculo de paginação
$totalPages = $perPage ? (int)ceil($total / $perPage) : 1;

// 6) Chama a view
include __DIR__ . '/../../_html/_percentuais_comissao/list.php';
