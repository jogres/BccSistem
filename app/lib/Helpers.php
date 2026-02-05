<?php

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(string $path = ''): string {
    $cfg = require __DIR__ . '/../config/config.php';
    $base = rtrim($cfg['app']['base_url'] ?? '', '/');
    $path = ltrim($path, '/');
    return $base . ($path ? "/$path" : '');
}

/**
 * Formata CPF no padrão 000.000.000-00
 */
function formatCpf(?string $value): string {
    $digits = preg_replace('/\D+/', '', (string)$value);

    if (strlen($digits) !== 11) {
        return (string)$value;
    }

    return substr($digits, 0, 3) . '.' .
           substr($digits, 3, 3) . '.' .
           substr($digits, 6, 3) . '-' .
           substr($digits, 9, 2);
}

/**
 * Formata telefone brasileiro (10 ou 11 dígitos)
 * 10 dígitos: (00) 0000-0000
 * 11 dígitos: (00) 00000-0000
 */
function formatTelefone(?string $value): string {
    $digits = preg_replace('/\D+/', '', (string)$value);
    $len = strlen($digits);

    if ($len === 10) {
        return sprintf('(%s) %s-%s',
            substr($digits, 0, 2),
            substr($digits, 2, 4),
            substr($digits, 6, 4)
        );
    }

    if ($len === 11) {
        return sprintf('(%s) %s-%s',
            substr($digits, 0, 2),
            substr($digits, 2, 5),
            substr($digits, 7, 4)
        );
    }

    return (string)$value;
}

/**
 * Formata CEP no padrão 00000-000
 */
function formatCep(?string $value): string {
    $digits = preg_replace('/\D+/', '', (string)$value);

    if (strlen($digits) !== 8) {
        return (string)$value;
    }

    return substr($digits, 0, 5) . '-' . substr($digits, 5, 3);
}

