<?php
  include('../../_php/_login/logado.php');

  // Lê escolha de “Venda” armazenada em sessão
  $vendaSelecionada  = $_SESSION['venda'] ?? null;
  $mostrarCamposVenda = $vendaSelecionada === 'Sim';

  // Define quantos selects exibir (quando voltar de formNumFuncs)
  $num_funcs = isset($_POST['num_funcs'])
               ? max(1, (int) $_POST['num_funcs'])
               : 1;

  // Carrega opções de funcionários
  $conn = mysqli_connect("localhost", "root", "", "bcc");
  if (!$conn) die("Falha na conexão: " . mysqli_connect_error());
  $resFun = mysqli_query($conn, "SELECT idFun, nome FROM cad_fun");
  $options_fun = "";
  if ($resFun && $resFun->num_rows > 0) {
    while ($f = $resFun->fetch_assoc()) {
      $options_fun .= "<option value=\"{$f['idFun']}\">{$f['nome']}</option>";
    }
  } else {
    $options_fun = "<option value=\"\">Nenhum funcionário</option>";
  }
  mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastro de Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../_css/_menu/menu.css">
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css">
</head>
<body>
  <div class="container">
    <!-- NAV -->
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
        <span class="user-name"><?= htmlspecialchars($nomeP) ?></span>
        <form action="../../_php/_login/deslogar.php" method="post" class="logout-form">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>

    <!-- FORM “Venda” -->
    <form id="formVenda" action="../../_php/_vendas/vendaCheck.php" method="post">
      <div class="radio-group">
        <label for="venda">Venda:</label>
        <input type="radio" name="venda" id="Sim"  value="Sim"
               <?= $vendaSelecionada==='Sim' ? 'checked':'' ?>>
        <label for="Sim">Sim</label>
        <input type="radio" name="venda" id="Nao"  value="Nao"
               <?= $vendaSelecionada==='Nao' ? 'checked':'' ?>>
        <label for="Nao">Não</label>
      </div>
    </form>

    <!-- FORM “Quantidade de Vendedores” -->
    <?php if ($mostrarCamposVenda): ?>
      <form id="formNumFuncs" action="" method="post">
        <input type="hidden" name="venda" value="<?= htmlspecialchars($vendaSelecionada) ?>">
        <div class="inline-group">
          <label for="num-funcs">Quantos funcionários participaram?</label>
          <input
            type="number"
            name="num_funcs"
            id="num-funcs"
            min="1"
            value="<?= htmlspecialchars($num_funcs) ?>"
            required
          >
        </div>
      </form>
    <?php endif; ?>

    <!-- FORM “Cadastro” (FINAL) -->
    <form id="formCadastro" action="../../_php/_cadastro/cadCli.php" method="post">
      <fieldset>
        <label for="nome">Nome Completo:</label>
        <input type="text" name="nome" id="nome" required>

        <label for="cpf">CPF:</label>
        <input type="text" name="cpf" id="cpf" required>

        <label for="endereco">Endereço:</label>
        <input type="text" name="endereco" id="endereco" required>

        <label for="telefone">Telefone:</label>
        <input type="text" name="telefone" id="telefone" required>

        <?php if ($mostrarCamposVenda): ?>
          <label for="select-adm">Administradora:</label>
          <select name="select-adm" id="select-adm">
            <?php include('../../_php/_buscar/_buscaAdm/buscaAdm.php'); ?>
          </select>

          <!-- Replicação de selects -->
          <?php for ($i = 1; $i <= $num_funcs; $i++): ?>
            <div class="func-block">
              <label for="select-fun-<?= $i ?>">Funcionário <?= $i ?>:</label>
              <select name="select_fun[]" id="select-fun-<?= $i ?>">
                <?= $options_fun ?>
              </select>
            </div>
          <?php endfor; ?>

          <label for="idVenda">Número do Contrato:</label>
          <input type="number" name="idVenda" id="idVenda" required>

          <label for="select_tipo">Tipo:</label>
          <select name="select_tipo" id="select_tipo">
            <option value="tipo1">Normal</option>
            <option value="tipo2">2%</option>
          </select>

          <label for="valor">Valor:</label>
          <input type="number" name="valor" id="valor" required>

          <label for="data">Data:</label>
          <input type="date" name="data" id="data" required>
        <?php endif; ?>
      </fieldset>

      <button type="submit">Cadastrar</button>
    </form>
  </div>

  <script>
    // Submete formVenda ao trocar radio
    document.querySelectorAll('input[name="venda"]').forEach(rb =>
      rb.addEventListener('change', () =>
        document.getElementById('formVenda').submit()
      )
    );

    // Submete formNumFuncs ao mudar quantidade
    document.getElementById('num-funcs')?.addEventListener('change', () => {
      document.getElementById('formNumFuncs').submit();
    });

    // Toggle do menu em mobile
    document.querySelector('.menu-toggle')
      .addEventListener('click', () => {
        document.querySelector('.nav-links')
          .classList.toggle('open');
      });
  </script>
</body>
</html>
