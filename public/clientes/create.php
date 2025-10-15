<?php
require __DIR__.'/../../app/lib/Database.php';
require __DIR__.'/../../app/lib/Auth.php';
require __DIR__.'/../../app/lib/Helpers.php';
require __DIR__.'/../../app/lib/Logger.php';
require __DIR__.'/../../app/lib/Request.php';
require __DIR__.'/../../app/lib/CSRF.php';
require __DIR__.'/../../app/middleware/require_login.php';
require __DIR__.'/../../app/models/Cliente.php';

$opcoesInteresse = require __DIR__.'/../../app/config/interesses.php';
$user = Auth::user();
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
        $id = Cliente::create([
            'nome'       => $nome,
            'telefone'   => $telefone,
            'cidade'     => $cidade,
            'estado'     => $estado,
            'interesse'  => $interesse,
            'criado_por' => $user['id'],
        ]);
        
        // Log da criação
        Logger::crud('CREATE', 'clientes', $id, $user['id'], [
            'nome' => $nome,
            'telefone' => $telefone,
            'cidade' => $cidade,
            'interesse' => $interesse
        ]);
        
        // Notificar administradores sobre novo cliente
        require_once __DIR__.'/../../app/lib/Notification.php';
        Notification::notifyNewClient($id, $nome, $user['id']);
        
        header('Location: '.base_url('clientes/index.php'), true, 303); // 303 opcional e recomendado
        exit;
    }
}

include __DIR__.'/../../app/views/partials/header.php';
?>
<div class="main-container">
  <div class="form-container">
    <!-- Cabeçalho do formulário -->
    <div class="form-header">
      <h1 class="form-title text-balance leading-tight">👤 Novo Cliente</h1>
      <p class="form-subtitle hyphens leading-relaxed line-clamp-2">Cadastre um novo cliente no sistema</p>
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
              value="<?= e($_POST['nome'] ?? '') ?>"
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
              value="<?= e($_POST['telefone'] ?? '') ?>"
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
              value="<?= e($_POST['cidade'] ?? '') ?>"
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
              value="<?= e($_POST['estado'] ?? '') ?>"
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
                <option value="<?= e($opt) ?>" <?= (($_POST['interesse'] ?? '') === $opt) ? 'selected' : '' ?>>
                  <?= e($opt) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Botões de ação -->
      <div class="form-actions">
        <button class="btn-save" type="submit">Salvar Cliente</button>
        <a class="btn-cancel" href="<?= e(base_url('clientes/index.php')) ?>">Cancelar</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__.'/../../app/views/partials/footer.php'; ?>
