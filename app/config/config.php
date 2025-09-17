<?php
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
    ],
];
