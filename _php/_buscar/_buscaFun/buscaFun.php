<?php
require_once __DIR__ . '/../../../config/db.php';
$result = $pdo->query("SELECT idFun, nome FROM cad_fun WHERE ativo = 'Sim' ORDER BY nome");
if ($result) {
    $funs = $result->fetchAll();
    if (!$funs) {
        echo "<option value=''>Nenhum funcionário encontrado</option>";
    } else {
        foreach ($funs as $fun) {
            $id = $fun['idFun'];
            $nomeFun = htmlspecialchars($fun['nome'], ENT_QUOTES);
            echo "<option value='$id'>$nomeFun</option>";
        }
    }
} else {
    echo "<option value=''>Erro ao buscar funcionários</option>";
}
