<?php
// /_consorcioBcc/_php/_clientes/delete.php
require __DIR__ . '/../../config/database.php';
session_start();
if (empty($_SESSION['user_id']) || empty($_GET['id'])) {
    header('Location: /BccSistem/_php/_clientes/list.php');
    exit;
}

$pdo  = getPDO();
$stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = :id");
$stmt->execute([':id' => (int) $_GET['id']]);

header('Location: /BccSistem/_php/_clientes/list.php');
exit;
