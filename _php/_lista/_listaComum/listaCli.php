<?php
require_once __DIR__ . '/../../../config/db.php';
if ($acesso !== 'admin' && $acesso !== 'user') exit;

// Definir o número de registros por página
$limite = 15;

// Obter o número da página atual a partir da URL
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) {
    $pagina = 1;
}

// Calcular o offset para a consulta SQL
$offset = ($pagina - 1) * $limite;

// Obter o total de registros
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM cad_cli");
$totalRegistros = $stmtTotal->fetchColumn();

// Calcular o total de páginas
$totalPaginas = ceil($totalRegistros / $limite);

// Obter os registros da página atual
$stmt = $pdo->prepare("
    SELECT 
        idCli,
        nome, 
        cpf, 
        telefone, 
        endereco 
    FROM cad_cli 
    ORDER BY nome
    LIMIT :limite OFFSET :offset
");
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table class='table table-striped'>
        <thead>
            <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>Telefone</th>
                <th>Endereço</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>";

// Exibir os registros
if (!$clientes) {
    echo "<tr><td colspan='5'>Nenhum cliente encontrado.</td></tr>";
} else {
    foreach ($clientes as $row) {
        $idCli = (int)$row['idCli'];
        $nomeC  = htmlspecialchars($row['nome'],      ENT_QUOTES);
        $cpfC   = htmlspecialchars($row['cpf'],       ENT_QUOTES);
        $telC   = htmlspecialchars($row['telefone'],  ENT_QUOTES);
        $endC   = htmlspecialchars($row['endereco'],  ENT_QUOTES);

        echo "<tr>";
        echo "  <td data-label='Nome'>$nomeC</td>";
        echo "  <td data-label='CPF'>$cpfC</td>";
        echo "  <td data-label='Telefone'>$telC</td>";
        echo "  <td data-label='Endereço'>$endC</td>";
        echo "  <td data-label='Ações'><a href='../../_html/_cadastro/cadCli.php?idCli=$idCli' class='btn btn-sm btn-warning'>Editar</a> </td>";
        echo "</tr>";
    }
}
echo "</tbody></table>";

// Exibir a paginação
if ($totalPaginas > 1) {
    echo "<nav aria-label='Paginação de clientes'><ul class='pagination'>";
    $paginaAnterior = max(1, $pagina - 1);
    echo "<li class='page-item" . ($pagina == 1 ? ' disabled' : '') . "'>";
    echo "<a class='page-link' href='?pagina=$paginaAnterior' aria-label='Anterior'>Anterior</a></li>";
    for ($p = 1; $p <= $totalPaginas; $p++) {
        echo "<li class='page-item" . ($p == $pagina ? ' active' : '') . "'>";
        echo "<a class='page-link' href='?pagina=$p'>$p</a></li>";
    }
    $paginaSeguinte = min($totalPaginas, $pagina + 1);
    echo "<li class='page-item" . ($pagina == $totalPaginas ? ' disabled' : '') . "'>";
    echo "<a class='page-link' href='?pagina=$paginaSeguinte' aria-label='Próximo'>Próximo</a></li>";
    echo "</ul></nav>";
}
?>
