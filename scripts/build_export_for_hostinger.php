<?php
/**
 * Prepara el ZIP del sitio para subir a Hostinger (archivos sin credenciales).
 * Uso: php scripts/build_export_for_hostinger.php
 * Genera: valencia-website.zip en la raíz del proyecto.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$outZip = $root . DIRECTORY_SEPARATOR . 'valencia-website.zip';

$excludePaths = [
    '.git',
    'node_modules',
    '.idea',
    '.vscode',
    '.cursor',
    'valencia-website.zip',
    'export',
];
$excludeFiles = [
    'config/database.local.php',
    'config/deploy-credentials.php',
    '.gitignore',
    '.DS_Store',
    'Thumbs.db',
];

function shouldExclude(string $relPath, array $excludePaths, array $excludeFiles): bool {
    $relPath = str_replace('\\', '/', $relPath);
    foreach ($excludePaths as $p) {
        if ($relPath === $p || strpos($relPath, $p . '/') === 0) return true;
    }
    foreach ($excludeFiles as $f) {
        if ($relPath === $f) return true;
    }
    return false;
}

$tempDir = $root . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR . 'valencia-website';
if (is_dir($tempDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $f) {
        if ($f->isDir()) rmdir($f->getPathname()); else unlink($f->getPathname());
    }
    rmdir($tempDir);
}
mkdir($tempDir, 0755, true);

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
    RecursiveIteratorIterator::SELF_FIRST
);

$count = 0;
foreach ($it as $path) {
    $full = $path->getPathname();
    $rel = substr($full, strlen($root) + 1);
    if ($rel === '' || $rel === false) continue;
    if (shouldExclude($rel, $excludePaths, $excludeFiles)) continue;
    if (preg_match('#^assets/uploads/#', str_replace('\\', '/', $rel)) && basename($rel) !== '.gitkeep') continue;

    $dest = $tempDir . DIRECTORY_SEPARATOR . $rel;
    if ($path->isDir()) {
        if (!is_dir($dest)) mkdir($dest, 0755, true);
    } else {
        $dir = dirname($dest);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        copy($full, $dest);
        $count++;
    }
}

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($outZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $zipIt = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($zipIt as $path) {
            $full = $path->getPathname();
            $rel = substr($full, strlen($tempDir) + 1);
            if ($path->isDir()) {
                $zip->addEmptyDir($rel . '/');
            } else {
                $zip->addFile($full, $rel);
            }
        }
        $zip->close();
        // Remove temp copy
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $f) {
            if ($f->isDir()) rmdir($f->getPathname()); else unlink($f->getPathname());
        }
        rmdir($tempDir);
        echo "Listo. Creado: " . realpath($outZip) . " ({$count} archivos)\n";
    } else {
        echo "ZIP no disponible. Archivos copiados a: " . realpath($tempDir) . "\nComprime esa carpeta manualmente en valencia-website.zip\n";
    }
} else {
    $zipPath = $root . DIRECTORY_SEPARATOR . 'valencia-website.zip';
    $tempDirQuoted = escapeshellarg($tempDir);
    $zipQuoted = escapeshellarg($zipPath);
    exec("powershell -NoProfile -Command Compress-Archive -Path {$tempDirQuoted}\\* -DestinationPath {$zipQuoted} -Force", $out, $psCode);
    if ($psCode === 0 && is_file($zipPath)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $f) {
            if ($f->isDir()) rmdir($f->getPathname()); else unlink($f->getPathname());
        }
        rmdir($tempDir);
        echo "Listo. Creado: " . realpath($zipPath) . " ({$count} archivos)\n";
    } else {
        echo "Archivos listos en: " . realpath($tempDir) . " ({$count} archivos)\n";
        echo "Comprime esa carpeta manualmente (clic derecho → Comprimir) como valencia-website.zip y súbela a Hostinger.\n";
    }
}
echo "Sube el ZIP en el asistente de migración de Hostinger.\n";
