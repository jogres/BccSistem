<?php 

  include __DIR__ . '/../../_php/_menu/menu.php'; 
  if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
  }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Clientes — consórcioBCC</title>
  <link rel="stylesheet" href="/_consorcioBcc/_css/_menu/style.css">
  <link rel="stylesheet" href="/_consorcioBcc/_css/_listas/style.css">
</head>
<body>
  
  <main class="list-wrapper">
    <header class="list-header">
      <h1>Clientes</h1>
      <form method="get" class="search-form">
        <input type="text"
               name="search"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Buscar nome, telefone ou motivo…"
               class="search-input">
        <button type="submit" class="btn btn-secondary">Buscar</button>
      </form>
      <label>
        Mostrar
        <select name="per_page" class="per-page-select">
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
          <th>Nome</th>
          <th>Telefone</th>
          <th>Motivo</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="5" class="no-data">Nenhum registro encontrado.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= $r['id_cliente'] ?></td>
            <td><?= htmlspecialchars($r['nome']) ?></td>
            <td><?= htmlspecialchars($r['telefone']) ?></td>
            <td><?= nl2br(htmlspecialchars($r['motivo'])) ?></td>
            <td class="actions">
              <a href="/_consorcioBcc/_html/_clientes/form.php?id=<?= $r['id_cliente'] ?>"
                 class="btn btn-primary btn-sm">Editar</a>
              <a href="/_consorcioBcc/_php/_clientes/delete.php?id=<?= $r['id_cliente'] ?>"
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