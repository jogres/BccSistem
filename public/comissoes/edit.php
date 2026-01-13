<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/lib/ActivityLogger.php';
require __DIR__ . '/../../app/lib/Logger.php';
require __DIR__ . '/../../app/models/Comissao.php';
require __DIR__ . '/../../app/models/Venda.php';
require __DIR__ . '/../../app/models/Funcionario.php';
require __DIR__ . '/../../app/middleware/require_admin.php';

$user = Auth::user();
$errors = [];
$success = false;

// Verificar ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Comissão não encontrada';
    header('Location: index.php');
    exit;
}

$comissaoId = (int)$_GET['id'];

// Buscar comissão
$comissao = Comissao::find($comissaoId);
if (!$comissao) {
    $_SESSION['error'] = 'Comissão não encontrada';
    header('Location: index.php');
    exit;
}

// Buscar venda
$venda = Venda::find($comissao['venda_id']);
if (!$venda) {
    $_SESSION['error'] = 'Venda não encontrada';
    header('Location: index.php');
    exit;
}

// Buscar funcionário
$funcionario = Funcionario::find($comissao['funcionario_id']);
if (!$funcionario) {
    $_SESSION['error'] = 'Funcionário não encontrado';
    header('Location: index.php');
    exit;
}

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
    
    if (empty($errors)) {
        try {
            // Obter valores antigos para log
            $valoresAntigos = [
                'parcela' => $comissao['parcela'],
                'porcentagem' => $comissao['porcentagem'],
                'valor_base' => $comissao['valor_base'],
                'valor_comissao' => $comissao['valor_comissao']
            ];
            
            // Atualizar comissão
            Comissao::update($comissaoId, [
                'parcela' => $parcela,
                'porcentagem' => $porcentagemFloat,
                'recalcular_valor_base' => false // Manter valor base original
            ]);
            
            // Buscar comissão atualizada
            $comissaoAtualizada = Comissao::find($comissaoId);
            
            // Log da atualização
            Logger::crud('UPDATE', 'comissoes', $comissaoId, $user['id'], [
                'venda_id' => $comissao['venda_id'],
                'funcionario_id' => $comissao['funcionario_id'],
                'tipo_comissao' => $comissao['tipo_comissao'],
                'parcela_anterior' => $valoresAntigos['parcela'],
                'parcela_nova' => $parcela,
                'porcentagem_anterior' => $valoresAntigos['porcentagem'],
                'porcentagem_nova' => $porcentagemFloat,
                'valor_comissao_anterior' => $valoresAntigos['valor_comissao'],
                'valor_comissao_novo' => $comissaoAtualizada['valor_comissao']
            ]);
            
            $_SESSION['success'] = "Comissão atualizada com sucesso!";
            header('Location: index.php?visualizar=comissoes&funcionario_comissao_id=' . $comissao['funcionario_id']);
            exit;
        } catch (Exception $e) {
            $errors[] = 'Erro ao atualizar comissão: ' . $e->getMessage();
            Logger::error('Erro ao atualizar comissão', [
                'id' => $comissaoId,
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
        }
    }
}

// Calcular valor base atual
$valorBase = (float)$comissao['valor_base'];

include __DIR__ . '/../../app/views/partials/header.php';
?>

<link rel="stylesheet" href="<?= e(base_url('assets/css/forms.css')) ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= e(base_url('assets/css/comissoes.css')) ?>?v=<?= time() ?>">

<div class="main-container">
  <div class="clients-container">
    <!-- Cabeçalho -->
    <div class="clients-header">
      <div>
        <h1 class="clients-title">✏️ Editar Comissão</h1>
        <p class="clients-subtitle">
          Comissão #<?= $comissaoId ?> - Venda #<?= e($venda['numero_contrato']) ?> - 
          <?= e($funcionario['nome']) ?> (<?= ucfirst(e($comissao['tipo_comissao'])) ?>)
        </p>
      </div>
      <div class="clients-actions">
        <a href="index.php?visualizar=comissoes&funcionario_comissao_id=<?= $comissao['funcionario_id'] ?>" 
           class="btn-secondary-compact">
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

    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success">
        <strong>Sucesso:</strong> <?= e($_SESSION['success']) ?>
      </div>
      <?php unset($_SESSION['success']); ?>
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

    <!-- Informações da Comissão -->
    <div class="form-section">
      <div class="form-section-title">💰 Informações da Comissão</div>
      <div class="form-section-body">
        <div class="form-row">
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Funcionário</label>
            <div class="info-value"><?= e($funcionario['nome']) ?></div>
          </div>
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Tipo de Comissão</label>
            <div class="info-value">
              <span class="badge <?= $comissao['tipo_comissao'] === 'vendedor' ? 'badge-primary' : 'badge-success' ?>">
                <?= ucfirst(e($comissao['tipo_comissao'])) ?>
              </span>
            </div>
          </div>
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Número da Parcela</label>
            <div class="info-value"><?= $comissao['numero_parcela'] ?></div>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Data de Criação</label>
            <div class="info-value">
              <?= date('d/m/Y H:i', strtotime($comissao['created_at'])) ?>
            </div>
          </div>
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Criado por</label>
            <div class="info-value"><?= e($comissao['created_by_nome'] ?? 'Sistema') ?></div>
          </div>
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Valor Base</label>
            <div class="info-value">
              <strong>R$ <?= number_format($comissao['valor_base'], 2, ',', '.') ?></strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Formulário de Edição -->
    <form method="POST" class="comissoes-form">
      <div class="form-section">
        <div class="form-section-title">✏️ Editar Comissão</div>
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
                value="<?= e($comissao['parcela']) ?>"
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
                value="<?= number_format($comissao['porcentagem'], 2, '.', '') ?>"
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
                  <span id="preview-porcentagem"><?= number_format($comissao['porcentagem'], 2, ',', '.') ?></span>% sobre R$ <?= number_format($valorBase, 2, ',', '.') ?> = 
                  <strong id="preview-valor">R$ <?= number_format($comissao['valor_comissao'], 2, ',', '.') ?></strong>
                </div>
                <div class="text-xs text-muted" style="margin-top: 0.5rem;">
                  <strong>Valor atual:</strong> R$ <?= number_format($comissao['valor_comissao'], 2, ',', '.') ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <a href="index.php?visualizar=comissoes&funcionario_comissao_id=<?= $comissao['funcionario_id'] ?>" 
           class="btn-cancel no-icon">
          Cancelar
        </a>
        <button type="submit" class="btn-save no-icon">
          💾 Salvar Alterações
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
    
    previewPorcentagem.textContent = porcentagem.toFixed(2).replace('.', ',');
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
