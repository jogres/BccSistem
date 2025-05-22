<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

// 1) Permissão de qualquer usuário logado
if (!in_array($acesso, ['admin','user','vendedor'])) {
    exit('Acesso negado.');
}

// 2) Coleta e validação dos dados básicos
$nome     = trim($_POST['nome'] ?? '');
$cpf      = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');

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

    if ($_SESSION['venda'] === 'Sim') {
        // 3) Dados da venda
        $idAdm     = (int) ($_POST['select-adm']  ?? 0);
        $fun_ids   = $_POST['select_fun']       ?? [];
        $numContrato = (int) ($_POST['idVenda'] ?? 0);   // número de contrato
        $tipoVenda = $_POST['select_tipo']      ?? 'Normal';
        $valorTot  = (float) ($_POST['valor']    ?? 0);
        $dataV     = $_POST['data']             ?? date('Y-m-d');

        // validações
        if (!$idAdm || !$numContrato || $valorTot <= 0 || empty($fun_ids)) {
            throw new Exception('Dados da venda incompletos ou inválidos.');
        }
        $dt = DateTime::createFromFormat('Y-m-d', $dataV);
        if (!$dt || $dt > new DateTime()) {
            throw new Exception('Data da venda inválida ou futura.');
        }

        // 4) Insere cliente (vendedor principal = primeiro da lista)
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

        // 5) Insere a venda e captura o PK gerado (venda.id)
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
        // *** Aqui usamos lastInsertId() para obter venda.id ***
        $vendaPk = $pdo->lastInsertId();

        // 6) Vincula venda_fun e venda_cli usando o PK
        $stmtVF = $pdo->prepare("
            INSERT INTO venda_fun (idFun, idVenda)
            VALUES (?, ?)
        ");
        $stmtVC = $pdo->prepare("
            INSERT INTO venda_cli (idCli, idVenda)
            VALUES (?, ?)
        ");
        foreach ($fun_ids as $funId) {
            $fun = (int) $funId;
            $stmtVF->execute([$fun, $vendaPk]);
            // vincula cliente apenas uma vez (apenas para o primeiro fun)
            if ($fun === $firstFun) {
                $stmtVC->execute([$idCli, $vendaPk]);
            }
        }

    } elseif ($_SESSION['venda'] === 'Nao') {
        // 7) Cadastro de cliente sem venda
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
    } else {
        throw new Exception('Selecione Sim ou Não para a venda.');
    }

    $pdo->commit();
    unset($_SESSION['venda']);
    header('Location: ../../_html/_cadastro/sucesso.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    exit('Erro ao cadastrar: ' . htmlspecialchars($e->getMessage()));
}
