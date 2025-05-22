<?php
session_start();
require_once __DIR__ . '/../../config/db.php';  // conexão PDO

// 1. Obter dados do formulário
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    $_SESSION['error'] = 'Por favor, preencha email e senha.';
    header('Location: ../../_html/_login/index.php');
    exit;
}

try {
    // 2. Consulta preparada para encontrar usuário pelo email
    $stmt = $pdo->prepare("SELECT idFun, senha, nome, nivel, acesso FROM cad_fun WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Verifica a senha usando password_verify (senha armazenada agora com password_hash)
        if (password_verify($senha, $user['senha'])) {
            // Credenciais corretas -> inicia sessão do usuário
            session_regenerate_id(true);  // previne fixação de sessão
            $_SESSION['user_id']   = $user['idFun'];
            // Guarda primeiro nome para exibir no menu
            $_SESSION['user_name'] = explode(' ', $user['nome'])[0] ?? $user['nome'];
            $_SESSION['nivel']     = $user['nivel'];
            $_SESSION['acesso']    = $user['acesso'];
            header('Location: ../../_html/_dashboard/dashboard.php');
            exit;
        } else {
            // Senha incorreta
            $_SESSION['error'] = 'Senha inválida.';
        }
    } else {
        // Usuário não encontrado
        $_SESSION['error'] = 'Usuário não encontrado.';
    }
} catch (Exception $e) {
    // Em caso de erro na consulta
    $_SESSION['error'] = 'Erro no login: '.$e->getMessage();
}
// Redireciona de volta para o login em caso de falha
header('Location: ../../_html/_login/index.php');
exit;
?>
