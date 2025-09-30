<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/lib/CSRF.php';
require __DIR__ . '/../../app/middleware/require_login.php';
require __DIR__ . '/../../app/models/Cliente.php';

CSRF::validate();
$id = (int)($_POST['id'] ?? 0);
$cliente = Cliente::find($id);
if (!$cliente || $cliente['deleted_at'] !== null) {
    http_response_code(404);
    die('Cliente não encontrado.');
}
$user = Auth::user();
$isAdmin = Auth::isAdmin();
if (!$isAdmin && (int)$cliente['criado_por'] !== (int)$user['id']) {
    http_response_code(403);
    die('Sem permissão para excluir este cliente.');
}

Cliente::softDelete($id);
header('Location: ' . base_url('clientes/index.php'));
exit;
