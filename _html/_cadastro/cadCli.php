<?php
include('../../_php/_login/logado.php');

$vendaSelecionada = $_SESSION['venda'] ?? null;
$mostrarCamposVenda = $vendaSelecionada === 'Sim';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastro de Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    .hidden { display: none; }
  </style>
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
        <span class="user-name"><?= $nomeP ?></span>
        <form action="../../_php/_login/deslogar.php" method="post" class="logout-form">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>

    <div class="form-container">
       <form action="../../_php/_vendas/vendaCheck.php" method="post" id="formCadastro">
          <label for="venda">Venda:</label><br>
          <input type="radio" name="venda" id="Sim" value="Sim" <?= isset($_POST['venda']) && $_POST['venda'] === 'Sim' ? 'checked' : '' ?>>
          <label for="Sim">Sim</label>

          <input type="radio" name="venda" id="Nao" value="Nao" <?= isset($_POST['venda']) && $_POST['venda'] === 'Nao' ? 'checked' : '' ?>>
          <label for="Nao">Não</label>
      </form>
      <form action="" method="post" >
        <fieldset>
          <label for="nome">Nome Completo:</label>
          <input type="text" name="nome" id="nome" required>

          <label for="cpf">CPF:</label>
          <input type="text" name="cpf" id="cpf" required>

          <label for="endereco">Endereço Completo:</label>
          <input type="text" name="endereco" id="endereco" required>

          <label for="telefone">Telefone:</label>
          <input type="text" name="telefone" id="telefone" required>

          <?php if ($mostrarCamposVenda): ?>
              <label for="produto">Produto:</label>
              <input type="text" name="produto" id="produto">
              <label for="valor">Valor da Venda:</label>
              <input type="text" name="valor" id="valor">
          <?php endif; ?>


        </fieldset>
        <button type="submit">Cadastrar</button>
      </form>
    </div>
  </div>

  <script>
    // Envia automaticamente o formulário quando radio for alterado
    document.querySelectorAll('input[name="venda"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        document.getElementById('formCadastro').submit();
      });
    });
  </script>
</body>
</html>
