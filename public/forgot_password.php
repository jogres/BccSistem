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

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate();
    $login = trim($_POST['login'] ?? '');
    
    if (empty($login)) {
        $error = 'Login é obrigatório';
    } else {
        $result = PasswordReset::requestReset($login);
        if ($result['success']) {
            $success = true;
            $resetLink = $result['reset_link'] ?? '';
            $userName = $result['user_name'] ?? '';
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

include __DIR__ . '/../app/views/partials/header.php';
?>
<div class="login-container">
  <?php if (isset($success) && $success && $resetLink): ?>
    <!-- Tela de Sucesso com o Link -->
    <div class="reset-link-display">
      <h3>✅ Link de Recuperação Gerado!</h3>
      <p>Olá, <strong><?= e($userName) ?></strong>!</p>
      <p>Seu link de recuperação de senha foi gerado com sucesso!</p>
      
      <div class="reset-url" id="resetUrl" onclick="copyResetLink()" title="Clique para copiar"><?= e($resetLink) ?></div>
      
      <div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
        <a class="direct-link" href="<?= e($resetLink) ?>" target="_blank">
          🔓 Abrir Link de Redefinição
        </a>
        
        <button class="copy-btn" onclick="copyResetLink()">
          📋 Copiar Link
        </button>
      </div>
      
      <div class="copy-success" id="copySuccess">
        ✅ Link copiado para a área de transferência!
      </div>
      
      <p style="margin-top: 1.5rem; font-size: var(--fs-13); color: var(--bcc-gray-600); line-height: 1.5;">
        <strong>⚠️ Importante:</strong> Este link expira em 1 hora. Mantenha-o seguro e não compartilhe com outras pessoas.
      </p>
      
      <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--bcc-gray-300);">
        <a class="btn btn-secondary" href="<?= e(base_url('login.php')) ?>" style="display: inline-block;">
          ← Voltar ao Login
        </a>
      </div>
    </div>
  <?php else: ?>
    <!-- Formulário de Recuperação -->
    <div class="card" style="max-width: 500px; width: 100%;">
      <div class="card-header">
        <h1 class="card-title">🔑 Recuperar Senha</h1>
      </div>
      <div class="card-body">
        <p style="color: var(--bcc-gray-600); margin-bottom: 1.5rem; text-align: center;">
          Digite seu login de usuário para gerar um link de recuperação de senha.
        </p>
        
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
                   placeholder="Digite seu login de usuário">
          </div>
          
          <div class="cluster" style="justify-content: space-between; margin-top: 2rem;">
            <a class="btn btn-secondary" href="<?= e(base_url('login.php')) ?>">
              ← Voltar ao Login
            </a>
            <button class="btn btn-primary" type="submit">
              🔗 Gerar Link de Recuperação
            </button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../app/views/partials/footer.php'; ?>

<script>
function copyResetLink() {
    const resetUrl = document.getElementById('resetUrl').textContent;
    const copyBtn = document.querySelector('.copy-btn');
    const copySuccess = document.getElementById('copySuccess');
    
    // Usar a API moderna de clipboard se disponível
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(resetUrl).then(function() {
            showCopySuccess();
        }).catch(function() {
            fallbackCopyText(resetUrl);
        });
    } else {
        fallbackCopyText(resetUrl);
    }
}

function fallbackCopyText(text) {
    // Método alternativo para navegadores mais antigos
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess();
    } catch (err) {
        console.error('Falha ao copiar: ', err);
        alert('Falha ao copiar o link. Por favor, selecione e copie manualmente.');
    }
    
    document.body.removeChild(textArea);
}

function showCopySuccess() {
    const copySuccess = document.getElementById('copySuccess');
    const copyBtn = document.querySelector('.copy-btn');
    
    copyBtn.style.display = 'none';
    copySuccess.style.display = 'block';
    
    // Resetar após 3 segundos
    setTimeout(function() {
        copyBtn.style.display = 'block';
        copySuccess.style.display = 'none';
    }, 3000);
}
</script>
