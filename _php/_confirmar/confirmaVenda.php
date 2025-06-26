<?php
// confirmaVenda.php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

// Permissões de acesso
if (!in_array($acesso, ['admin', 'user'], true)) {
    exit('Acesso negado.');
}

// Valida parâmetros obrigatórios
if (
    !isset($_POST['idVenda'], $_POST['parcela'], $_POST['idFun']) ||
    !is_numeric($_POST['idVenda']) ||
    !is_numeric($_POST['parcela']) ||
    !is_numeric($_POST['idFun'])
) {
    exit('Parâmetros inválidos.');
}

$idVenda = (int) $_POST['idVenda'];   // PK da venda
$parcela = (int) $_POST['parcela'];   // 1 a 4
$idFun   = (int) $_POST['idFun'];     // ID do administrador que confirma

try {
    $pdo->beginTransaction();

    // 1) Recupera dados da venda (valor, tipo, segmento, etc.)
    $stmt = $pdo->prepare(
        "SELECT valor, dataV, idAdm, confirmada, tipo, segmento
           FROM venda
          WHERE id = ?
          LIMIT 1"
    );
    $stmt->execute([$idVenda]);
    $v = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$v) throw new Exception('Venda não encontrada.');

    $valorVenda      = (float)$v['valor'];
    $tipoVenda       = $v['tipo'];
    $segmentoVenda   = $v['segmento'];
    $idAdm           = (int)$v['idAdm'];
    $vendaConfirmada = (int)$v['confirmada'];

    // 2) Busca todos os funcionários da venda
    $stmtF = $pdo->prepare("SELECT idFun FROM venda_fun WHERE idVenda = ?");
    $stmtF->execute([$idVenda]);
    $funList = $stmtF->fetchAll(PDO::FETCH_COLUMN);
    if (empty($funList)) {
        throw new Exception('Nenhum funcionário associado à venda.');
    }

    // 3) Regras de permissão e confirmação global
    if ($parcela === 1) {
        if ($acesso !== 'admin') {
            throw new Exception('Somente administrador pode confirmar a parcela 1.');
        }
        if (!$vendaConfirmada) {
            $pdo->prepare("UPDATE venda SET confirmada = 1 WHERE id = ?")
                ->execute([$idVenda]);
        }
    } else {
        if (!$vendaConfirmada) {
            throw new Exception('Venda global ainda não confirmada.');
        }
    }

    // 4) Número de funcionários
    $numFun = count($funList);

    // 5) Para cada funcionário, calcula e registra comissão na parcela
    $tabelaParcela = 'parcela' . $parcela;
    $agora = date('Y-m-d H:i:s');

    foreach ($funList as $funIdItem) {
        // 5.1) Verifica se já confirmou nesta parcela
        $stmt = $pdo->prepare(
            "SELECT confirmada 
               FROM {$tabelaParcela} 
              WHERE idVenda = ? AND idFun = ? 
              LIMIT 1"
        );
        $stmt->execute([$idVenda, $funIdItem]);
        $resPar = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resPar && (int)$resPar['confirmada'] === 1) {
            continue; // pula funcionário já confirmado
        }

        // 5.2) Recupera nível do funcionário
        $stmtN = $pdo->prepare(
            "SELECT UPPER(TRIM(nivel)) 
               FROM cad_fun 
              WHERE idFun = ? 
              LIMIT 1"
        );
        $stmtN->execute([$funIdItem]);
        $nivel = $stmtN->fetchColumn() ?: 'APRENDIZ';

        // 5.3) Busca percentual conforme nível, tipo e segmento
        $percentual = 0.0;
        if (in_array($nivel, ['MASTER','CLASSIC','BASIC'], true)) {
            $cols = ['primeira','segunda','terceira','quarta'];
            if (!isset($cols[$parcela - 1])) {
                throw new Exception('Parcela inválida.');
            }
            $col = $cols[$parcela - 1];
            $tabelaNivel = strtolower($nivel);

            $stmtP = $pdo->prepare(
                "SELECT {$col} AS pc
                   FROM {$tabelaNivel}
                  WHERE idAdm = ?
                    AND nome = ?
                    AND segmento = ?
                  LIMIT 1"
            );
            $stmtP->execute([$idAdm, $tipoVenda, $segmentoVenda]);
            $pcRow = $stmtP->fetch(PDO::FETCH_ASSOC);
            $percentual = $pcRow ? (float)$pcRow['pc'] : 0.0;
        }

        // 5.4) Calcula valor-base para comissão
        $base = $valorVenda;
        if (strcasecmp($tipoVenda, 'Meia Gazin') === 0) {
            $base /= 2;
        }
        if ($numFun > 1) {
            $base /= $numFun;
        }
        $valorComissao = ($percentual / 100.0) * $base;

        // 5.5) Insere ou atualiza registro na tabela de parcela
        if (!$resPar) {
            $pdo->prepare(
                "INSERT INTO {$tabelaParcela}
                   (idVenda, idFun, valor, dataConfirmacao, confirmada)
                 VALUES (?, ?, ?, ?, 1)"
            )->execute([$idVenda, $funIdItem, $valorComissao, $agora]);
        } else {
            $pdo->prepare(
                "UPDATE {$tabelaParcela}
                    SET valor = ?, dataConfirmacao = ?, confirmada = 1
                  WHERE idVenda = ? AND idFun = ?"
            )->execute([$valorComissao, $agora, $idVenda, $funIdItem]);
        }

        // 5.6) Atualiza ou insere na tabela comissao mensal
        $mesC = date('Y-m-01');
        $stmtC = $pdo->prepare(
            "SELECT idCom, totalV, totalC
               FROM comissao
              WHERE idFun = ? AND mesC = ?
              LIMIT 1"
        );
        $stmtC->execute([$funIdItem, $mesC]);
        $cRow = $stmtC->fetch(PDO::FETCH_ASSOC);
        if ($cRow) {
            $novoV = $cRow['totalV'] + ($parcela === 1 ? $valorVenda : 0.0);
            $novoC = $cRow['totalC'] + $valorComissao;
            $pdo->prepare(
                "UPDATE comissao
                    SET totalV = ?, totalC = ?
                  WHERE idCom = ?"
            )->execute([$novoV, $novoC, $cRow['idCom']]);
        } else {
            $initV = ($parcela === 1 ? $valorVenda : 0.0);
            $initC = $valorComissao;
            $pdo->prepare(
                "INSERT INTO comissao (totalV, mesC, idFun, totalC)
                 VALUES (?, ?, ?, ?)"
            )->execute([$initV, $mesC, $funIdItem, $initC]);
        }

        // 5.7) Marca notificações desta parcela como lidas
        // agora usando $idFun (administrador) em vez de $funIdItem (vendedor)
        $pdo->prepare(
            "UPDATE notificacoes
                SET lida = 1
              WHERE idVenda = ?
                AND parcela = ?
                AND idFun = ?"
        )->execute([$idVenda, $parcela, $idFun]);
    }

    $pdo->commit();
    header('Location: ../../_html/_detalhes/detalhesVenda.php?idVenda=' . $idVenda);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    exit('Erro na confirmação: ' . htmlspecialchars($e->getMessage()));
}
?>
