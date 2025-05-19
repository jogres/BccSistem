<?php 
  $conn = mysqli_connect("localhost", "root", "", "bcc");
  if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
  }
  $nivel = $_POST['select-niveis'];
  $nome = $_POST['nome'];
  $primeira = $_POST['primeira'];
  $segunda = $_POST['segunda'];
  $terceira = $_POST['terceira'];
  $quarta = $_POST['quarta'];
  $selectAdm = $_POST['select-adm'];
  mysqli_query($conn, "INSERT INTO $nivel (primeira, segunda, terceira, quarta, nome, idAdm) VALUES ('$primeira', '$segunda', '$terceira', '$quarta', '$nome','$selectAdm')");
  if (mysqli_affected_rows($conn) > 0) {
    echo "<script>alert('Cadastro realizado com sucesso!');</script>";
  } else {
    echo "<script>alert('Erro ao cadastrar!');</script>";
  }
  mysqli_close($conn);
  header('Location: ../../_html/_cadastro/cadNivel.php');
  exit;
?>