<?php
/**
 * VCF Academy Houston - PDO Database Connection
 * Use environment variables or change these for your XAMPP setup.
 * En producción (InfinityFree): crea config/database.local.php con tus datos
 * (copia de config/database.infinityfree.example.php).
 */

declare(strict_types=1);

date_default_timezone_set('America/Chicago');

// Si existe database.local.php, usarlo siempre (remoto o servidor). Si no, usar XAMPP/local por defecto.
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = (php_sapi_name() !== 'cli') && in_array($host, ['localhost', '127.0.0.1'], true);

// Hide PHP errors from the public response in production. Errors are still written to the server log.
if (!$isLocal && php_sapi_name() !== 'cli') {
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    @ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
}

if (file_exists(__DIR__ . '/database.local.php')) {
    require __DIR__ . '/database.local.php';
} else {
    $DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
    $DB_NAME = $_ENV['DB_NAME'] ?? 'if0_41281527_valenciacf';
    $DB_USER = $_ENV['DB_USER'] ?? 'root';
    $DB_PASS = $_ENV['DB_PASS'] ?? '';
}

$usingRemote = !in_array($DB_HOST ?? '', ['localhost', '127.0.0.1'], true);
$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        throw $e;
    }

    // ── Stale-if-error fallback ────────────────────────────────────────
    // Hostinger's shared plan caps each MySQL user at 500 connections per
    // hour. When that ceiling is hit (or any other DB outage occurs) we
    // would normally render a hard error page. Instead, try to serve the
    // last good cached version of this URL — better to show stale data
    // for a few minutes than break the site for every visitor.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $pageCacheFile = __DIR__ . '/../includes/page_cache.php';
        if (is_file($pageCacheFile)) {
            require_once $pageCacheFile;
            if (function_exists('vcf_page_cache_try_serve_stale') && vcf_page_cache_try_serve_stale(3600)) {
                exit;
            }
        }
    }

    http_response_code(500);
    // Make sure browsers (and Cloudflare-style proxies) don't cache the
    // error page. Otherwise a user that hit the error once will keep
    // seeing it locally even after MySQL recovers.
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database connection failed</title>
        <style>
            body { font-family: system-ui, sans-serif; background: #1A1A1A; color: #fff; margin: 2rem; line-height: 1.6; }
            h1 { color: #FF6600; }
            ul { margin: 1rem 0; }
            code { background: #333; padding: 0.2em 0.4em; border-radius: 4px; }
        </style>
    </head>
    <body>
        <h1>Database connection failed</h1>
        <?php if ($isLocal): ?>
        <p><strong>Error:</strong> <code><?php echo htmlspecialchars($e->getMessage()); ?></code></p>
        <?php endif; ?>
        <p>This site cannot connect to MySQL. Check the following:</p>
        <ul>
            <?php if ($usingRemote): ?>
            <li><strong>Panel del hosting:</strong> En «Bases de datos MySQL» o «MySQL Databases», asegúrate de que el usuario <code><?php echo htmlspecialchars($DB_USER ?? ''); ?></code> esté <strong>asignado a la base de datos</strong> <code><?php echo htmlspecialchars($DB_NAME ?? ''); ?></code> con todos los privilegios.</li>
            <li><strong>Credenciales:</strong> Revisa en <code>config/database.local.php</code> que el host, nombre de BD, usuario y contraseña coincidan con el panel.</li>
            <?php elseif ($isLocal): ?>
            <li><strong>XAMPP:</strong> Start <strong>MySQL</strong> in the XAMPP Control Panel.</li>
            <li><strong>Create the database:</strong> In phpMyAdmin, create a database named <code>if0_41281527_valenciacf</code>, then import <code>sql/schema.sql</code>.</li>
            <li><strong>Credentials:</strong> Local uses defaults in <code>config/database.php</code> (user <code>root</code>, no password).</li>
            <?php else: ?>
            <li><strong>Production:</strong> Check that the database exists in your hosting panel and that <code>config/database.local.php</code> has the correct host, database name, user and password.</li>
            <?php endif; ?>
        </ul>
        <p>After fixing, reload this page.</p>
    </body>
    </html>
    <?php
    exit;
}
