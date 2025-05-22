<?php
include('../../_php/_login/logado.php');      // garente que usuário está logado e $pdo disponível
if ($acesso !== 'admin') { exit('Acesso negado.'); }

$nome = trim($_POST['nome'] ?? '');
$cnpj = trim($_POST['cnpj'] ?? '');

// Validações básicas servidor-side
if ($nome === '' || $cnpj === '') {
    $_SESSION['error'] = 'Nome e CNPJ são obrigatórios.';
    header('Location: ../../_html/_cadastro/cadAdm.php');
    exit;
}
// Validação de formato de CNPJ (14 dígitos, permite máscara com .-/)
if (!preg_match('/^\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}$/', $cnpj)) {
    $_SESSION['error'] = 'CNPJ em formato inválido.';
    header('Location: ../../_html/_cadastro/cadAdm.php');
    exit;
}

try {
    require_once __DIR__ . '/../../config/db.php';
    // Prepara consulta para inserir nova administradora
    $stmt = $pdo->prepare("INSERT INTO cad_adm (cnpj, nome) VALUES (?, ?)");
    $stmt->execute([$cnpj, $nome]);
    // Sucesso - redireciona para página de sucesso ou lista
    header('Location: ../../_html/_cadastro/sucesso.php');
    exit;
} catch (PDOException $e) {
    // Trata erro de violação de unicidade do CNPJ ou outros erros SQL
    if ($e->errorInfo[1] == 1062) {  // Código 1062: entrada duplicada
        $_SESSION['error'] = 'CNPJ já cadastrado no sistema.';
    } else {
        $_SESSION['error'] = 'Erro ao cadastrar Administradora: ' . $e->getMessage();
    }
    header('Location: ../../_html/_cadastro/cadAdm.php');
    exit;
}
?>
