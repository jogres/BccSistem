<?php
class Database {
    private static ?PDO $conn = null;

    public static function getConnection(): PDO {
        if (self::$conn === null) {
            $config = require __DIR__ . '/../config/config.php';
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
                $config['db']['host'],
                $config['db']['dbname'],
                $config['db']['charset']
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$conn = new PDO($dsn, $config['db']['user'], $config['db']['pass'], $options);
        }
        return self::$conn;
    }
}
