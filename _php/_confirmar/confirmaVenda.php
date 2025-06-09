<?php
// confirmaVenda.php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

// Permissões de acesso
if (!in_array($acesso, ['admin', 'user'])) {
    exit('Acesso negado.');
}

// Valida parâmetros obrigatórios
if (
    !isset($_POST['idVenda']) || !is_numeric($_POST['idVenda']) ||
    !isset($_POST['parcela']) || !is_numeric($_POST['parcela']) ||
    !isset($_POST['idFun'])   || !is_numeric($_POST['idFun'])
) {
    exit('Parâmetros inválidos.');
}

$idVenda  = (int) $_POST['idVenda'];   // PK da venda
$parcela  = (int) $_POST['parcela'];   // 1 a 4
$idFun    = (int) $_POST['idFun'];     // ID do funcionário que confirma

// Apenas admin pode confirmar parcela 1
if ($parcela === 1 && $acesso !== 'admin') {
    exit('Somente administrador pode confirmar a parcela 1.');
}

try {
    $pdo->beginTransaction();

    // 1) Recupera dados da venda
    $stmt = $pdo->prepare(
        "SELECT valor, dataV, idAdm, confirmada, tipo FROM venda WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$idVenda]);
    $vendaDados = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$vendaDados) {
        throw new Exception('Venda não encontrada.');
    }

    $valorVenda      = (float) $vendaDados['valor'];
    $dataVenda       = $vendaDados['dataV'];
    $idAdm           = (int) $vendaDados['idAdm'];
    $vendaConfirmada = (int) $vendaDados['confirmada'];
    $tipoVenda       = $vendaDados['tipo'];

    // 2) Confirmar venda global (parcela 1)
    if ($parcela === 1 && !$vendaConfirmada) {
        $pdo->prepare("UPDATE venda SET confirmada = 1 WHERE id = ?")
            ->execute([$idVenda]);
    }

    // 3) Para parcelas >1, exige venda global confirmada
    if ($parcela > 1 && !$vendaConfirmada) {
        throw new Exception('Venda global ainda não foi confirmada.');
    }

    // 4) Verifica se esta parcela já foi confirmada
    $tabelaParcela = 'parcela' . $parcela;
    $stmt = $pdo->prepare(
        "SELECT confirmada FROM {$tabelaParcela} WHERE idVenda = ? AND idFun = ? LIMIT 1"
    );
    $stmt->execute([$idVenda, $idFun]);
    $resPar = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resPar !== false && (int)$resPar['confirmada'] === 1) {
        throw new Exception("Parcela {$parcela} já confirmada para este funcionário.");
    }

    // 5) Obtém nível do funcionário
    $stmt = $pdo->prepare("SELECT UPPER(TRIM(nivel)) FROM cad_fun WHERE idFun = ?");
    $stmt->execute([$idFun]);
    $nivel = $stmt->fetchColumn() ?: 'APRENDIZ';

    // 6) Busca percentual conforme nível e tipo de venda
    $percentual = 0.0;
    if (in_array($nivel, ['MASTER','CLASSIC','BASIC'])) {
        $coluna = ['primeira','segunda','terceira','quarta'][$parcela-1] ?? null;
        if (!$coluna) throw new Exception('Parcela inválida.');
        $tabelaNivel = strtolower($nivel);
        $stmt2 = $pdo->prepare(
            "SELECT {$coluna} AS pc FROM {$tabelaNivel} WHERE idAdm = ? AND nome = ? LIMIT 1"
        );
        $stmt2->execute([$idAdm, $tipoVenda]);
        $pc = $stmt2->fetch(PDO::FETCH_ASSOC);
        $percentual = $pc ? (float)$pc['pc'] : 0.0;
    }

    // 7) Calcula valor da comissão da parcela
    $valorComissao = ($percentual/100.0) * $valorVenda;

    // 8) Insere ou atualiza na tabela da parcela
    $agora = date('Y-m-d H:i:s');
    if ($resPar === false) {
        $stmtIns = $pdo->prepare(
            "INSERT INTO {$tabelaParcela} (idVenda, idFun, valor, dataConfirmacao, confirmada) VALUES (?, ?, ?, ?, 1)"
        );
        $stmtIns->execute([$idVenda, $idFun, $valorComissao, $agora]);
    } else {
        $stmtUpd = $pdo->prepare(
            "UPDATE {$tabelaParcela} SET valor = ?, dataConfirmacao = ?, confirmada = 1 WHERE idVenda = ? AND idFun = ?"
        );
        $stmtUpd->execute([$valorComissao, $agora, $idVenda, $idFun]);
    }

    // 9) Atualiza ou insere na tabela `comissao`
    $mesC = date('Y-m-01');
    $stmt = $pdo->prepare("SELECT idCom, totalV, totalC FROM comissao WHERE idFun = ? AND mesC = ? LIMIT 1");
    $stmt->execute([$idFun, $mesC]);
    $cRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cRow) {
        $novoV = ($parcela===1 ? $cRow['totalV'] + $valorVenda : $cRow['totalV']);
        $novoC = $cRow['totalC'] + $valorComissao;
        $pdo->prepare("UPDATE comissao SET totalV = ?, totalC = ? WHERE idCom = ?")
            ->execute([$novoV, $novoC, $cRow['idCom']]);
    } else {
        $initV = ($parcela===1 ? $valorVenda : 0.0);
        $initC = $valorComissao;
        $pdo->prepare("INSERT INTO comissao (totalV, mesC, idFun, totalC) VALUES (?, ?, ?, ?)")
            ->execute([$initV, $mesC, $idFun, $initC]);
    }

    // 10) Marca notificação desta parcela como lida
    $stmtNotif = $pdo->prepare(
        "UPDATE notificacoes SET lida = 1 WHERE idVenda = ? AND parcela = ?"
    );
    $stmtNotif->execute([$idVenda, $parcela]);

    $pdo->commit();

    // Redireciona de volta aos detalhes da venda
    header('Location: ../../_html/_detalhes/detalhesVenda.php?idVenda=' . $idVenda);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    exit('Erro na confirmação: ' . htmlspecialchars($e->getMessage()));
}
?>
