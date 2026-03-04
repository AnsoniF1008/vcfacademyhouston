<?php
/**
 * Quita los datos de prueba añadidos por seed_demo_data.sql (5 partidos y sus MOTM).
 * Uso: php scripts/rollback_demo.php   o abrir en navegador (con sesión admin).
 */

declare(strict_types=1);

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    require dirname(__DIR__) . '/admin/includes/auth.php';
}

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

$sqlFile = dirname(__DIR__) . '/sql/rollback_demo_data.sql';
if (!is_readable($sqlFile)) {
    $msg = "SQL file not found: {$sqlFile}";
    if ($isCli) { fwrite(STDERR, $msg . PHP_EOL); exit(1); }
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}

$sql = file_get_contents($sqlFile);
$sql = preg_replace('/^\s*USE\s+\S+;\s*/m', '', $sql);

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS ?? '', $DB_NAME);
if ($mysqli->connect_error) {
    $msg = 'Database connection failed: ' . $mysqli->connect_error;
    if ($isCli) { fwrite(STDERR, $msg . PHP_EOL); exit(1); }
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$ok = $mysqli->multi_query($sql);
if (!$ok) {
    $msg = 'Rollback failed: ' . $mysqli->error;
    $mysqli->close();
    if ($isCli) { fwrite(STDERR, $msg . PHP_EOL); exit(1); }
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}
do {
    if ($result = $mysqli->store_result()) $result->free();
} while ($mysqli->more_results() && $mysqli->next_result());
if ($mysqli->error) {
    $msg = 'Rollback error: ' . $mysqli->error;
    $mysqli->close();
    if ($isCli) { fwrite(STDERR, $msg . PHP_EOL); exit(1); }
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}
$mysqli->close();

$msg = "Demo data removed. Your previous games and data are unchanged.";
if ($isCli) {
    echo $msg . PHP_EOL;
    exit(0);
}
header('Location: ../admin/dashboard.php?rollback=1');
exit(0);
