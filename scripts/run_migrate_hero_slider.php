<?php
if (php_sapi_name() === 'cli') {
    $pdo = new PDO('mysql:host=localhost;dbname=if0_41281527_valenciacf;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} else {
    require dirname(__DIR__) . '/config/database.php';
}
$sql = file_get_contents(dirname(__DIR__) . '/sql/migrate_hero_slider.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
$pdo->exec(trim($sql));
echo "hero_slides table created.\n";
