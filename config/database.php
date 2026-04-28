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
    // Reasonable connect timeout: don't hang for ages if MySQL is down.
    PDO::ATTR_TIMEOUT            => 4,
];

// ── Circuit breaker ───────────────────────────────────────────────────
// When Hostinger's per-user MySQL cap (500 connections / hour) is hit,
// every visitor that arrives during that window also tries to connect,
// fails, and *each failure also counts* against the cap. That keeps the
// site stuck in the rate-limited state long after traffic would have
// otherwise died down.
//
// To break the cycle: once we observe a connection failure we stamp a
// "blocked-until" timestamp on disk, and for the next N seconds NO
// request even attempts a new PDO connection — they all go straight to
// the stale cache or the branded fallback page. After N seconds, ONE
// request probes again. If it succeeds, the breaker resets; if not, the
// timestamp slides forward.
$breakerFile = __DIR__ . '/../cache/db_blocked_until.txt';
$breakerWindow = 90; // seconds the breaker stays open after a failure.
$breakerOpen = false;
if (php_sapi_name() !== 'cli' && is_file($breakerFile)) {
    $blockedUntil = (int) @file_get_contents($breakerFile);
    if ($blockedUntil > time()) {
        $breakerOpen = true;
    }
}

try {
    if ($breakerOpen) {
        // Synthesize an exception so we share the catch block with real
        // failures. The message is for logs only — it never reaches users.
        throw new PDOException('Circuit breaker open: skipping connection attempt to ' . $DB_HOST);
    }
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    // Successful connection → clear any prior breaker state.
    if (is_file($breakerFile)) {
        @unlink($breakerFile);
    }
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    // Trip the breaker (only on real failures, not on every short-circuit).
    if (!$breakerOpen) {
        @file_put_contents($breakerFile, (string) (time() + $breakerWindow), LOCK_EX);
    }
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

    // 503 (Service Unavailable) is the right code: we're up but a
    // dependency is temporarily down. Search engines and uptime monitors
    // treat 503 as "try again later" rather than a permanent failure.
    http_response_code(503);
    header('Retry-After: 60');
    // Make sure browsers (and Cloudflare-style proxies) don't cache the
    // fallback page. Otherwise a user that hit it once would keep seeing
    // it locally even after MySQL recovers.
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: text/html; charset=utf-8');

    if ($isLocal) {
        // Developer-facing diagnostic page (only on localhost / 127.0.0.1).
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database connection failed (dev)</title>
            <style>
                body { font-family: system-ui, sans-serif; background: #1A1A1A; color: #fff; margin: 2rem; line-height: 1.6; }
                h1 { color: #FF6600; }
                code { background: #333; padding: 0.2em 0.4em; border-radius: 4px; }
                ul { margin: 1rem 0; }
            </style>
        </head>
        <body>
            <h1>Database connection failed</h1>
            <p><strong>Error:</strong> <code><?php echo htmlspecialchars($e->getMessage()); ?></code></p>
            <ul>
                <li>Check XAMPP MySQL is running, or that <code>config/database.local.php</code> has the correct credentials.</li>
                <li>If this is a Hostinger rate-limit (<code>max_connections_per_hour</code>), wait a few minutes and reload.</li>
            </ul>
        </body>
        </html>
        <?php
        exit;
    }

    // Public-facing fallback: branded "back soon" page (no DB needed).
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex">
        <meta http-equiv="refresh" content="60">
        <title>VCF Academy Houston — Back in a moment</title>
        <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
        <style>
            :root { --orange: #FF6B00; --dark: #0E0E0E; --dark2: #1A1A1A; --gray: #888; --light: #DADADA; }
            * { box-sizing: border-box; }
            body {
                margin: 0; min-height: 100vh;
                display: flex; align-items: center; justify-content: center;
                font-family: -apple-system, "Segoe UI", system-ui, Roboto, sans-serif;
                background: radial-gradient(ellipse at top, #1f1f1f 0%, var(--dark) 60%);
                color: var(--light); text-align: center; padding: 24px;
            }
            .vcf-fallback { max-width: 560px; }
            .vcf-fallback__crest {
                width: 88px; height: 88px; margin: 0 auto 24px;
                display: block;
            }
            .vcf-fallback__bar {
                width: 60px; height: 3px; margin: 0 auto 28px;
                background: var(--orange); border-radius: 2px;
            }
            h1 {
                font-family: "Bebas Neue", "Oswald", sans-serif;
                font-size: clamp(28px, 5vw, 44px); letter-spacing: 0.04em;
                margin: 0 0 12px; color: #fff; text-transform: uppercase;
            }
            h1 em { color: var(--orange); font-style: normal; }
            p { font-size: 15px; line-height: 1.6; color: var(--gray); margin: 0 0 18px; }
            .vcf-fallback__small { font-size: 12px; color: #555; margin-top: 28px; }
            .vcf-fallback__btn {
                display: inline-block; margin-top: 8px;
                padding: 11px 22px; background: var(--orange); color: #fff;
                font-weight: 700; font-size: 13px; letter-spacing: 0.08em;
                text-transform: uppercase; text-decoration: none;
                border-radius: 4px; transition: filter 0.2s;
            }
            .vcf-fallback__btn:hover { filter: brightness(1.15); }
            .vcf-fallback__spinner {
                width: 14px; height: 14px; display: inline-block;
                border: 2px solid rgba(255,107,0,0.25);
                border-top-color: var(--orange);
                border-radius: 50%; vertical-align: middle;
                margin-right: 8px;
                animation: vcfspin 0.9s linear infinite;
            }
            @keyframes vcfspin { to { transform: rotate(360deg); } }
        </style>
    </head>
    <body>
        <main class="vcf-fallback" role="main">
            <img class="vcf-fallback__crest" src="/assets/img/vfc-crest.svg" alt="VCF Academy Houston" onerror="this.style.display='none'">
            <div class="vcf-fallback__bar" aria-hidden="true"></div>
            <h1>We'll Be Right <em>Back</em></h1>
            <p>Our site is taking a quick breather. This usually clears up within a minute or two — your browser will refresh automatically.</p>
            <p><span class="vcf-fallback__spinner" aria-hidden="true"></span>Reconnecting&hellip;</p>
            <a href="" class="vcf-fallback__btn" onclick="location.reload();return false;">Reload now</a>
            <p class="vcf-fallback__small">VCF Academy Houston &middot; Amunt Valencia &middot; Amunt Houston</p>
        </main>
    </body>
    </html>
    <?php
    exit;
}
