<?php
// confirmaVenda.php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

if (!in_array($acesso, ['admin', 'user'])) {
    exit('Acesso negado.');
}

if (
    !isset($_POST['idVenda']) || !is_numeric($_POST['idVenda']) ||
    !isset($_POST['parcela']) || !is_numeric($_POST['parcela']) ||
    !isset($_POST['idFun'])   || !is_numeric($_POST['idFun'])
) {
    exit('Parâmetros inválidos.');
}

$id      = (int) $_POST['idVenda'];  // PK da venda
$parcela = (int) $_POST['parcela'];  // 1 a 4
$idFun   = (int) $_POST['idFun'];

if ($parcela === 1 && $acesso !== 'admin') {
    exit('Somente administrador pode confirmar a parcela 1.');
}


try {
    $pdo->beginTransaction();

    // 1) Busca venda pelo campo `id` (PK)
    $stmt = $pdo->prepare("
        SELECT valor, dataV, idAdm, confirmada, tipo 
        FROM venda 
        WHERE id = ? 
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $vendaDados = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$vendaDados) {
        throw new Exception('Venda não encontrada.');
    }

    $valorVenda      = (float) $vendaDados['valor'];
    $dataVenda       = $vendaDados['dataV'];
    $idAdm           = (int) $vendaDados['idAdm'];
    $vendaConfirmada = (int) $vendaDados['confirmada'];
    $tipoVenda       = $vendaDados['tipo'];

    // 2) Se for parcela 1 e ainda não estiver confirmada, atualiza
    if ($parcela === 1 && !$vendaConfirmada) {
        $stmtUpd = $pdo->prepare("UPDATE venda SET confirmada = 1 WHERE id = ?");
        $stmtUpd->execute([$id]);
    }
    // 3) Se for parcela >1, exige que venda global já esteja confirmada
    if ($parcela > 1 && !$vendaConfirmada) {
        throw new Exception('Venda global ainda não confirmada.');
    }

    // 4) Verifica se esta parcela já foi confirmada em qualquer estado
    $tabelaParcela = 'parcela' . $parcela;
    $stmt = $pdo->prepare("
        SELECT confirmada 
        FROM $tabelaParcela 
        WHERE idVenda = ? AND idFun = ? 
        LIMIT 1
    ");
    $stmt->execute([$id, $idFun]);
    $resPar = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resPar !== false && (int)$resPar['confirmada'] === 1) {
        throw new Exception("Parcela $parcela já confirmada para este funcionário.");
    }

    // 5) Busca nível do funcionário
    $stmt = $pdo->prepare("SELECT UPPER(TRIM(nivel)) AS nivel FROM cad_fun WHERE idFun = ?");
    $stmt->execute([$idFun]);
    $nivel = $stmt->fetchColumn();
    // Se não houver nível, assumir “APRENDIZ”
    if (!$nivel) {
        $nivel = 'APRENDIZ';
    } else {
        $nivel = strtoupper($nivel);
    }

    // 6) Obtém percentual na tabela de nível (master/classic/basic)
    $percentual = 0.0;
    if (in_array($nivel, ['MASTER', 'CLASSIC', 'BASIC'])) {
        $tabelaNivel = strtolower($nivel);
        switch ($parcela) {
            case 1:
                $coluna = 'primeira';
                break;
            case 2:
                $coluna = 'segunda';
                break;
            case 3:
                $coluna = 'terceira';
                break;
            case 4:
                $coluna = 'quarta';
                break;
            default:
                throw new Exception('Parcela inválida.');
        }
        $stmt2 = $pdo->prepare("
            SELECT $coluna AS pc 
            FROM $tabelaNivel 
            WHERE idAdm = ? AND nome = ? 
            LIMIT 1
        ");
        $stmt2->execute([$idAdm, $tipoVenda]);
        $pc = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($pc) {
            $percentual = (float) $pc['pc'];
        }
    }
    // Se for “APRENDIZ”, $percentual permanece 0.0

    // 7) Calcula o valor da comissão desta parcela
    $valorComissaoParcela = ($percentual / 100.0) * $valorVenda;

    // 8) Insere ou atualiza registro na tabela de parcela correspondente
    if ($resPar === false) {
        // Ainda não existe → inserir
        $stmtIns = $pdo->prepare("
            INSERT INTO $tabelaParcela
            (idVenda, idFun, valor, dataConfirmacao, confirmada)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmtIns->execute([
            $id,
            $idFun,
            $valorComissaoParcela,
            date('Y-m-d H:i:s')
        ]);
    } else {
        // Já existe linha (confirmada = 0) → atualizar
        $stmtUpd2 = $pdo->prepare("
            UPDATE $tabelaParcela
            SET valor = ?, dataConfirmacao = ?, confirmada = 1
            WHERE idVenda = ? AND idFun = ?
        ");
        $stmtUpd2->execute([
            $valorComissaoParcela,
            date('Y-m-d H:i:s'),
            $id,
            $idFun
        ]);
    }

    // 9) Atualiza ou insere na tabela comissao (apenas um registro por funcionário e mês)
    $mesAtual = date('Y-m-01');
    $stmt = $pdo->prepare("
        SELECT idCom, totalV, totalC 
        FROM comissao 
        WHERE idFun = ? AND mesC = ? 
        LIMIT 1
    ");
    $stmt->execute([$idFun, $mesAtual]);
    $comRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($comRow) {
        $idComExistente = (int) $comRow['idCom'];
        $antigoV = (float) $comRow['totalV'];
        $antigoC = (float) $comRow['totalC'];

        $novoV = ($parcela === 1) ? $antigoV + $valorVenda : $antigoV;
        $novoC = $antigoC + $valorComissaoParcela;

        $stmtUpd3 = $pdo->prepare("
            UPDATE comissao 
            SET totalV = ?, totalC = ? 
            WHERE idCom = ?
        ");
        $stmtUpd3->execute([$novoV, $novoC, $idComExistente]);
        echo "Comissão atualizada: Venda Total = R$ " . number_format($novoV, 2, ',', '.') . ", Comissão Total = R$ " . number_format($novoC, 2, ',', '.');
        echo "<br>Parcela $parcela confirmada com sucesso.";
        echo "<br>Funcionário: $idFun, Nível: $nivel, Percentual: $percentual%";
        echo "<br>Valor da Comissão desta Parcela: R$ " . number_format($valorComissaoParcela, 2, ',', '.');
        echo "<br>" . $coluna;
        echo "<br>". $tabelaNivel;
        echo "<br>". $idAdm;
        echo "<br>". $tipoVenda;
    } else {
        $initV = ($parcela === 1) ? $valorVenda : 0.0;
        $initC = $valorComissaoParcela;

        $stmtIns2 = $pdo->prepare("
            INSERT INTO comissao (totalV, mesC, idFun, totalC) 
            VALUES (?, ?, ?, ?)
        ");
        $stmtIns2->execute([$initV, $mesAtual, $idFun, $initC]);
    }

    $pdo->commit();
    header('Location: ../../_html/_detalhes/detalhesVenda.php?idVenda=' . $id);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    exit('Erro na confirmação: ' . $e->getMessage());
}
?>
