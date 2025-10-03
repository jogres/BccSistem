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

// Calcular estatísticas
$totalFuncs = count($funcs);
$activeFuncs = count(array_filter($funcs, fn($f) => (int)$f['is_ativo'] === 1));
$inactiveFuncs = $totalFuncs - $activeFuncs;
?>
<div class="main-container">
  <div class="employees-container">
    <!-- Cabeçalho -->
    <div class="employees-header">
      <div>
        <h1 class="employees-title">🧑‍💼 Funcionários</h1>
        <p class="employees-subtitle">Gestão completa da equipe</p>
      </div>
      <div class="employees-actions">
        <a class="btn-new-employee" href="<?= e(base_url('funcionarios/create.php')) ?>">
          ➕ Novo Funcionário
        </a>
      </div>
    </div>

    <!-- Estatísticas -->
    <div class="employees-stats">
      <div class="stat-card total">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?= $totalFuncs ?></div>
        <div class="stat-label">Total</div>
      </div>
      <div class="stat-card active">
        <div class="stat-icon">✅</div>
        <div class="stat-number"><?= $activeFuncs ?></div>
        <div class="stat-label">Ativos</div>
      </div>
      <div class="stat-card inactive">
        <div class="stat-icon">❌</div>
        <div class="stat-number"><?= $inactiveFuncs ?></div>
        <div class="stat-label">Inativos</div>
      </div>
    </div>

    <!-- Filtros -->
    <form method="get" class="employees-filters" aria-label="Filtros de funcionários">
      <div class="employees-filters-grid">
        <div class="filter-group">
          <label class="filter-label" for="status">📊 Status</label>
          <select class="filter-control" id="status" name="status">
            <option value="active"   <?= $status==='active'   ? 'selected' : '' ?>>✅ Somente ativos</option>
            <option value="inactive" <?= $status==='inactive' ? 'selected' : '' ?>>❌ Somente inativos</option>
            <option value="all"      <?= $status==='all'      ? 'selected' : '' ?>>🔄 Todos</option>
          </select>
        </div>
        <div></div>
        <div class="filter-group">
          <button class="btn-filter" type="submit">
            🔍 Filtrar
          </button>
        </div>
      </div>
    </form>

    <!-- Tabela de funcionários -->
    <div class="employees-table-container">
      <table class="employees-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>👤 Nome</th>
            <th>🔑 Login</th>
            <th>👔 Perfil</th>
            <th>✅ Status</th>
            <th>📅 Criado em</th>
            <th>🔄 Atualizado</th>
            <th>⚙️ Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($funcs as $f): ?>
            <tr class="<?= ((int)$f['is_ativo']===0 ? 'row-muted' : '') ?>">
              <td><?= (int)$f['id'] ?></td>
              <td>
                <div class="employee-name"><?= e($f['nome']) ?></div>
              </td>
              <td>
                <span class="employee-login"><?= e($f['login']) ?></span>
              </td>
              <td>
                <span class="badge-employee-role"><?= e($f['role_name']) ?></span>
              </td>
              <td>
                <?php if ((int)$f['is_ativo']===1): ?>
                  <span class="badge-employee-status active">✅ Ativo</span>
                <?php else: ?>
                  <span class="badge-employee-status inactive">❌ Inativo</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="employee-date"><?= e(date('d/m/Y', strtotime($f['created_at']))) ?></span>
              </td>
              <td>
                <span class="employee-date updated"><?= e(date('d/m/Y', strtotime($f['updated_at']))) ?></span>
              </td>
              <td>
                <div class="employee-actions">
                  <a class="btn-employee-edit" href="<?= e(base_url('funcionarios/edit.php?id=' . (int)$f['id'])) ?>">
                    ✏️ Editar
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>
