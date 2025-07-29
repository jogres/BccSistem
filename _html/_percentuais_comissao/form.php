<?php
// /_consorcioBcc/_html/_percentuais_comissao/form.php
require __DIR__ . '/../../_php/shared/verify_session.php';
include __DIR__ . '/../../_php/_menu/menu.php';
include __DIR__ . '/../../_php/_percentuais_comissao/form.php';

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
  <title><?= $isEdit ? 'Editar Percentuais' : 'Novos Percentuais' ?></title>
  <link rel="stylesheet" href="/BccSistem/_css/_menu/style.css">
  <link rel="stylesheet" href="/BccSistem/_css/_cadastro/style.css">
</head>
<body id="emp-create-body">
  <main id="emp-create-wrapper">
    <header id="emp-create-header">
      <h1 id="emp-create-heading">
        <?= $isEdit ? 'Editar Percentuais de Comissão' : 'Cadastrar Percentuais de Comissão' ?>
      </h1>
    </header>

    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-error">
        <?= htmlspecialchars($_GET['error']) ?>
      </div>
    <?php endif; ?>

    <!-- 1) Seleção de Plano (GET) -->
    <form id="plan-selector" method="get" action="">
      <div class="form-group">
        <label for="id_plano_comissao" class="form-label">Plano de Comissão</label>
        <select id="id_plano_comissao" name="plano" class="form-input" required>
          <option value="">— escolha um plano —</option>
          <?php foreach ($planos as $p): ?>
            <option value="<?= $p['id_plano_comissao'] ?>"
                    <?= $p['id_plano_comissao']==$id_plano_comissao?'selected':''?>>
              <?= htmlspecialchars($p['nome_plano']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <?php if ($id_plano_comissao && $numParcelas>0): ?>
      <!-- 2) Formulário de Percentuais (POST) -->
      <form id="emp-create-form" class="form"
            action="/BccSistem/_php/_percentuais_comissao/process.php"
            method="post" novalidate>
        <input type="hidden" name="id_plano_comissao" value="<?= $id_plano_comissao ?>">

        <!-- Seleção única de Nível -->
        <div class="form-group">
          <label for="id_nivel_comissao" class="form-label">Nível de Comissão (válido para todas as parcelas)</label>
          <select id="id_nivel_comissao" name="id_nivel_comissao" class="form-input" required>
            <option value="">— selecione nível —</option>
            <?php foreach ($niveis as $n): ?>
              <option value="<?= $n['id'] ?>"
                <?= (isset($currentNivel) && $currentNivel==$n['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($n['nivel']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Loop de parcelas: apenas exibe número e campo Perc. -->
        <?php for ($i = 1; $i <= $numParcelas; $i++): ?>
          <div class="form-group">
            <label for="pct_<?= $i ?>" class="form-label">Parcela <?= $i ?> (%)</label>
            <input type="hidden" name="numero_parcela[]" value="<?= $i ?>">
            <input type="number"
                   id="pct_<?= $i ?>"
                   name="percentual[]"
                   class="form-input"
                   value="<?= htmlspecialchars($percentuais[$i] ?? '') ?>"
                   required min="0" step="0.01">
          </div>
        <?php endfor; ?>

        <div class="form-group form-group-submit">
          <button type="submit" class="btn btn-primary">
            <?= $isEdit ? 'Atualizar Todos' : 'Salvar Todos' ?>
          </button>
        </div>
      </form>
    <?php endif; ?>
  </main>

  <script>
    // Reenvia GET ao mudar de plano
    document.getElementById('id_plano_comissao')
      .addEventListener('change', () => document.getElementById('plan-selector').submit());
  </script>
</body>
</html>
