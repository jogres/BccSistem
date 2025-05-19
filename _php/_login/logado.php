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
      '../_lista/listaCli.php'=>'Clientes',
      '../_dashboard/dashboard.php'=>'Dashboard'
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

  if($acesso === 'vendedor'){
    $menu = array(
      '../_lista/listaCli.php'=>'Clientes',
      '../_dashboard/dashboard.php'=>'Dashboard'
    );
  }
  if($acesso === 'admin' && (basename($_SERVER['PHP_SELF']) === 'listaFun.php' || basename($_SERVER['PHP_SELF']) === 'cadFun.php' )){
    $menu = array(
      '../_lista/listaFun.php'=>'Funcionários',
      '../_cadastro/cadFun.php'=>'Cadastrar Funcionário',
      '../_dashboard/dashboard.php'=>'Dashboard'
    );
  }
  if($acesso === 'admin' && (basename($_SERVER['PHP_SELF']) === 'listaAdm.php' || basename($_SERVER['PHP_SELF']) === 'cadAdm.php' )){
    $menu = array(
      '../_lista/listaAdm.php'=>'Adiministradoras',
      '../_cadastro/cadAdm.php'=>'Cadastrar Adiministradora',
      '../_dashboard/dashboard.php'=>'Dashboard'
    );
  }
  if($acesso === 'admin' && (basename($_SERVER['PHP_SELF']) === 'listaNivel.php' || basename($_SERVER['PHP_SELF']) === 'cadNivel.php' )){
    $menu = array(
      '../_lista/listaNivel.php'=>'Niveis',
      '../_cadastro/cadNivel.php'=>'Cadastrar Niveis',
      '../_dashboard/dashboard.php'=>'Dashboard'
    );
  }
  if($acesso === 'admin' && (basename($_SERVER['PHP_SELF']) === 'listaCli.php' || basename($_SERVER['PHP_SELF']) === 'cadCli.php' )){
    $menu = array(
      '../_lista/listaCli.php'=>'Clientes',
      '../_cadastro/cadCli.php'=>'Cadastrar Clientes',
      '../_dashboard/dashboard.php'=>'Dashboard'
    );
  }
?>