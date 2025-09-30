<?php
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function base_url(string $path=''): string {
    $cfg = require __DIR__ . '/../config/config.php';
    $base = rtrim($cfg['app']['base_url'] ?? '', '/');
    $path = ltrim($path, '/');
    return $base . ($path ? "/$path" : '');
}

