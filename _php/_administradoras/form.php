<?php
// /_consorcioBcc/_php/_administradoras/form.php
require __DIR__ . '/../../config/database.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

// Defaults
$isEdit   = false;
$id        = null;
$nome      = '';
$cnpj      = '';

// Se veio ?id, carrega admin
if (!empty($_GET['id'])) {
    $isEdit = true;
    $id     = (int) $_GET['id'];
    $pdo    = getPDO();
    $stmt   = $pdo->prepare("SELECT * FROM administradoras WHERE id_administradora = :id");
    $stmt->execute([':id' => $id]);
    $a = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$a) {
        header('Location: /_consorcioBcc/_php/_administradoras/list.php');
        exit;
    }
    $nome = $a['nome'];
    $cnpj = $a['cnpj'];
}
?>