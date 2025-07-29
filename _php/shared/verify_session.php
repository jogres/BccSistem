<?php
// _php/shared/verify_session.php
session_start();
if (
    empty($_SESSION['user_id']) ||
    empty($_SESSION['user_papel'])
) {
    // Não autenticado: volta ao login
    header('Location: /_html/_login/index.php');
    exit;
}

// Exemplo: verificar papel específico
// if ($_SESSION['user_papel'] !== 'admin') {
//     die('Você não tem permissão para acessar esta página.');
// }
