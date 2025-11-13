<?php
declare(strict_types=1);

require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/lib/Validator.php';
require __DIR__ . '/../../app/lib/Notification.php';
require __DIR__ . '/../../app/models/Cliente.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    
    switch ($method) {
        case 'GET':
            handleGet($user, $isAdmin);
            break;
        case 'POST':
            handlePost($user, $isAdmin);
            break;
        case 'PUT':
            handlePut($user, $isAdmin);
            break;
        case 'DELETE':
            handleDelete($user, $isAdmin);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

function handleGet(array $user, bool $isAdmin): void
{
    $pdo = Database::getConnection();
    
    // Parâmetros de filtro
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;
    
    $search = trim($_GET['search'] ?? '');
    $interesse = trim($_GET['interesse'] ?? '');
    $estado = trim($_GET['estado'] ?? '');
    $criado_por = (int)($_GET['criado_por'] ?? 0);
    
    // Construir WHERE
    $where = ["c.deleted_at IS NULL"];
    $params = [];
    
    if (!$isAdmin) {
        $where[] = "c.criado_por = :user_id";
        $params[':user_id'] = $user['id'];
    }
    
    if ($search) {
        $where[] = "(c.nome LIKE :search OR c.telefone LIKE :search OR c.cidade LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    if ($interesse) {
        $where[] = "c.interesse = :interesse";
        $params[':interesse'] = $interesse;
    }
    
    if ($estado) {
        $where[] = "c.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    if ($criado_por && $isAdmin) {
        $where[] = "c.criado_por = :criado_por";
        $params[':criado_por'] = $criado_por;
    }
    
    $whereSql = implode(' AND ', $where);
    
    // Contar total
    $countSql = "SELECT COUNT(*) FROM clientes c WHERE {$whereSql}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    
    // Buscar dados
    $sql = "
        SELECT c.id, c.nome, c.telefone, c.cidade, c.estado, c.interesse, 
               c.created_at, c.updated_at, f.nome as criado_por_nome
        FROM clientes c
        JOIN funcionarios f ON f.id = c.criado_por
        WHERE {$whereSql}
        ORDER BY c.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
    
    // Formatar telefones
    foreach ($clients as &$client) {
        $client['telefone_formatado'] = Validator::formatPhone($client['telefone']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $clients,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function handlePost(array $user, bool $isAdmin): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Validações
    $errors = [];
    
    $nome = trim($input['nome'] ?? '');
    $telefone = trim($input['telefone'] ?? '');
    $cidade = trim($input['cidade'] ?? '');
    $estado = strtoupper(substr(trim($input['estado'] ?? ''), 0, 2));
    $interesse = trim($input['interesse'] ?? '');
    
    // Validar nome
    $nomeValidation = Validator::validateFullName($nome);
    if (!$nomeValidation['valid']) {
        $errors['nome'] = $nomeValidation['message'];
    }
    
    // Validar telefone
    $telefoneValidation = Validator::validatePhone($telefone);
    if (!$telefoneValidation['valid']) {
        $errors['telefone'] = $telefoneValidation['message'];
    }
    
    if (empty($cidade)) {
        $errors['cidade'] = 'Cidade é obrigatória';
    }
    
    if (strlen($estado) !== 2) {
        $errors['estado'] = 'Estado deve ter 2 caracteres';
    }
    
    if (empty($interesse)) {
        $errors['interesse'] = 'Interesse é obrigatório';
    }
    
    if (!$errors && Cliente::isPhoneTaken($telefone)) {
        $errors['telefone'] = 'Já existe um cliente cadastrado com este telefone.';
    }

    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Criar cliente
    $clientId = Cliente::create([
        'nome' => $nome,
        'telefone' => $telefone,
        'cidade' => $cidade,
        'estado' => $estado,
        'interesse' => $interesse,
        'criado_por' => $user['id']
    ]);
    
    // Notificar administradores
    Notification::notifyNewClient($clientId, $nome, $user['id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cliente criado com sucesso',
        'data' => ['id' => $clientId]
    ], JSON_UNESCAPED_UNICODE);
}

function handlePut(array $user, bool $isAdmin): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID é obrigatório'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Buscar cliente
    $cliente = Cliente::find($id);
    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Cliente não encontrado'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Verificar permissão
    if (!$isAdmin && (int)$cliente['criado_por'] !== $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Sem permissão'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Validações (mesmo do POST)
    $errors = [];
    
    $nome = trim($input['nome'] ?? '');
    $telefone = trim($input['telefone'] ?? '');
    $cidade = trim($input['cidade'] ?? '');
    $estado = strtoupper(substr(trim($input['estado'] ?? ''), 0, 2));
    $interesse = trim($input['interesse'] ?? '');
    
    $nomeValidation = Validator::validateFullName($nome);
    if (!$nomeValidation['valid']) {
        $errors['nome'] = $nomeValidation['message'];
    }
    
    $telefoneValidation = Validator::validatePhone($telefone);
    if (!$telefoneValidation['valid']) {
        $errors['telefone'] = $telefoneValidation['message'];
    }
    
    if (empty($cidade)) {
        $errors['cidade'] = 'Cidade é obrigatória';
    }
    
    if (strlen($estado) !== 2) {
        $errors['estado'] = 'Estado deve ter 2 caracteres';
    }
    
    if (empty($interesse)) {
        $errors['interesse'] = 'Interesse é obrigatório';
    }
    
    if (!$errors && Cliente::isPhoneTaken($telefone, $id)) {
        $errors['telefone'] = 'Já existe um cliente cadastrado com este telefone.';
    }

    if ($errors) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Atualizar cliente
    Cliente::update($id, [
        'nome' => $nome,
        'telefone' => $telefone,
        'cidade' => $cidade,
        'estado' => $estado,
        'interesse' => $interesse
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cliente atualizado com sucesso'
    ], JSON_UNESCAPED_UNICODE);
}

function handleDelete(array $user, bool $isAdmin): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID é obrigatório'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Buscar cliente
    $cliente = Cliente::find($id);
    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Cliente não encontrado'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Verificar permissão
    if (!$isAdmin && (int)$cliente['criado_por'] !== $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Sem permissão'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Soft delete
    Cliente::softDelete($id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cliente excluído com sucesso'
    ], JSON_UNESCAPED_UNICODE);
}
