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
<div class="login-container">
  <div class="card" style="max-width: 420px; width: 100%;">
    <div class="card-header">
      <h1 class="card-title">🔐 Acesso ao Sistema</h1>
    </div>
    <div class="card-body">
      <?php if ($error): ?>
        <div class="notice notice-error">
          <strong>❌ Erro:</strong> <?= e($error) ?>
        </div>
      <?php endif; ?>
      
      <form method="post">
        <?= CSRF::field() ?>
        
        <div class="form-group">
          <label class="form-label" for="login">👤 Login</label>
          <input class="form-control" type="text" id="login" name="login" required 
                 value="<?= e($_POST['login'] ?? '') ?>" 
                 placeholder="Digite seu login">
        </div>
        
        <div class="form-group">
          <label class="form-label" for="password">🔒 Senha</label>
          <input class="form-control" type="password" id="password" name="password" required 
                 placeholder="Digite sua senha">
        </div>
        
        <div class="cluster" style="justify-content: space-between; margin-top: 2rem;">
          <a class="btn btn-secondary" href="<?= e(base_url('forgot_password.php')) ?>">
            🔑 Esqueci minha senha
          </a>
          <button class="btn btn-primary" type="submit">
            🚀 Entrar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
include __DIR__ . '/../app/views/partials/footer.php';
