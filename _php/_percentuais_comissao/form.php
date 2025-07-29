<?php
// File: /_consorcioBcc/_php/_percentuais_comissao/form.php
require __DIR__ . '/../../config/database.php';

// 1) Inicia sessão (se ainda não estiver ativa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2) Só permite acesso de usuário logado
if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

// 3) Variáveis iniciais
$isEdit            = false;
$id_percentual     = null;
$id_plano_comissao = (int) ($_GET['plano'] ?? 0);
$numParcelas       = 0;
$percentuais       = [];  // [numero_parcela => percentual]
$currentNivel      = null; // id_nivel_comissao para este plano

$pdo = getPDO();

// 4) Carrega todos os planos de comissão
$planos = $pdo->query("
    SELECT id_plano_comissao, nome_plano, num_parcelas_comiss
      FROM planos_comissao
  ORDER BY nome_plano
")->fetchAll(PDO::FETCH_ASSOC);

// 5) Carrega todos os níveis de comissão
$niveis = $pdo->query("
    SELECT id, nivel
      FROM niveis_comissao
  ORDER BY vendas_min
")->fetchAll(PDO::FETCH_ASSOC);

// 6) Se veio ?id=, estamos editando um conjunto de percentuais já existente
if (!empty($_GET['id'])) {
    $isEdit        = true;
    $id_percentual = (int) $_GET['id'];

    // 6a) Carrega todos os percentuais desse plano, incluindo o id_nivel
    $stmt = $pdo->prepare("
      SELECT numero_parcela, percentual, id_nivel
        FROM percentuais_comissao
       WHERE id_plano_comissao = (
         SELECT id_plano_comissao
           FROM percentuais_comissao
          WHERE id_percentual = :id
       )
    ");
    $stmt->execute([':id' => $id_percentual]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $percentuais[(int)$row['numero_parcela']] = $row['percentual'];
        $currentNivel = (int)$row['id_nivel'];
    }

    // 6b) Descobre qual é o plano e quantas parcelas exibir
    $stmt2 = $pdo->prepare("
      SELECT p.id_plano_comissao, p.num_parcelas_comiss
        FROM planos_comissao p
        JOIN percentuais_comissao pc
          ON pc.id_plano_comissao = p.id_plano_comissao
       WHERE pc.id_percentual = :id
       LIMIT 1
    ");
    $stmt2->execute([':id' => $id_percentual]);
    if ($info = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $id_plano_comissao = (int)$info['id_plano_comissao'];
        $numParcelas       = (int)$info['num_parcelas_comiss'];
    }
}

// 7) Se não for edição mas já escolheu um plano via GET, carrega número de parcelas
if (!$isEdit && $id_plano_comissao) {
    $stmt = $pdo->prepare("
      SELECT num_parcelas_comiss
        FROM planos_comissao
       WHERE id_plano_comissao = :pl
    ");
    $stmt->execute([':pl' => $id_plano_comissao]);
    $numParcelas = (int)$stmt->fetchColumn();
}

// Agora o template pode usar:
//  - $planos            : lista de planos de comissão
//  - $niveis            : lista de níveis
//  - $id_plano_comissao : plano selecionado
//  - $numParcelas       : quantas parcelas mostrar
//  - $percentuais       : percentuais já definidos [1=>x,2=>y,...]
//  - $currentNivel      : nível já salvo para este conjunto
//  - $isEdit            : flag de edição
//  - $id_percentual     : id se for edição em lote
