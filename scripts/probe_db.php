<?php
/**
 * Quick MySQL connectivity probe.
 *
 * Use this whenever the public site or local dev shows the
 * "circuit breaker open / Database connection failed" page to verify
 * whether MySQL itself is responding (and if not, what error). It also
 * resets the local circuit breaker on a successful connection.
 *
 * Usage (from project root):
 *   php scripts/probe_db.php
 */
declare(strict_types=1);

$cfg = __DIR__ . '/../config/database.local.php';
if (!is_file($cfg)) {
    fwrite(STDERR, "[fail] config/database.local.php not found.\n");
    exit(1);
}
require $cfg;

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
$opts = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 5,
];

$start = microtime(true);
try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $opts);
    $row = $pdo->query('SELECT NOW() AS t')->fetch(PDO::FETCH_ASSOC);
    $ms = (int) round((microtime(true) - $start) * 1000);
    echo "[ok] MySQL responde en {$ms}ms — server time: {$row['t']}\n";

    // Reset the local circuit breaker so the next request to the dev
    // server doesn't get short-circuited based on a stale failure.
    $breaker = __DIR__ . '/../cache/db_blocked_until.txt';
    if (is_file($breaker)) {
        @unlink($breaker);
        echo "[info] circuit breaker reset (cache/db_blocked_until.txt removed)\n";
    }
} catch (Throwable $e) {
    $ms = (int) round((microtime(true) - $start) * 1000);
    echo "[fail] tras {$ms}ms: " . $e->getMessage() . "\n";
    exit(2);
}
