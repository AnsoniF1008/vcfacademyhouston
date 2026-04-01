<?php
require dirname(__DIR__) . '/config/database.php';
$sql = file_get_contents(dirname(__DIR__) . '/sql/migrate_star_voting.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
$pdo->exec(trim($sql));
echo "star_votaciones, star_nominees, star_votes tables created.\n";
