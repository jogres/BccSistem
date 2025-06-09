<?php
// detalhesVenda.php
// Inclui sessão e login

require_once __DIR__ . '/../../config/db.php';

// Somente administradores podem acessar
if ($acesso !== 'admin') {
    exit('Acesso restrito.');
}

// Captura idVenda via GET
$id = isset($_GET['idVenda']) ? (int) $_GET['idVenda'] : null;
if (!$id) {
    exit('Venda não especificada.');
}

// 1) Consulta principal da venda
$stmt = $pdo->prepare(
    "SELECT
         v.id,
         v.idVenda     AS num_contrato,
         v.tipo,
         v.valor,
         v.dataV,
         v.confirmada,
         v.segmento,
         a.nome        AS nome_adm
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

// 2) Cliente vinculado
$stmt = $pdo->prepare(
    "SELECT c.nome
       FROM venda_cli vc
       JOIN cad_cli c ON vc.idCli = c.idCli
       WHERE vc.idVenda = ?
       LIMIT 1"
);
$stmt->execute([$id]);
$cliente = $stmt->fetchColumn() ?: '';

// 3) Funcionários envolvidos
$stmt = $pdo->prepare(
    "SELECT f.idFun, f.nome
       FROM venda_fun vf
       JOIN cad_fun f ON vf.idFun = f.idFun
       WHERE vf.idVenda = ?"
);
$stmt->execute([$id]);
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Notificações/Parcelas vinculadas à venda (tabela notificacoes)
$stmt = $pdo->prepare(
    "SELECT parcela, idFun, data_criacao, lida
       FROM notificacoes
      WHERE idVenda = ?
      ORDER BY parcela"
);
$stmt->execute([$id]);
$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função para controlar permissão de confirmação
function canConfirm(int $parcela, string $acesso): bool {
    if ($parcela === 1) {
        return $acesso === 'admin';
    }
    return in_array($acesso, ['admin'], true);
}
?>
