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
  <link rel="stylesheet" href="<?= e(base_url('assets/css/design-system.css')) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/main.css')) ?>?v=<?= time() ?>">
 
  <script>
    window.APP = {
      isAdmin: <?= Auth::isAdmin() ? 'true' : 'false' ?>,
      userId: <?= (int)Auth::user()['id'] ?>
    };
  </script>

</head>

<body>
  <header class="header">
    <div class="navbar">
      <a href="<?= e(base_url('dashboard.php')) ?>" class="navbar-brand">
        🏢 Brasil Center Cred
      </a>
      
      <?php if ($user): ?>
        <nav class="navbar-nav">
          <a href="<?= e(base_url('dashboard.php')) ?>" class="nav-link">
            📊 Dashboard
          </a>
          <a href="<?= e(base_url('clientes/index.php')) ?>" class="nav-link">
            👥 Clientes
          </a>
          <?php if (Auth::isAdmin()): ?>
            <a href="<?= e(base_url('funcionarios/index.php')) ?>" class="nav-link">
              🧑‍💼 Funcionários
            </a>
          <?php endif; ?>
          
          <?php
          // Buscar contador de notificações não lidas
          require_once __DIR__ . '/../../lib/Notification.php';
          $unreadCount = Notification::getUnreadCount($user['id']);
          ?>
          
          <a href="<?= e(base_url('notifications.php')) ?>" class="nav-link notification-badge">
            🔔 Notificações
            <?php if ($unreadCount > 0): ?>
              <span class="badge"><?= $unreadCount ?></span>
            <?php endif; ?>
          </a>
          
          <span class="nav-link">👋 Olá, <?= e($user['nome']) ?></span>
          <a href="<?= e(base_url('logout.php')) ?>" class="nav-link">
            🚪 Sair
          </a>
        </nav>
      <?php endif; ?>
    </div>
  </header>
  
  <main class="main-container">