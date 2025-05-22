<?php
// Incluir verificação de login
include('../../_php/_login/logado.php');

// Conexão com o banco de dados
$conn = new mysqli('localhost', 'root', '', 'bcc');
if ($conn->connect_error) {
    die('Falha na conexão: ' . $conn->connect_error);
}
$conn->begin_transaction();

try {
    // 1) Captura dados do cliente
    $nome     = trim($_POST['nome'] ?? '');
    $cpf      = trim($_POST['cpf'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    // Validação básica de telefone
    if (!preg_match('/^[0-9\s\-()]+$/', $telefone)) {
        throw new Exception('Telefone inválido.');
    }

    // 2) Checa escolha de venda
    if ($_SESSION['venda'] === 'Sim') {
        // Dados de venda
        $idAdm      = (int) ($_POST['select-adm'] ?? 0);
        $fun_ids    = $_POST['select_fun'] ?? [];
        $businessId = (int) ($_POST['idVenda'] ?? 0);
        $tipo       = trim($_POST['select_tipo'] ?? '');
        $valorTot   = (float) ($_POST['valor'] ?? 0);
        $dataV      = $_POST['data'] ?? date('Y-m-d');

        // Validações
        if (empty($fun_ids)) {
            throw new Exception('Selecione ao menos um funcionário.');
        }
        if ($businessId <= 0) {
            throw new Exception('O número do contrato (idVenda) é obrigatório.');
        }
        if ($valorTot <= 0) {
            throw new Exception('O valor da venda deve ser maior que zero.');
        }

        // 3) Insere cliente (vincula ao primeiro vendedor apenas)
        $firstFun = $fun_ids[0];
        $stmtClient = $conn->prepare(
            'INSERT INTO cad_cli (nome, cpf, idFun, endereco, telefone, tipo)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$stmtClient) {
            throw new Exception('Erro no prepare cad_cli: ' . $conn->error);
        }
        $stmtClient->bind_param('ssisss', $nome, $cpf, $firstFun, $endereco, $telefone, $tipo);
        if (!$stmtClient->execute()) {
            throw new Exception('Erro ao inserir cliente: ' . $stmtClient->error);
        }
        $clientId = $conn->insert_id;
        $stmtClient->close();

        // 4) Prepara statements de venda e vínculos
        $stmtVenda = $conn->prepare(
            'INSERT INTO venda (idVenda, tipo, valor, dataV, idAdm)
             VALUES (?, ?, ?, ?, ?)'
        );
        if (!$stmtVenda) {
            throw new Exception('Erro no prepare venda: ' . $conn->error);
        }

        $stmtLinkFun = $conn->prepare(
            'INSERT INTO venda_fun (idFun, idVenda) VALUES (?, ?)'
        );
        if (!$stmtLinkFun) {
            throw new Exception('Erro no prepare venda_fun: ' . $conn->error);
        }

        $stmtLinkCli = $conn->prepare(
            'INSERT INTO venda_cli (idCli, idVenda) VALUES (?, ?)'
        );
        if (!$stmtLinkCli) {
            throw new Exception('Erro no prepare venda_cli: ' . $conn->error);
        }

        // 5) Insere as vendas divididas por funcionário
        $n = count($fun_ids);
        $valorPorFun = round($valorTot / $n, 2);
        $firstSaleId = null;

        foreach ($fun_ids as $fun) {
            // 5.1) Venda
            $stmtVenda->bind_param('isdsi', $businessId, $tipo, $valorPorFun, $dataV, $idAdm);
            if (!$stmtVenda->execute()) {
                throw new Exception('Erro ao inserir venda: ' . $stmtVenda->error);
            }
            $saleId = $conn->insert_id;
            if ($firstSaleId === null) {
                $firstSaleId = $saleId;
            }

            // 5.2) Vínculo funcionário ↔ venda
            $stmtLinkFun->bind_param('ii', $fun, $saleId);
            if (!$stmtLinkFun->execute()) {
                throw new Exception('Erro ao inserir venda_fun: ' . $stmtLinkFun->error);
            }

            // 5.3) Vínculo cliente ↔ venda
            $stmtLinkCli->bind_param('ii', $clientId, $saleId);
            if (!$stmtLinkCli->execute()) {
                throw new Exception('Erro ao inserir venda_cli: ' . $stmtLinkCli->error);
            }
        }

        // Fecha statements
        $stmtVenda->close();
        $stmtLinkFun->close();
        $stmtLinkCli->close();

        // 6) Commit e redirecionamento
        $conn->commit();
        header('Location: sucesso.php');
        exit;

    } elseif ($_SESSION['venda'] === 'Nao') {
        // 7) Inserção de cliente sem venda
        $idFun = $_SESSION['user_id'];
        $tipo  = 'sem_venda';

        $stmtClient = $conn->prepare(
            'INSERT INTO cad_cli (nome, cpf, idFun, endereco, telefone, tipo)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$stmtClient) {
            throw new Exception('Erro no prepare cad_cli: ' . $conn->error);
        }
        $stmtClient->bind_param('ssisss', $nome, $cpf, $idFun, $endereco, $telefone, $tipo);
        if (!$stmtClient->execute()) {
            throw new Exception('Erro ao inserir cliente: ' . $stmtClient->error);
        }
        $stmtClient->close();

        $conn->commit();
        header('Location: sucesso.php');
        exit;

    } else {
        throw new Exception('Valor inválido para $_SESSION["venda"].');
    }

} catch (Exception $e) {
    // Rollback em caso de erro
    $conn->rollback();
    echo 'Erro ao cadastrar: ' . $e->getMessage();
} finally {
    $conn->close();
}
?>
