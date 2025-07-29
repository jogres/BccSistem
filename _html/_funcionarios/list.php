<?php
// /_consorcioBcc/_html/_funcionarios/list.php
// Variáveis $search, $page, $perPage, $funcionarios e $totalPages vêm do controller.

include __DIR__ . '/../../_php/_menu/menu.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Listar Funcionários — consórcioBCC</title>
  <link rel="stylesheet" href="/_consorcioBcc/_css/_listas/style.css">
  <link rel="stylesheet" href="/_consorcioBcc/_css/_menu/style.css">
</head>
<body>
  <main class="list-wrapper">
    <header class="list-header">
      <h1>Funcionários</h1>
      <form method="get" action="/_consorcioBcc/_php/_funcionarios/list.php" class="search-form">
        <input
          type="text"
          name="search"
          value="<?= htmlspecialchars($search) ?>"
          placeholder="Buscar por nome…"
          class="search-input">
        <button type="submit" class="btn btn-secondary">Buscar</button>
      </form>
      <label>
        Mostrar
        <select name="per_page" onchange="this.form.submit()" class="per-page-select">
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
          <th>Data Nasc.</th>
          <th>CPF</th>
          <th>E-mail</th>
          <th>Cargo</th>
          <th>Ativo</th>
          <th>Papel</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($funcionarios)): ?>
          <tr>
            <td colspan="9" class="no-data">Nenhum funcionário encontrado.</td>
          </tr>
        <?php else: foreach ($funcionarios as $f): ?>
          <tr>
            <td><?= $f['id_funcionario'] ?></td>
            <td><?= htmlspecialchars($f['nome']) ?></td>
            <td>
              <?= 
                !empty($f['data_nascimento'])
                  ? date('d/m/Y', strtotime($f['data_nascimento']))
                  : '—'
              ?>
            </td>
            <td><?= htmlspecialchars($f['cpf']) ?></td>
            <td><?= htmlspecialchars($f['email']) ?></td>
            <td><?= htmlspecialchars($f['cargo']) ?></td>
            <td><?= $f['ativo'] ? 'Sim' : 'Não' ?></td>
            <td><?= htmlspecialchars($f['papel']) ?></td>
            <td class="actions">
              <a
                href="/_consorcioBcc/_html/_funcionarios/form.php?id=<?= $f['id_funcionario'] ?>"
                class="btn btn-primary btn-sm">
                Editar
              </a>
              <a
                href="/_consorcioBcc/_html/_funcionarios/commissions.php?id=<?= $f['id_funcionario'] ?>"
                class="btn btn-secondary btn-sm">
                Comissões
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
          class="page-link <?= $p === $page ? 'active' : '' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
  </main>

  <script src="/_consorcioBcc/_js/_listas/list.js"></script>
</body>
</html>
