<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'bcc';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_errno) {
    die('Falha na conexão: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    if ($email === '' || $senha === '') {
        $_SESSION['error'] = 'Por favor, preencha email e senha.';
        header('Location: ../../_html/_login/index.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT idFun, senha, nome, nivel, acesso FROM cad_fun WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $user = $result->fetch_assoc()) {
            // A senha está armazenada como hash MD5 no banco de dados
            if (md5($senha) === $user['senha']) {
                $_SESSION['user_id'] = $user['idFun'];
                $_SESSION['user_name'] = explode(' ', $user['nome'])[0];
                $_SESSION['nivel'] = $user['nivel'];
                $_SESSION['acesso'] = $user['acesso'];
                header('Location: ../../_html/dashboard.php');
                exit;
            } else {
                $_SESSION['error'] = 'Usuário não encontrado.';
            }
        } else {
            $_SESSION['error'] = 'Usuário não encontrado.';
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = 'Erro na preparação da consulta.';
    }

    header('Location: ../../_html/_login/index.php');
    exit;
}

$conn->close();
?>