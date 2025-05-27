<?php include('../../_php/_login/logado.php'); ?>
<?php
require_once __DIR__ . '/../../config/db.php';

// 1) Detecta edição de Administradora
$editing = false;
$admData = [
    'idAdm' => '',
    'nome'  => '',
    'cnpj'  => ''
];

if (isset($_GET['idAdm']) && is_numeric($_GET['idAdm'])) {
    $editing = true;
    $idAdm = (int) $_GET['idAdm'];
    $stmt  = $pdo->prepare("
        SELECT idAdm, nome, cnpj
          FROM cad_adm
         WHERE idAdm = ?
    ");
    $stmt->execute([$idAdm]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $admData = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../../_css/_menu/menu.css" />
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css" />
  <title><?= $editing ? 'Editar Administradora' : 'Cadastro de Administradora' ?></title>
</head>
<body>
  <div class="container">
    <!-- Menu de navegação -->
    <nav class="main-nav" role="navigation">
      <button class="menu-toggle" aria-label="Abrir menu">&#9776;</button>
      <ul class="nav-links">
        <?php foreach ($menu as $link => $nome): ?>
          <li class="nav-item">
            <a href="<?= $link ?>" class="nav-link"><?= $nome ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-user-actions">
        <span class="user-name"><?= htmlspecialchars($nomeP) ?></span>
        <form action="../../_php/_login/deslogar.php" method="post" class="logout-form">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>

    <!-- Formulário de Administradora -->
    <div class="form-container">
      <form action="../../_php/_cadastro/cadAdm.php" method="post">
        <!-- Flag e ID ocultos em modo edição -->
        <?php if ($editing): ?>
          <input type="hidden" name="editing" value="1" />
          <input type="hidden" name="idAdm"    value="<?= $admData['idAdm'] ?>" />
        <?php endif; ?>

        <fieldset>
          <legend>
            <?= $editing ? 'Editar Administradora' : 'Cadastro de Administradora' ?>
          </legend>

          

          <label for="nome">Nome:</label>
          <input
            type="text"
            name="nome"
            id="nome"
            maxlength="150"
            required
            value="<?= htmlspecialchars($admData['nome'], ENT_QUOTES) ?>"
          />

          <label for="cnpj">CNPJ:</label>
          <input
            type="text"
            name="cnpj"
            id="cnpj"
            maxlength="18"
            required
            pattern="\d{2}\.?\d{3}\.?\d{3}/?\d{4}-?\d{2}"
            title="Digite um CNPJ válido (14 dígitos)"
            value="<?= htmlspecialchars($admData['cnpj'], ENT_QUOTES) ?>"
          />
        </fieldset>

        <div class="form-buttons">
          <button type="submit" class="btn btn-primary">
            <?= $editing ? 'Salvar Alterações' : 'Cadastrar' ?>
          </button>
          <?php if ($editing): ?>
            <a href="../../_html/_lista/listaAdm.php" class="btn btn-secondary">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Toggle do menu em mobile
    document.querySelector('.menu-toggle').addEventListener('click', () => {
      document.querySelector('.nav-links').classList.toggle('open');
    });
  </script>
</body>
</html>
