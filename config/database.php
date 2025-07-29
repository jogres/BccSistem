<?php
// config/database.php

// Definição de constantes de conexão
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'consorcio_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna uma instância PDO pronta para uso
 *
 * @return \PDO
 */
function getPDO(): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Em produção, envie este erro para um log em vez de exibir na tela
        die('Erro de conexão com o banco de dados: ' . $e->getMessage());
    }
}
?>