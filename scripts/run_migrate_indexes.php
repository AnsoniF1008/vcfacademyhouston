<?php
/**
 * Add performance indexes to juegos, contact_messages, inscripciones.
 * Run once from CLI: php scripts/run_migrate_indexes.php
 * Or open in browser while logged into admin on localhost.
 */
require __DIR__ . '/../config/database.php';

$queries = [
    "ALTER TABLE juegos ADD INDEX idx_juegos_torneo_fecha (torneo_id, fecha)",
    "ALTER TABLE juegos ADD INDEX idx_juegos_fecha_estado (fecha, estado)",
    "ALTER TABLE contact_messages ADD INDEX idx_contact_created (created_at)",
    "ALTER TABLE inscripciones ADD INDEX idx_inscripciones_created (created_at)",
];

$results = [];
foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['sql' => $sql, 'ok' => true, 'msg' => 'Index created'];
    } catch (PDOException $e) {
        // Error 1061 = Duplicate key name — index already exists, that's fine
        $msg = $e->getMessage();
        $ok  = str_contains($msg, '1061') || str_contains($msg, 'Duplicate key name');
        $results[] = ['sql' => $sql, 'ok' => $ok, 'msg' => $ok ? 'Already exists (OK)' : $msg];
    }
}

if (php_sapi_name() === 'cli') {
    foreach ($results as $r) {
        echo ($r['ok'] ? '[OK] ' : '[ERR] ') . $r['msg'] . "\n  " . $r['sql'] . "\n";
    }
} else {
    echo '<pre style="font-family:monospace;background:#111;color:#eee;padding:20px;">';
    echo "VCF DB Index Migration\n======================\n\n";
    foreach ($results as $r) {
        echo ($r['ok'] ? '[OK] ' : '[ERR] ') . htmlspecialchars($r['msg']) . "\n";
        echo '  ' . htmlspecialchars($r['sql']) . "\n\n";
    }
    echo '</pre>';
}
