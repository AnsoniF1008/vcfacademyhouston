<?php
if (php_sapi_name() === 'cli') {
    $pdo = new PDO('mysql:host=localhost;dbname=if0_41281527_valenciacf;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} else {
    require dirname(__DIR__) . '/config/database.php';
}
$sql = file_get_contents(dirname(__DIR__) . '/sql/migrate_roster_sub_posicion.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
try {
    $pdo->exec(trim($sql));
    echo "roster.sub_posicion column added.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column sub_posicion already exists. OK.\n";
    } else {
        throw $e;
    }
}
