<?php
require_once __DIR__ . '/../../../config/db.php';
if ($acesso !== 'admin') exit;

$limit = 15; // Número de registros por página
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$offset = ($page - 1) * $limit;

// Obter o total de registros
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM cad_fun");
$totalRows = $stmtTotal->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Obter os registros da página atual
$stmt = $pdo->prepare("SELECT idFun, nome, ativo, nivel FROM cad_fun ORDER BY nome LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$funcs = $stmt->fetchAll();
echo"   <thead>
          <tr>
            <th>Nome</th>
            <th>Ativo</th>
            <th>Nivel</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>";

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
echo "</tbody></table>";

// Exibir a paginação se houver mais de uma página
if ($totalPages > 1) {
    echo "<nav aria-label='Paginação de funcionários'><ul class='pagination'>";
    $prevPage = max(1, $page - 1);
    echo "<li class='page-item". ($page==1? ' disabled':'') ."'>";
    echo "<a class='page-link' href='?page=$prevPage' aria-label='Anterior'>Anterior</a></li>";
    for ($p=1; $p<=$totalPages; $p++) {
        echo "<li class='page-item". ($p==$page? ' active':'') ."'>";
        echo "<a class='page-link' href='?page=$p'>$p</a></li>";
    }
    $nextPage = min($totalPages, $page + 1);
    echo "<li class='page-item". ($page==$totalPages? ' disabled':'') ."'>";
    echo "<a class='page-link' href='?page=$nextPage' aria-label='Próximo'>Próximo</a></li>";
    echo "</ul></nav>";
}
?>
