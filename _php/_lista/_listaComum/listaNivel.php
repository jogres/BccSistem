<?php
require_once __DIR__ . '/../../../config/db.php';
if ($acesso !== 'admin') exit;

$tiposNivel = [
    'master'  => ['label' => 'Master',  'pk' => 'idMaster'],
    'classic' => ['label' => 'Classic', 'pk' => 'idClassic'],
    'basic'   => ['label' => 'Basic',   'pk' => 'idBasic'],
];

foreach ($tiposNivel as $tabela => $info) {
    $rotulo = $info['label'];
    $pk     = $info['pk'];

    $stmt = $pdo->query(
        "SELECT n.{$pk} AS idPlano,
                n.nome,
                n.primeira,
                n.segunda,
                n.terceira,
                n.quarta,
                a.nome AS adm_nome
         FROM {$tabela} n
         JOIN cad_adm a ON a.idAdm = n.idAdm
         ORDER BY n.nome"
    );
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
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>";

    if (empty($planos)) {
        echo "<tr><td colspan='7'>Nenhum nível {$rotulo} cadastrado.</td></tr>";
    } else {
        foreach ($planos as $row) {
            $idPlano   = (int) $row['idPlano'];
            $nomePlano = htmlspecialchars($row['nome'], ENT_QUOTES);
            $p1        = htmlspecialchars($row['primeira'], ENT_QUOTES);
            $p2        = htmlspecialchars($row['segunda'],  ENT_QUOTES);
            $p3        = htmlspecialchars($row['terceira'], ENT_QUOTES);
            $p4        = htmlspecialchars($row['quarta'],  ENT_QUOTES);
            $admNome   = htmlspecialchars($row['adm_nome'],ENT_QUOTES);

            echo "<tr>";
            echo "<td data-label='Plano'>{$nomePlano}</td>";
            echo "<td data-label='1ª (%)'>{$p1}</td>";
            echo "<td data-label='2ª (%)'>{$p2}</td>";
            echo "<td data-label='3ª (%)'>{$p3}</td>";
            echo "<td data-label='4ª (%)'>{$p4}</td>";
            echo "<td data-label='Administradora'>{$admNome}</td>";
            echo "<td data-label='Ações'>";
            echo "<a href='../../_html/_cadastro/cadNivel.php?nivel={$tabela}&idPlano={$idPlano}' class='btn btn-sm btn-warning'>Editar</a>";
            echo "</td>";
            echo "</tr>";
        }
    }

    echo "</tbody></table><br/>";
}
?>
