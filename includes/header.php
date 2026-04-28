<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
require_once __DIR__ . '/../config/site_loader.php';
if (!isset($star_section_visible)) {
    $star_section_visible = false;
}
if (!isset($page_title)) {
    $page_title = 'VCF Academy Houston';
}
if (!isset($page_description)) {
    $page_description = 'Official Valencia CF youth academy in Katy & Houston, Texas. Competitive training, methodology, tournaments, and community. Train the Valencia way.';
}
if (!isset($body_class)) {
    $body_class = '';
}
$body_class = trim((string) $body_class);
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$is_admin = (strpos($script, '/admin/') !== false);
$base = dirname($script);
if ($is_admin) {
    $base = dirname($base);
}
$base = rtrim($base, '/');
if (!empty($vcf_public_redesign) && !$is_admin) {
    $body_class = trim($body_class . ' vcf-public-site');
}
if (!empty($is_admin)) {
    $body_class = trim($body_class . ' vcf-public-site vcf-admin-site');
}
if (!isset($meta_robots)) {
    $meta_robots = '';
}
$meta_robots = trim((string) $meta_robots);
if (!isset($vcf_public_redesign)) {
    $vcf_public_redesign = !$is_admin;
}
if (!isset($page_active) && !$is_admin) {
    $bn = basename($script);
    $vcfPageMap = [
        'index.php' => 'home',
        'contact.php' => 'contact',
        'recaudaciones.php' => 'support',
        'calendar.php' => 'calendar',
        'join.php' => 'join',
        'privacy.php' => 'privacy',
    ];
    $page_active = $vcfPageMap[$bn] ?? 'home';
}
if (!$is_admin) {
    require_once __DIR__ . '/public-nav-context.php';
}
$origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'vcfacademyhouston.com');
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($reqPath) || $reqPath === '') {
    $reqPath = '/';
}
$canonical_url = rtrim($origin, '/') . $reqPath;
$show_back_admin = !$is_admin && !empty($_SESSION['admin_logged']);
$vendor_root = __DIR__ . '/../assets/vendor';
$header_crest_file = null;
if (file_exists(__DIR__ . '/../assets/img/vcf-crest.svg')) {
    $header_crest_file = 'vcf-crest.svg';
} elseif (file_exists(__DIR__ . '/../assets/img/vcf-crest.png')) {
    $header_crest_file = 'vcf-crest.png';
} elseif (file_exists(__DIR__ . '/../assets/img/vfc-crest.svg')) {
    $header_crest_file = 'vfc-crest.svg';
}
$vcf_public_base = rtrim($origin, '/') . ($base === '' ? '' : $base);
$vcf_home_url = $vcf_public_base . '/';
$crestForLogo = $header_crest_file ?: 'favicon.svg';
$vcf_crest_version_qs = ($crestForLogo === 'vcf-crest.png') ? '?v=20260401' : '';
$vcf_logo_url = $vcf_public_base . '/assets/img/' . $crestForLogo;

/**
 * Absolute URL for assets (fixes missing slash when $base is empty at site root).
 */
$vcf_absolute_url = static function (string $relPath) use ($origin, $base): string {
    if (preg_match('#^https?://#i', $relPath)) {
        return $relPath;
    }
    $relPath = ltrim($relPath, '/');
    $root = rtrim(rtrim($origin, '/') . ($base === '' ? '' : $base), '/');
    return $root . '/' . $relPath;
};

// Open Graph / WhatsApp: prefer raster crest (SVG often has no preview). Optional $page_og_image overrides $page_image.
$og_image_path = '';
if (isset($page_og_image) && is_string($page_og_image) && $page_og_image !== '') {
    $og_image_path = $page_og_image;
} elseif (isset($page_image) && is_string($page_image) && $page_image !== '') {
    $og_image_path = $page_image;
}
if ($og_image_path === '') {
    if (file_exists(__DIR__ . '/../assets/img/vcf-crest.png')) {
        $og_image_path = 'assets/img/vcf-crest.png';
    } elseif (file_exists(__DIR__ . '/../assets/img/hero.jpg')) {
        $og_image_path = 'assets/img/hero.jpg';
    } elseif ($header_crest_file) {
        $og_image_path = 'assets/img/' . $header_crest_file;
    } else {
        $og_image_path = 'assets/img/favicon.svg';
    }
}
$og_image_url = $vcf_absolute_url($og_image_path);
$og_image_alt = 'VCF Academy Houston — Official Valencia CF Academy';
if (isset($page_og_image_alt) && is_string($page_og_image_alt) && $page_og_image_alt !== '') {
    $og_image_alt = $page_og_image_alt;
}
$og_image_width = 0;
$og_image_height = 0;
$og_image_mime = '';
if ($og_image_path !== '' && !preg_match('#^https?://#i', $og_image_path)) {
    $og_fs = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $og_image_path), '/');
    if (is_file($og_fs) && is_readable($og_fs)) {
        $dim = @getimagesize($og_fs);
        if (is_array($dim)) {
            $og_image_width = (int) ($dim[0] ?? 0);
            $og_image_height = (int) ($dim[1] ?? 0);
            $og_image_mime = (string) ($dim['mime'] ?? '');
        }
    }
}
$use_local_bootstrap_css = file_exists($vendor_root . '/bootstrap/css/bootstrap.min.css');
$use_local_fontawesome = file_exists($vendor_root . '/fontawesome/css/all.min.css');
$use_local_swiper_css = file_exists($vendor_root . '/swiper/swiper-bundle.min.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description ?? '') ?>">
    <?php if ($meta_robots !== ''): ?>
    <meta name="robots" content="<?= htmlspecialchars($meta_robots) ?>">
    <?php endif; ?>
    <?php
    $vcf_favicon_png = __DIR__ . '/../assets/img/vcf-crest.png';
    $vcf_has_favicon_png = is_file($vcf_favicon_png);
    ?>
    <?php if ($vcf_has_favicon_png): ?>
    <link rel="icon" type="image/png" sizes="48x48" href="<?= $base ?>/assets/img/vcf-crest.png?v=20260401">
    <link rel="shortcut icon" type="image/png" href="<?= $base ?>/assets/img/vcf-crest.png?v=20260401">
    <?php endif; ?>
    <link rel="icon" href="<?= $base ?>/assets/img/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $base ?>/assets/img/<?= $vcf_has_favicon_png ? 'vcf-crest.png?v=20260401' : 'favicon.svg' ?>">
    <meta name="theme-color" content="#080808">
    <meta name="geo.region" content="US-TX">
    <meta name="geo.placename" content="Houston, Katy">
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="VCF Academy Houston">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description ?? '') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta property="og:locale" content="en_US">
    <meta property="og:image" content="<?= htmlspecialchars($og_image_url) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($og_image_url) ?>">
    <meta property="og:image:alt" content="<?= htmlspecialchars($og_image_alt) ?>">
    <?php if ($og_image_width > 0 && $og_image_height > 0): ?>
    <meta property="og:image:width" content="<?= (int) $og_image_width ?>">
    <meta property="og:image:height" content="<?= (int) $og_image_height ?>">
    <?php endif; ?>
    <?php if ($og_image_mime !== ''): ?>
    <meta property="og:image:type" content="<?= htmlspecialchars($og_image_mime) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description ?? '') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_image_url) ?>">
    <meta name="twitter:image:alt" content="<?= htmlspecialchars($og_image_alt) ?>">
    <?php if (!$is_admin): ?>
    <?php
    $vcf_same_as = ['https://www.valenciacf.com'];
    if (isset($vcf_site) && is_array($vcf_site)) {
        foreach (['instagram_url', 'facebook_url', 'youtube_url', 'x_url'] as $vcf_social_key) {
            $vcf_u = trim((string) ($vcf_site[$vcf_social_key] ?? ''));
            if ($vcf_u !== '' && preg_match('#^https?://#i', $vcf_u)) {
                $vcf_same_as[] = $vcf_u;
            }
        }
    }
    $vcf_same_as = array_values(array_unique($vcf_same_as));
    $vcf_schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'SportsOrganization',
                '@id' => $vcf_home_url . '#organization',
                'name' => 'VCF Academy Houston',
                'alternateName' => 'Valencia CF Academy Houston',
                'url' => $vcf_home_url,
                'logo' => $vcf_logo_url,
                'image' => $og_image_url,
                'description' => $page_description,
                'sport' => 'Soccer',
                'sameAs' => $vcf_same_as,
                'areaServed' => [
                    ['@type' => 'City', 'name' => 'Houston', 'containedInPlace' => ['@type' => 'State', 'name' => 'Texas', 'containedInPlace' => ['@type' => 'Country', 'name' => 'United States']]],
                    ['@type' => 'City', 'name' => 'Katy', 'containedInPlace' => ['@type' => 'State', 'name' => 'Texas']],
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $vcf_home_url . '#website',
                'name' => 'VCF Academy Houston',
                'url' => $vcf_home_url,
                'inLanguage' => 'en-US',
                'description' => $page_description,
                'publisher' => ['@id' => $vcf_home_url . '#organization'],
            ],
        ],
    ];
    ?>
    <script type="application/ld+json"><?= json_encode($vcf_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
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
    <?php /* Swiper CSS removed — hero uses custom slideshow, not Swiper library */ ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700&family=Hanken+Grotesk:wght@400;600;700&display=swap" rel="stylesheet" crossorigin>
    <?php if (!empty($is_admin)): ?>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&display=swap" rel="stylesheet" crossorigin>
    <?php endif; ?>
    <?php if (isset($hero_mobile_path) && !empty($hero_mobile_path) && isset($preload_image_path) && $preload_image_path !== ''): ?>
    <link rel="preload" as="image" href="<?= $base ?>/<?= htmlspecialchars($hero_mobile_path) ?>" media="(max-width: 768px)" fetchpriority="high">
    <link rel="preload" as="image" href="<?= $base ?>/<?= htmlspecialchars($preload_image_path) ?>" media="(min-width: 769px)" fetchpriority="high">
    <?php elseif (isset($preload_image_path) && !empty($preload_image_path)): ?>
    <link rel="preload" as="image" href="<?= $base ?>/<?= htmlspecialchars($preload_image_path) ?>" fetchpriority="high">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css?v=33">
    <?php if ((!empty($vcf_public_redesign) && !$is_admin) || !empty($is_admin)): ?>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/vcf-style.css?v=19">
    <?php endif; ?>
    <?php if (isset($reels) && count($reels) > 0 && empty($vcf_public_redesign)): ?><link rel="stylesheet" href="<?= $base ?>/assets/css/reels-carousel.css?v=2"><?php endif; ?>
    <?php if (!empty($is_admin)): ?><link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css?v=3"><?php endif; ?>
</head>
<body<?php if ($body_class !== ''): ?> class="<?= htmlspecialchars($body_class) ?>"<?php endif; ?>>
    <?php if (!empty($vcf_public_redesign) && !$is_admin): ?>
    <?php require __DIR__ . '/vcf-public-nav.php'; ?>
    <?php elseif (!empty($is_admin)): ?>
    <?php require __DIR__ . '/vcf-admin-nav.php'; ?>
    <?php else: ?>
    <header class="vcf-header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $base ?>/index.php">
                    <?php if ($header_crest_file): ?>
                        <img src="<?= $base ?>/assets/img/<?= $header_crest_file ?><?= $vcf_crest_version_qs ?>" alt="" class="vcf-navbar-crest vcf-logo-anim--premium" width="72" height="72">
                    <?php endif; ?>
                    <span class="vcf-logo-text">VCF</span>
                    <span class="vcf-academy-text">Academy Houston</span>
                </a>
                <button class="navbar-toggler border-orange" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#hero">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#methodology">Methodology</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#grounds">Grounds</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#roster">Roster</a></li>
                        <?php if ((isset($motmOpen) && $motmOpen) || (isset($motmWinner) && $motmWinner)): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#motm">Man of the Match</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#tournaments">Tournaments</a></li>
                        <?php if (!empty($star_section_visible)): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/index.php#star">Star of the Month</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/recaudaciones.php">Support</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/calendar.php">Calendar</a></li>
                        <?php if ($show_back_admin): ?>
                        <li class="nav-item"><a class="nav-link fw-bold" href="<?= $base ?>/admin/dashboard.php" style="color: #FF6600 !important;"><i class="fas fa-cog me-1"></i> Back to Admin</a></li>
                        <?php else: ?>
                        <li class="nav-item"><a class="nav-link vcf-nav-login" href="<?= $base ?>/admin/"><i class="fas fa-sign-in-alt me-1" aria-hidden="true"></i> Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <?php endif; ?>
    <main>
