<?php
  // Conecta ao banco
  $conn = new mysqli("localhost", "root", "", "bcc");
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  // Seleciona colunas com aliases para evitar ambiguidade
  $sqlM = "
    SELECT
      master.nome     AS nome_master,
      master.primeira,
      master.segunda,
      master.terceira,
      master.quarta,
      cad_adm.nome    AS nome_adm
    FROM master
    JOIN cad_adm ON cad_adm.idAdm = master.idAdm
  ";
  $sqlC = "
    SELECT
      classic.nome     AS nome_classic,
      classic.primeira,
      classic.segunda,
      classic.terceira,
      classic.quarta,
      cad_adm.nome    AS nome_adm
    FROM classic
    JOIN cad_adm ON cad_adm.idAdm = classic.idAdm
  ";
  $sqlB = "
    SELECT
      basic.nome     AS nome_brasic,
      basic.primeira,
      basic.segunda,
      basic.terceira,
      basic.quarta,
      cad_adm.nome    AS nome_adm
    FROM basic
    JOIN cad_adm ON cad_adm.idAdm = basic.idAdm
  ";

  $master = $conn->query($sqlM);
  if (!$master) {
    die("Query failed: " . $conn->error);
  }

  // Só exibe se for admin
  if ($acesso === 'admin') {
    echo "      <table>
        <thead>
          <tr>
            <th>Nome</th>
            <th>1ª</th>
            <th>2ª</th>
            <th>3ª</th>
            <th>4ª</th>
            <th>Adiministradora</th>
          </tr>
        </thead>
        <tbody>";
    while ($row = $master->fetch_assoc()) {
      echo "<tr>";
        echo "<td>" . htmlspecialchars($row['nome_master']) . "</td>";
        echo "<td>" . htmlspecialchars($row['primeira'])     . "</td>";
        echo "<td>" . htmlspecialchars($row['segunda'])      . "</td>";
        echo "<td>" . htmlspecialchars($row['terceira'])     . "</td>";
        echo "<td>" . htmlspecialchars($row['quarta'])       . "</td>";
        echo "<td>" . htmlspecialchars($row['nome_adm'])     . "</td>";
      echo "</tr>";
    }
    echo "</tbody>
      </table>";
  }
  // Exibe a tabela classic
  if ($acesso === 'admin') {
    $classic = $conn->query($sqlC);
    if (!$classic) {
      die("Query failed: " . $conn->error);
    }
    echo "      <table>
        <thead>
          <tr>
            <th>Nome</th>
            <th>1ª</th>
            <th>2ª</th>
            <th>3ª</th>
            <th>4ª</th>
            <th>Adiministradora</th>
          </tr>
        </thead>
        <tbody>";
    while ($row = $classic->fetch_assoc()) {
      echo "<tr>";
        echo "<td>" . htmlspecialchars($row['nome_classic']) . "</td>";
        echo "<td>" . htmlspecialchars($row['primeira'])     . "</td>";
        echo "<td>" . htmlspecialchars($row['segunda'])      . "</td>";
        echo "<td>" . htmlspecialchars($row['terceira'])     . "</td>";
        echo "<td>" . htmlspecialchars($row['quarta'])       . "</td>";
        echo "<td>" . htmlspecialchars($row['nome_adm'])     . "</td>";
      echo "</tr>";
    }
    echo "</tbody>
      </table>";
  }
  // Exibe a tabela basic 
  if ($acesso === 'admin') {
    $basic = $conn->query($sqlB);
    if (!$basic) {
      die("Query failed: " . $conn->error);
    }
    echo "      <table>
        <thead>
          <tr>
            <th>Nome</th>
            <th>1ª</th>
            <th>2ª</th>
            <th>3ª</th>
            <th>4ª</th>
            <th>Adiministradora</th>
          </tr>
        </thead>
        <tbody>";
    while ($row = $basic->fetch_assoc()) {
      echo "<tr>";
        echo "<td>" . htmlspecialchars($row['nome_brasic']) . "</td>";
        echo "<td>" . htmlspecialchars($row['primeira'])     . "</td>";
        echo "<td>" . htmlspecialchars($row['segunda'])      . "</td>";
        echo "<td>" . htmlspecialchars($row['terceira'])     . "</td>";
        echo "<td>" . htmlspecialchars($row['quarta'])       . "</td>";
        echo "<td>" . htmlspecialchars($row['nome_adm'])     . "</td>";
      echo "</tr>";
    }
    echo "</tbody>
      </table>";
  }

  $conn->close();
?>
