<?php
// /_consorcioBcc/_php/_planos_comissao/process.php
require __DIR__ . '/../../config/database.php';
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

// Coleta
$id_plano_comissao   = isset($_POST['id_plano_comissao'])
    ? (int) $_POST['id_plano_comissao']
    : null;
$id_administradora   = (int) ($_POST['id_administradora'] ?? 0);
$nome_plano          = trim($_POST['nome_plano'] ?? '');
$num_parcelas_comiss = (int) ($_POST['num_parcelas_comiss'] ?? 0);
$modalidade          = $_POST['modalidade'] ?? '';

// Validações
$errors = [];
if (!$id_administradora) {
    $errors[] = 'Selecione uma administradora.';
}
if ($nome_plano === '') {
    $errors[] = 'Nome do plano é obrigatório.';
}
if ($num_parcelas_comiss < 1) {
    $errors[] = 'Número de parcelas deve ser no mínimo 1.';
}
if (!in_array($modalidade,
     ['Automóvel','Imóvel','Moto','Móveis','Outros'], true)) {
    $errors[] = 'Modalidade inválida.';
}

if ($errors) {
    $err = urlencode($errors[0]);
    $loc = $id_plano_comissao
         ? "/BccSistem/_html/_planos_comissao/form.php?id={$id_plano_comissao}&error={$err}"
         : "/BccSistem/_html/_planos_comissao/form.php?error={$err}";
    header("Location: {$loc}");
    exit;
}

try {
    $pdo = getPDO();

    // Verifica duplicidade
    $sqlDup = $id_plano_comissao
      ? "SELECT COUNT(*) FROM planos_comissao
           WHERE id_administradora = :adm
             AND nome_plano = :nome
             AND id_plano_comissao <> :id"
      : "SELECT COUNT(*) FROM planos_comissao
           WHERE id_administradora = :adm
             AND nome_plano = :nome";
    $dup = $pdo->prepare($sqlDup);
    $params = [
      ':adm'  => $id_administradora,
      ':nome' => $nome_plano
    ];
    if ($id_plano_comissao) {
      $params[':id'] = $id_plano_comissao;
    }
    $dup->execute($params);
    if ($dup->fetchColumn() > 0) {
        throw new Exception('Já existe um plano com este nome para a administradora.');
    }

    if ($id_plano_comissao) {
        // UPDATE
        $sql = "
          UPDATE planos_comissao
             SET id_administradora   = :adm,
                 nome_plano          = :nome,
                 num_parcelas_comiss = :num,
                 modalidade          = :mod
           WHERE id_plano_comissao = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
          ':adm'  => $id_administradora,
          ':nome' => $nome_plano,
          ':num'  => $num_parcelas_comiss,
          ':mod'  => $modalidade,
          ':id'   => $id_plano_comissao
        ]);
    } else {
        // INSERT
        $sql = "
          INSERT INTO planos_comissao
            (id_administradora, nome_plano, num_parcelas_comiss, modalidade)
          VALUES
            (:adm, :nome, :num, :mod)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
          ':adm'  => $id_administradora,
          ':nome' => $nome_plano,
          ':num'  => $num_parcelas_comiss,
          ':mod'  => $modalidade
        ]);
    }

    header('Location: /BccSistem/_php/_planos_comissao/list.php');
    exit;
} catch (Exception $e) {
    $msg = urlencode($e->getMessage());
    $loc = $id_plano_comissao
         ? "/BccSistem/_html/_planos_comissao/form.php?id={$id_plano_comissao}&error={$msg}"
         : "/BccSistem/_html/_planos_comissao/form.php?error={$msg}";
    header("Location: {$loc}");
    exit;
}
