<?php
// _php/_lista/_listaComum/listaAdm.php

require_once __DIR__ . '/../../../config/db.php';

// Verifica se o usuário tem permissão de acesso
if ($acesso !== 'admin') exit;

// Define o número de registros por página
$limit = 15;

// Obtém o número da página atual a partir da URL
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// Calcula o deslocamento para a consulta SQL
$offset = ($page - 1) * $limit;

// Obtém o total de registros
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM cad_adm");
$totalRegistros = $stmtTotal->fetchColumn();

// Calcula o total de páginas
$totalPages = ceil($totalRegistros / $limit);

// Prepara e executa a consulta para obter os registros da página atual
$stmt = $pdo->prepare("SELECT idAdm, nome, cnpj FROM cad_adm ORDER BY nome LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table class='table table-striped'>
        <thead>
            <tr>
                <th>Nome</th>
                <th>CNPJ</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>";

// Exibe os registros
if (!$admins) {
    echo "<tr><td colspan='3'>Nenhuma administradora encontrada.</td></tr>";
} else {
    foreach ($admins as $adm) {
        $id   = (int) $adm['idAdm'];
        $nome = htmlspecialchars($adm['nome'], ENT_QUOTES);
        $cnpj = htmlspecialchars($adm['cnpj'], ENT_QUOTES);

        echo "<tr>";
        echo "  <td data-label='Nome'>{$nome}</td>";
        echo "  <td data-label='CNPJ'>{$cnpj}</td>";
        echo "  <td data-label='Ações'>";
        echo "    <a href='../../_html/_cadastro/cadAdm.php?idAdm={$id}' class='btn btn-sm btn-warning'>Editar</a>";
        echo "  </td>";
        echo "</tr>";
    }
}

echo "</tbody></table>";
// Exibe a paginação
if ($totalPages > 1) {
    echo "<nav aria-label='Paginação de administradoras'><ul class='pagination'>";

    // Página anterior
    $prevPage = max(1, $page - 1);
    echo "<li class='page-item" . ($page == 1 ? ' disabled' : '') . "'>";
    echo "<a class='page-link' href='?page=$prevPage' aria-label='Anterior'>Anterior</a></li>";

    // Páginas numeradas
    for ($p = 1; $p <= $totalPages; $p++) {
        echo "<li class='page-item" . ($p == $page ? ' active' : '') . "'>";
        echo "<a class='page-link' href='?page=$p'>$p</a></li>";
    }

    // Próxima página
    $nextPage = min($totalPages, $page + 1);
    echo "<li class='page-item" . ($page == $totalPages ? ' disabled' : '') . "'>";
    echo "<a class='page-link' href='?page=$nextPage' aria-label='Próximo'>Próximo</a></li>";

    echo "</ul></nav>";
}
?>
