<?php
/**
 * Importa un backup de producción (Hostinger) a tu entorno local XAMPP.
 * Uso: php scripts/import_to_local.php [carpeta_backup]
 * Ejemplo: php scripts/import_to_local.php backup/production-20250305-143000
 * Si no pasas carpeta, usa la más reciente en backup/ que tenga valencia-database.sql.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    fwrite(STDERR, "Ejecuta este script desde la terminal: php scripts/import_to_local.php\n");
    exit(1);
}

$backupArg = $argv[1] ?? null;
$backupDir = null;
$backupBase = $root . DIRECTORY_SEPARATOR . 'backup';

if ($backupArg) {
    $candidate = is_dir($backupArg) ? $backupArg : $root . DIRECTORY_SEPARATOR . $backupArg;
    if (is_dir($candidate) && is_file($candidate . DIRECTORY_SEPARATOR . 'valencia-database.sql')) {
        $backupDir = realpath($candidate);
    }
}
if (!$backupDir && is_dir($backupBase)) {
    $dirs = glob($backupBase . DIRECTORY_SEPARATOR . 'production-*', GLOB_ONLYDIR);
    rsort($dirs);
    foreach ($dirs as $d) {
        if (is_file($d . DIRECTORY_SEPARATOR . 'valencia-database.sql')) {
            $backupDir = realpath($d);
            break;
        }
    }
}

if (!$backupDir || !is_file($backupDir . DIRECTORY_SEPARATOR . 'valencia-database.sql')) {
    fwrite(STDERR, "No se encontró carpeta de backup con valencia-database.sql.\n");
    fwrite(STDERR, "Ejecuta primero: .\\scripts\\backup_from_hostinger.ps1 -Password 'TU_CONTRASEÑA_SSH'\n");
    exit(1);
}

$sqlFile = $backupDir . DIRECTORY_SEPARATOR . 'valencia-database.sql';
$filesDir = $backupDir . DIRECTORY_SEPARATOR . 'files';

$DB_LOCAL = 'if0_41281527_valenciacf';
$mysqlExe = 'mysql';
if (PHP_OS_FAMILY === 'Windows' && is_executable('C:\\xampp\\mysql\\bin\\mysql.exe')) {
    $mysqlExe = 'C:\\xampp\\mysql\\bin\\mysql.exe';
}

echo "Backup: $backupDir\n";
echo "Importando BD en MySQL local (base: $DB_LOCAL)...\n";

// Normalizar dump: asegurar que se importe en la BD local (quitar CREATE DATABASE / USE de producción)
$sql = file_get_contents($sqlFile);
$sql = preg_replace('/^\s*CREATE\s+DATABASE\s+.*?;/mi', '-- \0', $sql);
$sql = preg_replace('/^\s*USE\s+`?[\w]+`?\s*;/mi', '-- \0', $sql);
$sql = "CREATE DATABASE IF NOT EXISTS `$DB_LOCAL` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE `$DB_LOCAL`;\n" . $sql;

$tmpSql = $root . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . '.import_temp.sql';
$tmpDir = dirname($tmpSql);
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}
file_put_contents($tmpSql, $sql);

// El SQL ya incluye CREATE DATABASE y USE; solo redirigimos el fichero
if (PHP_OS_FAMILY === 'Windows') {
    $cmd = 'cmd /c "' . $mysqlExe . ' --host=localhost --user=root --password= --default-character-set=utf8mb4 < ' . str_replace('"', '\"', $tmpSql) . '" 2>&1';
} else {
    $cmd = sprintf('%s --host=localhost --user=root --password= --default-character-set=utf8mb4 < %s 2>&1', escapeshellcmd($mysqlExe), escapeshellarg($tmpSql));
}
$output = [];
exec($cmd, $output, $code);
@unlink($tmpSql);

if ($code !== 0) {
    fwrite(STDERR, "Error al importar: " . implode("\n", $output) . "\n");
    fwrite(STDERR, "Importa manualmente: en phpMyAdmin selecciona la base $DB_LOCAL e Importar -> $sqlFile\n");
    exit(1);
}
echo "Base de datos importada.\n";

// Copiar assets/uploads del backup al proyecto
$uploadsBackup = $filesDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
$uploadsLocal = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
if (is_dir($uploadsBackup)) {
    if (!is_dir($uploadsLocal)) {
        mkdir($uploadsLocal, 0755, true);
    }
    $count = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsBackup, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile()) {
            $rel = substr($f->getPathname(), strlen($uploadsBackup) + 1);
            $dest = $uploadsLocal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            copy($f->getPathname(), $dest);
            $count++;
        }
    }
    echo "Copiados $count archivos en assets/uploads.\n";
} else {
    echo "No hay carpeta assets/uploads en el backup (opcional).\n";
}

echo "Importación lista. Abre http://localhost/valencia y comprueba que la BD y las imágenes se ven correctamente.\n";
echo "No copies config/database.local.php del backup a tu proyecto (local usa localhost por defecto).\n";
