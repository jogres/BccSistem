<?php include('../../_php/_login/logado.php'); ?>
<?php
require_once __DIR__ . '/../../config/db.php';

// Detecta edição de funcionário
$editing = false;
$funcData = [
    'idFun'    => '',
    'nome'     => '',
    'endereco' => '',
    'numero'   => '',
    'dataN'    => '',
    'cpf'      => '',
    'email'    => '',
    'acesso'   => '',
    'ativo'    => ''
];

if (isset($_GET['idFun']) && is_numeric($_GET['idFun'])) {
    $editing = true;
    $idFun = (int) $_GET['idFun'];
    $stmt  = $pdo->prepare(
        "SELECT idFun, nome, endereco, numero, dataN, cpf, email, acesso, ativo
         FROM cad_fun WHERE idFun = ?"
    );
    $stmt->execute([$idFun]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $funcData = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../../_css/_menu/menu.css" />
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css" />
  <title><?= $editing ? 'Editar Funcionário' : 'Cadastro de Funcionário' ?></title>
</head>
<body>
  <div class="container">
    <!-- Menu omitido -->
    <button class="menu-toggle float" aria-label="Abrir menu">&#9776;</button>
    <nav class="main-nav">
      <button class="menu-toggle inmenu" aria-label="Fechar menu">&#9776;</button>
      <ul class="nav-links">
        <?php foreach ($menu as $link => $nome): ?>
          <li class="nav-item"><a href="<?= $link ?>" class="nav-link"><?= $nome ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-user-actions">
        <span class="user-name"><?= htmlspecialchars($nomeP) ?></span>
        <form action="../../_php/_login/deslogar.php" method="post" class="logout-form">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>
    <div class="form-container">
      <form action="../../_php/_cadastro/cadFun.php" method="post">
        <?php if ($editing): ?>
          <input type="hidden" name="editing" value="1" />
          <input type="hidden" name="idFun"    value="<?= $funcData['idFun'] ?>" />
        <?php endif; ?>
        <fieldset>
          <legend><?= $editing ? 'Editar Funcionário' : 'Cadastro de Funcionário' ?></legend>

          <?php if (!$editing): ?>
            <label for="matricula">Matrícula:</label>
            <input class="matricula"
              type="number"
              name="idFun"
              id="matricula"
              min="1"
              required
            />
          <?php endif; ?>

          <label for="nome">Nome Completo:</label>
          <input class="nome"
            type="text" name="nome" id="nome" maxlength="100" required
            value="<?= htmlspecialchars($funcData['nome'], ENT_QUOTES) ?>"
          />
          <?php if (!$editing): ?>
            <div class="endereco-group">
              <label for="rua">Rua:</label>
              <input class="endereco"
                type="text" name="rua" id="rua" maxlength="100" required
              />

              <label for="numero">Número:</label>
              <input class="endereco"
                type="text" name="numero" id="numero" maxlength="10" required

              />

              <label for="bairro">Bairro:</label>
              <input class="endereco"
                type="text" name="bairro" id="bairro" maxlength="50" required
              />

              <label for="cidade">Cidade:</label>
              <input class="endereco"
                type="text" name="cidade" id="cidade" maxlength="50" required

              />

              <label for="estado">Estado:</label>
              <input class="endereco"
                type="text" name="estado" id="estado" maxlength="2" required
                placeholder="UF"

              />

              <label for="cep">CEP:</label>
              <input class="endereco"
                type="text" name="cep" id="cep" maxlength="9" required
                placeholder="00000-000"
                />
            </div>
          <?php else: ?>
            <label for="endereco">Endereço:</label>
            <textarea name="endereco" id="endereco" required><?= htmlspecialchars($funcData['endereco']) ?></textarea>
          <?php endif; ?>
          

          <label for="telefone">Telefone:</label>
          <input class="telefone"
            type="text" name="telefone" id="telefone" maxlength="20" required
            pattern="\d+[\d\s\-()]*" title="Digite um telefone válido"
            value="<?= htmlspecialchars($funcData['numero'], ENT_QUOTES) ?>"
          />

          <label for="dataN">Data de Nascimento:</label>
          <input class="data"
            type="date" name="dataN" id="dataN" required
            value="<?= htmlspecialchars($funcData['dataN'], ENT_QUOTES) ?>"
          />

          <label for="cpf">CPF:</label>
          <input class="cpf"
            type="text" name="cpf" id="cpf" maxlength="14" required
            pattern="\d{3}\.?\d{3}\.?\d{3}-?\d{2}" title="Digite um CPF válido"
            value="<?= htmlspecialchars($funcData['cpf'], ENT_QUOTES) ?>"
          />

          <label for="email">E-mail:</label>
          <input class="email"
            type="email" name="email" id="email" maxlength="150" required
            value="<?= htmlspecialchars($funcData['email'], ENT_QUOTES) ?>"
          />

          <label for="senha">
            <?= $editing ? 'Nova Senha (deixe em branco para manter)' : 'Senha' ?>:
          </label>
          <input class="senha"
            type="password" name="senha" id="senha" <?= $editing ? '' : 'required' ?>
          />

          <label for="acesso">Perfil:</label>
          <select name="acesso" id="acesso">
            <option value="admin"    <?= $funcData['acesso']==='admin'    ? 'selected' : '' ?>>Administrador</option>
            <option value="user"     <?= $funcData['acesso']==='user'     ? 'selected' : '' ?>>Vendedor</option>
          </select>

          <label for="ativo">Ativo:</label>
          <select name="ativo" id="ativo">
            <option value="Sim" <?= $funcData['ativo']==='Sim' ? 'selected' : '' ?>>Sim</option>
            <option value="Nao" <?= $funcData['ativo']==='Nao' ? 'selected' : '' ?>>Não</option>
          </select>

        </fieldset>

        <div class="form-buttons">
          <button type="submit" class="btn btn-primary">
            <?= $editing ? 'Salvar Alterações' : 'Cadastrar' ?>
          </button>
          <?php if ($editing): ?>
            <a href="../../_html/_lista/listaFun.php" class="btn btn-secondary">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
  <script src="../../_js/_cadastro/cadFun.js"></script>
  <script src="../../_js/_menu/menu.js"></script>
</body>
</html>