<?php
require __DIR__ . '/../../config/database.php';
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: /BccSistem/_html/_login/index.php');
  exit;
}

$id_adm_nivel      = isset($_POST['id_adm_nivel'])     ? (int)$_POST['id_adm_nivel']     : null;
$id_administradora = (int)($_POST['id_administradora'] ?? 0);
$nivel             = (int)($_POST['nivel']             ?? 0);
$percentual        = trim($_POST['percentual']         ?? '');

$errors = [];
if (!$id_administradora) {
  $errors[] = 'Selecione uma administradora.';
}
if (!$nivel) {
  $errors[] = 'Selecione um nível.';
}
if (!is_numeric($percentual) || $percentual < 0) {
  $errors[] = '% de comissão inválido.';
}

if ($errors) {
  $err = urlencode($errors[0]);
  $loc = $id_adm_nivel
       ? "/BccSistem/_html/_niveis_comissao/form.php?id={$id_adm_nivel}&error={$err}"
       : "/BccSistem/_html/_niveis_comissao/form.php?error={$err}";
  header("Location: {$loc}");
  exit;
}

try {
  $pdo = getPDO();

  // Verifica duplicidade (em insert ou change de par)
  if ($id_adm_nivel) {
    $dup = $pdo->prepare("
      SELECT COUNT(*) FROM administradora_nivel_comissao
       WHERE id_administradora = :adm
         AND nivel = :nivel
         AND id_adm_nivel <> :id
    ");
    $dup->execute([':adm'=>$id_administradora,':nivel'=>$nivel,':id'=>$id_adm_nivel]);
  } else {
    $dup = $pdo->prepare("
      SELECT COUNT(*) FROM administradora_nivel_comissao
       WHERE id_administradora = :adm
         AND nivel = :nivel
    ");
    $dup->execute([':adm'=>$id_administradora,':nivel'=>$nivel]);
  }
  if ($dup->fetchColumn() > 0) {
    throw new Exception('Já existe percentual para este nível e administradora.');
  }

  if ($id_adm_nivel) {
    // UPDATE
    $sql = "
      UPDATE administradora_nivel_comissao
         SET id_administradora = :adm,
             nivel             = :nivel,
             percentual        = :pct
       WHERE id_adm_nivel = :id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':adm'   => $id_administradora,
      ':nivel' => $nivel,
      ':pct'   => $percentual,
      ':id'    => $id_adm_nivel
    ]);
  } else {
    // INSERT
    $sql = "
      INSERT INTO administradora_nivel_comissao
        (id_administradora, nivel, percentual)
      VALUES
        (:adm, :nivel, :pct)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':adm'   => $id_administradora,
      ':nivel' => $nivel,
      ':pct'   => $percentual
    ]);
  }

  header('Location: /BccSistem/_php/_niveis_comissao/list.php');
  exit;

} catch (Exception $e) {
  $msg = urlencode($e->getMessage());
  $loc = $id_adm_nivel
       ? "/BccSistem/_html/_niveis_comissao/form.php?id={$id_adm_nivel}&error={$msg}"
       : "/BccSistem/_html/_niveis_comissao/form.php?error={$msg}";
  header("Location: {$loc}");
  exit;
}
