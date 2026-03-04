<?php
/**
 * Despliegue a InfinityFree por FTP.
 * Uso: php deploy.php
 * Requiere: config/deploy-credentials.php con los datos FTP y MySQL.
 */
declare(strict_types=1);

$baseDir = __DIR__;

$credsFile = $baseDir . '/config/deploy-credentials.php';
if (!is_file($credsFile)) {
    echo "ERROR: Crea config/deploy-credentials.php a partir de config/deploy-credentials.example.php y rellena FTP y MySQL.\n";
    exit(1);
}

$creds = require $credsFile;
$ftpHost = $creds['ftp_host'] ?? '';
$ftpUser = $creds['ftp_user'] ?? '';
$ftpPass = trim((string) ($creds['ftp_pass'] ?? ''));
$ftpPort = (int) ($creds['ftp_port'] ?? 21);
$htdocs  = $creds['ftp_htdocs'] ?? 'htdocs';

if (empty($ftpHost) || empty($ftpUser) || $ftpPass === '' || $ftpPass === 'TU_CONTRASEÑA_FTP') {
    echo "ERROR: Rellena ftp_host, ftp_user y ftp_pass en config/deploy-credentials.php\n";
    exit(1);
}

echo "Conectando a FTP {$ftpHost}...\n";
$ftp = @ftp_connect($ftpHost, $ftpPort, 15);
if (!$ftp) {
    echo "ERROR: No se pudo conectar al servidor FTP.\n";
    exit(1);
}
if (!@ftp_login($ftp, $ftpUser, $ftpPass)) {
    @ftp_close($ftp);
    echo "ERROR: Usuario o contraseña FTP incorrectos.\n";
    echo "Comprueba en el panel (FTP Details) que usuario y contraseña coincidan.\n";
    echo "Prueba a conectar con FileZilla a ftpupload.net con los mismos datos para verificar.\n";
    exit(1);
}
ftp_pasv($ftp, true);
echo "Conectado. Entrando en {$htdocs}...\n";
if (!@ftp_chdir($ftp, $htdocs)) {
    if (!@ftp_mkdir($ftp, $htdocs)) {
        echo "ERROR: No se pudo entrar o crear la carpeta {$htdocs}.\n";
        ftp_close($ftp);
        exit(1);
    }
    ftp_chdir($ftp, $htdocs);
}

$skipNames = ['.git', '.idea', '.vscode', 'node_modules', 'deploy.php', 'deploy-credentials.php', 'DEPLOY-INFINITYFREE.md', 'SETUP.md', 'README.md', '.gitignore', '.DS_Store'];
$skipPaths = [__DIR__ . '\\.git', __DIR__ . '\\config\\deploy-credentials.php'];

function uploadDir($ftp, string $localDir, string $remoteDir, array $skipNames): void {
    $items = @scandir($localDir);
    if (!$items) return;
    foreach ($items as $name) {
        if ($name === '.' || $name === '..') continue;
        if (in_array($name, $skipNames, true)) continue;
        $localPath = $localDir . DIRECTORY_SEPARATOR . $name;
        $remotePath = $remoteDir . '/' . $name;
        if (is_dir($localPath)) {
            @ftp_mkdir($ftp, $remotePath);
            uploadDir($ftp, $localPath, $remotePath, $skipNames);
        } else {
            echo "  Subiendo: " . str_replace($localDir . DIRECTORY_SEPARATOR, '', $localPath) . "\n";
            ftp_put($ftp, $remotePath, $localPath, FTP_BINARY);
        }
    }
}

echo "Subiendo archivos...\n";
$rootFiles = ['index.php', 'contact.php', 'privacy.php', 'join.php', 'calendar.php'];
foreach ($rootFiles as $f) {
    $local = $baseDir . DIRECTORY_SEPARATOR . $f;
    if (is_file($local)) {
        echo "  Subiendo: {$f}\n";
        ftp_put($ftp, $htdocs . '/' . $f, $local, FTP_BINARY);
    }
}
uploadDir($ftp, $baseDir . DIRECTORY_SEPARATOR . 'admin', $htdocs . '/admin', $skipNames);
uploadDir($ftp, $baseDir . DIRECTORY_SEPARATOR . 'assets', $htdocs . '/assets', $skipNames);
uploadDir($ftp, $baseDir . DIRECTORY_SEPARATOR . 'config', $htdocs . '/config', array_merge($skipNames, ['deploy-credentials.php']));
uploadDir($ftp, $baseDir . DIRECTORY_SEPARATOR . 'includes', $htdocs . '/includes', $skipNames);
uploadDir($ftp, $baseDir . DIRECTORY_SEPARATOR . 'sql', $htdocs . '/sql', $skipNames);

$dbPass = $creds['db_pass'] ?? '';
if (!empty($dbPass) && $dbPass !== 'TU_CONTRASEÑA_MYSQL') {
    $dbLocal = "<?php\n\$DB_HOST = " . var_export($creds['db_host'] ?? 'sql101.infinityfree.com', true) . ";\n";
    $dbLocal .= "\$DB_NAME = " . var_export($creds['db_name'] ?? 'if0_41281527_valenciacf', true) . ";\n";
    $dbLocal .= "\$DB_USER = " . var_export($creds['db_user'] ?? 'if0_41281527', true) . ";\n";
    $dbLocal .= "\$DB_PASS = " . var_export($dbPass, true) . ";\n";
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $dbLocal);
    rewind($stream);
    echo "  Creando config/database.local.php en el servidor...\n";
    ftp_fput($ftp, $htdocs . '/config/database.local.php', $stream, FTP_ASCII);
    fclose($stream);
}

ftp_close($ftp);
echo "Listo. Revisa tu sitio en https://valenciafc-academyhouston.rf.gd\n";
echo "Admin: https://valenciafc-academyhouston.rf.gd/admin/ (admin / password)\n";
exit(0);
