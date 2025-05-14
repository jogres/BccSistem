<?php
  session_start();
  $error = $_SESSION['error'] ?? '';
  unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
</head>
<body>
  <div class="login-container">
    <?php if($error): ?>
      <div class="error-menssage">
        <?php 
          echo htmlspecialchars($error);
        ?>
      </div>
    <?php endif; ?>
    <div class="login-form">
      <form action="" method="POST">
        <fieldset>
          <input type="email" name="email" id="email" placeholder="E-mail">
          <input type="password" name="senha" id="senha" placeholder="senha">   
        </fieldset>
        <button type="submit" id="enviar" name="enviar" class="login-button">enviar</button>               
      </form>
    </div>  
  </div>
</body>
</html>