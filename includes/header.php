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
    <link rel="preload" href="<?= $base ?>/assets/vendor/fontawesome/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?= $base ?>/assets/vendor/fontawesome/css/all.min.css"></noscript>
    <?php else: ?>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>
    <?php endif; ?>
    <?php if (isset($heroSlides) && count($heroSlides) > 0): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" media="print" onload="this.media='all'">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet" crossorigin>
    <?php if (isset($hero_mobile_path) && !empty($hero_mobile_path) && isset($preload_image_path) && $preload_image_path !== ''): ?>
    <link rel="preload" as="image" href="<?= $base ?>/<?= htmlspecialchars($hero_mobile_path) ?>" media="(max-width: 768px)" fetchpriority="high">
    <link rel="preload" as="image" href="<?= $base ?>/<?= htmlspecialchars($preload_image_path) ?>" media="(min-width: 769px)" fetchpriority="high">
    <?php elseif (isset($preload_image_path) && !empty($preload_image_path)): ?>
    <link rel="preload" as="image" href="<?= $base ?>/<?= htmlspecialchars($preload_image_path) ?>" fetchpriority="high">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css?v=22">
    <?php if (!empty($is_admin)): ?><link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css?v=1"><?php endif; ?>
</head>
<body>
    <header class="vcf-header<?= !empty($is_admin) ? ' vcf-header-admin' : '' ?>">
        <nav class="navbar navbar-expand<?= !empty($is_admin) ? '-xxl' : '-lg' ?> navbar-dark">
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
                        <?php if ($is_admin):
                            $admin_current = basename($script);
                            $navLink = function($href, $label, $perm = null) use ($base, $admin_current) {
                                $can = $perm === null || (function_exists('admin_can') && admin_can($perm));
                                if (!$can) return;
                                $active = (basename($href) === $admin_current || ($admin_current === 'juegos.php' && strpos($href, 'torneos') !== false)) ? ' active' : '';
                                echo '<li class="nav-item"><a class="nav-link' . $active . '" href="' . htmlspecialchars($base . $href) . '">' . htmlspecialchars($label) . '</a></li>';
                            };
                        ?>
                        <?php $navLink('/admin/dashboard.php', 'Dashboard'); ?>
                        <?php
                        $content_pages = [
                            'hero_slider' => ['/admin/hero-slider.php', 'Hero'],
                            'jugador_mes' => ['/admin/jugador-mes.php', 'Jugador del Mes'],
                            'sedes' => ['/admin/sedes.php', 'Sedes'],
                            'torneos' => ['/admin/torneos.php', 'Torneos'],
                            'categorias' => ['/admin/categorias.php', 'Categories'],
                            'roster_edit' => ['/admin/roster.php', 'Roster'],
                            'motm' => ['/admin/motm.php', 'MOTM'],
                            'juegos' => ['/admin/juegos.php', 'Juegos'],
                        ];
                        $has_juegos = function_exists('admin_can') && (admin_can('juegos') || admin_can('juegos_live_score'));
                        $content_links = [];
                        foreach ($content_pages as $perm => $item) {
                            if ($perm === 'juegos') { if ($has_juegos) $content_links[] = $item; }
                            elseif ($perm === 'hero_slider') { if (function_exists('admin_can') && admin_can('hero_slider')) $content_links[] = $item; }
                            elseif (function_exists('admin_can') && admin_can($perm)) $content_links[] = $item;
                        }
                        $in_content = in_array($admin_current, ['hero-slider.php', 'jugador-mes.php', 'sedes.php', 'torneos.php', 'categorias.php', 'roster.php', 'motm.php', 'juegos.php'], true);
                        if (!empty($content_links)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle<?= $in_content ? ' active' : '' ?>" href="#" id="adminNavGestionar" role="button" data-bs-toggle="dropdown" aria-expanded="false">Gestionar</a>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="adminNavGestionar">
                                <?php foreach ($content_links as $item): ?>
                                <li><a class="dropdown-item<?= basename($item[0]) === $admin_current ? ' active' : '' ?>" href="<?= $base . $item[0] ?>"><?= htmlspecialchars($item[1]) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php endif; ?>
                        <?php if (function_exists('admin_can') && admin_can('*')): ?><li class="nav-item"><a class="nav-link<?= $admin_current === 'users.php' ? ' active' : '' ?>" href="<?= $base ?>/admin/users.php">Users</a></li><?php endif; ?>
                        <?php if (function_exists('admin_can') && (admin_can('*') || admin_can('activity_log_view'))): ?><li class="nav-item"><a class="nav-link<?= $admin_current === 'activity-log.php' ? ' active' : '' ?>" href="<?= $base ?>/admin/activity-log.php">Activity log</a></li><?php endif; ?>
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
