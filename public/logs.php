<?php
// public/logs.php
// Interface para visualizar logs do sistema

require __DIR__ . '/../app/lib/Database.php';
require __DIR__ . '/../app/lib/Auth.php';
require __DIR__ . '/../app/lib/Helpers.php';

// Iniciar sessão
Auth::startSessionSecure();

// Verificar se está logado
if (!Auth::check()) {
    $_SESSION['error'] = 'Você precisa estar logado para acessar esta página.';
    header('Location: login.php');
    exit;
}

// Verificar se é administrador
if (!Auth::isAdmin()) {
    $_SESSION['error'] = 'Acesso negado. Apenas administradores podem visualizar os logs do sistema.';
    header('Location: dashboard.php');
    exit;
}

require __DIR__ . '/../app/lib/Logger.php';
require __DIR__ . '/../app/lib/ErrorHandler.php';

$user = Auth::user();

// Parâmetros de busca
$level = $_GET['level'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');
$userId = $_GET['user_id'] ?? '';
$limit = min(500, max(10, (int)($_GET['limit'] ?? 100)));

// Buscar logs
$logs = [];
$stats = [
    'errors' => 0,
    'warnings' => 0,
    'info' => 0,
    'security' => 0,
    'actions' => 0
];
$funcionarios = [];

try {
    $logs = Logger::search($level, $date, $userId ?: null, $limit);
    $stats = Logger::getStats($date);
    
    // Buscar usuários para filtro
    $pdo = Database::getConnection();
    $stmt = $pdo->query("SELECT id, nome FROM funcionarios WHERE is_ativo = 1 ORDER BY nome");
    $funcionarios = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erro em logs.php: " . $e->getMessage());
    $_SESSION['warning'] = "Erro ao carregar alguns dados: " . $e->getMessage();
}

include __DIR__ . '/../app/views/partials/header.php';
?>

<div class="main-container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <strong>✅</strong> <?= e($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <strong>❌</strong> <?= e($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning">
            <strong>⚠️</strong> <?= e($_SESSION['warning']) ?>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">📋 Logs do Sistema</h1>
        <p class="page-subtitle">Monitoramento de eventos e erros do sistema</p>
    </div>

    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">❌</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['errors'] ?></div>
                <div class="stat-label">Erros</div>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['warnings'] ?></div>
                <div class="stat-label">Avisos</div>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">ℹ️</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['info'] ?></div>
                <div class="stat-label">Informações</div>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon">🔒</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['security'] ?></div>
                <div class="stat-label">Segurança</div>
            </div>
        </div>
        
        <div class="stat-card stat-secondary">
            <div class="stat-icon">⚡</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['actions'] ?></div>
                <div class="stat-label">Ações</div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="form-container">
        <form method="get" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">📅 Data</label>
                    <input type="date" name="date" value="<?= e($date) ?>" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">📊 Nível</label>
                    <select name="level" class="form-control">
                        <option value="">Todos</option>
                        <option value="ERROR" <?= $level === 'ERROR' ? 'selected' : '' ?>>❌ Erros</option>
                        <option value="WARNING" <?= $level === 'WARNING' ? 'selected' : '' ?>>⚠️ Avisos</option>
                        <option value="INFO" <?= $level === 'INFO' ? 'selected' : '' ?>>ℹ️ Informações</option>
                        <option value="SECURITY" <?= $level === 'SECURITY' ? 'selected' : '' ?>>🔒 Segurança</option>
                        <option value="ACTION" <?= $level === 'ACTION' ? 'selected' : '' ?>>⚡ Ações</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">👤 Usuário</label>
                    <select name="user_id" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($funcionarios as $func): ?>
                            <option value="<?= $func['id'] ?>" <?= $userId == $func['id'] ? 'selected' : '' ?>>
                                <?= e($func['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">📄 Limite</label>
                    <select name="limit" class="form-control">
                        <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50 registros</option>
                        <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100 registros</option>
                        <option value="200" <?= $limit === 200 ? 'selected' : '' ?>>200 registros</option>
                        <option value="500" <?= $limit === 500 ? 'selected' : '' ?>>500 registros</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-primary">🔍 Filtrar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Lista de Logs -->
    <div class="table-container">
        <div class="table-header">
            <h3>📋 Registros de Log</h3>
            <p>Exibindo <?= count($logs) ?> registros de <?= date('d/m/Y', strtotime($date)) ?></p>
        </div>
        
        <?php if (empty($logs)): ?>
            <div style="padding: 40px; text-align: center; color: #666;">
                <p>📭 Nenhum registro encontrado para os filtros selecionados.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>⏰ Data/Hora</th>
                            <th>📊 Nível</th>
                            <th>👤 Usuário</th>
                            <th>💬 Mensagem</th>
                            <th>📄 Contexto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <small><?= date('d/m/Y H:i:s', strtotime($log['timestamp'])) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $levelColors = [
                                        'ERROR' => 'background: #f8d7da; color: #721c24;',
                                        'WARNING' => 'background: #fff3cd; color: #856404;',
                                        'INFO' => 'background: #d1ecf1; color: #0c5460;',
                                        'SECURITY' => 'background: #e2e3e5; color: #383d41;',
                                        'ACTION' => 'background: #d4edda; color: #155724;'
                                    ];
                                    $style = $levelColors[$log['level']] ?? '';
                                    ?>
                                    <span style="<?= $style ?> padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">
                                        <?= $log['level'] ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= e($log['user_id']) ?></small>
                                </td>
                                <td>
                                    <div style="max-width: 400px; word-wrap: break-word;">
                                        <?= e($log['message']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($log['context'])): ?>
                                        <details>
                                            <summary style="cursor: pointer; color: #007bff;">Ver detalhes</summary>
                                            <pre style="font-size: 0.8em; background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px; max-height: 200px; overflow-y: auto;"><?= htmlspecialchars(json_encode($log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                        </details>
                                    <?php else: ?>
                                        <small style="color: #999;">-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Ações -->
    <div class="form-container">
        <div class="form-actions">
            <a href="?<?= http_build_query(array_merge($_GET, ['date' => date('Y-m-d')])) ?>" class="btn-secondary">
                📅 Hoje
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['date' => date('Y-m-d', strtotime('-1 day'))])) ?>" class="btn-secondary">
                📅 Ontem
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['level' => 'ERROR'])) ?>" class="btn-warning">
                ❌ Só Erros
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['level' => 'SECURITY'])) ?>" class="btn-info">
                🔒 Segurança
            </a>
            <button onclick="location.reload()" class="btn-primary">
                🔄 Atualizar
            </button>
        </div>
    </div>
</div>

<style>
.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin: 16px 0;
    border: 1px solid transparent;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.stat-card {
    text-align: center;
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 8px;
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #e0a800, #d39e00);
    color: white;
    text-decoration: none;
}

.btn-info {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
}

.btn-info:hover {
    background: linear-gradient(135deg, #138496, #117a8b);
    color: white;
    text-decoration: none;
}
</style>

<?php include __DIR__ . '/../app/views/partials/footer.php'; ?>
