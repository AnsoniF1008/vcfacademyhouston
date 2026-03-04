#!/bin/bash
# Crear config/database.local.php en el servidor (ejecutar desde la raíz de la web, ej. htdocs)
# Uso: subir por FTP a htdocs, luego por SSH: chmod +x create-database-local-on-server.sh && ./create-database-local-on-server.sh
# O desde la raíz de la web: bash create-database-local-on-server.sh

set -e
CONFIG_DIR="config"
mkdir -p "$CONFIG_DIR"
cat > "$CONFIG_DIR/database.local.php" << 'ENDOFFILE'
<?php
$DB_HOST = 'sql101.infinityfree.com';
$DB_NAME = 'if0_41281527_valenciacf';
$DB_USER = 'if0_41281527';
$DB_PASS = 'TU_CONTRASEÑA_MYSQL';
ENDOFFILE
chmod 644 "$CONFIG_DIR/database.local.php"
echo "Creado $CONFIG_DIR/database.local.php correctamente."
