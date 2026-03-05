<?php
/**
 * Prueba de conexión a la BD remota.
 * Uso: php scripts/test_db_connection.php
 * Si falla por "access denied to database", prueba nombre de BD alternativo (6K3gelNG).
 */

$root = dirname(__DIR__);
if (!is_file($root . '/config/database.local.php')) {
    fwrite(STDERR, "No existe config/database.local.php.\n");
    exit(1);
}
require $root . '/config/database.local.php';

$tryNames = [$DB_NAME];
// En Hostinger a veces la BD tiene el mismo sufijo que el usuario (6K3gelNG)
if ($DB_NAME === 'u766140586_db_6K3geING') {
    $tryNames[] = 'u766140586_db_6K3gelNG';
}

foreach ($tryNames as $name) {
    $dsn = "mysql:host={$DB_HOST};dbname={$name};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "OK: Conexión correcta a {$DB_HOST} / {$name} con usuario {$DB_USER}.\n";
        if ($name !== $DB_NAME) {
            echo "AVISO: La BD correcta es '{$name}'. Actualiza config/database.local.php: \$DB_NAME = '{$name}';\n";
        }
        exit(0);
    } catch (PDOException $e) {
        echo "Intentando {$name}: " . $e->getMessage() . "\n";
    }
}

fwrite(STDERR, "No se pudo conectar con ningún nombre de BD. Revisa usuario/contraseña y que el usuario esté asignado a la BD en el panel de Hostinger.\n");
exit(1);
