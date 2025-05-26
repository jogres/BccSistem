<?php
include('../../_php/_login/logado.php');
if ($acesso !== 'admin') exit('Acesso negado.');
require_once __DIR__ . '/../../config/db.php';

// 1) Detecta edição pela flag 'editing'
$editing = isset($_POST['editing']) && $_POST['editing'] === '1';

// 2) Coleta dados
$idFun      = isset($_POST['idFun']) && is_numeric($_POST['idFun']) ? (int) $_POST['idFun'] : null;
$nome       = trim($_POST['nome']     ?? '');
$endereco   = trim($_POST['endereco'] ?? '');
$telefone   = trim($_POST['numero']   ?? '');
$dataN      = $_POST['dataN']         ?? '';
$cpf        = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$email      = trim($_POST['email']    ?? '');
$senha      = $_POST['senha']         ?? '';
$acessoNovo = $_POST['acesso']        ?? 'user';
$ativo      = $_POST['ativo']         ?? 'Sim';

// 3) Validações básicas (omitir por brevidade)

try {
    if ($editing) {
        // === UPDATE ===
        $params = [
            $nome, $endereco, $telefone, $dataN,
            $cpf, $email, $acessoNovo, $ativo
        ];
        $sql = "UPDATE cad_fun SET
                    nome     = ?,
                    endereco = ?,
                    numero   = ?,
                    dataN    = ?,
                    cpf      = ?,
                    email    = ?,
                    acesso   = ?,
                    ativo    = ?";
        if ($senha !== '') {
            $sql .= ", senha = ?";
            $params[] = password_hash($senha, PASSWORD_BCRYPT);
        }
        $sql .= " WHERE idFun = ?";
        $params[] = $idFun;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $_SESSION['success'] = 'Funcionário atualizado com sucesso.';
    } else {
        // === INSERT ===
        $hash         = password_hash($senha, PASSWORD_BCRYPT);
        $nivelInicial = 'basic';
        $stmt = $pdo->prepare(
            "INSERT INTO cad_fun
              (idFun, nome, endereco, numero, dataN, cpf, email, senha, acesso, ativo, nivel)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $idFun,
            $nome,
            $endereco,
            $telefone,
            $dataN,
            $cpf,
            $email,
            $hash,
            $acessoNovo,
            $ativo,
            $nivelInicial
        ]);
        $_SESSION['success'] = 'Funcionário cadastrado com sucesso.';
    }

    header('Location: ../../_html/_lista/listaFun.php');
    exit;
} catch (PDOException $e) {
    $_SESSION['error'] = 'Erro ao salvar funcionário: ' . $e->getMessage();
    header('Location: ../../_html/_cadastro/cadFun.php' . ($editing ? "?idFun={$idFun}" : ''));
    exit;
}
?>
