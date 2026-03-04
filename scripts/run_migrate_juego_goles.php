<?php
if (php_sapi_name() === 'cli') {
    $DB_HOST = 'localhost';
    $DB_NAME = 'if0_41281527_valenciacf';
    $DB_USER = 'root';
    $DB_PASS = '';
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} else {
    require dirname(__DIR__) . '/config/database.php';
}
$sql = file_get_contents(dirname(__DIR__) . '/sql/migrate_juego_goles.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
$pdo->exec(trim($sql));
echo "juego_goles table created.\n";
