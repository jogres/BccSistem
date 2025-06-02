<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

// 1) Detecção de edição
$editing = false;
$idCli   = null;
$cliente = ['nome'=>'','cpf'=>'','endereco'=>'','telefone'=>'','descricao'=>''];
$sale    = ['idAdm'=>null,'fun_ids'=>[],'idVenda'=>'','select_tipo'=>'Normal','valor'=>'','data'=>date('Y-m-d')];




// 2) Captura POST para etapas
$postVenda   = $_POST['venda']     ?? ($_SESSION['venda'] ?? 'Nao');
$postNumFunc = max(1, (int)($_POST['num_funcs'] ?? 1));

// 3) Se for edição, carrega dados existentes e força venda=Sim
if (isset($_GET['idCli']) && is_numeric($_GET['idCli'])) {
    $editing = true;
    $idCli   = (int) $_GET['idCli'];
    // Cliente
    $stmt = $pdo->prepare("SELECT nome, cpf, endereco, telefone, tipo, descricao FROM cad_cli WHERE idCli = ?");
    $stmt->execute([$idCli]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cliente = [
            'nome'     => $row['nome'],
            'cpf'      => $row['cpf'],
            'endereco' => $row['endereco'],
            'telefone' => $row['telefone'],
            'descricao'=> $row['descricao'] ?? '',
            'tipo'     => $row['tipo'] === 'com_venda' ? 'Sim' : 'Nao'
        ];
        $postVenda = 'Sim';
    }
    // Venda
    $stmt = $pdo->prepare(
        "SELECT v.idAdm, v.idVenda, v.tipo, v.valor, v.dataV
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
        // Vendedores
        $stmt2 = $pdo->prepare("SELECT idFun FROM venda_fun WHERE idVenda = ?");
        $stmt2->execute([$v['idVenda']]);
        $sale['fun_ids'] = array_column($stmt2->fetchAll(), 'idFun');
        $postNumFunc = count($sale['fun_ids']) ?: 1;
    }
}

// 4) Persiste venda na sessão
$_SESSION['venda'] = $postVenda;

// 5) Lógica de exibição
$vendaSelecionada = $postVenda;
$mostrarVenda     = ($vendaSelecionada === 'Sim');
$num_funcs        = $postNumFunc;

// 6) Carrega opções para selects
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
  <title><?= $editing ? 'Editar Cliente' : 'Cadastro de Cliente' ?></title>
</head>
<body>
  <div class="container">
    <!-- Menu omitido -->
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
    
    <!-- Etapa 1: Venda Sim/Não -->
    <form id="formVenda" action="?idCli=<?= $editing ? $idCli : '' ?>" method="post" class="radio-group">
      <?php if ($editing): ?><input type="hidden" name="idCli" value="<?= $idCli ?>" /><?php endif; ?>
      <label>Venda:</label>
      <input type="radio" name="venda" value="Sim" <?= $vendaSelecionada==='Sim' ? 'checked' : '' ?> /> Sim
      <input type="radio" name="venda" value="Nao" <?= $vendaSelecionada==='Nao' ? 'checked' : '' ?> /> Não
      <input type="hidden" name="num_funcs" value="<?= $num_funcs ?>" />
      <script>
        document.querySelectorAll('#formVenda input[name=venda]').forEach(rb =>
          rb.addEventListener('change', () => document.getElementById('formVenda').submit())
        );
      </script>
    </form>

    <!-- Etapa 2: Número de funcionários -->
    <?php if ($vendaSelecionada === 'Sim'): ?>
        <form id="formNumFuncs" action="?idCli=<?= $editing ? $idCli : '' ?>" method="post" class="inline-group">
          <?php if ($editing): ?><input type="hidden" name="idCli" value="<?= $idCli ?>" /><?php endif; ?>
          <input type="hidden" name="venda" value="<?= $vendaSelecionada ?>" />
          <label>Quantos Vendedores?</label>
          <input class="num-funcs" type="number" name="num_funcs" min="1" value="<?= $num_funcs ?>" required />
          <script>
            document.querySelector('#formNumFuncs input[name=num_funcs]').addEventListener('change', () =>
              document.getElementById('formNumFuncs').submit()
            );
          </script>
        </form>
    <?php endif; ?>

    <!-- Etapa 3: Formulário Cliente e Venda -->
    <form action="../../_php/_cadastro/cadCli.php" method="post">
      <?php if ($editing): ?><input type="hidden" name="idCli" value="<?= $idCli ?>" /><?php endif; ?>
      <input type="hidden" name="venda" value="<?= $vendaSelecionada ?>" />
      <input type="hidden" name="num_funcs" value="<?= $num_funcs ?>" />

      <fieldset>
        <legend><?= $editing ? 'Editar Cliente' : 'Cadastrar Cliente' ?><?= $mostrarVenda ? ' e Venda' : '' ?></legend>

        <label>Nome:</label>
        <input class="nome" type="text" name="nome" required value="<?= htmlspecialchars($cliente['nome']) ?>" />

        <label>CPF:</label>
        <input class="cpf" type="text" name="cpf" pattern="\d{11}" required value="<?= htmlspecialchars($cliente['cpf']) ?>" />

          <?php if (!$editing): ?>
            <div class="endereco-group">
              <label for="rua">Rua:</label>
              <input class="endereco"
                type="text" name="rua" id="rua" maxlength="100" required
              />

              <label for="numero">Número:</label>
              <input class="endereco"
                type="text" name="numero" id="numero" maxlength="10" required

              />

              <label for="bairro">Bairro:</label>
              <input class="endereco"
                type="text" name="bairro" id="bairro" maxlength="50" required
              />

              <label for="cidade">Cidade:</label>
              <input class="endereco"
                type="text" name="cidade" id="cidade" maxlength="50" required

              />

              <label for="estado">Estado:</label>
              <input class="endereco"
                type="text" name="estado" id="estado" maxlength="2" required
                placeholder="UF"

              />

              <label for="cep">CEP:</label>
              <input class="endereco"
                type="text" name="cep" id="cep" maxlength="9" required
                placeholder="00000-000"
                />
            </div>
          <?php else: ?>
            <label for="endereco">Endereço:</label>
            <textarea name="endereco" id="endereco" required><?= htmlspecialchars($cliente['endereco']) ?></textarea>
          <?php endif; ?>
        <label for="descricao">Descrição</label>
        <textarea name="descricao" id="descricao" required><?= htmlspecialchars($cliente['descricao']) ?></textarea>  

        <label>Telefone:</label>
        <input class="telefone" type="text" name="telefone" required value="<?= htmlspecialchars($cliente['telefone']) ?>" />

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
          <input class="contrato" type="number" name="idVenda" required value="<?= htmlspecialchars($sale['idVenda']) ?>" />

          <label>Tipo:</label>
          <select name="select_tipo">
            <option <?= $sale['select_tipo']==='Normal'?'selected':'' ?>>Normal</option>
            <option <?= $sale['select_tipo']==='2%'?'selected':'' ?>>2%</option>
          </select>

          <label>Valor:</label>
          <input class="valor" type="number" name="valor" step="0.01" required value="<?= htmlspecialchars($sale['valor']) ?>" />

          <label>Data:</label>
          <input class="data" type="date" name="data" required value="<?= htmlspecialchars($sale['data']) ?>" />
        <?php endif; ?>
      </fieldset>

      <div class="form-buttons">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Salvar Alterações' : 'Cadastrar' ?></button>
        <?php if ($editing): ?><a href="../../_html/_lista/listaCli.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>
  <script src="../../_js/_cadastro/cadCli.js"></script>
  <script src="../../_js/_menu/menu.js"></script>
</body>
</html>
