<?php
// _html/_detalhes/detalhesVenda.php


require_once __DIR__ . '/../../config/db.php';

if ($acesso !== 'admin') {
    exit('Acesso restrito.');
}

// Obtém o parâmetro idVenda (que, na verdade, é a PK 'id' da tabela venda)
$id = isset($_GET['idVenda']) ? (int) $_GET['idVenda'] : null;
if (!$id) {
    exit('Venda não especificada.');
}

// 1) Consulta principal pelo campo "id" (PK)
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
    LIMIT 1
");
$stmt->execute([$id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venda) {
    exit('Venda não encontrada.');
}

// 2) Consulta do cliente vinculado (se houver)
$stmt = $pdo->prepare("
    SELECT c.nome AS nome_cliente 
    FROM venda_cli vc
    JOIN cad_cli c ON vc.idCli = c.idCli
    WHERE vc.idVenda = ?
    LIMIT 1
");
$stmt->execute([$id]);
$cliente = $stmt->fetchColumn();
if ($cliente === false) {
    $cliente = '';
}

// 3) Consulta dos funcionários envolvidos
$stmt = $pdo->prepare("
    SELECT f.idFun, f.nome 
    FROM venda_fun vf
    JOIN cad_fun f ON vf.idFun = f.idFun
    WHERE vf.idVenda = ?
");
$stmt->execute([$id]);
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($funcionarios === false) {
    $funcionarios = [];
}


// 4) Verifica se já existe registro de parcela1
$stmt = $pdo->prepare("
    SELECT confirmada 
    FROM parcela1 
    WHERE idVenda = ? 
    LIMIT 1
");
$stmt->execute([$id]);
$resParcela1 = $stmt->fetchColumn(); // null = não existe; 0 ou 1 se existir

// 5) Define $parcela para uso no HTML. 
//    Aqui, como estamos exibindo apenas confirmação de parcela 1 pelo admin, fixamos em 1.
$parcela = 1; 
?>