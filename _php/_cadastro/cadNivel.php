<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

if ($acesso !== 'admin') {
    exit('Acesso negado.');
}

// 1) Captura dados do formulário
$tabelaNivel    = $_POST['select-niveis']    ?? '';
$nomePlano      = trim($_POST['nome']        ?? '');
$p1             = (float) ($_POST['primeira']  ?? 0);
$p2             = (float) ($_POST['segunda']   ?? 0);
$p3             = (float) ($_POST['terceira']  ?? 0);
$p4             = (float) ($_POST['quarta']    ?? 0);
$segmento       = trim($_POST['segmento']     ?? '');
$idAdm          = (int)   ($_POST['select-adm'] ?? 0);

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
    $qs = $nivelOriginal ? "?nivel={$nivelOriginal}&idPlano={$idPlano}" : '';
    header("Location: ../../_html/_cadastro/cadNivel.php{$qs}");
    exit;
}
foreach (['1ª'=>$p1,'2ª'=>$p2,'3ª'=>$p3,'4ª'=>$p4] as $label=>$v) {
    if ($v < 0 || $v > 100) {
        $_SESSION['error'] = "Percentual {$label} deve estar entre 0 e 100.";
        $qs = $nivelOriginal ? "?nivel={$nivelOriginal}&idPlano={$idPlano}" : '';
        header("Location: ../../_html/_cadastro/cadNivel.php{$qs}");
        exit;
    }
}
if ($segmento === '') {
    $_SESSION['error'] = 'Segmento é obrigatório.';
    $qs = $nivelOriginal ? "?nivel={$nivelOriginal}&idPlano={$idPlano}" : '';
    header("Location: ../../_html/_cadastro/cadNivel.php{$qs}");
    exit;
}
if ($idAdm <= 0) {
    $_SESSION['error'] = 'Selecione uma Administradora válida.';
    $qs = $nivelOriginal ? "?nivel={$nivelOriginal}&idPlano={$idPlano}" : '';
    header("Location: ../../_html/_cadastro/cadNivel.php{$qs}");
    exit;
}

try {
    if ($nivelOriginal && $idPlano) {
        // === UPDATE ===
        $pk = $nivelOriginal==='basic'   ? 'idBasic'
             : ($nivelOriginal==='classic' ? 'idClassic' : 'idMaster');

        $sql = "
            UPDATE {$nivelOriginal}
               SET nome     = ?,
                   primeira = ?,
                   segunda  = ?,
                   terceira = ?,
                   quarta   = ?,
                   segmento = ?,
                   idAdm    = ?
             WHERE {$pk} = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nomePlano, $p1, $p2, $p3, $p4,
            $segmento, $idAdm, $idPlano
        ]);

        $_SESSION['success'] = 'Nível atualizado com sucesso.';
    } else {
        // === INSERT ===
        $sql = "
            INSERT INTO {$tabelaNivel}
               (nome, primeira, segunda, terceira, quarta, segmento, idAdm)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nomePlano, $p1, $p2, $p3, $p4,
            $segmento, $idAdm
        ]);

        $_SESSION['success'] = 'Nível cadastrado com sucesso.';
    }

    header('Location: ../../_html/_lista/listaNivel.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = 'Erro ao salvar nível: ' . htmlspecialchars($e->getMessage());
    $qs = $nivelOriginal ? "?nivel={$nivelOriginal}&idPlano={$idPlano}" : '';
    header("Location: ../../_html/_cadastro/cadNivel.php{$qs}");
    exit;
}
?>