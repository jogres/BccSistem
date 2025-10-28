<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/lib/ActivityLogger.php';
require __DIR__ . '/../../app/lib/Notification.php';
require __DIR__ . '/../../app/models/Comissao.php';
require __DIR__ . '/../../app/models/Venda.php';
require __DIR__ . '/../../app/models/Funcionario.php';
require __DIR__ . '/../../app/middleware/require_admin.php';

$user = Auth::user();
$errors = [];
$success = false;

// Verificar parâmetros
if (!isset($_GET['venda_id']) || !isset($_GET['tipo']) || !isset($_GET['funcionario_id'])) {
    $_SESSION['error'] = 'Parâmetros inválidos';
    header('Location: index.php');
    exit;
}

$vendaId = (int)$_GET['venda_id'];
$tipoComissao = trim($_GET['tipo']);
$funcionarioId = (int)$_GET['funcionario_id'];

// Validar tipo de comissão
if (!in_array($tipoComissao, ['vendedor', 'virador'], true)) {
    $_SESSION['error'] = 'Tipo de comissão inválido';
    header('Location: index.php');
    exit;
}

// Buscar venda
$venda = Venda::find($vendaId);
if (!$venda) {
    $_SESSION['error'] = 'Venda não encontrada';
    header('Location: index.php');
    exit;
}

// Verificar se a venda já atingiu a parcela final
if (Comissao::isParcelaFinal($vendaId, $tipoComissao)) {
    $_SESSION['error'] = 'Esta venda já atingiu a parcela final para este tipo de comissão';
    header('Location: index.php?tipo=' . urlencode($tipoComissao) . '&funcionario_id=' . $funcionarioId);
    exit;
}

// Buscar funcionário
$funcionario = Funcionario::find($funcionarioId);
if (!$funcionario) {
    $_SESSION['error'] = 'Funcionário não encontrado';
    header('Location: index.php');
    exit;
}

// Buscar próxima parcela
$proximaParcela = Comissao::getProximaParcela($vendaId, $tipoComissao);

// Calcular valor base
$valorBase = Comissao::calcularValorBase($venda);

// Buscar comissões anteriores
$comissoesAnteriores = Comissao::getByVenda($vendaId, $tipoComissao);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parcela = trim($_POST['parcela'] ?? '');
    $porcentagem = trim($_POST['porcentagem'] ?? '');
    
    // Validações
    if (empty($parcela)) {
        $errors[] = 'Descrição da parcela é obrigatória';
    }
    
    if (empty($porcentagem)) {
        $errors[] = 'Porcentagem é obrigatória';
    } elseif (!is_numeric($porcentagem)) {
        $errors[] = 'Porcentagem deve ser um número';
    } else {
        $porcentagemFloat = (float)$porcentagem;
        if ($porcentagemFloat <= 0 || $porcentagemFloat > 100) {
            $errors[] = 'Porcentagem deve estar entre 0 e 100';
        }
        
        // Validar casas decimais (máximo 2)
        if (round($porcentagemFloat, 2) != $porcentagemFloat) {
            $errors[] = 'Porcentagem deve ter no máximo 2 casas decimais';
        }
    }
    
    // Verificar se a parcela já existe
    $numeroParcela = $proximaParcela['numero'];
    $parcelaExistente = Comissao::getUltimaParcela($vendaId, $tipoComissao);
    if ($parcelaExistente && $parcelaExistente['numero_parcela'] >= $numeroParcela) {
        $errors[] = 'Esta parcela já foi gerada';
    }
    
    if (empty($errors)) {
        try {
            $comissaoId = Comissao::create([
                'venda_id' => $vendaId,
                'funcionario_id' => $funcionarioId,
                'tipo_comissao' => $tipoComissao,
                'parcela' => $parcela,
                'numero_parcela' => $numeroParcela,
                'porcentagem' => $porcentagemFloat,
                'created_by' => $user['id']
            ]);
            
            // Buscar comissão criada para log completo
            $comissaoCriada = Comissao::find($comissaoId);
            $valorComissao = $comissaoCriada ? (float)$comissaoCriada['valor_comissao'] : 0;
            
            // Log da ação
            ActivityLogger::logComissaoCreated(
                $comissaoId,
                $funcionario['nome'],
                $tipoComissao,
                $parcela,
                $porcentagemFloat,
                $valorBase,
                $valorComissao,
                $venda['numero_contrato']
            );
            
            // Notificar administradores
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT id FROM funcionarios 
                WHERE role_id = 1 AND is_ativo = 1 AND id != :created_by
            ");
            $stmt->execute([':created_by' => $user['id']]);
            $admins = $stmt->fetchAll();
            
            foreach ($admins as $admin) {
                Notification::create(
                    $admin['id'],
                    'Nova Comissão Gerada',
                    "Comissão de {$tipoComissao} gerada para {$funcionario['nome']} na venda #{$venda['numero_contrato']} - Parcela {$parcela}",
                    Notification::TYPE_SUCCESS,
                    base_url("comissoes/index.php?tipo={$tipoComissao}&funcionario_id={$funcionarioId}")
                );
            }
            
            // Se não houver outros admins, notifica o próprio criador se for admin
            if (count($admins) === 0 && $user['role_id'] == 1) {
                Notification::create(
                    $user['id'],
                    'Comissão Gerada',
                    "Comissão de {$tipoComissao} gerada para {$funcionario['nome']} na venda #{$venda['numero_contrato']} - Parcela {$parcela}",
                    Notification::TYPE_SUCCESS,
                    base_url("comissoes/index.php?tipo={$tipoComissao}&funcionario_id={$funcionarioId}")
                );
            }
            
            $_SESSION['success'] = "Comissão gerada com sucesso! Parcela {$parcela} ({$porcentagemFloat}%)";
            header('Location: index.php?tipo=' . urlencode($tipoComissao) . '&funcionario_id=' . $funcionarioId);
            exit;
        } catch (Exception $e) {
            $errors[] = 'Erro ao salvar comissão: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../app/views/partials/header.php';
?>

<link rel="stylesheet" href="<?= e(base_url('assets/css/forms.css')) ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= e(base_url('assets/css/comissoes.css')) ?>?v=<?= time() ?>">

<div class="main-container">
  <div class="clients-container">
    <!-- Cabeçalho -->
    <div class="clients-header">
      <div>
        <h1 class="clients-title">💰 Gerar Comissão</h1>
        <p class="clients-subtitle">Venda #<?= e($venda['numero_contrato']) ?> - <?= e($funcionario['nome']) ?> (<?= $tipoComissao === 'vendedor' ? 'Vendedor' : 'Virador' ?>)</p>
      </div>
      <div class="clients-actions">
        <a href="index.php?tipo=<?= e($tipoComissao) ?>&funcionario_id=<?= $funcionarioId ?>" class="btn-secondary-compact">
          ⬅ Voltar
        </a>
      </div>
    </div>

    <!-- Alertas -->
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-error">
        <strong>Erro:</strong> <?= e($_SESSION['error']) ?>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <strong>Erros:</strong>
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Informações da Venda -->
    <div class="form-section">
      <div class="form-section-title">📋 Informações da Venda</div>
      <div class="form-section-body">
        <div class="form-row">
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Contrato</label>
            <div class="info-value"><?= e($venda['numero_contrato']) ?></div>
          </div>
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Cliente</label>
            <div class="info-value"><?= e($venda['cliente_nome']) ?></div>
          </div>
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Administradora</label>
            <div class="info-value"><?= e($venda['administradora']) ?></div>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Tipo de Venda</label>
            <div class="info-value">
              <span class="badge <?= $venda['tipo'] === 'Normal' ? 'badge-primary' : 'badge-warning' ?>">
                <?= e($venda['tipo']) ?>
              </span>
            </div>
          </div>
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Valor do Crédito</label>
            <div class="info-value">R$ <?= number_format($venda['valor_credito'], 2, ',', '.') ?></div>
          </div>
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Valor Base para Comissão</label>
            <div class="info-value" style="font-weight: 700; color: var(--bcc-green); font-size: 1.25rem;">
              R$ <?= number_format($valorBase, 2, ',', '.') ?>
            </div>
                  <?php 
                  $adminNormalizada = strtolower(trim($venda['administradora'] ?? ''));
                  if ($adminNormalizada === 'gazin' && $venda['tipo'] === 'Meia'): 
                  ?>
                  <div class="text-xs text-muted" style="margin-top: 0.25rem;">
                    ⚠️ Meia parcela Gazin: valor dividido por 2
                  </div>
                  <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Comissões Anteriores -->
    <?php if (count($comissoesAnteriores) > 0): ?>
    <div class="form-section">
      <div class="form-section-title">📊 Comissões Já Geradas</div>
      <div class="form-section-body">
        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
          <table class="clients-table" style="font-size: 0.9rem;">
            <thead>
              <tr>
                <th>Parcela</th>
                <th>Porcentagem</th>
                <th>Valor Base</th>
                <th>Valor Comissão</th>
                <th>Data</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($comissoesAnteriores as $com): ?>
              <tr>
                <td><?= e($com['parcela']) ?></td>
                <td><?= number_format($com['porcentagem'], 2, ',', '.') ?>%</td>
                <td>R$ <?= number_format($com['valor_base'], 2, ',', '.') ?></td>
                <td><strong>R$ <?= number_format($com['valor_comissao'], 2, ',', '.') ?></strong></td>
                <td><?= date('d/m/Y H:i', strtotime($com['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Formulário de Comissão -->
    <form method="POST" class="comissoes-form">
      <div class="form-section">
        <div class="form-section-title">✨ Nova Comissão</div>
        <div class="form-section-body">
          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label class="form-label" for="parcela">
                📄 Descrição da Parcela <span class="text-danger">*</span>
              </label>
              <input 
                type="text" 
                id="parcela" 
                name="parcela" 
                class="form-control" 
                value="<?= e($proximaParcela['descricao']) ?>"
                placeholder="Ex: Parcela 1, Parcela 2, Parcela Final"
                required
              >
              <small class="form-text text-muted">
                Informe a descrição da parcela. Use "Parcela Final" para a última parcela.
              </small>
            </div>
            
            <div class="form-group" style="flex: 1;">
              <label class="form-label" for="porcentagem">
                📊 Porcentagem de Comissão (%) <span class="text-danger">*</span>
              </label>
              <input 
                type="number" 
                id="porcentagem" 
                name="porcentagem" 
                class="form-control" 
                step="0.01"
                min="0.01"
                max="100"
                placeholder="Ex: 5.00, 10.50"
                required
              >
              <small class="form-text text-muted">
                Informe a porcentagem (ex: 5.00 para 5%, 10.50 para 10.5%). Máximo 2 casas decimais.
              </small>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <div class="comissoes-preview">
                <strong>Preview:</strong>
                <div class="comissoes-preview-value">
                  <span id="preview-porcentagem">0.00</span>% sobre R$ <?= number_format($valorBase, 2, ',', '.') ?> = 
                  <strong id="preview-valor">R$ 0,00</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <a href="index.php?tipo=<?= e($tipoComissao) ?>&funcionario_id=<?= $funcionarioId ?>" class="btn-cancel no-icon">
          Cancelar
        </a>
        <button type="submit" class="btn-save no-icon">
          💰 Gerar Comissão
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Preview do valor da comissão
document.addEventListener('DOMContentLoaded', function() {
  const porcentagemInput = document.getElementById('porcentagem');
  const previewPorcentagem = document.getElementById('preview-porcentagem');
  const previewValor = document.getElementById('preview-valor');
  const valorBase = <?= $valorBase ?>;
  
  function updatePreview() {
    const porcentagem = parseFloat(porcentagemInput.value) || 0;
    const valorComissao = (valorBase * porcentagem) / 100;
    
    previewPorcentagem.textContent = porcentagem.toFixed(2);
    previewValor.textContent = 'R$ ' + valorComissao.toLocaleString('pt-BR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }
  
  porcentagemInput.addEventListener('input', updatePreview);
  porcentagemInput.addEventListener('change', updatePreview);
});
</script>

<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>

