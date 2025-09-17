<?php
Auth::startSessionSecure();
$user = Auth::user();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BCC - Gestão</title>
  <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body>
<header class="topbar">
  <div class="container">
    <div class="brand">Brasil Center Cred — Gestão</div>
    <?php if ($user): ?>
      <nav class="nav">
        <a href="<?= e(base_url('dashboard.php')) ?>">Dashboard</a>
        <a href="<?= e(base_url('clientes/index.php')) ?>">Clientes</a>
        <?php if (Auth::isAdmin()): ?>
          <a href="<?= e(base_url('funcionarios/index.php')) ?>">Funcionários</a>
        <?php endif; ?>
        <span class="user">Olá, <?= e($user['nome']) ?> (<?= e($user['role_name']) ?>)</span>
        <a class="logout" href="<?= e(base_url('logout.php')) ?>">Sair</a>
      </nav>
    <?php endif; ?>
  </div>
</header>
<main class="container">
