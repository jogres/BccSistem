<?php
require_once __DIR__ . '/../../config/db.php';

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'ID inválido.']);
    exit;
}

$idNotificacao = (int) $_POST['id'];

try {
    $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ?");
    $stmt->execute([$idNotificacao]);
    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao atualizar a notificação.']);
}
?>
