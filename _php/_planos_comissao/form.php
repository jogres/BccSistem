<?php
// /_consorcioBcc/_php/_planos_comissao/form_data.php

require __DIR__ . '/../../config/database.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

// Defaults
$isEdit              = false;
$id_plano_comissao   = null;
$id_administradora   = '';
$nome_plano          = '';
$num_parcelas_comiss = '';
$modalidade          = 'Automóvel'; // valor default

// Load administradoras
$pdo = getPDO();
$administradoras = $pdo
    ->query("SELECT id_administradora, nome FROM administradoras ORDER BY nome")
    ->fetchAll(PDO::FETCH_ASSOC);

// Modalidades disponíveis
$modalidades = ['Automóvel','Imóvel','Moto','Móveis','Outros'];

// Se edição
if (!empty($_GET['id'])) {
    $isEdit = true;
    $id_plano_comissao = (int) $_GET['id'];
    $stmt = $pdo->prepare("
      SELECT id_administradora, nome_plano, num_parcelas_comiss, modalidade
        FROM planos_comissao
       WHERE id_plano_comissao = :id
    ");
    $stmt->execute([':id' => $id_plano_comissao]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        header('Location: /BccSistem/_php/_planos_comissao/list.php');
        exit;
    }
    $id_administradora   = $row['id_administradora'];
    $nome_plano          = $row['nome_plano'];
    $num_parcelas_comiss = $row['num_parcelas_comiss'];
    $modalidade          = $row['modalidade'];
}
