<?php
  include('../../_php/_login/logado.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../_css/_menu/menu.css">
  <link rel="stylesheet" href="../../_css/_menu/paginacao.css">
  <link rel="stylesheet" href="../../_css/_listas/listas.css">
  <title>Lista de Vendas</title>
</head>
<body>
  <div class="container">
    <button class="menu-toggle float" aria-label="Abrir menu">&#9776;</button>
    <nav class="main-nav">
      <button class="menu-toggle inmenu" aria-label="Fechar menu">&#9776;</button>
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
    <div class="lista-container">     
        
          <?php include '../../_php/_lista/_listaComum/listaVendas.php'; ?>

    </div>
  </div>
  <script>
    // Toggle do menu em mobile
const toggle = document.querySelector('.menu-toggle');
    const menu = document.querySelector('.main-nav');
    toggle.addEventListener('click', () => {
      menu.classList.toggle('open');
    });
    // Fechar o menu ao clicar fora dele (opcional)
    document.addEventListener('click', e => {
      if (window.innerWidth <= 900 && menu.classList.contains('open')) {
        if (!menu.contains(e.target) && !toggle.contains(e.target)) {
          menu.classList.remove('open');
        }
      }
    });
    const floatBtn = document.querySelector('.menu-toggle.float');
    const nav = document.querySelector('.main-nav');
    const inMenuBtn = document.querySelector('.menu-toggle.inmenu');

    // Mostrar menu
    floatBtn.addEventListener('click', (e) => {
      nav.classList.add('open');
      floatBtn.style.display = 'none'; // Esconde ao abrir
      e.stopPropagation();
    });

    // Fechar menu pelo botão interno
    inMenuBtn.addEventListener('click', (e) => {
      nav.classList.remove('open');
      floatBtn.style.display = 'block'; // Mostra ao fechar
      e.stopPropagation();
    });

    // Fechar menu ao clicar fora
    document.addEventListener('click', (e) => {
      if (
        nav.classList.contains('open') &&
        window.innerWidth < 1920 &&
        !nav.contains(e.target) &&
        !floatBtn.contains(e.target)
      ) {
        nav.classList.remove('open');
        floatBtn.style.display = 'block'; // Mostra novamente
      }
    });

// Previne que cliques no menu fechem ele
    nav.addEventListener('click', (e) => e.stopPropagation());
  </script>
</body>
</html>