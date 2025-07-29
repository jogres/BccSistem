<?php
// /_consorcioBcc/_php/_planos_comissao/delete.php
require __DIR__ . '/../../config/database.php';
session_start();
if (empty($_SESSION['user_id']) || empty($_GET['id'])) {
    header('Location: /BccSistem/_php/_planos_comissao/list.php');
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare("
    DELETE FROM planos_comissao
     WHERE id_plano_comissao = :id
");
$stmt->execute([':id' => (int) $_GET['id']]);

header('Location: /BccSistem/_php/_planos_comissao/list.php');
exit;
