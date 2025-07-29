<?php
// /_consorcioBcc/_php/_vendas/form_data.php
require __DIR__ . '/../../config/database.php';


if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

$pdo = getPDO();

// 1) Carrega listas para selects
$clientes = $pdo->query("
    SELECT 
      id_cliente,
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
    ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);

$funcionarios = $pdo->query("
    SELECT id_funcionario, nome 
      FROM funcionarios 
     WHERE ativo = 1 
     ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);

$administradoras = $pdo->query("
    SELECT id_administradora, nome 
      FROM administradoras 
     ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);

$planos = $pdo->query("
    SELECT 
      id_plano_comissao, 
      nome_plano, 
      num_parcelas_comiss 
    FROM planos_comissao 
    ORDER BY nome_plano
")->fetchAll(PDO::FETCH_ASSOC);

// 2) Defaults
$isEdit   = false;
$id_venda = null;
$venda    = [
    'numero_contrato'   => '',
    'id_cliente'        => '',
    'id_vendedor'       => '',
    'id_virador'        => '',
    'id_administradora' => '',
    'id_plano_comissao' => '',
    'modalidade'        => '',
    'valor_total'       => '',
    'data_venda'        => date('Y-m-d'),
    'status'            => 'PENDENTE',
    // sub-array para dados do cliente
    'cliente_data'      => [
      'nome'        => '',
      'cpf'         => '',
      'telefone'    => '',
      'celular'     => '',
      'logradouro'  => '',
      'numero'      => '',
      'complemento' => '',
      'bairro'      => '',
      'cidade'      => '',
      'estado'      => '',
      'cep'         => '',
      'motivo'      => '',
    ],
];

// 3) Se for edição, busca venda e dados do cliente
if (!empty($_GET['id'])) {
    $isEdit   = true;
    $id_venda = (int) $_GET['id'];

    // busca a venda
    $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id_venda = :id");
    $stmt->execute([':id' => $id_venda]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        header('Location: /BccSistem/_php/_vendas/list.php');
        exit;
    }

    // popula dados da venda
    $venda['numero_contrato']   = $row['numero_contrato'];
    $venda['id_cliente']        = $row['id_cliente'];
    $venda['id_vendedor']       = $row['id_vendedor'];
    $venda['id_virador']        = $row['id_virador'];
    $venda['id_administradora'] = $row['id_administradora'];
    $venda['id_plano_comissao'] = $row['id_plano_comissao'];
    $venda['modalidade']        = $row['modalidade'];
    $venda['valor_total']       = $row['valor_total'];
    $venda['data_venda']        = $row['data_venda'];
    $venda['status']            = $row['status'];

    // busca dados completos do cliente
    $cstmt = $pdo->prepare("
      SELECT
        nome, cpf, telefone, celular,
        logradouro, numero, complemento,
        bairro, cidade, estado, cep,
        motivo
      FROM clientes
      WHERE id_cliente = :cid
    ");
    $cstmt->execute([':cid' => $venda['id_cliente']]);
    if ($cdata = $cstmt->fetch(PDO::FETCH_ASSOC)) {
        $venda['cliente_data'] = $cdata;
    }
}

return compact(
    'clientes',
    'funcionarios',
    'administradoras',
    'planos',
    'isEdit',
    'id_venda',
    'venda'
);
