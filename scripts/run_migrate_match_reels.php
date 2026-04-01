<?php
// Usa la misma conexión que la web (config/database.php → database.local.php si existe)
require dirname(__DIR__) . '/config/database.php';
$sql = file_get_contents(dirname(__DIR__) . '/sql/migrate_match_reels.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
$pdo->exec(trim($sql));
echo "match_reels table created.\n";
