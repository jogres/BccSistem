<?php
require_once __DIR__ . '/Database.php';

class Notification
{
    /**
     * Tipos de notificação
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    
    /**
     * Cria uma notificação
     */
    public static function create(int $userId, string $title, string $message, string $type = self::TYPE_INFO, ?string $actionUrl = null): int
    {
        try {
            $pdo = Database::getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, action_url, created_at)
                VALUES (:user_id, :title, :message, :type, :action_url, NOW())
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':title' => $title,
                ':message' => $message,
                ':type' => $type,
                ':action_url' => $actionUrl
            ]);
            
            return (int)$pdo->lastInsertId();
        } catch (Throwable $e) {
            // Log erro silenciosamente para não quebrar o fluxo
            error_log("Erro ao criar notificação: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Marca notificação como lida
     */
    public static function markAsRead(int $notificationId, int $userId): bool
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET read_at = NOW() 
            WHERE id = :id AND user_id = :user_id AND read_at IS NULL
        ");
        
        $stmt->execute([
            ':id' => $notificationId,
            ':user_id' => $userId
        ]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Marca todas as notificações como lidas
     */
    public static function markAllAsRead(int $userId): int
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET read_at = NOW() 
            WHERE user_id = :user_id AND read_at IS NULL
        ");
        
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Busca notificações do usuário
     */
    public static function getUserNotifications(int $userId, int $limit = 20, bool $unreadOnly = false): array
    {
        $pdo = Database::getConnection();
        
        $where = "WHERE user_id = :user_id";
        $params = [':user_id' => $userId];
        
        if ($unreadOnly) {
            $where .= " AND read_at IS NULL";
        }
        
        $sql = "
            SELECT id, title, message, type, action_url, created_at, read_at
            FROM notifications 
            {$where}
            ORDER BY created_at DESC 
            LIMIT :limit
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        // Adicionar outros parâmetros se existirem
        foreach ($params as $key => $value) {
            if ($key !== ':user_id') { // user_id já foi bindado
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Conta notificações não lidas
     */
    public static function getUnreadCount(int $userId): int
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM notifications 
            WHERE user_id = :user_id AND read_at IS NULL
        ");
        
        $stmt->execute([':user_id' => $userId]);
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Remove notificações antigas (mais de 30 dias)
     */
    public static function cleanupOld(): int
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Notifica administradores sobre novo cliente
     */
    public static function notifyNewClient(int $clientId, string $clientName, int $createdBy): void
    {
        $pdo = Database::getConnection();
        
        // Buscar nome do funcionário que criou o cliente
        $stmtCreator = $pdo->prepare("SELECT nome FROM funcionarios WHERE id = :id");
        $stmtCreator->execute([':id' => $createdBy]);
        $creatorName = $stmtCreator->fetchColumn();
        
        // Fallback se não encontrar o funcionário
        if (!$creatorName) {
            $creatorName = "Funcionário ID {$createdBy}";
        }
        
        // Busca todos os administradores
        $stmt = $pdo->prepare("
            SELECT id FROM funcionarios 
            WHERE role_id = 1 AND is_ativo = 1 AND id != :created_by
        ");
        $stmt->execute([':created_by' => $createdBy]);
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            self::create(
                $admin['id'],
                'Novo Cliente Cadastrado',
                "Cliente '{$clientName}' foi cadastrado por {$creatorName}",
                self::TYPE_INFO,
                base_url("clientes/edit.php?id={$clientId}")
            );
        }
    }
    
    /**
     * Notifica sobre funcionário inativado
     */
    public static function notifyInactiveUser(int $userId, string $userName, int $inactivatedBy): void
    {
        $pdo = Database::getConnection();
        
        // Buscar nome do funcionário que inativou
        $stmtInactivator = $pdo->prepare("SELECT nome FROM funcionarios WHERE id = :id");
        $stmtInactivator->execute([':id' => $inactivatedBy]);
        $inactivatorName = $stmtInactivator->fetchColumn();
        
        // Fallback se não encontrar o funcionário
        if (!$inactivatorName) {
            $inactivatorName = "Funcionário ID {$inactivatedBy}";
        }
        
        // Busca todos os administradores
        $stmt = $pdo->prepare("
            SELECT id FROM funcionarios 
            WHERE role_id = 1 AND is_ativo = 1
        ");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            self::create(
                $admin['id'],
                'Funcionário Inativado',
                "Funcionário '{$userName}' foi inativado por {$inactivatorName}",
                self::TYPE_WARNING,
                base_url("funcionarios/edit.php?id={$userId}")
            );
        }
    }
    
    /**
     * Notifica sobre tentativa de login suspeita
     */
    public static function notifySuspiciousLogin(string $login, string $ip): void
    {
        $pdo = Database::getConnection();
        
        // Busca todos os administradores
        $stmt = $pdo->prepare("
            SELECT id FROM funcionarios 
            WHERE role_id = 1 AND is_ativo = 1
        ");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            self::create(
                $admin['id'],
                'Tentativa de Login Suspeita',
                "Múltiplas tentativas de login falharam para '{$login}' do IP {$ip}",
                self::TYPE_ERROR,
                base_url("admin/security_logs.php")
            );
        }
    }
}
