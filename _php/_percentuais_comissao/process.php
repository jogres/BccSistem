<?php
// /_consorcioBcc/_php/_percentuais_comissao/process.php
require __DIR__ . '/../../config/database.php';
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

$id_plano_comissao   = (int) ($_POST['id_plano_comissao']   ?? 0);
$parcelas            = $_POST['numero_parcela']    ?? [];
$percentuaisArr      = $_POST['percentual']        ?? [];
$id_nivel_comissao   = (int) ($_POST['id_nivel_comissao'] ?? 0);

if (!$id_plano_comissao || !$id_nivel_comissao || !count($parcelas)) {
    header('Location: /_consorcioBcc/_html/_percentuais_comissao/form.php?'
         . 'plano=' . $id_plano_comissao
         . '&error=' . urlencode('Selecione plano, nível e preencha ao menos uma parcela.'));
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    // Prepara statements de busca, insert e update
    $sel = $pdo->prepare("
      SELECT id_percentual
        FROM percentuais_comissao
       WHERE id_plano_comissao   = :pl
         AND id_nivel           = :niv
         AND numero_parcela      = :num
      LIMIT 1
    ");

    $ins = $pdo->prepare("
      INSERT INTO percentuais_comissao
        (id_plano_comissao, id_nivel, numero_parcela, percentual)
      VALUES
        (:pl, :niv, :num, :pct)
    ");

    $upd = $pdo->prepare("
      UPDATE percentuais_comissao
         SET percentual = :pct
       WHERE id_percentual   = :idp
    ");

    foreach ($parcelas as $idx => $num) {
        $pct = $percentuaisArr[$idx];
        if (!is_numeric($pct) || $pct < 0) {
            throw new Exception("Percentual inválido na parcela {$num}.");
        }

        // verifica existência
        $sel->execute([
            ':pl'  => $id_plano_comissao,
            ':niv' => $id_nivel_comissao,
            ':num' => (int)$num
        ]);

        if ($row = $sel->fetch(PDO::FETCH_ASSOC)) {
            // já existe → update
            $upd->execute([
                ':pct' => (float)$pct,
                ':idp' => $row['id_percentual'],
            ]);
        } else {
            // não existe → insert
            $ins->execute([
                ':pl'  => $id_plano_comissao,
                ':niv' => $id_nivel_comissao,
                ':num' => (int)$num,
                ':pct' => (float)$pct,
            ]);
        }
    }

    $pdo->commit();
    header('Location: /_consorcioBcc/_php/_percentuais_comissao/list.php');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: /_consorcioBcc/_html/_percentuais_comissao/form.php?'
         . 'plano='  . $id_plano_comissao
         . '&error=' . urlencode($e->getMessage()));
    exit;
}
