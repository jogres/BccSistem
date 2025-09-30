<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/middleware/require_login.php';
require __DIR__ . '/../../app/middleware/require_admin.php';
require __DIR__ . '/../../app/models/Funcionario.php';

$status = $_GET['status'] ?? 'active';
$allowed = ['active','inactive','all'];
if (!in_array($status, $allowed, true)) $status = 'active';

$funcs = Funcionario::all($status);

include __DIR__ . '/../../app/views/partials/header.php';
?>
<div class="card">
  <h1>Funcionários</h1>
  <div class="cluster" style="justify-content:space-between">
    
    <a class="btn secondary" href="<?= e(base_url('funcionarios/create.php')) ?>">Novo funcionário</a>
  </div>

  <form method="get" class="filters mt-3" aria-label="Filtros de funcionários">
    <div>
      <label for="status">Status</label>
      <select class="form-control" id="status" name="status">
        <option value="active"   <?= $status==='active'   ? 'selected' : '' ?>>Somente ativos</option>
        <option value="inactive" <?= $status==='inactive' ? 'selected' : '' ?>>Somente inativos</option>
        <option value="all"      <?= $status==='all'      ? 'selected' : '' ?>>Todos</option>
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
          <th>ID</th><th>Nome</th><th>Login</th><th>Perfil</th><th>Ativo</th><th>Criado em</th><th>Atualizado</th><th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($funcs as $f): ?>
          <tr class="<?= ((int)$f['is_ativo']===0 ? 'row-muted' : '') ?>">
            <td><?= (int)$f['id'] ?></td>
            <td><?= e($f['nome']) ?></td>
            <td><?= e($f['login']) ?></td>
            <td><?= e($f['role_name']) ?></td>
            <td>
              <?php if ((int)$f['is_ativo']===1): ?>
                <span class="badge success">Sim</span>
              <?php else: ?>
                <span class="badge warn">Não</span>
              <?php endif; ?>
            </td>
            <td><?= e($f['created_at']) ?></td>
            <td><?= e($f['updated_at']) ?></td>
            <td>
              <a class="btn secondary" href="<?= e(base_url('funcionarios/edit.php?id=' . (int)$f['id'])) ?>">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>
