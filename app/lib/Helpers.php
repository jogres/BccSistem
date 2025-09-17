<?php
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}
function base_url(string $path = ''): string {
    $config = require __DIR__ . '/../config/config.php';
    $base = rtrim($config['app']['base_url'] ?? '', '/');
    return $base . '/' . ltrim($path, '/');
}
