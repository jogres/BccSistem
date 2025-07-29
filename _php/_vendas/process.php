<?php
// /_consorcioBcc/_php/_vendas/process.php
require __DIR__ . '/../../config/database.php';
session_start();

// Exibir erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

$pdo = getPDO();
// Configurar PDO para lançar exceções
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

try {
    // Inicia transação para garantir atomicidade
    $pdo->beginTransaction();

    // --------------------------------------------------
    // 1) INSERT / UPDATE DO CLIENTE
    // --------------------------------------------------
    $clienteId = (int) ($_POST['id_cliente'] ?? 0);
    $cliNome   = trim($_POST['cli_nome']     ?? '');
    $cliCpf    = preg_replace('/\D/', '', $_POST['cli_cpf'] ?? '');
    $cliTel    = trim($_POST['cli_telefone'] ?? '');
    $cliCel    = trim($_POST['cli_celular']  ?? '');
    $cliLog    = trim($_POST['cli_logradouro'] ?? '');
    $cliNum    = trim($_POST['cli_numero']     ?? '');
    $cliComp   = trim($_POST['cli_complemento']?? '');
    $cliBairro = trim($_POST['cli_bairro']     ?? '');
    $cliCid    = trim($_POST['cli_cidade']     ?? '');
    $cliEst    = trim($_POST['cli_estado']     ?? '');
    $cliCep    = trim($_POST['cli_cep']        ?? '');
    $cliMotivo = trim($_POST['cli_motivo']     ?? '');

    if ($cliNome === '') {
        throw new Exception('Nome do cliente é obrigatório.');
    }

    if ($clienteId > 0) {
        // Atualiza cliente
        $sql = "UPDATE clientes SET
                    nome        = :nome,
                    cpf         = :cpf,
                    telefone    = :tel,
                    celular     = :cel,
                    logradouro  = :log,
                    numero      = :num,
                    complemento = :comp,
                    bairro      = :bairro,
                    cidade      = :cidade,
                    estado      = :estado,
                    cep         = :cep,
                    motivo      = :motivo
                WHERE id_cliente = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'=>$cliNome,
            ':cpf'=>$cliCpf,
            ':tel'=>$cliTel,
            ':cel'=>$cliCel,
            ':log'=>$cliLog,
            ':num'=>$cliNum,
            ':comp'=>$cliComp,
            ':bairro'=>$cliBairro,
            ':cidade'=>$cliCid,
            ':estado'=>$cliEst,
            ':cep'=>$cliCep,
            ':motivo'=>$cliMotivo,
            ':id'=>$clienteId
        ]);
    } else {
        // Insere cliente
        $sql = "INSERT INTO clientes
                    (nome, cpf, telefone, celular, logradouro, numero,
                     complemento, bairro, cidade, estado, cep, motivo)
                VALUES
                    (:nome, :cpf, :tel, :cel, :log, :num,
                     :comp, :bairro, :cidade, :estado, :cep, :motivo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'=>$cliNome,
            ':cpf'=>$cliCpf,
            ':tel'=>$cliTel,
            ':cel'=>$cliCel,
            ':log'=>$cliLog,
            ':num'=>$cliNum,
            ':comp'=>$cliComp,
            ':bairro'=>$cliBairro,
            ':cidade'=>$cliCid,
            ':estado'=>$cliEst,
            ':cep'=>$cliCep,
            ':motivo'=>$cliMotivo
        ]);
        $clienteId = (int) $pdo->lastInsertId();
    }

    // --------------------------------------------------
    // 2) INSERT / UPDATE DA VENDA
    // --------------------------------------------------
    $idVenda        = isset($_POST['id_venda']) ? (int) $_POST['id_venda'] : null;
    $numeroContrato = trim($_POST['numero_contrato'] ?? '');
    $idVirador      = (int)($_POST['id_virador']       ?? 0);
    $idVendedor     = (int)($_POST['id_vendedor']      ?? 0);
    $idAdm          = (int)($_POST['id_administradora']?? 0);
    $idPlano        = (int)($_POST['id_plano_comissao']?? 0);
    $modalidade     = trim($_POST['modalidade']        ?? '');
    $valorTotal     = (float)($_POST['valor_total']     ?? 0);
    $dataVenda      = $_POST['data_venda']            ?? date('Y-m-d');
    $status         = $_POST['status']                ?? 'PENDENTE';

    if ($numeroContrato === '') {
        throw new Exception('Número do contrato é obrigatório.');
    }
    if (!$idVendedor || !$idVirador || !$idAdm || !$idPlano) {
        throw new Exception('Preencha vendedor, virador, administradora e plano.');
    }
    if ($valorTotal <= 0) {
        throw new Exception('Valor da venda deve ser maior que zero.');
    }

    // Divide valor entre vendedor e virador
    if ($idVendedor !== $idVirador) {
        $shareV = 0.5; $shareR = 0.5;
    } else {
        $shareV = 1.0; $shareR = 0.0;
    }

    if ($idVenda) {
        // Atualiza venda
        $sql = "UPDATE vendas SET
                    numero_contrato   = :ctr,
                    id_cliente        = :cli,
                    id_vendedor       = :ven,
                    id_virador        = :vir,
                    id_administradora = :adm,
                    id_plano_comissao = :pl,
                    modalidade        = :mod,
                    valor_total       = :val,
                    data_venda        = :dv,
                    status            = :st
                WHERE id_venda = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ctr'=>$numeroContrato,
            ':cli'=>$clienteId,
            ':ven'=>$idVendedor,
            ':vir'=>$idVirador,
            ':adm'=>$idAdm,
            ':pl'=>$idPlano,
            ':mod'=>$modalidade,
            ':val'=>$valorTotal,
            ':dv'=>$dataVenda,
            ':st'=>$status,
            ':id'=>$idVenda
        ]);
    } else {
        // Insere venda
        $sql = "INSERT INTO vendas
                    (numero_contrato,id_cliente,id_vendedor,id_virador,
                     id_administradora,id_plano_comissao,modalidade,
                     valor_total,data_venda,status)
                VALUES
                    (:ctr,:cli,:ven,:vir,
                     :adm,:pl,:mod,
                     :val,:dv,:st)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ctr'=>$numeroContrato,
            ':cli'=>$clienteId,
            ':ven'=>$idVendedor,
            ':vir'=>$idVirador,
            ':adm'=>$idAdm,
            ':pl'=>$idPlano,
            ':mod'=>$modalidade,
            ':val'=>$valorTotal,
            ':dv'=>$dataVenda,
            ':st'=>$status
        ]);
        $idVenda = (int) $pdo->lastInsertId();
    }

    // --------------------------------------------------
    // 3) ATUALIZA total_parcelas_comiss na venda
    // --------------------------------------------------
    $stmt = $pdo->prepare(
        "UPDATE vendas v
            JOIN planos_comissao p ON v.id_plano_comissao = p.id_plano_comissao
            SET v.total_parcelas_comiss = p.num_parcelas_comiss
          WHERE v.id_venda = :id"
    );
    $stmt->execute([':id' => $idVenda]);

    // --------------------------------------------------
    // 4) EXECUTA PROCEDURES DE NÍVEL E PARCELAS
    // --------------------------------------------------
    try {
        // Atualiza níveis mensal
        $pdo->exec("CALL sp_atualiza_nivel_por_venda({$idVenda})");
    } catch (PDOException $ex) {
        throw new Exception("Erro ao atualizar nível: " . $ex->getMessage());
    }

    try {
        // Gera/recalcula parcelas de comissão
        $pdo->exec("CALL sp_gerar_parcelas_comissao({$idVenda})");
    } catch (PDOException $ex) {
        throw new Exception("Erro ao gerar parcelas: " . $ex->getMessage());
    }

    // Commit real da transação
    $pdo->commit();

    echo "<p>Venda e cliente registrados com sucesso! (idVenda={$idVenda}, clienteId={$clienteId})</p>";

} catch (Exception $e) {
    // Desfaz transação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo '<h2>Erro no processamento da venda</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</pre>';
    echo '<h3>Trace:</h3>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES) . '</pre>';
    exit;
}

?>
