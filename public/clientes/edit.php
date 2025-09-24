<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/lib/CSRF.php';
require __DIR__ . '/../../app/middleware/require_login.php';
require __DIR__ . '/../../app/models/Cliente.php';
$opcoesInteresse = require __DIR__.'/../../app/config/interesses.php';

$id = (int)($_GET['id'] ?? 0);
$cliente = Cliente::find($id);
if (!$cliente || $cliente['deleted_at'] !== null) {
    http_response_code(404);
    die('Cliente não encontrado.');
}
$user = Auth::user();
$isAdmin = Auth::isAdmin();
if (!$isAdmin && (int)$cliente['criado_por'] !== (int)$user['id']) {
    http_response_code(403);
    die('Sem permissão para editar este cliente.');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate();
    $nome      = trim($_POST['nome']      ?? '');
    $telefone  = trim($_POST['telefone']  ?? '');
    $cidade    = trim($_POST['cidade']    ?? '');
    $estado    = trim($_POST['estado']    ?? '');
    $interesse = trim($_POST['interesse'] ?? '');
    if ($nome && $telefone && $cidade && $estado && $interesse) {
        Cliente::update($id, $nome, $telefone, $cidade, $estado, $interesse);
        redirect(base_url('clientes/index.php'));
    } else {
        $message = 'Preencha todos os campos.';
    }
}

include __DIR__ . '/../../app/views/partials/header.php';
?>
<div class="card" >
  <h1>Editar  <?= $cliente['nome'] ?></h1>
  <?php if ($message): ?><div class="notice" style="background:#fff3e0;border-color:#ffe0b2;color:#e65100"><?= e($message) ?></div><?php endif; ?>
  <form method="post">
    <?= CSRF::field() ?>
    <div class="form-row">
      <div class="col">
        <label>Nome</label>
        <input class="form-control" name="nome" value="<?= e($cliente['nome']) ?>" required>
      </div>
    </div>
    <div class="form-row">
      <div class="col">
        <label>Telefone</label>
        <input class="form-control" name="telefone" value="<?= e($cliente['telefone']) ?>" required>
      </div>
      <div class="col">
        <label>Interesse</label>
        <select class="form-control" name="interesse" required>
          <option value="" disabled>Selecione</option>
          <?php foreach($opcoesInteresse as $opcao): ?>
            <option value="<?= e($opcao) ?>" <?= ($cliente['interesse'] === $opcao) ? 'selected' : '' ?>><?= e($opcao) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <label>Cidade</label>
        <input class="form-control" name="cidade" value="<?= e($cliente['cidade']) ?>" required>
      </div>
      <div class="col" style="max-width:120px">
        <label>UF</label>
        <input class="form-control" name="estado" maxlength="2" value="<?= e($cliente['estado']) ?>" required>
      </div>
    </div>
    <div style="margin-top:12px">
      <button class="btn" type="submit">Salvar</button>
      <a class="btn secondary" href="<?= e(base_url('clientes/index.php')) ?>">Cancelar</a>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>
