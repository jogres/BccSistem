<?php
require __DIR__.'/../../app/lib/Database.php';
require __DIR__.'/../../app/lib/Auth.php';
require __DIR__.'/../../app/lib/Helpers.php';
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
        header('Location: '.base_url('clientes/index.php'), true, 303); // 303 opcional e recomendado
        exit;
    }
}

include __DIR__.'/../../app/views/partials/header.php';
?>
<div class="card" >
  <?php if ($errors): ?>
    <div class="notice" style="background:#ffebee;border-color:#ffcdd2;color:#b71c1c">
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

<h1>Novo cliente</h1>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>">
    <div class="form-row">
      <div class="col">
        <label>Nome <input class="form-control" name="nome" required></label>
      </div>
    </div>
    <div class="form-row">
      <div class="col">
        <label>Telefone <input class="form-control" name="telefone" required></label>
      </div>
    </div>
    <div class="form-row">
      <div class="col">
        <label>Cidade <input class="form-control" name="cidade" required></label>
      </div>
    </div>
    <div class="form-row">
      <div class="col">
        <label>Estado <input class="form-control" name="estado" maxlength="2" required></label>
      </div>
    </div>
    <div class="form-row">
      <div class="col">
        <label>Interesse</label>
          <select class="form-control" name="interesse" required>
              <option value="">Selecione...</option>
        <?php foreach ($opcoesInteresse as $opt): ?>
          <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <button class="btn" type="submit">Salvar</button>
</form>
</div>
<?php include __DIR__.'/../../app/views/partials/footer.php'; ?>
