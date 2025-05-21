<?php
   $conn = mysqli_connect("localhost", "root", "", "bcc");
   if (!$conn) {
     die("Connection failed: " . mysqli_connect_error());
   }
   $list = mysqli_query($conn, "SELECT nome, ativo, nivel FROM cad_fun");
   if (!$list) {
     die("Query failed: " . mysqli_error($conn));
   }
    if($acesso == 'admin'){
      while ($row = mysqli_fetch_assoc($list)) {
        echo "<tr>";
        echo "<td>" . $row['nome'] . "</td>";
        echo "<td>" . $row['ativo'] . "</td>";
        echo "<td>" . $row['nivel'] . "</td>";
        echo "</tr>";
      }
    }
?>