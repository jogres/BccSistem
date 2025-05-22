<?php
// clientes_list_include.php
// Arquivo de listagem de clientes com vendas para include em template HTML

// Conexão com o banco de dados
$conn = mysqli_connect("localhost", "root", "", "bcc");
if (!$conn) {
    die("Falha na conexão: " . mysqli_connect_error());
}

// Verifica permissão de acesso
if (!isset($acesso) || $acesso !== 'admin') {
    exit;
}

// Consulta de clientes que efetuaram vendas
$sql = "
SELECT DISTINCT
    cli.nome     AS cliente,
    cli.cpf      AS cpf,
    cli.telefone AS telefone,
    cli.endereco AS endereco
FROM cad_cli cli
";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Erro na consulta de clientes: " . mysqli_error($conn));
}

// Exibe resultado ou mensagem de nenhum registro
if (mysqli_num_rows($result) === 0) {
    echo '<tr><td colspan="4">Nenhum cliente encontrado.</td></tr>';
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['cliente'], ENT_QUOTES)   . '</td>';
        echo '<td>' . htmlspecialchars($row['cpf'], ENT_QUOTES)       . '</td>';
        echo '<td>' . htmlspecialchars($row['telefone'], ENT_QUOTES) . '</td>';
        echo '<td>' . htmlspecialchars($row['endereco'], ENT_QUOTES) . '</td>';
        echo '</tr>';
    }
}

// Libera recursos e fecha conexão
mysqli_free_result($result);
mysqli_close($conn);
?>
