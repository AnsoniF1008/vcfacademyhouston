<?php
require __DIR__ . '/includes/page_cache.php';
// Public join form. Content only changes when admin updates categorias/horarios
// (rare). Cache for 15 min — POST submissions bypass cache automatically.
if (vcf_page_cache_try_serve(900)) {
    exit;
}
require __DIR__ . '/config/database.php';
vcf_page_cache_start(900);

$categorias = [];
try {
    $stmt = $pdo->query("SELECT id, nombre, horarios_entrenamiento FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll();
    $slotsRows = [];
    try {
        $slotsRows = $pdo->query("SELECT categoria_id, dia_semana, hora FROM categoria_horarios ORDER BY categoria_id, dia_semana, hora")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
    }
    $dayShort = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
    $byCat = [];
    foreach ($slotsRows as $s) {
        $cid = (int) $s['categoria_id'];
        if (!isset($byCat[$cid])) $byCat[$cid] = [];
        $byCat[$cid][] = $s;
    }
    foreach ($categorias as &$c) {
        $cid = (int) $c['id'];
        if (!empty($byCat[$cid])) {
            $parts = [];
            foreach ($byCat[$cid] as $s) {
                $dia = (int) $s['dia_semana'];
                $h = $s['hora'];
                if (preg_match('/^(\d{1,2}):(\d{2})/', $h, $m)) {
                    $h12 = (int) $m[1] > 12 ? (int) $m[1] - 12 : ((int) $m[1] === 0 ? 12 : (int) $m[1]);
                    $parts[] = ($dayShort[$dia] ?? '') . ' ' . $h12 . ':' . $m[2] . ' ' . ((int) $m[1] >= 12 ? 'PM' : 'AM');
                } else {
                    $parts[] = ($dayShort[$dia] ?? '') . ' ' . $h;
                }
            }
            $c['schedule_display'] = implode(', ', $parts);
        } else {
            $c['schedule_display'] = $c['horarios_entrenamiento'] ?? '';
        }
    }
    unset($c);
} catch (PDOException $e) {
    error_log('Categorias query: ' . $e->getMessage());
}

$message = '';
$messageType = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot: bots fill this hidden field, humans leave it empty
    if (!empty($_POST['website'])) {
        $success = true; // Silently discard — bot thinks it succeeded
        $message = 'Thank you for your interest! We have received your registration and will contact you soon.';
        $messageType = 'success';
    } else {
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
    } // end honeypot check
}

$page_title = 'Join the Academy - VCF Academy Houston';
$page_description = 'Register interest in VCF Academy Houston youth soccer programs and Valencia CF methodology in Texas.';
$meta_robots = 'noindex, follow';
require __DIR__ . '/includes/header.php';
?>

<section class="vcf-section--dark vcf-page-sub vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container py-5">
        <h1 class="vcf-section__title">Join the <em>Academy</em></h1>
        <p class="vcf-section-desc">Register your interest in joining VCF Academy Houston. Fill out the form below and our team will get in touch with you about next steps, tryouts, and program details.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> mb-4 vcf-alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="vcf-sede-card p-4 vcf-page-card">
                    <form method="post" action="">
                        <!-- Honeypot anti-spam: hidden from humans, filled by bots -->
                        <div style="display:none" aria-hidden="true">
                            <label for="hp_website">Website</label>
                            <input type="text" id="hp_website" name="website" tabindex="-1" autocomplete="off" value="">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label vcf-form-label">Parent / Guardian Name</label>
                                <input type="text" class="form-control vcf-form-control" name="parent_name" required value="<?= htmlspecialchars($_POST['parent_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label vcf-form-label">Email</label>
                                <input type="email" class="form-control vcf-form-control" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label vcf-form-label">Phone (optional)</label>
                                <input type="tel" class="form-control vcf-form-control" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label vcf-form-label">Child's Name</label>
                                <input type="text" class="form-control vcf-form-control" name="child_name" required value="<?= htmlspecialchars($_POST['child_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label vcf-form-label">Age Category</label>
                                <select class="form-select vcf-form-control" name="child_category" required>
                                    <option value="">Select category</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['nombre']) ?>" <?= ($_POST['child_category'] ?? '') === $cat['nombre'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nombre']) ?><?= !empty($cat['schedule_display']) ? ' — ' . htmlspecialchars($cat['schedule_display']) : ($cat['horarios_entrenamiento'] ? ' — ' . htmlspecialchars($cat['horarios_entrenamiento']) : '') ?>
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
                                <label class="form-label vcf-form-label">Additional message (optional)</label>
                                <textarea class="form-control vcf-form-control" name="message" rows="3"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="vcf-btn-cta">Submit Registration</button>
                            <a href="<?= $base ?? '' ?>/index.php" class="btn btn-outline-light ms-2">Cancel</a>
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
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
