<?php
/**
 * Compact footer for admin — matches public redesign footer bottom strip.
 */
$vcf_base_url = ($base === '' ? '/' : $base . '/');
?>
<footer class="vcf-footer vcf-admin-footer">
  <div class="vcf-admin-footer__inner">
    <p class="vcf-admin-footer__tagline">Amunt Valencia &middot; Amunt Houston</p>
    <div class="vcf-admin-footer__row">
      <a href="<?= htmlspecialchars($vcf_base_url) ?>index.php" class="vcf-admin-footer__link">View public site</a>
      <span class="vcf-admin-footer__sep" aria-hidden="true">&middot;</span>
      <a href="<?= htmlspecialchars($base) ?>/admin/logout.php" class="vcf-admin-footer__link js-logout" data-confirm="¿Cerrar sesión?">Log out</a>
      <span class="vcf-admin-footer__copy">VCF Academy Houston &copy; <?= (int) date('Y') ?></span>
    </div>
    <p class="vcf-admin-footer__official">
      Official partner of <a href="https://www.valenciacf.com" target="_blank" rel="noopener noreferrer">valenciacf.com</a>
    </p>
  </div>
</footer>
