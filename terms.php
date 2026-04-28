<?php
require __DIR__ . '/includes/page_cache.php';
if (vcf_page_cache_try_serve(1800)) {
    exit;
}
vcf_page_cache_start(1800);

$page_title = 'Terms of Use - VCF Academy Houston';
$page_description = 'Terms of use for the VCF Academy Houston website. Read the rules and disclaimers that apply when using this site.';
require __DIR__ . '/includes/header.php';
?>

<section class="vcf-section--dark vcf-page-sub vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container py-5">
        <h1 class="vcf-section__title">Terms of <em>Use</em></h1>
        <p class="vcf-section-desc" style="color:var(--vcf-gray);">Last updated: <?= date('F j, Y') ?></p>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Acceptance of Terms</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;">By accessing or using this website you agree to these Terms of Use. If you do not agree, please do not use the site. We may update these terms at any time; continued use after an update constitutes acceptance of the changes.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Use of the Site</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;">This site is provided for informational purposes about VCF Academy Houston programs, schedules, and community events. You agree not to misuse the site, attempt to access non-public areas, interfere with normal operation, or upload malicious content.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Content and Trademarks</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;">All Valencia CF marks, names, and logos are the property of their respective owners and are used here under the academy partnership. Other content (text, photos, schedules) is published by the academy and may not be copied for commercial use without written permission.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">User-Submitted Information</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;">When you submit information through registration or contact forms you confirm that the data is accurate and that you have authority to provide it. Handling of personal data is described in our <a href="<?= htmlspecialchars($base ?? '') ?>/privacy.php" class="footer-link">Privacy Policy</a>.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Disclaimers</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;">Schedules, results, and rosters are provided for informational purposes and may change without notice. The academy is not responsible for inaccuracies, third-party links, or downtime affecting site availability.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Contact</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;margin-bottom:0;">Questions about these terms? <a href="<?= htmlspecialchars($base ?? '') ?>/contact.php" class="footer-link">Contact us</a>.</p>
        </div>

        <div class="text-center mt-4">
            <a href="<?= htmlspecialchars($base ?? '') ?>/index.php" class="btn btn-outline-light">Back to Home</a>
        </div>
    </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
