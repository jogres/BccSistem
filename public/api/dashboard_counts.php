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
    
    // Filtros avançados
    $minClients = (int)($_GET['minClients'] ?? 0);
    $sortBy = $_GET['sortBy'] ?? 'name';
    $sortBy = in_array($sortBy, ['name', 'total', 'average'], true) ? $sortBy : 'name';

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
    $userIds = array_map('intval', (array)($_GET['users'] ?? [])); // quando "Comparar" estiver ligado

    // Se não houver usuários selecionados, aplicar lógica padrão
    if (empty($userIds)) {
        if ($isAdmin) {
            // Admin sem seleção específica: buscar todos os usuários ativos
            require_once __DIR__ . '/../../app/models/Funcionario.php';
            $userIds = Funcionario::allActiveIds();
        } else {
            // Usuário normal: apenas ele mesmo
            $userIds = [$user['id']];
        }
    }

    // Se ainda não houver nenhum usuário, devolve vazio coerente
    if (empty($userIds)) {
        echo json_encode([
            'ok' => true,
            'mode' => $mode,
            'start' => $start,
            'end' => $end,
            'labels' => [],
            'series' => new stdClass(),
            'stats' => [
                'total_clients' => 0,
                'total_users' => 0,
                'average_per_user' => 0,
                'top_performer' => null,
                'period_label' => $mode === 'week' ? 'semana' : ($mode === 'month' ? 'mês' : 'dia'),
                'date_range' => [
                    'start_formatted' => date('d/m/Y', strtotime($start)),
                    'end_formatted' => date('d/m/Y', strtotime($end)),
                    'duration_days' => (strtotime($end) - strtotime($start)) / (60 * 60 * 24) + 1
                ]
            ]
        ]);
        exit;
    }

    // -------- Label por modo --------
    switch ($mode) {
        case 'week':
            // ISO week: formato 'YYYY-Wxx' (ex: 2025-W01)
            // Usando DATE_FORMAT para consistência com Dashboard.php
            $labelExpr = "DATE_FORMAT(c.created_at, '%x-W%v')";
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
    $userTotals = []; // Para ordenação
    
    foreach ($activeUserIds as $uid) {
        $uid  = (int)$uid;
        $data = [];
        foreach ($labels as $lab) {
            $data[] = (int)($byUserByLabel[$uid][$lab] ?? 0);
        }
        
        $total = array_sum($data);
        
        // Aplicar filtro de mínimo de clientes
        if ($minClients > 0 && $total < $minClients) continue;
        
        // (Segurança extra: se por algum motivo ficar tudo 0, pula)
        if ($total === 0) continue;

        $userTotals[$uid] = $total;
        $series[(string)$uid] = [
            'name' => $names[$uid] ?? ('ID '.$uid),
            'data' => $data,
            'total' => $total,
            'average' => count($labels) > 0 ? round($total / count($labels), 2) : 0,
        ];
    }

    // -------- Ordenação das séries --------
    if (!empty($series) && $sortBy !== 'name') {
        $sortedSeries = [];
        
        // Criar array para ordenação
        $sortData = [];
        foreach ($series as $uid => $seriesData) {
            $sortValue = match($sortBy) {
                'total' => $seriesData['total'],
                'average' => $seriesData['average'],
                default => $seriesData['total']
            };
            $sortData[$uid] = $sortValue;
        }
        
        // Ordenar por valor (decrescente)
        arsort($sortData);
        
        // Reconstruir séries na ordem correta
        foreach (array_keys($sortData) as $uid) {
            $sortedSeries[$uid] = $series[$uid];
        }
        
        $series = $sortedSeries;
    }

    // -------- Estatísticas adicionais --------
    $totalClients = array_sum(array_column($series, 'total'));
    $totalUsers = count($series);
    $averagePerUser = $totalUsers > 0 ? round($totalClients / $totalUsers, 2) : 0;
    
    // Top performer
    $topPerformer = null;
    if (!empty($series)) {
        $maxTotal = max(array_column($series, 'total'));
        foreach ($series as $uid => $data) {
            if ($data['total'] === $maxTotal) {
                $topPerformer = ['id' => $uid, 'name' => $data['name'], 'total' => $data['total']];
                break;
            }
        }
    }

    echo json_encode([
        'ok'     => true,
        'mode'   => $mode,
        'start'  => $start,
        'end'    => $end,
        'labels' => $labels,
        'series' => !empty($series) ? $series : new stdClass(),
        'stats'  => [
            'total_clients' => $totalClients,
            'total_users'   => $totalUsers,
            'average_per_user' => $averagePerUser,
            'top_performer' => $topPerformer,
            'period_label' => $mode === 'week' ? 'semana' : ($mode === 'month' ? 'mês' : 'dia'),
            'date_range' => [
                'start_formatted' => date('d/m/Y', strtotime($start)),
                'end_formatted' => date('d/m/Y', strtotime($end)),
                'duration_days' => (strtotime($end) - strtotime($start)) / (60 * 60 * 24) + 1
            ]
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
