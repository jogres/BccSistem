<?php
  include('../../_php/_login/logado.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../_css/_menu/menu.css">
  <link rel="stylesheet" href="../../_css/_listas/listas.css">
  <title>Lista de Vendas</title>
</head>
<body>
  <div class="container">
    <nav class="main-nav" role="navigation">
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
    <div class="lista-container">
      <table>
        <thead>
          <tr>
            <th>Contrato</th><th>Cliente</th><th>Vendedor</th><th>Valor</th><th>Data</th><th>Adiministradora</th>
          </tr>
        </thead>
        <tbody>
          <?php include '../../_php/_lista/_listaComum/listaVendas.php'; ?>
        </tbody>
      </table>

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