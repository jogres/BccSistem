<?php
   $conn = mysqli_connect("localhost", "root", "", "bcc");
   if (!$conn) {
     die("Connection failed: " . mysqli_connect_error());
   }
    $nome = $_POST['nome'];
    $endereco = $_POST['endereco'];
    $numero = $_POST['numero'];
    $dataN = $_POST['dataN'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $senha = md5($_POST['senha']);
    $acesso = $_POST['acesso'];
    $ativo = $_POST['ativo'];
    $idFun = $_POST['idFun'];
    $sql = "INSERT INTO cad_fun (idFun, nome, endereco, numero, dataN, cpf, email, senha, acesso, ativo) VALUES ('$idFun', '$nome', '$endereco', '$numero', '$dataN', '$cpf', '$email', '$senha', '$acesso', '$ativo')";
    mysqli_query($conn, $sql);    
?>