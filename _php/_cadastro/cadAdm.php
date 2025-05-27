<?php
include('../../_php/_login/logado.php');
if ($acesso !== 'admin') {
    exit('Acesso negado.');
}

require_once __DIR__ . '/../../config/db.php';

// 1) Detecta se é edição
$editing = isset($_POST['editing']) && $_POST['editing'] === '1';
$idAdm = isset($_POST['idAdm']) ? (int) $_POST['idAdm'] : null;

// 2) Coleta dados do formulário
$nome  = trim($_POST['nome']  ?? '');
$cnpj  = trim($_POST['cnpj']  ?? '');

// 3) Validações servidor-side
if ($nome === '' || $cnpj === '') {
    $_SESSION['error'] = 'Nome e CNPJ são obrigatórios.';
    $query = $editing ? "?idAdm={$idAdm}" : '';
    header("Location: ../../_html/_cadastro/cadAdm.php{$query}");
    exit;
}

// Formato de CNPJ: 14 dígitos, aceita máscara
if (!preg_match('/^\\d{2}\\.?(\\d{3})\\.?(\\d{3})\\/?(\\d{4})-?(\\d{2})$/', $cnpj)) {
    $_SESSION['error'] = 'CNPJ em formato inválido.';
    $query = $editing ? "?idAdm={$idAdm}" : '';
    header("Location: ../../_html/_cadastro/cadAdm.php{$query}");
    exit;
}

try {
    if ($editing) {
        // === UPDATE ===
        $sql = "UPDATE cad_adm SET nome = ?, cnpj = ? WHERE idAdm = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $cnpj, $idAdm]);
        $_SESSION['success'] = 'Administradora atualizada com sucesso.';
    } else {
        // === INSERT ===
        $sql = "INSERT INTO cad_adm (nome, cnpj) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $cnpj]);
        $_SESSION['success'] = 'Administradora cadastrada com sucesso.';
    }

    header('Location: ../../_html/_lista/listaAdm.php');
    exit;

} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) {
        $_SESSION['error'] = 'CNPJ ou Código já cadastrado no sistema.';
    } else {
        $_SESSION['error'] = 'Erro ao salvar Administradora: ' . $e->getMessage();
    }
    $query = $editing ? "?idAdm={$idAdm}" : '';
    header("Location: ../../_html/_cadastro/cadAdm.php{$query}");
    exit;
}
?>