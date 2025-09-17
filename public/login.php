<?php
require __DIR__ . '/../app/lib/Database.php';
require __DIR__ . '/../app/lib/Auth.php';
require __DIR__ . '/../app/lib/Helpers.php';
require __DIR__ . '/../app/lib/CSRF.php';

Auth::startSessionSecure();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    if (Auth::login($login, $password)) {
        header('Location: ' . base_url('dashboard.php'));
        exit;
    } else {
        $error = 'Login ou senha inválidos.';
    }
}

include __DIR__ . '/../app/views/partials/header.php';
?>
<div class="card" style="max-width:420px;margin:40px auto;">
  <h1>Acesso</h1>
  <?php if ($error): ?><div class="notice" style="background:#ffebee;border-color:#ffcdd2;color:#b71c1c"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= CSRF::field() ?>
    <div class="form-row">
      <div class="col">
        <label>Login</label>
        <input class="form-control" type="text" name="login" required>
      </div>
    </div>
    <div class="form-row" style="margin-top:8px">
      <div class="col">
        <label>Senha</label>
        <input class="form-control" type="password" name="password" required>
      </div>
    </div>
    <div style="margin-top:12px">
      <button class="btn" type="submit">Entrar</button>
    </div>
  </form>
  <p style="font-size:12px;color:#666;margin-top:12px">Dica: rode o script <code>scripts/seed_admin.php</code> para criar o primeiro administrador.</p>
</div>
<?php
include __DIR__ . '/../app/views/partials/footer.php';
