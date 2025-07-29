<?php
// /_php/_vendas/list.php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../_php/shared/verify_session.php';
if (empty($_SESSION['user_id'])) {
  header('Location: /_consorcioBcc/_html/_login/index.php');
  exit;
}

$search  = trim($_GET['search']??'');
$page    = max(1,(int)($_GET['page']??1));
$perPage = max(1,min(100,(int)($_GET['per_page']??10)));
$offset  = ($page-1)*$perPage;
$term    = "%{$search}%";

$pdo = getPDO();

// total
$count = $pdo->prepare("
  SELECT COUNT(*) FROM vendas v
  JOIN clientes c USING(id_cliente)
  WHERE c.nome LIKE :t1 OR v.numero_contrato LIKE :t2
");
$count->execute([':t1'=>$term,':t2'=>$term]);
$total = (int)$count->fetchColumn();

// listagem
$stmt = $pdo->prepare("
  SELECT
    v.id_venda,
    v.numero_contrato,
    c.nome AS cliente,
    fv.nome AS vendedor,
    fr.nome AS virador,
    a.nome AS administradora,
    pl.nome_plano AS plano,
    v.modalidade,
    v.valor_total,
    v.data_venda,
    v.status
  FROM vendas v
  JOIN clientes c          USING(id_cliente)
  JOIN funcionarios fv     ON v.id_vendedor  = fv.id_funcionario
  JOIN funcionarios fr     ON v.id_virador   = fr.id_funcionario
  JOIN administradoras a   USING(id_administradora)
  JOIN planos_comissao pl  USING(id_plano_comissao)
  WHERE c.nome LIKE :t1 OR v.numero_contrato LIKE :t2
  ORDER BY v.data_venda DESC
  LIMIT :lim OFFSET :off
");
$stmt->bindValue(':t1',$term,PDO::PARAM_STR);
$stmt->bindValue(':t2',$term,PDO::PARAM_STR);
$stmt->bindValue(':lim',$perPage,PDO::PARAM_INT);
$stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = $perPage>0?ceil($total/$perPage):1;

// 5) Chama a view
include __DIR__ . '/../../_html/_vendas/list.php';
?>