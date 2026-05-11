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

// Persistent connections are safe for our use (no transactions, no
// session-state queries) and cut connection ATTEMPTS dramatically — the
// metric Hostinger's `max_connections_per_hour` cap actually counts.
// A handful of PHP-FPM workers will share a few persistent sockets
// instead of dialing MySQL on every request. We disable persistence in
// CLI scripts and on local dev to avoid surprising behaviour during
// debugging.
$persistentSafe = (php_sapi_name() !== 'cli') && $usingRemote;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Reasonable connect timeout: don't hang for ages if MySQL is down.
    PDO::ATTR_TIMEOUT            => 4,
    PDO::ATTR_PERSISTENT         => $persistentSafe,
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
$breakerWindow = 120; // seconds the breaker stays open after a failure.
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
    // Persistent connections survive across requests, so if a previous
    // request's script crashed mid-transaction (PHP fatal, browser
    // disconnect, timeout) we may inherit a "transaction-in-progress"
    // state. Roll it back so the new request starts clean.
    if ($persistentSafe && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (PDOException $rbErr) {
            // Already rolled back or connection in a bad state — nothing to do.
        }
    }
    // Successful connection → clear any prior breaker state.
    if (is_file($breakerFile)) {
        @unlink($breakerFile);
    }
} catch (PDOException $e) {
    $errMsg = $e->getMessage();
    // Tag rate-limit failures explicitly so they're easy to grep in logs.
    if (strpos($errMsg, 'max_connections_per_hour') !== false || strpos($errMsg, '1226') !== false) {
        error_log('Database connection failed (HOSTINGER RATE LIMIT / 1226 max_connections_per_hour): ' . $errMsg);
    } else {
        error_log('Database connection failed: ' . $errMsg);
    }
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
        $msg = $e->getMessage();
        $isRateLimit = strpos($msg, 'max_connections_per_hour') !== false || strpos($msg, '1226') !== false;
        $isBreakerOpen = strpos($msg, 'Circuit breaker') !== false;
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database connection failed (dev)</title>
            <style>
                body { font-family: system-ui, sans-serif; background: #1A1A1A; color: #fff; margin: 2rem; line-height: 1.6; max-width: 760px; }
                h1 { color: #FF6600; }
                h2 { color: #FF6B00; font-size: 1.1rem; margin-top: 1.4rem; }
                code { background: #333; padding: 0.2em 0.4em; border-radius: 4px; word-break: break-all; }
                ul { margin: 0.6rem 0; padding-left: 1.4rem; }
                .err { background: #2a1212; border-left: 3px solid #FF6600; padding: 12px 16px; border-radius: 4px; margin: 1rem 0; }
                .meta { color: #888; font-size: 0.9rem; margin-top: 1.5rem; }
                kbd { background: #444; padding: 1px 6px; border-radius: 3px; font-size: 0.9em; }
            </style>
        </head>
        <body>
            <h1>Database connection failed</h1>
            <div class="err"><strong>Error:</strong> <code><?php echo htmlspecialchars($msg); ?></code></div>
            <?php if ($usingRemote): ?>
            <p>Tu <code>config/database.local.php</code> apunta a <strong>la base remota de Hostinger</strong> (<code><?php echo htmlspecialchars($DB_HOST); ?></code>), no a XAMPP. Esa BD es la misma que usa producción, así que cualquier problema lo verás también en https://vcfacademyhouston.com.</p>

            <?php if ($isRateLimit): ?>
            <h2>Causa: límite del plan de Hostinger</h2>
            <p>El plan compartido limita al usuario MySQL a <strong>500 conexiones por hora</strong>. Cuando se rebasa, MySQL rechaza nuevas conexiones hasta que pase la ventana rolling.</p>
            <ul>
                <li>Suele restablecerse solo en 30-60 minutos.</li>
                <li>El page cache + circuit breaker reducen drásticamente la frecuencia con la que esto ocurre.</li>
                <li>Si vuelve a pasar a menudo, considera subir al plan Premium/Business de Hostinger.</li>
            </ul>
            <?php elseif ($isBreakerOpen): ?>
            <h2>Causa: circuit breaker activo</h2>
            <p>Una conexión falló hace pocos segundos. Para no machacar a MySQL, el sistema deja de intentar conectar durante unos 120 segundos.</p>
            <ul>
                <li>Espera ~2 minutos y recarga.</li>
                <li>Para forzar el reseteo manualmente: borra <code>cache/db_blocked_until.txt</code> en el proyecto.</li>
            </ul>
            <?php else: ?>
            <h2>Comprobaciones</h2>
            <ul>
                <li>Verifica que el host <code><?php echo htmlspecialchars($DB_HOST); ?></code> sea accesible desde tu red (algunos proveedores bloquean conexiones MySQL salientes).</li>
                <li>Confirma en el panel Hostinger que el usuario <code><?php echo htmlspecialchars($DB_USER); ?></code> sigue asignado a la BD <code><?php echo htmlspecialchars($DB_NAME); ?></code>.</li>
                <li>Comprueba que la contraseña en <code>config/database.local.php</code> coincide con la del panel.</li>
            </ul>
            <?php endif; ?>
            <p class="meta">Pulsa <kbd>F5</kbd> para reintentar, o limpia <code>cache/</code> y <code>cache/db_blocked_until.txt</code> si quieres forzar el estado.</p>
            <?php else: ?>
            <ul>
                <li>Comprueba que XAMPP MySQL esté corriendo.</li>
                <li>O que <code>config/database.local.php</code> tenga credenciales correctas.</li>
            </ul>
            <?php endif; ?>
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
