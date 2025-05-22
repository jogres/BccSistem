<?php
session_start();
if (!isset($_SESSION['acesso'])) {
    header('Location: ../../_html/_login/index.php');
    exit;
}
// Podemos carregar a conexão aqui caso páginas subsequentes precisem realizar consultas
require_once __DIR__ . '/../../config/db.php'; 

// Dados do usuário logado
$nivel   = $_SESSION['nivel'];
$acesso  = $_SESSION['acesso'];
$nomeP   = $_SESSION['user_name'];
$idUser  = $_SESSION['user_id'];

// Definição de menus por perfil de acesso
$permis = [
    'admin' => [
        '../_lista/listaFun.php'   => 'Funcionários',
        '../_lista/listaVenda.php' => 'Vendas',
        '../_lista/listaAdm.php'   => 'Administradoras',
        '../_lista/listaCli.php'   => 'Clientes',
        '../_lista/listaNivel.php' => 'Níveis',
        '../_dashboard/dashboard.php' => 'Dashboard'
    ],
    'user' => [
        '../_lista/listaCli.php'   => 'Clientes',
        '../_dashboard/dashboard.php' => 'Dashboard'
    ],
    'vendedor' => [  // "vendedor" tratado igual a "user" aqui
        '../_lista/listaCli.php'   => 'Clientes',
        '../_dashboard/dashboard.php' => 'Dashboard'
    ]
];
$menu = $permis[$acesso] ?? [];

// Ajusta menu em páginas específicas para adicionar opção de cadastro relacionado
$currentPage = basename($_SERVER['PHP_SELF']);
if ($acesso === 'admin') {
    if (in_array($currentPage, ['listaFun.php','cadFun.php'])) {
        $menu = [
            '../_lista/listaFun.php' => 'Funcionários',
            '../_cadastro/cadFun.php'=> 'Cadastrar Funcionário',
            '../_dashboard/dashboard.php' => 'Dashboard'
        ];
    } elseif (in_array($currentPage, ['listaAdm.php','cadAdm.php'])) {
        $menu = [
            '../_lista/listaAdm.php' => 'Administradoras',
            '../_cadastro/cadAdm.php'=> 'Cadastrar Administradora',
            '../_dashboard/dashboard.php' => 'Dashboard'
        ];
    } elseif (in_array($currentPage, ['listaNivel.php','cadNivel.php'])) {
        $menu = [
            '../_lista/listaNivel.php' => 'Níveis',
            '../_cadastro/cadNivel.php'=> 'Cadastrar Nível',
            '../_dashboard/dashboard.php' => 'Dashboard'
        ];
    } elseif (in_array($currentPage, ['listaCli.php','cadCli.php'])) {
        $menu = [
            '../_lista/listaCli.php' => 'Clientes',
            '../_cadastro/cadCli.php'=> 'Cadastrar Cliente',
            '../_dashboard/dashboard.php' => 'Dashboard'
        ];
    }
}
?>
