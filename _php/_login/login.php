<?php
// _php/_login/login.php
session_start();
require __DIR__ . '/../../config/database.php';

// Coleta e sanitiza os dados do formulário
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha = $_POST['senha'] ?? '';

if (!$email || !$senha) {
    header('Location: /BccSistem/_html/_login/index.php?error=1');
    exit;
}

try {
    $pdo = getPDO();
    $sql = 'SELECT id_funcionario, senha_hash, papel, ativo, foto_url, nome 
            FROM funcionarios 
            WHERE email = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verifica se usuário existe, está ativo e senha confere
    if (
        $user &&
        (int)$user['ativo'] === 1 &&
        password_verify($senha, $user['senha_hash'])
    ) {
        // Grava informações essenciais na sessão
        $_SESSION['user_id']    = $user['id_funcionario'];
        $_SESSION['user_papel'] = $user['papel'];
        $_SESSION['user_photo']  = $user['foto_url'];
        $_SESSION['user_name']  = $user['nome'];
        // Redireciona para dashboard
        header('Location: /BccSistem/_html/_dashboard/index.php');
        exit;
    } else {
        // Credenciais inválidas
        header('Location: /BccSistem/_html/_login/index.php?error=1');
        exit;
    }
} catch (PDOException $e) {
    // Em produção, logue o erro em vez de mostrar
    die('Erro ao autenticar: ' . $e->getMessage());
}
?>