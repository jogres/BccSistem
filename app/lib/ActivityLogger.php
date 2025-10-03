<?php
class ActivityLogger
{
    /**
     * Log de atividade do usuário
     */
    public static function log(string $action, string $description, ?int $userId = null): void
    {
        $pdo = Database::getConnection();
        
        // Se não especificado, pega do usuário logado
        if ($userId === null) {
            $user = Auth::user();
            $userId = $user ? $user['id'] : null;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
            VALUES (:user_id, :action, :description, :ip, :user_agent)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    /**
     * Log de login
     */
    public static function logLogin(int $userId, bool $success): void
    {
        $action = $success ? 'login_success' : 'login_failed';
        $description = $success ? 'Login realizado com sucesso' : 'Tentativa de login falhou';
        
        self::log($action, $description, $userId);
        
        if ($success) {
            // Atualiza último login
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                UPDATE funcionarios 
                SET last_login_at = NOW(), last_login_ip = :ip, failed_login_attempts = 0
                WHERE id = :user_id
            ");
            $stmt->execute([
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ':user_id' => $userId
            ]);
        } else {
            // Incrementa tentativas falhadas
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                UPDATE funcionarios 
                SET failed_login_attempts = failed_login_attempts + 1
                WHERE id = :user_id
            ");
            $stmt->execute([':user_id' => $userId]);
        }
    }
    
    /**
     * Log de logout
     */
    public static function logLogout(?int $userId = null): void
    {
        self::log('logout', 'Logout realizado', $userId);
    }
    
    /**
     * Log de criação de cliente
     */
    public static function logClientCreated(int $clientId, string $clientName): void
    {
        self::log('client_created', "Cliente '{$clientName}' (ID: {$clientId}) criado");
    }
    
    /**
     * Log de atualização de cliente
     */
    public static function logClientUpdated(int $clientId, string $clientName): void
    {
        self::log('client_updated', "Cliente '{$clientName}' (ID: {$clientId}) atualizado");
    }
    
    /**
     * Log de exclusão de cliente
     */
    public static function logClientDeleted(int $clientId, string $clientName): void
    {
        self::log('client_deleted', "Cliente '{$clientName}' (ID: {$clientId}) excluído");
    }
    
    /**
     * Log de criação de funcionário
     */
    public static function logUserCreated(int $userId, string $userName): void
    {
        self::log('user_created', "Funcionário '{$userName}' (ID: {$userId}) criado");
    }
    
    /**
     * Log de atualização de funcionário
     */
    public static function logUserUpdated(int $userId, string $userName): void
    {
        self::log('user_updated', "Funcionário '{$userName}' (ID: {$userId}) atualizado");
    }
    
    /**
     * Log de inativação de funcionário
     */
    public static function logUserDeactivated(int $userId, string $userName): void
    {
        self::log('user_deactivated', "Funcionário '{$userName}' (ID: {$userId}) inativado");
    }
    
    /**
     * Log de alteração de senha
     */
    public static function logPasswordChanged(int $userId): void
    {
        self::log('password_changed', 'Senha alterada', $userId);
    }
    
    /**
     * Log de reset de senha
     */
    public static function logPasswordReset(int $userId): void
    {
        self::log('password_reset', 'Senha redefinida via email', $userId);
    }
    
    /**
     * Log de acesso a área administrativa
     */
    public static function logAdminAccess(string $area): void
    {
        self::log('admin_access', "Acesso à área administrativa: {$area}");
    }
    
    /**
     * Log de exportação de dados
     */
    public static function logDataExport(string $type, int $recordCount): void
    {
        self::log('data_export', "Exportação de {$type}: {$recordCount} registros");
    }
    
    /**
     * Log de backup
     */
    public static function logBackup(string $action, bool $success, ?string $details = null): void
    {
        $description = "Backup {$action} " . ($success ? 'realizado com sucesso' : 'falhou');
        if ($details) {
            $description .= ": {$details}";
        }
        
        self::log('backup_' . $action, $description);
    }
    
    /**
     * Log de configuração alterada
     */
    public static function logConfigChanged(string $setting, string $oldValue, string $newValue): void
    {
        self::log('config_changed', "Configuração '{$setting}' alterada de '{$oldValue}' para '{$newValue}'");
    }
    
    /**
     * Log de erro do sistema
     */
    public static function logSystemError(string $error, string $file, int $line): void
    {
        self::log('system_error', "Erro em {$file}:{$line} - {$error}");
    }
    
    /**
     * Busca logs de atividade
     */
    public static function getLogs(int $limit = 100, ?string $action = null, ?int $userId = null): array
    {
        $pdo = Database::getConnection();
        
        $where = [];
        $params = [];
        
        if ($action) {
            $where[] = "action = :action";
            $params[':action'] = $action;
        }
        
        if ($userId) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "
            SELECT al.*, f.nome as user_name
            FROM activity_logs al
            LEFT JOIN funcionarios f ON f.id = al.user_id
            {$whereSql}
            ORDER BY al.created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Estatísticas de atividade
     */
    public static function getActivityStats(int $days = 30): array
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_actions,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(CASE WHEN action LIKE 'login%' THEN 1 ELSE 0 END) as logins,
                SUM(CASE WHEN action LIKE 'client_%' THEN 1 ELSE 0 END) as client_actions,
                SUM(CASE WHEN action LIKE 'user_%' THEN 1 ELSE 0 END) as user_actions
            FROM activity_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        
        $stmt->execute([':days' => $days]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Limpa logs antigos
     */
    public static function cleanupOldLogs(int $days = 90): int
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            DELETE FROM activity_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        
        $stmt->execute([':days' => $days]);
        
        return $stmt->rowCount();
    }
}
