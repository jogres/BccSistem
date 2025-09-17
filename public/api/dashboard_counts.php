<?php
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/middleware/require_login.php';
require __DIR__ . '/../../app/models/Dashboard.php';

header('Content-Type: application/json; charset=utf-8');

$user = Auth::user();
$isAdmin = Auth::isAdmin();

$mode = $_GET['mode'] ?? 'week';

$today = new DateTimeImmutable('today');
if ($mode === 'day') {
    $d = $_GET['day'] ?? $today->format('Y-m-d');
    $start = $end = $d;
} elseif ($mode === 'month') {
    $month = $_GET['month'] ?? $today->format('Y-m');
    $first = DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01');
    $start = $first->format('Y-m-d');
    $end   = $first->modify('last day of this month')->format('Y-m-d');
} else { // week (intervalo livre - default semana atual)
    $start = $_GET['start'] ?? $today->modify('monday this week')->format('Y-m-d');
    $end   = $_GET['end']   ?? $today->modify('sunday this week')->format('Y-m-d');
}

$userIds = [];
if ($isAdmin && isset($_GET['users']) && is_array($_GET['users'])) {
    $userIds = array_values(array_filter(array_map('intval', $_GET['users']), fn($v)=>$v>0));
} else {
    $userIds = [$user['id']];
}

try {
    $data = Dashboard::aggregate($mode, $start, $end, $userIds);
    echo json_encode([
        'ok'      => true,
        'mode'    => $mode,
        'start'   => $start,
        'end'     => $end,
        'labels'  => $data['labels'],
        'series'  => $data['series'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
