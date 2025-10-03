<?php
class Auth
{
    /**
     * Inicia a sessão com cookies seguros.
     * Define os parâmetros APENAS se a sessão ainda não estiver ativa,
     * evitando o warning "session_set_cookie_params(): cannot change..."
     */
    public static function startSessionSecure(): void
    {
        // Detecta HTTPS simples
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
              || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Endurece a sessão contra fixation
            ini_set('session.use_strict_mode', '1');

            // PHP ≥ 7.3: assinatura com array (inclui SameSite)
            if (PHP_VERSION_ID >= 70300) {
                session_set_cookie_params([
                    'lifetime' => 0,        // cookie de sessão
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => $https,   // só envia via HTTPS
                    'httponly' => true,     // inacessível ao JS
                    'samesite' => 'Lax',    // mitiga CSRF
                ]);
            } else {
                // PHP < 7.3: definir via ini_* antes de session_start()
                ini_set('session.cookie_lifetime', '0');
                ini_set('session.cookie_path', '/');
                ini_set('session.cookie_domain', '');
                ini_set('session.cookie_secure', $https ? '1' : '0');
                ini_set('session.cookie_httponly', '1');
                // SameSite não é suportado nativamente antes do 7.3
            }

            session_start(); // cria/retoma a sessão
        }
        // Se já estiver ativa, não alteramos parâmetros (evita o warning)
    }

    /**
     * Autentica usuário e coloca dados essenciais na sessão.
     */
    public static function login(string $login, string $password): bool
    {
        $pdo = Database::getConnection();
        $sql = "SELECT f.*, r.nome AS role_name
                  FROM funcionarios f
                  JOIN roles r ON r.id = f.role_id
                 WHERE f.login = :login AND f.is_ativo = 1
                 LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();

        // Verifica a senha com password_verify (recomendado pela doc)
        if ($user && password_verify($password, $user['senha_hash'])) {
            // Previne fixation
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'        => (int)$user['id'],
                'nome'      => $user['nome'],
                'login'     => $user['login'],
                'role_id'   => (int)$user['role_id'],
                'role_name' => $user['role_name'],
            ];
            
            // Registrar login bem-sucedido
            self::recordLoginAttempt($login, $_SERVER['REMOTE_ADDR'] ?? '', true);
            
            return true;
        } else {
            // Registrar tentativa falhada
            self::recordLoginAttempt($login, $_SERVER['REMOTE_ADDR'] ?? '', false);
        }
        return false;
    }

    /**
     * Encerra a sessão e expira o cookie.
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Limpa dados
            $_SESSION = [];

            // Expira o cookie de sessão, se existir
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();
        }
    }
    
    /**
     * Registra tentativa de login (sucesso ou falha)
     */
    private static function recordLoginAttempt(string $login, string $ip, bool $success): void
    {
        try {
            $pdo = Database::getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO login_attempts (login, ip, attempted_at, success) 
                VALUES (?, ?, NOW(), ?)
            ");
            $stmt->execute([$login, $ip, $success ? 1 : 0]);
            
            // Se falhou, verificar se deve notificar sobre login suspeito
            if (!$success) {
                self::checkSuspiciousLogin($login, $ip);
            }
            
        } catch (Exception $e) {
            // Falha silenciosa para não afetar o login
            error_log("Erro ao registrar tentativa de login: " . $e->getMessage());
        }
    }
    
    /**
     * Verifica se há tentativas suspeitas e notifica administradores
     */
    private static function checkSuspiciousLogin(string $login, string $ip): void
    {
        try {
            $pdo = Database::getConnection();
            
            // Contar tentativas falhadas nos últimos 15 minutos
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM login_attempts 
                WHERE login = ? AND ip = ? AND success = 0 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute([$login, $ip]);
            $failedAttempts = $stmt->fetchColumn();
            
            // Se 3 ou mais tentativas falhadas, notificar
            if ($failedAttempts >= 3) {
                // Verificar se já foi notificado recentemente (evitar spam)
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM notifications n
                    JOIN funcionarios f ON n.user_id = f.id
                    WHERE f.role_id = 1 AND n.title = 'Tentativa de Login Suspeita'
                    AND n.message LIKE ? AND n.created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ");
                $stmt->execute(["%{$login}% do IP {$ip}%"]);
                $recentNotifications = $stmt->fetchColumn();
                
                // Só notificar se não foi notificado nos últimos 5 minutos
                if ($recentNotifications == 0) {
                    require_once __DIR__ . '/Notification.php';
                    Notification::notifySuspiciousLogin($login, $ip);
                }
            }
            
        } catch (Exception $e) {
            // Falha silenciosa
            error_log("Erro ao verificar login suspeito: " . $e->getMessage());
        }
    }
    
    /**
     * Limpa tentativas antigas de login (mais de 24 horas)
     */
    public static function cleanupLoginAttempts(): int
    {
        try {
            $pdo = Database::getConnection();
            
            $stmt = $pdo->prepare("
                DELETE FROM login_attempts 
                WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Erro ao limpar tentativas de login: " . $e->getMessage());
            return 0;
        }
    }
    
    /** Retorna o usuário logado (ou null). */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /** Há usuário autenticado? */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** Helpers de papel (RBAC simples) */
    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u && strtoupper($u['role_name']) === 'ADMIN';
    }

    public static function isPadrao(): bool
    {
        $u = self::user();
        return $u && strtoupper($u['role_name']) === 'PADRAO';
    }

    public static function isAprendiz(): bool
    {
        $u = self::user();
        return $u && strtoupper($u['role_name']) === 'APRENDIZ';
    }
}
