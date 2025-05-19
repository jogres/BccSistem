<?php
  $conn = mysqli_connect("localhost", "root", "", "bcc");
  if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
  }
  $result = mysqli_query($conn, "SELECT idAdm, nome FROM cad_adm");
  if (!$result) {
    die("Query failed: " . mysqli_error($conn));
  }
  if($result->num_rows > 0){
    while ($row = $result->fetch_assoc()){
      echo "<option value='" . $row['idAdm'] . "'>" . $row['nome'] . "</option>";
    } 
  }else {
      echo "<option value=''>Nenhum administrador encontrado</option>";
  }
  $conn->close();
?>