<?php include('../../_php/_login/logado.php'); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../../_css/_menu/menu.css" />
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css" />
  <title>Cadastro de Administradora</title>
</head>
<body>
  <div class="container">
    <!-- Menu de navegação -->
    <nav class="main-nav" role="navigation">
      <button class="menu-toggle" aria-label="Abrir menu">&#9776;</button>
      <ul class="nav-links">
        <?php foreach ($menu as $link => $nome): ?>
          <li class="nav-item"><a href="<?= $link ?>" class="nav-link"><?= $nome ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-user-actions">
        <span class="user-name"><?= htmlspecialchars($nomeP) ?></span>
        <form action="../../_php/_login/deslogar.php" method="post" class="logout-form">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>

    <!-- Formulário de cadastro -->
    <div class="form-container">
      <form action="../../_php/_cadastro/cadAdm.php" method="post">
        <fieldset>
          <legend>Cadastro de Administradora</legend>
          <label for="nome">Nome:</label>
          <input type="text" id="nome" name="nome" maxlength="150" required />
          <label for="cnpj">CNPJ:</label>
          <input type="text" id="cnpj" name="cnpj" maxlength="18" required 
                 pattern="\d{2}\.?\d{3}\.?\d{3}/?\d{4}-?\d{2}" 
                 title="Digite um CNPJ válido (14 dígitos)" />
        </fieldset>
        <button type="submit">Salvar</button>
      </form>
    </div>
  </div>
  <script>
    // Toggle do menu em mobile
    document.querySelector('.menu-toggle').addEventListener('click', () => {
      document.querySelector('.nav-links').classList.toggle('open');
    });
  </script>
</body>
</html>
