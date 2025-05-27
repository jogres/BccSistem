<?php

require_once __DIR__ . '/../../config/db.php';
include('../../_php/_login/logado.php');

// Verifica se o usuário tem permissão de administrador
if ($acesso !== 'admin') {
    exit('Acesso negado.');
}

// Verifica se o ID da venda foi fornecido
if (!isset($_POST['idVenda']) || !is_numeric($_POST['idVenda'])) {
    exit('ID de venda inválido.');
}

$idVenda = (int) $_POST['idVenda'];

try {
    // Inicia a transação
    $pdo->beginTransaction();

    // Exclui notificações relacionadas à venda
    $stmtNotif = $pdo->prepare("DELETE FROM notificacoes WHERE link LIKE ?");
    $linkPattern = "%idVenda=$idVenda";
    $stmtNotif->execute([$linkPattern]);

    // Exclui a venda (as tabelas venda_fun e venda_cli serão afetadas automaticamente devido às restrições ON DELETE CASCADE)
    $stmtVenda = $pdo->prepare("DELETE FROM venda WHERE id = ?");
    $stmtVenda->execute([$idVenda]);

    // Confirma a transação
    $pdo->commit();

    // Redireciona para a página de dashboard com uma mensagem de sucesso
    $_SESSION['success'] = 'Venda recusada e excluída com sucesso.';
    header('Location: ../../_html/_dashboard/dashboard.php');
    exit;
} catch (Exception $e) {
    // Reverte a transação em caso de erro
    $pdo->rollBack();
    exit('Erro ao recusar venda: ' . htmlspecialchars($e->getMessage()));
}
?>
