<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

if (!in_array($acesso, ['admin','user','vendedor'])) {
    exit('Acesso negado.');
}

$idCli    = isset($_POST['idCli']) ? (int) $_POST['idCli'] : null;
$nome     = trim($_POST['nome']     ?? '');
$cpf      = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$endereco = $endereco   = trim($_POST['rua'].', '.$_POST['numero'].', '.$_POST['bairro'].', '.$_POST['cidade'].', '.$_POST['estado'].', '.$_POST['cep'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$tipo     = $_SESSION['venda'] === 'Sim' ? 'com_venda' : 'sem_venda';

if ($nome === '' || $cpf === '' || $endereco === '' || $telefone === '') {
    exit('Erro: preencha todos os dados do cliente.');
}
if (!preg_match('/^\d{11}$/', $cpf)) {
    exit('Erro: CPF inválido.');
}
if (!preg_match('/^[0-9\s\-\(\)]+$/', $telefone)) {
    exit('Erro: Telefone inválido.');
}

try {
    $pdo->beginTransaction();

    if ($idCli) {
        $stmt = $pdo->prepare("UPDATE cad_cli SET nome = ?, cpf = ?, endereco = ?, telefone = ?, tipo = ? WHERE idCli = ?");
        $stmt->execute([$nome, $cpf, $endereco, $telefone, $tipo, $idCli]);

        if ($_SESSION['venda'] === 'Sim') {
            $idAdm       = (int) ($_POST['select-adm']  ?? 0);
            $fun_ids     = $_POST['select_fun']       ?? [];
            $numContrato = (int) ($_POST['idVenda']   ?? 0);
            $tipoVenda   = $_POST['select_tipo']      ?? 'Normal';
            $valorTot    = (float) ($_POST['valor']    ?? 0);
            $dataV       = $_POST['data']             ?? date('Y-m-d');

            if (!$idAdm || !$numContrato || $valorTot <= 0 || empty($fun_ids)) {
                throw new Exception('Dados da venda incompletos ou inválidos.');
            }
            $dt = DateTime::createFromFormat('Y-m-d', $dataV);
            if (!$dt || $dt > new DateTime()) {
                throw new Exception('Data da venda inválida ou futura.');
            }

            $firstFun = (int) $fun_ids[0];

            $stmt = $pdo->prepare("SELECT v.id FROM venda v JOIN venda_cli vc ON vc.idVenda = v.id WHERE vc.idCli = ? LIMIT 1");
            $stmt->execute([$idCli]);
            $existingVenda = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingVenda) {
                $vendaPk = (int) $existingVenda['id'];

                $stmt = $pdo->prepare("UPDATE venda SET idVenda = ?, tipo = ?, valor = ?, dataV = ?, idAdm = ? WHERE id = ?");
                $stmt->execute([$numContrato, $tipoVenda, $valorTot, $dataV, $idAdm, $vendaPk]);

                $stmt = $pdo->prepare("DELETE FROM venda_fun WHERE idVenda = ?");
                $stmt->execute([$vendaPk]);

                $stmt = $pdo->prepare("INSERT INTO venda_fun (idFun, idVenda) VALUES (?, ?)");
                foreach ($fun_ids as $funId) {
                    $fun = (int) $funId;
                    $stmt->execute([$fun, $vendaPk]);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO venda (idVenda, tipo, valor, dataV, idAdm) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$numContrato, $tipoVenda, $valorTot, $dataV, $idAdm]);
                $vendaPk = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO venda_cli (idCli, idVenda) VALUES (?, ?)");
                $stmt->execute([$idCli, $vendaPk]);

                $stmt = $pdo->prepare("INSERT INTO venda_fun (idFun, idVenda) VALUES (?, ?)");
                foreach ($fun_ids as $funId) {
                    $fun = (int) $funId;
                    $stmt->execute([$fun, $vendaPk]);
                }

                // Inserir notificacoes para admins
                $stmtAdmin = $pdo->query("SELECT idFun FROM cad_fun WHERE acesso = 'admin'");
                $admins = $stmtAdmin->fetchAll(PDO::FETCH_COLUMN);

                $stmtNotif = $pdo->prepare("INSERT INTO notificacoes (idFun, mensagem, link, lida, data_criacao) VALUES (?, ?, ?, 0, NOW())");
                foreach ($admins as $idAdmin) {
                    $mensagem = "Nova venda realizada por ID Func. $firstFun no valor de R$ " . number_format($valorTot, 2, ',', '.');
                    $link = "../../_html/_detalhes/detalhesVenda.php?idVenda=$vendaPk";
                    $stmtNotif->execute([$idAdmin, $mensagem, $link]);
                }
            }

            $stmt = $pdo->prepare("UPDATE cad_cli SET idFun = ? WHERE idCli = ?");
            $stmt->execute([$firstFun, $idCli]);
        }
    } else {
        if ($_SESSION['venda'] === 'Sim') {
            $idAdm       = (int) ($_POST['select-adm']  ?? 0);
            $fun_ids     = $_POST['select_fun']       ?? [];
            $numContrato = (int) ($_POST['idVenda']   ?? 0);
            $tipoVenda   = $_POST['select_tipo']      ?? 'Normal';
            $valorTot    = (float) ($_POST['valor']    ?? 0);
            $dataV       = $_POST['data']             ?? date('Y-m-d');

            if (!$idAdm || !$numContrato || $valorTot <= 0 || empty($fun_ids)) {
                throw new Exception('Dados da venda incompletos ou inválidos.');
            }
            $dt = DateTime::createFromFormat('Y-m-d', $dataV);
            if (!$dt || $dt > new DateTime()) {
                throw new Exception('Data da venda inválida ou futura.');
            }

            $firstFun = (int) $fun_ids[0];

            $stmtCli = $pdo->prepare("INSERT INTO cad_cli (nome, cpf, idFun, endereco, telefone, tipo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtCli->execute([$nome, $cpf, $firstFun, $endereco, $telefone, 'com_venda']);
            $idCli = $pdo->lastInsertId();

            $stmtVenda = $pdo->prepare("INSERT INTO venda (idVenda, tipo, valor, dataV, idAdm) VALUES (?, ?, ?, ?, ?)");
            $stmtVenda->execute([$numContrato, $tipoVenda, $valorTot, $dataV, $idAdm]);
            $vendaPk = $pdo->lastInsertId();

            $stmtVF = $pdo->prepare("INSERT INTO venda_fun (idFun, idVenda) VALUES (?, ?)");
            $stmtVC = $pdo->prepare("INSERT INTO venda_cli (idCli, idVenda) VALUES (?, ?)");
            foreach ($fun_ids as $funId) {
                $fun = (int) $funId;
                $stmtVF->execute([$fun, $vendaPk]);
                if ($fun === $firstFun) {
                    $stmtVC->execute([$idCli, $vendaPk]);
                }
            }

            // Inserir notificacoes para admins
            $stmtAdmin = $pdo->query("SELECT idFun FROM cad_fun WHERE acesso = 'admin'");
            $admins = $stmtAdmin->fetchAll(PDO::FETCH_COLUMN);

            // Buscar o nome do funcionário responsável pela venda
            $stmtFun = $pdo->prepare("SELECT nome FROM cad_fun WHERE idFun = ?");
            $stmtFun->execute([$firstFun]);
            $funData = $stmtFun->fetch(PDO::FETCH_ASSOC);
            $nomeFun = $funData ? $funData['nome'] : 'Funcionário desconhecido';
            
            // Preparar a inserção da notificação
            $stmtNotif = $pdo->prepare("INSERT INTO notificacoes (idFun, mensagem, link, lida, data_criacao, idVenda) VALUES (?, ?, ?, 0, NOW(), ?)");
            $VendaID = (int) $vendaPk;
            
            // Criar a mensagem de notificação
            $mensagem = "Nova venda realizada por $nomeFun no valor de R$ " . number_format($valorTot, 2, ',', '.');
            $link = "../../_html/_detalhes/detalhesVenda.php?idVenda=$vendaPk";
            
            // Inserir a notificação para cada administrador
            foreach ($admins as $idAdmin) {
                $stmtNotif->execute([$idAdmin, $mensagem, $link, $VendaID]);
            }
        } else {
            $stmtCli = $pdo->prepare("INSERT INTO cad_cli (nome, cpf, idFun, endereco, telefone, tipo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtCli->execute([$nome, $cpf, $_SESSION['user_id'], $endereco, $telefone, 'sem_venda']);
            $idCli = $pdo->lastInsertId();
        }
    }

    $pdo->commit();
    unset($_SESSION['venda']);
    header('Location: ../../_html/_lista/listaCli.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    exit('Erro ao salvar cliente: ' . htmlspecialchars($e->getMessage()));
}
?>
