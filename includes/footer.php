    </main>
    <footer class="vcf-footer">
        <div class="container py-4">
            <p class="vcf-footer-amunt text-center mb-4 mb-md-5">Amunt Valencia. Amunt Houston.</p>
            <div class="row vcf-footer-cols">
                <div class="col-6 col-md-3 mb-4 mb-md-0">
                    <h4 class="vcf-footer-heading">Academy</h4>
                    <ul class="vcf-footer-list">
                        <li><a href="<?= isset($base) ? $base : '' ?>/index.php" class="footer-link">Home</a></li>
                        <li><a href="<?= isset($base) ? $base : '' ?>/index.php#methodology" class="footer-link">Methodology</a></li>
                        <li><a href="<?= isset($base) ? $base : '' ?>/index.php#grounds" class="footer-link">Grounds</a></li>
                        <li><a href="<?= isset($base) ? $base : '' ?>/index.php#roster" class="footer-link">Roster</a></li>
                        <li><a href="<?= isset($base) ? $base : '' ?>/index.php#tournaments" class="footer-link">Tournaments</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 mb-4 mb-md-0">
                    <h4 class="vcf-footer-heading">Join</h4>
                    <ul class="vcf-footer-list">
                        <li><a href="<?= isset($base) ? $base : '' ?>/join.php" class="footer-link">Join the Academy</a></li>
                        <li><a href="<?= isset($base) ? $base : '' ?>/calendar.php" class="footer-link">Calendar</a></li>
                        <li><a href="<?= isset($base) ? $base : '' ?>/contact.php" class="footer-link">Contact</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 mb-4 mb-md-0">
                    <h4 class="vcf-footer-heading">Legal</h4>
                    <ul class="vcf-footer-list">
                        <li><a href="<?= isset($base) ? $base : '' ?>/privacy.php" class="footer-link">Privacy</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 mb-4 mb-md-0">
                    <h4 class="vcf-footer-heading">Follow</h4>
                    <p class="vcf-footer-follow-text mb-1">Official Valencia CF</p>
                    <a href="https://www.valenciacf.com" target="_blank" rel="noopener noreferrer" class="footer-link d-inline-flex align-items-center gap-1"><i class="fas fa-external-link-alt small me-1" aria-hidden="true"></i> valenciacf.com</a>
                </div>
            </div>
            <hr class="vcf-footer-sep my-4">
            <div class="vcf-footer-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="vcf-footer-copy">
                    <span class="vcf-logo-text">VCF</span> Academy Houston &copy; <?= date('Y') ?>
                </div>
                <a href="https://www.valenciacf.com" target="_blank" rel="noopener noreferrer" class="vcf-footer-btn-methodology">Official VCF Methodology</a>
            </div>
        </div>
    </footer>
    <?php
    $footer_vendor_root = __DIR__ . '/../assets/vendor';
    $use_local_bootstrap_js = file_exists($footer_vendor_root . '/bootstrap/js/bootstrap.bundle.min.js');
    ?>
    <?php if ($use_local_bootstrap_js): ?>
    <script src="<?= isset($base) ? $base : '' ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <?php else: ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php endif; ?>
    <script src="<?= isset($base) ? $base : '' ?>/assets/js/main.js?v=6"></script>
</body>
</html>
