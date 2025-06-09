<?php
// _html/_detalhes/detalhesVenda.php

require_once __DIR__ . '/../../config/db.php';

// Apenas administradores podem acessar
if ($acesso !== 'admin') {
    exit('Acesso restrito.');
}

// Obtém parâmetro idVenda (PK)
$id = isset($_GET['idVenda']) ? (int)$_GET['idVenda'] : null;
if (!$id) {
    exit('Venda não especificada.');
}

// 1) Consulta principal da venda
$stmt = $pdo->prepare(
    "SELECT
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
    LIMIT 1"
);
$stmt->execute([$id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venda) {
    exit('Venda não encontrada.');
}

// 2) Consulta do cliente vinculado
$stmt = $pdo->prepare(
    "SELECT c.nome FROM venda_cli vc
     JOIN cad_cli c ON vc.idCli = c.idCli
     WHERE vc.idVenda = ?
     LIMIT 1"
);
$stmt->execute([$id]);
$cliente = $stmt->fetchColumn();
if ($cliente === false) {
    $cliente = '';
}

// 3) Consulta dos funcionários envolvidos
$stmt = $pdo->prepare(
    "SELECT f.idFun, f.nome
     FROM venda_fun vf
     JOIN cad_fun f ON vf.idFun = f.idFun
     WHERE vf.idVenda = ?"
);
$stmt->execute([$id]);
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Busca notificações relacionadas a esta venda
$stmt = $pdo->prepare(
    "SELECT n.id, n.idFun, n.mensagem, n.link, n.lida, n.data_criacao, n.parcela
     FROM notificacoes n
     WHERE n.idVenda = ?
     ORDER BY n.parcela"
);
$stmt->execute([$id]);
$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ID do usuário atual para confirmação
$idFunSess = (int)$_SESSION['user_id'];

// Função para verificar permissão de confirmação
function canConfirm($parcela, $acesso) {
    if ($parcela === 1 && $acesso === 'admin') return true;
    if ($parcela > 1 && in_array($acesso, ['admin'])) return true;
    return false;
}
?>

