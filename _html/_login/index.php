<?php
// /_consorcioBcc/_html/_login/index.php

session_start();
// Redireciona usuário já autenticado
if (!empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_dashboard/index.php');
    exit;
}
?><!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title id="page-title">Login — consórcioBCC</title>
  <link id="login-css" rel="stylesheet" href="/BccSistem/_css/_login/style.css">
</head>
<body id="login-body">
  <!-- Wrapper flex para centralizar vertical e horizontalmente -->
  <div id="login-wrapper">
    <div id="login-container" class="login-container">
      <header id="login-header">
        <h1 id="login-title">Área de Login</h1>
      </header>

      <?php if (isset($_GET['error'])): ?>
        <div id="login-error" class="alert alert-error">
          E-mail ou senha inválidos.
        </div>
      <?php endif; ?>

      <form id="login-form" action="/BccSistem/_php/_login/login.php" method="post" novalidate>
        <div class="form-group" id="login-group-email">
          <label for="email" class="form-label">E-mail</label>
          <input
            type="email"
            id="email"
            name="email"
            class="form-input"
            placeholder="seu@email.com"
            required
            autofocus>
        </div>

        <div class="form-group" id="login-group-senha">
          <label for="senha" class="form-label">Senha</label>
          <input
            type="password"
            id="senha"
            name="senha"
            class="form-input"
            placeholder="••••••••"
            required>
        </div>

        <div class="form-group form-group-submit" id="login-group-submit">
          <button type="submit" id="login-button" class="btn btn-primary">
            Entrar
          </button>
        </div>
      </form>

      <footer id="login-footer">
        <p class="footer-text">© Brasil Center Credit – Todos os direitos reservados.</p>
      </footer>
    </div>
  </div>

  <script id="login-js" src="/BccSistem/_js/_login/app.js"></script>
</body>
</html>
