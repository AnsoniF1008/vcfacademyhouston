<?php
/**
 * Run sql/seed_demo_data.sql to fill DB with demo data for testing the full page.
 * Usage: php scripts/run_seed_demo.php   (CLI)
 *    or: open in browser (requires admin login if run from admin folder)
 * Uses mysqli for multi-statement execution.
 */

declare(strict_types=1);

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    // When run from browser, require admin auth
    require dirname(__DIR__) . '/admin/includes/auth.php';
}

// Reuse same DB config as config/database.php (CLI = treat as local)
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = $isCli || in_array($host, ['localhost', '127.0.0.1'], true);
if (!$isLocal && file_exists(dirname(__DIR__) . '/config/database.local.php')) {
    require dirname(__DIR__) . '/config/database.local.php';
}
if (!isset($DB_HOST)) {
    $DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
    $DB_NAME = $_ENV['DB_NAME'] ?? 'if0_41281527_valenciacf';
    $DB_USER = $_ENV['DB_USER'] ?? 'root';
    $DB_PASS = $_ENV['DB_PASS'] ?? '';
}

$sqlFile = dirname(__DIR__) . '/sql/seed_demo_data.sql';
if (!is_readable($sqlFile)) {
    $msg = "SQL file not found or not readable: {$sqlFile}";
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}

$sql = file_get_contents($sqlFile);
// Remove USE statement so we connect to the right DB
$sql = preg_replace('/^\s*USE\s+\S+;\s*/m', '', $sql);

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS ?? '', $DB_NAME);
if ($mysqli->connect_error) {
    $msg = 'Database connection failed: ' . $mysqli->connect_error;
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$ok = $mysqli->multi_query($sql);
if (!$ok) {
    $msg = 'Seed failed: ' . $mysqli->error;
    $mysqli->close();
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}

// Drain results (required after multi_query)
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());

if ($mysqli->error) {
    $msg = 'Seed error: ' . $mysqli->error;
    $mysqli->close();
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}

$mysqli->close();

$msg = "Demo data seeded successfully. Reload the homepage to see Latest Results, Next Match, MOTM, and player stats/radar.";
if ($isCli) {
    echo $msg . PHP_EOL;
    exit(0);
}
header('Location: ../admin/dashboard.php?seeded=1');
exit(0);
