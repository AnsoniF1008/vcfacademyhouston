<?php
/**
 * Dark redesign: topbar + sticky nav (valenciacf-style).
 * Expects: $base, $page_active, optional $motmOpen, $motmWinner, $star_section_visible, $show_back_admin
 */
if (!isset($page_active)) {
    $page_active = 'home';
}
$vcf_base_url = ($base === '' ? '/' : $base . '/');
$navMotm = !empty($motmOpen) || !empty($motmWinner);
$navStar = !empty($star_section_visible);
$vcf_nav_crest = $header_crest_file ?? null;
if ($vcf_nav_crest === null && file_exists(__DIR__ . '/../assets/img/vfc-crest.svg')) {
    $vcf_nav_crest = 'vfc-crest.svg';
}
?>
<!-- ══ TOPBAR ══ -->
<div class="vcf-topbar">
  <div class="vcf-topbar__inner">
    <nav class="vcf-topbar__links">
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#hero" class="<?= $page_active === 'home' ? 'active' : '' ?>">Home</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#methodology" class="<?= $page_active === 'methodology' ? 'active' : '' ?>">Methodology</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#grounds" class="<?= $page_active === 'grounds' ? 'active' : '' ?>">Grounds</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#roster" class="<?= $page_active === 'roster' ? 'active' : '' ?>">Roster</a>
      <?php if ($navMotm): ?>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#motm">MOTM</a>
      <?php endif; ?>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#tournaments" class="<?= $page_active === 'tournaments' ? 'active' : '' ?>">Tournaments</a>
      <?php if ($navStar): ?>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#star">Star</a>
      <?php endif; ?>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>calendar.php" class="<?= $page_active === 'calendar' ? 'active' : '' ?>">Calendar</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>join.php" class="<?= $page_active === 'join' ? 'active' : '' ?>">Join</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>recaudaciones.php" class="<?= $page_active === 'support' ? 'active' : '' ?>">Support</a>
    </nav>
    <span class="vcf-topbar__badge">VCF Academy Houston &middot; Katy, TX</span>
  </div>
</div>

<!-- ══ NAVBAR ══ -->
<header class="vcf-nav" id="vcf-nav">
  <div class="vcf-nav__inner">
    <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php" class="vcf-nav__logo">
      <?php if ($vcf_nav_crest): ?>
      <img class="vcf-nav__crest" src="<?= htmlspecialchars($base) ?>/assets/img/<?= htmlspecialchars($vcf_nav_crest) ?>" alt="Valencia CF" width="44" height="58" loading="eager" decoding="async">
      <?php else: ?>
      <div class="vcf-nav__flag" aria-hidden="true">
        <span class="f1"></span>
        <span class="f2"></span>
        <span class="f3"></span>
      </div>
      <?php endif; ?>
      <div class="vcf-nav__text">
        <span class="t1">VCF Academy</span>
        <span class="t2">Houston &middot; Official</span>
      </div>
    </a>

    <nav class="vcf-nav__links">
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#hero" class="<?= $page_active === 'home' ? 'active' : '' ?>">Home</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#methodology" class="<?= $page_active === 'methodology' ? 'active' : '' ?>">Methodology</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#grounds" class="<?= $page_active === 'grounds' ? 'active' : '' ?>">Grounds</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#roster" class="<?= $page_active === 'roster' ? 'active' : '' ?>">Roster</a>
      <?php if ($navMotm): ?>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#motm">MOTM</a>
      <?php endif; ?>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#tournaments" class="<?= $page_active === 'tournaments' ? 'active' : '' ?>">Tournaments</a>
      <?php if ($navStar): ?>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#star">Star</a>
      <?php endif; ?>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>calendar.php" class="<?= $page_active === 'calendar' ? 'active' : '' ?>">Calendar</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>join.php" class="<?= $page_active === 'join' ? 'active' : '' ?>">Join</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>recaudaciones.php" class="<?= $page_active === 'support' ? 'active' : '' ?>">Support</a>
    </nav>
    <div class="vcf-nav__actions">
      <?php if (!empty($show_back_admin)): ?>
      <a href="<?= htmlspecialchars($base) ?>/admin/dashboard.php" class="vcf-nav__admin-link">Dashboard</a>
      <?php else: ?>
      <a href="<?= htmlspecialchars($base) ?>/admin/" class="vcf-nav__admin-link">Admin</a>
      <?php endif; ?>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>contact.php" class="vcf-nav__cta">Contact Us</a>
    </div>

    <button class="vcf-nav__burger" id="vcf-burger" type="button" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>

  <nav class="vcf-nav__mobile" id="vcf-mobile-menu">
    <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#hero">Home</a>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#methodology">Methodology</a>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#grounds">Grounds</a>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#roster">Roster</a>
    <?php if ($navMotm): ?>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#motm">MOTM</a>
    <?php endif; ?>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#tournaments">Tournaments</a>
    <?php if ($navStar): ?>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#star">Star</a>
    <?php endif; ?>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>calendar.php">Calendar</a>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>join.php" class="<?= $page_active === 'join' ? 'active' : '' ?>">Join</a>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>recaudaciones.php">Support</a>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>contact.php" class="<?= $page_active === 'contact' ? 'active' : '' ?>">Contact</a>
    <?php if (!empty($show_back_admin)): ?>
    <a href="<?= htmlspecialchars($base) ?>/admin/dashboard.php">Dashboard</a>
    <?php else: ?>
    <a href="<?= htmlspecialchars($base) ?>/admin/">Admin</a>
    <?php endif; ?>
  </nav>
</header>

<script>
(function(){
  var b = document.getElementById('vcf-burger');
  var m = document.getElementById('vcf-mobile-menu');
  if (b && m) {
    b.addEventListener('click', function(e){ e.stopPropagation(); m.classList.toggle('open'); });
    document.addEventListener('click', function(e){
      if (!e.target.closest('#vcf-nav')) m.classList.remove('open');
    });
  }
})();
</script>
