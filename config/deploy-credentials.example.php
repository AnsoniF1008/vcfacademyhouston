<?php
/**
 * Copia este archivo a deploy-credentials.php y rellena los datos.
 * deploy-credentials.php está en .gitignore (no se sube a git).
 */
return [
    'ftp_host'     => 'ftp.vcfacademyhouston.com',
    'ftp_user'     => 'u766140586.AnsoniF',
    'ftp_pass'     => 'TU_CONTRASEÑA_FTP',
    'ftp_port'     => 21,
    'ftp_htdocs'   => 'public_html',
    // FTP Hostinger (scripts/deploy-hostinger-ftp.ps1): prioridad sobre valores por defecto del script.
    // host: suele ser ftp.tudominio.com; la IP del panel también vale si el modo pasivo falla con el hostname.
    'hostinger_ftp_host' => 'ftp.vcfacademyhouston.com',
    'hostinger_ftp_user' => 'u766140586.AnsoniF',
    'hostinger_ftp_pass' => 'TU_CONTRASEÑA_FTP_HOSTINGER',
    'hostinger_ftp_port' => 21,
    // Carpeta del sitio respecto a la raíz FTP.
    // Hostinger (muy habitual): la cuenta FTP ya abre en public_html → usa '' para no crear public_html/public_html/.
    // Solo usa 'public_html' si al conectar por FTP ves public_html como subcarpeta (raíz = home de la cuenta).
    'hostinger_ftp_remote_path' => '',
    'db_host'      => 'sql101.infinityfree.com',
    'db_name'      => 'if0_41281527_valenciacf',
    'db_user'      => 'if0_41281527',
    'db_pass'      => 'TU_CONTRASEÑA_MYSQL',
];
