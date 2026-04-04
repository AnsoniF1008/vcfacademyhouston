<?php
// Logout must be POST to prevent CSRF logout via <img>, <link> or plain GET links.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Someone navigated here directly via GET — redirect safely.
    header('Location: ' . (!empty($_SESSION['admin_logged']) ? 'dashboard.php' : 'index.php'));
    exit;
}

require __DIR__ . '/includes/csrf.php';
if (!csrf_verify()) {
    // Bad or expired token — send back to dashboard.
    header('Location: dashboard.php');
    exit;
}

// Destroy session completely.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();
header('Location: index.php');
exit;
