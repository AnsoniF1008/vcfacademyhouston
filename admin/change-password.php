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
        } elseif (strlen($new) < 8) {
            $message = 'New password must be at least 8 characters.';
            $messageType = 'danger';
        } elseif (!preg_match('/[a-zA-Z]/', $new) || !preg_match('/[0-9]/', $new)) {
            $message = 'New password must contain at least one letter and one number.';
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

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Change Password - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Change password']]) ?>
    <h1 class="mb-4 admin-page-title">Change Password</h1>

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
                            <input type="password" class="form-control bg-dark text-white border-secondary" name="new_password" required minlength="8" autocomplete="new-password" title="At least 8 characters, one letter and one number">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-white small">Confirm new password</label>
                            <input type="password" class="form-control bg-dark text-white border-secondary" name="confirm_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary btn-admin-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
