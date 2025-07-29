<?php
// /_consorcioBcc/_php/_funcionarios/process.php

require __DIR__ . '/../../config/database.php';
session_start();

// 1) Proteção de rota
if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

// 2) Coleta e sanitização dos campos
$id             = isset($_POST['id_funcionario']) ? (int) $_POST['id_funcionario'] : null;
$nome           = trim($_POST['nome']               ?? '');
$data_nasc      = trim($_POST['data_nascimento']    ?? '');
$cpf            = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$cargo          = $_POST['cargo']                   ?? 'Vendedor';
$email          = trim($_POST['email']              ?? '');
$senha          = $_POST['senha']                   ?? '';
$telefone       = trim($_POST['telefone']           ?? null);
$celular        = trim($_POST['celular']            ?? null);
$logradouro     = trim($_POST['logradouro']         ?? null);
$numero         = trim($_POST['numero']             ?? null);
$complemento    = trim($_POST['complemento']        ?? null);
$bairro         = trim($_POST['bairro']             ?? null);
$cidade         = trim($_POST['cidade']             ?? null);
$estado         = trim($_POST['estado']             ?? null);
$cep            = trim($_POST['cep']                ?? null);
$foto_url       = trim($_POST['foto_url']           ?? null);
$ativo          = isset($_POST['ativo'])            ? 1 : 0;
$papel          = $_POST['papel']                   ?? 'vendedor';

// 3) Validações básicas
$errors = [];
if (!$nome || !$cpf || strlen($cpf) !== 11) {
    $errors[] = 'Nome e CPF (11 dígitos) são obrigatórios.';
}
if ($data_nasc && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_nasc)) {
    $errors[] = 'Data de nascimento deve estar no formato YYYY-MM-DD.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'E-mail inválido.';
}
if (!$id && strlen($senha) < 6) {
    // em criação, senha obrigatória
    $errors[] = 'Senha deve ter ao menos 6 caracteres.';
}
if (!in_array($cargo, ['Vendedor','Virador','Ambos'], true)) {
    $errors[] = 'Cargo inválido.';
}
if (!in_array($papel, ['admin','gerente','vendedor'], true)) {
    $errors[] = 'Papel inválido.';
}

if ($errors) {
    $err = urlencode($errors[0]);
    $redir = $id
      ? "/BccSistem/_html/_funcionarios/form.php?id={$id}&error={$err}"
      : "/BccSistem/_html/_funcionarios/form.php?error={$err}";
    header("Location: {$redir}");
    exit;
}

try {
    $pdo = getPDO();

    // 4) Checa duplicidade de CPF/email, excluindo o próprio em update
    if ($id) {
        $dup = $pdo->prepare("
            SELECT COUNT(*) FROM funcionarios
             WHERE (cpf = :cpf OR email = :email)
               AND id_funcionario <> :id
        ");
        $dup->execute([
            ':cpf'   => $cpf,
            ':email' => $email,
            ':id'    => $id,
        ]);
    } else {
        $dup = $pdo->prepare("
            SELECT COUNT(*) FROM funcionarios
             WHERE cpf = :cpf OR email = :email
        ");
        $dup->execute([':cpf' => $cpf, ':email' => $email]);
    }
    if ($dup->fetchColumn() > 0) {
        throw new Exception('CPF ou e-mail já cadastrado.');
    }

    // 5) Monta INSERT ou UPDATE
    if ($id) {
        // UPDATE existente
        $fields = "
            nome            = :nome,
            data_nascimento = :data_nasc,
            cpf             = :cpf,
            cargo           = :cargo,
            email           = :email,
            telefone        = :telefone,
            celular         = :celular,
            logradouro      = :logradouro,
            numero          = :numero,
            complemento     = :complemento,
            bairro          = :bairro,
            cidade          = :cidade,
            estado          = :estado,
            cep             = :cep,
            foto_url        = :foto_url,
            ativo           = :ativo,
            papel           = :papel
        ";

        if (!empty($senha)) {
            $fields .= ", senha_hash = :senha_hash";
            $hash = password_hash($senha, PASSWORD_BCRYPT);
        }

        $sql = "UPDATE funcionarios SET {$fields} WHERE id_funcionario = :id";
        $stmt = $pdo->prepare($sql);

        $params = [
            ':nome'       => $nome,
            ':data_nasc'  => $data_nasc ?: null,
            ':cpf'        => $cpf,
            ':cargo'      => $cargo,
            ':email'      => $email,
            ':telefone'   => $telefone,
            ':celular'    => $celular,
            ':logradouro' => $logradouro,
            ':numero'     => $numero,
            ':complemento'=> $complemento,
            ':bairro'     => $bairro,
            ':cidade'     => $cidade,
            ':estado'     => $estado,
            ':cep'        => $cep,
            ':foto_url'   => $foto_url,
            ':ativo'      => $ativo,
            ':papel'      => $papel,
            ':id'         => $id,
        ];
        if (!empty($senha)) {
            $params[':senha_hash'] = $hash;
        }

        $stmt->execute($params);

    } else {
        // INSERT novo
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $sql = "INSERT INTO funcionarios
          (nome, data_nascimento, cpf, cargo, email, senha_hash, telefone, celular,
           logradouro, numero, complemento, bairro, cidade, estado, cep,
           foto_url, ativo, papel)
        VALUES
          (:nome, :data_nasc, :cpf, :cargo, :email, :senha_hash, :telefone, :celular,
           :logradouro, :numero, :complemento, :bairro, :cidade, :estado, :cep,
           :foto_url, :ativo, :papel)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'        => $nome,
            ':data_nasc'   => $data_nasc ?: null,
            ':cpf'         => $cpf,
            ':cargo'       => $cargo,
            ':email'       => $email,
            ':senha_hash'  => $hash,
            ':telefone'    => $telefone,
            ':celular'     => $celular,
            ':logradouro'  => $logradouro,
            ':numero'      => $numero,
            ':complemento' => $complemento,
            ':bairro'      => $bairro,
            ':cidade'      => $cidade,
            ':estado'      => $estado,
            ':cep'         => $cep,
            ':foto_url'    => $foto_url,
            ':ativo'       => $ativo,
            ':papel'       => $papel,
        ]);
    }

    // 6) Redireciona para lista
    header('Location: /BccSistem/_php/_funcionarios/list.php');
    exit;

} catch (Exception $e) {
    $err = urlencode($e->getMessage());
    $redir = $id
      ? "/BccSistem/_html/_funcionarios/form.php?id={$id}&error={$err}"
      : "/BccSistem/_html/_funcionarios/form.php?error={$err}";
    header("Location: {$redir}");
    exit;
}
