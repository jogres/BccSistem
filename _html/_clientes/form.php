<?php
// /_consorcioBcc/_html/_clientes/form.php
require __DIR__ . '/../../_php/shared/verify_session.php';
include __DIR__ . '/../../_php/_menu/menu.php';
include __DIR__ . '/../../_php/_clientes/form.php'; // popula $isEdit, $id_cliente, $nome, $cpf, etc.

if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>
    <?= $isEdit 
        ? "Editar Cliente — " . htmlspecialchars($nome) 
        : "Cadastrar Cliente" ?>
  </title>
  <link rel="stylesheet" href="/BccSistem/_css/_menu/style.css">
  <link rel="stylesheet" href="/BccSistem/_css/_cadastro/style.css">
</head>
<body id="emp-create-body">
  <main id="emp-create-wrapper">
    <header id="emp-create-header">
      <h1 id="emp-create-heading">
        <?= $isEdit ? 'Editar Cliente' : 'Novo Cliente' ?>
      </h1>
    </header>

    <?php if (!empty($_GET['error'])): ?>
      <div class="alert-error">
        <?= htmlspecialchars($_GET['error']) ?>
      </div>
    <?php endif; ?>

    <form id="emp-create-form" class="form"
          action="/BccSistem/_php/_clientes/process.php"
          method="post" novalidate>
      <?php if ($isEdit): ?>
        <input type="hidden" name="id_cliente" value="<?= $id_cliente ?>">
      <?php endif; ?>

      <!-- Nome -->
      <div class="form-group">
        <label for="nome" class="form-label">Nome</label>
        <input type="text" id="nome" name="nome"
               class="form-input"
               value="<?= htmlspecialchars($nome) ?>"
               required maxlength="100">
      </div>

      <!-- CPF (opcional) -->
      <div class="form-group">
        <label for="cpf" class="form-label">CPF</label>
        <input type="text" id="cpf" name="cpf"
               class="form-input"
               value="<?= htmlspecialchars($cpf ?? '') ?>"
               pattern="\d{11}"
               placeholder="somente números" maxlength="11">
      </div>

      <!-- Telefone -->
      <div class="form-group">
        <label for="telefone" class="form-label">Telefone</label>
        <input type="text" id="telefone" name="telefone"
               class="form-input"
               value="<?= htmlspecialchars($telefone) ?>"
               maxlength="20">
      </div>

      <!-- Celular -->
      <div class="form-group">
        <label for="celular" class="form-label">Celular</label>
        <input type="text" id="celular" name="celular"
               class="form-input"
               value="<?= htmlspecialchars($celular) ?>"
               maxlength="20">
      </div>

      <!-- Logradouro -->
      <div class="form-group">
        <label for="logradouro" class="form-label">Logradouro</label>
        <input type="text" id="logradouro" name="logradouro"
               class="form-input"
               value="<?= htmlspecialchars($logradouro) ?>"
               maxlength="200">
      </div>

      <!-- Número -->
      <div class="form-group">
        <label for="numero" class="form-label">Número</label>
        <input type="text" id="numero" name="numero"
               class="form-input"
               value="<?= htmlspecialchars($numero) ?>"
               maxlength="20">
      </div>

      <!-- Complemento -->
      <div class="form-group">
        <label for="complemento" class="form-label">Complemento</label>
        <input type="text" id="complemento" name="complemento"
               class="form-input"
               value="<?= htmlspecialchars($complemento) ?>"
               maxlength="100">
      </div>

      <!-- Bairro -->
      <div class="form-group">
        <label for="bairro" class="form-label">Bairro</label>
        <input type="text" id="bairro" name="bairro"
               class="form-input"
               value="<?= htmlspecialchars($bairro) ?>"
               maxlength="100">
      </div>

      <!-- Cidade -->
      <div class="form-group">
        <label for="cidade" class="form-label">Cidade</label>
        <input type="text" id="cidade" name="cidade"
               class="form-input"
               value="<?= htmlspecialchars($cidade) ?>"
               maxlength="100">
      </div>

      <!-- Estado -->
      <div class="form-group">
        <label for="estado" class="form-label">Estado</label>
        <input type="text" id="estado" name="estado"
               class="form-input"
               value="<?= htmlspecialchars($estado) ?>"
               maxlength="2" placeholder="SP">
      </div>

      <!-- CEP -->
      <div class="form-group">
        <label for="cep" class="form-label">CEP</label>
        <input type="text" id="cep" name="cep"
               class="form-input"
               value="<?= htmlspecialchars($cep) ?>"
               maxlength="10">
      </div>

      <!-- Motivo / Observações -->
      <div class="form-group">
        <label for="motivo" class="form-label">Motivo / Observações</label>
        <textarea id="motivo" name="motivo"
                  class="form-input"
                  rows="4"
                  required
        ><?= htmlspecialchars($motivo) ?></textarea>
      </div>

      <div class="form-group form-group-submit">
        <button type="submit" class="btn btn-primary">
          <?= $isEdit ? 'Atualizar Cliente' : 'Salvar Cliente' ?>
        </button>
      </div>
    </form>
  </main>
</body>
</html>
