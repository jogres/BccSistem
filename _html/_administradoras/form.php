<?php
// /_consorcioBcc/_html/_funcionarios/form.php
require __DIR__ . '/../../_php/shared/verify_session.php';
include __DIR__ . '/../../_php/_menu/menu.php';
if (!empty($_GET['id'])) {
  include __DIR__ . '/../../_php/_administradoras/form.php';
}
if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>
    <?= !empty($_GET['id'])
         ? "Editar Administradora — " . htmlspecialchars($nome)
         : "Cadastrar Nova Administradora" ?>
  </title>
  <link rel="stylesheet" href="/BccSistem/_css/_menu/style.css">
  <link rel="stylesheet" href="/BccSistem/_css/_cadastro/style.css">
</head>
<body id="emp-create-body">
  <main id="emp-create-wrapper">
    <header id="emp-create-header">
      <h1 id="emp-create-heading">
        <?= !empty($_GET['id'])
             ? "Editar Administradora"
             : "Cadastrar Nova Administradora" ?>
      </h1>
    </header>

    <?php if (!empty($_GET['error'])): ?>
      <div id="emp-create-error" class="alert alert-error">
        <?= htmlspecialchars($_GET['error']) ?>
      </div>
    <?php endif; ?>

    <form id="emp-create-form"
          class="form"
          action="/BccSistem/_php/_administradoras/process.php"
          method="post"
          novalidate>
      <?php if (!empty($_GET['id'])): ?>
        <input type="hidden" name="id_administradora" value="<?= $id ?>">
      <?php endif; ?>

      <div class="form-group">
        <label for="nome" class="form-label">Nome da Administradora</label>
        <input type="text"
               id="nome"
               name="nome"
               class="form-input"
               value="<?= htmlspecialchars($nome ?? '') ?>"
               required
               maxlength="150">
      </div>

      <div class="form-group">
        <label for="cnpj" class="form-label">CNPJ</label>
        <input type="text"
               id="cnpj"
               name="cnpj"
               class="form-input"
               value="<?= htmlspecialchars($cnpj ?? '') ?>"
               required
               placeholder="00.000.000/0000-00"
               maxlength="18">
      </div>

      <div class="form-group form-group-submit">
        <button type="submit" class="btn btn-primary">
          <?= !empty($_GET['id']) ? 'Atualizar Administradora' : 'Salvar Administradora' ?>
        </button>
      </div>
    </form>
  </main>

  <script src="/BccSistem/_js/_cadastro/administradoras-form.js"></script>
</body>
</html>