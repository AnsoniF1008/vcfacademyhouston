<?php
require __DIR__ . '/config/database.php';

$categorias = [];
try {
    $stmt = $pdo->query("SELECT id, nombre, horarios_entrenamiento FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Categorias query: ' . $e->getMessage());
}

$message = '';
$messageType = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_name = trim($_POST['parent_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $child_name = trim($_POST['child_name'] ?? '');
    $child_category = trim($_POST['child_category'] ?? '');
    $msg = trim($_POST['message'] ?? '');

    if ($parent_name === '' || $email === '' || $child_name === '' || $child_category === '') {
        $message = 'Parent name, email, child name, and category are required.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO inscripciones (parent_name, email, phone, child_name, child_category, message) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$parent_name, $email, $phone ?: null, $child_name, $child_category, $msg ?: null]);
            $success = true;
            $message = 'Thank you for your interest! We have received your registration and will contact you soon.';
            $messageType = 'success';
        } catch (PDOException $e) {
            error_log('Join form: ' . $e->getMessage());
            $message = 'Sorry, we could not process your registration. Please try again later.';
            $messageType = 'danger';
        }
    }
}

$page_title = 'Join the Academy - VCF Academy Houston';
require __DIR__ . '/includes/header.php';
?>

<section class="vcf-section">
    <div class="container">
        <h1 class="vcf-section-title">Join the Academy</h1>
        <p class="vcf-section-desc">Register your interest in joining VCF Academy Houston. Fill out the form below and our team will get in touch with you about next steps, tryouts, and program details.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> mb-4"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="vcf-sede-card p-4">
                    <form method="post" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-white">Parent / Guardian Name</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" name="parent_name" required value="<?= htmlspecialchars($_POST['parent_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white">Email</label>
                                <input type="email" class="form-control bg-dark text-white border-secondary" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-white">Phone (optional)</label>
                                <input type="tel" class="form-control bg-dark text-white border-secondary" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white">Child's Name</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" name="child_name" required value="<?= htmlspecialchars($_POST['child_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white">Age Category</label>
                                <select class="form-select bg-dark text-white border-secondary" name="child_category" required>
                                    <option value="">Select category</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['nombre']) ?>" <?= ($_POST['child_category'] ?? '') === $cat['nombre'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nombre']) ?><?= $cat['horarios_entrenamiento'] ? ' — ' . htmlspecialchars($cat['horarios_entrenamiento']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if (empty($categorias)): ?>
                                        <option value="U6">U6</option>
                                        <option value="U8">U8</option>
                                        <option value="U10">U10</option>
                                        <option value="U12">U12</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-white">Additional message (optional)</label>
                                <textarea class="form-control bg-dark text-white border-secondary" name="message" rows="3"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="vcf-btn-cta">Submit Registration</button>
                            <a href="<?= $base ?? '' ?>/index.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                        </div>
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
