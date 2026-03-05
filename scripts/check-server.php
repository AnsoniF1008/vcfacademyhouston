<?php
/**
 * Diagnóstico en el servidor. Sube este archivo a la raíz del sitio en Hostinger,
 * abre https://vcfacademyhouston.com/check-server.php y luego BÓRRALO por seguridad.
 */
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Diagnóstico</title>';
echo '<style>body{font-family:sans-serif;background:#1a1a1a;color:#eee;margin:2rem;}';
echo 'h1{color:#f60;} .ok{color:#6f6;} .err{color:#f66;} pre{background:#333;padding:1rem;overflow:auto;}</style></head><body>';
echo '<h1>Diagnóstico del servidor</h1>';

echo '<p><strong>PHP:</strong> ' . phpversion() . '</p>';
echo '<p><strong>Document root:</strong> <code>' . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? '') . '</code></p>';
echo '<p><strong>Script:</strong> <code>' . htmlspecialchars(__FILE__) . '</code></p>';

$configPath = is_file(__DIR__ . '/../config/database.local.php')
    ? __DIR__ . '/../config/database.local.php'
    : __DIR__ . '/config/database.local.php';
if (is_file($configPath)) {
    echo '<p class="ok">config/database.local.php existe.</p>';
    ob_start();
    $DB_HOST = $DB_NAME = $DB_USER = $DB_PASS = '';
    require $configPath;
    ob_end_clean();
    echo '<p>DB_HOST=' . htmlspecialchars($DB_HOST ?? '') . ', DB_NAME=' . htmlspecialchars($DB_NAME ?? '') . ', usuario configurado.</p>';
    try {
        $dsn = "mysql:host=" . ($DB_HOST ?? 'localhost') . ";dbname=" . ($DB_NAME ?? '') . ";charset=utf8mb4";
        $pdo = new PDO($dsn, $DB_USER ?? '', $DB_PASS ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo '<p class="ok">Conexión a la base de datos: OK.</p>';
    } catch (PDOException $e) {
        echo '<p class="err">Base de datos: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    echo '<p class="err">No se encuentra config/database.local.php. Créalo en el servidor con los datos MySQL de Hostinger (copia config/database.hostinger.example.php).</p>';
}

$rootIndex = is_file(__DIR__ . '/../index.php') ? __DIR__ . '/../index.php' : __DIR__ . '/index.php';
echo '<p><strong>index.php en raíz:</strong> ' . (is_file($rootIndex) ? 'sí' : 'no') . '</p>';
echo '<p><em>Elimina este archivo (check-server.php) después de revisar.</em></p>';
echo '</body></html>';
