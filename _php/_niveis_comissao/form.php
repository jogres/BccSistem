<?php
require __DIR__ . '/../../config/database.php';

if (empty($_SESSION['user_id'])) {
  header('Location: /_consorcioBcc/_html/_login/index.php');
  exit;
}

// Dados comuns
$isEdit           = false;
$id_adm_nivel     = null;
$id_administradora = '';
$nivel            = '';
$percentual       = '';

// Listas auxiliares
$pdo = getPDO();
$administradoras = $pdo->query("SELECT id_administradora, nome FROM administradoras ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$niveis           = $pdo->query("SELECT nivel, vendas_min, vendas_max FROM niveis_comissao ORDER BY nivel")->fetchAll(PDO::FETCH_ASSOC);

// Se veio ?id na URL, carregamos para edição
if (!empty($_GET['id'])) {
  $isEdit       = true;
  $id_adm_nivel = (int)$_GET['id'];
  $stmt = $pdo->prepare("
    SELECT id_administradora, nivel, percentual
      FROM administradora_nivel_comissao
     WHERE id_adm_nivel = :id
  ");
  $stmt->execute([':id'=>$id_adm_nivel]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    header('Location: /_consorcioBcc/_php/_niveis_comissao/list.php');
    exit;
  }
  $id_administradora = $row['id_administradora'];
  $nivel             = $row['nivel'];
  $percentual        = $row['percentual'];
}
