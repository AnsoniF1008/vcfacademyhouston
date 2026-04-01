<?php
/**
 * VCF Academy Houston — Shared Header
 * Incluir al inicio de cada página: <?php include 'includes/header.php'; ?>
 * $page_active: string con el nombre de la página activa ('home','roster','tournaments','calendar','support','contact')
 */
if (!isset($page_active)) $page_active = 'home';
$base_url = '/'; // Ajustar si el sitio está en subdirectorio
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="VCF Academy Houston — Official Valencia CF Academy Program in Katy, TX. Developing young footballers with identity, effort and intelligence.">
  <meta property="og:title" content="VCF Academy Houston">
  <meta property="og:description" content="Official Valencia CF Academy Program in Houston, TX. Join the B13 team in Katy.">
  <meta property="og:image" content="<?= $base_url ?>assets/img/vcf-crest.png">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://vcfacademyhouston.com">
  <title><?= isset($page_title) ? htmlspecialchars($page_title).' — ' : '' ?>VCF Academy Houston</title>
  <link rel="icon" type="image/png" href="<?= $base_url ?>assets/img/vcf-crest.png">
  <!-- VCF Stylesheet -->
  <link rel="stylesheet" href="<?= $base_url ?>assets/css/vcf-style.css">
  <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body>

<!-- ══ TOPBAR ══ -->
<div class="vcf-topbar">
  <div class="vcf-topbar__inner">
    <nav class="vcf-topbar__links">
      <a href="<?= $base_url ?>index.php#hero"        class="<?= $page_active==='home'         ? 'active' : '' ?>">Home</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= $base_url ?>index.php#methodology" class="<?= $page_active==='methodology'  ? 'active' : '' ?>">Methodology</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= $base_url ?>index.php#grounds"     class="<?= $page_active==='grounds'      ? 'active' : '' ?>">Grounds</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= $base_url ?>index.php#roster"      class="<?= $page_active==='roster'       ? 'active' : '' ?>">Roster</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= $base_url ?>index.php#tournaments" class="<?= $page_active==='tournaments'  ? 'active' : '' ?>">Tournaments</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= $base_url ?>calendar.php"          class="<?= $page_active==='calendar'     ? 'active' : '' ?>">Calendar</a>
      <div class="vcf-topbar__sep"></div>
      <a href="<?= $base_url ?>recaudaciones.php"     class="<?= $page_active==='support'      ? 'active' : '' ?>">Support</a>
    </nav>
    <span class="vcf-topbar__badge">VCF Academy Houston &middot; Katy, TX &middot; B13</span>
  </div>
</div>

<!-- ══ NAVBAR ══ -->
<header class="vcf-nav" id="vcf-nav">
  <div class="vcf-nav__inner">
    <!-- Logo -->
    <a href="<?= $base_url ?>index.php" class="vcf-nav__logo">
      <div class="vcf-nav__flag">
        <span class="f1"></span>
        <span class="f2"></span>
        <span class="f3"></span>
      </div>
      <div class="vcf-nav__text">
        <span class="t1">VCF Academy</span>
        <span class="t2">Houston &middot; Official</span>
      </div>
    </a>

    <!-- Desktop links -->
    <nav class="vcf-nav__links">
      <a href="<?= $base_url ?>index.php#hero"        class="<?= $page_active==='home'        ? 'active':'' ?>">Home</a>
      <a href="<?= $base_url ?>index.php#methodology" class="<?= $page_active==='methodology' ? 'active':'' ?>">Methodology</a>
      <a href="<?= $base_url ?>index.php#grounds"     class="<?= $page_active==='grounds'     ? 'active':'' ?>">Grounds</a>
      <a href="<?= $base_url ?>index.php#roster"      class="<?= $page_active==='roster'      ? 'active':'' ?>">Roster</a>
      <a href="<?= $base_url ?>index.php#tournaments" class="<?= $page_active==='tournaments' ? 'active':'' ?>">Tournaments</a>
      <a href="<?= $base_url ?>calendar.php"          class="<?= $page_active==='calendar'    ? 'active':'' ?>">Calendar</a>
      <a href="<?= $base_url ?>recaudaciones.php"     class="<?= $page_active==='support'     ? 'active':'' ?>">Support</a>
    </nav>
    <a href="<?= $base_url ?>contact.php" class="vcf-nav__cta">Contact Us</a>

    <!-- Mobile burger -->
    <button class="vcf-nav__burger" id="vcf-burger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>

  <!-- Mobile menu -->
  <nav class="vcf-nav__mobile" id="vcf-mobile-menu">
    <a href="<?= $base_url ?>index.php#hero"        class="<?= $page_active==='home'        ? 'active':'' ?>">Home</a>
    <a href="<?= $base_url ?>index.php#methodology" class="<?= $page_active==='methodology' ? 'active':'' ?>">Methodology</a>
    <a href="<?= $base_url ?>index.php#grounds"     class="<?= $page_active==='grounds'     ? 'active':'' ?>">Grounds</a>
    <a href="<?= $base_url ?>index.php#roster"      class="<?= $page_active==='roster'      ? 'active':'' ?>">Roster</a>
    <a href="<?= $base_url ?>index.php#tournaments" class="<?= $page_active==='tournaments' ? 'active':'' ?>">Tournaments</a>
    <a href="<?= $base_url ?>calendar.php"          class="<?= $page_active==='calendar'    ? 'active':'' ?>">Calendar</a>
    <a href="<?= $base_url ?>recaudaciones.php"     class="<?= $page_active==='support'     ? 'active':'' ?>">Support</a>
    <a href="<?= $base_url ?>contact.php"           class="<?= $page_active==='contact'     ? 'active':'' ?>">Contact</a>
  </nav>
</header>

<script>
// Mobile menu toggle
document.getElementById('vcf-burger').addEventListener('click', function(){
  const m = document.getElementById('vcf-mobile-menu');
  m.classList.toggle('open');
});
// Close on outside click
document.addEventListener('click', function(e){
  if (!e.target.closest('#vcf-nav')) {
    document.getElementById('vcf-mobile-menu').classList.remove('open');
  }
});
</script>
