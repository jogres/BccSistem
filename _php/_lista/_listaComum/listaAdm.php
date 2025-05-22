<?php
require_once __DIR__ . '/../../../config/db.php';
if ($acesso !== 'admin') exit;  // somente admin visualiza

$stmt = $pdo->query("SELECT nome, cnpj FROM cad_adm ORDER BY nome");
$admins = $stmt->fetchAll();
if (!$admins) {
    // Nenhum registro
    echo "<tr><td colspan='3'>Nenhuma administradora encontrada.</td></tr>";
} else {
    foreach ($admins as $row) {
        $nomeAdm = htmlspecialchars($row['nome'], ENT_QUOTES);
        $cnpjAdm = htmlspecialchars($row['cnpj'], ENT_QUOTES);
        echo "<tr>";
        echo "<td data-label='Nome'>$nomeAdm</td>";
        echo "<td data-label='CNPJ'>$cnpjAdm</td>";
        
        echo "</tr>";
    }
}
?>
