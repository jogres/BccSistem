<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

// 1) Verifica permissão de acesso
if (!in_array($acesso, ['admin','user','vendedor'])) {
    exit('Acesso negado.');
}

// 2) Coleta dados básicos do cliente
$idCli    = isset($_POST['idCli']) ? (int) $_POST['idCli'] : null;
$nome     = trim($_POST['nome']     ?? '');
$cpf      = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$tipo     = $_SESSION['venda'] === 'Sim' ? 'com_venda' : 'sem_venda';

// 3) Validações comuns
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
        //
        // === EDIÇÃO DE CLIENTE EXISTENTE ===
        //
        $stmt = $pdo->prepare("
            UPDATE cad_cli
               SET nome      = ?,
                   cpf       = ?,
                   endereco  = ?,
                   telefone  = ?,
                   tipo      = ?
             WHERE idCli     = ?
        ");
        $stmt->execute([
            $nome,
            $cpf,
            $endereco,
            $telefone,
            $tipo,
            $idCli
        ]);

    } else {
        //
        // === CRIAÇÃO DE CLIENTE ===
        //
        // Se for com venda, insere cliente + venda + vínculos
        if ($_SESSION['venda'] === 'Sim') {
            // coleta dados de venda
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

            // insere cliente
            $firstFun = (int) $fun_ids[0];
            $stmtCli = $pdo->prepare("
                INSERT INTO cad_cli (nome, cpf, idFun, endereco, telefone, tipo)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtCli->execute([
                $nome,
                $cpf,
                $firstFun,
                $endereco,
                $telefone,
                'com_venda'
            ]);
            $idCli = $pdo->lastInsertId();

            // insere venda
            $stmtVenda = $pdo->prepare("
                INSERT INTO venda (idVenda, tipo, valor, dataV, idAdm)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtVenda->execute([
                $numContrato,
                $tipoVenda,
                $valorTot,
                $dataV,
                $idAdm
            ]);
            $vendaPk = $pdo->lastInsertId();

            // vincula venda_fun e venda_cli
            $stmtVF = $pdo->prepare("INSERT INTO venda_fun (idFun, idVenda) VALUES (?, ?)");
            $stmtVC = $pdo->prepare("INSERT INTO venda_cli (idCli, idVenda) VALUES (?, ?)");
            foreach ($fun_ids as $funId) {
                $fun = (int) $funId;
                $stmtVF->execute([$fun, $vendaPk]);
                if ($fun === $firstFun) {
                    $stmtVC->execute([$idCli, $vendaPk]);
                }
            }

        } else {
            // sem venda: só insere cliente
            $stmtCli = $pdo->prepare("
                INSERT INTO cad_cli (nome, cpf, idFun, endereco, telefone, tipo)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtCli->execute([
                $nome,
                $cpf,
                $_SESSION['user_id'],
                $endereco,
                $telefone,
                'sem_venda'
            ]);
            $idCli = $pdo->lastInsertId();
        }
    }

    $pdo->commit();
    unset($_SESSION['venda']);

    // redireciona para lista ou sucesso
    header('Location: ../../_html/_lista/listaCli.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    exit('Erro ao salvar cliente: ' . htmlspecialchars($e->getMessage()));
}
?>
