<?php
include('../../_php/_login/logado.php');
if ($acesso !== 'admin') { exit('Acesso negado.'); }

require_once __DIR__ . '/../../config/db.php';

$nome     = trim($_POST['nome'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$telefone = trim($_POST['numero'] ?? '');   // 'numero' do POST é telefone
$dataN    = $_POST['dataN'] ?? '';
$cpf      = preg_replace('/\D/', '', $_POST['cpf'] ?? '');    // remove mascára, deixa só dígitos
$email    = trim($_POST['email'] ?? '');
$senha    = $_POST['senha'] ?? '';
$acessoNovo   = $_POST['acesso'] ?? 'user';
$ativo    = $_POST['ativo'] ?? 'Sim';
// Código opcional: se fornecido usa, senão deixa NULL para auto-incremento
$idFun    = $_POST['idFun'] !== '' ? (int)$_POST['idFun'] : NULL;

// Validações servidor
if ($nome === '' || $endereco === '' || $telefone === '' || $dataN === '' || 
    $cpf === '' || $email === '' || $senha === '') {
    $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
    header('Location: ../../_html/_cadastro/cadFun.php');
    exit;
}
// Formato de CPF básico (11 dígitos)
if (!preg_match('/^\d{11}$/', $cpf)) {
    $_SESSION['error'] = 'CPF inválido. Use apenas dígitos.';
    header('Location: ../../_html/_cadastro/cadFun.php');
    exit;
}
// Valida data de nascimento (exemplo: deve ser data válida e no passado)
$dob = DateTime::createFromFormat('Y-m-d', $dataN);
$now = new DateTime();
if (!$dob || $dob > $now) {
    $_SESSION['error'] = 'Data de nascimento inválida.';
    header('Location: ../../_html/_cadastro/cadFun.php');
    exit;
}
// Valida email no servidor
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'E-mail em formato inválido.';
    header('Location: ../../_html/_cadastro/cadFun.php');
    exit;
}

// Determina valor de $idFun para inserir (caso precise auto incremento manual)
if ($idFun === NULL) {
    // Deixar o SGBD atribuir auto_increment se disponível.
    // Alternativamente, poderíamos buscar maior idFun existente:
    // $idFun = $pdo->query("SELECT MAX(idFun)+1 FROM cad_fun")->fetchColumn();
    // Para este código, assumimos auto_increment no banco ou handle pelo SGBD.
}

try {
    // Prepara inserção do funcionário
    $stmt = $pdo->prepare("INSERT INTO cad_fun 
        (idFun, nome, endereco, numero, dataN, cpf, email, senha, acesso, ativo, nivel) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    // Criptografa senha usando password_hash
    $hash = password_hash($senha, PASSWORD_BCRYPT);
    // Por padrão, novo funcionário não tem nível de comissão definido ainda -> usar 'basic' ou valor default
    $nivelInicial = 'basic';
    $stmt->execute([$idFun, $nome, $endereco, $telefone, $dataN, $cpf, $email, $hash, $acessoNovo, $ativo, $nivelInicial]);
    header('Location: ../../_html/_cadastro/sucesso.php');
    exit;
} catch (PDOException $e) {
    // Verifica erros de duplicidade (CPF ou Email podem conflitar)
    if ($e->errorInfo[1] == 1062) {
        if (strpos($e->getMessage(), 'cpf') !== false) {
            $_SESSION['error'] = 'CPF já cadastrado.';
        } elseif (strpos($e->getMessage(), 'email') !== false) {
            $_SESSION['error'] = 'E-mail já cadastrado.';
        } else {
            $_SESSION['error'] = 'Código ID já existe, use outro.';
        }
    } else {
        $_SESSION['error'] = 'Erro ao cadastrar funcionário: ' . $e->getMessage();
    }
    header('Location: ../../_html/_cadastro/cadFun.php');
    exit;
}
?>
