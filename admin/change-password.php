<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            $message = 'All fields are required.';
            $messageType = 'danger';
        } elseif (strlen($new) < 6) {
            $message = 'New password must be at least 6 characters.';
            $messageType = 'danger';
        } elseif ($new !== $confirm) {
            $message = 'New password and confirmation do not match.';
            $messageType = 'danger';
        } else {
            $userId = (int) ($_SESSION['admin_user_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($current, $row['password_hash'])) {
                $message = 'Current password is incorrect.';
                $messageType = 'danger';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hash, $userId]);
                $message = 'Password changed successfully.';
                $messageType = 'success';
            }
        }
    }
}

$page_title = 'Change Password - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <h1 class="mb-4" style="color: #FF6600;">Change Password</h1>
    <p><a href="dashboard.php" class="text-decoration-none" style="color: #FF6600;">&larr; Dashboard</a></p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <form method="post" action="">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label text-white small">Current password</label>
                            <input type="password" class="form-control bg-dark text-white border-secondary" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">New password</label>
                            <input type="password" class="form-control bg-dark text-white border-secondary" name="new_password" required minlength="6" autocomplete="new-password">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-white small">Confirm new password</label>
                            <input type="password" class="form-control bg-dark text-white border-secondary" name="confirm_password" required minlength="6" autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
