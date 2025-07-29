<?php
// /_consorcioBcc/_php/_clientes/process.php
require __DIR__ . '/../../config/database.php';
session_start();

// 1) Autenticação
if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

// 2) Coleta e sanitização dos campos
$id_cliente   = isset($_POST['id_cliente'])    ? (int) $_POST['id_cliente'] : null;
$nome         = trim($_POST['nome']            ?? '');
$cpf          = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$telefone     = trim($_POST['telefone']        ?? '');
$celular      = trim($_POST['celular']         ?? '');
$logradouro   = trim($_POST['logradouro']      ?? '');
$numero       = trim($_POST['numero']          ?? '');
$complemento  = trim($_POST['complemento']     ?? '');
$bairro       = trim($_POST['bairro']          ?? '');
$cidade       = trim($_POST['cidade']          ?? '');
$estado       = trim($_POST['estado']          ?? '');
$cep          = trim($_POST['cep']             ?? '');
$motivo       = trim($_POST['motivo']          ?? '');

// 3) Validações
$errors = [];
if ($nome === '') {
    $errors[] = 'O nome é obrigatório.';
}
if (strlen($cpf) !== 11) {
    $errors[] = 'CPF deve conter 11 dígitos.';
}
if ($telefone === '') {
    $errors[] = 'O telefone é obrigatório.';
}
if ($motivo === '') {
    $errors[] = 'O motivo é obrigatório.';
}

if ($errors) {
    $err = urlencode($errors[0]);
    $loc = $id_cliente
         ? "/_consorcioBcc/_html/_clientes/form.php?id={$id_cliente}&error={$err}"
         : "/_consorcioBcc/_html/_clientes/form.php?error={$err}";
    header("Location: {$loc}");
    exit;
}

try {
    $pdo = getPDO();

    if ($id_cliente) {
        // 4a) UPDATE existente
        $sql = "
          UPDATE clientes
             SET nome        = :nome,
                 cpf         = :cpf,
                 telefone    = :telefone,
                 celular     = :celular,
                 logradouro  = :logradouro,
                 numero      = :numero,
                 complemento = :complemento,
                 bairro      = :bairro,
                 cidade      = :cidade,
                 estado      = :estado,
                 cep         = :cep,
                 motivo      = :motivo
           WHERE id_cliente = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'        => $nome,
            ':cpf'         => $cpf,
            ':telefone'    => $telefone,
            ':celular'     => $celular,
            ':logradouro'  => $logradouro,
            ':numero'      => $numero,
            ':complemento' => $complemento,
            ':bairro'      => $bairro,
            ':cidade'      => $cidade,
            ':estado'      => $estado,
            ':cep'         => $cep,
            ':motivo'      => $motivo,
            ':id'          => $id_cliente,
        ]);
    } else {
        // 4b) INSERT novo registro
        $sql = "
          INSERT INTO clientes
            (nome, cpf, telefone, celular,
             logradouro, numero, complemento,
             bairro, cidade, estado, cep, motivo)
          VALUES
            (:nome, :cpf, :telefone, :celular,
             :logradouro, :numero, :complemento,
             :bairro, :cidade, :estado, :cep, :motivo)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'        => $nome,
            ':cpf'         => $cpf,
            ':telefone'    => $telefone,
            ':celular'     => $celular,
            ':logradouro'  => $logradouro,
            ':numero'      => $numero,
            ':complemento' => $complemento,
            ':bairro'      => $bairro,
            ':cidade'      => $cidade,
            ':estado'      => $estado,
            ':cep'         => $cep,
            ':motivo'      => $motivo,
        ]);
    }

    // 5) Redireciona para a listagem
    header('Location: /_consorcioBcc/_php/_clientes/list.php');
    exit;

} catch (Exception $e) {
    // Em caso de erro, retorna ao formulário com a mensagem
    $msg = urlencode($e->getMessage());
    $loc = $id_cliente
         ? "/_consorcioBcc/_html/_clientes/form.php?id={$id_cliente}&error={$msg}"
         : "/_consorcioBcc/_html/_clientes/form.php?error={$msg}";
    header("Location: {$loc}");
    exit;
}
