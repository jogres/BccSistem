<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/middleware/require_login.php';

$pdo     = Database::getConnection();
$user    = Auth::user();
$isAdmin = Auth::isAdmin();

// opções
$opcoesInteresse = is_file(__DIR__.'/../../app/config/interesses.php')
  ? require __DIR__.'/../../app/config/interesses.php'
  : [];

// --------- Filtros (GET) ----------
$period   = ($_GET['period'] ?? 'month') === 'all' ? 'all' : 'month'; // padrão = month
$month    = $_GET['m'] ?? date('Y-m'); // AAAA-MM (se month)
$nome     = trim($_GET['f_nome']     ?? '');
$tel      = trim($_GET['f_telefone'] ?? '');
$cidade   = trim($_GET['f_cidade']   ?? '');
$estado   = strtoupper(substr(trim($_GET['f_estado'] ?? ''), 0, 2));
$interesse= trim($_GET['f_interesse'] ?? '');
$q        = trim($_GET['q'] ?? ''); // busca geral
$pp       = (int)($_GET['pp'] ?? 25);
$page     = max(1, (int)($_GET['p'] ?? 1));
$perPage  = in_array($pp, [10,25,50,100], true) ? $pp : 25;
$offset   = ($page - 1) * $perPage;

// --------- WHERE dinâmico ----------
$where = ["c.deleted_at IS NULL"];
$params = [];

if (!$isAdmin) { $where[] = "c.criado_por = :uid"; $params[':uid'] = (int)$user['id']; }

if ($period === 'month' && preg_match('/^\d{4}-\d{2}$/', $month)) {
  // intervalo do mês
  $start = $month . '-01';
  $end   = date('Y-m-d', strtotime('last day of ' . $start));
  $where[] = "c.created_at BETWEEN :start AND :end";
  $params[':start'] = $start . ' 00:00:00';
  $params[':end']   = $end   . ' 23:59:59';
}

if ($nome !== '')      { $where[] = "c.nome LIKE :fn";      $params[':fn'] = "%$nome%"; }
if ($tel !== '')       { $where[] = "c.telefone LIKE :ft";  $params[':ft'] = "%$tel%"; }
if ($cidade !== '')    { $where[] = "c.cidade LIKE :fc";    $params[':fc'] = "%$cidade%"; }
if ($estado !== '')    { $where[] = "c.estado = :fe";       $params[':fe'] = $estado; }
if ($interesse !== '' && in_array($interesse, $opcoesInteresse, true)) {
  $where[] = "c.interesse = :fi"; $params[':fi'] = $interesse;
}
if ($q !== '') {
  // busca ampla (nome/telefone/cidade/estado/interesse)
  $where[] = "(c.nome LIKE :q OR c.telefone LIKE :q OR c.cidade LIKE :q OR c.estado LIKE :q OR c.interesse LIKE :q)";
  $params[':q'] = '%'.str_replace(' ', '%', $q).'%';
}

$whereSql = implode(' AND ', $where);

// --------- COUNT total (para paginação) ----------
$sqlCount = "SELECT COUNT(*) FROM clientes c WHERE $whereSql";
$stCount  = $pdo->prepare($sqlCount);
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

// --------- Consulta paginada ----------
$sql = "SELECT c.id, c.nome, c.telefone, c.cidade, c.estado, c.interesse, c.created_at, f.nome AS criado_por_nome
        FROM clientes c
        JOIN funcionarios f ON f.id=c.criado_por
        WHERE $whereSql
        ORDER BY c.created_at DESC
        LIMIT $perPage OFFSET $offset"; // $perPage/$offset já são ints validados

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

// --------- UI ---------
include __DIR__ . '/../../app/views/partials/header.php';
?>
<div class="card">
  <h1>Clientes</h1>
  <div class="cluster" style="justify-content:space-between">
    <a class="btn secondary" href="<?= e(base_url('clientes/create.php')) ?>">Novo cliente</a>
  </div>
  <form class="filters" method="get">
    <fieldset>
      <legend>Período</legend>
      <label><input type="radio" name="period" value="month" <?= $period==='month'?'checked':'' ?>> Por mês</label>
      <label><input type="radio" name="period" value="all" <?= $period==='all'?'checked':'' ?>> Todos</label>
      <div class="when-month" style="<?= $period==='month'?'':'display:none' ?>">
        <label>Mês
          <input type="month" name="m" value="<?= e($month) ?>">
        </label>
      </div>
    </fieldset>

    <fieldset>
      <legend>Filtros</legend>
      <label>Nome <input type="text" name="f_nome" value="<?= e($nome) ?>"></label>
      <label>Telefone <input type="text" name="f_telefone" value="<?= e($tel) ?>"></label>
      <label>Cidade <input type="text" name="f_cidade" value="<?= e($cidade) ?>"></label>
      <label>Estado
        <select name="f_estado">
          <option value="">UF</option>
          <?php foreach (['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'] as $uf): ?>
            <option value="<?= $uf ?>" <?= $estado===$uf?'selected':'' ?>><?= $uf ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Interesse
        <select name="f_interesse">
          <option value="">Todos</option>
          <?php foreach ($opcoesInteresse as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $interesse===$opt?'selected':'' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Busca geral <input type="text" name="q" value="<?= e($q) ?>" placeholder="nome, telefone, cidade, ..."></label>
      <label>Itens por página
        <select name="pp">
          <?php foreach ([10,25,50,100] as $n): ?>
            <option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn" type="submit">Aplicar</button>
    </fieldset>
  </form>

  <div class="table-wrap mt-2">
    <table class="table">
      <thead>
        <tr>
          <th>#</th><th>Nome</th><th>Telefone</th><th>Cidade</th><th>UF</th><th>Interesse</th><th>Criado por</th><th>Criado em</th><th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $currentMonthHeader = '';
        foreach ($rows as $r):
          $monthHeader = ($period==='month') ? date('m/Y', strtotime($r['created_at'])) : '';
          if ($period==='month' && $monthHeader !== $currentMonthHeader):
            $currentMonthHeader = $monthHeader; ?>
            <tr class="month-divider"><td colspan="9"><?= e($currentMonthHeader) ?></td></tr>
          <?php endif; ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= e($r['nome']) ?></td>
            <td><?= e($r['telefone']) ?></td>
            <td><?= e($r['cidade']) ?></td>
            <td><?= e($r['estado']) ?></td>
            <td><?= e($r['interesse'] ?? '') ?></td>
            <td><?= e($r['criado_por_nome']) ?></td>
            <td><?= e($r['created_at']) ?></td>
            <td>
              <a class="btn secondary" href="<?= e(base_url('clientes/edit.php?id='.(int)$r['id'])) ?>">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- paginação -->
  <div class="pagination">
    <?php
    $qs = $_GET; unset($qs['p']); $base = base_url('clientes/index.php').'?'.http_build_query($qs);
    ?>
    <span>Mostrando <?= $total ? ($offset+1) : 0 ?>–<?= min($offset+$perPage, $total) ?> de <?= $total ?></span>
    <div>
      <?php if ($page>1): ?>
        <a href="<?= e($base.'&p='.($page-1)) ?>">&laquo; Anterior</a>
      <?php endif; ?>
      <span>Página <?= $page ?> de <?= $pages ?></span>
      <?php if ($page<$pages): ?>
        <a href="<?= e($base.'&p='.($page+1)) ?>">Próxima &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// alterna exibição do seletor de mês
document.querySelectorAll('input[name="period"]').forEach(r => {
  r.addEventListener('change', () => {
    const isMonth = document.querySelector('input[name="period"][value="month"]').checked;
    document.querySelector('.when-month').style.display = isMonth ? '' : 'none';
  });
});
</script>

<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>
