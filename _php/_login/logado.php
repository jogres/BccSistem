<?php
  session_start();
  $permis = array(
    'admin'=> array(
      '../_lista/listaFun.php'=>'Funcionários',
      '../_lista/listaVenda.php'=>'Vendas',
      '../_lista/listaAdm.php'=>'Adiministradoras',
      '../_lista/listaCli.php'=>'Clientes',
      '../_lista/listaNivel.php'=>'Niveis',
      '../_dashboard/dashboard.php'=>'Dashboard'
    ),
    'user'=> array(
      '.._html/_lista/listaCli.php'=>'Clientes',
      '.._html/_dashboard/dashboard.php'=>'Dashboard'
    ),
  );

  if(!isset($_SESSION['acesso'])){
    header('Location: ../../_html/_login/index.php');
    exit;
  }
  $nivel = $_SESSION['nivel'];
  $acesso = $_SESSION['acesso'];
  $nomeP = $_SESSION['user_name'];
  $idP = $_SESSION['user_id'];
  $menu = $permis[$acesso];
?>