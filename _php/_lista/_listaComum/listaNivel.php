<?php
require_once __DIR__ . '/../../../config/db.php';
if ($acesso !== 'admin') exit;

$tiposNivel = ['master' => 'Master', 'classic' => 'Classic', 'basic' => 'Basic'];
foreach ($tiposNivel as $tabela => $rotulo) {
    $stmt = $pdo->query("SELECT n.nome, n.primeira, n.segunda, n.terceira, n.quarta, a.nome AS adm_nome 
                         FROM $tabela n JOIN cad_adm a ON a.idAdm = n.idAdm");
    $planos = $stmt->fetchAll();
    echo "<h3>$rotulo</h3>";
    echo "<table><thead><tr>
            <th>Plano</th><th>1ª (%)</th><th>2ª (%)</th><th>3ª (%)</th><th>4ª (%)</th><th>Administradora</th>
          </tr></thead><tbody>";
    if (!$planos) {
        echo "<tr><td colspan='6'>Nenhum nível $rotulo cadastrado.</td></tr>";
    } else {
        foreach ($planos as $row) {
            $nomePlano = htmlspecialchars($row['nome'], ENT_QUOTES);
            $p1 = htmlspecialchars($row['primeira'], ENT_QUOTES);
            $p2 = htmlspecialchars($row['segunda'], ENT_QUOTES);
            $p3 = htmlspecialchars($row['terceira'], ENT_QUOTES);
            $p4 = htmlspecialchars($row['quarta'], ENT_QUOTES);
            $admNome = htmlspecialchars($row['adm_nome'], ENT_QUOTES);
            echo "<tr>";
            echo "<td data-label='Plano'>$nomePlano</td>";
            echo "<td data-label='1ª (%)'>$p1</td>";
            echo "<td data-label='2ª (%)'>$p2</td>";
            echo "<td data-label='3ª (%)'>$p3</td>";
            echo "<td data-label='4ª (%)'>$p4</td>";
            echo "<td data-label='Administradora'>$admNome</td>";
            echo "</tr>";
        }
    }
    echo "</tbody></table><br/>";
}
?>
