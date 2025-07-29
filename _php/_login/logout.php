<?php
// _php/_login/logout.php
session_start();
// Limpa toda a sessão
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();
// Redireciona ao login
header('Location: /BccSistem/_html/_login/index.php');
exit;
?>