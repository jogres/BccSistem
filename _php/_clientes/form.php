<?php
// /_consorcioBcc/_php/_clientes/form.php
require __DIR__ . '/../../config/database.php';


// 1) Garante que só usuários autenticados acessem
if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

// 2) Variáveis padrão para novo cliente
$isEdit     = false;
$id_cliente = null;
$nome       = '';
$cpf        = '';
$telefone   = '';
$celular    = '';
$logradouro = '';
$numero     = '';
$complemento= '';
$bairro     = '';
$cidade     = '';
$estado     = '';
$cep        = '';
$motivo     = '';

// 3) Se veio ?id= na URL, busca do banco para edição
if (!empty($_GET['id'])) {
    $isEdit     = true;
    $id_cliente = (int) $_GET['id'];

    $pdo  = getPDO();
    $stmt = $pdo->prepare("
      SELECT
        nome,
        cpf,
        telefone,
        celular,
        logradouro,
        numero,
        complemento,
        bairro,
        cidade,
        estado,
        cep,
        motivo
      FROM clientes
     WHERE id_cliente = :id
    ");
    $stmt->execute([':id' => $id_cliente]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // ID inválido, volta à lista
        header('Location: /BccSistem/_php/_clientes/list.php');
        exit;
    }

    // 4) Popula variáveis com dados existentes
    $nome        = $row['nome'];
    $cpf         = $row['cpf'];
    $telefone    = $row['telefone'];
    $celular     = $row['celular'];
    $logradouro  = $row['logradouro'];
    $numero      = $row['numero'];
    $complemento = $row['complemento'];
    $bairro      = $row['bairro'];
    $cidade      = $row['cidade'];
    $estado      = $row['estado'];
    $cep         = $row['cep'];
    $motivo      = $row['motivo'];
}

// As variáveis acima ($isEdit, $id_cliente, $nome, $cpf, $telefone, $celular,
//  $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $cep, $motivo)
// ficam disponíveis para o template HTML que renderiza o form.
