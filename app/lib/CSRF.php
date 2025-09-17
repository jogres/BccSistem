<?php
class CSRF {
    public static function token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    public static function field(): string {
        $t = self::token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
    }
    public static function validate(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sent = $_POST['csrf_token'] ?? '';
            $valid = hash_equals($_SESSION['csrf_token'] ?? '', $sent);
            if (!$valid) {
                http_response_code(400);
                die('CSRF token inválido.');
            }
        }
    }
}
