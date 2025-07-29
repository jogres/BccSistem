<?php
// /_consorcioBcc/_html/_planos_comissao/form.php
require __DIR__ . '/../../_php/shared/verify_session.php';
include_once __DIR__ . '/../../_php/_menu/menu.php';
include    __DIR__ . '/../../_php/_planos_comissao/form.php';
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
    <?= $isEdit
         ? "Editar Plano — " . htmlspecialchars($nome_plano)
         : "Cadastrar Novo Plano de Comissão" ?>
  </title>
  <link rel="stylesheet" href="/BccSistem/_css/_menu/style.css">
  <link rel="stylesheet" href="/BccSistem/_css/_cadastro/style.css">
</head>
<body id="emp-create-body">
  <main id="emp-create-wrapper">
    <header id="emp-create-header">
      <h1 id="emp-create-heading">
        <?= $isEdit ? 'Editar Plano de Comissão' : 'Novo Plano de Comissão' ?>
      </h1>
    </header>

    <?php if (!empty($_GET['error'])): ?>
      <div id="emp-create-error" class="alert alert-error">
        <?= htmlspecialchars($_GET['error']) ?>
      </div>
    <?php endif; ?>

    <form id="emp-create-form" class="form"
          action="/BccSistem/_php/_planos_comissao/process.php"
          method="post" novalidate>
      <?php if ($isEdit): ?>
        <input type="hidden"
               name="id_plano_comissao"
               value="<?= $id_plano_comissao ?>">
      <?php endif; ?>

      <!-- Administradora -->
      <div class="form-group">
        <label for="id_administradora" class="form-label">Administradora</label>
        <select id="id_administradora"
                name="id_administradora"
                class="form-input"
                required>
          <option value="">Selecione…</option>
          <?php foreach ($administradoras as $adm): ?>
            <option value="<?= $adm['id_administradora'] ?>"
              <?= $adm['id_administradora']==$id_administradora?'selected':''?>>
              <?= htmlspecialchars($adm['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Nome do Plano -->
      <div class="form-group">
        <label for="nome_plano" class="form-label">Nome do Plano</label>
        <input type="text"
               id="nome_plano"
               name="nome_plano"
               class="form-input"
               value="<?= htmlspecialchars($nome_plano) ?>"
               required maxlength="100">
      </div>

      <!-- Número de Parcelas -->
      <div class="form-group">
        <label for="num_parcelas_comiss" class="form-label">
          Número de Parcelas
        </label>
        <input type="number"
               id="num_parcelas_comiss"
               name="num_parcelas_comiss"
               class="form-input"
               value="<?= htmlspecialchars($num_parcelas_comiss) ?>"
               required min="1" step="1">
      </div>

      <!-- Modalidade -->
      <div class="form-group">
        <label for="modalidade" class="form-label">Modalidade</label>
        <select id="modalidade"
                name="modalidade"
                class="form-input"
                required>
          <?php foreach ($modalidades as $mod): ?>
            <option value="<?= $mod ?>"
              <?= $mod===$modalidade?'selected':''?>>
              <?= $mod ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group form-group-submit">
        <button type="submit" class="btn btn-primary">
          <?= $isEdit ? 'Atualizar Plano' : 'Salvar Plano' ?>
        </button>
      </div>
    </form>
  </main>
</body>
</html>
