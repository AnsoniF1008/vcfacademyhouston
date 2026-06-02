<?php
/**
 * Generate .webp siblings for every jpg/png under assets/ (skipping ones that
 * are already up to date). Safe to run repeatedly — e.g. via cron or after
 * uploading new roster/news images on the server. The site only *serves* the
 * .webp when includes/vcf_picture.php finds it, so running this never breaks
 * anything; it just makes images lighter where supported.
 *
 * Usage:  php scripts/generate-webp.php [--quality=82] [--force]
 *
 * Requires GD with WebP support (function_exists('imagewebp')).
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}
if (!function_exists('imagewebp')) {
    fwrite(STDERR, "GD WebP support not available (imagewebp missing).\n");
    exit(1);
}

$quality = 82;
$force = false;
foreach ($argv as $arg) {
    if (preg_match('/^--quality=(\d{1,3})$/', $arg, $m)) {
        $quality = max(1, min(100, (int) $m[1]));
    } elseif ($arg === '--force') {
        $force = true;
    }
}

$root = dirname(__DIR__);
$assets = $root . '/assets';
if (!is_dir($assets)) {
    fwrite(STDERR, "No assets/ directory.\n");
    exit(1);
}

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($assets, FilesystemIterator::SKIP_DOTS)
);

$made = 0;
$skipped = 0;
$failed = 0;
foreach ($it as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    if (!preg_match('/\.(jpe?g|png)$/i', $path)) {
        continue;
    }
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);

    // Up to date? Skip unless --force.
    if (!$force && is_file($webp) && filemtime($webp) >= filemtime($path)) {
        $skipped++;
        continue;
    }

    // Detect the real type by content, not extension (some files are PNGs
    // saved with a .jpg name and vice versa).
    $info = @getimagesize($path);
    $type = is_array($info) ? ($info[2] ?? 0) : 0;
    if ($type === IMAGETYPE_PNG) {
        $img = @imagecreatefrompng($path);
        $isPng = true;
    } elseif ($type === IMAGETYPE_JPEG) {
        $img = @imagecreatefromjpeg($path);
        $isPng = false;
    } else {
        fwrite(STDERR, "  SKIP (not jpeg/png content): $path\n");
        $skipped++;
        continue;
    }
    if ($img === false) {
        fwrite(STDERR, "  FAIL (decode): $path\n");
        $failed++;
        continue;
    }
    if ($isPng) {
        imagepalettetotruecolor($img);
        imagealphablending($img, false);
        imagesavealpha($img, true);
    }
    if (@imagewebp($img, $webp, $quality)) {
        $made++;
        echo "  webp: " . substr($webp, strlen($root) + 1) . "\n";
    } else {
        fwrite(STDERR, "  FAIL (encode): $path\n");
        $failed++;
    }
    imagedestroy($img);
}

echo "Done. created/updated=$made, skipped=$skipped, failed=$failed (quality=$quality)\n";
exit($failed > 0 ? 1 : 0);
