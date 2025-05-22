<?php
// config/db.php - Conexão PDO centralizada
$dsn = 'mysql:host=localhost;dbname=bcc;charset=utf8';
$dbUser = 'root';
$dbPass = '';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Exceções em erros SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associativo por padrão
];
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Em ambiente de produção, ideal logar o erro ao invés de mostrar diretamente
    die("Erro na conexão com o banco: " . $e->getMessage());
}
?>
