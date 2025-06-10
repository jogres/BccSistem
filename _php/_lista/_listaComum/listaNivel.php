<?php
require_once __DIR__ . '/../../../config/db.php';
if ($acesso !== 'admin') exit;

$limit = 10; // Número de registros por página
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$offset = ($page - 1) * $limit;

$tiposNivel = [
    'master'  => ['label' => 'Master',  'pk' => 'idMaster'],
    'classic' => ['label' => 'Classic', 'pk' => 'idClassic'],
    'basic'   => ['label' => 'Basic',   'pk' => 'idBasic'],
];

foreach ($tiposNivel as $tabela => $info) {
    $rotulo = $info['label'];
    $pk     = $info['pk'];

    // Obter o total de registros
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM {$tabela}");
    $totalRows = $stmtTotal->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    // Obter os registros da página atual
    $stmt = $pdo->prepare(
        "SELECT n.{$pk} AS idPlano,
                n.nome,
                n.primeira,
                n.segunda,
                n.terceira,
                n.quarta,
                n.segmento,
                a.nome AS adm_nome
         FROM {$tabela} n
         JOIN cad_adm a ON a.idAdm = n.idAdm
         ORDER BY n.nome
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>{$rotulo}</h3>";
    echo "<table>
            <thead>
              <tr>
                <th>Plano</th>
                <th>1ª (%)</th>
                <th>2ª (%)</th>
                <th>3ª (%)</th>
                <th>4ª (%)</th>
                <th>Administradora</th>
                <th>Segmento</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>";

    if (empty($planos)) {
        echo "<tr><td colspan='8'>Nenhum nível {$rotulo} cadastrado.</td></tr>";
    } else {
        foreach ($planos as $row) {
            $idPlano   = (int) $row['idPlano'];
            $nomePlano = htmlspecialchars($row['nome'], ENT_QUOTES);
            $p1        = htmlspecialchars($row['primeira'], ENT_QUOTES);
            $p2        = htmlspecialchars($row['segunda'],  ENT_QUOTES);
            $p3        = htmlspecialchars($row['terceira'], ENT_QUOTES);
            $p4        = htmlspecialchars($row['quarta'],  ENT_QUOTES);
            $admNome   = htmlspecialchars($row['adm_nome'],ENT_QUOTES);
            $segmento  = htmlspecialchars($row['segmento'], ENT_QUOTES);

            echo "<tr>";
            echo "<td data-label='Plano'>{$nomePlano}</td>";
            echo "<td data-label='1ª (%)'>{$p1}</td>";
            echo "<td data-label='2ª (%)'>{$p2}</td>";
            echo "<td data-label='3ª (%)'>{$p3}</td>";
            echo "<td data-label='4ª (%)'>{$p4}</td>";
            echo "<td data-label='Administradora'>{$admNome}</td>";
            echo "<td data-label='Segmento'>{$segmento}</td>";
            echo "<td data-label='Ações'>";
            echo "<a href='../../_html/_cadastro/cadNivel.php?nivel={$tabela}&idPlano={$idPlano}' class='btn btn-sm btn-warning'>Editar</a>";
            echo "</td>";
            echo "</tr>";
        }
    }

    echo "</tbody></table><br/>";

    // Exibir a paginação se houver mais de uma página
  
}
  if ($totalPages > 1) {
        echo "<nav aria-label='Paginação de {$rotulo}'><ul class='pagination'>";
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
