<?php

require __DIR__ . '/../shared/verify_session.php';
require __DIR__ . '/../../config/database.php';
$pdo = getPDO();

$id_venda = isset($_GET['venda']) ? (int) $_GET['venda'] : 0;
if ($id_venda < 1) {
    echo "Venda inválida.";
    exit;
}

// busca contrato
$saleStmt = $pdo->prepare("SELECT numero_contrato FROM vendas WHERE id_venda = :venda");
$saleStmt->execute([':venda' => $id_venda]);
$contrato = $saleStmt->fetchColumn();
if (!$contrato) {
    echo "Venda não encontrada.";
    exit;
}

// busca parcelas
$stmt = $pdo->prepare("
    SELECT numero_parcela, valor,
           DATE_FORMAT(data_prevista, '%d/%m/%Y') AS vencimento,
           status
    FROM parcelas_comissao
    WHERE id_venda = :venda
    ORDER BY numero_parcela
");
$stmt->execute([':venda' => $id_venda]);
$parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// incluir a view
include __DIR__ . '/../../_html/_parcelas_comissao/list.php';
