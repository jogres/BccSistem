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
            return true;
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
