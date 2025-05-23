<?php
include('../../_php/_login/logado.php');
if ($acesso !== 'admin') { exit('Acesso negado.'); }

require_once __DIR__ . '/../../config/db.php';

// 1) Detecta edição pela flag hidden 'editing'
$editing = isset($_POST['editing']) && $_POST['editing'] === '1';

// 2) Coleta dados do formulário
// idFun é obrigatório no cadastro e enviado oculto na edição
$idFun    = isset($_POST['idFun']) && is_numeric($_POST['idFun']) 
            ? (int) $_POST['idFun'] 
            : null;

$nome     = trim($_POST['nome']     ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$telefone = trim($_POST['numero']   ?? '');  // 'numero' = telefone
$dataN    = $_POST['dataN']         ?? '';
$cpf      = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$email    = trim($_POST['email']    ?? '');
$senha    = $_POST['senha']         ?? '';
$acessoNovo= $_POST['acesso']       ?? 'user';
$ativo    = $_POST['ativo']         ?? 'Sim';

// 3) Validações básicas
if ($nome === '' || $endereco === '' || $telefone === '' ||
    $dataN === '' || $cpf === '' || $email === '' ||
    (!$editing && $idFun === null) || (!$editing && $senha === '')) {
    $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
    header('Location: ../../_html/_cadastro/cadFun.php' . ($editing ? "?idFun={$idFun}" : ''));
    exit;
}

// CPF 11 dígitos
if (!preg_match('/^\d{11}$/', $cpf)) {
    $_SESSION['error'] = 'CPF inválido. Use apenas dígitos.';
    header('Location: ../../_html/_cadastro/cadFun.php' . ($editing ? "?idFun={$idFun}" : ''));
    exit;
}

// Data de nascimento válida e no passado
$dob = DateTime::createFromFormat('Y-m-d', $dataN);
if (!$dob || $dob > new DateTime()) {
    $_SESSION['error'] = 'Data de nascimento inválida.';
    header('Location: ../../_html/_cadastro/cadFun.php' . ($editing ? "?idFun={$idFun}" : ''));
    exit;
}

// E-mail válido
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'E-mail em formato inválido.';
    header('Location: ../../_html/_cadastro/cadFun.php' . ($editing ? "?idFun={$idFun}" : ''));
    exit;
}

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
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $nivelInicial = 'basic';
        $stmt = $pdo->prepare("
            INSERT INTO cad_fun
              (idFun, nome, endereco, numero, dataN, cpf, email, senha, acesso, ativo, nivel)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $idFun,          // matrícula obrigatória
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