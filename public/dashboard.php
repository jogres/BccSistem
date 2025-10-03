<?php
require __DIR__ . '/../app/lib/Database.php';
require __DIR__ . '/../app/lib/Auth.php';
require __DIR__ . '/../app/lib/Helpers.php';
require __DIR__ . '/../app/lib/CSRF.php';
require __DIR__ . '/../app/middleware/require_login.php';
require __DIR__ . '/../app/models/Funcionario.php';
$user = Auth::user();
$isAdmin = Auth::isAdmin();
$monthDefault = date('Y-m');
$start = $monthDefault . '-01';
$end   = date('Y-m-d', strtotime('last day of ' . $start));
// Busca todos os funcionários ativos para seleção
$allUsers = Funcionario::allActive();


include __DIR__ . '/../app/views/partials/header.php';
?>

<div class="page-header">
  <h1 class="page-title">📊 Dashboard Analytics</h1>
  <p class="page-subtitle">Visão geral da performance e métricas do sistema</p>
</div>

<!-- Sistema de Filtros Compacto -->
<div class="filters-container">
  <div class="filters-header">
    <h3>🔧 Filtros e Configurações</h3>
    <div class="filters-actions">
      <button type="button" class="btn-secondary-compact" onclick="resetFilters()">
        🔄 Resetar
      </button>
      <button type="button" class="btn-secondary-compact" onclick="saveFilters()">
        💾 Salvar
      </button>
    </div>
  </div>

  <form id="filters-form" class="filters-compact" method="get">
    <div class="filters-grid">
      <!-- Período e Modo -->
      <div class="filter-section">
        <h4>📅 Período e Modo</h4>
        <div class="form-group">
          <label class="form-label" for="mode">Visualização</label>
          <select class="form-control" name="mode" id="mode">
            <option value="week">📅 Semanal</option>
            <option value="month" selected>📆 Mensal</option>
            <option value="day">📅 Diário</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="groupBy">Agrupamento</label>
          <select class="form-control" name="groupBy" id="groupBy">
            <option value="period" selected>📊 Por Período</option>
            <option value="user">👥 Por Usuário</option>
          </select>
        </div>
      </div>

      <!-- Seleção de Data -->
      <div class="filter-section">
        <h4>📆 Seleção de Data</h4>
        <div id="range-week" class="date-range" style="display: none;">
          <div class="form-group">
            <label class="form-label" for="start">Data Inicial</label>
            <input class="form-control" type="date" name="start" id="start">
          </div>
          <div class="form-group">
            <label class="form-label" for="end">Data Final</label>
            <input class="form-control" type="date" name="end" id="end">
          </div>
          <div class="date-presets">
            <button type="button" class="preset-btn" onclick="setDateRange('thisWeek')">Esta Semana</button>
            <button type="button" class="preset-btn" onclick="setDateRange('lastWeek')">Semana Passada</button>
          </div>
        </div>

        <div id="range-month" class="date-range">
          <div class="form-group">
            <label class="form-label" for="month">Mês/Ano</label>
            <input class="form-control" type="month" name="month" id="month" value="<?= e(date('Y-m')) ?>">
          </div>
          <div class="date-presets">
            <button type="button" class="preset-btn active" onclick="setMonth('current')">Atual</button>
            <button type="button" class="preset-btn" onclick="setMonth('previous')">Anterior</button>
            <button type="button" class="preset-btn" onclick="setMonth('last3')">Últimos 3</button>
          </div>
        </div>

        <div id="range-day" class="date-range" style="display: none;">
          <div class="form-group">
            <label class="form-label" for="day">Data</label>
            <input class="form-control" type="date" name="day" id="day" value="<?= e(date('Y-m-d')) ?>">
          </div>
          <div class="date-presets">
            <button type="button" class="preset-btn" onclick="setDay('today')">Hoje</button>
            <button type="button" class="preset-btn" onclick="setDay('yesterday')">Ontem</button>
          </div>
        </div>
      </div>

      <?php if ($isAdmin): ?>
      <!-- Usuários -->
      <div class="filter-section users-section">
        <h4>👥 Usuários</h4>
        <div class="form-group">
          <div class="form-check">
            <input id="toggle-compare" type="checkbox" checked>
            <label class="form-check-label" for="toggle-compare">Comparar usuários</label>
          </div>
        </div>
        <div id="multi-users">
          <div class="users-controls">
            <button type="button" class="btn-secondary-compact btn-sm" onclick="selectAllUsers()">Todos</button>
            <button type="button" class="btn-secondary-compact btn-sm" onclick="selectNoneUsers()">Limpar</button>
            <button type="button" class="btn-secondary-compact btn-sm" onclick="toggleUserSelection()">Inverter</button>
          </div>
          <select class="form-control users-select" name="users[]" multiple size="4">
            <?php foreach ($allUsers as $u): ?>
              <option value="<?= (int)$u['id'] ?>" data-name="<?= e($u['nome']) ?>">
                <?= e($u['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="users-info">
            <span class="users-count">Usuários selecionados:</span>
            <span class="selected-count" id="selected-count">0</span>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Filtros Avançados -->
      <div class="filter-section">
        <h4>⚙️ Filtros</h4>
        <div class="advanced-filters">
          <div class="filter-input">
            <label class="form-label" for="minClients">Mín. Clientes</label>
            <input class="form-control" type="number" name="minClients" id="minClients" min="0" placeholder="0">
          </div>
          <div class="filter-input">
            <label class="form-label" for="sortBy">Ordenar</label>
            <select class="form-control" name="sortBy" id="sortBy">
              <option value="name">Nome</option>
              <option value="total">Total</option>
              <option value="average">Média</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Ações Principais -->
    <div class="filters-actions-main">
      <button class="btn-apply" type="submit">
        <span class="loading-spinner hidden"></span>
        📊 Aplicar Filtros
      </button>
      <button type="button" class="btn-secondary-compact" onclick="exportChart('png')">
        📥 Exportar PNG
      </button>
    </div>
  </form>
</div>

<!-- Container do Gráfico Moderno -->
<div class="card">
  <div class="chart-header">
    <h3>📊 Performance Analytics</h3>
    <div class="chart-controls">
      <div class="control-group">
        <button type="button" class="control-btn active" onclick="toggleLegend()" title="Mostrar/Ocultar Legenda">
          👁️
        </button>
        <button type="button" class="control-btn" onclick="resetChart()" title="Resetar Gráfico">
          🔄
        </button>
        <button type="button" class="control-btn" onclick="toggleChartType()" title="Alternar Tipo">
          📊
        </button>
        <button type="button" class="control-btn" onclick="toggleGroupMode()" title="Alternar Agrupamento">
          📅
        </button>
      </div>
    </div>
  </div>
  
  <div class="chart-wrapper">
    <div class="chart-container-inner">
      <canvas id="kpi-chart"></canvas>
    </div>
  </div>
  
  <div id="chart-caption" class="chart-caption"></div>
  <div id="dashboard-error" class="notice notice-error hidden"></div>
</div>


<?php include __DIR__ . '/../app/views/partials/footer.php'; ?>

<!-- Configurações JavaScript -->
<script>
window.APP = {
  isAdmin: <?= $isAdmin ? 'true' : 'false' ?>,
  userId: <?= (int)$user['id'] ?>,
  userRole: '<?= e($user['role_id']) ?>'
};
</script>

<!-- Chart.js + DataLabels (responsivo; rótulos nos valores) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>