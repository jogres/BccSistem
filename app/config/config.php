<?php
// Configurar fuso horário para o Brasil (Brasília)
date_default_timezone_set('America/Sao_Paulo');

// Configurar codificação UTF-8 para caracteres especiais
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=UTF-8');

return [
    'db' => [
        'host'    => '127.0.0.1',
        'dbname'  => 'bcc',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    // Ajuste se publicar em subpasta (ex.: '/bcc-php-app/public')
    'app' => [
        'base_url' => '/BccSistem/public', // vazio = relativo
        'timezone' => 'America/Sao_Paulo', // Fuso horário do Brasil (UTC-3)
    ],
];
