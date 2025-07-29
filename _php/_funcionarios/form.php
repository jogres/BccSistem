<?php
// File: /_consorcioBcc/_php/_funcionarios/form.php


require __DIR__ . '/../../config/database.php';

// Verifica sessão
if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

$pdo = getPDO();

// Variáveis padrão para novo cadastro
$isEdit        = false;
$id             = null;
$nome           = '';
$data_nascimento = '';
$cpf            = '';
$cargo          = 'Vendedor';
$email           = '';
$telefone        = '';
$celular         = '';
$logradouro      = '';
$numero          = '';
$complemento     = '';
$bairro          = '';
$cidade          = '';
$estado          = '';
$cep             = '';
$foto_url        = '';
$ativo           = 1;
$papel           = 'vendedor';

// Se veio ?id= na URL, busca do banco para edição
if (!empty($_GET['id'])) {
    $isEdit = true;
    $id     = (int) $_GET['id'];
    $stmt   = $pdo->prepare("SELECT * FROM funcionarios WHERE id_funcionario = :id");
    $stmt->execute([':id' => $id]);
    $f = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$f) {
        // id inválido — volta para a lista
        header('Location: /_consorcioBcc/_php/_funcionarios/list.php');
        exit;
    }
    // Popula variáveis com dados do funcionário
    $nome             = $f['nome'];
    $data_nascimento  = $f['data_nascimento'] ?? '';
    $cpf              = $f['cpf'];
    $cargo            = $f['cargo'];
    $email            = $f['email'];
    $telefone         = $f['telefone'];
    $celular          = $f['celular'];
    $logradouro       = $f['logradouro'];
    $numero           = $f['numero'];
    $complemento      = $f['complemento'];
    $bairro           = $f['bairro'];
    $cidade           = $f['cidade'];
    $estado           = $f['estado'];
    $cep              = $f['cep'];
    $foto_url         = $f['foto_url'];
    $ativo            = $f['ativo'];
    $papel            = $f['papel'];
}
