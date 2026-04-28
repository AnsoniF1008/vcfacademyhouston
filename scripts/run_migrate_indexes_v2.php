<?php
/**
 * Add round-2 performance indexes (MOTM, roster, scorers, hero).
 * Run once from CLI:   php scripts/run_migrate_indexes_v2.php
 * Or open in browser:  /scripts/run_migrate_indexes_v2.php  (only on localhost)
 */
declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$isCli = php_sapi_name() === 'cli';
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = in_array($host, ['localhost', '127.0.0.1'], true);

if (!$isCli && !$isLocal) {
    http_response_code(403);
    exit('Forbidden');
}

$queries = [
    "ALTER TABLE juego_goles ADD INDEX idx_juego_goles_juego (juego_id)",
    "ALTER TABLE juego_goles ADD INDEX idx_juego_goles_roster (roster_id)",
    "ALTER TABLE motm_votaciones ADD INDEX idx_motm_votaciones_status_ends (status, ends_at)",
    "ALTER TABLE motm_votes ADD INDEX idx_motm_votes_votacion (votacion_id)",
    "ALTER TABLE motm_votes ADD INDEX idx_motm_votes_votacion_nominee (votacion_id, nominee_id)",
    "ALTER TABLE motm_votes ADD INDEX idx_motm_votes_votacion_ip (votacion_id, ip_address)",
    "ALTER TABLE motm_nominees ADD INDEX idx_motm_nominees_votacion (votacion_id, orden)",
    "ALTER TABLE jugador_mes ADD INDEX idx_jugador_mes_created (created_at)",
    "ALTER TABLE hero_slides ADD INDEX idx_hero_slides_active_order (activo, orden, id)",
    "ALTER TABLE roster ADD INDEX idx_roster_categoria_activo (categoria_id, activo)",
];

$results = [];
foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['sql' => $sql, 'ok' => true, 'msg' => 'Index created'];
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        $ok = str_contains($msg, '1061')
            || str_contains($msg, 'Duplicate key name')
            || str_contains($msg, "doesn't exist")
            || str_contains($msg, '1146'); // table missing -> not fatal
        $results[] = [
            'sql' => $sql,
            'ok'  => $ok,
            'msg' => $ok ? (str_contains($msg, '1146') ? 'Table missing (skipped)' : 'Already exists (OK)') : $msg,
        ];
    }
}

if ($isCli) {
    foreach ($results as $r) {
        echo ($r['ok'] ? '[OK] ' : '[ERR] ') . $r['msg'] . "\n  " . $r['sql'] . "\n";
    }
} else {
    echo '<pre style="font-family:monospace;background:#111;color:#eee;padding:20px;">';
    echo "VCF DB Index Migration v2\n=========================\n\n";
    foreach ($results as $r) {
        echo ($r['ok'] ? '[OK] ' : '[ERR] ') . htmlspecialchars($r['msg']) . "\n";
        echo '  ' . htmlspecialchars($r['sql']) . "\n\n";
    }
    echo '</pre>';
}
