<?php
  session_start();
  $error = $_SESSION['error'] ?? '';
  unset($_SESSION['error']); // limpa mensagem após exibir
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../_css/_login/login.css">
  <title>Login</title>
</head>
<body>
  <div class="login-container">
    <?php if ($error): ?>
      <!-- Mensagem de erro vinda da sessão, sanitizada para evitar XSS -->
      <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div class="login-form">
      <form action="../../_php/_login/checklogin.php" method="POST">
        <fieldset>
          <input type="email" name="email" id="email" placeholder="E-mail" required>
          <input type="password" name="senha" id="senha" placeholder="Senha" required>
        </fieldset>
        <button type="submit" id="enviar" name="enviar" class="login-button">Enviar</button>
      </form>
    </div>
  </div>
</body>
</html>
