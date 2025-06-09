<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

if (!in_array($acesso, ['admin','user','vendedor'])) {
    exit('Acesso negado.');
}

$idCli     = isset($_POST['idCli']) ? (int) $_POST['idCli'] : null;
$nome      = trim($_POST['nome'] ?? '');
$cpf       = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$endereco  = trim(
    ($_POST['rua'] ?? '') . ', ' .
    ($_POST['numero'] ?? '') . ', ' .
    ($_POST['bairro'] ?? '') . ', ' .
    ($_POST['cidade'] ?? '') . ', ' .
    ($_POST['estado'] ?? '') . ', ' .
    ($_POST['cep'] ?? '')
);
$telefone  = trim($_POST['telefone'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

// Verifica se é venda
$vendaSim = ($_SESSION['venda'] ?? '') === 'Sim';
$tipo     = $vendaSim ? 'com_venda' : 'sem_venda';

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

try {
    $pdo->beginTransaction();

    if ($idCli) {
        // Atualiza cliente
        $stmt = $pdo->prepare(
            "UPDATE cad_cli SET nome=?, cpf=?, endereco=?, telefone=?, tipo=?, descricao=? WHERE idCli=?"
        );
        $stmt->execute([$nome, $cpf, $endereco, $telefone, $tipo, $descricao, $idCli]);

        if ($vendaSim) {
            // Captura dados da venda
            $idAdm       = (int)($_POST['select-adm'] ?? 0);
            $fun_ids     = $_POST['select_fun'] ?? [];
            $numContrato = (int)($_POST['idVenda'] ?? 0);
            $tipoVenda   = $_POST['select_tipo'] ?? 'Normal';
            $valorTot    = (float)($_POST['valor'] ?? 0);
            $dataV       = $_POST['data'] ?? date('Y-m-d');

            // Valida dados
            if (!$idAdm || !$numContrato || $valorTot <= 0 || empty($fun_ids)) {
                throw new Exception('Dados da venda incompletos ou inválidos.');
            }
            $dt = DateTime::createFromFormat('Y-m-d', $dataV);
            if (!$dt || $dt > new DateTime()) {
                throw new Exception('Data da venda inválida ou futura.');
            }



            $firstFun = (int)$fun_ids[0];

            // Verifica venda existente
            $stmt = $pdo->prepare(
                "SELECT v.id FROM venda v JOIN venda_cli vc ON vc.idVenda=v.id WHERE vc.idCli=? LIMIT 1"
            );
            $stmt->execute([$idCli]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Atualiza venda
                $vendaPk = (int)$existing['id'];
                $stmt = $pdo->prepare(
                    "UPDATE venda SET idVenda=?, tipo=?, valor=?, dataV=?, idAdm=? WHERE id=?"
                );
                $stmt->execute([$numContrato, $tipoVenda, $valorTot, $dataV, $idAdm, $vendaPk]);

                // Atualiza vínculos
                $pdo->prepare("DELETE FROM venda_fun WHERE idVenda=?")->execute([$vendaPk]);
                $stmt = $pdo->prepare("INSERT INTO venda_fun (idFun, idVenda) VALUES (?, ?)");
                foreach ($fun_ids as $f) {
                    $stmt->execute([(int)$f, $vendaPk]);
                }
            } else {
                // Insere venda
                $stmt = $pdo->prepare(
                    "INSERT INTO venda (idVenda, tipo, valor, dataV, idAdm) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$numContrato, $tipoVenda, $valorTot, $dataV, $idAdm]);
                $vendaPk = $pdo->lastInsertId();

                // Insere vínculos
                $pdo->prepare("INSERT INTO venda_cli (idCli, idVenda) VALUES (?, ?)")
                    ->execute([$idCli, $vendaPk]);
                $stmt = $pdo->prepare("INSERT INTO venda_fun (idFun, idVenda) VALUES (?, ?)");
                foreach ($fun_ids as $f) {
                    $stmt->execute([(int)$f, $vendaPk]);
                }

                // Lógica de notificações… (permanece igual)
                $skipList = [/* IDs especiais */];
                $interval = in_array($idAdm, $skipList) ? 2 : 1;
                $dtBase   = new DateTime($dataV);
                $link     = "../../_html/_detalhes/detalhesVenda.php?idVenda=$vendaPk";
                $admins   = $pdo->query("SELECT idFun FROM cad_fun WHERE acesso='admin'")
                                   ->fetchAll(PDO::FETCH_COLUMN);
                $nomeFun  = $pdo->query("SELECT nome FROM cad_fun WHERE idFun=$firstFun")->fetchColumn() ?: 'Funcionário';
                $stmtNotif = $pdo->prepare(
                    "INSERT INTO notificacoes (idFun, mensagem, link, lida, data_criacao, idVenda, parcela) VALUES (?, ?, ?, 0, ?, ?, ?)"
                );
                foreach ($admins as $admId) {
                    for ($p=1; $p<=4; $p++) {
                        $dtNot = (clone $dtBase)->add(new DateInterval('P'.(($p-1)*$interval).'M'));
                        $stmtNotif->execute([
                            $admId,
                            "Contrato $numContrato da venda no valor de R$ $valorTot realizada por $nomeFun",
                            $link,
                            $dtNot->format('Y-m-d H:i:s'),
                            $vendaPk,
                            $p
                        ]);
                    }
                }
            }

            // Atualiza idFun no cliente
            $pdo->prepare("UPDATE cad_cli SET idFun=? WHERE idCli=?")
                ->execute([$firstFun, $idCli]);
        }
    } else {
        // Inserção de cliente
        if ($vendaSim) {
            $idAdm       = (int)($_POST['select-adm'] ?? 0);
            $fun_ids     = $_POST['select_fun'] ?? [];
            $numContrato = (int)($_POST['idVenda'] ?? 0);
            $tipoVenda   = $_POST['select_tipo'] ?? 'Normal';
            $valorTot    = (float)($_POST['valor'] ?? 0);
            $dataV       = $_POST['data'] ?? date('Y-m-d');
            if (!$idAdm || !$numContrato || $valorTot<=0 || empty($fun_ids)) {
                throw new Exception('Dados da venda incompletos ou inválidos.');
            }
            $dt = DateTime::createFromFormat('Y-m-d',$dataV);
            if(!$dt||$dt>new DateTime()) throw new Exception('Data inválida ou futura.');


            $firstFun = (int)$fun_ids[0];
            $stmtCli = $pdo->prepare(
                "INSERT INTO cad_cli (nome,cpf,idFun,endereco,telefone,tipo,descricao) VALUES(?,?,?,?,?,?,?)"
            );
            $stmtCli->execute([$nome,$cpf,$firstFun,$endereco,$telefone,'com_venda',$descricao]);
            $idCli = $pdo->lastInsertId();

            $stmtVenda = $pdo->prepare(
                "INSERT INTO venda (idVenda,tipo,valor,dataV,idAdm) VALUES(?,?,?,?,?)"
            );
            $stmtVenda->execute([$numContrato,$tipoVenda,$valorTot,$dataV,$idAdm]);
            $vendaPk=$pdo->lastInsertId();

            $stmtVF=$pdo->prepare("INSERT INTO venda_fun(idFun,idVenda) VALUES(?,?)");
            $stmtVC=$pdo->prepare("INSERT INTO venda_cli(idCli,idVenda) VALUES(?,?)");
            foreach($fun_ids as $f){
                $stmtVF->execute([(int)$f,$vendaPk]);
                if((int)$f=== $firstFun) $stmtVC->execute([$idCli,$vendaPk]);
            }
            // Notificações idem acima
        } else {
            $stmtCli=$pdo->prepare(
                "INSERT INTO cad_cli(nome,cpf,idFun,endereco,telefone,tipo,descricao) VALUES(?,?,?,?,?,?,?)"
            );
            $stmtCli->execute([$nome,$cpf,$_SESSION['user_id'],$endereco,$telefone,'sem_venda',$descricao]);
            $idCli=$pdo->lastInsertId();
        }
    }

    $pdo->commit();
    unset($_SESSION['venda']);
    header('Location: ../../_html/_lista/listaCli.php');
    exit;
} catch(Exception $e) {
    $pdo->rollBack();
    exit('Erro ao salvar cliente: '.htmlspecialchars($e->getMessage()));
}
?>