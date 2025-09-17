<?php
require __DIR__ . '/../app/lib/Database.php';
require __DIR__ . '/../app/lib/Auth.php';
require __DIR__ . '/../app/lib/Helpers.php';
require __DIR__ . '/../app/lib/CSRF.php';
require __DIR__ . '/../app/middleware/require_login.php';
require __DIR__ . '/../app/models/Funcionario.php';

$user = Auth::user();
$isAdmin = Auth::isAdmin();
$allUsers = Funcionario::allActive();

include __DIR__ . '/../app/views/partials/header.php';
?>
<h1>Dashboard</h1>

<div class="card dashboard-card">
  <form id="filters-form" class="filters" method="get">
    <div>
      <label>Modo</label>
      <select class="form-control" name="mode" id="mode">
        <option value="week" selected>Semanal (padrão)</option>
        <option value="month">Mensal</option>
        <option value="day">Diário</option>
      </select>
    </div>

    <div>
      <label>Agrupar por</label>
      <select class="form-control" name="groupBy" id="groupBy">
        <option value="period" selected>Período (dias/semanas/meses)</option>
        <option value="user">Usuário (nomes dos funcionários)</option>
      </select>
    </div>

    <div id="range-week" class="cluster">
      <div>
        <label>Início</label>
        <input class="form-control" type="date" name="start" value="<?= e((new DateTime('monday this week'))->format('Y-m-d')) ?>">
      </div>
      <div>
        <label>Fim</label>
        <input class="form-control" type="date" name="end" value="<?= e((new DateTime('sunday this week'))->format('Y-m-d')) ?>">
      </div>
    </div>

    <div id="range-month" class="hidden">
      <div>
        <label>Mês</label>
        <input class="form-control" type="month" name="month" value="<?= e(date('Y-m')) ?>">
      </div>
    </div>

    <div id="range-day" class="hidden">
      <div>
        <label>Dia</label>
        <input class="form-control" type="date" name="day" value="<?= e(date('Y-m-d')) ?>">
      </div>
    </div>

    <?php if ($isAdmin): ?>
      <div>
        <label><input id="toggle-compare" type="checkbox"> Comparar usuários</label>
      </div>
      <div id="multi-users" style="display:none">
        <label>Usuários</label>
        <select class="form-control" name="users[]" multiple size="5">
          <?php foreach ($allUsers as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= e($u['nome']) ?></option>
          <?php endforeach; ?>
        </select>
        <small>Segure CTRL para múltipla seleção.</small>
      </div>
    <?php endif; ?>

    <div>
      <button class="btn" type="submit">Aplicar</button>
    </div>
  </form>
</div>

<div class="card">
  <h3>Cadastros de clientes (comparativo)</h3>
  <div class="table-wrap"><canvas id="kpi-chart" style="min-height:340px"></canvas></div>
  <div id="chart-caption" class="chart-caption"></div> <!-- <<< NOVO rodapé amigável -->
  <div id="dashboard-error" class="notice error hidden"></div>
</div>


<?php include __DIR__ . '/../app/views/partials/footer.php'; ?>
<!-- Chart.js + DataLabels (responsivo; rótulos nos valores) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
