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
            redirect(base_url('funcionarios/index.php'));
        } catch (PDOException $e) {
            $message = 'Erro ao criar: ' . $e->getMessage();
        }
    } else {
        $message = 'Preencha todos os campos.';
    }
}

include __DIR__ . '/../../app/views/partials/header.php';
?>
<div class="card" >
  <h1>Novo funcionário</h1>
  <?php if ($message): ?><div class="notice" style="background:#ffebee;border-color:#ffcdd2;color:#b71c1c"><?= e($message) ?></div><?php endif; ?>
  <form method="post">
    <?= CSRF::field() ?>
    <div class="form-row">
      <div class="col">
        <label>Nome</label>
        <input class="form-control" name="nome" required>
      </div>
      <div class="col">
        <label>Login</label>
        <input class="form-control" name="login" required>
      </div>
    </div>
    <div class="form-row">
      <div class="col">
        <label>Senha</label>
        <input class="form-control" type="password" name="senha" required>
      </div>
      <div class="col">
        <label>Perfil</label>
        <select class="form-control" name="role_id" required>
          <option value="">Selecione</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int)$r['id'] ?>"><?= e($r['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col" style="max-width:160px">
        <label>Ativo</label>
        <select class="form-control" name="is_ativo">
          <option value="1">Sim</option>
          <option value="0">Não</option>
        </select>
      </div>
    </div>
    <div style="margin-top:12px">
      <button class="btn" type="submit">Salvar</button>
      <a class="btn secondary" href="<?= e(base_url('funcionarios/index.php')) ?>">Cancelar</a>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>
