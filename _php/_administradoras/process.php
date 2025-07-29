<?php
// /_consorcioBcc/_php/_administradoras/process.php
require __DIR__ . '/../../config/database.php';
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

// Coleta
$id   = isset($_POST['id_administradora']) ? (int) $_POST['id_administradora'] : null;
$nome = trim($_POST['nome'] ?? '');
$cnpj = trim($_POST['cnpj'] ?? '');

// Validações
$errors = [];
if (!$nome) {
    $errors[] = 'Nome é obrigatório.';
}
if (!preg_match('/^\d{2}\.\d{3}\.\d{3}\/\d{4}\-\d{2}$/', $cnpj)) {
    $errors[] = 'CNPJ inválido.';
}
if ($errors) {
    $err = urlencode($errors[0]);
    $loc = $id
      ? "/_consorcioBcc/_html/_administradoras/form.php?id={$id}&error={$err}"
      : "/_consorcioBcc/_html/_administradoras/form.php?error={$err}";
    header("Location: {$loc}");
    exit;
}

try {
    $pdo = getPDO();

    // Verifica duplicado (excluindo próprio registro em update)
    if ($id) {
        $dup = $pdo->prepare("
          SELECT COUNT(*) FROM administradoras
           WHERE cnpj = :cnpj
             AND id_administradora <> :id
        ");
        $dup->execute([':cnpj'=>$cnpj, ':id'=>$id]);
    } else {
        $dup = $pdo->prepare("
          SELECT COUNT(*) FROM administradoras
           WHERE cnpj = :cnpj
        ");
        $dup->execute([':cnpj'=>$cnpj]);
    }
    if ($dup->fetchColumn() > 0) {
        throw new Exception('CNPJ já cadastrado.');
    }

    if ($id) {
        // UPDATE
        $stmt = $pdo->prepare("
          UPDATE administradoras
             SET nome = :nome,
                 cnpj = :cnpj
           WHERE id_administradora = :id
        ");
        $stmt->execute([
          ':nome' => $nome,
          ':cnpj' => $cnpj,
          ':id'   => $id
        ]);
    } else {
        // INSERT
        $stmt = $pdo->prepare("
          INSERT INTO administradoras (nome, cnpj)
          VALUES (:nome, :cnpj)
        ");
        $stmt->execute([
          ':nome' => $nome,
          ':cnpj' => $cnpj
        ]);
    }

    header('Location: /_consorcioBcc/_php/_administradoras/list.php');
    exit;

} catch (Exception $e) {
    $err = urlencode($e->getMessage());
    $loc = $id
      ? "/_consorcioBcc/_html/_administradoras/form.php?id={$id}&error={$err}"
      : "/_consorcioBcc/_html/_administradoras/form.php?error={$err}";
    header("Location: {$loc}");
    exit;
}
