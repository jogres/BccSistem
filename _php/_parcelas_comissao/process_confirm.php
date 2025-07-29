<?php
// File: /_php/_parcelas_comissao/process_confirm.php
session_start();
require __DIR__ . '/../../config/database.php';

// 1) Verifica autenticação
if (empty($_SESSION['user_id'])) {
    header('Location: /_consorcioBcc/_html/_login/index.php');
    exit;
}

// 2) Recupera parâmetros e valida
$id_venda        = isset($_GET['venda'])    ? (int) $_GET['venda'] : 0;
$numero_parcela  = isset($_GET['parcela'])  ? (int) $_GET['parcela'] : 0;

if ($id_venda <= 0 || $numero_parcela <= 0) {
    // Parâmetros inválidos
    header("Location: /_consorcioBcc/_php/_parcelas_comissao/list.php?venda={$id_venda}&error=Parâmetros inválidos");
    exit;
}

try {
    $pdo = getPDO();

    // 3) Atualiza status e data_realizacao_cliente
    $stmt = $pdo->prepare("
        UPDATE parcelas_comissao
           SET status = 'paga',
               data_realizacao_cliente = CURDATE()
         WHERE id_venda = :venda
           AND numero_parcela = :parcela
           AND status = 'pendente'
    ");
    $stmt->execute([
        ':venda'   => $id_venda,
        ':parcela' => $numero_parcela,
    ]);

    // 4) Redireciona de volta para a listagem
    header("Location: /_consorcioBcc/_php/_parcelas_comissao/list.php?venda={$id_venda}");
    exit;

} catch (Exception $e) {
    // Em caso de erro, redireciona com mensagem
    $msg = urlencode($e->getMessage());
    header("Location: /_consorcioBcc/_php/_parcelas_comissao/list.php?venda={$id_venda}&error={$msg}");
    exit;
}
