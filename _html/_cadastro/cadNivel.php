<?php
  include('../../_php/_login/logado.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../_css/_menu/menu.css">
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css">
  <title>Cadastro de Nivel</title>
</head>
<body>
  <div class="container">
    <nav class="main-nav">
      <button class="menu-toggle" aria-label="Abrir menú">&#9776;</button>
      <ul class="nav-links">
        <?php 
          foreach ($menu as $link => $nome){
            echo "<li class='nav-item'><a href=\"$link\" class='nav-link'>$nome</a></li>";
          }
        ?>
      </ul>
      <div class="nav-user-actions">
        <span class="user-name">
          <?php
            echo $nomeP;
          ?>
        </span>
        <form action="../../_php/_login/deslogar.php" method="post" class="logout-form">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>
    <div class="form-container">
      <form action="../../_php/_cadastro/cadNivel.php" method="post">
        <fieldset>
          <legend>Cadastro de Nível</legend>
          <label for="nivel">Nivel</label>
          <select name="select-niveis" id="select-niveis">
            <option value="master">Master</option>
            <option value="classic">Classic</option>
            <option value="basic">Basic</option>
          </select>
          <label for="nome">Nome</label>
          <input type="text" id="nome" name="nome" required>
          <label for="primeira">1ª</label>
          <input type="number" min="0.00" step="0.01" id="primeira" name="primeira" required>
          <label for="segunda">2ª</label>
          <input type="number" min="0.00" step="0.01" id="segunda" name="segunda" required>
          <label for="terceira">3ª</label>
          <input type="number" min="0.00" step="0.01" id="terceira" name="terceira" required>
          <label for="quarta">4ª</label>
          <input type="number" min="0.00" step="0.01" id="quarta" name="quarta" required>
          <label for="adm">Adiministradora</label>
          <select name="select-adm" id="select-adm">
            <?php
              include('../../_php/_buscar/_buscaAdm/buscaAdm.php')
            ?>
          </select>
        </fieldset>
        <button type="submit">Salvar</button>
      </form>
      
    </div>
  </div>
  <script>
    // Toggle do menu em mobile
    document.querySelector('.menu-toggle')
      .addEventListener('click', () => {
        document.querySelector('.nav-links')
          .classList.toggle('open');
      });
  </script>
</body>
</html>