<?php
// scripts/health_check.php
// Verificação de saúde do sistema

require __DIR__ . '/../app/lib/Database.php';
require __DIR__ . '/../app/lib/Logger.php';

class HealthChecker {
    private $pdo;
    private $issues = [];
    private $warnings = [];
    private $ok = [];
    
    public function __construct() {
        try {
            $this->pdo = Database::getConnection();
            $this->ok[] = "✅ Conexão com banco de dados: OK";
        } catch (Exception $e) {
            $this->issues[] = "❌ Conexão com banco de dados: FALHA - " . $e->getMessage();
            Logger::error("Falha na conexão com banco de dados", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function checkDatabaseTables() {
        $requiredTables = ['funcionarios', 'roles', 'clientes', 'vendas', 'notifications'];
        
        foreach ($requiredTables as $table) {
            try {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->rowCount() > 0) {
                    $this->ok[] = "✅ Tabela '{$table}': Existe";
                } else {
                    $this->issues[] = "❌ Tabela '{$table}': Não encontrada";
                }
            } catch (Exception $e) {
                $this->issues[] = "❌ Erro ao verificar tabela '{$table}': " . $e->getMessage();
            }
        }
    }
    
    public function checkDataIntegrity() {
        // Verificar se existem funcionários ativos
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM funcionarios WHERE is_ativo = 1");
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $this->ok[] = "✅ Funcionários ativos: {$result['count']}";
            } else {
                $this->issues[] = "❌ Nenhum funcionário ativo encontrado";
            }
        } catch (Exception $e) {
            $this->issues[] = "❌ Erro ao verificar funcionários: " . $e->getMessage();
        }
        
        // Verificar roles
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM roles");
            $result = $stmt->fetch();
            
            if ($result['count'] >= 3) {
                $this->ok[] = "✅ Roles configuradas: {$result['count']}";
            } else {
                $this->warnings[] = "⚠️ Poucos roles encontrados: {$result['count']}";
            }
        } catch (Exception $e) {
            $this->issues[] = "❌ Erro ao verificar roles: " . $e->getMessage();
        }
    }
    
    public function checkFilePermissions() {
        $directories = [
            'logs' => __DIR__ . '/../logs/',
            'uploads' => __DIR__ . '/../public/uploads/',
            'uploads/contratos' => __DIR__ . '/../public/uploads/contratos/'
        ];
        
        foreach ($directories as $name => $path) {
            if (!is_dir($path)) {
                $this->issues[] = "❌ Diretório '{$name}': Não existe";
                continue;
            }
            
            if (!is_writable($path)) {
                $this->issues[] = "❌ Diretório '{$name}': Sem permissão de escrita";
            } else {
                $this->ok[] = "✅ Diretório '{$name}': OK";
            }
        }
    }
    
    public function checkDiskSpace() {
        $freeBytes = disk_free_space(__DIR__ . '/../');
        $totalBytes = disk_total_space(__DIR__ . '/../');
        
        if ($freeBytes && $totalBytes) {
            $freePercent = ($freeBytes / $totalBytes) * 100;
            
            if ($freePercent > 20) {
                $this->ok[] = "✅ Espaço em disco: " . round($freePercent, 1) . "% livre";
            } elseif ($freePercent > 10) {
                $this->warnings[] = "⚠️ Espaço em disco baixo: " . round($freePercent, 1) . "% livre";
            } else {
                $this->issues[] = "❌ Espaço em disco crítico: " . round($freePercent, 1) . "% livre";
            }
        }
    }
    
    public function checkLogFiles() {
        $logDir = __DIR__ . '/../logs/';
        
        if (!is_dir($logDir)) {
            $this->warnings[] = "⚠️ Diretório de logs não existe";
            return;
        }
        
        $logFiles = glob($logDir . '*.log*');
        $totalSize = 0;
        
        foreach ($logFiles as $file) {
            $totalSize += filesize($file);
        }
        
        $totalSizeMB = round($totalSize / 1024 / 1024, 2);
        
        if ($totalSizeMB < 100) {
            $this->ok[] = "✅ Tamanho dos logs: {$totalSizeMB}MB";
        } elseif ($totalSizeMB < 500) {
            $this->warnings[] = "⚠️ Logs ocupando muito espaço: {$totalSizeMB}MB";
        } else {
            $this->issues[] = "❌ Logs ocupando espaço excessivo: {$totalSizeMB}MB";
        }
    }
    
    public function checkRecentErrors() {
        try {
            $logs = Logger::search('ERROR', date('Y-m-d'), null, 10);
            
            if (count($logs) == 0) {
                $this->ok[] = "✅ Nenhum erro registrado hoje";
            } elseif (count($logs) < 5) {
                $this->ok[] = "✅ Poucos erros hoje: " . count($logs);
            } elseif (count($logs) < 20) {
                $this->warnings[] = "⚠️ Muitos erros hoje: " . count($logs);
            } else {
                $this->issues[] = "❌ Número excessivo de erros hoje: " . count($logs);
            }
        } catch (Exception $e) {
            $this->warnings[] = "⚠️ Não foi possível verificar erros recentes: " . $e->getMessage();
        }
    }
    
    public function runAllChecks() {
        $this->checkDatabaseTables();
        $this->checkDataIntegrity();
        $this->checkFilePermissions();
        $this->checkDiskSpace();
        $this->checkLogFiles();
        $this->checkRecentErrors();
        
        // Log do resultado
        $status = empty($this->issues) ? 'OK' : 'PROBLEMAS';
        Logger::info("Health check executado", [
            'status' => $status,
            'issues' => count($this->issues),
            'warnings' => count($this->warnings),
            'ok' => count($this->ok)
        ]);
    }
    
    public function getReport() {
        return [
            'issues' => $this->issues,
            'warnings' => $this->warnings,
            'ok' => $this->ok,
            'status' => empty($this->issues) ? 'healthy' : 'unhealthy'
        ];
    }
}

// Executar verificação
$checker = new HealthChecker();
$checker->runAllChecks();
$report = $checker->getReport();

// Output baseado no contexto
$isCLI = php_sapi_name() === 'cli';

if ($isCLI) {
    echo "🏥 VERIFICAÇÃO DE SAÚDE DO SISTEMA\n";
    echo "==================================\n\n";
    
    if (!empty($report['ok'])) {
        echo "✅ ITENS OK:\n";
        foreach ($report['ok'] as $item) {
            echo "   {$item}\n";
        }
        echo "\n";
    }
    
    if (!empty($report['warnings'])) {
        echo "⚠️ AVISOS:\n";
        foreach ($report['warnings'] as $item) {
            echo "   {$item}\n";
        }
        echo "\n";
    }
    
    if (!empty($report['issues'])) {
        echo "❌ PROBLEMAS:\n";
        foreach ($report['issues'] as $item) {
            echo "   {$item}\n";
        }
        echo "\n";
    }
    
    echo "STATUS GERAL: " . strtoupper($report['status']) . "\n";
    echo "Exit code: " . ($report['status'] === 'healthy' ? 0 : 1) . "\n";
    
    exit($report['status'] === 'healthy' ? 0 : 1);
} else {
    // Para uso web
    header('Content-Type: application/json');
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
