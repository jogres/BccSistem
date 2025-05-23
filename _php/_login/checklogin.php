<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if ($email === '' || $senha === '') {
    $_SESSION['error'] = 'Por favor, preencha email e senha.';
    header('Location: ../../_html/_login/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT idFun, senha, nome, nivel, acesso 
         FROM cad_fun 
         WHERE email = ?"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = 'Usuário não encontrado.';
        header('Location: ../../_html/_login/index.php');
        exit;
    }

    $dbHash = $user['senha'];
    $ok = false;

    // 1) Se for MD5 (32 caracteres hexadecimais)
    if (preg_match('/^[0-9a-f]{32}$/i', $dbHash)) {
        if (md5($senha) === $dbHash) {
            $ok = true;
            // Re-hash em bcrypt para migrar do MD5
            $newHash = password_hash($senha, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE cad_fun SET senha = ? WHERE idFun = ?");
            $upd->execute([$newHash, $user['idFun']]);
        }
    }
    // 2) Senão, assumimos hash bcrypt/PHP >=5.5
    elseif (password_verify($senha, $dbHash)) {
        $ok = true;
        // Opcional: se usar cost diferente, podemos re-hashar para ajustar cost
        if (password_needs_rehash($dbHash, PASSWORD_BCRYPT)) {
            $rehash = password_hash($senha, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE cad_fun SET senha = ? WHERE idFun = ?")
                ->execute([$rehash, $user['idFun']]);
        }
    }

    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['idFun'];
        $_SESSION['user_name'] = explode(' ', $user['nome'])[0];
        $_SESSION['nivel']     = $user['nivel'];
        $_SESSION['acesso']    = $user['acesso'];
        header('Location: ../../_html/_dashboard/dashboard.php');
        exit;
    } else {
        $_SESSION['error'] = 'Senha inválida.';
        header('Location: ../../_html/_login/index.php');
        exit;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro no login: ' . $e->getMessage();
    header('Location: ../../_html/_login/index.php');
    exit;
}
