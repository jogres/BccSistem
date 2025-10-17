<?php
// app/lib/Logger.php
// Sistema de logging para erros e ações do BCC Sistema

class Logger {
    private static $logDir;
    private static $maxLogSize = 10485760; // 10MB
    private static $maxLogFiles = 5;
    
    public static function init() {
        // Configurar fuso horário para Brasil (Brasília)
        date_default_timezone_set('America/Sao_Paulo');
        
        // Configurar codificação UTF-8
        mb_internal_encoding('UTF-8');
        ini_set('default_charset', 'UTF-8');
        
        self::$logDir = __DIR__ . '/../../logs/';
        
        // Criar diretório de logs se não existir
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
        
        // Configurar PHP para usar nosso sistema de log
        ini_set('log_errors', 1);
        ini_set('error_log', self::$logDir . 'php_errors.log');
    }
    
    /**
     * Log de erro do sistema
     */
    public static function error($message, $context = [], $file = null, $line = null) {
        self::writeLog('ERROR', $message, $context, $file, $line);
    }
    
    /**
     * Log de aviso do sistema
     */
    public static function warning($message, $context = []) {
        self::writeLog('WARNING', $message, $context);
    }
    
    /**
     * Log de informação
     */
    public static function info($message, $context = []) {
        self::writeLog('INFO', $message, $context);
    }
    
    /**
     * Log de ação do usuário
     */
    public static function action($action, $userId = null, $details = []) {
        $context = array_merge([
            'user_id' => $userId,
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ], $details);
        
        self::writeLog('ACTION', $action, $context);
    }
    
    /**
     * Log de segurança
     */
    public static function security($message, $context = []) {
        $context['ip'] = self::getClientIP();
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        self::writeLog('SECURITY', $message, $context);
    }
    
    /**
     * Log de acesso negado
     */
    public static function accessDenied($resource, $userId = null, $reason = '') {
        self::security("Acesso negado a {$resource}", [
            'user_id' => $userId,
            'reason' => $reason,
            'resource' => $resource
        ]);
    }
    
    /**
     * Log de login
     */
    public static function login($success, $login, $userId = null, $reason = '') {
        $level = $success ? 'INFO' : 'WARNING';
        $message = $success ? "Login bem-sucedido" : "Tentativa de login falhada";
        
        self::writeLog($level, $message, [
            'login' => $login,
            'user_id' => $userId,
            'reason' => $reason,
            'ip' => self::getClientIP()
        ]);
    }
    
    /**
     * Log de operação CRUD
     */
    public static function crud($operation, $table, $recordId = null, $userId = null, $data = []) {
        self::action("CRUD: {$operation} em {$table}", $userId, [
            'operation' => $operation,
            'table' => $table,
            'record_id' => $recordId,
            'data' => $data
        ]);
    }
    
    /**
     * Escrever no arquivo de log
     */
    private static function writeLog($level, $message, $context = [], $file = null, $line = null) {
        $timestamp = date('Y-m-d H:i:s');
        $userId = $context['user_id'] ?? 'system';
        
        // Formatar contexto
        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        // Informações adicionais para erros
        $extra = '';
        if ($file && $line) {
            $extra = " | {$file}:{$line}";
        }
        
        // Garantir UTF-8 na mensagem (detectar e converter automaticamente)
        $encoding = mb_detect_encoding($message, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $message = mb_convert_encoding($message, 'UTF-8', $encoding);
        }
        
        $logEntry = "[{$timestamp}] [{$level}] [{$userId}] {$message}{$contextStr}{$extra}" . PHP_EOL;
        
        // Determinar arquivo de log baseado no nível
        $logFile = self::getLogFile($level);
        
        // Verificar tamanho do arquivo e rotacionar se necessário
        self::rotateLog($logFile);
        
        // Escrever no arquivo com UTF-8
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Obter arquivo de log baseado no nível
     */
    private static function getLogFile($level, $date = null) {
        $date = $date ?: date('Y-m-d');
        
        switch ($level) {
            case 'ERROR':
                return self::$logDir . "errors_{$date}.log";
            case 'WARNING':
                return self::$logDir . "warnings_{$date}.log";
            case 'SECURITY':
                return self::$logDir . "security_{$date}.log";
            case 'ACTION':
                return self::$logDir . "actions_{$date}.log";
            default:
                return self::$logDir . "system_{$date}.log";
        }
    }
    
    /**
     * Rotacionar arquivo de log se necessário
     */
    private static function rotateLog($logFile) {
        if (!file_exists($logFile)) {
            return;
        }
        
        if (filesize($logFile) > self::$maxLogSize) {
            // Rotacionar arquivos
            for ($i = self::$maxLogFiles - 1; $i > 0; $i--) {
                $oldFile = $logFile . '.' . $i;
                $newFile = $logFile . '.' . ($i + 1);
                
                if (file_exists($oldFile)) {
                    if ($i === self::$maxLogFiles - 1) {
                        unlink($oldFile); // Remover arquivo mais antigo
                    } else {
                        rename($oldFile, $newFile);
                    }
                }
            }
            
            // Mover arquivo atual
            rename($logFile, $logFile . '.1');
        }
    }
    
    /**
     * Obter IP do cliente
     */
    private static function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Buscar logs por critérios
     */
    public static function search($level = null, $date = null, $userId = null, $limit = 100) {
        $logs = [];
        $date = $date ?: date('Y-m-d');
        
        $logFiles = [];
        if ($level) {
            $logFiles[] = self::getLogFile($level, $date);
        } else {
            $logFiles = [
                self::getLogFile('ERROR', $date),
                self::getLogFile('WARNING', $date),
                self::getLogFile('INFO', $date),
                self::getLogFile('SECURITY', $date),
                self::getLogFile('ACTION', $date)
            ];
        }
        
        foreach ($logFiles as $logFile) {
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                foreach ($lines as $line) {
                    $log = self::parseLogLine($line);
                    if ($log) {
                        if ($userId && $log['user_id'] != $userId) {
                            continue;
                        }
                        $logs[] = $log;
                    }
                }
            }
        }
        
        // Ordenar por timestamp (mais recente primeiro)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($logs, 0, $limit);
    }
    
    /**
     * Parsear linha de log
     */
    private static function parseLogLine($line) {
        // Formato: [timestamp] [level] [user_id] message | context
        $pattern = '/^\[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] (.+?)(?:\s*\|\s*(.+))?$/';
        
        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'user_id' => $matches[3],
                'message' => $matches[4],
                'context' => isset($matches[5]) ? json_decode($matches[5], true) : []
            ];
        }
        
        return null;
    }
    
    /**
     * Limpar logs antigos
     */
    public static function cleanup($days = 30) {
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
        $deleted = 0;
        
        $files = glob(self::$logDir . '*.log*');
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/(\d{4}-\d{2}-\d{2})/', $filename, $matches)) {
                if ($matches[1] < $cutoffDate) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        self::info("Limpeza de logs: {$deleted} arquivos removidos");
        return $deleted;
    }
    
    /**
     * Estatísticas dos logs
     */
    public static function getStats($date = null) {
        $date = $date ?: date('Y-m-d');
        $stats = [
            'errors' => 0,
            'warnings' => 0,
            'info' => 0,
            'security' => 0,
            'actions' => 0
        ];
        
        $logFiles = [
            'errors' => self::getLogFile('ERROR', $date),
            'warnings' => self::getLogFile('WARNING', $date),
            'info' => self::getLogFile('INFO', $date),
            'security' => self::getLogFile('SECURITY', $date),
            'actions' => self::getLogFile('ACTION', $date)
        ];
        
        foreach ($logFiles as $type => $file) {
            if (file_exists($file)) {
                $stats[$type] = count(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
            }
        }
        
        return $stats;
    }
}

// Inicializar o sistema de log
Logger::init();
?>
