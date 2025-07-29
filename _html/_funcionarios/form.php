<?php
// /_consorcioBcc/_html/_funcionarios/form.php
require __DIR__ . '/../../_php/shared/verify_session.php';
include __DIR__ . '/../../_php/_menu/menu.php';

// Se for edição, carrega os dados atuais
if (!empty($_GET['id'])) {
    include __DIR__ . '/../../_php/_funcionarios/form.php';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title id="emp-create-title">
    <?= !empty($_GET['id'])
        ? 'Editar Funcionário — consórcioBCC'
        : 'Cadastrar Funcionário — consórcioBCC' ?>
  </title>
  <link id="emp-create-css" rel="stylesheet" href="/BccSistem/_css/_cadastro/style.css">
  <link rel="stylesheet" href="/BccSistem/_css/_menu/style.css">
</head>
<body id="emp-create-body">
  <main id="emp-create-wrapper">
    <header id="emp-create-header">
      <h1 id="emp-create-heading">
        <?= !empty($_GET['id']) ? 'Editar Funcionário' : 'Cadastrar Novo Funcionário' ?>
      </h1>
    </header>

    <?php if (isset($_GET['error'])): ?>
      <div id="emp-create-error" class="alert alert-error">
        <?= htmlspecialchars($_GET['error']) ?>
      </div>
    <?php endif; ?>

    <form id="emp-create-form" class="form"
          action="/_consorcioBcc/_php/_funcionarios/process.php"
          method="post" novalidate>
      
      <?php if (!empty($_GET['id'])): ?>
        <input type="hidden" name="id_funcionario" value="<?= (int) $_GET['id'] ?>">
      <?php endif; ?>

      <!-- Nome -->
      <div class="form-group">
        <label for="nome" class="form-label">Nome completo</label>
        <input type="text" id="nome" name="nome"
               class="form-input"
               value="<?= htmlspecialchars($nome ?? '') ?>"
               required maxlength="100">
      </div>

      <!-- Data de Nascimento -->
      <div class="form-group">
        <label for="data_nascimento" class="form-label">Data de Nascimento</label>
        <input type="date" id="data_nascimento" name="data_nascimento"
               class="form-input"
               value="<?= htmlspecialchars($data_nascimento ?? '') ?>"
               required>
      </div>

      <!-- CPF -->
      <div class="form-group">
        <label for="cpf" class="form-label">CPF</label>
        <input type="text" id="cpf" name="cpf"
               class="form-input"
               value="<?= htmlspecialchars($cpf ?? '') ?>"
               required pattern="\d{11}"
               placeholder="somente números">
      </div>

      <!-- Cargo -->
      <div class="form-group">
        <label for="cargo" class="form-label">Cargo</label>
        <select id="cargo" name="cargo" class="form-input" required>
          <?php foreach (['Vendedor','Virador','Ambos'] as $opt): ?>
            <option value="<?= $opt ?>"
              <?= (isset($cargo) && $cargo === $opt) ? 'selected' : '' ?>>
              <?= $opt ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- E-mail -->
      <div class="form-group">
        <label for="email" class="form-label">E-mail</label>
        <input type="email" id="email" name="email"
               class="form-input"
               value="<?= htmlspecialchars($email ?? '') ?>"
               required maxlength="150">
      </div>

      <!-- Senha -->
      <div class="form-group">
        <label for="senha" class="form-label">
          <?= !empty($_GET['id'])
              ? 'Nova senha (deixe em branco para manter)'
              : 'Senha' ?>
        </label>
        <input type="password" id="senha" name="senha"
               class="form-input"
               <?= empty($_GET['id']) ? 'required minlength="6"' : '' ?>>
      </div>

      <!-- Telefone -->
      <div class="form-group">
        <label for="telefone" class="form-label">Telefone</label>
        <input type="text" id="telefone" name="telefone"
               class="form-input"
               value="<?= htmlspecialchars($telefone ?? '') ?>"
               maxlength="20">
      </div>

      <!-- Celular -->
      <div class="form-group">
        <label for="celular" class="form-label">Celular</label>
        <input type="text" id="celular" name="celular"
               class="form-input"
               value="<?= htmlspecialchars($celular ?? '') ?>"
               maxlength="20">
      </div>

      <!-- Endereço -->
      <div class="form-group">
        <label for="logradouro" class="form-label">Logradouro</label>
        <input type="text" id="logradouro" name="logradouro"
               class="form-input"
               value="<?= htmlspecialchars($logradouro ?? '') ?>"
               maxlength="200">
      </div>
      <div class="form-group">
        <label for="numero" class="form-label">Número</label>
        <input type="text" id="numero" name="numero"
               class="form-input"
               value="<?= htmlspecialchars($numero ?? '') ?>"
               maxlength="20">
      </div>
      <div class="form-group">
        <label for="complemento" class="form-label">Complemento</label>
        <input type="text" id="complemento" name="complemento"
               class="form-input"
               value="<?= htmlspecialchars($complemento ?? '') ?>"
               maxlength="100">
      </div>
      <div class="form-group">
        <label for="bairro" class="form-label">Bairro</label>
        <input type="text" id="bairro" name="bairro"
               class="form-input"
               value="<?= htmlspecialchars($bairro ?? '') ?>"
               maxlength="100">
      </div>
      <div class="form-group">
        <label for="cidade" class="form-label">Cidade</label>
        <input type="text" id="cidade" name="cidade"
               class="form-input"
               value="<?= htmlspecialchars($cidade ?? '') ?>"
               maxlength="100">
      </div>
      <div class="form-group">
        <label for="estado" class="form-label">Estado</label>
        <input type="text" id="estado" name="estado"
               class="form-input"
               value="<?= htmlspecialchars($estado ?? '') ?>"
               maxlength="2" placeholder="SP">
      </div>
      <div class="form-group">
        <label for="cep" class="form-label">CEP</label>
        <input type="text" id="cep" name="cep"
               class="form-input"
               value="<?= htmlspecialchars($cep ?? '') ?>"
               maxlength="10">
      </div>

      <!-- Foto URL -->
      <div class="form-group">
        <label for="foto_url" class="form-label">URL da Foto</label>
        <input type="url" id="foto_url" name="foto_url"
               class="form-input"
               value="<?= htmlspecialchars($foto_url ?? '') ?>"
               maxlength="255">
      </div>

      <!-- Ativo -->
      <div class="form-group">
        <label class="form-label">
          <input type="checkbox" id="ativo" name="ativo" value="1"
                 <?= !empty($ativo) ? 'checked' : '' ?>>
          Funcionário ativo
        </label>
      </div>

      <!-- Papel -->
      <div class="form-group">
        <label for="papel" class="form-label">Papel</label>
        <select id="papel" name="papel" class="form-input" required>
          <?php foreach (['admin','gerente','vendedor'] as $opt): ?>
            <option value="<?= $opt ?>"
              <?= (isset($papel) && $papel === $opt) ? 'selected' : '' ?>>
              <?= ucfirst($opt) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group form-group-submit">
        <button type="submit" id="emp-create-button" class="btn btn-primary">
          <?= !empty($_GET['id']) ? 'Atualizar Funcionário' : 'Salvar Funcionário' ?>
        </button>
      </div>
    </form>
  </main>
  <script src="/BccSistem/_js/_cadastro/form.js"></script>
</body>
</html>
