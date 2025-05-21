
<?php
// Database Insertion Script
include('../../_php/_login/logado.php');

// 1) Conexão com o banco e início de transação
$conn = new mysqli('localhost', 'root', '', 'bcc');
if ($conn->connect_error) {
    die('Falha na conexão: ' . $conn->connect_error);
}
$conn->begin_transaction();

try {
    // 2) Captura e validações iniciais
    $nome      = trim($_POST['nome'] ?? '');
    $cpf       = trim($_POST['cpf'] ?? '');
    $endereco  = trim($_POST['endereco'] ?? '');
    $telefone  = trim($_POST['telefone'] ?? '');
    $idAdm     = (int) ($_POST['select-adm'] ?? 0);
    $fun_ids   = $_POST['select_fun'] ?? [];
    $businessId= (int) ($_POST['idVenda'] ?? 0);
    $tipo      = trim($_POST['select_tipo'] ?? '');
    $valorTot  = (float) ($_POST['valor'] ?? 0);
    $dataV     = $_POST['data'] ?? date('Y-m-d');

    // Validações básicas
    /*if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $cpf)) {
        throw new Exception('CPF inválido. Use o formato xxx.xxx.xxx-xx.');
    }*/
    if (!preg_match('/^[0-9\s\-()]+$/', $telefone)) {
        throw new Exception('Telefone inválido.');
    }
    if (empty($fun_ids)) {
        throw new Exception('Selecione ao menos um funcionário.');
    }
    if ($businessId <= 0) {
        throw new Exception('O campo Número do Contrato (idVenda) é obrigatório.');
    }
    if ($valorTot <= 0) {
        throw new Exception('O valor da venda deve ser maior que zero.');
    }

    // 3) Inserção do cliente em cad_cli, vinculando ao primeiro vendedor
    $firstFun = $fun_ids[0];
    $stmtClient = $conn->prepare(
        'INSERT INTO cad_cli (nome, cpf, idFun, endereco, telefone, tipo) VALUES (?, ?, ?, ?, ?, ?)'
    );
    if (!$stmtClient) throw new Exception('Erro no prepare cad_cli: ' . $conn->error);
    $stmtClient->bind_param('ssisss', $nome, $cpf, $firstFun, $endereco, $telefone, $tipo);
    if (!$stmtClient->execute()) {
        throw new Exception('Erro ao inserir cliente: ' . $stmtClient->error);
    }
    $clientId = $conn->insert_id;
    $stmtClient->close();

    // 4) Preparação dos statements para venda, vínculo e comissão
    $stmtVenda = $conn->prepare(
        'INSERT INTO venda (idVenda, tipo, valor, dataV, idAdm) VALUES (?, ?, ?, ?, ?)' 
    );
    if (!$stmtVenda) throw new Exception('Erro no prepare venda: ' . $conn->error);

    $stmtLinkFun = $conn->prepare(
        'INSERT INTO venda_fun (idFun, idVenda) VALUES (?, ?)' 
    );
    if (!$stmtLinkFun) throw new Exception('Erro no prepare venda_fun: ' . $conn->error);

    $stmtLinkCli = $conn->prepare(
        'INSERT INTO venda_cli (idCli, idVenda) VALUES (?, ?)' 
    );
    if (!$stmtLinkCli) throw new Exception('Erro no prepare venda_cli: ' . $conn->error);

    $stmtCom = $conn->prepare(
        'INSERT INTO comissao (idVenda, totalV, mesC, idFun, primeira, segunda, terceira, quarta, totalC)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)' 
    );
    if (!$stmtCom) throw new Exception('Erro no prepare comissao: ' . $conn->error);

    // 5) Lógica de divisão e inserção múltipla
    $n = count($fun_ids);
    $valorPorFun = round($valorTot / $n, 2);
    $firstSaleId = null;

    foreach ($fun_ids as $fun) {
        // 5.1) Insere venda
        $stmtVenda->bind_param('isdsi', $businessId, $tipo, $valorPorFun, $dataV, $idAdm);
        if (!$stmtVenda->execute()) {
            throw new Exception('Erro ao inserir venda: ' . $stmtVenda->error);
        }
        $saleId = $conn->insert_id;
        if ($firstSaleId === null) {
            $firstSaleId = $saleId;
        }

        // 5.2) Insere vínculo na venda_fun
        $stmtLinkFun->bind_param('ii', $fun, $saleId);
        if (!$stmtLinkFun->execute()) {
            throw new Exception('Erro ao inserir venda_fun: ' . $stmtLinkFun->error);
        }

        // 5.3) Insere vínculo na venda_cli
        $stmtLinkCli->bind_param('ii', $clientId, $saleId);
        if (!$stmtLinkCli->execute()) {
            throw new Exception('Erro ao inserir venda_cli: ' . $stmtLinkCli->error);
        }

        // 5.4) Insere comissão (exemplo)
        $mesC = date('Y-m-01', strtotime($dataV));
        $primeira = 0; $segunda = 0; $terceira = 0; $quarta = 0;
        $totalC = $primeira + $segunda + $terceira + $quarta;

        $stmtCom->bind_param('idisidddi', $saleId, $valorPorFun, $mesC, $fun,
                             $primeira, $segunda, $terceira, $quarta, $totalC);
        if (!$stmtCom->execute()) {
            throw new Exception('Erro ao inserir comissao: ' . $stmtCom->error);
        }
    }

    // 6) Commit e redirecionamento
    $conn->commit();
    header('Location: sucesso.php');
    exit;

} catch (Exception $e) {
    // Rollback em caso de erro
    $conn->rollback();
    echo 'Erro ao cadastrar: ' . $e->getMessage();
} finally {
    // Fecha statements e conexão
    foreach (['stmtVenda','stmtLinkFun','stmtLinkCli','stmtCom'] as $v) {
        if (!empty($$v)) $$v->close();
    }
    $conn->close();
}
?>
