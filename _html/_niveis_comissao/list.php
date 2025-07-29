<?php include __DIR__ . '/../../_php/_menu/menu.php'; 

if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Percentuais por Administradora/Nível</title>
  <link rel="stylesheet" href="/_consorcioBcc/_css/_menu/style.css">
  <link rel="stylesheet" href="/_consorcioBcc/_css/_listas/style.css">
</head>
<body>
  <main class="list-wrapper">
    <header class="list-header">
      <h1>Percentuais por Administradora e Nível</h1>
      <form method="get" action="/_consorcioBcc/_php/_niveis_comissao/list.php" class="search-form">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar…" class="search-input">
        <button type="submit" class="btn btn-secondary">Buscar</button>
      </form>
      <label>Mostrar
        <select name="per_page" class="per-page-select">
          <?php foreach ([10,25,50,100] as $n): ?>
            <option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select> por página
      </label>
    </header>

    <table class="list-table">
      <thead>
        <tr>
          <th>Adm.</th><th>Nível</th><th>Vendas Min</th><th>Vendas Max</th><th>% Comissão</th><th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="no-data">Nenhum registro encontrado.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['administradora']) ?></td>
          <td><?= $r['nivel'] ?></td>
          <td><?= number_format($r['vendas_min'],2,',','.') ?></td>
          <td><?= number_format($r['vendas_max'],2,',','.') ?></td>
          <td><?= number_format($r['percentual'],2,',','.') ?>%</td>
          <td class="actions">
            <a href="/_consorcioBcc/_html/_niveis_comissao/form.php?id=<?= $r['id_adm_nivel'] ?>" class="btn btn-primary btn-sm">Editar</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages>1): ?>
      <nav class="pagination">
        <?php for($p=1;$p<=$totalPages;$p++): ?>
          <a href="?search=<?=urlencode($search)?>&page=<?=$p?>&per_page=<?=$perPage?>"
             class="page-link <?=$p===$page?'active':''?>"><?=$p?></a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  </main>
</body>
</html>