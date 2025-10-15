<?php
// Iniciar sessão se necessário
Auth::startSessionSecure();

// Verificar se está logado
if (!Auth::check()) {
    $_SESSION['error'] = 'Você precisa estar logado para acessar esta página';
    header('Location: ' . base_url('login.php'));
    exit;
}

// Verificar se é administrador
if (!Auth::isAdmin()) {
    $_SESSION['error'] = 'Acesso negado. Apenas administradores podem acessar esta página.';
    header('Location: ' . base_url('dashboard.php'));
    exit;
}
