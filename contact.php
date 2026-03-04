<?php
require __DIR__ . '/config/database.php';

$message = '';
$messageType = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}

$page_title = 'Contact - VCF Academy Houston';
require __DIR__ . '/includes/header.php';
?>

<section class="vcf-section">
    <div class="container">
        <h1 class="vcf-section-title">Contact Us</h1>
        <p class="vcf-section-desc">Have questions about our programs, training schedules, or how to join? Get in touch with the VCF Academy Houston team.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> mb-4"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="vcf-sede-card p-4">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label text-white">Name</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white">Email</label>
                            <input type="email" class="form-control bg-dark text-white border-secondary" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white">Subject</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="subject" required value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-white">Message</label>
                            <textarea class="form-control bg-dark text-white border-secondary" name="message" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="vcf-btn-cta">Send Message</button>
                        <a href="<?= $base ?? '' ?>/index.php" class="btn btn-outline-secondary ms-2">Back to Home</a>
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
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
