<?php
require dirname(__DIR__) . '/config/database.php';
$pdo->exec("DELETE FROM motm_votaciones WHERE id = 3");
echo "Test votation (id 3) removed. MOTM section stays; only test data was deleted.\n";
