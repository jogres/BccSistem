<?php
// vendas_list_include.php
// Arquivo de listagem de vendas para include em template HTML

// Conexão com o banco de dados
$conn = mysqli_connect("localhost", "root", "", "bcc");
if (!$conn) {
    die("Falha na conexão: " . mysqli_connect_error());
}

// Verifica se o usuário tem acesso para visualizar vendas
if (!isset($acesso) || $acesso !== 'admin') {
    exit;
}

// Monta e executa a consulta de vendas, clientes e vendedores
$sql = "SELECT
    v.idVenda                       AS contrato,
    cli.nome                        AS cliente,
    fun.nome                        AS vendedor,
    adm.nome                        AS administradora,
    v.valor                         AS valor_venda,
    DATE_FORMAT(v.dataV, '%d/%m/%Y') AS data_venda
FROM venda v
INNER JOIN cad_adm    adm ON adm.idAdm    = v.idAdm
INNER JOIN venda_cli  vc  ON vc.idVenda   = v.id
INNER JOIN cad_cli    cli ON cli.idCli    = vc.idCli
INNER JOIN venda_fun  vf  ON vf.idVenda   = v.id
INNER JOIN cad_fun    fun ON fun.idFun    = vf.idFun
ORDER BY v.dataV DESC;
";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Erro na consulta: " . mysqli_error($conn));
}

// Se não houver registros, exibe mensagem
if (mysqli_num_rows($result) === 0) {
    echo '<tr><td colspan="5">Nenhuma venda encontrada.</td></tr>';
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['contrato'], ENT_QUOTES) . '</td>';
        echo '<td>' . htmlspecialchars($row['cliente'], ENT_QUOTES) . '</td>';
        echo '<td>' . htmlspecialchars($row['vendedor'], ENT_QUOTES) . '</td>';
        echo '<td>R$ ' . number_format($row['valor_venda'], 2, ',', '.') . '</td>';
        echo '<td>' . htmlspecialchars($row['data_venda'], ENT_QUOTES) . '</td>';
        echo '<td>' . htmlspecialchars($row['administradora'], ENT_QUOTES) . '</td>';
        echo '</tr>';
    }
}

// Libera memória e fecha conexão
mysqli_free_result($result);
mysqli_close($conn);
?>