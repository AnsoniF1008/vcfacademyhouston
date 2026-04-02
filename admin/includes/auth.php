<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['admin_logged'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/../../config/database.php';

$admin_logout = static function (): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: index.php');
    exit;
};

$admin_user_id = (int) ($_SESSION['admin_user_id'] ?? 0);
if ($admin_user_id <= 0) {
    $admin_logout();
}

$resolved_role = 'staff_campo';
$resolved_username = 'Unknown';
try {
    $stmt = $pdo->prepare("SELECT role, username FROM admin_users WHERE id = ?");
    $stmt->execute([$admin_user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $admin_logout();
    }
    $resolved_username = (string) ($row['username'] ?? 'Unknown');
    $raw_role = $row['role'] ?? '';
    $resolved_role = (is_string($raw_role) && $raw_role !== '') ? $raw_role : 'staff_campo';
} catch (PDOException $e) {
    // Legacy schema (without role): keep least privilege instead of escalating privileges.
    if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1054) {
        $stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
        $stmt->execute([$admin_user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $admin_logout();
        }
        $resolved_username = (string) ($row['username'] ?? 'Unknown');
        $resolved_role = 'staff_campo';
    } else {
        error_log('admin auth: ' . $e->getMessage());
        $admin_logout();
    }
}

$_SESSION['admin_role'] = $resolved_role;
$_SESSION['admin_username'] = $resolved_username;

$admin_role = $_SESSION['admin_role'] ?? 'staff_campo';
$admin_username = $_SESSION['admin_username'] ?? '';

function admin_can($permission) {
    $role = $_SESSION['admin_role'] ?? 'staff_campo';
    $matrix = [
        'super_admin' => ['*'],
        'editor_coach' => [
            'dashboard', 'jugador_mes', 'roster_edit', 'roster_stats', 'juegos', 'juegos_delete', 'motm',
            'torneos', 'categorias', 'sedes', 'hero_slider', 'match_reels', 'change_own_password', 'activity_log_view'
        ],
        'staff_campo' => [
            'dashboard', 'jugador_mes', 'juegos_live_score', 'motm', 'change_own_password'
        ],
    ];
    if (!isset($matrix[$role])) return false;
    if (in_array('*', $matrix[$role], true)) return true;
    return in_array($permission, $matrix[$role], true);
}

function require_permission($permission) {
    if (!admin_can($permission)) {
        header('Location: dashboard.php?error=forbidden');
        exit;
    }
}

function require_super_admin() {
    require_permission('*');
}

function admin_log($action, $details = null) {
    global $pdo;
    if (!isset($pdo)) return;
    try {
        $user_id = $_SESSION['admin_user_id'] ?? null;
        $username = $_SESSION['admin_username'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO admin_activity_log (user_id, username, action, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $username, $action, $details]);
    } catch (PDOException $e) {
        error_log('admin_log: ' . $e->getMessage());
    }
}
