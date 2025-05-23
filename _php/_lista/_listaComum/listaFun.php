<?php
require_once __DIR__ . '/../../../config/db.php';
if ($acesso !== 'admin') exit;

$stmt = $pdo->query("SELECT idFun,nome, ativo, nivel FROM cad_fun ORDER BY nome");
$funcs = $stmt->fetchAll();
if (!$funcs) {
    echo "<tr><td colspan='4'>Nenhum funcionário encontrado.</td></tr>";
} else {
    foreach ($funcs as $row) {
        $nomeF   = htmlspecialchars($row['nome'], ENT_QUOTES);
        $ativoF  = htmlspecialchars($row['ativo'], ENT_QUOTES);
        $nivelF  = htmlspecialchars($row['nivel'], ENT_QUOTES);
        echo "<tr>";
        echo "<td data-label='Nome'>$nomeF</td>";
        echo "<td data-label='Ativo'>$ativoF</td>";
        echo "<td data-label='Nível'>$nivelF</td>";
        echo "<td data-label='Ações'><a href='../_lista/listaVenda.php?idFun={$row['idFun']}' class='btn btn-primary'>Ver</a>
            <a href='../../_html/_cadastro/cadFun.php?idFun={$row['idFun']}' class='btn btn-sm btn-warning'>Editar</a>
        </td>";
        
        echo "</tr>";
    }
}
?>
