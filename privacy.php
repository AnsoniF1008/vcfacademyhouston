<?php
require __DIR__ . '/includes/page_cache.php';
if (vcf_page_cache_try_serve(600)) {
    exit;
}
vcf_page_cache_start(600);

$page_title = 'Privacy Policy - VCF Academy Houston';
$page_description = 'Privacy policy for VCF Academy Houston: how we collect, use, and protect your information.';
require __DIR__ . '/includes/header.php';
?>

<section class="vcf-section--dark vcf-page-sub vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container py-5">
        <h1 class="vcf-section__title">Privacy <em>Policy</em></h1>
        <p class="vcf-section-desc" style="color:var(--vcf-gray);">Last updated: <?= date('F j, Y') ?></p>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Information We Collect</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;">When you contact us or register interest in joining the academy, we may collect your name, email address, phone number, and any information you voluntarily provide. For academy registrations, we also collect information about your child, including name and age category.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">How We Use Your Information</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;">We use the information you provide to respond to your inquiries, process academy registration requests, and communicate with you about our programs and events. We do not sell or share your personal information with third parties for marketing purposes.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Data Security</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;">We take reasonable measures to protect your personal information from unauthorized access, alteration, or disclosure. Data transmitted through our website is stored securely in our database.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Your Rights</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;">You may request access to, correction of, or deletion of your personal data by contacting us. We will respond to such requests in accordance with applicable law.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4 vcf-page-card">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Contact</h3>
            <p style="color:var(--vcf-lightgray);line-height:1.7;margin-bottom:0;">For questions about this Privacy Policy, please <a href="<?= $base ?? '' ?>/contact.php" class="footer-link">contact us</a>.</p>
        </div>

        <div class="text-center mt-4">
            <a href="<?= $base ?? '' ?>/index.php" class="btn btn-outline-light">Back to Home</a>
        </div>
    </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
