<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

if ($acesso !== 'admin') { exit('Acesso negado.'); }

// Obtém os dados do formulário
$tabelaNivel = $_POST['select-niveis'] ?? '';  // "master", "classic" ou "basic"
$nomePlano   = trim($_POST['nome'] ?? '');
$p1 = (float) ($_POST['primeira'] ?? 0);
$p2 = (float) ($_POST['segunda'] ?? 0);
$p3 = (float) ($_POST['terceira'] ?? 0);
$p4 = (float) ($_POST['quarta'] ?? 0);
$idAdm = (int) ($_POST['select-adm'] ?? 0);

// Valida inputs
if (!in_array($tabelaNivel, ['master','classic','basic'])) {
    $_SESSION['error'] = 'Nível selecionado inválido.';
    header('Location: ../../_html/_cadastro/cadNivel.php');
    exit;
}
if ($nomePlano === '') {
    $_SESSION['error'] = 'Nome do plano é obrigatório.';
    header('Location: ../../_html/_cadastro/cadNivel.php');
    exit;
}
if ($p1 < 0 || $p2 < 0 || $p3 < 0 || $p4 < 0 || $p1 > 100 || $p2 > 100 || $p3 > 100 || $p4 > 100) {
    $_SESSION['error'] = 'Percentuais devem estar entre 0 e 100.';
    header('Location: ../../_html/_cadastro/cadNivel.php');
    exit;
}
if ($idAdm <= 0) {
    $_SESSION['error'] = 'Selecione uma Administradora válida.';
    header('Location: ../../_html/_cadastro/cadNivel.php');
    exit;
}

try {
    // Insere o novo nível na tabela correspondente (master/classic/basic)
    $sql = "INSERT INTO $tabelaNivel (nome, primeira, segunda, terceira, quarta, idAdm) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nomePlano, $p1, $p2, $p3, $p4, $idAdm]);
    header('Location: ../../_html/_cadastro/sucesso.php');
    exit;
} catch (PDOException $e) {
    // Tratamento de erros (ex: duplicidade de nome de plano ou violação de FK admin)
    $_SESSION['error'] = 'Erro ao cadastrar nível: ' . $e->getMessage();
    header('Location: ../../_html/_cadastro/cadNivel.php');
    exit;
}
?>
