<?php
$page_title = 'Privacy Policy - VCF Academy Houston';
require __DIR__ . '/includes/header.php';
?>

<section class="vcf-section">
    <div class="container">
        <h1 class="vcf-section-title">Privacy Policy</h1>
        <p class="vcf-section-desc text-muted">Last updated: <?= date('F j, Y') ?></p>

        <div class="vcf-sede-card p-4 mb-4">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Information We Collect</h3>
            <p class="text-white-50">When you contact us or register interest in joining the academy, we may collect your name, email address, phone number, and any information you voluntarily provide. For academy registrations, we also collect information about your child, including name and age category.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4">
            <h3 class="mb-3" style="color: var(--vcf-orange);">How We Use Your Information</h3>
            <p class="text-white-50">We use the information you provide to respond to your inquiries, process academy registration requests, and communicate with you about our programs and events. We do not sell or share your personal information with third parties for marketing purposes.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Data Security</h3>
            <p class="text-white-50">We take reasonable measures to protect your personal information from unauthorized access, alteration, or disclosure. Data transmitted through our website is stored securely in our database.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Your Rights</h3>
            <p class="text-white-50">You may request access to, correction of, or deletion of your personal data by contacting us. We will respond to such requests in accordance with applicable law.</p>
        </div>

        <div class="vcf-sede-card p-4 mb-4">
            <h3 class="mb-3" style="color: var(--vcf-orange);">Contact</h3>
            <p class="text-white-50 mb-0">For questions about this Privacy Policy, please <a href="<?= $base ?? '' ?>/contact.php" class="footer-link">contact us</a>.</p>
        </div>

        <div class="text-center mt-4">
            <a href="<?= $base ?? '' ?>/index.php" class="vcf-btn-outline">Back to Home</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
