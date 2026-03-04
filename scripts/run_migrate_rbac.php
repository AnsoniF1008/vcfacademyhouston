<?php
/**
 * Run RBAC migration: add role/email to admin_users and create admin_activity_log.
 * Usage: php scripts/run_migrate_rbac.php   (CLI)
 *    or: open in browser (requires admin login when run from web)
 */
declare(strict_types=1);

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    require dirname(__DIR__) . '/admin/includes/auth.php';
}

$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = $isCli || in_array($host, ['localhost', '127.0.0.1'], true);
if (!$isLocal && file_exists(dirname(__DIR__) . '/config/database.local.php')) {
    require dirname(__DIR__) . '/config/database.local.php';
}
if (!isset($DB_HOST)) {
    $DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
    $DB_NAME = $_ENV['DB_NAME'] ?? 'if0_41281527_valenciacf';
    $DB_USER = $_ENV['DB_USER'] ?? 'root';
    $DB_PASS = $_ENV['DB_PASS'] ?? '';
}

try {
    $dsn = "mysql:host=" . $DB_HOST . ";dbname=" . $DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    $msg = 'Database connection failed: ' . $e->getMessage();
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}

$done = [];

// Add role column if missing
$st = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'role'");
if (!$st->fetch()) {
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'super_admin' AFTER password_hash");
    $done[] = 'Added column admin_users.role';
} else {
    $done[] = 'Column admin_users.role already exists';
}

// Add email column if missing
$st = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'email'");
if (!$st->fetch()) {
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN email VARCHAR(255) NULL AFTER role");
    $done[] = 'Added column admin_users.email';
} else {
    $done[] = 'Column admin_users.email already exists';
}

$pdo->exec("UPDATE admin_users SET role = 'super_admin' WHERE role = '' OR role IS NULL");
$done[] = 'Ensured existing users have role super_admin';

// Create activity log table
$pdo->exec("
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    username VARCHAR(50) NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_user (user_id)
)
");
$done[] = 'Table admin_activity_log ready';

$msg = "RBAC migration completed.\n" . implode("\n", $done);
if ($isCli) {
    echo $msg . PHP_EOL;
    exit(0);
}
header('Location: ../admin/dashboard.php?migrate_rbac=1');
exit(0);
