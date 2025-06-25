<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

if (!in_array($acesso, ['admin','user','vendedor'], true)) {
    exit('Acesso negado.');
}

$idCli     = isset($_POST['idCli'])    ? (int)$_POST['idCli'] : null;
$nome      = trim($_POST['nome']       ?? '');
$cpf       = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$telefone  = trim($_POST['telefone']   ?? '');
$descricao = trim($_POST['descricao']  ?? '');

// Verifica se é venda
$vendaSim  = ($_SESSION['venda'] ?? '') === 'Sim';
$tipo      = $vendaSim ? 'com_venda' : 'sem_venda';

// Novo: captura segmento
$segmento  = trim($_POST['segmento']   ?? '');

// ENDEREÇO: se veio do textarea (edição), usa ele; caso contrário concatena
if (isset($_POST['endereco']) && trim($_POST['endereco']) !== '') {
    $endereco = trim($_POST['endereco']);
} else {
    $endereco = trim(
        ($_POST['rua']     ?? '') . ', ' .
        ($_POST['numero']  ?? '') . ', ' .
        ($_POST['bairro']  ?? '') . ', ' .
        ($_POST['cidade']  ?? '') . ', ' .
        ($_POST['estado']  ?? '') . ', ' .
        ($_POST['cep']     ?? '')
    );
}

// Validações
if ($nome === '' || $cpf === '' || $endereco === '' || $telefone === '') {
    exit('Erro: preencha todos os dados do cliente.');
}
if (!preg_match('/^\d{11}$/', $cpf)) {
    exit('Erro: CPF inválido.');
}
if (!preg_match('/^[0-9\s\-\(\)]+$/', $telefone)) {
    exit('Erro: Telefone inválido.');
}
if ($vendaSim && $segmento === '') {
    exit('Erro: selecione o segmento da venda.');
}

try {
    $pdo->beginTransaction();

    // 1) Inserir ou atualizar cliente
    if ($idCli) {
        // Atualiza cliente
        $stmt = $pdo->prepare(
            "UPDATE cad_cli
                SET nome      = ?,
                    cpf       = ?,
                    endereco  = ?,
                    telefone  = ?,
                    tipo      = ?,
                    descricao = ?
              WHERE idCli = ?"
        );
        $stmt->execute([$nome, $cpf, $endereco, $telefone, $tipo, $descricao, $idCli]);
    } else {
        // Insere novo cliente
        $stmt = $pdo->prepare(
            "INSERT INTO cad_cli
                (nome, cpf, idFun, endereco, telefone, tipo, descricao)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $nome,
            $cpf,
            $_SESSION['user_id'], // idFun inicial
            $endereco,
            $telefone,
            $tipo,
            $descricao
        ]);
        $idCli = $pdo->lastInsertId();
    }

    // 2) Se for venda, insere/atualiza `venda`
    if ($vendaSim) {
        $idAdm       = (int)($_POST['select-adm']  ?? 0);
        $fun_ids     = $_POST['select_fun']       ?? [];
        $numContrato = (int)($_POST['idVenda']    ?? 0);
        $tipoVenda   = $_POST['select_tipo']      ?? 'Normal';
        $valorTot    = (float)($_POST['valor']    ?? 0);
        $dataV       = $_POST['data']             ?? date('Y-m-d');

        if (!$idAdm || !$numContrato || $valorTot <= 0 || empty($fun_ids)) {
            throw new Exception('Dados da venda incompletos ou inválidos.');
        }
        $dt = DateTime::createFromFormat('Y-m-d', $dataV);
        if (!$dt || $dt > new DateTime()) {
            throw new Exception('Data da venda inválida ou futura.');
        }

        // Verifica se já existe venda vinculada a este cliente
        $stmt = $pdo->prepare(
            "SELECT v.id
               FROM venda v
               JOIN venda_cli vc ON vc.idVenda = v.id
              WHERE vc.idCli = ?
              LIMIT 1"
        );
        $stmt->execute([$idCli]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Atualiza venda existente (incluindo segmento)
            $vendaPk = (int)$existing['id'];
            $stmt = $pdo->prepare(
                "UPDATE venda
                    SET idVenda  = ?,
                        tipo     = ?,
                        valor    = ?,
                        dataV    = ?,
                        idAdm    = ?,
                        segmento = ?
                  WHERE id = ?"
            );
            $stmt->execute([
                $numContrato,
                $tipoVenda,
                $valorTot,
                $dataV,
                $idAdm,
                $segmento,
                $vendaPk
            ]);

            // Remove vínculos antigos de funcionários
            $pdo->prepare("DELETE FROM venda_fun WHERE idVenda = ?")
                ->execute([$vendaPk]);
        } else {
            // Insere nova venda (incluindo segmento)
            $stmt = $pdo->prepare(
                "INSERT INTO venda
                    (idVenda, tipo, valor, dataV, idAdm, segmento)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $numContrato,
                $tipoVenda,
                $valorTot,
                $dataV,
                $idAdm,
                $segmento
            ]);
            $vendaPk = $pdo->lastInsertId();

            // Vincula cliente à venda
            $pdo->prepare(
                "INSERT INTO venda_cli (idCli, idVenda) VALUES (?, ?)"
            )->execute([$idCli, $vendaPk]);
        }

        // 3) Vincula todos os funcionários
        $stmtFun = $pdo->prepare("INSERT INTO venda_fun (idFun, idVenda) VALUES (?, ?)");
        foreach ($fun_ids as $f) {
            $stmtFun->execute([(int)$f, $vendaPk]);
        }

        // 4) Gera notificações para as 4 parcelas (sem multiplicar por admins)
        $skipList = [/* ids que saltam 2 meses */];
        $interval = in_array($idAdm, $skipList, true) ? 2 : 1;
        $dtBase   = new DateTime($dataV);
        $link     = "../../_html/_detalhes/detalhesVenda.php?idVenda=$vendaPk";

        // Pega apenas UM administrador para receber as notificações
        $admId = $pdo
            ->query("SELECT idFun FROM cad_fun WHERE acesso = 'admin' LIMIT 1")
            ->fetchColumn();

        // Nome do primeiro funcionário
        $stmtF0 = $pdo->prepare("SELECT nome FROM cad_fun WHERE idFun = ?");
        $stmtF0->execute([$fun_ids[0]]);
        $nomeFun = $stmtF0->fetchColumn() ?: 'Funcionário';

        $stmtNotif = $pdo->prepare(
            "INSERT INTO notificacoes
                (idFun, mensagem, link, lida, data_criacao, idVenda, parcela)
             VALUES (?, ?, ?, 0, ?, ?, ?)"
        );
        for ($p = 1; $p <= 4; $p++) {
            $dtNot = (clone $dtBase)->add(
                new DateInterval('P' . (($p - 1) * $interval) . 'M')
            );
            // Insere apenas UMA notificação por parcela
            $stmtNotif->execute([
                $admId,
                "Pagamento — Contrato $numContrato (R$ " .
                    number_format($valorTot, 2, ',', '.') .
                    ") — $nomeFun",
                $link,
                $dtNot->format('Y-m-d H:i:s'),
                $vendaPk,
                $p
            ]);
        }
    }

    // 5) Atualiza idFun principal no cliente
    if ($vendaSim && !empty($fun_ids)) {
        $stmt = $pdo->prepare(
            "UPDATE cad_cli SET idFun = ? WHERE idCli = ?"
        );
        $stmt->execute([(int)$fun_ids[0], $idCli]);
    }

    $pdo->commit();
    unset($_SESSION['venda']);
    header('Location: ../../_html/_lista/listaCli.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    exit('Erro ao salvar cliente/venda: ' . htmlspecialchars($e->getMessage()));
}
?>
