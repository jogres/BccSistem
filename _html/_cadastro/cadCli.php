<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

// === DETECÇÃO DE EDIÇÃO ===
$editing = false;
$clienteData = ['nome'=>'','cpf'=>'','endereco'=>'','telefone'=>''];
$saleData = ['idAdm'=>null,'fun_ids'=>[],'idVenda'=>'','select_tipo'=>'Normal','valor'=>'','data'=>date('Y-m-d')];
$_SESSION['venda'] = $_SESSION['venda'] ?? null;

if (isset($_GET['idCli']) && is_numeric($_GET['idCli'])) {
    $editing = true;
    $idCli = (int) $_GET['idCli'];
    // Carrega dados do cliente
    $stmt = $pdo->prepare("SELECT nome, cpf, endereco, telefone FROM cad_cli WHERE idCli = ?");
    $stmt->execute([$idCli]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $clienteData = $row;
    }
    // Verifica se este cliente tem venda associada
    $stmt2 = $pdo->prepare(
        "SELECT v.idAdm, vf.idFun, v.idVenda, v.tipo, v.valor, v.dataV
         FROM venda v
         JOIN venda_cli vc ON vc.idVenda = v.id
         JOIN venda_fun vf ON vf.idVenda = v.id
         WHERE vc.idCli = ? LIMIT 1"
    );
    $stmt2->execute([$idCli]);
    if ($sale = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['venda'] = 'Sim';
        // Preenche saleData
        $saleData['idAdm']        = $sale['idAdm'];
        // coletar todos fun_ids
        $stmt3 = $pdo->prepare("SELECT idFun FROM venda_fun WHERE idVenda = ?");
        $stmt3->execute([$sale['idVenda']]);
        $saleData['fun_ids']      = array_column($stmt3->fetchAll(), 'idFun');
        $saleData['idVenda']      = $sale['idVenda'];
        $saleData['select_tipo']  = $sale['tipo'];
        $saleData['valor']        = $sale['valor'];
        $saleData['data']         = $sale['dataV'];
    } else {
        $_SESSION['venda'] = 'Nao';
    }
}

// Lógica de múltiplas etapas
$vendaSelecionada   = $_SESSION['venda'];
$mostrarCamposVenda = ($vendaSelecionada === 'Sim');
$num_funcs         = $mostrarCamposVenda ? count($saleData['fun_ids']) : (int)($_POST['num_funcs'] ?? 1);

// Carrega opções de Funcionários e Administradoras
ob_start(); include('../../_php/_buscar/_buscaFun/buscaFun.php'); $options_fun = ob_get_clean();
ob_start(); include('../../_php/_buscar/_buscaAdm/buscaAdm.php'); $options_adm = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title><?= $editing ? 'Editar Cliente' : 'Cadastro de Cliente' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../../_css/_menu/menu.css" />
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css" />
</head>
<body>
  <div class="container">
    <!-- Menu omitted for brevity -->
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
    <!-- Etapa 1 -->
    <form id="formVenda" action="../../_php/_vendas/vendaCheck.php" method="post" class="radio-group">
      <label>Venda:</label>
      <input type="radio" name="venda" value="Sim" <?= $vendaSelecionada==='Sim'?'checked':'' ?> /> Sim
      <input type="radio" name="venda" value="Nao" <?= $vendaSelecionada==='Nao'?'checked':'' ?> /> Não
    </form>

    <!-- Etapa 2 -->
    <?php if ($mostrarCamposVenda): ?>
    <form id="formNumFuncs" action="" method="post" class="inline-group">
      <input type="hidden" name="venda" value="Sim" />
      <label for="num-funcs">Quantos funcionários?</label>
      <input type="number" name="num_funcs" id="num-funcs" min="1" value="<?= $num_funcs ?>" required />
    </form>
    <?php endif; ?>

    <!-- Etapa 3 -->
    <form id="formCadastro" action="../../_php/_cadastro/cadCli.php" method="post">
      <?php if ($editing): ?>
        <input type="hidden" name="idCli" value="<?= $idCli ?>" />
      <?php endif; ?>
      <fieldset>
        <legend><?= $editing ? 'Editar Cliente' : 'Dados do Cliente' ?><?= $mostrarCamposVenda?' e Venda':'' ?></legend>

        <label>Nome:</label>
        <input name="nome" required value="<?= htmlspecialchars($clienteData['nome']) ?>" />

        <label>CPF:</label>
        <input name="cpf" required pattern="\d{11}" value="<?= htmlspecialchars($clienteData['cpf']) ?>" />

        <label>Endereço:</label>
        <input name="endereco" required value="<?= htmlspecialchars($clienteData['endereco']) ?>" />

        <label>Telefone:</label>
        <input name="telefone" required value="<?= htmlspecialchars($clienteData['telefone']) ?>" />

        <?php if ($mostrarCamposVenda): ?>
          <label>Administradora:</label>
          <select name="select-adm" required>
            <?php foreach(explode("\n", trim($options_adm)) as $opt) {
              // check if value matches
              if (strpos($opt, 'value="'.$saleData['idAdm'].'"')!==false) echo str_replace('<option', '<option selected', $opt);
              else echo $opt;
            } ?>
          </select>

          <?php for ($i=0; $i<$num_funcs; $i++): $fid = $saleData['fun_ids'][$i] ?? null; ?>
          <label>Funcionário <?= $i+1 ?>:</label>
          <select name="select_fun[]" required>
            <?php foreach(explode("\n", trim($options_fun)) as $opt) {
              if ($fid && strpos($opt, 'value="'.$fid.'"')!==false) echo str_replace('<option', '<option selected', $opt);
              else echo $opt;
            } ?>
          </select>
          <?php endfor; ?>

          <label>Contrato:</label>
          <input name="idVenda" type="number" required value="<?= htmlspecialchars($saleData['idVenda']) ?>" />

          <label>Tipo Venda:</label>
          <select name="select_tipo">
            <option <?= $saleData['select_tipo']==='Normal'?'selected':'' ?>>Normal</option>
            <option <?= $saleData['select_tipo']==='2%'?'selected':'' ?>>2%</option>
          </select>

          <label>Valor:</label>
          <input name="valor" type="number" step="0.01" required value="<?= htmlspecialchars($saleData['valor']) ?>" />

          <label>Data:</label>
          <input name="data" type="date" required value="<?= htmlspecialchars($saleData['data']) ?>" />
        <?php endif; ?>

      </fieldset>
      <button type="submit"><?= $editing?'Salvar Alterações':'Cadastrar' ?></button>
      <?php if ($editing): ?><a href="../../_html/_lista/listaCli.php">Cancelar</a><?php endif; ?>
    </form>
  </div>

  <script>
    document.querySelectorAll('input[name="venda"]').forEach(rb=>rb.onchange=_=>document.getElementById('formVenda').submit());
    document.getElementById('num-funcs')?.addEventListener('change',_=>document.getElementById('formNumFuncs').submit());
    document.querySelector('.menu-toggle').onclick=_=>document.querySelector('.nav-links').classList.toggle('open');
  </script>
</body>
</html>