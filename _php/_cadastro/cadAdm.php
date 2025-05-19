<?php
  $conn = mysqli_connect("localhost", "root", "", "bcc");
  if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
  }
  $nome = $_POST['nome'];
  $cnpj = $_POST['cnpj'];
  mysqli_query($conn, "INSERT INTO cad_adm (cnpj, nome) VALUES ('$cnpj', '$nome')");
  if (mysqli_affected_rows($conn) > 0) {
    echo "<script>alert('Cadastro realizado com sucesso!');</script>";
  } else {
    echo "<script>alert('Erro ao cadastrar!');</script>";
  }
  mysqli_close($conn);
  header('Location: ../../_html/_cadastro/cadAdm.php');
  exit;
?>