<?php include __DIR__ . '/../../_php/_menu/menu.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Administradoras — consórcioBCC</title>
  <link rel="stylesheet" href="/_consorcioBcc/_css/_menu/style.css">
  <link rel="stylesheet" href="/_consorcioBcc/_css/_listas/style.css">
</head>
<body>
  <main class="list-wrapper">
    <header class="list-header">
      <h1>Administradoras</h1>
      <form method="get" action="/_consorcioBcc/_php/_administradoras/list.php" class="search-form">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por nome…" class="search-input">
        <button type="submit" class="btn btn-secondary">Buscar</button>
      </form>
      <label>
        Mostrar
        <select name="per_page" class="per-page-select">
          <?php foreach ([10,25,50,100] as $n): ?>
            <option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select> por página
      </label>
    </header>

    <table class="list-table">
      <thead>
        <tr><th>ID</th><th>Nome</th><th>CNPJ</th><th>Ações</th></tr>
      </thead>
      <tbody>
        <?php if (empty($admins)): ?>
          <tr><td colspan="4" class="no-data">Nenhuma administradora encontrada.</td></tr>
        <?php else: foreach ($admins as $a): ?>
        <tr>
          <td><?= $a['id_administradora'] ?></td>
          <td><?= htmlspecialchars($a['nome']) ?></td>
          <td><?= htmlspecialchars($a['cnpj']) ?></td>
          <td class="actions">
            <a href="/_consorcioBcc/_html/_administradoras/form.php?id=<?= $a['id_administradora'] ?>"
               class="btn btn-primary btn-sm">Editar</a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination">
      <?php for ($p=1;$p<=$totalPages;$p++): ?>
      <a href="?search=<?= urlencode($search) ?>&page=<?= $p ?>&per_page=<?= $perPage ?>"
         class="page-link <?= $p===$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
  </main>
  <script src="/_consorcioBcc/_js/_listas/list.js"></script>
</body>
</html>