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
<div class="login-container">
  <div class="card" style="max-width: 500px; width: 100%;">
    <div class="card-header" style="text-align: center;">
      <h1 class="card-title">🔐 Redefinir Senha</h1>
    </div>
    
    <div class="card-body">
      <?php if ($message): ?>
        <div class="notice notice-success" style="text-align: center;">
          <strong>✅ Sucesso!</strong><br>
          <?= e($message) ?>
          <br><small style="color: var(--bcc-gray-600);">Redirecionando para o login...</small>
        </div>
      <?php endif; ?>
      
      <?php if ($error): ?>
        <div class="notice notice-error" style="text-align: center;">
          <strong>❌ Erro!</strong><br>
          <?= e($error) ?>
        </div>
      <?php endif; ?>
      
      <?php if ($resetData && !$message): ?>
        <div style="text-align: center; margin-bottom: 1.5rem;">
          <p style="font-size: var(--fs-16); color: var(--bcc-gray-700);">
            Olá, <strong><?= e($resetData['nome']) ?></strong>! 👋
          </p>
          <p style="color: var(--bcc-gray-600); margin-top: 0.5rem;">
            Digite sua nova senha abaixo:
          </p>
        </div>
        
        <form method="post">
          <?= CSRF::field() ?>
          
          <div class="form-group">
            <label class="form-label" for="password">🔑 Nova Senha</label>
            <input class="form-control" type="password" id="password" name="password" required 
                   minlength="8" placeholder="Mínimo 8 caracteres">
          </div>
          
          <div class="form-group">
            <label class="form-label" for="confirm_password">🔑 Confirmar Senha</label>
            <input class="form-control" type="password" id="confirm_password" name="confirm_password" required 
                   minlength="8" placeholder="Digite a senha novamente">
          </div>
          
          <div style="text-align: center; margin-top: 1.5rem;">
            <button class="btn btn-primary" type="submit" style="min-width: 200px;">
              ✅ Redefinir Senha
            </button>
          </div>
        </form>
      <?php endif; ?>
      
      <div style="margin-top: 1.5rem; text-align: center; padding-top: 1rem; border-top: 1px solid var(--bcc-gray-300);">
        <a class="btn btn-secondary" href="<?= e(base_url('login.php')) ?>">
          ← Voltar ao Login
        </a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../app/views/partials/footer.php'; ?>
