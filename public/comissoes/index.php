<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/models/Comissao.php';
require __DIR__ . '/../../app/models/Funcionario.php';
require __DIR__ . '/../../app/middleware/require_admin.php';

$user = Auth::user();

// Verificar tipo selecionado (vendedor ou virador) ou visualizar comissões
$tipoComissao = $_GET['tipo'] ?? '';
$funcionarioId = isset($_GET['funcionario_id']) ? (int)$_GET['funcionario_id'] : 0;
$visualizar = $_GET['visualizar'] ?? '';
$funcionarioComissaoId = isset($_GET['funcionario_comissao_id']) ? (int)$_GET['funcionario_comissao_id'] : 0;

// Se tiver tipo mas não funcionário, mostrar lista
$vendedoresDisponiveis = [];
$viradoresDisponiveis = [];
$vendasDisponiveis = [];
$funcionarioSelecionado = null;

// Para visualização de comissões
$funcionariosComComissoes = [];
$comissoesFuncionario = [];
$funcionarioComissaoSelecionado = null;
$mesFiltro = $_GET['mes_filtro'] ?? date('Y-m');

if ($visualizar === 'comissoes') {
    $funcionariosComComissoes = Comissao::getFuncionariosComComissoes();
    
    if ($funcionarioComissaoId > 0) {
        $funcionarioComissaoSelecionado = Funcionario::find($funcionarioComissaoId);
        
        if ($funcionarioComissaoSelecionado) {
            // Parse do filtro de mês
            $anoMes = explode('-', $mesFiltro);
            $filters = [
                'funcionario_id' => $funcionarioComissaoId
            ];
            
            if (count($anoMes) === 2 && is_numeric($anoMes[0]) && is_numeric($anoMes[1])) {
                $filters['ano'] = (int)$anoMes[0];
                $filters['mes'] = (int)$anoMes[1];
            }
            
            $comissoesFuncionario = Comissao::all($filters);
        }
    }
}

if ($tipoComissao === 'vendedor') {
    $vendedoresDisponiveis = Comissao::getVendedoresDisponiveis();
    
    if ($funcionarioId > 0) {
        $vendasDisponiveis = Comissao::getVendasDisponiveis($funcionarioId, 'vendedor');
        $funcionario = Funcionario::find($funcionarioId);
        if ($funcionario) {
            $funcionarioSelecionado = $funcionario;
        }
    }
} elseif ($tipoComissao === 'virador') {
    $viradoresDisponiveis = Comissao::getViradoresDisponiveis();
    
    if ($funcionarioId > 0) {
        $vendasDisponiveis = Comissao::getVendasDisponiveis($funcionarioId, 'virador');
        $funcionario = Funcionario::find($funcionarioId);
        if ($funcionario) {
            $funcionarioSelecionado = $funcionario;
        }
    }
}

include __DIR__ . '/../../app/views/partials/header.php';
?>

<link rel="stylesheet" href="<?= e(base_url('assets/css/clients.css')) ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= e(base_url('assets/css/comissoes.css')) ?>?v=<?= time() ?>">

<div class="main-container">
  <div class="clients-container">
    <!-- Cabeçalho -->
    <div class="clients-header">
      <div>
        <h1 class="clients-title">💰 Comissionamento</h1>
        <p class="clients-subtitle">Gerencie comissões de vendedores e viradores</p>
      </div>
    </div>

    <!-- Botões de Tipo -->
    <?php if (!$tipoComissao && $visualizar !== 'comissoes'): ?>
    <div class="comissoes-type-selector">
      <a href="?tipo=vendedor" class="comissoes-type-btn comissoes-type-vendedor">
        <div class="comissoes-type-icon">🧑‍💼</div>
        <div class="comissoes-type-title">Vendedor</div>
        <div class="comissoes-type-desc">Gerar comissões para vendedores</div>
      </a>
      
      <a href="?tipo=virador" class="comissoes-type-btn comissoes-type-virador">
        <div class="comissoes-type-icon">✅</div>
        <div class="comissoes-type-title">Virador</div>
        <div class="comissoes-type-desc">Gerar comissões para viradores</div>
      </a>
      
      <a href="?visualizar=comissoes" class="comissoes-type-btn comissoes-type-view">
        <div class="comissoes-type-icon">📊</div>
        <div class="comissoes-type-title">Visualizar Comissões</div>
        <div class="comissoes-type-desc">Ver comissões já geradas por funcionário</div>
      </a>
    </div>
    <?php endif; ?>

    <!-- Lista de Funcionários -->
    <?php if ($tipoComissao && $funcionarioId === 0): ?>
    <div class="comissoes-funcionarios-list">
      <div class="comissoes-funcionarios-header">
        <a href="index.php" class="btn-secondary-compact">⬅ Voltar</a>
        <h2>
          <?php if ($tipoComissao === 'vendedor'): ?>
            🧑‍💼 Selecione um Vendedor
          <?php else: ?>
            ✅ Selecione um Virador
          <?php endif; ?>
        </h2>
      </div>
      
      <?php 
      $listaFuncionarios = $tipoComissao === 'vendedor' ? $vendedoresDisponiveis : $viradoresDisponiveis;
      ?>
      
      <?php if (count($listaFuncionarios) > 0): ?>
      <div class="comissoes-funcionarios-grid">
        <?php foreach ($listaFuncionarios as $func): ?>
        <a href="?tipo=<?= e($tipoComissao) ?>&funcionario_id=<?= $func['funcionario_id'] ?>" 
           class="comissoes-funcionario-card">
          <div class="comissoes-funcionario-avatar">
            <?= $tipoComissao === 'vendedor' ? '🧑‍💼' : '✅' ?>
          </div>
          <div class="comissoes-funcionario-name">
            <?= e($func['nome']) ?>
          </div>
          <div class="comissoes-funcionario-action">
            Ver vendas →
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">📊</div>
        <h3 class="empty-title">Nenhum funcionário encontrado</h3>
        <p class="empty-text">
          Não há <?= $tipoComissao === 'vendedor' ? 'vendedores' : 'viradores' ?> com vendas disponíveis para comissão.
        </p>
        <a href="index.php" class="btn-primary">⬅ Voltar</a>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Lista de Vendas -->
    <?php if ($funcionarioId > 0 && $funcionarioSelecionado): ?>
    <div class="comissoes-vendas-list">
      <div class="comissoes-vendas-header">
        <a href="?tipo=<?= e($tipoComissao) ?>" class="btn-secondary-compact">⬅ Voltar</a>
        <h2>
          Vendas de <strong><?= e($funcionarioSelecionado['nome']) ?></strong>
          (<?= $tipoComissao === 'vendedor' ? 'Vendedor' : 'Virador' ?>)
        </h2>
      </div>
      
      <?php if (count($vendasDisponiveis) > 0): ?>
      <div class="clients-table-container">
        <div class="table-responsive">
          <table class="clients-table">
            <thead>
              <tr>
                <th>Contrato</th>
                <th>Cliente</th>
                <th>Administradora</th>
                <th>Tipo</th>
                <th>Valor Crédito</th>
                <th>Última Parcela</th>
                <th>Data Venda</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($vendasDisponiveis as $venda): ?>
              <?php
              // Buscar comissões da venda
              $comissoesVenda = Comissao::getByVenda($venda['id'], $tipoComissao);
              $ultimaParcela = $venda['ultima_parcela'] ?? 0;
              
              // Calcular valor base (considerando Gazin meia parcela)
              $valorBase = Comissao::calcularValorBase($venda);
              ?>
              <tr>
                <td><code><?= e($venda['numero_contrato']) ?></code></td>
                <td>
                  <div class="text-sm font-medium"><?= e($venda['cliente_nome']) ?></div>
                </td>
                <td><?= e($venda['administradora']) ?></td>
                <td>
                  <span class="badge <?= $venda['tipo'] === 'Normal' ? 'badge-primary' : 'badge-warning' ?>">
                    <?= e($venda['tipo']) ?>
                  </span>
                </td>
                <td>
                  <div>R$ <?= number_format($venda['valor_credito'], 2, ',', '.') ?></div>
                  <?php 
                  $adminNormalizada = strtolower(trim($venda['administradora'] ?? ''));
                  if ($adminNormalizada === 'gazin' && $venda['tipo'] === 'Meia'): 
                  ?>
                  <div class="text-xs text-muted">
                    Base: R$ <?= number_format($valorBase, 2, ',', '.') ?>
                  </div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($ultimaParcela > 0): ?>
                    <span class="badge badge-info">Parcela <?= $ultimaParcela ?></span>
                  <?php else: ?>
                    <span class="text-muted">Nenhuma</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="text-xs"><?= date('d/m/Y', strtotime($venda['created_at'])) ?></div>
                </td>
                <td class="actions-cell">
                  <a href="create.php?venda_id=<?= $venda['id'] ?>&tipo=<?= e($tipoComissao) ?>&funcionario_id=<?= $funcionarioId ?>" 
                     class="btn-primary-compact">
                    ➕ Gerar Comissão
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">📊</div>
        <h3 class="empty-title">Nenhuma venda disponível</h3>
        <p class="empty-text">
          Não há vendas disponíveis para gerar comissão para este <?= $tipoComissao === 'vendedor' ? 'vendedor' : 'virador' ?>.
        </p>
        <a href="?tipo=<?= e($tipoComissao) ?>" class="btn-primary">⬅ Voltar</a>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Visualização de Comissões -->
    <?php if ($visualizar === 'comissoes'): ?>
      <?php if ($funcionarioComissaoId === 0): ?>
        <!-- Lista de Funcionários com Comissões -->
        <div class="comissoes-funcionarios-list">
          <div class="comissoes-funcionarios-header">
            <a href="index.php" class="btn-secondary-compact">⬅ Voltar</a>
            <h2>📊 Selecione um Funcionário</h2>
          </div>
          
          <?php if (count($funcionariosComComissoes) > 0): ?>
          <div class="comissoes-funcionarios-grid">
            <?php foreach ($funcionariosComComissoes as $func): ?>
            <a href="?visualizar=comissoes&funcionario_comissao_id=<?= $func['funcionario_id'] ?>" 
               class="comissoes-funcionario-card">
              <div class="comissoes-funcionario-avatar">👤</div>
              <div class="comissoes-funcionario-name">
                <?= e($func['nome']) ?>
              </div>
              <div class="comissoes-funcionario-stats">
                <div class="comissoes-stat-item">
                  <span class="comissoes-stat-label">Total:</span>
                  <span class="comissoes-stat-value"><?= $func['total_comissoes'] ?> comissões</span>
                </div>
                <div class="comissoes-stat-item">
                  <span class="comissoes-stat-label">Valor Total:</span>
                  <span class="comissoes-stat-value">R$ <?= number_format($func['total_valor'], 2, ',', '.') ?></span>
                </div>
                <div class="comissoes-stat-item">
                  <span class="comissoes-stat-label">Última:</span>
                  <span class="comissoes-stat-value"><?= date('d/m/Y', strtotime($func['ultima_comissao'])) ?></span>
                </div>
              </div>
              <div class="comissoes-funcionario-action">
                Ver comissões →
              </div>
            </a>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">📊</div>
            <h3 class="empty-title">Nenhuma comissão encontrada</h3>
            <p class="empty-text">
              Ainda não há comissões geradas no sistema.
            </p>
            <a href="index.php" class="btn-primary">⬅ Voltar</a>
          </div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <!-- Lista de Comissões do Funcionário -->
        <?php if ($funcionarioComissaoSelecionado): ?>
        <div class="comissoes-list-container">
          <div class="comissoes-funcionarios-header">
            <a href="?visualizar=comissoes" class="btn-secondary-compact">⬅ Voltar</a>
            <h2>
              📊 Comissões de <strong><?= e($funcionarioComissaoSelecionado['nome']) ?></strong>
            </h2>
          </div>
          
          <!-- Filtro por Mês -->
          <div class="clients-filters-container">
            <div class="clients-filters-header">
              <h3 class="text-balance">🔍 Filtro por Mês</h3>
            </div>
            
            <form method="get" class="clients-filters-body">
              <input type="hidden" name="visualizar" value="comissoes">
              <input type="hidden" name="funcionario_comissao_id" value="<?= $funcionarioComissaoId ?>">
              
              <div class="clients-filters-grid">
                <div class="clients-filters-field">
                  <label class="clients-filters-label">📅 Mês</label>
                  <input 
                    type="month" 
                    name="mes_filtro" 
                    value="<?= e($mesFiltro) ?>"
                    class="clients-filter-control"
                  >
                </div>
                
                <!-- Botão aplicar -->
                <div class="clients-filters-apply">
                  <button class="clients-btn-apply" type="submit">🔍 Aplicar</button>
                </div>
                
                <?php if ($mesFiltro !== date('Y-m')): ?>
                <div style="display: flex; align-items: center;">
                  <a href="?visualizar=comissoes&funcionario_comissao_id=<?= $funcionarioComissaoId ?>" 
                     class="btn-secondary-compact">
                    🔄 Limpar Filtro
                  </a>
                </div>
                <?php endif; ?>
              </div>
            </form>
          </div>
          
          <?php if (count($comissoesFuncionario) > 0): ?>
          <div class="clients-table-container">
            <div class="table-responsive">
              <table class="clients-table">
                <thead>
                  <tr>
                    <th>Data</th>
                    <th>Contrato</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Parcela</th>
                    <th>Porcentagem</th>
                    <th>Valor Base</th>
                    <th>Valor Comissão</th>
                    <th class="text-center">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $totalComissao = 0;
                  foreach ($comissoesFuncionario as $com): 
                    $totalComissao += (float)$com['valor_comissao'];
                  ?>
                  <tr>
                    <td>
                      <div class="text-xs"><?= date('d/m/Y', strtotime($com['created_at'])) ?></div>
                      <div class="text-xs text-muted"><?= date('H:i', strtotime($com['created_at'])) ?></div>
                    </td>
                    <td><code><?= e($com['numero_contrato']) ?></code></td>
                    <td><?= e($com['cliente_nome']) ?></td>
                    <td>
                      <span class="badge <?= $com['tipo_comissao'] === 'vendedor' ? 'badge-primary' : 'badge-success' ?>">
                        <?= ucfirst(e($com['tipo_comissao'])) ?>
                      </span>
                    </td>
                    <td><?= e($com['parcela']) ?></td>
                    <td><?= number_format($com['porcentagem'], 2, ',', '.') ?>%</td>
                    <td>R$ <?= number_format($com['valor_base'], 2, ',', '.') ?></td>
                    <td>
                      <strong style="color: var(--bcc-green);">
                        R$ <?= number_format($com['valor_comissao'], 2, ',', '.') ?>
                      </strong>
                    </td>
                    <td class="actions-cell">
                      <a href="edit.php?id=<?= $com['id'] ?>" 
                         class="btn-primary-compact" 
                         title="Editar comissão">
                        ✏️ Editar
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr style="background: var(--bcc-gray-50); font-weight: 700;">
                    <td colspan="7" class="text-right">Total:</td>
                    <td style="color: var(--bcc-green); font-size: 1.125rem;">
                      R$ <?= number_format($totalComissao, 2, ',', '.') ?>
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">📊</div>
            <h3 class="empty-title">Nenhuma comissão encontrada</h3>
            <p class="empty-text">
              <?php if ($mesFiltro !== date('Y-m')): ?>
                Não há comissões para o mês selecionado.
              <?php else: ?>
                Este funcionário ainda não possui comissões geradas.
              <?php endif; ?>
            </p>
            <a href="?visualizar=comissoes" class="btn-primary">⬅ Voltar</a>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>

