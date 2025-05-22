<?php
require_once __DIR__ . '/../../../config/db.php';
if ($acesso !== 'admin') exit;

// Seleciona clientes (poderia filtrar apenas os associados ao usuário, mas admin vê todos)
$stmt = $pdo->query("SELECT nome, cpf, telefone, endereco FROM cad_cli ORDER BY nome");
$clientes = $stmt->fetchAll();
if (!$clientes) {
    echo "<tr><td colspan='5'>Nenhum cliente encontrado.</td></tr>";
} else {
    foreach ($clientes as $row) {
        $nomeC  = htmlspecialchars($row['nome'], ENT_QUOTES);
        $cpfC   = htmlspecialchars($row['cpf'], ENT_QUOTES);
        $telC   = htmlspecialchars($row['telefone'], ENT_QUOTES);
        $endC   = htmlspecialchars($row['endereco'], ENT_QUOTES);
        echo "<tr>";
        echo "<td data-label='Nome'>$nomeC</td>";
        echo "<td data-label='CPF'>$cpfC</td>";
        echo "<td data-label='Telefone'>$telC</td>";
        echo "<td data-label='Endereço'>$endC</td>";
        echo "<td data-label='Ações'>-</td>";
        echo "</tr>";
    }
}
?>
