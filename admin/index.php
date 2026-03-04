<?php
session_start();
if (!empty($_SESSION['admin_logged'])) {
    header('Location: dashboard.php');
    exit;
}

require __DIR__ . '/../config/database.php';
require __DIR__ . '/includes/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($user === '' || $pass === '') {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
        $stmt->execute([$user]);
        $row = $stmt->fetch();

        if ($row && password_verify($pass, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_user_id'] = $row['id'];
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - VCF Academy Houston</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { --vcf-black: #1A1A1A; --vcf-orange: #FF6600; --vcf-white: #FFFFFF; }
        body { font-family: 'Montserrat', sans-serif; background: var(--vcf-black); color: var(--vcf-white); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #2d2d2d; border: 2px solid var(--vcf-orange); border-radius: 12px; padding: 2rem; max-width: 400px; }
        .login-card h1 { font-family: 'Oswald', sans-serif; color: var(--vcf-orange); font-size: 1.5rem; margin-bottom: 1.5rem; }
        .btn-vcf { background: var(--vcf-orange); color: var(--vcf-white); border: none; font-weight: 600; }
        .btn-vcf:hover { background: #ff8533; color: var(--vcf-white); }
    </style>
</head>
<body>
    <div class="login-card w-100 mx-3">
        <h1>VCF Academy Admin</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control bg-dark text-white border-secondary" id="username" name="username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control bg-dark text-white border-secondary" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-vcf w-100">Log in</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
