<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($page_title)) {
    $page_title = 'VCF Academy Houston';
}
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$base = dirname($script);
$is_admin = (strpos($script, '/admin/') !== false);
if ($is_admin) {
    $base = dirname($base);
}
$base = rtrim($base, '/');
$show_back_admin = !$is_admin && !empty($_SESSION['admin_logged']);
$vendor_root = __DIR__ . '/../assets/vendor';
$header_crest_file = null;
if (file_exists(__DIR__ . '/../assets/img/vcf-crest.svg')) {
    $header_crest_file = 'vcf-crest.svg';
} elseif (file_exists(__DIR__ . '/../assets/img/vcf-crest.png')) {
    $header_crest_file = 'vcf-crest.png';
}
$use_local_bootstrap_css = file_exists($vendor_root . '/bootstrap/css/bootstrap.min.css');
$use_local_fontawesome = file_exists($vendor_root . '/fontawesome/css/all.min.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <?php if ($use_local_bootstrap_css): ?>
    <link href="<?= $base ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <?php else: ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <?php if ($use_local_fontawesome): ?>
    <link rel="stylesheet" href="<?= $base ?>/assets/vendor/fontawesome/css/all.min.css">
    <?php else: ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
    <header class="vcf-header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $is_admin ? $base . '/admin/dashboard.php' : $base . '/index.php' ?>">
                    <?php if ($header_crest_file): ?>
                        <img src="<?= $base ?>/assets/img/<?= $header_crest_file ?>" alt="" class="vcf-navbar-crest" width="72" height="72">
                    <?php endif; ?>
                    <span class="vcf-logo-text">VCF</span>
                    <span class="vcf-academy-text">Academy Houston</span>
                </a>
                <button class="navbar-toggler border-orange" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if ($is_admin): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/admin/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/admin/jugador-mes.php">Jugador del Mes</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/admin/sedes.php">Sedes</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/admin/torneos.php">Torneos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/admin/categorias.php">Categories</a></li>
                        <?php if (!empty($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/admin/users.php">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/admin/activity-log.php">Activity log</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php">View site</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/admin/logout.php">Log out</a></li>
                        <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#hero">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#methodology">Methodology</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#grounds">Grounds</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#roster">Roster</a></li>
                        <?php if ((isset($motmOpen) && $motmOpen) || (isset($motmWinner) && $motmWinner)): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#motm">Man of the Match</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#tournaments">Tournaments</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#star">Star of the Month</a></li>
                        <?php if ($show_back_admin): ?>
                        <li class="nav-item"><a class="nav-link fw-bold" href="<?= $base ?>/admin/dashboard.php" style="color: #FF6600 !important;"><i class="fas fa-cog me-1"></i> Back to Admin</a></li>
                        <?php else: ?>
                        <li class="nav-item"><a class="nav-link vcf-nav-login" href="<?= $base ?>/admin/"><i class="fas fa-sign-in-alt me-1"></i> Acceder</a></li>
                        <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main>
