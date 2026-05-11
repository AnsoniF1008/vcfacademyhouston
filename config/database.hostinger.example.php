<?php
/**
 * Ejemplo de configuración para Hostinger.
 * En el servidor: copia este archivo a config/database.local.php
 * y sustituye los valores por los de tu base de datos en el panel de Hostinger
 * (Bases de datos MySQL → tu base de datos → host, usuario, contraseña).
 *
 * Para MySQL remoto del plan hosting, el panel suele mostrar un host tipo
 * srvNNNN.hstgr.io — úsalo tal cual aquí si NO es “localhost”.
 */

$DB_HOST = 'localhost';           // O el host remoto del panel (p. ej. srvNNNN.hstgr.io)
$DB_NAME = 'u123456789_valencia'; // Nombre de la BD que crees en el panel
$DB_USER = 'u123456789_admin';    // Usuario MySQL que te asigne Hostinger
$DB_PASS = 'TU_CONTRASEÑA';       // Contraseña que definas para ese usuario
