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

$id = (int)($_GET['id'] ?? 0);
$func = Funcionario::find($id);
if (!$func) {
    http_response_code(404);
    die('Funcionário não encontrado.');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate();
    $nome  = trim($_POST['nome']  ?? '');
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? null; // opcional
    $role_id = (int)($_POST['role_id'] ?? 0);
    $is_ativo = (int)($_POST['is_ativo'] ?? 1);

    if ($nome && $login && $role_id) {
        try {
            Funcionario::update($id, $nome, $login, $senha, $role_id, $is_ativo);
            header('Location: ' . base_url('funcionarios/index.php'));
            exit;
        } catch (PDOException $e) {
            // 23000 = violação de integridade (UNIQUE/PK). 1062 = Duplicate entry. :contentReference[oaicite:4]{index=4}
            $sqlstate = $e->getCode();
            $driverCode = $e->errorInfo[1] ?? null;
            if ($sqlstate === '23000' && $driverCode == 1062) {
                $message = 'Já existe um funcionário com este login.';
            } else {
                $message = 'Erro ao atualizar: ' . $e->getMessage();
            }
        }
    } else {
        $message = 'Preencha nome, login e perfil.';
    }
}

include __DIR__ . '/../../app/views/partials/header.php';
?>
<div class="card" >
  <h1>Editar <?= $func['nome'] ?></h1>
  <?php if ($message): ?><div class="notice error"><?= e($message) ?></div><?php endif; ?>
  <form method="post">
    <?= CSRF::field() ?>
    <div class="form-row">
      <div class="col">
        <label>Nome</label>
        <input class="form-control" name="nome" value="<?= e($func['nome']) ?>" required>
      </div>
      <div class="col">
        <label>Login</label>
        <input class="form-control" name="login" value="<?= e($func['login']) ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="col">
        <label>Senha (deixe em branco para não alterar)</label>
        <input class="form-control" type="password" name="senha" autocomplete="new-password">
      </div>
      <div class="col w-4">
        <label>Perfil (cargo)</label>
        <select class="form-control" name="role_id" required>
          <option value="">Selecione</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= ((int)$func['role_id']===(int)$r['id']?'selected':'') ?>>
              <?= e($r['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col w-4">
        <label>Ativo</label>
        <select class="form-control" name="is_ativo">
          <option value="1" <?= ((int)$func['is_ativo']===1?'selected':'') ?>>Sim</option>
          <option value="0" <?= ((int)$func['is_ativo']===0?'selected':'') ?>>Não</option>
        </select>
      </div>
    </div>

    <div class="mt-3">
      <button class="btn secondary" type="submit">Salvar</button>
      <a class="btn danger" href="<?= e(base_url('funcionarios/index.php')) ?>">Cancelar</a>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>
