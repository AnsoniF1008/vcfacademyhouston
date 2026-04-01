<?php
require dirname(__DIR__) . '/config/database.php';
$sql = file_get_contents(dirname(__DIR__) . '/sql/migrate_categoria_horarios.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
$pdo->exec(trim($sql));
echo "categoria_horarios table created. You can now add multiple training days per category in Admin > Categories.\n";
