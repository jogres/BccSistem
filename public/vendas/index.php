<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/models/Venda.php';
require __DIR__ . '/../../app/models/Funcionario.php';
require __DIR__ . '/../../app/middleware/require_login.php';

$pdo = Database::getConnection();
$user = Auth::user();
$isAdmin = Auth::isAdmin();

// Opções de filtros
$opcoesInteresse = is_file(__DIR__ . '/../../app/config/interesses.php')
    ? require __DIR__ . '/../../app/config/interesses.php'
    : [];

$opcoesAdministradoras = is_file(__DIR__ . '/../../app/config/administradoras.php')
    ? require __DIR__ . '/../../app/config/administradoras.php'
    : [];

// Funcionários para filtro
$funcionarios = Funcionario::allActive();

// --------- Filtros (GET) ----------
$period = ($_GET['period'] ?? 'month') === 'all' ? 'all' : 'month';
$month = $_GET['m'] ?? date('Y-m'); // AAAA-MM
$vendedorId = isset($_GET['vendedor_id']) && $_GET['vendedor_id'] !== '' ? (int)$_GET['vendedor_id'] : null;
$viradorId = isset($_GET['virador_id']) && $_GET['virador_id'] !== '' ? (int)$_GET['virador_id'] : null;
$administradora = trim($_GET['administradora'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$segmento = trim($_GET['segmento'] ?? '');
$q = trim($_GET['q'] ?? '');
$pp = (int)($_GET['pp'] ?? 25);
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = in_array($pp, [10, 25, 50, 100], true) ? $pp : 25;
$offset = ($page - 1) * $perPage;

// Montar filtros
$filters = [];

// Adicionar filtro de período
if ($period === 'month' && preg_match('/^\d{4}-\d{2}$/', $month)) {
    $filters['mes'] = (int)date('n', strtotime($month . '-01'));
    $filters['ano'] = (int)date('Y', strtotime($month . '-01'));
}

if ($vendedorId) $filters['vendedor_id'] = $vendedorId;
if ($viradorId) $filters['virador_id'] = $viradorId;
if ($administradora !== '') $filters['administradora'] = $administradora;
if ($tipo !== '') $filters['tipo'] = $tipo;
if ($segmento !== '') $filters['segmento'] = $segmento;

// Buscar vendas
$userId = $isAdmin ? null : (int)$user['id'];
$vendasTodas = Venda::all($userId, $filters);

// Busca geral
if ($q !== '') {
    $vendasTodas = array_filter($vendasTodas, function($venda) use ($q) {
        $searchIn = strtolower(
            $venda['numero_contrato'] . ' ' .
            $venda['cliente_nome'] . ' ' .
            $venda['vendedor_nome'] . ' ' .
            $venda['virador_nome'] . ' ' .
            $venda['cpf']
        );
        return strpos($searchIn, strtolower($q)) !== false;
    });
}

// Paginação
$total = count($vendasTodas);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$vendas = array_slice($vendasTodas, $offset, $perPage);

// Estatísticas
$mesStats = $period === 'month' ? (int)date('n', strtotime($month . '-01')) : null;
$anoStats = $period === 'month' ? (int)date('Y', strtotime($month . '-01')) : null;
$stats = Venda::getStats($userId, $mesStats, $anoStats);

include __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="main-container">
  <div class="clients-container">
    <!-- Cabeçalho no mesmo padrão de Clientes -->
    <div class="clients-header">
      <div>
        <h1 class="clients-title">🛒 Vendas</h1>
        <p class="clients-subtitle">Gerenciamento completo de vendas realizadas</p>
      </div>
      <div class="clients-actions">
        <?php if (Auth::isPadrao() || Auth::isAdmin()): ?>
          <a class="btn-new-client" href="<?= e(base_url('vendas/create.php')) ?>">
            ➕ Nova Venda
          </a>
        <?php endif; ?>
        <?php if (Auth::isAdmin()): ?>
          <?php
          // reaproveita todos os filtros atuais da página
          $qs = $_GET;
          // força mês atual caso não tenha vindo
          if (($qs['period'] ?? 'month') !== 'all') {
            $qs['period'] = 'month';
            $qs['m'] = $qs['m'] ?? date('Y-m');
          }
          $exportUrl = base_url('vendas/export_excel.php') . '?' . http_build_query($qs);
          ?>
          <a class="btn-export-excel" href="<?= e($exportUrl) ?>">
            📊 Exportar Excel
          </a>
        <?php endif; ?>
      </div>
    </div>

<!-- Estatísticas (mesmo grid utilizado em clientes) -->
<div class="clients-stats">
    <div class="stat-card stat-primary">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['total_vendas'], 0, ',', '.') ?></div>
            <div class="stat-label">Total de Vendas</div>
        </div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
            <div class="stat-value">R$ <?= number_format($stats['total_credito'], 2, ',', '.') ?></div>
            <div class="stat-label">Valor Total</div>
        </div>
    </div>

    <div class="stat-card stat-info">
        <div class="stat-icon">📈</div>
        <div class="stat-content">
            <div class="stat-value">R$ <?= number_format($stats['media_credito'], 2, ',', '.') ?></div>
            <div class="stat-label">Ticket Médio</div>
        </div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['total_vendedores'], 0, ',', '.') ?></div>
            <div class="stat-label">Vendedores Ativos</div>
        </div>
    </div>
</div>

<!-- Sistema de Filtros Melhorado -->
<div class="clients-filters-container">
  <div class="clients-filters-header">
    <h3 class="text-balance">🔍 Filtros de Busca</h3>
  </div>
  
  <form class="clients-filters-body" method="get">
    <div class="clients-filters-grid">
      <!-- Período -->
      <div class="clients-filters-period">
        <label>
          <input type="radio" name="period" value="month" <?= $period === 'month' ? 'checked' : '' ?>>
          <span>📅 Por mês</span>
        </label>
        <label>
          <input type="radio" name="period" value="all" <?= $period === 'all' ? 'checked' : '' ?>>
          <span>📊 Todos</span>
        </label>
      </div>

      <!-- Mês (aparece quando "Por mês" está selecionado) -->
      <div class="clients-filters-month when-month" style="display: <?= $period === 'month' ? 'flex' : 'none' ?>">
        <label class="clients-filters-label">Mês</label>
        <input class="clients-filter-control" type="month" name="m" value="<?= e($month) ?>">
      </div>

      <!-- Vendedor -->
      <div class="clients-filters-field">
        <label class="clients-filters-label">🧑‍💼 Vendedor</label>
        <select name="vendedor_id" class="clients-filter-control">
          <option value="">Todos</option>
          <?php foreach ($funcionarios as $func): ?>
            <option value="<?= $func['id'] ?>" <?= $vendedorId == $func['id'] ? 'selected' : '' ?>>
              <?= e($func['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Virador -->
      <div class="clients-filters-field">
        <label class="clients-filters-label">🔁 Virador</label>
        <select name="virador_id" class="clients-filter-control">
          <option value="">Todos</option>
          <?php foreach ($funcionarios as $func): ?>
            <option value="<?= $func['id'] ?>" <?= $viradorId == $func['id'] ? 'selected' : '' ?>>
              <?= e($func['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Administradora -->
      <div class="clients-filters-field">
        <label class="clients-filters-label">🏢 Administradora</label>
        <select name="administradora" class="clients-filter-control">
          <option value="">Todas</option>
          <?php foreach ($opcoesAdministradoras as $adm): ?>
            <option value="<?= e($adm) ?>" <?= $administradora === $adm ? 'selected' : '' ?>>
              <?= e($adm) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Tipo -->
      <div class="clients-filters-field">
        <label class="clients-filters-label">🏷️ Tipo</label>
        <select name="tipo" class="clients-filter-control">
          <option value="">Todos</option>
          <option value="Normal" <?= $tipo === 'Normal' ? 'selected' : '' ?>>Normal</option>
          <option value="Meia" <?= $tipo === 'Meia' ? 'selected' : '' ?>>Meia</option>
        </select>
      </div>

      <!-- Segmento -->
      <div class="clients-filters-field">
        <label class="clients-filters-label">🎯 Segmento</label>
        <select name="segmento" class="clients-filter-control">
          <option value="">Todos</option>
          <?php foreach ($opcoesInteresse as $int): ?>
            <option value="<?= e($int) ?>" <?= $segmento === $int ? 'selected' : '' ?>>
              <?= e($int) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Busca Geral -->
      <div class="clients-filters-field">
        <label class="clients-filters-label">🔎 Busca Geral</label>
        <input class="clients-filter-control" type="text" name="q" value="<?= e($q) ?>" placeholder="Contrato, cliente, CPF...">
      </div>

      <!-- Itens por página -->
      <div class="clients-filters-perpage">
        <label class="clients-filters-label">📄 Por página</label>
        <select class="clients-filter-control" name="pp">
          <?php foreach ([10, 25, 50, 100] as $n): ?>
            <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Botão aplicar -->
      <div class="clients-filters-apply">
        <button class="clients-btn-apply" type="submit">🔍 Aplicar</button>
      </div>
    </div>
  </form>
</div>

<!-- Tabela de Vendas -->
<div class="clients-table-container">
    <?php if (count($vendas) > 0): ?>
        <div class="table-responsive">
        <table class="clients-table">
            <thead>
                <tr>
                    <th>Contrato</th>
                    <th>Cliente</th>
                    <th>CPF</th>
                    <th>Vendedor</th>
                    <th>Virador</th>
                    <th>Administradora</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Data</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendas as $venda): ?>
                    <tr>
                        <td>
                            <code><?= e($venda['numero_contrato']) ?></code>
                        </td>
                        <td>
                            <div class="text-sm font-medium"><?= e($venda['cliente_nome']) ?></div>
                            <div class="text-xs text-muted"><?= e($venda['cliente_telefone']) ?></div>
                        </td>
                        <td><code><?= e($venda['cpf']) ?></code></td>
                        <td><?= e($venda['vendedor_nome']) ?></td>
                        <td><?= e($venda['virador_nome']) ?></td>
                        <td><?= e($venda['administradora']) ?></td>
                        <td>
                            <span class="badge <?= $venda['tipo'] === 'Normal' ? 'badge-primary' : 'badge-warning' ?>">
                                <?= e($venda['tipo']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-success font-semibold">
                                R$ <?= number_format($venda['valor_credito'], 2, ',', '.') ?>
                            </span>
                        </td>
                        <td>
                            <div class="text-xs"><?= date('d/m/Y', strtotime($venda['created_at'])) ?></div>
                            <div class="text-xs text-muted"><?= date('H:i', strtotime($venda['created_at'])) ?></div>
                        </td>
                        <td class="actions-cell">
                            <a href="view.php?id=<?= $venda['id'] ?>" class="btn-secondary-compact" title="Visualizar">👁️ Ver</a>
                            <?php if ($isAdmin): ?>
                                <a href="edit.php?id=<?= $venda['id'] ?>" class="btn-client-edit" title="Editar">✏️ Editar</a>
                                <button type="button" class="btn-client-delete" 
                                        onclick="confirmDelete(<?= $venda['id'] ?>)"
                                        title="Excluir">
                                    🗑️ Excluir
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

    <!-- Paginação Profissional -->
    <div class="pagination">
      <div class="pagination-info">
        📊 Mostrando <?= $total ? ($offset + 1) : 0 ?>–<?= min($offset + $perPage, $total) ?> de <?= $total ?> registros
      </div>
      
      <div class="pagination-nav">
        <?php
        $qs = $_GET;
        unset($qs['p']);
        $base = base_url('vendas/index.php') . '?' . http_build_query($qs);
        ?>
        
        <?php if ($page > 1): ?>
          <a href="<?= e($base . '&p=' . ($page - 1)) ?>" title="Página anterior">
            ← Anterior
          </a>
        <?php else: ?>
          <span class="disabled">← Anterior</span>
        <?php endif; ?>
        
        <?php
        // Mostrar números das páginas
        $start = max(1, $page - 2);
        $end = min($pages, $page + 2);
        
        if ($start > 1): ?>
          <a href="<?= e($base . '&p=1') ?>">1</a>
          <?php if ($start > 2): ?>
            <span>...</span>
          <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start; $i <= $end; $i++): ?>
          <?php if ($i == $page): ?>
            <span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="<?= e($base . '&p=' . $i) ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($end < $pages): ?>
          <?php if ($end < $pages - 1): ?>
            <span>...</span>
          <?php endif; ?>
          <a href="<?= e($base . '&p=' . $pages) ?>"><?= $pages ?></a>
        <?php endif; ?>
        
        <?php if ($page < $pages): ?>
          <a href="<?= e($base . '&p=' . ($page + 1)) ?>" title="Próxima página">
            Próxima →
          </a>
        <?php else: ?>
          <span class="disabled">Próxima →</span>
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📊</div>
            <h3 class="empty-title">Nenhuma venda encontrada</h3>
            <p class="empty-text">
                <?php if ($period === 'month'): ?>
                    Nenhuma venda foi encontrada no período selecionado (<?= strftime('%B de %Y', strtotime($month . '-01')) ?>).
                <?php else: ?>
                    Nenhuma venda foi encontrada com os filtros selecionados.
                <?php endif; ?>
                <br><br>
                <strong>💡 Dica:</strong> Tente ajustar os filtros ou cadastrar uma nova venda.
            </p>
            <a href="create.php" class="btn-primary">
                ➕ Cadastrar Nova Venda
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Form de delete (hidden) -->
<form id="deleteForm" method="POST" action="delete.php" style="display: none;">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
// Controla a exibição do campo de mês
document.addEventListener('DOMContentLoaded', function() {
  const periodRadios = document.querySelectorAll('input[name="period"]');
  const monthField = document.querySelector('.when-month');
  
  function toggleMonthField() {
    const isMonth = document.querySelector('input[name="period"][value="month"]').checked;
    monthField.style.display = isMonth ? '' : 'none';
    
    // Animação suave
    if (isMonth) {
      monthField.style.opacity = '0';
      monthField.style.transform = 'translateY(-10px)';
      setTimeout(() => {
        monthField.style.opacity = '1';
        monthField.style.transform = 'translateY(0)';
      }, 100);
    }
  }
  
  // Adiciona evento aos radio buttons
  periodRadios.forEach(radio => {
    radio.addEventListener('change', toggleMonthField);
  });
  
  // Inicializa o estado
  toggleMonthField();
  
  // Adiciona animação aos campos quando têm valor
  const filterInputs = document.querySelectorAll('.clients-filters-field input, .clients-filters-field select, .clients-filters-perpage select');
  filterInputs.forEach(input => {
    if (input.value.trim() !== '') {
      input.classList.add('filters-active');
    }
    
    input.addEventListener('input', function() {
      if (this.value.trim() !== '') {
        this.classList.add('filters-active');
      } else {
        this.classList.remove('filters-active');
      }
    });
  });
  
  // Feedback visual no botão aplicar
  const applyBtn = document.querySelector('.clients-btn-apply');
  if (applyBtn) {
    applyBtn.addEventListener('click', function() {
      // Efeito de loading
      this.style.background = 'linear-gradient(135deg, var(--bcc-green), var(--bcc-green-dark))';
      this.innerHTML = '⏳ Aplicando...';
      
      setTimeout(() => {
        this.form.submit();
      }, 300);
    });
  }
});

function confirmDelete(id) {
    if (confirm('Tem certeza que deseja excluir esta venda? Esta ação não pode ser desfeita.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>
  </div>
</div>
