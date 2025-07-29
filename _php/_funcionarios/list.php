<?php
// File: /_consorcioBcc/_php/_funcionarios/list.php


require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../_php/shared/verify_session.php';

// 1. Verifica sessão
if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

// 2. Parâmetros de busca e paginação
$search   = trim($_GET['search']     ?? '');
$page     = max(1, (int)($_GET['page']      ?? 1));
$perPage  = max(1, min(100, (int)($_GET['per_page'] ?? 10)));
$offset   = ($page - 1) * $perPage;

// 3. Conexão ao banco
$pdo = getPDO();

// 4. Conta total de registros filtrados
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
      FROM funcionarios
     WHERE nome LIKE :search
");
$countStmt->execute([':search' => "%{$search}%"]);
$total = (int)$countStmt->fetchColumn();

// 5. Busca página atual (adicionando data_nascimento)
$stmt = $pdo->prepare("
    SELECT
      id_funcionario,
      nome,
      DATE_FORMAT(data_nascimento, '%Y-%m-%d') AS data_nascimento,
      cpf,
      email,
      cargo,
      ativo,
      papel
      FROM funcionarios
     WHERE nome LIKE :search
     ORDER BY nome
     LIMIT :limit
     OFFSET :offset
");
$stmt->bindValue(':search', "%{$search}%", PDO::PARAM_STR);
$stmt->bindValue(':limit',   $perPage,    PDO::PARAM_INT);
$stmt->bindValue(':offset',  $offset,     PDO::PARAM_INT);
$stmt->execute();
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Cálculo de paginação
$totalPages = $perPage ? (int)ceil($total / $perPage) : 1;

// 7. Chama view
include __DIR__ . '/../../_html/_funcionarios/list.php';

?>