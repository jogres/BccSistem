<?php

require_once __DIR__ . '/../../../config/db.php';

$acesso = $_SESSION['acesso'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

// 1) Verifica sessão
if (!$userId) {
    header('Location: ../../_html/_login/index.php');
    exit;
}
echo "<table>";

// 2) Determina filtro por funcionário
$useFilter = false;
if ($acesso === 'admin' && isset($_GET['idFun'])) {
    $filterFun = (int) $_GET['idFun'];
    $useFilter = true;
} elseif ($acesso !== 'admin') {
    $filterFun = $userId;
    $useFilter = true;
}

// 3) Paginação
$limit = 10;
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$offset = ($page - 1) * $limit;

// 4) Contar total de vendas para filtro
$countSql = "SELECT COUNT(*) FROM venda v JOIN venda_fun vf ON vf.idVenda = v.id";
$countParams = [];
if ($useFilter) {
    $countSql .= " WHERE vf.idFun = ?";
    $countParams[] = $filterFun;
}
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($countParams);
$totalRows = (int)$stmtCount->fetchColumn();
$totalPages = (int)ceil($totalRows / $limit);

// 5) Carrega vendas com limit e offset usando literais para evitar binding de strings
$sql = "
    SELECT
        v.id        AS venda_pk,
        v.idVenda   AS contrato,
        cli.nome    AS cliente,
        fun.nome    AS vendedor,
        DATE_FORMAT(v.dataV, '%d/%m/%Y') AS data_venda,
        adm.nome    AS administradora,
        v.valor     AS valor_venda
    FROM venda v
    JOIN cad_adm    adm ON adm.idAdm   = v.idAdm
    JOIN venda_cli  vc  ON vc.idVenda  = v.id
    JOIN cad_cli    cli ON cli.idCli   = vc.idCli
    JOIN venda_fun  vf  ON vf.idVenda  = v.id
    JOIN cad_fun    fun ON fun.idFun   = vf.idFun
";
$params = [];
if ($useFilter) {
    $sql .= " WHERE vf.idFun = ?";
    $params[] = $filterFun;
}
// Adiciona paginação diretamente
$sql .= " ORDER BY v.dataV DESC LIMIT {$limit} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6) Carrega comissões se necessário
$commissions = [];
if ($useFilter && $vendas) {
    $saleIds = array_column($vendas, 'venda_pk');
    $placeholders = implode(',', array_fill(0, count($saleIds), '?'));
    $sqlComm = "
        SELECT idVenda, primeira, segunda, terceira, quarta, totalC AS total_comissao
        FROM comissao
        WHERE idVenda IN ($placeholders) AND idFun = ?
    ";
    $stmtComm = $pdo->prepare($sqlComm);
    $stmtComm->execute(array_merge($saleIds, [$filterFun]));
    foreach ($stmtComm->fetchAll(PDO::FETCH_ASSOC) as $c) {
        $commissions[$c['idVenda']] = $c;
    }
    echo "<thead> <tr> <th>Contrato</th><th>Cliente</th><th>Vendedor</th><th>Data</th><th>Adiministradora</th><th>Valor</th><th>1ª </th><th>2ª </th><th>3ª (%)</th><th>4ª (%)</th><th>Total Comissão</th> </tr> </thead>";
}else {
    echo "<thead> <tr> <th>Contrato</th><th>Cliente</th><th>Vendedor</th><th>Data</th><th>Adiministradora</th><th>Valor</th> </tr> </thead>";
}

// 7) Renderiza a tabela
$colspan = $useFilter ? 11 : 6;
if (empty($vendas)) {
    echo "<tr><td colspan='{$colspan}'>Nenhuma venda encontrada.</td></tr>";
} else {
    foreach ($vendas as $row) {
        $vendaPk    = $row['venda_pk'];
        $contrato   = htmlspecialchars($row['contrato'], ENT_QUOTES);
        $cliente    = htmlspecialchars($row['cliente'], ENT_QUOTES);
        $vendedor   = htmlspecialchars($row['vendedor'], ENT_QUOTES);
        $data       = htmlspecialchars($row['data_venda'], ENT_QUOTES);
        $adm        = htmlspecialchars($row['administradora'], ENT_QUOTES);
        $valorVenda = 'R$ ' . number_format($row['valor_venda'], 2, ',', '.');
        echo "<tbody>";
        echo "<tr>";
        echo "<td data-label='Contrato'>$contrato</td>";
        echo "<td data-label='Cliente'>$cliente</td>";
        echo "<td data-label='Vendedor'>$vendedor</td>";
        echo "<td data-label='Data'>$data</td>";
        echo "<td data-label='Administradora'>$adm</td>";
        echo "<td data-label='Valor Venda'>$valorVenda</td>";

        if ($useFilter) {
            $c = $commissions[$vendaPk] ?? ['primeira'=>0,'segunda'=>0,'terceira'=>0,'quarta'=>0,'total_comissao'=>0];
            $com1  = 'R$ ' . number_format($c['primeira'], 2, ',', '.');
            $com2  = 'R$ ' . number_format($c['segunda'], 2, ',', '.');
            $com3  = 'R$ ' . number_format($c['terceira'], 2, ',', '.');
            $com4  = 'R$ ' . number_format($c['quarta'], 2, ',', '.');
            $total = 'R$ ' . number_format($c['total_comissao'], 2, ',', '.');

            echo "<td data-label='Comissão 1'>$com1</td>";
            echo "<td data-label='Comissão 2'>$com2</td>";
            echo "<td data-label='Comissão 3'>$com3</td>";
            echo "<td data-label='Comissão 4'>$com4</td>";
            echo "<td data-label='Total Comissão'>$total</td>";
        }
        echo "</tr>";

    }
}
echo "</tbody>";
echo "</table>";

// 8) Navegação de páginas
if (isset($totalPages) && $totalPages > 1) {
    echo "<nav aria-label='Paginação de vendas'><ul class='pagination'>";
    $prevPage = max(1, $page - 1);
    echo "<li class='page-item". ($page==1? ' disabled':'') ."'>";
    echo "<a class='page-link' href='?". ($useFilter? "idFun=$filterFun&": '') ."page=$prevPage' aria-label='Anterior'>Anterior</a></li>";
    for ($p=1; $p<=$totalPages; $p++) {
        echo "<li class='page-item". ($p==$page? ' active':'') ."'>";
        echo "<a class='page-link' href='?". ($useFilter? "idFun=$filterFun&": '') ."page=$p'>$p</a></li>";
    }
    $nextPage = min($totalPages, $page + 1);
    echo "<li class='page-item". ($page==$totalPages? ' disabled':'') ."'>";
    echo "<a class='page-link' href='?". ($useFilter? "idFun=$filterFun&": '') ."page=$nextPage' aria-label='Próximo'>Próximo</a></li>";
    echo "</ul></nav>";
}