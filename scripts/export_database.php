<?php
/**
 * Exporta la base de datos MySQL a un archivo .sql para importar en Hostinger.
 * Uso: php scripts/export_database.php
 * Genera: export/valencia-database.sql (o valencia-database.sql en la raíz si no existe export/)
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    require $root . '/admin/includes/auth.php';
    require_permission('*');
}

// Same DB config logic as run_migrate_rbac.php
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = $isCli || in_array($host, ['localhost', '127.0.0.1'], true);
if (file_exists($root . '/config/database.local.php')) {
    require $root . '/config/database.local.php';
}
if (!isset($DB_HOST)) {
    $DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
    $DB_NAME = $_ENV['DB_NAME'] ?? 'if0_41281527_valenciacf';
    $DB_USER = $_ENV['DB_USER'] ?? 'root';
    $DB_PASS = $_ENV['DB_PASS'] ?? '';
}

$outDir = $root . DIRECTORY_SEPARATOR . 'export';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}
$outFile = $outDir . DIRECTORY_SEPARATOR . 'valencia-database.sql';

// Try mysqldump (XAMPP Windows common path)
$mysqldump = 'mysqldump';
if (PHP_OS_FAMILY === 'Windows' && is_executable('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
    $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
}

$pass = $DB_PASS ?? '';
$cmd = sprintf(
    '%s --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s 2>&1',
    escapeshellcmd($mysqldump),
    escapeshellarg($DB_HOST),
    escapeshellarg($DB_USER),
    escapeshellarg($pass),
    escapeshellarg($DB_NAME)
);

$output = [];
exec($cmd, $output, $code);
$sqlContent = implode("\n", $output);
if ($code !== 0 || strlen($sqlContent) < 100) {
    $msg = 'mysqldump falló o no está en el PATH. Exporta desde phpMyAdmin: Exportar → SQL y guarda el .sql.';
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}
file_put_contents($outFile, $sqlContent);

$msg = 'Base de datos exportada: ' . realpath($outFile) . "\nSúbela en Hostinger como archivo .sql en el paso de migración.";
if ($isCli) {
    echo $msg . PHP_EOL;
    exit(0);
}
header('Content-Type: text/plain; charset=utf-8');
echo $msg;
