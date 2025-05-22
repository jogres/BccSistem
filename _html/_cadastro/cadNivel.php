<?php include('../../_php/_login/logado.php'); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../../_css/_menu/menu.css" />
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css" />
  <title>Cadastro de Nível</title>
</head>
<body>
  <div class="container">
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
    <div class="form-container">
      <form action="../../_php/_cadastro/cadNivel.php" method="post">
        <fieldset>
          <legend>Cadastro de Nível</legend>
          <label for="select-niveis">Nível:</label>
          <select name="select-niveis" id="select-niveis">
            <option value="master">Master</option>
            <option value="classic">Classic</option>
            <option value="basic">Basic</option>
          </select>
          <label for="nome">Nome do Plano:</label>
          <input type="text" id="nome" name="nome" maxlength="100" required />
          <label for="primeira">1ª (%)</label>
          <input type="number" id="primeira" name="primeira" min="0" max="100" step="0.01" required />
          <label for="segunda">2ª (%)</label>
          <input type="number" id="segunda" name="segunda" min="0" max="100" step="0.01" required />
          <label for="terceira">3ª (%)</label>
          <input type="number" id="terceira" name="terceira" min="0" max="100" step="0.01" required />
          <label for="quarta">4ª (%)</label>
          <input type="number" id="quarta" name="quarta" min="0" max="100" step="0.01" required />
          <label for="select-adm">Administradora:</label>
          <select name="select-adm" id="select-adm" required>
            <?php include('../../_php/_buscar/_buscaAdm/buscaAdm.php'); ?>
          </select>
        </fieldset>
        <button type="submit">Salvar</button>
      </form>
    </div>
  </div>
  <script>
    document.querySelector('.menu-toggle').addEventListener('click', () => {
      document.querySelector('.nav-links').classList.toggle('open');
    });
  </script>
</body>
</html>
