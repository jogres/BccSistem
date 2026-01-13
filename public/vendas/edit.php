<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/lib/Logger.php';
require __DIR__ . '/../../app/middleware/require_admin.php'; // Apenas admin pode editar
require __DIR__ . '/../../app/lib/FileUpload.php';
require __DIR__ . '/../../app/models/Venda.php';
require __DIR__ . '/../../app/models/Cliente.php';
require __DIR__ . '/../../app/models/Funcionario.php';

$user = Auth::user();

// Verificar ID
if (!isset($_GET['id'])) {
    Logger::warning('Tentativa de editar venda sem ID', ['user_id' => $user['id']]);
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

// Buscar clientes e funcionários
$clientes = Cliente::allForUser(null); // Admin vê todos
$funcionarios = Funcionario::allActive();

// Opções de configuração
$opcoesInteresse = require __DIR__ . '/../../app/config/interesses.php';
$opcoesAdministradoras = require __DIR__ . '/../../app/config/administradoras.php';

$errors = [];

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
        
        // Verificar se contrato já existe (excluindo o atual)
        if (Venda::contratoExists($numeroContrato, $vendaId)) {
            $errors[] = 'Número de contrato já existe no sistema';
        }
        
        // Validar CPF
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        if (strlen($cpf) !== 11) {
            $errors[] = 'CPF inválido';
        }
        
        // Validar CEP
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep']);
        if (strlen($cep) !== 8) {
            $errors[] = 'CEP inválido';
        }
        
        // Validar valor (formato brasileiro: 2.000.000,00)
        $valorCreditoStr = $_POST['valor_credito'] ?? '';
        // Remove espaços e caracteres não numéricos exceto ponto e vírgula
        $valorCreditoStr = preg_replace('/[^\d,.]/', '', $valorCreditoStr);
        
        // Se tem vírgula, assume formato brasileiro (ponto = milhar, vírgula = decimal)
        if (strpos($valorCreditoStr, ',') !== false) {
            // Remove pontos (separadores de milhar) e converte vírgula para ponto
            $valorCredito = str_replace('.', '', $valorCreditoStr); // Remove pontos de milhar
            $valorCredito = str_replace(',', '.', $valorCredito); // Converte vírgula para ponto decimal
        } else {
            // Se não tem vírgula, pode ter ponto como decimal ou como milhar
            // Se tem múltiplos pontos, são separadores de milhar
            $partes = explode('.', $valorCreditoStr);
            if (count($partes) > 2) {
                // Múltiplos pontos = separadores de milhar, remove todos
                $valorCredito = str_replace('.', '', $valorCreditoStr);
            } else {
                // Um ponto = pode ser decimal ou milhar
                // Se a parte após o ponto tem 3 dígitos, é milhar; se tem 1-2, é decimal
                if (count($partes) === 2 && strlen($partes[1]) === 3) {
                    // É milhar, remove o ponto
                    $valorCredito = str_replace('.', '', $valorCreditoStr);
                } else {
                    // É decimal, mantém
                    $valorCredito = $valorCreditoStr;
                }
            }
        }
        
        if (!is_numeric($valorCredito) || $valorCredito <= 0) {
            $errors[] = 'Valor do crédito inválido. Use o formato: 2.000.000,00 ou 2000000,00';
        } else {
            // Garantir que é float
            $valorCredito = (float)$valorCredito;
        }
    }
    
    // Upload do contrato (se enviado novo)
    $arquivoContrato = $venda['arquivo_contrato']; // Mantém o atual
    if (!$errors && isset($_FILES['arquivo_contrato']) && $_FILES['arquivo_contrato']['error'] === UPLOAD_ERR_OK) {
        // Deletar arquivo antigo se existir
        if ($venda['arquivo_contrato']) {
            FileUpload::delete($venda['arquivo_contrato'], 'contratos');
        }
        
        $uploadResult = FileUpload::upload($_FILES['arquivo_contrato'], 'contratos', true);
        
        if ($uploadResult['success']) {
            $arquivoContrato = $uploadResult['filename'];
        } else {
            $errors[] = 'Erro no upload do contrato: ' . $uploadResult['error'];
        }
    }
    
    // Atualizar venda
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
            
            Venda::update($vendaId, $vendaData);
            
            // Log da atualização
            Logger::crud('UPDATE', 'vendas', $vendaId, $user['id'], [
                'numero_contrato' => $vendaData['numero_contrato'],
                'cliente_id' => $vendaData['cliente_id'],
                'valor_credito' => $vendaData['valor_credito']
            ]);
            
            $_SESSION['success'] = 'Venda atualizada com sucesso!';
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            Logger::error('Erro ao atualizar venda', [
                'user_id' => $user['id'],
                'venda_id' => $vendaId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $errors[] = 'Erro ao atualizar venda: ' . $e->getMessage();
        }
    }
    
    // Se houve erros, usa os dados do POST
    if ($errors) {
        $venda = array_merge($venda, $_POST);
    }
}

include __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="main-container">
    <div class="clients-container">
        <div class="clients-header">
            <div>
                <h1 class="clients-title">✏️ Editar Venda</h1>
                <p class="clients-subtitle">Contrato #<?= e($venda['numero_contrato']) ?></p>
            </div>
            <div class="clients-actions">
                <a href="view.php?id=<?= $vendaId ?>" class="btn-secondary-compact">⬅ Voltar</a>
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

            <div class="alert alert-warning">
                <strong>Atenção:</strong> Você está editando uma venda existente. Qualquer alteração será registrada no sistema.
            </div>

            <!-- Formulário -->
            <form method="POST" enctype="multipart/form-data" id="vendaForm" class="form-container">
                <!-- Seleção de Cliente -->
                <div class="form-section">
                    <div class="form-section-title">1. Cliente</div>
                    <div class="form-section-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Cliente *</label>
                                <select name="cliente_id" id="cliente_id" class="form-control" required>
                                    <option value="">Selecione um cliente...</option>
                                    <?php foreach ($clientes as $cli): ?>
                                        <option value="<?= $cli['id'] ?>" 
                                                <?= $venda['cliente_id'] == $cli['id'] ? 'selected' : '' ?>
                                                data-nome="<?= e($cli['nome']) ?>"
                                                data-telefone="<?= e($cli['telefone']) ?>"
                                                data-cidade="<?= e($cli['cidade']) ?>"
                                                data-estado="<?= e($cli['estado']) ?>">
                                            <?= e($cli['telefone']) ?> - <?= e($cli['nome']) ?> (<?= e($cli['cidade']) ?>/<?= e($cli['estado']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Info do cliente selecionado -->
                        <div class="form-row" style="margin-top: var(--space-4);">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">Nome do Cliente (editável) *</label>
                                <input type="text" name="cliente_nome_editado" class="form-control" 
                                       value="<?= e($venda['cliente_nome']) ?>" required>
                                <small class="text-muted">Você pode editar o nome do cliente aqui (altera permanentemente)</small>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" readonly style="background: var(--bcc-gray-100);" 
                                       value="<?= e($venda['cliente_telefone']) ?>">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">Cidade/Estado</label>
                                <input type="text" class="form-control" readonly style="background: var(--bcc-gray-100);" 
                                       value="<?= e($venda['cliente_cidade']) ?>/<?= e($venda['cliente_estado']) ?>">
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
                                       value="<?= e($venda['rua']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Número *</label>
                                <input type="text" name="numero" class="form-control" 
                                       value="<?= e($venda['numero']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bairro *</label>
                                <input type="text" name="bairro" class="form-control" 
                                       value="<?= e($venda['bairro']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CEP *</label>
                                <input type="text" name="cep" class="form-control" 
                                       placeholder="00000-000" 
                                       value="<?= !empty($venda['cep']) ? formatCep($venda['cep']) : '' ?>" 
                                       maxlength="9" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CPF do Cliente *</label>
                                <input type="text" name="cpf" class="form-control" 
                                       placeholder="000.000.000-00" 
                                       value="<?= !empty($venda['cpf']) ? formatCpf($venda['cpf']) : '' ?>" 
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
                                       value="<?= e($venda['numero_contrato']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor do Crédito *</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="text" name="valor_credito" class="form-control" 
                                           placeholder="0,00" 
                                           value="<?= number_format($venda['valor_credito'], 2, ',', '.') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendedor *</label>
                                <select name="vendedor_id" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($funcionarios as $func): ?>
                                        <option value="<?= $func['id'] ?>" 
                                                <?= $venda['vendedor_id'] == $func['id'] ? 'selected' : '' ?>>
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
                                                <?= $venda['virador_id'] == $func['id'] ? 'selected' : '' ?>>
                                            <?= e($func['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Segmento *</label>
                                <select name="segmento" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($opcoesInteresse as $interesse): ?>
                                        <option value="<?= e($interesse) ?>" 
                                                <?= $venda['segmento'] === $interesse ? 'selected' : '' ?>>
                                            <?= e($interesse) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo de Venda *</label>
                                <select name="tipo" class="form-control" required>
                                    <option value="Normal" <?= $venda['tipo'] === 'Normal' ? 'selected' : '' ?>>Normal</option>
                                    <option value="Meia" <?= $venda['tipo'] === 'Meia' ? 'selected' : '' ?>>Meia</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Administradora *</label>
                                <select name="administradora" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($opcoesAdministradoras as $adm): ?>
                                        <option value="<?= e($adm) ?>" 
                                                <?= $venda['administradora'] === $adm ? 'selected' : '' ?>>
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
                    <div class="form-section-title">4. Contrato</div>
                    <div class="form-section-body">
                        <?php if ($venda['arquivo_contrato']): ?>
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle"></i>
                                <strong>Contrato atual:</strong> <?= e($venda['arquivo_contrato']) ?>
                                <a href="<?= base_url('uploads/contratos/' . $venda['arquivo_contrato']) ?>" 
                                   class="btn btn-sm btn-outline-primary ms-2" 
                                   target="_blank">
                                    <i class="bi bi-eye"></i> Visualizar
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">
                                    <?= $venda['arquivo_contrato'] ? 'Substituir Contrato' : 'Adicionar Contrato' ?>
                                </label>
                                <input type="file" name="arquivo_contrato" class="form-control" 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <small class="text-muted">
                                    Formatos aceitos: PDF, DOC, DOCX, JPG, PNG (Máx: 5MB)
                                    <?php if ($venda['arquivo_contrato']): ?>
                                        <br>Deixe em branco para manter o arquivo atual.
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="form-actions">
                    <a href="view.php?id=<?= $vendaId ?>" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-save">Salvar Alterações</button>
                </div>
            </form>
    </div>
</div>

<script>
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

