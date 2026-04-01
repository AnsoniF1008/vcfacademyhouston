<?php
/**
 * Dark redesign: support strip + footer.
 * Expects: $base, $vcf_site (from site_loader)
 */
$vcf_base_url = ($base === '' ? '/' : $base . '/');
$fe = trim((string) ($vcf_site['public_email'] ?? ''));
$fp = trim((string) ($vcf_site['phone'] ?? ''));
$telHref = $fp !== '' ? preg_replace('/[^\d+]/', '', $fp) : '';
?>
<!-- ══ SUPPORT STRIP ══ -->
<div class="vcf-support-strip">
  <div class="vcf-support-strip__inner">
    <div>
      <div class="vcf-support-strip__title">Support the Academy</div>
      <div class="vcf-support-strip__text">Help keep this site online — hosting, domain &amp; updates for families and players.</div>
    </div>
    <a href="<?= htmlspecialchars($vcf_base_url) ?>recaudaciones.php" class="vcf-support-strip__btn">Contribute &rarr;</a>
  </div>
</div>

<!-- ══ FOOTER ══ -->
<footer class="vcf-footer">
  <div class="vcf-footer__top">

    <div>
      <div class="vcf-footer__brand-name">VCF Academy <span>Houston</span></div>
      <div class="vcf-footer__brand-sub">Official Valencia CF Academy Program</div>
      <p class="vcf-footer__brand-desc">
        Developing young footballers in Houston, TX with the official Valencia CF methodology —
        Identity, Effort, and Intelligence. Katy, TX.
      </p>
      <?php if ($fe !== ''): ?>
      <a href="mailto:<?= htmlspecialchars($fe) ?>" class="vcf-footer__contact-item">
        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M2 4h16v12H2V4zm8 7L3 6h14l-7 5z"/></svg>
        <?= htmlspecialchars($fe) ?>
      </a>
      <?php endif; ?>
      <?php if ($fp !== ''): ?>
      <a href="tel:<?= htmlspecialchars($telHref) ?>" class="vcf-footer__contact-item">
        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 3h4l2 5-2.5 1.5c1 2 2.5 3.5 4.5 4.5L13 12l5 2v4c-8.3 0-15-6.7-15-15z"/></svg>
        <?= htmlspecialchars($fp) ?>
      </a>
      <?php endif; ?>
    </div>

    <div class="vcf-footer__col">
      <div class="vcf-footer__col-title">Academy</div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#hero">Home</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#methodology">Methodology</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#grounds">Training Grounds</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#roster">Roster</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#tournaments">Tournaments</a>
      <?php if (!empty($star_section_visible)): ?>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php#star">Star of the Month</a>
      <?php endif; ?>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>calendar.php">Calendar</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>join.php">Join the Academy</a>
    </div>

    <div class="vcf-footer__col">
      <div class="vcf-footer__col-title">Info</div>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>contact.php">Contact Us</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>recaudaciones.php">Support the Site</a>
      <a href="<?= htmlspecialchars($vcf_base_url) ?>privacy.php">Privacy Policy</a>
      <a href="https://www.valenciacf.com" target="_blank" rel="noopener">valenciacf.com</a>
    </div>

    <div class="vcf-footer__col">
      <div class="vcf-footer__col-title">Training Schedule</div>
      <div style="font-family:var(--font-display);font-size:13px;color:var(--vcf-orange);font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">B13 — Katy, TX</div>
      <div style="font-size:12px;color:var(--vcf-gray);line-height:1.8;">
        2203 N Westgreen Blvd<br>
        Katy, TX 77449<br><br>
        Mon · Wed · Fri<br>
        5:00 PM
      </div>
      <a href="https://maps.app.goo.gl/q27c1FCQw4cvspGX8" target="_blank" rel="noopener" class="vcf-ground__gps" style="margin-top:14px;">
        <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2a6 6 0 0 0-6 6c0 4 6 10 6 10s6-6 6-10a6 6 0 0 0-6-6zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/></svg>
        Open GPS
      </a>
    </div>

    <?php
    $partnerLogos = $vcf_site['partner_logos'] ?? [];
    if (is_array($partnerLogos) && count($partnerLogos) > 0): ?>
    <div class="vcf-footer__partners" style="grid-column:1/-1;padding-top:24px;border-top:1px solid var(--vcf-border);margin-top:8px;">
      <div style="font-family:var(--font-display);font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:var(--vcf-gray);margin-bottom:16px;text-align:center;">Partners</div>
      <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:24px;">
        <?php foreach ($partnerLogos as $pl):
            if (empty($pl['src'])) {
                continue;
            }
            $src = $pl['src'];
            $alt = htmlspecialchars($pl['alt'] ?? 'Partner');
            $isAbs = strpos($src, 'http') === 0;
            $imgSrc = $isAbs ? htmlspecialchars($src) : htmlspecialchars(($base === '' ? '' : $base) . '/' . ltrim($src, '/'));
            $href = !empty($pl['href']) ? $pl['href'] : '';
            if ($href !== ''): ?>
        <a href="<?= htmlspecialchars($href) ?>" target="_blank" rel="noopener noreferrer"><img src="<?= $imgSrc ?>" alt="<?= $alt ?>" style="max-height:40px;width:auto;opacity:0.85;" loading="lazy"></a>
            <?php else: ?>
        <img src="<?= $imgSrc ?>" alt="<?= $alt ?>" style="max-height:40px;width:auto;opacity:0.85;" loading="lazy">
            <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="vcf-footer__bottom">
    <span class="vcf-footer__bottom-copy">VCF Academy Houston &copy; <?= date('Y') ?></span>
    <span class="vcf-footer__amunt">Amunt Valencia &middot; Amunt Houston</span>
    <span class="vcf-footer__official">
      Official partner of <a href="https://www.valenciacf.com" target="_blank" rel="noopener">valenciacf.com</a>
    </span>
  </div>
</footer>
