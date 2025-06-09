<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

if ($acesso !== 'admin') {
    exit('Acesso negado.');
}

// 1) Captura dados do formulário
$tabelaNivel    = $_POST['select-niveis']    ?? '';
$nomePlano      = trim($_POST['nome']        ?? '');
$p1              = (float) ($_POST['primeira']  ?? 0);
$p2              = (float) ($_POST['segunda']   ?? 0);
$p3              = (float) ($_POST['terceira']  ?? 0);
$p4              = (float) ($_POST['quarta']    ?? 0);
$idAdm           = (int)   ($_POST['select-adm'] ?? 0);

// 2) Detecção de edição
$nivelOriginal = $_POST['nivelOriginal'] ?? null;
$idPlano       = isset($_POST['idPlano']) ? (int)$_POST['idPlano'] : null;

// 3) Validações
if (!in_array($tabelaNivel, ['master','classic','basic'], true)) {
    $_SESSION['error'] = 'Nível selecionado inválido.';
    header('Location: ../../_html/_cadastro/cadNivel.php');
    exit;
}
if ($nomePlano === '') {
    $_SESSION['error'] = 'Nome do plano é obrigatório.';
    header('Location: ../../_html/_cadastro/cadNivel.php?'
           . ($nivelOriginal ? "nivel={$nivelOriginal}&idPlano={$idPlano}" : ''));
    exit;
}
foreach (['p1'=>$p1,'p2'=>$p2,'p3'=>$p3,'p4'=>$p4] as $k=>$v) {
    if ($v < 0 || $v > 100) {
        $_SESSION['error'] = 'Percentuais devem estar entre 0 e 100.';
        header('Location: ../../_html/_cadastro/cadNivel.php?'
               . ($nivelOriginal ? "nivel={$nivelOriginal}&idPlano={$idPlano}" : ''));
        exit;
    }
}
if ($idAdm <= 0) {
    $_SESSION['error'] = 'Selecione uma Administradora válida.';
    header('Location: ../../_html/_cadastro/cadNivel.php?'
           . ($nivelOriginal ? "nivel={$nivelOriginal}&idPlano={$idPlano}" : ''));
    exit;
}

try {
    if ($nivelOriginal && $idPlano) {
        // === UPDATE ===
        // determina PK dinamicamente
        $pk = $nivelOriginal==='basic' ? 'idBasic'
             : ($nivelOriginal==='classic' ? 'idClassic' : 'idMaster');

        $sql = "
            UPDATE {$nivelOriginal}
               SET nome     = ?,
                   primeira = ?,
                   segunda  = ?,
                   terceira = ?,
                   quarta   = ?,
                   idAdm    = ?
             WHERE {$pk} = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nomePlano, $p1, $p2, $p3, $p4, $idAdm,
            $idPlano
        ]);

        $_SESSION['success'] = 'Nível atualizado com sucesso.';
    } else {
        // === INSERT ===
        $sql = "
            INSERT INTO {$tabelaNivel}
               (nome, primeira, segunda, terceira, quarta, idAdm)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nomePlano, $p1, $p2, $p3, $p4, $idAdm
        ]);

        $_SESSION['success'] = 'Nível cadastrado com sucesso.';
    }

    // Redireciona de volta à listagem (ou à página de cadastro limpa)
    header('Location: ../../_html/_lista/listaNivel.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = 'Erro ao salvar nível: ' . $e->getMessage();
    // mantém query params em caso de edição
    echo $e->getMessage();
    
    $qs = $nivelOriginal
        ? "?nivel={$nivelOriginal}&idPlano={$idPlano}"
        : '';
    header("Location: ../../_html/_cadastro/cadNivel.php{$qs}");
    exit;
    
}

?>
