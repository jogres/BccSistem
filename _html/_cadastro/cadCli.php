<?php include('../../_php/_login/logado.php'); 

// Lógica de controle do formulário de múltiplas etapas:
$vendaSelecionada   = $_SESSION['venda'] ?? null;             // valor selecionado de "Venda: Sim/Não" persistido na sessão
$mostrarCamposVenda = ($vendaSelecionada === 'Sim');

// Número de funcionários participantes selecionado na etapa anterior (padrão 1)
$num_funcs = isset($_POST['num_funcs']) ? max(1, (int)$_POST['num_funcs']) : 1;

// Carrega opções de Funcionários para selects (reutilizamos script buscaFun.php)
ob_start();
include('../../_php/_buscar/_buscaFun/buscaFun.php');
$options_fun = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Cadastro de Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../../_css/_menu/menu.css" />
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css" />
</head>
<body>
  <div class="container">
    <!-- Menu -->
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
    <!-- Etapa 1: Pergunta se haverá venda associada -->
    <form id="formVenda" action="../../_php/_vendas/vendaCheck.php" method="post" class="radio-group">
      <label>Venda:</label>
      <input type="radio" name="venda" id="venda-sim" value="Sim" 
             <?= $vendaSelecionada === 'Sim' ? 'checked' : '' ?> />
      <label for="venda-sim">Sim</label>
      <input type="radio" name="venda" id="venda-nao" value="Nao" 
             <?= $vendaSelecionada === 'Nao' ? 'checked' : '' ?> />
      <label for="venda-nao">Não</label>
    </form>

    <!-- Etapa 2: Se Sim, perguntar quantos funcionários participaram da venda -->
    <?php if ($mostrarCamposVenda): ?>
    <form id="formNumFuncs" action="" method="post" class="inline-group">
      <!-- mantém escolha de venda no post -->
      <input type="hidden" name="venda" value="<?= htmlspecialchars($vendaSelecionada) ?>" />
      <label for="num-funcs">Quantos funcionários participaram?</label>
      <input type="number" name="num_funcs" id="num-funcs" min="1" value="<?= htmlspecialchars($num_funcs) ?>" required />
    </form>
    <?php endif; ?>

    <!-- Etapa 3: Formulário final de cadastro do cliente (e venda, se aplicável) -->
    <form id="formCadastro" action="../../_php/_cadastro/cadCli.php" method="post">
      <fieldset>
        <legend>Dados do Cliente<?= $mostrarCamposVenda ? ' e Venda' : '' ?></legend>
        <label for="nome">Nome Completo:</label>
        <input type="text" name="nome" id="nome" maxlength="100" required />
        <label for="cpf">CPF:</label>
        <input type="text" name="cpf" id="cpf" maxlength="14" required 
               pattern="\d{3}\.?\d{3}\.?\d{3}-?\d{2}" title="Digite um CPF válido" />
        <label for="endereco">Endereço:</label>
        <input type="text" name="endereco" id="endereco" maxlength="200" required />
        <label for="telefone">Telefone:</label>
        <input type="text" name="telefone" id="telefone" maxlength="20" required 
               pattern="\d+[\d\s\-()]*" title="Digite um telefone válido" />
        
        <?php if ($mostrarCamposVenda): ?>
          <!-- Se for registrar venda, incluir seleção de administradora e funcionários -->
          <label for="select-adm">Administradora:</label>
          <select name="select-adm" id="select-adm" required>
            <?php include('../../_php/_buscar/_buscaAdm/buscaAdm.php'); ?>
          </select>
          <?php for ($i = 1; $i <= $num_funcs; $i++): ?>
            <div class="func-block">
              <label for="select-fun-<?= $i ?>">Funcionário <?= $i ?>:</label>
              <select name="select_fun[]" id="select-fun-<?= $i ?>" required>
                <?= $options_fun /* opções de funcionários carregadas acima */ ?>
              </select>
            </div>
          <?php endfor; ?>
          <label for="idVenda">Número do Contrato:</label>
          <input type="number" name="idVenda" id="idVenda" required />
          <label for="select_tipo">Tipo (categoria da venda):</label>
          <select name="select_tipo" id="select_tipo">
            <option value="Normal">Normal</option>
            <option value="2%">2%</option>
          </select>
          <label for="valor">Valor (R$):</label>
          <input type="number" name="valor" id="valor" step="0.01" min="0.01" required />
          <label for="data">Data da Venda:</label>
          <input type="date" name="data" id="data" required />
        <?php endif; ?>
      </fieldset>
      <button type="submit">Cadastrar</button>
    </form>
  </div>

  <script>
    // Ao mudar opção de venda, submete formVenda automaticamente
    document.querySelectorAll('input[name="venda"]').forEach(rb =>
      rb.addEventListener('change', () => {
        document.getElementById('formVenda').submit();
      })
    );
    // Ao alterar número de funcs, submete formNumFuncs
    document.getElementById('num-funcs')?.addEventListener('change', () => {
      document.getElementById('formNumFuncs').submit();
    });
    // Toggle menu mobile
    document.querySelector('.menu-toggle').addEventListener('click', () => {
      document.querySelector('.nav-links').classList.toggle('open');
    });
  </script>
</body>
</html>
