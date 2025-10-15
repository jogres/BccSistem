<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/lib/Logger.php';
require __DIR__ . '/../../app/lib/FileUpload.php';
require __DIR__ . '/../../app/lib/Notification.php';
require __DIR__ . '/../../app/models/Venda.php';
require __DIR__ . '/../../app/models/Cliente.php';
require __DIR__ . '/../../app/models/Funcionario.php';
require __DIR__ . '/../../app/middleware/require_login.php';

$user = Auth::user();
$isAdmin = Auth::isAdmin();

// Verificar se pode criar vendas (padrão ou admin)
if (!Auth::isPadrao() && !Auth::isAdmin()) {
    Logger::accessDenied('vendas/create.php', $user['id'], 'Perfil sem permissão para criar vendas');
    $_SESSION['error'] = 'Acesso negado. Apenas funcionários com perfil padrão ou administrador podem cadastrar vendas.';
    header('Location: index.php');
    exit;
}

// Buscar clientes para o select
$userId = $isAdmin ? null : (int)$user['id'];
$clientes = Cliente::allForUser($userId);

// Buscar funcionários ativos
$funcionarios = Funcionario::allActive();

// Opções de configuração
$opcoesInteresse = require __DIR__ . '/../../app/config/interesses.php';
$opcoesAdministradoras = require __DIR__ . '/../../app/config/administradoras.php';

$errors = [];
$success = false;

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validações manuais
    $requiredFields = [
        'cliente_id' => 'Cliente',
        'cliente_nome_editado' => 'Nome do cliente',
        'vendedor_id' => 'Vendedor',
        'virador_id' => 'Virador',
        'numero_contrato' => 'Número do contrato',
        'rua' => 'Rua',
        'bairro' => 'Bairro',
        'numero' => 'Número',
        'cep' => 'CEP',
        'cpf' => 'CPF',
        'segmento' => 'Segmento',
        'tipo' => 'Tipo',
        'administradora' => 'Administradora',
        'valor_credito' => 'Valor do crédito'
    ];
    
    foreach ($requiredFields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[] = "{$label} é obrigatório";
        }
    }
    
    // Validações específicas
    if (!$errors) {
        $clienteId = (int)$_POST['cliente_id'];
        $numeroContrato = trim($_POST['numero_contrato']);
        
        // Verificar se cliente existe
        $cliente = Cliente::find($clienteId);
        if (!$cliente) {
            $errors[] = 'Cliente não encontrado';
        }
        
        // Verificar se contrato já existe
        if (Venda::contratoExists($numeroContrato)) {
            $errors[] = 'Número de contrato já existe no sistema';
        }
        
        // Validar CPF (formato básico)
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        if (strlen($cpf) !== 11) {
            $errors[] = 'CPF inválido';
        }
        
        // Validar CEP
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep']);
        if (strlen($cep) !== 8) {
            $errors[] = 'CEP inválido';
        }
        
        // Validar valor
        $valorCredito = str_replace(['.', ','], ['', '.'], $_POST['valor_credito']);
        if (!is_numeric($valorCredito) || $valorCredito <= 0) {
            $errors[] = 'Valor do crédito inválido';
        }
    }
    
    // Upload do contrato
    $arquivoContrato = null;
    if (!$errors && isset($_FILES['arquivo_contrato']) && $_FILES['arquivo_contrato']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = FileUpload::upload($_FILES['arquivo_contrato'], 'contratos', true);
        
        if ($uploadResult['success']) {
            $arquivoContrato = $uploadResult['filename'];
        } else {
            $errors[] = 'Erro no upload do contrato: ' . $uploadResult['error'];
        }
    } elseif (!isset($_FILES['arquivo_contrato']) || $_FILES['arquivo_contrato']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Se houve erro no upload (mas não foi "nenhum arquivo")
        if (isset($_FILES['arquivo_contrato']['error']) && $_FILES['arquivo_contrato']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Erro no upload do arquivo';
        }
    }
    
    // Criar venda
    if (!$errors) {
        try {
            // Atualizar nome do cliente se foi editado
            $nomeEditado = trim($_POST['cliente_nome_editado']);
            if ($nomeEditado !== $cliente['nome']) {
                Cliente::updateFields($clienteId, ['nome' => $nomeEditado]);
            }
            
            $vendaData = [
                'cliente_id' => $clienteId,
                'vendedor_id' => (int)$_POST['vendedor_id'],
                'virador_id' => (int)$_POST['virador_id'],
                'numero_contrato' => $numeroContrato,
                'rua' => trim($_POST['rua']),
                'bairro' => trim($_POST['bairro']),
                'numero' => trim($_POST['numero']),
                'cep' => $cep,
                'cpf' => $cpf,
                'segmento' => $_POST['segmento'],
                'tipo' => $_POST['tipo'],
                'administradora' => $_POST['administradora'],
                'valor_credito' => $valorCredito,
                'arquivo_contrato' => $arquivoContrato
            ];
            
            $vendaId = Venda::create($vendaData);
            
            if (!$vendaId) {
                Logger::error('Falha ao criar venda - ID não retornado', [
                    'user_id' => $user['id'],
                    'data' => $vendaData
                ]);
                throw new Exception('Falha ao criar venda - ID não retornado');
            }
            
            // Log da criação
            Logger::crud('CREATE', 'vendas', $vendaId, $user['id'], [
                'numero_contrato' => $numeroContrato,
                'cliente_id' => $clienteId,
                'valor_credito' => $valorCredito
            ]);
            
            // Criar notificações
            // Notificar vendedor (se não for o próprio usuário)
            if ($vendaData['vendedor_id'] != $user['id']) {
                Notification::create(
                    $vendaData['vendedor_id'],
                    'Nova Venda Registrada',
                    "Venda #{$numeroContrato} foi registrada em seu nome. Cliente: {$cliente['nome']}",
                    Notification::TYPE_SUCCESS,
                    base_url("vendas/view.php?id={$vendaId}")
                );
            }
            
            // Notificar virador (se diferente do vendedor e do usuário atual)
            if ($vendaData['virador_id'] != $vendaData['vendedor_id'] && $vendaData['virador_id'] != $user['id']) {
                Notification::create(
                    $vendaData['virador_id'],
                    'Nova Venda Registrada',
                    "Venda #{$numeroContrato} foi registrada com você como virador. Cliente: {$cliente['nome']}",
                    Notification::TYPE_SUCCESS,
                    base_url("vendas/view.php?id={$vendaId}")
                );
            }
            
            // Notificar administradores
            if (!$isAdmin) {
                $pdo = Database::getConnection();
                $stmt = $pdo->query("SELECT id FROM funcionarios WHERE role_id = 1 AND is_ativo = 1");
                $admins = $stmt->fetchAll();
                
                foreach ($admins as $admin) {
                    Notification::create(
                        $admin['id'],
                        'Nova Venda Cadastrada',
                        "Venda #{$numeroContrato} cadastrada por {$user['nome']}. Cliente: {$cliente['nome']}",
                        Notification::TYPE_INFO,
                        base_url("vendas/view.php?id={$vendaId}")
                    );
                }
            }
            
            $_SESSION['success'] = 'Venda cadastrada com sucesso!';
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            Logger::error('Erro ao cadastrar venda', [
                'user_id' => $user['id'],
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $errors[] = 'Erro ao cadastrar venda: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="main-container">
    <div class="clients-container">
        <div class="clients-header">
            <div>
                <h1 class="clients-title">🛒 Nova Venda</h1>
                <p class="clients-subtitle">Cadastre uma nova venda no sistema</p>
            </div>
            <div class="clients-actions">
                <a href="index.php" class="btn-secondary-compact">⬅ Voltar</a>
            </div>
        </div>

            <!-- Alertas -->
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <h6 class="alert-heading">⚠ Erros encontrados:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Formulário -->
            <form method="POST" enctype="multipart/form-data" id="vendaForm" class="form-container">
                <!-- Seleção de Cliente -->
                <div class="form-section">
                    <div class="form-section-title">1. Selecione o Cliente</div>
                    <div class="form-section-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Cliente *</label>
                                <select name="cliente_id" id="cliente_id" class="form-control" required>
                                    <option value="">Selecione um cliente...</option>
                                    <?php foreach ($clientes as $cli): ?>
                                        <option value="<?= $cli['id'] ?>" 
                                                data-nome="<?= e($cli['nome']) ?>"
                                                data-telefone="<?= e($cli['telefone']) ?>"
                                                data-cidade="<?= e($cli['cidade']) ?>"
                                                data-estado="<?= e($cli['estado']) ?>"
                                                data-interesse="<?= e($cli['interesse']) ?>">
                                            <?= e($cli['nome']) ?> - <?= e($cli['telefone']) ?> (<?= e($cli['cidade']) ?>/<?= e($cli['estado']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Dados do Cliente (preenchidos automaticamente) -->
                        <div id="clienteInfo" class="sale-info-box" style="display: none; margin-top: var(--space-4);">
                            <h6 class="mb-3">Dados do Cliente Selecionado</h6>
                            <div class="form-row">
                                <div class="form-group" style="flex: 2;">
                                    <label class="form-label">Nome do Cliente (editável) *</label>
                                    <input type="text" name="cliente_nome_editado" id="cliente_nome_editado" class="form-control" 
                                           placeholder="Nome será preenchido automaticamente" required>
                                    <small class="text-muted">Você pode editar o nome do cliente aqui (altera permanentemente)</small>
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label">Telefone</label>
                                    <input type="text" id="info_telefone" class="form-control" readonly style="background: var(--bcc-gray-100);">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label class="form-label">Cidade/Estado</label>
                                    <input type="text" id="info_cidade_estado" class="form-control" readonly style="background: var(--bcc-gray-100);">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Endereço Completo -->
                <div class="form-section">
                    <div class="form-section-title">2. Endereço Completo</div>
                    <div class="form-section-body">
                        <div class="form-row">
                            <div class="col-md-8">
                                <label class="form-label">Rua *</label>
                                <input type="text" name="rua" class="form-control" 
                                       value="<?= e($_POST['rua'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Número *</label>
                                <input type="text" name="numero" class="form-control" 
                                       value="<?= e($_POST['numero'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bairro *</label>
                                <input type="text" name="bairro" class="form-control" 
                                       value="<?= e($_POST['bairro'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CEP *</label>
                                <input type="text" name="cep" class="form-control" 
                                       placeholder="00000-000" 
                                       value="<?= e($_POST['cep'] ?? '') ?>" 
                                       maxlength="9" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CPF do Cliente *</label>
                                <input type="text" name="cpf" class="form-control" 
                                       placeholder="000.000.000-00" 
                                       value="<?= e($_POST['cpf'] ?? '') ?>" 
                                       maxlength="14" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados da Venda -->
                <div class="form-section">
                    <div class="form-section-title">3. Dados da Venda</div>
                    <div class="form-section-body">
                        <div class="form-row">
                            <div class="col-md-6">
                                <label class="form-label">Número do Contrato *</label>
                                <input type="text" name="numero_contrato" class="form-control" 
                                       value="<?= e($_POST['numero_contrato'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor do Crédito *</label>
                                <input type="text" name="valor_credito" class="form-control" 
                                       placeholder="0,00" 
                                       value="<?= e($_POST['valor_credito'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendedor *</label>
                                <select name="vendedor_id" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($funcionarios as $func): ?>
                                        <option value="<?= $func['id'] ?>" 
                                                <?= isset($_POST['vendedor_id']) && $_POST['vendedor_id'] == $func['id'] ? 'selected' : '' ?>>
                                            <?= e($func['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Virador *</label>
                                <select name="virador_id" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($funcionarios as $func): ?>
                                        <option value="<?= $func['id'] ?>" 
                                                <?= isset($_POST['virador_id']) && $_POST['virador_id'] == $func['id'] ? 'selected' : '' ?>>
                                            <?= e($func['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Pode ser o mesmo que o vendedor</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Segmento *</label>
                                <select name="segmento" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($opcoesInteresse as $interesse): ?>
                                        <option value="<?= e($interesse) ?>" 
                                                <?= isset($_POST['segmento']) && $_POST['segmento'] === $interesse ? 'selected' : '' ?>>
                                            <?= e($interesse) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo de Venda *</label>
                                <select name="tipo" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <option value="Normal" <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Normal' ? 'selected' : '' ?>>Normal</option>
                                    <option value="Meia" <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Meia' ? 'selected' : '' ?>>Meia</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Administradora *</label>
                                <select name="administradora" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($opcoesAdministradoras as $adm): ?>
                                        <option value="<?= e($adm) ?>" 
                                                <?= isset($_POST['administradora']) && $_POST['administradora'] === $adm ? 'selected' : '' ?>>
                                            <?= e($adm) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload do Contrato -->
                <div class="form-section">
                    <div class="form-section-title">4. Contrato (Opcional)</div>
                    <div class="form-section-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Arquivo do Contrato</label>
                                <input type="file" name="arquivo_contrato" class="form-control" 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <small class="text-muted">
                                    Formatos aceitos: PDF, DOC, DOCX, JPG, PNG (Máx: 5MB)
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="form-actions">
                    <a href="index.php" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-save">Cadastrar Venda</button>
                </div>
            </form>
    </div>
</div>

<script>
// Preencher informações do cliente ao selecionar
document.getElementById('cliente_id').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const infoDiv = document.getElementById('clienteInfo');
    
    if (this.value) {
        // Preencher campo editável do nome
        document.getElementById('cliente_nome_editado').value = option.dataset.nome;
        document.getElementById('info_telefone').value = option.dataset.telefone;
        document.getElementById('info_cidade_estado').value = option.dataset.cidade + '/' + option.dataset.estado;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
        document.getElementById('cliente_nome_editado').value = '';
    }
});

// Máscaras
document.querySelector('input[name="cep"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 5) {
        value = value.substring(0, 5) + '-' + value.substring(5, 8);
    }
    e.target.value = value;
});

document.querySelector('input[name="cpf"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 3) {
        value = value.substring(0, 3) + '.' + value.substring(3);
    }
    if (value.length > 7) {
        value = value.substring(0, 7) + '.' + value.substring(7);
    }
    if (value.length > 11) {
        value = value.substring(0, 11) + '-' + value.substring(11, 13);
    }
    e.target.value = value;
});

// Máscara de valor em reais
document.querySelector('input[name="valor_credito"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = (value / 100).toFixed(2);
    value = value.replace('.', ',');
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    e.target.value = value;
});
</script>

<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>

