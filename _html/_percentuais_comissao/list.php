<?php 
// /_consorcioBcc/_html/_percentuais_comissao/list.php

include __DIR__ . '/../../_php/_menu/menu.php'; 

if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Percentuais de Comissão — consórcioBCC</title>
  <link rel="stylesheet" href="/BccSistem/_css/_menu/style.css">
  <link rel="stylesheet" href="/BccSistem/_css/_listas/style.css">
</head>
<body>
  <main class="list-wrapper">
    <header class="list-header">
      <h1>Percentuais de Comissão</h1>
      <form method="get" action="" class="search-form">
        <input type="text"
               name="search"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Buscar plano, administradora, parcela ou nível…"
               class="search-input">
        <button type="submit" class="btn btn-secondary">Buscar</button>
      </form>
      <label>
        Mostrar
        <select name="per_page" class="per-page-select" onchange="this.form.submit()">
          <?php foreach ([10,25,50,100] as $n): ?>
            <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>>
              <?= $n ?>
            </option>
          <?php endforeach; ?>
        </select>
        por página
      </label>
    </header>

    <table class="list-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Adm.</th>
          <th>Plano</th>
          <th>Nível</th>
          <th>Parcela</th>
          <th>% Comissão</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="7" class="no-data">Nenhum registro encontrado.</td>
          </tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= $r['id_percentual'] ?></td>
            <td><?= htmlspecialchars($r['administradora']) ?></td>
            <td><?= htmlspecialchars($r['plano']) ?></td>
            <td><?= htmlspecialchars($r['nivel_nome'] ?? '—') ?></td>
            <td><?= $r['numero_parcela'] ?></td>
            <td><?= number_format($r['percentual'], 2, ',', '.') ?>%</td>
            <td class="actions">
              <a href="/BccSistem/_html/_percentuais_comissao/form.php?id=<?= $r['id_percentual'] ?>"
                 class="btn btn-primary btn-sm">Editar</a>
              <a href="/BccSistem/_php/_percentuais_comissao/delete.php?id=<?= $r['id_percentual'] ?>"
                 onclick="return confirm('Confirma exclusão?')"
                 class="btn btn-secondary btn-sm">Excluir</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <nav class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="?search=<?= urlencode($search) ?>&page=<?= $p ?>&per_page=<?= $perPage ?>"
             class="page-link <?= $p === $page ? 'active' : '' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  </main>
</body>
</html>
