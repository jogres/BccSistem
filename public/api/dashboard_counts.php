<?php
declare(strict_types=1);

require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    Auth::startSessionSecure();
    if (!Auth::check()) {
        echo json_encode(['ok' => false, 'error' => 'unauthorized']);
        exit;
    }

    $pdo     = Database::getConnection();
    $user    = Auth::user();
    $isAdmin = Auth::isAdmin();

    // -------- Params --------
    $mode = $_GET['mode'] ?? 'month'; // 'week' | 'month' | 'day'
    $mode = in_array($mode, ['week','month','day'], true) ? $mode : 'month';

    // Intervalo de datas (start/end inclusivo)
    if ($mode === 'month') {
        $ym    = $_GET['month'] ?? date('Y-m');      // YYYY-MM (HTML <input type="month">)
        $start = $ym . '-01';
        $end   = date('Y-m-d', strtotime('last day of ' . $start));
    } elseif ($mode === 'week') {
        $start = $_GET['start'] ?? date('Y-m-d', strtotime('monday this week'));
        $end   = $_GET['end']   ?? date('Y-m-d', strtotime('sunday this week'));
    } else { // day
        $d     = $_GET['day'] ?? date('Y-m-d');      // YYYY-MM-DD
        $start = $d;
        $end   = $d;
    }

    // -------- Usuários (segurança/escopo) --------
    $userIds = array_map('intval', (array)($_GET['users'] ?? [])); // quando “Comparar” estiver ligado

    if ($isAdmin && empty($userIds)) {
        require_once __DIR__ . '/../../app/models/Funcionario.php';
        $userIds = Funcionario::allActiveIds(); // todos ativos
    }
    if (!$isAdmin) {
        $userIds = [$user['id']]; // só ele mesmo
    }

    // Se não houver nenhum usuário de entrada, devolve vazio coerente
    if (empty($userIds)) {
        echo json_encode([
            'ok' => true,
            'mode' => $mode,
            'start' => $start,
            'end' => $end,
            'labels' => [],
            'series' => new stdClass(),
        ]);
        exit;
    }

    // -------- Label por modo --------
    switch ($mode) {
        case 'week':
            // ISO week: YEARWEEK(dt,3) => 'YYYY-W##'
            $labelExpr = "CONCAT(YEARWEEK(c.created_at, 3) DIV 100, '-W', LPAD(YEARWEEK(c.created_at, 3) % 100, 2, '0'))";
            break;
        case 'day':
            $labelExpr = "DATE_FORMAT(c.created_at, '%Y-%m-%d')";
            break;
        case 'month':
        default:
            $labelExpr = "DATE_FORMAT(c.created_at, '%Y-%m')";
            break;
    }

    // -------- IN nomeado para usuários --------
    $params = [
        ':start' => $start . ' 00:00:00',
        ':end'   => $end   . ' 23:59:59',
    ];
    $in = [];
    foreach ($userIds as $i => $uid) {
        $k = ":u{$i}";
        $in[] = $k;
        $params[$k] = (int)$uid;
    }

    // -------- Query principal (contagem por label x usuário) --------
    $sql = "SELECT
                {$labelExpr} AS label,
                c.criado_por AS uid,
                COUNT(*)     AS total
            FROM clientes c
            WHERE c.deleted_at IS NULL
              AND c.created_at BETWEEN :start AND :end
              AND c.criado_por IN (" . implode(',', $in) . ")
            GROUP BY label, uid
            ORDER BY label ASC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    // -------- Monta labels e mapa uid->label->total --------
    $labels = [];
    $byUserByLabel = []; // [uid][label] = total
    foreach ($rows as $r) {
        $lab = $r['label'];
        $uid = (int)$r['uid'];
        $byUserByLabel[$uid][$lab] = (int)$r['total'];
        if (!in_array($lab, $labels, true)) $labels[] = $lab;
    }

    // === NOVO: só seguem adiante os usuários que tiveram ao menos 1 cadastro no período
    $activeUserIds = array_values(array_unique(array_map(
        static fn($r) => (int)$r['uid'],
        $rows
    )));

    if (empty($activeUserIds)) {
        echo json_encode([
            'ok' => true,
            'mode' => $mode,
            'start' => $start,
            'end' => $end,
            'labels' => [],
            'series' => new stdClass(), // ninguém para mostrar
        ]);
        exit;
    }

    // -------- Nomes dos usuários ativos no período --------
    $nameParams = [];
    $nameIn = [];
    foreach ($activeUserIds as $i => $uid) {
        $nk = ":n{$i}";
        $nameIn[] = $nk;
        $nameParams[$nk] = (int)$uid;
    }
    $names = [];
    $stN = $pdo->prepare("SELECT id, nome FROM funcionarios WHERE id IN (" . implode(',', $nameIn) . ")");
    $stN->execute($nameParams);
    foreach ($stN->fetchAll() as $row) {
        $names[(int)$row['id']] = $row['nome'];
    }

    // -------- Séries (somente usuários com atividade) --------
    $series = [];
    foreach ($activeUserIds as $uid) {
        $uid  = (int)$uid;
        $data = [];
        foreach ($labels as $lab) {
            $data[] = (int)($byUserByLabel[$uid][$lab] ?? 0);
        }
        // (Segurança extra: se por algum motivo ficar tudo 0, pula)
        if (array_sum($data) === 0) continue;

        $series[(string)$uid] = [
            'name' => $names[$uid] ?? ('ID '.$uid),
            'data' => $data,
        ];
    }

    echo json_encode([
        'ok'     => true,
        'mode'   => $mode,
        'start'  => $start,
        'end'    => $end,
        'labels' => $labels,
        'series' => !empty($series) ? $series : new stdClass(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
