<?php include('../../_php/_login/logado.php'); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../../_css/_menu/menu.css" />
  <link rel="stylesheet" href="../../_css/_cadastro/cad.css" />
  <title>Cadastro de Funcionário</title>
</head>
<body>
  <div class="container">
    <nav class="main-nav" role="navigation">
      <button class="menu-toggle" aria-label="Abrir menu">&#9776;</button>
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
        <fieldset>
          <legend>Cadastro de Funcionário</legend>
          <label for="idFun">Código:</label>
          <input type="number" name="idFun" id="idFun" min="1" 
                 placeholder="Opcional (gerado automaticamente se vazio)" />
          <label for="nome">Nome Completo:</label>
          <input type="text" name="nome" id="nome" maxlength="100" required />
          <label for="endereco">Endereço Completo:</label>
          <input type="text" name="endereco" id="endereco" maxlength="200" required />
          <label for="telefone">Telefone:</label>
          <input type="text" name="numero" id="telefone" maxlength="20" required 
                 pattern="\d+[\d\s\-()]*" title="Digite um telefone válido" />
          <label for="dataN">Data de Nascimento:</label>
          <input type="date" name="dataN" id="dataN" required />
          <label for="cpf">CPF:</label>
          <input type="text" name="cpf" id="cpf" maxlength="14" required 
                 pattern="\d{3}\.?\d{3}\.?\d{3}-?\d{2}" title="Digite um CPF válido" />
          <label for="email">E-mail:</label>
          <input type="email" name="email" id="email" maxlength="150" required />
          <label for="senha">Senha:</label>
          <input type="password" name="senha" id="senha" required />
          <label for="acesso">Perfil:</label>
          <select name="acesso" id="acesso">
            <option value="admin">Administrador</option>
            <option value="user">Funcionário</option>
            <option value="vendedor">Vendedor</option>
          </select>
          <label for="ativo">Ativo:</label>
          <select name="ativo" id="ativo">
            <option value="Sim">Sim</option>
            <option value="Nao">Não</option>
          </select>
        </fieldset>
        <button type="submit">Cadastrar</button>
      </form>
    </div>
  </div>
  <script>
    document.querySelector('.menu-toggle').addEventListener('click', () => {
      document.querySelector('.nav-links').classList.toggle('open');
    });
  </script>
</body>
</html>
