<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

// 1) Detecção de edição
$editing = false;
$idCli   = null;
$cliente = ['nome'=>'','cpf'=>'','endereco'=>'','telefone'=>''];
$sale    = ['idAdm'=>null,'fun_ids'=>[],'idVenda'=>'','select_tipo'=>'Normal','valor'=>'','data'=>date('Y-m-d')];

// 2) Propaga seleção de venda e número de funcs via POST ou sessão
$postVenda   = $_POST['venda']     ?? ($_SESSION['venda'] ?? 'Nao');
$postNumFunc = (int)($_POST['num_funcs'] ?? 1);
// persiste a escolha de venda na sessão para subsequentes
$_SESSION['venda'] = $postVenda;

// 3) Carrega cliente se for edição
if (isset($_GET['idCli']) && is_numeric($_GET['idCli'])) {
    $editing = true;
    $idCli   = (int) $_GET['idCli'];
    $stmt    = $pdo->prepare("SELECT nome, cpf, endereco, telefone, tipo FROM cad_cli WHERE idCli = ?");
    $stmt->execute([$idCli]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cliente            = $row;
        $_SESSION['venda'] = ($row['tipo'] === 'com_venda') ? 'Sim' : 'Nao';
        $postVenda          = $_SESSION['venda'];
    }
    if ($_SESSION['venda'] === 'Sim') {
        $stmt = $pdo->prepare(
            "SELECT v.idAdm,v.idVenda,v.tipo,v.valor,v.dataV
             FROM venda v
             JOIN venda_cli vc ON vc.idVenda=v.id
             WHERE vc.idCli=? LIMIT 1"
        );
        $stmt->execute([$idCli]);
        if ($v = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sale = [
              'idAdm'       => $v['idAdm'],
              'idVenda'     => $v['idVenda'],
              'select_tipo' => $v['tipo'],
              'valor'       => $v['valor'],
              'data'        => $v['dataV'],
              'fun_ids'     => []
            ];
            $stmt2 = $pdo->prepare("SELECT idFun FROM venda_fun WHERE idVenda=?");
            $stmt2->execute([$v['idVenda']]);
            $sale['fun_ids'] = array_column($stmt2->fetchAll(), 'idFun');
            $postNumFunc     = count($sale['fun_ids']);
        }
    }
}

// 4) Lógica de etapas
$vendaSelecionada = $postVenda;
$mostrarVenda     = ($vendaSelecionada === 'Sim');
$num_funcs        = $mostrarVenda ? max(1, $postNumFunc) : 1;

// 5) Carrega selects
ob_start(); include('../../_php/_buscar/_buscaAdm/buscaAdm.php'); $optAdm = ob_get_clean();
ob_start(); include('../../_php/_buscar/_buscaFun/buscaFun.php'); $optFun = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <link rel="stylesheet" href="../../_css/_menu/menu.css" />
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css" />
  <title><?= $editing?'Editar Cliente':'Cadastro de Cliente' ?></title>
</head>
<body>
  <div class="container">
    <nav class="main-nav" role="navigation">
      <button class="menu-toggle" aria-label="Abrir menu">&#9776;</button>
      <ul class="nav-links">
        <?php foreach ($menu as $link => $nome): ?>
          <li class="nav-item"><a href="<?= $link ?>" class="nav-link"><?= $nome ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-user-actions">
        <span class="user-name"><?= htmlspecialchars($nomeP) ?></span>
        <form action="../../_php/_login/deslogar.php" method="post">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>   
    <!-- Etapa 1: venda Sim/Não -->
    <form id="formVenda" action="?idCli=<?= $editing ? $idCli : '' ?>" method="post" class="radio-group">
      <label for="vendaSim">Venda:</label>
      <?php if ($editing): ?><input type="hidden" name="idCli" value="<?= $idCli ?>" /><?php endif; ?>
      <input type="radio" name="venda" value="Sim" <?= $vendaSelecionada==='Sim'?'checked':'' ?>/> Sim
      <input type="radio" name="venda" value="Nao" <?= $vendaSelecionada==='Nao'?'checked':'' ?>/> Não
      <input type="hidden" name="num_funcs" value="<?= $num_funcs ?>" />
    </form>

    <!-- Etapa 2: número de funcionários -->
    <?php if ($mostrarVenda): ?>
    <form id="formNumFuncs" action="?idCli=<?= $editing ? $idCli : '' ?>" method="post" class="inline-group">
      <?php if ($editing): ?><input type="hidden" name="idCli" value="<?= $idCli ?>" /><?php endif; ?>
      <input type="hidden" name="venda" value="Sim" />
      <label>Funcionários:</label>
      <input type="number" name="num_funcs" min="1" value="<?= $num_funcs ?>" required />
    </form>
    <?php endif; ?>

    <!-- Etapa 3: formulário principal -->
    <form action="../../_php/_cadastro/cadCli.php" method="post">
      <?php if ($editing): ?><input type="hidden" name="idCli" value="<?= $idCli ?>" /><?php endif; ?>
      <input type="hidden" name="venda" value="<?= htmlspecialchars($vendaSelecionada) ?>" />
      <input type="hidden" name="num_funcs" value="<?= $num_funcs ?>" />
      <fieldset>
        <legend><?= $editing?'Editar':'Cadastrar' ?> Cliente<?= $mostrarVenda?' e Venda':'' ?></legend>

        <label>Nome:</label>
        <input type="text" name="nome" required value="<?= htmlspecialchars($cliente['nome']) ?>" />

        <label>CPF:</label>
        <input type="text" name="cpf" pattern="\d{11}" required value="<?= htmlspecialchars($cliente['cpf']) ?>" />

        <label>Endereço:</label>
        <input type="text" name="endereco" required value="<?= htmlspecialchars($cliente['endereco']) ?>" />

        <label>Telefone:</label>
        <input type="text" name="telefone" required value="<?= htmlspecialchars($cliente['telefone']) ?>" />

        <?php if ($mostrarVenda): ?>
          <label>Administradora:</label>
          <select name="select-adm" required><?= $optAdm ?></select>

          <?php for ($i=0; $i<$num_funcs; $i++): $fid = $sale['fun_ids'][$i] ?? null; ?>
            <label>Funcionário <?= $i+1 ?>:</label>
            <select name="select_fun[]" required>
              <?php foreach (explode("\n", trim($optFun)) as $o):
                if ($fid && strpos($o, "value=\"$fid\"")!==false) echo str_replace('<option','<option selected',$o);
                else echo $o;
              endforeach; ?>
            </select>
          <?php endfor; ?>

          <label>Contrato (nº):</label>
          <input type="number" name="idVenda" required value="<?= htmlspecialchars($sale['idVenda']) ?>" />

          <label>Tipo:</label>
          <select name="select_tipo">
            <option <?= $sale['select_tipo']==='Normal'?'selected':'' ?>>Normal</option>
            <option <?= $sale['select_tipo']==='2%'?'selected':'' ?>>2%</option>
          </select>

          <label>Valor:</label>
          <input type="number" name="valor" step="0.01" required value="<?= htmlspecialchars($sale['valor']) ?>" />

          <label>Data:</label>
          <input type="date" name="data" required value="<?= htmlspecialchars($sale['data']) ?>" />
        <?php endif; ?>
      </fieldset>

      <div class="form-buttons">
        <button type="submit" class="btn btn-primary"><?= $editing?'Salvar Alterações':'Cadastrar' ?></button>
        <?php if ($editing): ?><a href="../../_html/_lista/listaCli.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>

  <script>
    document.querySelectorAll('input[name=venda]').forEach(rb => rb.onchange = _ => document.getElementById('formVenda').submit());
    document.querySelector('input[name=num_funcs]')?.addEventListener('change', _ => document.getElementById('formNumFuncs').submit());
  </script>
</body>
</html>
