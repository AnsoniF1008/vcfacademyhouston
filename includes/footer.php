    </main>
    <footer class="vcf-footer">
        <div class="container py-4">
            <p class="vcf-footer-amunt text-center mb-3 mb-md-0">Amunt Valencia. Amunt Houston.</p>
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <span class="vcf-logo-text">VCF</span> Academy Houston &copy; <?= date('Y') ?>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="<?= isset($base) ? $base : '' ?>/contact.php" class="footer-link me-3">Contact</a>
                    <a href="<?= isset($base) ? $base : '' ?>/privacy.php" class="footer-link">Privacy</a>
                </div>
            </div>
        </div>
    </footer>
    <div class="vcf-badge-methodology" aria-hidden="true">
        <span class="vcf-badge-methodology-text">Official VCF Methodology</span>
    </div>
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
