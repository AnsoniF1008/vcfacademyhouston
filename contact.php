<?php
require __DIR__ . '/includes/page_cache.php';
if (vcf_page_cache_try_serve(900)) {
    exit;
}
require __DIR__ . '/config/database.php';
vcf_page_cache_start(900);

$message = '';
$messageType = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/rate_limit.php';
    require_once __DIR__ . '/includes/public_csrf.php';
    // Throttle before any DB write: 5 messages per 10 min per IP is enough
    // for a real visitor, but stops bots from flooding `contact_messages`.
    if (!vcf_rate_limit_check('contact-form', vcf_client_ip(), 5, 600)) {
        http_response_code(429);
        $message = 'Too many messages. Please wait a few minutes and try again.';
        $messageType = 'danger';
    }
    // CSRF: reject cross-site forged POSTs (session-less, cache-safe origin check).
    elseif (!vcf_verify_same_origin()) {
        http_response_code(403);
        $message = 'Your request could not be verified. Please reload the page and try again.';
        $messageType = 'danger';
    }
    // Honeypot: bots fill this hidden field, humans leave it empty
    elseif (!empty($_POST['website'])) {
        $success = true; // Silently discard — bot thinks it succeeded
        $message = 'Thank you for your message. We will get back to you soon.';
        $messageType = 'success';
    } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $subject === '' || $body === '') {
        $message = 'All fields are required.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $body]);
            $success = true;
            $message = 'Thank you for your message. We will get back to you soon.';
            $messageType = 'success';
        } catch (PDOException $e) {
            error_log('Contact form: ' . $e->getMessage());
            $message = 'Sorry, we could not send your message. Please try again later.';
            $messageType = 'danger';
        }
    }
    } // end honeypot check
}

$page_title = 'Contact - VCF Academy Houston';
$page_description = 'Contact VCF Academy Houston: programs, schedules, training locations, and general questions.';
require __DIR__ . '/includes/header.php';

$pubEmail = trim((string) ($vcf_site['public_email'] ?? ''));
$pubPhone = trim((string) ($vcf_site['phone'] ?? ''));
$ig = trim((string) ($vcf_site['instagram_url'] ?? ''));
$fb = trim((string) ($vcf_site['facebook_url'] ?? ''));
$yt = trim((string) ($vcf_site['youtube_url'] ?? ''));
$xurl = trim((string) ($vcf_site['x_url'] ?? ''));
$hasDirect = $pubEmail !== '' || $pubPhone !== '' || $ig !== '' || $fb !== '' || $yt !== '' || $xurl !== '';
?>

<section class="vcf-section--dark vcf-page-sub vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container py-5">
        <h1 class="vcf-section__title">Contact <em>Us</em></h1>
        <p class="vcf-section-desc mb-4">Have questions about our programs, training schedules, or training locations? Get in touch with the VCF Academy Houston team.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> mb-4 vcf-alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <div class="row justify-content-center g-4">
            <?php if ($hasDirect): ?>
            <div class="col-lg-4">
                <div class="vcf-sede-card p-4 h-100 vcf-page-card">
                    <h2 class="h6 mb-3" style="color:var(--vcf-orange);font-family:var(--font-display);text-transform:uppercase;letter-spacing:0.08em;">Direct contact</h2>
                    <ul class="list-unstyled mb-0 small">
                        <?php if ($pubEmail !== ''): ?>
                        <li class="mb-2"><a href="mailto:<?= htmlspecialchars($pubEmail) ?>" class="footer-link"><?= htmlspecialchars($pubEmail) ?></a></li>
                        <?php endif; ?>
                        <?php if ($pubPhone !== ''): ?>
                        <li class="mb-2"><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $pubPhone)) ?>" class="footer-link"><?= htmlspecialchars($pubPhone) ?></a></li>
                        <?php endif; ?>
                        <?php if ($ig !== ''): ?>
                        <li class="mb-2"><a href="<?= htmlspecialchars($ig) ?>" class="footer-link d-inline-flex align-items-center gap-2" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram" aria-hidden="true"></i> Instagram</a></li>
                        <?php endif; ?>
                        <?php if ($fb !== ''): ?>
                        <li class="mb-2"><a href="<?= htmlspecialchars($fb) ?>" class="footer-link d-inline-flex align-items-center gap-2" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f" aria-hidden="true"></i> Facebook</a></li>
                        <?php endif; ?>
                        <?php if ($yt !== ''): ?>
                        <li class="mb-2"><a href="<?= htmlspecialchars($yt) ?>" class="footer-link d-inline-flex align-items-center gap-2" target="_blank" rel="noopener noreferrer"><i class="fab fa-youtube" aria-hidden="true"></i> YouTube</a></li>
                        <?php endif; ?>
                        <?php if ($xurl !== ''): ?>
                        <li class="mb-2"><a href="<?= htmlspecialchars($xurl) ?>" class="footer-link d-inline-flex align-items-center gap-2" target="_blank" rel="noopener noreferrer"><i class="fab fa-twitter" aria-hidden="true"></i> X</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-lg-8">
                <div class="vcf-sede-card p-4 vcf-page-card">
                    <h2 class="h6 mb-3" style="color:var(--vcf-orange);font-family:var(--font-display);text-transform:uppercase;letter-spacing:0.08em;">Send a message</h2>
                    <form method="post" action="">
                        <!-- Honeypot anti-spam: hidden from humans, filled by bots -->
                        <div style="display:none" aria-hidden="true">
                            <label for="hp_website">Website</label>
                            <input type="text" id="hp_website" name="website" tabindex="-1" autocomplete="off" value="">
                        </div>
                        <div class="mb-3">
                            <label class="form-label vcf-form-label">Name</label>
                            <input type="text" class="form-control vcf-form-control" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label vcf-form-label">Email</label>
                            <input type="email" class="form-control vcf-form-control" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label vcf-form-label">Subject</label>
                            <input type="text" class="form-control vcf-form-control" name="subject" required value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label vcf-form-label">Message</label>
                            <textarea class="form-control vcf-form-control" name="message" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="vcf-btn-cta">Send Message</button>
                        <a href="<?= $base ?? '' ?>/index.php" class="btn btn-outline-light ms-2">Back to Home</a>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center">
            <a href="<?= $base ?? '' ?>/index.php" class="vcf-btn-cta">Back to Home</a>
        </div>
        <?php endif; ?>
    </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
