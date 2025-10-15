<?php
/**
 * API para buscar informações de um cliente
 * Usado na criação de vendas para preencher dados automaticamente
 */

require_once '../../vendor/autoload.php';
require_once '../../app/lib/Database.php';
require_once '../../app/lib/Auth.php';
require_once '../../app/models/Cliente.php';

session_start();

// Verifica se está logado
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Verifica se o ID do cliente foi enviado
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do cliente não fornecido']);
    exit;
}

$clienteId = (int)$_GET['id'];

// Busca o cliente
$cliente = Cliente::find($clienteId);

if (!$cliente) {
    http_response_code(404);
    echo json_encode(['error' => 'Cliente não encontrado']);
    exit;
}

// Retorna os dados do cliente
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'cliente' => [
        'id' => $cliente['id'],
        'nome' => $cliente['nome'],
        'telefone' => $cliente['telefone'],
        'cidade' => $cliente['cidade'],
        'estado' => $cliente['estado'],
        'interesse' => $cliente['interesse']
    ]
]);

