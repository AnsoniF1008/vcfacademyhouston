<?php
/**
 * Admin shell: same topbar + sticky nav as public redesign (vcf-style.css).
 * Expects: $base, $script, $header_crest_file, admin auth loaded (admin_can).
 */
require_once __DIR__ . '/../admin/includes/csrf.php';
$vcf_base_url = ($base === '' ? '/' : $base . '/');
$admin_current = basename($script);
$vcf_logout_token = csrf_token();
$vcf_logout_action = htmlspecialchars($base) . '/admin/logout.php';

$content_pages = [
    'hero_slider' => ['/admin/hero-slider.php', 'Hero'],
    'match_reels' => ['/admin/match-reels.php', 'Match Reels'],
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
    if ($perm === 'juegos') {
        if ($has_juegos) {
            $content_links[] = $item;
        }
    } elseif ($perm === 'hero_slider') {
        if (function_exists('admin_can') && admin_can('hero_slider')) {
            $content_links[] = $item;
        }
    } elseif (function_exists('admin_can') && admin_can($perm)) {
        $content_links[] = $item;
    }
}
$in_content = in_array($admin_current, ['hero-slider.php', 'match-reels.php', 'jugador-mes.php', 'sedes.php', 'torneos.php', 'categorias.php', 'roster.php', 'motm.php', 'juegos.php'], true);
?>
<!-- ══ TOPBAR (admin) ══ -->
<div class="vcf-topbar">
  <div class="vcf-topbar__inner">
    <nav class="vcf-topbar__links" aria-label="Quick links to public site">
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php">View site</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#hero">Home</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>calendar.php">Calendar</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>join.php">Join</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>recaudaciones.php">Support</a>
    </nav>
    <span class="vcf-topbar__badge">Admin &middot; VCF Academy Houston</span>
  </div>
</div>

<!-- ══ NAVBAR (admin) ══ -->
<header class="vcf-nav vcf-nav--admin" id="vcf-admin-nav">
  <div class="vcf-nav__inner">
    <nav class="vcf-nav__links vcf-nav__links--admin" aria-label="Admin navigation">
      <a href="<?= htmlspecialchars($base) ?>/admin/dashboard.php" class="<?= $admin_current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
      <?php if (!empty($content_links)): ?>
      <div class="dropdown vcf-nav__dropdown">
        <button class="vcf-nav__admin-dropdown-btn dropdown-toggle<?= $in_content ? ' active' : '' ?>" type="button" id="adminNavGestionar" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true">Gestionar</button>
        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="adminNavGestionar">
          <?php foreach ($content_links as $item): ?>
          <li><a class="dropdown-item<?= basename($item[0]) === $admin_current ? ' active' : '' ?>" href="<?= htmlspecialchars($base . $item[0]) ?>"><?= htmlspecialchars($item[1]) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      <?php if (function_exists('admin_can') && admin_can('*')): ?>
      <a href="<?= htmlspecialchars($base) ?>/admin/users.php" class="<?= $admin_current === 'users.php' ? 'active' : '' ?>">Users</a>
      <?php endif; ?>
      <?php if (function_exists('admin_can') && (admin_can('*') || admin_can('activity_log_view'))): ?>
      <a href="<?= htmlspecialchars($base) ?>/admin/activity-log.php" class="<?= $admin_current === 'activity-log.php' ? 'active' : '' ?>">Activity log</a>
      <?php endif; ?>
      <a href="<?= htmlspecialchars($base) ?>/admin/logout.php" class="js-logout" data-confirm="¿Cerrar sesión?">Log out</a>
    </nav>

    <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php" class="vcf-nav__cta">View site</a>

    <button class="vcf-nav__burger" id="vcf-admin-burger" type="button" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>

  <nav class="vcf-nav__mobile vcf-nav__mobile--admin" id="vcf-admin-mobile-menu" aria-label="Admin menu">
    <a href="<?= htmlspecialchars($base) ?>/admin/dashboard.php" class="<?= $admin_current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <?php foreach ($content_links as $item): ?>
    <a href="<?= htmlspecialchars($base . $item[0]) ?>" class="<?= basename($item[0]) === $admin_current ? 'active' : '' ?>"><?= htmlspecialchars($item[1]) ?></a>
    <?php endforeach; ?>
    <?php if (function_exists('admin_can') && admin_can('*')): ?>
    <a href="<?= htmlspecialchars($base) ?>/admin/users.php" class="<?= $admin_current === 'users.php' ? 'active' : '' ?>">Users</a>
    <?php endif; ?>
    <?php if (function_exists('admin_can') && (admin_can('*') || admin_can('activity_log_view'))): ?>
    <a href="<?= htmlspecialchars($base) ?>/admin/activity-log.php" class="<?= $admin_current === 'activity-log.php' ? 'active' : '' ?>">Activity log</a>
    <?php endif; ?>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php">View site</a>
    <a href="<?= htmlspecialchars($base) ?>/admin/logout.php" class="js-logout" data-confirm="¿Cerrar sesión?">Log out</a>
  </nav>
</header>

<form id="vcf-logout-form" method="post" action="<?= $vcf_logout_action ?>" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($vcf_logout_token) ?>">
</form>

<script>
(function(){
  var b = document.getElementById('vcf-admin-burger');
  var m = document.getElementById('vcf-admin-mobile-menu');
  var root = document.getElementById('vcf-admin-nav');
  if (b && m && root) {
    b.addEventListener('click', function(e){ e.stopPropagation(); m.classList.toggle('open'); });
    document.addEventListener('click', function(e){
      if (!e.target.closest('#vcf-admin-nav')) m.classList.remove('open');
    });
  }

  // Intercept any logout link (nav, footer, mobile menu) and submit a POST form
  // with CSRF token. The /admin/logout.php endpoint requires POST to prevent
  // CSRF logout via GET links / <img> tags. Delegated on document so links
  // rendered after this script (e.g. the footer) are also covered.
  document.addEventListener('click', function(e){
    var link = e.target.closest && e.target.closest('a.js-logout');
    if (!link) return;
    e.preventDefault();
    var msg = link.getAttribute('data-confirm');
    if (msg && !confirm(msg)) return;
    var form = document.getElementById('vcf-logout-form');
    if (form) {
      form.action = link.getAttribute('href') || form.action;
      form.submit();
    } else {
      window.location.href = link.getAttribute('href');
    }
  });
})();
</script>
