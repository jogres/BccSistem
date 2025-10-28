<?php
// Definir header de conteúdo UTF-8 antes de qualquer saída
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

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
      userId: <?= $user ? (int)$user['id'] : 0 ?>
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
        <button class="navbar-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" onclick="(function(btn){var nav=document.getElementById('primary-nav');var backdrop=document.getElementById('nav-backdrop');var open=nav.classList.toggle('is-open');btn.setAttribute('aria-expanded', open);if(backdrop){backdrop.classList.toggle('is-open', open);}document.body.classList.toggle('no-scroll', open);if(open){var first=nav.querySelector('a'); if(first) first.focus();}document.onkeydown=function(e){if(e.key==='Escape'){nav.classList.remove('is-open'); if(backdrop){backdrop.classList.remove('is-open');} document.body.classList.remove('no-scroll'); btn.setAttribute('aria-expanded','false');}}})(this)">☰ Menu</button>
      <?php endif; ?>

      <?php if ($user): ?>
        <nav id="primary-nav" class="navbar-nav" onclick="event.stopPropagation()">
          <a href="<?= e(base_url('dashboard.php')) ?>" class="nav-link">
            📊 Dashboard
          </a>
          <a href="<?= e(base_url('clientes/index.php')) ?>" class="nav-link">
            👥 Clientes
          </a>
          <a href="<?= e(base_url('vendas/index.php')) ?>" class="nav-link">
            🛒 Vendas
          </a>
          <?php if (Auth::isAdmin()): ?>
            <a href="<?= e(base_url('funcionarios/index.php')) ?>" class="nav-link">
              🧑‍💼 Funcionários
            </a>
            <a href="<?= e(base_url('comissoes/index.php')) ?>" class="nav-link">
              💰 Comissões
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

          <?php if (Auth::isAdmin()): ?>
            <a href="<?= e(base_url('logs.php')) ?>" class="nav-link">
              📋 Logs
            </a>
          <?php endif; ?>

          <span class="nav-link">👋 Olá, <?= e($user['nome']) ?></span>
          <a href="<?= e(base_url('logout.php')) ?>" class="nav-link">
            🚪 Sair
          </a>
        </nav>
      <?php endif; ?>
    </div>
    <?php if ($user): ?>
      <div id="nav-backdrop" class="nav-backdrop" onclick="(function(){var nav=document.getElementById('primary-nav');var btn=document.querySelector('.navbar-toggle');nav.classList.remove('is-open');document.body.classList.remove('no-scroll');if(btn){btn.setAttribute('aria-expanded','false');}})()"></div>
    <?php endif; ?>
  </header>

  <main class="main-container">