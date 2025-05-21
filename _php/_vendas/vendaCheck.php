<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['venda'])) {
    $_SESSION['venda'] = $_POST['venda'];

    // Redireciona de volta à página principal
    header('Location: ../../_html/_cadastro/cadCli.php');// ajuste o caminho se necessário
    exit;
}
?>

  
