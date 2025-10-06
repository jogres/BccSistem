<?php
require __DIR__.'/../../app/lib/Database.php';
require __DIR__.'/../../app/lib/Auth.php';
require __DIR__.'/../../app/lib/Helpers.php';
require __DIR__.'/../../app/lib/Request.php';
require __DIR__.'/../../app/lib/CSRF.php';
require __DIR__.'/../../app/middleware/require_login.php';
require __DIR__.'/../../app/models/Cliente.php';

$id = (int)($_GET['id'] ?? 0);
$cliente = Cliente::find($id);
if (!$cliente) { http_response_code(404); exit('Cliente não encontrado'); }

$opcoesInteresse = require __DIR__.'/../../app/config/interesses.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate();

    $nome      = Request::postString('nome');
    $telefone  = Request::postString('telefone');
    $cidade    = Request::postString('cidade');
    $estado    = strtoupper(substr(Request::postString('estado'), 0, 2));
    $interesse = Request::postString('interesse');

    if ($nome === '')      $errors[] = 'Nome é obrigatório.';
    if ($telefone === '')  $errors[] = 'Telefone é obrigatório.';
    if ($cidade === '')    $errors[] = 'Cidade é obrigatória.';
    if (strlen($estado) !== 2) $errors[] = 'Estado deve ter 2 letras.';
    if (!in_array($interesse, $opcoesInteresse, true)) $errors[] = 'Interesse inválido.';

    if (!$errors) {
        Cliente::update($id, [
            'nome'      => $nome,
            'telefone'  => $telefone,
            'cidade'    => $cidade,
            'estado'    => $estado,
            'interesse' => $interesse,
        ]);
        header('Location: '.base_url('clientes/index.php')); exit;
    }
}

include __DIR__.'/../../app/views/partials/header.php';
?>
<div class="main-container">
  <div class="form-container">
    <!-- Cabeçalho do formulário -->
    <div class="form-header">
      <h1 class="form-title text-balance leading-tight">✏️ Editar Cliente</h1>
      <p class="form-subtitle hyphens leading-relaxed line-clamp-2">Editando dados de: <strong><?= e($cliente['nome']) ?></strong></p>
    </div>

    <!-- Erros de validação -->
    <?php if ($errors): ?>
      <div class="form-errors">
        <h4>Erros encontrados:</h4>
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Formulário -->
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>">
      
      <!-- Seção de Dados Pessoais -->
      <div class="form-section">
        <h3 class="form-section-title">📋 Dados Pessoais</h3>
        
        <div class="form-row single">
          <div class="form-group">
            <label class="form-label required" for="nome">👤 Nome Completo</label>
            <input 
              class="form-control" 
              id="nome"
              name="nome" 
              type="text"
              placeholder="Digite o nome completo do cliente"
              value="<?= e($cliente['nome']) ?>"
              required
            >
          </div>
        </div>
        
        <div class="form-row single">
          <div class="form-group">
            <label class="form-label required" for="telefone">📞 Telefone</label>
            <input 
              class="form-control" 
              id="telefone"
              name="telefone" 
              type="tel"
              placeholder="(11) 99999-9999"
              value="<?= e($cliente['telefone']) ?>"
              required
            >
          </div>
        </div>
      </div>

      <!-- Seção de Localização -->
      <div class="form-section">
        <h3 class="form-section-title">📍 Localização</h3>
        
        <div class="form-row double">
          <div class="form-group">
            <label class="form-label required" for="cidade">🏙️ Cidade</label>
            <input 
              class="form-control" 
              id="cidade"
              name="cidade" 
              type="text"
              placeholder="Nome da cidade"
              value="<?= e($cliente['cidade']) ?>"
              required
            >
          </div>
          
          <div class="form-group">
            <label class="form-label required" for="estado">🗺️ Estado (UF)</label>
            <input 
              class="form-control" 
              id="estado"
              name="estado" 
              type="text"
              maxlength="2"
              placeholder="SP"
              value="<?= e($cliente['estado']) ?>"
              style="text-transform: uppercase;"
              required
            >
          </div>
        </div>
      </div>

      <!-- Seção de Interesse -->
      <div class="form-section">
        <h3 class="form-section-title">🎯 Interesse</h3>
        
        <div class="form-row single">
          <div class="form-group">
            <label class="form-label required" for="interesse">💼 Tipo de Interesse</label>
            <select class="form-control" id="interesse" name="interesse" required>
              <option value="">Selecione o tipo de interesse...</option>
              <?php foreach ($opcoesInteresse as $opt): ?>
                <option value="<?= e($opt) ?>" <?= $opt === $cliente['interesse'] ? 'selected' : '' ?>>
                  <?= e($opt) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Botões de ação -->
      <div class="form-actions">
        <button class="btn-save" type="submit">Salvar Alterações</button>
        <a class="btn-cancel" href="<?= e(base_url('clientes/index.php')) ?>">Cancelar</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__.'/../../app/views/partials/footer.php'; ?>
