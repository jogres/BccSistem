<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/lib/Logger.php';
require __DIR__ . '/../../app/middleware/require_admin.php'; // Apenas admin pode excluir
require __DIR__ . '/../../app/lib/FileUpload.php';
require __DIR__ . '/../../app/models/Venda.php';

$user = Auth::user();

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Logger::warning('Tentativa de deletar venda com método inválido', ['user_id' => $user['id']]);
    $_SESSION['error'] = 'Método inválido';
    header('Location: index.php');
    exit;
}

// Verificar ID
if (!isset($_POST['id'])) {
    Logger::warning('Tentativa de deletar venda sem ID', ['user_id' => $user['id']]);
    $_SESSION['error'] = 'Venda não encontrada';
    header('Location: index.php');
    exit;
}

$vendaId = (int)$_POST['id'];
$venda = Venda::find($vendaId);

if (!$venda) {
    Logger::warning('Tentativa de deletar venda inexistente', [
        'user_id' => $user['id'],
        'venda_id' => $vendaId
    ]);
    $_SESSION['error'] = 'Venda não encontrada';
    header('Location: index.php');
    exit;
}

try {
    // Deletar arquivo do contrato se existir
    if ($venda['arquivo_contrato']) {
        FileUpload::delete($venda['arquivo_contrato'], 'contratos');
    }
    
    // Soft delete da venda
    Venda::softDelete($vendaId);
    
    // Log da exclusão
    Logger::crud('DELETE', 'vendas', $vendaId, $user['id'], [
        'numero_contrato' => $venda['numero_contrato'],
        'cliente_id' => $venda['cliente_id']
    ]);
    
    $_SESSION['success'] = 'Venda excluída com sucesso!';
    
} catch (Exception $e) {
    Logger::error('Erro ao excluir venda', [
        'user_id' => $user['id'],
        'venda_id' => $vendaId,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    $_SESSION['error'] = 'Erro ao excluir venda: ' . $e->getMessage();
}

header('Location: index.php');
exit;

