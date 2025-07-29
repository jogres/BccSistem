<?php 
// /_html/_vendas/list.php
include __DIR__ . '/../../_php/_menu/menu.php'; 

if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

// Essas variáveis ($search, $rows, $page, $perPage, $totalPages) vêm do seu controller PHP
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vendas — consórcioBCC</title>
  <link rel="stylesheet" href="/BccSistem/_css/_menu/style.css">
  <link rel="stylesheet" href="/BccSistem/_css/_listas/style.css">
</head>
<body>
  <main class="list-wrapper">
    <header class="list-header">
      <h1>Vendas</h1>

      <form method="get" class="search-form">
        <input 
          type="text" 
          name="search" 
          class="search-input" 
          value="<?= htmlspecialchars($search) ?>" 
          placeholder="Buscar…" 
        >
        <button type="submit" class="btn-secondary">Buscar</button>
      </form>

      <div class="per-page-selector">
        Mostrar 
        <select onchange="location = this.value">
          <?php foreach ([10,25,50,100] as $n): ?>
          <option 
            value="?search=<?= urlencode($search) ?>&page=1&per_page=<?= $n ?>" 
            <?= $perPage === $n ? 'selected' : '' ?>
          >
            <?= $n ?>
          </option>
          <?php endforeach; ?>
        </select>
        por página
      </div>
    </header>

    <table class="list-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Contrato</th>
          <th>Cliente</th>
          <th>Vendedor</th>
          <th>Virador</th>
          <th>Admin.</th>
          <th>Plano</th>
          <th>Modal.</th>
          <th>Valor</th>
          <th>Data</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="12" class="no-data">Nenhuma venda.</td></tr>
        <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= $r['id_venda'] ?></td>
          <td><?= htmlspecialchars($r['numero_contrato']) ?></td>
          <td><?= htmlspecialchars($r['cliente']) ?></td>
          <td><?= htmlspecialchars($r['vendedor']) ?></td>
          <td><?= htmlspecialchars($r['virador']) ?></td>
          <td><?= htmlspecialchars($r['administradora']) ?></td>
          <td><?= htmlspecialchars($r['plano']) ?></td>
          <td><?= htmlspecialchars($r['modalidade']) ?></td>
          <td>R$ <?= number_format($r['valor_total'],2,',','.') ?></td>
          <td><?= date('d/m/Y', strtotime($r['data_venda'])) ?></td>
          <td><?= htmlspecialchars($r['status']) ?></td>
          <td class="actions">
            <a 
              href="/BccSistem/_php/_vendas/delete.php?id=<?= $r['id_venda'] ?>" 
              class="btn-sm btn-secondary"
              onclick="return confirm('Excluir?')"
            >
              Excluir
            </a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a 
        href="?search=<?= urlencode($search) ?>&page=<?= $p ?>&per_page=<?= $perPage ?>" 
        class="page-link <?= $p === $page ? 'active' : '' ?>"
      >
        <?= $p ?>
      </a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
  </main>
</body>
</html>
