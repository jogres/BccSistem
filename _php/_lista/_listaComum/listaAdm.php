<?php
// _php/_lista/_listaComum/listaAdm.php

// Busca todas as administradoras, ordenadas por nome
$stmt = $pdo->query("SELECT idAdm, nome, cnpj FROM cad_adm ORDER BY nome");
$admins = $stmt->fetchAll();

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
        echo "    <a href='../../_html/_cadastro/cadAdm.php?idAdm={$id}' class='btn btn-sm btn-warning'>";
        echo "      Editar";
        echo "    </a>";
        echo "  </td>";
        echo "</tr>";
    }
}
