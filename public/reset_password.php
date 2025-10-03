<?php
require __DIR__ . '/../app/lib/Database.php';
require __DIR__ . '/../app/lib/Auth.php';
require __DIR__ . '/../app/lib/Helpers.php';
require __DIR__ . '/../app/lib/PasswordReset.php';
require __DIR__ . '/../app/lib/CSRF.php';

Auth::startSessionSecure();

// Se já logado, redireciona
if (Auth::check()) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$token = $_GET['token'] ?? '';
$resetData = null;
$message = '';
$error = '';

if ($token) {
    $resetData = PasswordReset::validateToken($token);
    if (!$resetData) {
        $error = 'Token inválido ou expirado';
    }
} else {
    $error = 'Token não fornecido';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetData) {
    CSRF::validate();
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 8) {
        $error = 'Senha deve ter pelo menos 8 caracteres';
    } elseif ($password !== $confirmPassword) {
        $error = 'Senhas não coincidem';
    } else {
        $result = PasswordReset::resetPassword($token, $password);
        if ($result['success']) {
            $message = $result['message'];
            // Redireciona após 3 segundos
            header('refresh:3;url=' . base_url('login.php'));
        } else {
            $error = $result['message'];
        }
    }
}

include __DIR__ . '/../app/views/partials/header.php';
?>
<div class="card" style="max-width:420px;margin:40px auto;">
  <h1>Redefinir Senha</h1>
  
  <?php if ($message): ?>
    <div class="notice success"><?= e($message) ?>
      <br><small>Redirecionando para o login...</small>
    </div>
  <?php endif; ?>
  
  <?php if ($error): ?>
    <div class="notice error"><?= e($error) ?></div>
  <?php endif; ?>
  
  <?php if ($resetData && !$message): ?>
    <p>Olá, <strong><?= e($resetData['nome']) ?></strong>!</p>
    <p>Digite sua nova senha:</p>
    
    <form method="post">
      <?= CSRF::field() ?>
      <div class="form-row">
        <div class="col">
          <label>Nova Senha</label>
          <input class="form-control" type="password" name="password" required 
                 minlength="8" placeholder="Mínimo 8 caracteres">
        </div>
      </div>
      <div class="form-row">
        <div class="col">
          <label>Confirmar Senha</label>
          <input class="form-control" type="password" name="confirm_password" required 
                 minlength="8">
        </div>
      </div>
      <div style="margin-top:12px">
        <button class="btn" type="submit">Redefinir Senha</button>
      </div>
    </form>
  <?php endif; ?>
  
  <div style="margin-top:16px; text-align:center">
    <a href="<?= e(base_url('login.php')) ?>">← Voltar ao Login</a>
  </div>
</div>
<?php include __DIR__ . '/../app/views/partials/footer.php'; ?>
