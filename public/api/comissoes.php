<?php
declare(strict_types=1);

require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/models/Comissao.php';
require __DIR__ . '/../../app/models/Venda.php';
require __DIR__ . '/../../app/models/Funcionario.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    Auth::startSessionSecure();
    if (!Auth::check()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $user = Auth::user();
    $isAdmin = Auth::isAdmin();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Apenas administradores podem acessar comissões
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem acessar comissões.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    switch ($method) {
        case 'GET':
            handleGet($user);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

function handleGet(array $user): void
{
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'vendedores':
            handleGetVendedores();
            break;
        case 'viradores':
            handleGetViradores();
            break;
        case 'vendas':
            handleGetVendas();
            break;
        case 'comissoes':
            handleGetComissoes();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ação inválida'], JSON_UNESCAPED_UNICODE);
    }
}

function handleGetVendedores(): void
{
    $vendedores = Comissao::getVendedoresDisponiveis();
    
    echo json_encode([
        'success' => true,
        'data' => $vendedores
    ], JSON_UNESCAPED_UNICODE);
}

function handleGetViradores(): void
{
    $viradores = Comissao::getViradoresDisponiveis();
    
    echo json_encode([
        'success' => true,
        'data' => $viradores
    ], JSON_UNESCAPED_UNICODE);
}

function handleGetVendas(): void
{
    $funcionarioId = (int)($_GET['funcionario_id'] ?? 0);
    $tipoComissao = trim($_GET['tipo'] ?? '');
    
    if (!$funcionarioId || !in_array($tipoComissao, ['vendedor', 'virador'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $vendas = Comissao::getVendasDisponiveis($funcionarioId, $tipoComissao);
    
    // Adicionar informações de comissões anteriores
    foreach ($vendas as &$venda) {
        $comissoes = Comissao::getByVenda($venda['id'], $tipoComissao);
        $venda['comissoes'] = $comissoes;
        $venda['valor_base'] = Comissao::calcularValorBase($venda);
        $venda['proxima_parcela'] = Comissao::getProximaParcela($venda['id'], $tipoComissao);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $vendas
    ], JSON_UNESCAPED_UNICODE);
}

function handleGetComissoes(): void
{
    $filters = [];
    
    if (isset($_GET['funcionario_id']) && $_GET['funcionario_id'] !== '') {
        $filters['funcionario_id'] = (int)$_GET['funcionario_id'];
    }
    
    if (isset($_GET['tipo_comissao']) && $_GET['tipo_comissao'] !== '') {
        $filters['tipo_comissao'] = $_GET['tipo_comissao'];
    }
    
    if (isset($_GET['venda_id']) && $_GET['venda_id'] !== '') {
        $filters['venda_id'] = (int)$_GET['venda_id'];
    }
    
    $comissoes = Comissao::all($filters);
    
    echo json_encode([
        'success' => true,
        'data' => $comissoes
    ], JSON_UNESCAPED_UNICODE);
}

