<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/lib/CSRF.php';
require __DIR__ . '/../../app/middleware/require_login.php';
require __DIR__ . '/../../app/middleware/require_admin.php';
require __DIR__ . '/../../app/models/Funcionario.php';

$pdo = Database::getConnection();
$roles = $pdo->query("SELECT id, nome FROM roles ORDER BY id")->fetchAll();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate();
    $nome = trim($_POST['nome'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $role_id = (int)($_POST['role_id'] ?? 0);
    $is_ativo = (int)($_POST['is_ativo'] ?? 1);

    if ($nome && $login && $senha && $role_id) {
        try {
            Funcionario::create($nome, $login, $senha, $role_id, $is_ativo);
            header('Location: ' . base_url('funcionarios/index.php'));
            exit;
        } catch (PDOException $e) {
            $message = 'Erro ao criar: ' . $e->getMessage();
        }
    } else {
        $message = 'Preencha todos os campos.';
    }
}

include __DIR__ . '/../../app/views/partials/header.php';
?>
<div class="main-container">
  <div class="form-container">
    <!-- Cabeçalho do formulário -->
    <div class="form-header">
      <h1 class="form-title text-balance leading-tight">👥 Novo Funcionário</h1>
      <p class="form-subtitle hyphens leading-relaxed line-clamp-2">Cadastre um novo funcionário no sistema</p>
    </div>

    <!-- Erros de validação -->
    <?php if ($message): ?>
      <div class="form-errors">
        <h4>Erro encontrado:</h4>
        <ul>
          <li><?= e($message) ?></li>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Formulário -->
    <form method="post">
      <?= CSRF::field() ?>
      
      <!-- Seção de Dados Pessoais -->
      <div class="form-section">
        <h3 class="form-section-title">👤 Dados Pessoais</h3>
        
        <div class="form-row double">
          <div class="form-group">
            <label class="form-label required" for="nome">📝 Nome Completo</label>
            <input 
              class="form-control" 
              id="nome"
              name="nome" 
              type="text"
              placeholder="Digite o nome completo do funcionário"
              value="<?= e($_POST['nome'] ?? '') ?>"
              required
            >
          </div>
          
          <div class="form-group">
            <label class="form-label required" for="login">🔑 Login de Acesso</label>
            <input 
              class="form-control" 
              id="login"
              name="login" 
              type="text"
              placeholder="usuario123"
              value="<?= e($_POST['login'] ?? '') ?>"
              required
            >
          </div>
        </div>
      </div>

      <!-- Seção de Segurança -->
      <div class="form-section">
        <h3 class="form-section-title">🔒 Segurança e Acesso</h3>
        
        <div class="form-row single">
          <div class="form-group">
            <label class="form-label required" for="senha">🔐 Senha</label>
            <input 
              class="form-control" 
              id="senha"
              name="senha" 
              type="password"
              placeholder="Digite uma senha segura"
              required
            >
          </div>
        </div>
      </div>

      <!-- Seção de Permissões -->
      <div class="form-section">
        <h3 class="form-section-title">⚙️ Permissões e Status</h3>
        
        <div class="form-row double">
          <div class="form-group">
            <label class="form-label required" for="role_id">👔 Perfil de Acesso</label>
            <select class="form-control" id="role_id" name="role_id" required>
              <option value="">Selecione o perfil...</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= (($_POST['role_id'] ?? '') == $r['id']) ? 'selected' : '' ?>>
                  <?= e($r['nome']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label required" for="is_ativo">✅ Status da Conta</label>
            <select class="form-control" id="is_ativo" name="is_ativo">
              <option value="1" <?= (($_POST['is_ativo'] ?? '1') == '1') ? 'selected' : '' ?>>🟢 Ativo</option>
              <option value="0" <?= (($_POST['is_ativo'] ?? '') == '0') ? 'selected' : '' ?>>🔴 Inativo</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Botões de ação -->
      <div class="form-actions">
        <button class="btn-save" type="submit">Salvar Funcionário</button>
        <a class="btn-cancel" href="<?= e(base_url('funcionarios/index.php')) ?>">Cancelar</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>
