<?php
require_once __DIR__ . '/../../../config/db.php';
if ($acesso !== 'admin') exit;

// Consulta vendas com joins para trazer info de cliente, vendedor e administradora
$sql = "SELECT 
          v.idVenda          AS contrato,
          cli.nome           AS cliente,
          fun.nome           AS vendedor,
          adm.nome           AS administradora,
          v.valor            AS valor_venda,
          DATE_FORMAT(v.dataV, '%d/%m/%Y') AS data_venda
        FROM venda v
        JOIN cad_adm    adm ON adm.idAdm = v.idAdm
        JOIN venda_cli  vc  ON vc.id = v.id   /* id = PK de venda */
        JOIN cad_cli    cli ON cli.idCli = vc.idCli
        JOIN venda_fun  vf  ON vf.id = v.id
        JOIN cad_fun    fun ON fun.idFun = vf.idFun
        ORDER BY v.dataV DESC";
$stmt = $pdo->query($sql);
$vendas = $stmt->fetchAll();
if (!$vendas) {
    echo "<tr><td colspan='6'>Nenhuma venda encontrada.</td></tr>";
} else {
    foreach ($vendas as $row) {
        $contrato = htmlspecialchars($row['contrato'], ENT_QUOTES);
        $cliente  = htmlspecialchars($row['cliente'], ENT_QUOTES);
        $vend     = htmlspecialchars($row['vendedor'], ENT_QUOTES);
        $adm      = htmlspecialchars($row['administradora'], ENT_QUOTES);
        $valor    = 'R$ ' . number_format($row['valor_venda'], 2, ',', '.');
        $dataV    = htmlspecialchars($row['data_venda'], ENT_QUOTES);
        echo "<tr>";
        echo "<td data-label='Contrato'>$contrato</td>";
        echo "<td data-label='Cliente'>$cliente</td>";
        echo "<td data-label='Vendedor'>$vend</td>";
        echo "<td data-label='Valor'>$valor</td>";
        echo "<td data-label='Data'>$dataV</td>";
        echo "<td data-label='Administradora'>$adm</td>";
        echo "</tr>";
    }
}
?>
