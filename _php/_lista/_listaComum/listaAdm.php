<?php
  $conn = new mysqli("localhost", "root", "", "bcc");
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
  $list = mysqli_query($conn, "SELECT * FROM cad_adm");
  if (!$list) {
    die("Query failed: " . mysqli_error($conn));
  }
  if($acesso == 'admin'){
    while ($row = mysqli_fetch_assoc($list)) {
      echo "<tr>";
      echo "<td>" . $row['nome'] . "</td>";
      echo "<td>" . $row['cnpj'] . "</td>";
      echo "</tr>";
    }
  }
?>