<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/lib/CSRF.php';
require __DIR__ . '/../../app/middleware/require_login.php';
require __DIR__ . '/../../app/models/Cliente.php';

$user = Auth::user();
$isAdmin = Auth::isAdmin();
$clientes = Cliente::listForUser($user['id'], $isAdmin);

include __DIR__ . '/../../app/views/partials/header.php';
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h1>Clientes</h1>
    <a class="btn" href="<?= e(base_url('clientes/create.php')) ?>">Novo cliente</a>
  </div>
  <div style="overflow:auto;">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>Telefone</th>
          <th>Cidade/UF</th>
          <?php if ($isAdmin): ?><th>Criado por</th><?php endif; ?>
          <th>Criado em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clientes as $c): ?>
          <tr>
            <td><?= (int)$c['id'] ?></td>
            <td><?= e($c['nome']) ?></td>
            <td><?= e($c['telefone']) ?></td>
            <td><?= e($c['cidade']) ?>/<?= e($c['estado']) ?></td>
            <?php if ($isAdmin): ?><td><?= e($c['criado_por_nome']) ?></td><?php endif; ?>
            <td><?= e($c['created_at']) ?></td>
            <td>
              <a class="btn secondary" href="<?= e(base_url('clientes/edit.php?id=' . (int)$c['id'])) ?>">Editar</a>
              <form method="post" action="<?= e(base_url('clientes/delete.php')) ?>" style="display:inline" onsubmit="return confirm('Confirma excluir (soft delete)?')">
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn danger" type="submit">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../../app/views/partials/footer.php'; ?>
