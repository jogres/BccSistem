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
$limit = 15;
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

// 5) Carrega vendas com limit e offset
$sql = "
    SELECT
        v.id        AS venda_pk,
        v.idVenda   AS contrato,
        cli.nome    AS cliente,
        fun.nome    AS vendedor,
        DATE_FORMAT(v.dataV, '%d/%m/%Y') AS data_venda,
        adm.nome    AS administradora,
        v.valor     AS valor_venda,
        v.segmento  AS segmento,
        v.tipo      AS tipo_venda
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
$sql .= " ORDER BY v.dataV DESC LIMIT {$limit} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6) Renderiza cabeçalho da tabela
if ($useFilter) {
    echo "<thead><tr>"
       ."<th>Contrato</th><th>Cliente</th><th>Vendedor</th><th>Data</th><th>Administradora</th><th>Segmento</th><th>Tipo</th><th>Valor</th>"
       ."<th>1ª Comissão</th><th>2ª Comissão</th><th>3ª Comissão</th><th>4ª Comissão</th><th>Total Comissão</th>"
       ."</tr></thead>";
} else {
    echo "<thead><tr>"
       ."<th>Contrato</th><th>Cliente</th><th>Vendedor</th><th>Data</th><th>Administradora</th><th>Segmento</th><th>Tipo</th><th>Valor</th>"
       ."</tr></thead>";
}

echo "<tbody>";

// 7) Renderiza cada linha de venda
if (empty($vendas)) {
    $colspan = $useFilter ? 11 : 6;
    echo "<tr><td colspan='{$colspan}'>Nenhuma venda encontrada.</td></tr>";
} else {
    // Prepara queries para comissão por parcela
    $stmtP1 = $pdo->prepare("SELECT valor FROM parcela1 WHERE idVenda = ? AND idFun = ?");
    $stmtP2 = $pdo->prepare("SELECT valor FROM parcela2 WHERE idVenda = ? AND idFun = ?");
    $stmtP3 = $pdo->prepare("SELECT valor FROM parcela3 WHERE idVenda = ? AND idFun = ?");
    $stmtP4 = $pdo->prepare("SELECT valor FROM parcela4 WHERE idVenda = ? AND idFun = ?");

    foreach ($vendas as $row) {
        $vendaPk    = $row['venda_pk'];
        $contrato   = htmlspecialchars($row['contrato'], ENT_QUOTES);
        $cliente    = htmlspecialchars($row['cliente'], ENT_QUOTES);
        $vendedor   = htmlspecialchars($row['vendedor'], ENT_QUOTES);
        $data       = htmlspecialchars($row['data_venda'], ENT_QUOTES);
        $adm        = htmlspecialchars($row['administradora'], ENT_QUOTES);
        $segmento   = htmlspecialchars($row['segmento'], ENT_QUOTES);
        $tipoVenda  = htmlspecialchars($row['tipo_venda'], ENT_QUOTES);
        
        // Lógica de divisão do valor da venda por número de funcionários
        $stmtCountFun = $pdo->prepare("SELECT COUNT(*) FROM venda_fun WHERE idVenda = ?");
        $stmtCountFun->execute([$vendaPk]);
        $countFun = (int)$stmtCountFun->fetchColumn() ?: 1;
        $sharedValue = $row['valor_venda'] / $countFun;
        $valorVenda  = 'R$ ' . number_format($sharedValue, 2, ',', '.');

        echo "<tr>";
        echo "<td data-label='Contrato'>{$contrato}</td>";
        echo "<td data-label='Cliente'>{$cliente}</td>";
        echo "<td data-label='Vendedor'>{$vendedor}</td>";
        echo "<td data-label='Data'>{$data}</td>";
        echo "<td data-label='Administradora'>{$adm}</td>";
        echo "<td data-label='Segmento'>{$segmento}</td>";
        echo "<td data-label='Tipo'>{$tipoVenda}</td>";
        echo "<td data-label='Valor Venda'>{$valorVenda}</td>";

        if ($useFilter) {
            // Parte de comissão por parcela
            foreach ([1,2,3,4] as $p) {
                $stmt = ${'stmtP'.$p};
                $stmt->execute([$vendaPk, $filterFun]);
                $val = $stmt->fetchColumn();
                $valFmt = $val ? 'R$ '.number_format($val,2,',','.') : '-';
                echo "<td data-label='{$p}ª Comissão'>{$valFmt}</td>";
            }
            // Total Comissão = soma das parcelas
            $total = 0;
            foreach ([1,2,3,4] as $p) {
                $stmt = ${'stmtP'.$p};
                $stmt->execute([$vendaPk, $filterFun]);
                $v = $stmt->fetchColumn();
                $total += $v ? floatval($v) : 0;
            }
            $totalFmt = 'R$ '.number_format($total,2,',','.');
            echo "<td data-label='Total Comissão'>{$totalFmt}</td>";
        }

        echo "</tr>";
    }
}

echo "</tbody></table>";

// 8) Paginação
if (isset($totalPages) && $totalPages > 1) {
    echo "<nav aria-label='Paginação de vendas'><ul class='pagination'>";
    $prevPage = max(1, $page - 1);
    echo "<li class='page-item".($page==1?' disabled':'')."'>";
    echo "<a class='page-link' href='?".($useFilter?"idFun=$filterFun&":'')."page=$prevPage'>Anterior</a></li>";
    for ($p=1; $p<=$totalPages; $p++) {
        echo "<li class='page-item".($p==$page?' active':'')."'>";
        echo "<a class='page-link' href='?".($useFilter?"idFun=$filterFun&":'')."page=$p'>$p</a></li>";
    }
    $nextPage = min($totalPages, $page + 1);
    echo "<li class='page-item".($page==$totalPages?' disabled':'')."'>";
    echo "<a class='page-link' href='?".($useFilter?"idFun=$filterFun&":'')."page=$nextPage'>Próximo</a></li>";
    echo "</ul></nav>";
}
?>
