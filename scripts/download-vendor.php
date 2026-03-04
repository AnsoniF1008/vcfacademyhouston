<?php
/**
 * One-time script: download Bootstrap and Font Awesome into assets/vendor/
 * so the site can serve them locally and avoid Tracking Prevention blocking CDN storage.
 * Run from project root: php scripts/download-vendor.php
 */
$projectRoot = dirname(__DIR__);
$vendorDir = $projectRoot . '/assets/vendor';
$context = stream_context_create([
    'http' => ['user_agent' => 'VCF-Academy-Houston/1.0'],
    'ssl'  => ['verify_peer' => true]
]);

$urls = [
    'bootstrap/css/bootstrap.min.css' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'bootstrap/js/bootstrap.bundle.min.js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
    'fontawesome/css/all.min.css' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
];

$webfonts = [
    'fontawesome/webfonts/fa-solid-900.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/fa-solid-900.woff2',
    'fontawesome/webfonts/fa-regular-400.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/fa-regular-400.woff2',
    'fontawesome/webfonts/fa-brands-400.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/fa-brands-400.woff2',
];

foreach (array_merge($urls, $webfonts) as $relPath => $url) {
    $fullPath = $vendorDir . '/' . $relPath;
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    echo "Downloading {$relPath} ... ";
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        echo "FAILED\n";
        continue;
    }
    file_put_contents($fullPath, $data);
    echo "OK\n";
}

echo "Done. Reload the site to use local Bootstrap and Font Awesome (no CDN, no tracking warnings).\n";
