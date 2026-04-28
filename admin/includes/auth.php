<?php
// ── Session hardening ──────────────────────────────────────────────────────
// Cookie params MUST be set before session_start(). Centralising them here
// ensures every admin page (not just the login form) uses Secure/HttpOnly/Strict.
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
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
            'hero_slider', 'match_reels', 'change_own_password', 'activity_log_view',
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
