<?php
  require_once __DIR__ . '/../../config/db.php';

if ($acesso !== 'admin') {
    exit('Acesso restrito.');
}

$idVenda = isset($_GET['idVenda']) ? (int) $_GET['idVenda'] : null;
if (!$idVenda) {
    exit('Venda não especificada.');
}

// Consulta principal da venda
$stmt = $pdo->prepare("
    SELECT 
        v.id,
        v.idVenda AS num_contrato,
        v.tipo,
        v.valor,
        v.dataV,
        v.confirmada,
        a.nome AS nome_adm
    FROM venda v
    LEFT JOIN cad_adm a ON v.idAdm = a.idAdm
    WHERE v.id = ?
");
$stmt->execute([$idVenda]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venda) {
    exit('Venda não encontrada.');
}

// Consulta do cliente vinculado
$stmt = $pdo->prepare("
    SELECT c.nome AS nome_cliente 
    FROM venda_cli vc
    JOIN cad_cli c ON vc.idCli = c.idCli
    WHERE vc.idVenda = ?
");
$stmt->execute([$idVenda]);
$cliente = $stmt->fetchColumn();

// Consulta dos funcionários envolvidos
$stmt = $pdo->prepare("
    SELECT f.nome 
    FROM venda_fun vf
    JOIN cad_fun f ON vf.idFun = f.idFun
    WHERE vf.idVenda = ?
");
$stmt->execute([$idVenda]);
$funcionarios = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>