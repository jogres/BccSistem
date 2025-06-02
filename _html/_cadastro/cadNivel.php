<?php include('../../_php/_login/logado.php'); ?>
<?php
require_once __DIR__ . '/../../config/db.php';

// Detecta edição de nível
$editing = false;
$nivelEscolhido = '';
$nivelData = [
    'nome'     => '',
    'primeira' => '',
    'segunda'  => '',
    'terceira' => '',
    'quarta'   => '',
    'idAdm'    => ''
];
$idPlano = null;

if (!empty($_GET['nivel']) && isset($_GET['idPlano'])) {
    $nivelEscolhido = $_GET['nivel'];
    $idPlano = (int) $_GET['idPlano'];
    $allowed = ['basic','classic','master'];
    if (in_array($nivelEscolhido, $allowed, true)) {
        $editing = true;
        $pk = $nivelEscolhido === 'basic' ? 'idBasic' : ($nivelEscolhido === 'classic' ? 'idClassic' : 'idMaster');
        $stmt = $pdo->prepare(
            "SELECT nome, primeira, segunda, terceira, quarta, idAdm
             FROM {$nivelEscolhido}
             WHERE {$pk} = ?"
        );
        $stmt->execute([$idPlano]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $nivelData = $row;
        }
    }
}

// Carrega administradoras
ob_start();
include('../../_php/_buscar/_buscaAdm/buscaAdm.php');
$options_adm = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../_css/_menu/menu.css">
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css">
  <title><?= $editing ? 'Editar Nível' : 'Cadastro de Nível' ?></title>
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

    <div class="form-container">
      <form action="../../_php/_cadastro/cadNivel.php" method="post">

        <?php if ($editing): ?>
          <input type="hidden" name="nivelOriginal" value="<?= htmlspecialchars($nivelEscolhido, ENT_QUOTES) ?>">
          <input type="hidden" name="idPlano" value="<?= $idPlano ?>">
        <?php endif; ?>

        <fieldset>
          <legend><?= $editing ? 'Editar Nível' : 'Cadastro de Nível' ?></legend>

          <label for="select-niveis">Nível:</label>
          <select name="select-niveis" id="select-niveis" required>
            <option value="basic"   <?= $nivelEscolhido==='basic'   ? 'selected' : '' ?>>Basic</option>
            <option value="classic" <?= $nivelEscolhido==='classic' ? 'selected' : '' ?>>Classic</option>
            <option value="master"  <?= $nivelEscolhido==='master'  ? 'selected' : '' ?>>Master</option>
          </select>

          <label for="nome">Nome do Plano:</label>
          <input class="nome" type="text" id="nome" name="nome" maxlength="100" required
                 value="<?= htmlspecialchars($nivelData['nome'], ENT_QUOTES) ?>">

          <label for="primeira">1ª (%):</label>
          <input class="primeira" type="number" id="primeira" name="primeira" min="0" max="100" step="0.01" required
                 value="<?= htmlspecialchars($nivelData['primeira'], ENT_QUOTES) ?>">

          <label for="segunda">2ª (%):</label>
          <input class="segunda" type="number" id="segunda" name="segunda" min="0" max="100" step="0.01" required
                 value="<?= htmlspecialchars($nivelData['segunda'], ENT_QUOTES) ?>">

          <label for="terceira">3ª (%):</label>
          <input class="terceira" type="number" id="terceira" name="terceira" min="0" max="100" step="0.01" required
                 value="<?= htmlspecialchars($nivelData['terceira'], ENT_QUOTES) ?>">

          <label for="quarta">4ª (%):</label>
          <input class="quarta" type="number" id="quarta" name="quarta" min="0" max="100" step="0.01" required
                 value="<?= htmlspecialchars($nivelData['quarta'], ENT_QUOTES) ?>">

          <label for="select-adm">Administradora:</label>
          <select name="select-adm" id="select-adm" required>
            <?php
            foreach (explode("\n", trim($options_adm)) as $opt) {
                if (strpos($opt, 'value="' . $nivelData['idAdm'] . '"') !== false) {
                    echo str_replace('<option', '<option selected', $opt);
                } else {
                    echo $opt;
                }
            }
            ?>
          </select>
        </fieldset>

        <div class="form-buttons">
          <button type="submit" class="btn btn-primary">
            <?= $editing ? 'Salvar Alterações' : 'Salvar' ?>
          </button>
          <?php if ($editing): ?>
            <a href="../../_html/_lista/listaNivel.php" class="btn btn-secondary">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <script src="../../_js/_menu/menu.js"></script>
  <script src="../../_js/_cadastro/cadNivel.js"></script>
</body>
</html>