<?php
final class Database {
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO {
        if (self::$pdo instanceof PDO) return self::$pdo;

        $cfg = require __DIR__ . '/../config/config.php';
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
            $cfg['db']['host'], $cfg['db']['dbname'], $cfg['db']['charset'] ?? 'utf8mb4'
        );

        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // usa prepare nativo (evita mapeamentos esquisitos)
            PDO::ATTR_PERSISTENT         => false,   // sem conexão persistente
        ];

        self::$pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], $opt);
        return self::$pdo;
    }
}
