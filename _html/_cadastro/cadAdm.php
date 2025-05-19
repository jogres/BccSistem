<?php
  include('../../_php/_login/logado.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro de Administradores</title>
</head>
<body>
  <div class="container">
    <nav class="main-nav" role="navigation">
      <button class="menu-toggle" aria-label="Abrir menú">&#9776;</button>
      <ul class="nav-links">
        <?php 
          foreach ($menu as $link => $nome){
            echo "<li clsass='nav-item'><a href=\"$link\" class='nav-link'>$nome</a></li>";
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
      <form action="../../_php/_cadastro/cadAdm.php" method="post">
        <fieldset>
          <legend>Cadastro de Administradores</legend>          
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" required>
        </fieldset>
        <button type="submit">Salvar</button>
      </form>
    </div>      
  </div>
</body>
</html>