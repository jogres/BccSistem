<?php
require __DIR__ . '/../app/lib/Database.php';

$pdo = Database::getConnection();



$nome = 'Administrador';
$login = 'admin1';
$senha = 'admin123'; // troque após o primeiro login
$hash = password_hash($senha, PASSWORD_DEFAULT);

// Garante que o papel exista (1=ADMIN)
$pdo->exec("INSERT IGNORE INTO roles (id, nome) VALUES (1, 'ADMIN'), (2, 'PADRAO'), (3, 'APRENDIZ')");

$stmt = $pdo->prepare("INSERT INTO funcionarios (nome, login, senha_hash, role_id, is_ativo) VALUES (:nome, :login, :hash, 1, 1)");
$stmt->execute([':nome' => $nome, ':login' => $login, ':hash' => $hash]);

echo "Admin criado com sucesso! Login: 'admin1' Senha: 'admin123'\n";
