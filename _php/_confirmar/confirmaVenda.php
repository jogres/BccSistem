<?php
include('../../_php/_login/logado.php');
require_once __DIR__ . '/../../config/db.php';

if (!in_array($acesso, ['admin'])) {
    exit('Acesso negado.');
}

if (!isset($_POST['idVenda']) || !is_numeric($_POST['idVenda'])) {
    exit('ID da venda inválido.');
}

$idVenda = (int) $_POST['idVenda'];

try {
    $pdo->beginTransaction();

    // Verifica se a venda já está confirmada
    $stmt = $pdo->prepare("SELECT confirmada FROM venda WHERE id = ?");
    $stmt->execute([$idVenda]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venda) {
        throw new Exception('Venda não encontrada.');
    }

    if ($venda['confirmada']) {
        throw new Exception('Venda já confirmada.');
    }

    // Atualiza o status da venda para confirmada
    $stmt = $pdo->prepare("UPDATE venda SET confirmada = 1 WHERE id = ?");
    $stmt->execute([$idVenda]);

    // Obtém os dados da venda
    $stmt = $pdo->prepare("SELECT tipo, valor, dataV, idAdm FROM venda WHERE id = ?");
    $stmt->execute([$idVenda]);
    $vendaDados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendaDados) {
        throw new Exception('Dados da venda não encontrados.');
    }

    $valorVenda = (float) $vendaDados['valor'];
    $dataVenda = $vendaDados['dataV'];
    $idAdm = (int) $vendaDados['idAdm'];
    $mesComissao = date('Y-m-01', strtotime($dataVenda));
    $tipo = $vendaDados['tipo'];

    // Obtém os IDs dos funcionários envolvidos na venda
    $stmt = $pdo->prepare("SELECT idFun FROM venda_fun WHERE idVenda = ?");
    $stmt->execute([$idVenda]);
    $funcionarios = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($funcionarios)) {
        throw new Exception('Nenhum funcionário associado à venda.');
    }

    foreach ($funcionarios as $idFun) {
        // Obtém o nível do funcionário
        $stmt = $pdo->prepare("SELECT UPPER(TRIM(nivel)) AS nivel FROM cad_fun WHERE idFun = ?");
        $stmt->execute([$idFun]);
        $nivel = $stmt->fetchColumn();

        if (!$nivel) {
            throw new Exception("Nível do funcionário ID $idFun não encontrado.");
        }

        $nivel = strtoupper(trim($nivel));

        // Inicializa as variáveis de comissão
        $primeira = $segunda = $terceira = $quarta = $totalComissao = 0.0;

        if (in_array($nivel, ['MASTER', 'CLASSIC', 'BASIC'])) {
            // Determina a tabela de comissão com base no nível
            $tabelaComissao = strtolower($nivel);

            // Obtém as porcentagens de comissão da tabela correspondente
            $stmt = $pdo->prepare("SELECT primeira, segunda, terceira, quarta FROM $tabelaComissao WHERE idAdm = ? AND nome = ? LIMIT 1");
            $stmt->execute([$idAdm, $tipo]);
            $percentuais = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$percentuais) {
                throw new Exception("Percentuais de comissão não encontrados para o nível '$nivel' e administradora ID $idAdm.");
            }

            // Calcula cada parcela da comissão
            $primeira = $valorVenda * ($percentuais['primeira'] / 100);
            $segunda = $valorVenda * ($percentuais['segunda'] / 100);
            $terceira = $valorVenda * ($percentuais['terceira'] / 100);
            $quarta = $valorVenda * ($percentuais['quarta'] / 100);
            $totalComissao = $primeira + $segunda + $terceira + $quarta;
        } else {
            // Nível 'APRENDIZ' ou outro desconhecido - comissões zeradas
            $primeira = $segunda = $terceira = $quarta = $totalComissao = 0.0;

        }

        // Insere o registro na tabela de comissões
        $stmt = $pdo->prepare("INSERT INTO comissao (idVenda, totalV, mesC, idFun, primeira, segunda, terceira, quarta, totalC) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$idVenda, $valorVenda, $mesComissao, $idFun, $primeira, $segunda, $terceira, $quarta, $totalComissao]);
    }

    $pdo->commit();
    header('Location: ../../_html/_detalhes/detalhesVenda.php?idVenda=' . $idVenda);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    exit('Erro ao confirmar venda: ' . $e->getMessage());
}
?>
