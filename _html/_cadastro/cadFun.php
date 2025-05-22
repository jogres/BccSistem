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
  <title>Cadastro de Funcionario</title>
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
      <form action="../../_php/_cadastro/cadFun.php" method="post">
        <fieldset>
          <label for="codigo">Codigo:</label>
          <input type="number" name="idFun" id="idfun" required>
          <label for="nome">Nome Completo:</label>
          <input type="text" name="nome" id="nome" required>
          <label for="endereco">Endereço Completo:</label>
          <input type="text" name="endereco" id="endereco" required>
          <label for="numero">Telefone:</label>
          <input type="text" name="numero" id="numero" required>
          <label for="data">Data de Nascimento:</label>
          <input type="date" name="dataN" id="dataN" required>
          <label for="cpf">CPF:</label>
          <input type="text" name="cpf" id="cpf" required>
          <label for="email">E-MAIL:</label>
          <input type="email" name="email" id="email" required>
          <label for="senha">senha:</label>
          <input type="password" name="senha" id="senha" required>
          <label for="acesso">Tipo:</label>
          <select name="acesso" id="acesso">
            <option value="admin">Administrador</option>
            <option value="user">Funcionario</option>
          </select>  
          <label for="ativo">Ativo:</label>
          <select name="ativo" id="ativo">
            <option value="Sim">Sim</option>
            <option value="Nao">Não</option>
          </select>
        </fieldset>
        <button type="submit" class="submit-button">Cadastrar</button>
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