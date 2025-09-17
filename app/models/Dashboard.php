<?php
class Dashboard {
    /**
     * KPIs agregados por dia/semana/mês no intervalo [start,end].
     * @param string $granularity 'day' | 'week' | 'month'
     * @param string $start 'Y-m-d'
     * @param string $end   'Y-m-d'
     * @param array  $userIds lista de IDs a filtrar (vazio = todos)
     * @return array ['labels'=>[], 'series'=> [ userId => ['name'=>..., 'data'=>[...], 'total'=>int ] ] ]
     */
    public static function aggregate(string $granularity, string $start, string $end, array $userIds = []): array {
        $pdo = Database::getConnection();

        $granularity = in_array($granularity, ['day','week','month'], true) ? $granularity : 'week';

        $params = [
            ':start' => $start . ' 00:00:00',
            ':end'   => $end   . ' 23:59:59',
        ];

        $userFilter = '';
        if (!empty($userIds)) {
            $ph = [];
            foreach (array_values($userIds) as $i => $uid) {
                $k = ":u{$i}";
                $ph[] = $k;
                $params[$k] = (int)$uid;
            }
            $userFilter = ' AND c.criado_por IN (' . implode(',', $ph) . ') ';
        }

        // Bucket SQL por granularidade
        if ($granularity === 'day') {
            $bucketSel = "DATE(c.created_at)";
            $orderSel  = "DATE(c.created_at)";
        } elseif ($granularity === 'week') {
            // ISO week (segunda como primeiro dia e “primeira semana com 4+ dias”)
            // YEARWEEK(...,3) para ordenar e %x-W%v para rotular (ano ISO + semana)
            $bucketSel = "DATE_FORMAT(c.created_at, '%x-W%v')";
            $orderSel  = "YEARWEEK(c.created_at, 3)"; // estável para ordenação cronológica ISO. :contentReference[oaicite:1]{index=1}
        } else { // month
            $bucketSel = "DATE_FORMAT(c.created_at, '%Y-%m')";
            $orderSel  = "DATE_FORMAT(c.created_at, '%Y-%m')"; // agrupa por ano-mês. :contentReference[oaicite:2]{index=2}
        }

        $sql = "SELECT {$bucketSel} AS bucket, c.criado_por, COUNT(*) AS total, f.nome AS func_nome
                  FROM clientes c
                  JOIN funcionarios f ON f.id = c.criado_por
                 WHERE c.deleted_at IS NULL
                   AND c.created_at BETWEEN :start AND :end
                   {$userFilter}
              GROUP BY bucket, c.criado_por, f.nome
              ORDER BY {$orderSel} ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $labels = [];
        $series = []; // [uid => ['name'=>..., 'data'=>[bucket=>count], 'total'=>int]]
        while ($row = $stmt->fetch()) {
            $b = $row['bucket'];
            if (!in_array($b, $labels, true)) $labels[] = $b;
            $uid = (int)$row['criado_por'];
            if (!isset($series[$uid])) {
                $series[$uid] = ['name' => $row['func_nome'], 'data' => [], 'total' => 0];
            }
            $series[$uid]['data'][$b] = (int)$row['total'];
            $series[$uid]['total']   += (int)$row['total'];
        }
        // normaliza data points faltantes em 0 na ordem dos labels
        foreach ($series as $uid => $s) {
            $normalized = [];
            foreach ($labels as $lab) {
                $normalized[] = (int)($s['data'][$lab] ?? 0);
            }
            $series[$uid]['data'] = $normalized;
        }

        return ['labels' => $labels, 'series' => $series];
    }
}
