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
    http_response_code(500);
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
