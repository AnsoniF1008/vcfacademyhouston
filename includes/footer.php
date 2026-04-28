    </main>
    <?php
    $vcf_base_safe = htmlspecialchars((string) ($base ?? ''), ENT_QUOTES, 'UTF-8');
    ?>
    <?php if (!empty($vcf_public_redesign) && empty($is_admin)): ?>
    <?php require __DIR__ . '/vcf-public-footer.php'; ?>
    <?php elseif (!empty($is_admin)): ?>
    <?php require __DIR__ . '/vcf-admin-footer.php'; ?>
    <?php else: ?>
    <footer class="vcf-footer">
        <div class="container py-4">
            <p class="vcf-footer-amunt text-center mb-4 mb-md-5">Amunt Valencia. Amunt Houston.</p>
            <div class="row vcf-footer-cols">
                <div class="col-6 col-md-4 mb-4 mb-md-0">
                    <h4 class="vcf-footer-heading">Academy</h4>
                    <ul class="vcf-footer-list">
                        <li><a href="<?= $vcf_base_safe ?>/contact.php" class="footer-link">Contact</a></li>
                        <li><a href="<?= $vcf_base_safe ?>/recaudaciones.php" class="footer-link">Support the site</a></li>
                        <li><a href="<?= $vcf_base_safe ?>/calendar.php" class="footer-link">Calendar</a></li>
                        <li><a href="<?= $vcf_base_safe ?>/index.php" class="footer-link">Home</a></li>
                        <li><a href="<?= $vcf_base_safe ?>/index.php#methodology" class="footer-link">Methodology</a></li>
                        <li><a href="<?= $vcf_base_safe ?>/index.php#grounds" class="footer-link">Grounds</a></li>
                        <li><a href="<?= $vcf_base_safe ?>/index.php#roster" class="footer-link">Roster</a></li>
                        <li><a href="<?= $vcf_base_safe ?>/index.php#tournaments" class="footer-link">Tournaments</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4 mb-4 mb-md-0">
                    <h4 class="vcf-footer-heading">Legal</h4>
                    <ul class="vcf-footer-list">
                        <li><a href="<?= $vcf_base_safe ?>/privacy.php" class="footer-link">Privacy</a></li>
                        <li><a href="<?= $vcf_base_safe ?>/terms.php" class="footer-link">Terms</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4 mb-4 mb-md-0">
                    <h4 class="vcf-footer-heading">Follow</h4>
                    <p class="vcf-footer-follow-text mb-1">Official Valencia CF</p>
                    <a href="https://www.valenciacf.com" target="_blank" rel="noopener noreferrer" class="footer-link d-inline-flex align-items-center gap-1"><i class="fas fa-external-link-alt small me-1" aria-hidden="true"></i> valenciacf.com</a>
                    <?php
                    $fe = trim((string) ($vcf_site['public_email'] ?? ''));
                    $fp = trim((string) ($vcf_site['phone'] ?? ''));
                    $fig = trim((string) ($vcf_site['instagram_url'] ?? ''));
                    $ffb = trim((string) ($vcf_site['facebook_url'] ?? ''));
                    if ($fe !== '' || $fp !== '' || $fig !== '' || $ffb !== ''): ?>
                    <ul class="vcf-footer-list mt-3 mb-0 small">
                        <?php if ($fe !== ''): ?><li><a href="mailto:<?= htmlspecialchars($fe) ?>" class="footer-link"><?= htmlspecialchars($fe) ?></a></li><?php endif; ?>
                        <?php if ($fp !== ''): ?><li><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $fp)) ?>" class="footer-link"><?= htmlspecialchars($fp) ?></a></li><?php endif; ?>
                        <?php if ($fig !== ''): ?><li><a href="<?= htmlspecialchars($fig) ?>" class="footer-link" target="_blank" rel="noopener noreferrer">Instagram</a></li><?php endif; ?>
                        <?php if ($ffb !== ''): ?><li><a href="<?= htmlspecialchars($ffb) ?>" class="footer-link" target="_blank" rel="noopener noreferrer">Facebook</a></li><?php endif; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            $partnerLogos = $vcf_site['partner_logos'] ?? [];
            if (is_array($partnerLogos) && count($partnerLogos) > 0): ?>
            <div id="partners" class="vcf-footer-sponsors mt-4 pt-4">
                <h4 class="vcf-footer-heading text-center mb-3">Partners</h4>
                <div class="vcf-footer-sponsors-logos d-flex flex-wrap align-items-center justify-content-center gap-4">
                    <?php foreach ($partnerLogos as $pl):
                        if (empty($pl['src'])) {
                            continue;
                        }
                        $src = $pl['src'];
                        $alt = htmlspecialchars($pl['alt'] ?? 'Partner');
                        $isAbs = strpos($src, 'http') === 0;
                        $imgSrc = $isAbs ? htmlspecialchars($src) : htmlspecialchars((isset($base) ? $base : '') . '/' . ltrim($src, '/'));
                        $href = !empty($pl['href']) ? $pl['href'] : '';
                        if ($href !== ''): ?>
                    <a href="<?= htmlspecialchars($href) ?>" target="_blank" rel="noopener noreferrer" class="d-inline-block"><img src="<?= $imgSrc ?>" alt="<?= $alt ?>" class="vcf-footer-sponsor-logo" loading="lazy"></a>
                        <?php else: ?>
                    <img src="<?= $imgSrc ?>" alt="<?= $alt ?>" class="vcf-footer-sponsor-logo" loading="lazy">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <hr class="vcf-footer-sep my-4">
            <div class="vcf-footer-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="vcf-footer-copy">
                    <span class="vcf-logo-text">VCF</span> Academy Houston &copy; <?= date('Y') ?>
                </div>
                <a href="https://www.valenciacf.com" target="_blank" rel="noopener noreferrer" class="vcf-footer-btn-methodology">Official VCF Methodology</a>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    <?php
    $footer_vendor_root = __DIR__ . '/../assets/vendor';
    $use_local_bootstrap_js = file_exists($footer_vendor_root . '/bootstrap/js/bootstrap.bundle.min.js');
    $use_local_gsap = file_exists($footer_vendor_root . '/gsap/gsap.min.js');
    ?>
    <?php
    // GSAP is heavy; load it only on pages that actually use scroll animations
    // (home redesign and admin). Static pages (privacy, terms, contact, etc.) skip it.
    $vcf_load_gsap = !empty($vcf_home_scripts) || !empty($is_admin);
    ?>
    <?php if ($use_local_bootstrap_js): ?>
    <script src="<?= $vcf_base_safe ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>
    <?php else: ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <?php endif; ?>
    <?php if ($vcf_load_gsap): ?>
        <?php if ($use_local_gsap): ?>
        <script src="<?= $vcf_base_safe ?>/assets/vendor/gsap/gsap.min.js" defer></script>
        <script src="<?= $vcf_base_safe ?>/assets/vendor/gsap/ScrollTrigger.min.js" defer></script>
        <?php else: ?>
        <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>
        <?php endif; ?>
    <?php endif; ?>
    <script src="<?= $vcf_base_safe ?>/assets/js/main.js?v=12" defer></script>
    <?php if (!empty($vcf_public_redesign) && empty($is_admin) && !empty($vcf_home_scripts)): ?>
    <script src="<?= $vcf_base_safe ?>/assets/js/vcf-home.js?v=6" defer></script>
    <?php endif; ?>
    <?php if (isset($reels) && count($reels) > 0 && empty($vcf_public_redesign)): ?><script src="<?= $vcf_base_safe ?>/assets/js/reels-carousel.js?v=1" defer></script><?php endif; ?>
</body>
</html>
