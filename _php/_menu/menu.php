<?php
// /_consorcioBcc/_php/_menu/menu.php


if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

// Dados do usuário
$role      = $_SESSION['user_papel'];    // 'admin', 'gerente' ou 'vendedor'
$userName  = $_SESSION['user_name']  ?? '';
$userPhoto = $_SESSION['user_photo'] ?? '/_consorcioBcc/_img/avatar-placeholder.jpg';

// Permissões de acesso por módulo
$permissions = [
    'admin'    => ['dashboard','clientes','funcionarios','administradoras','planos','percentuais','niveis','vendas', 'parcelas'],
    'gerente'  => ['dashboard','clientes','funcionarios','administradoras','planos','percentuais','niveis','vendas'],
    'vendedor' => ['dashboard','clientes','vendas'],  
];
function canView($module) {
    global $permissions, $role;
    return in_array($module, $permissions[$role]);
}
?>
<!-- Toggle button (mobile) -->
<button id="menu-toggle" aria-label="Abrir/Fechar Menu">☰</button>

<nav id="main-menu" class="open">
  <ul class="menu-list">
    <?php if (canView('dashboard')): ?>
      <li><a href="/BccSistem/_html/_dashboard/index.php">Dashboard</a></li>
    <?php endif; ?>

    <?php if (canView('clientes')): ?>
      <li class="has-submenu">
        <a href="#">Clientes</a>
        <ul class="submenu">
          <li><a href="/BccSistem/_html/_clientes/form.php">Cadastrar Cliente</a></li>
          <li><a href="/BccSistem/_php/_clientes/list.php">Listar Clientes</a></li>
        </ul>
      </li>
    <?php endif; ?>

    <?php if (canView('funcionarios')): ?>
      <li class="has-submenu">
        <a href="#">Funcionários</a>
        <ul class="submenu">
          <li><a href="/BccSistem/_html/_funcionarios/form.php">Cadastrar Funcionário</a></li>
          <li><a href="/BccSistem/_php/_funcionarios/list.php">Listar Funcionários</a></li>
        </ul>
      </li>
    <?php endif; ?>

    <?php if (canView('administradoras')): ?>
      <li class="has-submenu">
        <a href="#">Administradoras</a>
        <ul class="submenu">
          <li><a href="/BccSistem/_html/_administradoras/form.php">Cadastrar Administradora</a></li>
          <li><a href="/BccSistem/_php/_administradoras/list.php">Listar Administradoras</a></li>
        </ul>
      </li>
    <?php endif; ?>

    <?php if (canView('planos')): ?>
      <li class="has-submenu">
        <a href="#">Planos de Comissão</a>
        <ul class="submenu">
          <li><a href="/BccSistem/_html/_planos_comissao/form.php">Cadastrar Plano</a></li>
          <li><a href="/BccSistem/_php/_planos_comissao/list.php">Listar Planos</a></li>
        </ul>
      </li>
    <?php endif; ?>

    <?php if (canView('percentuais')): ?>
      <li class="has-submenu">
        <a href="#">Percentuais de Comissão</a>
        <ul class="submenu">
          <li><a href="/BccSistem/_html/_percentuais_comissao/form.php">Cadastrar Percentual</a></li>
          <li><a href="/BccSistem/_php/_percentuais_comissao/list.php">Listar Percentuais</a></li>
        </ul>
      </li>
    <?php endif; ?>

    <?php if (canView('niveis')): ?>
      <li class="has-submenu">
        <a href="#">Níveis de Comissão</a>
        <ul class="submenu">
          <li><a href="/BccSistem/_html/_niveis_comissao/form.php">Cadastrar Nível</a></li>
          <li><a href="/BccSistem/_php/_niveis_comissao/list.php">Listar Níveis</a></li>
        </ul>
      </li>
    <?php endif; ?>

    <?php if (canView('vendas')): ?>
      <li class="has-submenu">
        <a href="#">Vendas</a>
        <ul class="submenu">
          <li><a href="/BccSistem/_html/_vendas/form.php">Cadastrar Venda</a></li>
          <li><a href="/BccSistem/_php/_vendas/list.php">Listar Vendas</a></li>
        </ul>
      </li>
    <?php endif; ?>
  </ul>

  <div class="menu-footer">
    <img src="<?php echo htmlspecialchars($userPhoto); ?>"
         alt="Foto de <?php echo htmlspecialchars($userName); ?>"
         class="menu-avatar">
    <span class="menu-username"><?php echo htmlspecialchars($userName); ?></span>
    <a href="/BccSistem/_php/_login/logout.php" class="menu-logout">Sair</a>
  </div>
</nav>

<script src="/BccSistem/_js/_menu/menu.js"></script>
