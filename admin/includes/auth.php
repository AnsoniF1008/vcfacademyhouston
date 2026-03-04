<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['admin_logged'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/../../config/database.php';

if (empty($_SESSION['admin_role'])) {
    try {
        $stmt = $pdo->prepare("SELECT role, username FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_user_id'] ?? 0]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['admin_role'] = $row['role'];
            $_SESSION['admin_username'] = $row['username'] ?? 'Unknown';
        } else {
            $_SESSION['admin_role'] = 'staff_campo';
            $_SESSION['admin_username'] = 'Unknown';
        }
    } catch (PDOException $e) {
        // Migration not run: role column missing — treat as super_admin
        $stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_user_id'] ?? 0]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['admin_role'] = 'super_admin';
        $_SESSION['admin_username'] = $row['username'] ?? 'Admin';
    }
}

$admin_role = $_SESSION['admin_role'] ?? 'staff_campo';
$admin_username = $_SESSION['admin_username'] ?? '';

function admin_can($permission) {
    $role = $_SESSION['admin_role'] ?? 'staff_campo';
    $matrix = [
        'super_admin' => ['*'],
        'editor_coach' => [
            'dashboard', 'jugador_mes', 'roster_edit', 'roster_stats', 'juegos', 'juegos_delete', 'motm',
            'torneos', 'categorias', 'sedes', 'hero_slider', 'change_own_password', 'activity_log_view'
        ],
        'staff_campo' => [
            'dashboard', 'jugador_mes', 'juegos_live_score', 'change_own_password'
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
