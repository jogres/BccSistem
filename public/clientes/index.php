<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/middleware/require_login.php';

$pdo     = Database::getConnection();
$user    = Auth::user();
$isAdmin = Auth::isAdmin();

// (Opcional) lista branca de interesses para filtro
$opcoesInteresse = is_file(__DIR__.'/../../app/config/interesses.php')
  ? require __DIR__.'/../../app/config/interesses.php'
  : [];

// ------------ Filtros ------------
$f_interesse = trim($_GET['f_interesse'] ?? '');
if ($f_interesse !== '' && !in_array($f_interesse, $opcoesInteresse, true)) {
  // valor inválido no filtro: ignora
  $f_interesse = '';
}

$where  = "c.deleted_at IS NULL";
$params = [];

// Regra de acesso: PADRAO/APRENDIZ só veem o que criaram
if (!$isAdmin) {
  $where .= " AND c.criado_por = :uid";
  $params[':uid'] = (int)$user['id'];
}

// Filtro por interesse (opcional)  // NOVO
if ($f_interesse !== '') {
  $where .= " AND c.interesse = :interesse";
  $params[':interesse'] = $f_interesse;
}

$sql = "SELECT
          c.id, c.nome, c.telefone, c.cidade, c.estado,
          c.interesse,                      -- NOVO (seleciona o campo)
          c.created_at,
          f.nome AS criado_por_nome
        FROM clientes c
        JOIN funcionarios f ON f.id = c.criado_por
        WHERE $where
        ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

include __DIR__ . '/../../app/views/partials/header.php';
?>
<div class="card">
  <div class="cluster" style="justify-content:space-between">
    <h1>Clientes</h1>
    <a class="btn" href="<?= e(base_url('clientes/create.php')) ?>">Novo cliente</a>
  </div>

  <!-- Filtros (opcional) -->
  <form method="get" class="filters mt-3" aria-label="Filtros da listagem de clientes">
    <div>
      <label for="f_interesse">Interesse</label>
      <select class="form-control" id="f_interesse" name="f_interesse">
        <option value="">Todos</option>
        <?php foreach ($opcoesInteresse as $opt): ?>
          <option value="<?= e($opt) ?>" <?= $opt===$f_interesse?'selected':'' ?>><?= e($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <button class="btn" type="submit">Aplicar</button>
    </div>
  </form>

  <div class="table-wrap mt-3">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>Telefone</th>
          <th>Cidade</th>
          <th>Estado</th>
          <th>Interesse</th>         <!-- NOVO: coluna -->
          <th>Criado por</th>
          <th>Criado em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['nome']) ?></td>
          <td><?= e($r['telefone']) ?></td>
          <td><?= e($r['cidade']) ?></td>
          <td><?= e($r['estado']) ?></td>
          <td><?= e($r['interesse'] ?? '') ?></td>   <!-- NOVO: imprime o interesse -->
          <td><?= e($r['criado_por_nome']) ?></td>
          <td><?= e($r['created_at']) ?></td>
          <td>
            <a class="btn secondary" href="<?= e(base_url('clientes/edit.php?id='.(int)$r['id'])) ?>">Editar</a>
            <!-- se houver exclusão lógica/soft delete, coloque aqui -->
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>
