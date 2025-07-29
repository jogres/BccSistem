<?php
require __DIR__ . '/../../_php/shared/verify_session.php';
include_once __DIR__ . '/../../_php/_menu/menu.php';
include    __DIR__ . '/../../_php/_niveis_comissao/form.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>
    <?= $isEdit
         ? "Editar Comissão: {$administradoras[array_search($id_administradora,array_column($administradoras,'id_administradora'))]['nome']} / Nível {$nivel}"
         : "Cadastrar Comissão por Administradora e Nível" ?>
  </title>
  <link rel="stylesheet" href="/_consorcioBcc/_css/_menu/style.css">
  <link rel="stylesheet" href="/_consorcioBcc/_css/_cadastro/style.css">
</head>
<body id="emp-create-body">
  <main id="emp-create-wrapper">
    <header id="emp-create-header">
      <h1 id="emp-create-heading">
        <?= $isEdit ? "Editar Percentual" : "Novo Percentual" ?>
      </h1>
    </header>

    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form id="emp-create-form"
          class="form"
          action="/_consorcioBcc/_php/_niveis_comissao/process.php"
          method="post" novalidate>
      <?php if ($isEdit): ?>
        <input type="hidden" name="id_adm_nivel" value="<?= $id_adm_nivel ?>">
      <?php endif; ?>

      <div class="form-group">
        <label for="id_administradora" class="form-label">Administradora</label>
        <select id="id_administradora" name="id_administradora" class="form-input" required>
          <option value="">Selecione…</option>
          <?php foreach ($administradoras as $adm): ?>
            <option value="<?= $adm['id_administradora'] ?>"
              <?= $adm['id_administradora']==$id_administradora?'selected':''?>>
              <?= htmlspecialchars($adm['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="nivel" class="form-label">Nível</label>
        <select id="nivel" name="nivel" class="form-input" required>
          <option value="">Selecione…</option>
          <?php foreach ($niveis as $n): ?>
            <option value="<?= $n['nivel'] ?>"
              <?= $n['nivel']==$nivel?'selected':''?>>
              Nível <?= $n['nivel'] ?>
              (<?= number_format($n['vendas_min'],2,',','.') ?> – <?= number_format($n['vendas_max'],2,',','.') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="percentual" class="form-label">% Comissão</label>
        <input type="number"
               id="percentual" name="percentual"
               class="form-input"
               value="<?= htmlspecialchars($percentual) ?>"
               required min="0" step="0.01">
      </div>

      <div class="form-group form-group-submit">
        <button type="submit" class="btn btn-primary">
          <?= $isEdit ? 'Atualizar Percentual' : 'Salvar Percentual' ?>
        </button>
      </div>
    </form>
  </main>
</body>
</html>
