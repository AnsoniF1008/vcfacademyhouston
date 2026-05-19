<?php
// ── Session hardening ──────────────────────────────────────────────────────
// Cookie params MUST be set before session_start(). Centralising them here
// ensures every admin page (not just the login form) uses Secure/HttpOnly/Strict.
//
// Distinct session name `vcf_admin_sess` (separate from the default PHPSESSID)
// keeps admin cookies out of the public site's cookie jar. Otherwise any
// visitor who had ever opened /admin/ would carry the PHPSESSID cookie back
// to the public pages, where header.php would see it and skip the page cache
// for every subsequent request — quietly destroying the cache hit ratio.
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_name('vcf_admin_sess');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/admin/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
    session_start();
}

// ── Security headers on every admin page (not only login) ─────────────────
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

if (empty($_SESSION['admin_logged'])) {
    header('Location: index.php');
    exit;
}

// Idle timeout: 45 minutes of inactivity logs the admin out.
$VCF_ADMIN_IDLE_TIMEOUT = 45 * 60;
$now = time();
if (isset($_SESSION['admin_last_activity']) && ($now - (int) $_SESSION['admin_last_activity']) > $VCF_ADMIN_IDLE_TIMEOUT) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', $now - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: index.php?timeout=1');
    exit;
}
$_SESSION['admin_last_activity'] = $now;

require __DIR__ . '/../../config/database.php';

if (empty($_SESSION['admin_role'])) {
    try {
        $stmt = $pdo->prepare("SELECT role, username FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_user_id'] ?? 0]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['admin_role']    = $row['role'];
            $_SESSION['admin_username'] = $row['username'] ?? 'Unknown';
        } else {
            $_SESSION['admin_role']    = 'staff_campo';
            $_SESSION['admin_username'] = 'Unknown';
        }
    } catch (PDOException $e) {
        // Migration not yet run (role column missing) — treat as super_admin.
        $stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_user_id'] ?? 0]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['admin_role']    = 'super_admin';
        $_SESSION['admin_username'] = $row['username'] ?? 'Admin';
    }
}

$admin_role     = $_SESSION['admin_role']     ?? 'staff_campo';
$admin_username = $_SESSION['admin_username'] ?? '';

/**
 * Role → permissions matrix.
 *
 * roster_delete   — intentionally super_admin only. Coaches should mark players
 *                   inactive rather than permanently deleting them.
 *
 * inscripciones_view / contact_messages_view — read-only access to form submissions.
 */
function admin_can(string $permission): bool {
    $role   = $_SESSION['admin_role'] ?? 'staff_campo';
    $matrix = [
        'super_admin' => ['*'],
        'editor_coach' => [
            'dashboard', 'jugador_mes', 'roster_edit', 'roster_stats',
            'juegos', 'juegos_delete', 'motm', 'torneos', 'categorias', 'sedes',
            'hero_slider', 'match_reels', 'noticias', 'change_own_password', 'activity_log_view',
            'inscripciones_view', 'contact_messages_view', 'support_donations',
        ],
        'staff_campo' => [
            'dashboard', 'jugador_mes', 'juegos_live_score', 'motm', 'change_own_password',
        ],
    ];
    if (!isset($matrix[$role])) return false;
    if (in_array('*', $matrix[$role], true)) return true;
    return in_array($permission, $matrix[$role], true);
}

function require_permission(string $permission): void {
    if (!admin_can($permission)) {
        header('Location: dashboard.php?error=forbidden');
        exit;
    }
}

function require_super_admin(): void {
    require_permission('*');
}

function admin_log(string $action, ?string $details = null): void {
    global $pdo;
    if (!isset($pdo)) return;
    try {
        $user_id  = $_SESSION['admin_user_id'] ?? null;
        $username = $_SESSION['admin_username'] ?? '';
        $stmt = $pdo->prepare(
            "INSERT INTO admin_activity_log (user_id, username, action, details) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$user_id, $username, $action, $details]);
    } catch (PDOException $e) {
        error_log('admin_log: ' . $e->getMessage());
    }
}

// ── Auto-invalidate the public page/API cache on any admin POST ─────────────
// When admins save changes to roster, juegos, MOTM, hero, etc. the public site
// would otherwise keep serving cached HTML/JSON for up to 15-20 minutes (the
// page_cache TTLs). Wiping both caches on every successful admin POST guarantees
// visitors see the change immediately, without forcing admins to remember the
// "Clear cache" button.
//
// Scope: admin POST only (login form excluded — handled separately). GETs do
// not invalidate. Read-only admin pages (dashboard, activity-log) only see GETs
// so this is a no-op for them.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    register_shutdown_function(function (): void {
        // Only run on responses that look successful (2xx/3xx). Errors and
        // CSRF/validation failures (handlers usually fall through to re-render
        // the form with a 200) are fine to invalidate too — worst case we
        // regenerate a couple of cache entries unnecessarily.
        $code = function_exists('http_response_code') ? (int) http_response_code() : 200;
        if ($code >= 400) {
            return;
        }
        $pageCacheFile = __DIR__ . '/../../includes/page_cache.php';
        if (is_file($pageCacheFile)) {
            require_once $pageCacheFile;
            if (function_exists('vcf_public_cache_clear')) {
                @vcf_public_cache_clear();
            }
        }
    });
}
