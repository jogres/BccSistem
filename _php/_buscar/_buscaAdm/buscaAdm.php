<?php
require_once __DIR__ . '/../../../config/db.php';
$result = $pdo->query("SELECT idAdm, nome FROM cad_adm ORDER BY nome");
if ($result) {
    $admins = $result->fetchAll();
    if (!$admins) {
        echo "<option value=''>Nenhuma administradora encontrada</option>";
    } else {
        foreach ($admins as $adm) {
            $id = $adm['idAdm'];
            $nomeAdm = htmlspecialchars($adm['nome'], ENT_QUOTES);
            echo "<option value='$id'>$nomeAdm</option>";
        }
    }
} else {
    echo "<option value=''>Erro ao buscar administradoras</option>";
}
