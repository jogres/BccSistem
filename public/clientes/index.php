<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/middleware/require_login.php';

$pdo     = Database::getConnection();
$user    = Auth::user();
$isAdmin = Auth::isAdmin();

// opções
$opcoesInteresse = is_file(__DIR__ . '/../../app/config/interesses.php')
  ? require __DIR__ . '/../../app/config/interesses.php'
  : [];

// --------- Filtros (GET) ----------
$period   = ($_GET['period'] ?? 'month') === 'all' ? 'all' : 'month'; // padrão = month
$month    = $_GET['m'] ?? date('Y-m'); // AAAA-MM (se month)
$nome     = trim($_GET['f_nome']     ?? '');
$tel      = trim($_GET['f_telefone'] ?? '');
$cidade   = trim($_GET['f_cidade']   ?? '');
$estado   = strtoupper(substr(trim($_GET['f_estado'] ?? ''), 0, 2));
$interesse = trim($_GET['f_interesse'] ?? '');
$q        = trim($_GET['q'] ?? ''); // busca geral
$pp       = (int)($_GET['pp'] ?? 25);
$page     = max(1, (int)($_GET['p'] ?? 1));
$perPage  = in_array($pp, [10, 25, 50, 100], true) ? $pp : 25;
$offset   = ($page - 1) * $perPage;

// --------- WHERE dinâmico ----------
$where = ["c.deleted_at IS NULL"];
$params = [];

if (!$isAdmin) {
  $where[] = "c.criado_por = :uid";
  $params[':uid'] = (int)$user['id'];
}

if ($period === 'month' && preg_match('/^\d{4}-\d{2}$/', $month)) {
  // intervalo do mês
  $start = $month . '-01';
  $end   = date('Y-m-d', strtotime('last day of ' . $start));
  $where[] = "c.created_at BETWEEN :start AND :end";
  $params[':start'] = $start . ' 00:00:00';
  $params[':end']   = $end   . ' 23:59:59';
}

if ($nome !== '') {
  $where[] = "c.nome LIKE :fn";
  $params[':fn'] = "%$nome%";
}
if ($tel !== '') {
  $where[] = "c.telefone LIKE :ft";
  $params[':ft'] = "%$tel%";
}
if ($cidade !== '') {
  $where[] = "c.cidade LIKE :fc";
  $params[':fc'] = "%$cidade%";
}
if ($estado !== '') {
  $where[] = "c.estado = :fe";
  $params[':fe'] = $estado;
}
if ($interesse !== '' && in_array($interesse, $opcoesInteresse, true)) {
  $where[] = "c.interesse = :fi";
  $params[':fi'] = $interesse;
}
if ($q !== '') {
  // busca ampla (nome/telefone/cidade/estado/interesse)
  $where[] = "(c.nome LIKE :q OR c.telefone LIKE :q OR c.cidade LIKE :q OR c.estado LIKE :q OR c.interesse LIKE :q)";
  $params[':q'] = '%' . str_replace(' ', '%', $q) . '%';
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

// Calcular estatísticas
$totalClients = $total;
?>
<div class="main-container">
  <div class="clients-container">
    <!-- Cabeçalho -->
    <div class="clients-header">
      <div>
        <h1 class="clients-title">👥 Clientes</h1>
        <p class="clients-subtitle">Gestão completa do portfólio de clientes</p>
      </div>
      <div class="clients-actions">
        <a class="btn-new-client" href="<?= e(base_url('clientes/create.php')) ?>">
          ➕ Novo Cliente
        </a>
        <?php if ($isAdmin): ?>
          <?php
          // reaproveita todos os filtros atuais da página
          $qs = $_GET;
          // força mês atual caso não tenha vindo
          if (($qs['period'] ?? 'month') !== 'all') {
            $qs['period'] = 'month';
            $qs['m'] = $qs['m'] ?? date('Y-m');
          }
          $exportUrl = base_url('clientes/export_excel.php') . '?' . http_build_query($qs);
          ?>
          <a class="btn-export-excel" href="<?= e($exportUrl) ?>">
            📊 Exportar Excel
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Estatísticas -->
    <div class="clients-stats">
      <div class="stat-card total">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?= $totalClients ?></div>
        <div class="stat-label">Total</div>
      </div>
      <div class="stat-card this-period">
        <div class="stat-icon">📊</div>
        <div class="stat-number"><?= count($rows) ?></div>
        <div class="stat-label">Exibindo</div>
      </div>
    </div>

    <!-- Sistema de Filtros Melhorado -->
    <div class="clients-filters-container">
      <div class="clients-filters-header">
        <h3>🔍 Filtros de Busca</h3>
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
          <div class="clients-filters-month when-month" style="<?= $period === 'month' ? '' : 'display:none' ?>">
            <label class="clients-filters-label">Mês</label>
            <input class="clients-filter-control" type="month" name="m" value="<?= e($month) ?>">
          </div>

          <!-- Nome -->
          <div class="clients-filters-field">
            <label class="clients-filters-label">👤 Nome</label>
            <input class="clients-filter-control" type="text" name="f_nome" value="<?= e($nome) ?>" placeholder="Nome do cliente">
          </div>

          <!-- Telefone -->
          <div class="clients-filters-field">
            <label class="clients-filters-label">📞 Telefone</label>
            <input class="clients-filter-control" type="text" name="f_telefone" value="<?= e($tel) ?>" placeholder="Número">
          </div>

          <!-- Estado -->
          <div class="clients-filters-field">
            <label class="clients-filters-label">🗺️ Estado</label>
            <select class="clients-filter-control" name="f_estado">
              <option value="">UF</option>
              <?php foreach (['AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO'] as $uf): ?>
                <option value="<?= $uf ?>" <?= $estado === $uf ? 'selected' : '' ?>><?= $uf ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Interesse -->
          <div class="clients-filters-field">
            <label class="clients-filters-label">🎯 Interesse</label>
            <select class="clients-filter-control" name="f_interesse">
              <option value="">Todos</option>
              <?php foreach ($opcoesInteresse as $opt): ?>
                <option value="<?= e($opt) ?>" <?= $interesse === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
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

    <!-- Tabela de clientes -->
    <div class="clients-table-container">
      <table class="clients-table">
        <thead>
          <tr>
            <th>#</th>
            <th>👤 Nome</th>
            <th>📞 Telefone</th>
            <th>🏙️ Cidade</th>
            <th>🗺️ UF</th>
            <th>🎯 Interesse</th>
            <th>👨‍💼 Criado por</th>
            <th>📅 Criado em</th>
            <th>⚙️ Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $currentMonthHeader = '';
          foreach ($rows as $r):
            $monthHeader = ($period === 'month') ? date('m/Y', strtotime($r['created_at'])) : '';
            if ($period === 'month' && $monthHeader !== $currentMonthHeader):
              $currentMonthHeader = $monthHeader; ?>
              <tr class="month-divider">
                <td colspan="9">📅 <?= e($currentMonthHeader) ?></td>
              </tr>
            <?php endif; ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td>
                <div class="client-name"><?= e($r['nome']) ?></div>
              </td>
              <td>
                <span class="client-phone"><?= e($r['telefone']) ?></span>
              </td>
              <td>
                <div class="client-location">
                  <span class="client-city"><?= e($r['cidade']) ?></span>
                </div>
              </td>
              <td>
                <span class="client-state"><?= e($r['estado']) ?></span>
              </td>
              <td>
                <?php if ($r['interesse']): ?>
                  <span class="badge-client-interest"><?= e($r['interesse']) ?></span>
                <?php else: ?>
                  <span style="color: var(--bcc-gray-400); font-style: italic;">-</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="client-created-by"><?= e($r['criado_por_nome']) ?></span>
              </td>
              <td>
                <span class="client-date"><?= e(date('d/m/Y', strtotime($r['created_at']))) ?></span>
              </td>
              <td>
                <div class="client-actions">
                  <a class="btn-client-edit" href="<?= e(base_url('clientes/edit.php?id=' . (int)$r['id'])) ?>">
                    ✏️ Editar
                  </a>
                </div>
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
        $base = base_url('clientes/index.php') . '?' . http_build_query($qs);
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
</div>

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
          this.style.background = 'linear-gradient(135deg, var(--bcc-blue), var(--bcc-blue-light))';
          this.innerHTML = '🔍 Aplicar';
        }, 1000);
      });
    }
    
    // Animação das estatísticas
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
      setTimeout(() => {
        card.style.animation = 'none';
        card.offsetHeight; // Trigger reflow
        card.style.animation = 'sectionFadeIn 0.5s ease-out';
      }, index * 200);
    });
    
    console.log('✅ Listagem de clientes melhorada carregada!');
  });
</script>

<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>