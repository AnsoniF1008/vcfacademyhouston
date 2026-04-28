<?php
http_response_code(404);
require __DIR__ . '/includes/page_cache.php';
if (vcf_page_cache_try_serve(1800)) {
    exit;
}
vcf_page_cache_start(1800);

$page_title = 'Page not found - VCF Academy Houston';
$page_description = 'The page you were looking for does not exist or has moved.';
$meta_robots = 'noindex, follow';
require __DIR__ . '/includes/header.php';
?>
<section class="vcf-section--dark vcf-page-sub vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container py-5" style="max-width:760px;text-align:center;">
        <div class="mb-4">
            <span style="font-family:var(--font-display,sans-serif);font-size:96px;font-weight:900;line-height:1;color:var(--vcf-orange,#FF6600);letter-spacing:-0.04em;">404</span>
        </div>
        <h1 class="mb-3" style="font-family:var(--font-display,sans-serif);font-size:32px;font-weight:900;text-transform:uppercase;letter-spacing:.04em;color:#fff;">Page not found</h1>
        <p class="mb-4" style="color:var(--vcf-gray,#aaa);font-size:14px;line-height:1.7;max-width:480px;margin:0 auto;">
            The page you were looking for does not exist or has moved. Use one of the links below to keep exploring.
        </p>
        <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
            <a href="<?= htmlspecialchars($base ?? '') ?>/index.php" class="vcf-btn-cta">Back to Home</a>
            <a href="<?= htmlspecialchars($base ?? '') ?>/calendar.php" class="btn btn-outline-light">Calendar</a>
            <a href="<?= htmlspecialchars($base ?? '') ?>/index.php#tournaments" class="btn btn-outline-light">Schedule</a>
            <a href="<?= htmlspecialchars($base ?? '') ?>/contact.php" class="btn btn-outline-light">Contact</a>
        </div>
    </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
