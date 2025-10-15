<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/models/Venda.php';
require __DIR__ . '/../../app/middleware/require_login.php';

$user = Auth::user();
$isAdmin = Auth::isAdmin();

// Verificar ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Venda não encontrada';
    header('Location: index.php');
    exit;
}

$vendaId = (int)$_GET['id'];
$venda = Venda::find($vendaId);

if (!$venda) {
    $_SESSION['error'] = 'Venda não encontrada';
    header('Location: index.php');
    exit;
}

// Verificar permissão de visualização
if (!Venda::canView($vendaId, (int)$user['id'], $isAdmin)) {
    $_SESSION['error'] = 'Você não tem permissão para visualizar esta venda';
    header('Location: index.php');
    exit;
}

include __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="main-container">
    <div class="clients-container">
        <div class="clients-header">
            <div>
                <h1 class="clients-title">📄 Detalhes da Venda</h1>
                <p class="clients-subtitle">Contrato #<?= e($venda['numero_contrato']) ?></p>
            </div>
            <div class="clients-actions">
                <?php if ($isAdmin): ?>
                    <a href="edit.php?id=<?= $venda['id'] ?>" class="btn-client-edit">✏️ Editar</a>
                <?php endif; ?>
                <a href="index.php" class="btn-secondary-compact">⬅ Voltar</a>
            </div>
        </div>

            <!-- Alertas -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> <?= e($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Informações Principais -->
            <div class="stats-grid">
                <div class="col-md-3">
                    <div class="stat-card">
                            <div class="text-primary mb-2">
                            💰
                            </div>
                            <div class="stat-label">Valor do Crédito</div>
                            <div class="stat-value" style="color: var(--accent-700);">
                                R$ <?= number_format($venda['valor_credito'], 2, ',', '.') ?>
                            </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                            <div class="text-info mb-2">
                            🏢
                            </div>
                            <div class="stat-label">Administradora</div>
                            <div class="stat-value" style="font-size: 1.25rem;">
                                <?= e($venda['administradora']) ?>
                            </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                            <div class="text-warning mb-2">
                            🏷️
                            </div>
                            <div class="stat-label">Tipo de Venda</div>
                            <div class="stat-value" style="font-size: 1rem;">
                                <span class="badge <?= $venda['tipo'] === 'Normal' ? 'badge-primary' : 'badge-warning' ?>">
                                    <?= e($venda['tipo']) ?>
                                </span>
                            </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                            <div class="text-secondary mb-2">
                            📅
                            </div>
                            <div class="stat-label">Data da Venda</div>
                            <div class="stat-value" style="font-size: 1.25rem;">
                                <?= date('d/m/Y', strtotime($venda['created_at'])) ?>
                            </div>
                            <div class="text-xs text-muted" style="padding: .25rem 0;">
                                <?= date('H:i', strtotime($venda['created_at'])) ?>
                            </div>
                    </div>
                </div>
            </div>

            <!-- Dados do Cliente -->
            <div class="form-section">
                <div class="form-section-title">👤 Dados do Cliente</div>
                <div class="form-section-body">
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label">Nome Completo</label>
                            <div class="info-value"><?= e($venda['cliente_nome']) ?></div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">CPF</label>
                            <div class="info-value"><?= formatCpf($venda['cpf']) ?></div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Telefone</label>
                            <div class="info-value"><?= e($venda['cliente_telefone']) ?></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label">Endereço</label>
                            <div class="info-value"><?= e($venda['rua']) ?>, <?= e($venda['numero']) ?></div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Bairro</label>
                            <div class="info-value"><?= e($venda['bairro']) ?></div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">CEP</label>
                            <div class="info-value"><?= formatCep($venda['cep']) ?></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Cidade</label>
                            <div class="info-value"><?= e($venda['cliente_cidade']) ?></div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Estado</label>
                            <div class="info-value"><?= strtoupper(e($venda['cliente_estado'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dados da Venda -->
            <div class="form-section">
                <div class="form-section-title">📋 Dados da Venda</div>
                <div class="form-section-body">
                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Número do Contrato</label>
                            <div class="info-value" style="font-weight: 700; color: var(--bcc-blue);">
                                <?= e($venda['numero_contrato']) ?>
                            </div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Segmento</label>
                            <div class="info-value"><?= e($venda['segmento']) ?></div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Tipo de Venda</label>
                            <div class="info-value">
                                <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); 
                                             background: <?= $venda['tipo'] === 'Normal' ? 'var(--bcc-blue-bg)' : 'var(--bcc-orange-bg)' ?>; 
                                             color: <?= $venda['tipo'] === 'Normal' ? 'var(--bcc-blue)' : 'var(--bcc-orange-dark)' ?>; 
                                             font-weight: 600; font-size: 0.875rem;">
                                    <?= e($venda['tipo']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Administradora</label>
                            <div class="info-value"><?= e($venda['administradora']) ?></div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Valor do Crédito</label>
                            <div class="info-value" style="font-size: 1.25rem; font-weight: 700; color: var(--bcc-green);">
                                R$ <?= number_format($venda['valor_credito'], 2, ',', '.') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Responsáveis -->
            <div class="form-section">
                <div class="form-section-title">👥 Responsáveis pela Venda</div>
                <div class="form-section-body">
                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Vendedor</label>
                            <div class="info-value" style="display: flex; align-items: center; gap: var(--space-3);">
                                <div style="width: 40px; height: 40px; border-radius: 50%; 
                                            background: linear-gradient(135deg, var(--bcc-blue), var(--bcc-blue-light)); 
                                            color: var(--bcc-white); display: flex; align-items: center; 
                                            justify-content: center; font-size: 1.25rem; font-weight: 700;">
                                    👨‍💼
                                </div>
                                <span style="font-weight: 600;"><?= e($venda['vendedor_nome']) ?></span>
                            </div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Virador</label>
                            <div class="info-value" style="display: flex; align-items: center; gap: var(--space-3);">
                                <div style="width: 40px; height: 40px; border-radius: 50%; 
                                            background: linear-gradient(135deg, var(--bcc-green), var(--bcc-green-dark)); 
                                            color: var(--bcc-white); display: flex; align-items: center; 
                                            justify-content: center; font-size: 1.25rem; font-weight: 700;">
                                    ✅
                                </div>
                                <span style="font-weight: 600;"><?= e($venda['virador_nome']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contrato Anexado -->
            <?php if ($venda['arquivo_contrato']): ?>
                <div class="form-section">
                    <div class="form-section-title">Contrato Anexado</div>
                    <div class="form-section-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="text-danger me-3" style="font-size: 2.5rem;">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </div>
                                <div>
                                    <p class="mb-0 fw-bold"><?= e($venda['arquivo_contrato']) ?></p>
                                    <small class="text-muted">Arquivo do contrato</small>
                                </div>
                            </div>
                            <a href="<?= base_url('uploads/contratos/' . $venda['arquivo_contrato']) ?>" 
                               class="btn-primary" 
                               download
                               target="_blank">
                                ⬇ Download
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-section">
                    <div class="form-section-body text-center py-4">
                        <i class="bi bi-file-earmark-x text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2 mb-0">Nenhum contrato anexado</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Informações do Sistema -->
            <div class="form-section">
                <div class="form-section-body">
                    <div class="row g-3 small text-muted">
                        <div class="col-md-6">
                            <i class="bi bi-clock-history"></i>
                            <strong>Cadastrado em:</strong> 
                            <?= date('d/m/Y \à\s H:i', strtotime($venda['created_at'])) ?>
                        </div>
                        <div class="col-md-6">
                            <i class="bi bi-arrow-repeat"></i>
                            <strong>Última atualização:</strong> 
                            <?= date('d/m/Y \à\s H:i', strtotime($venda['updated_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações -->
            <div class="form-actions" style="justify-content: space-between;">
                <a href="index.php" class="btn-secondary-compact">⬅ Voltar para Lista</a>
                <?php if ($isAdmin): ?>
                    <div>
                        <a href="edit.php?id=<?= $venda['id'] ?>" class="btn-client-edit">✏️ Editar</a>
                        <button type="button" class="btn-client-delete" onclick="confirmDelete()">
                            🗑️ Excluir
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Form de delete (hidden) -->
<form id="deleteForm" method="POST" action="delete.php" style="display: none;">
    <input type="hidden" name="id" value="<?= $venda['id'] ?>">
</form>

<script>
function confirmDelete() {
    if (confirm('Tem certeza que deseja excluir esta venda? Esta ação não pode ser desfeita.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
<?php endif; ?>

<?php
// Funções auxiliares para formatação
function formatCpf($cpf) {
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function formatCep($cep) {
    return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);
}
?>

<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>

