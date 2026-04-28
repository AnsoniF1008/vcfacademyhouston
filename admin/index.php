<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();
if (!empty($_SESSION['admin_logged'])) {
    header('Location: dashboard.php');
    exit;
}

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

require __DIR__ . '/../config/database.php';
require __DIR__ . '/includes/csrf.php';

$error = '';

$window_sec = 900;
if (isset($_SESSION['login_attempts_time']) && (time() - $_SESSION['login_attempts_time']) > $window_sec) {
    $_SESSION['login_attempts_time'] = null;
    $_SESSION['login_attempts_count'] = 0;
}
$attempts = (int) ($_SESSION['login_attempts_count'] ?? 0);
$max_attempts = 5;

$is_locked = $attempts >= $max_attempts;
$lockout_remaining = 0;
if ($is_locked && !empty($_SESSION['login_attempts_time'])) {
    $lockout_remaining = max(0, (int) $_SESSION['login_attempts_time'] + $window_sec - time());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } elseif ($attempts >= $max_attempts) {
        $error = 'Too many login attempts. Try again in 15 minutes.';
    } else {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';

        if ($user === '' || $pass === '') {
            $error = 'Please enter username and password.';
        } else {
            $stmt = $pdo->prepare('SELECT id, password_hash, username FROM admin_users WHERE username = ?');
            $stmt->execute([$user]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($pass, $row['password_hash'])) {
                $_SESSION['login_attempts_count'] = 0;
                $_SESSION['login_attempts_time'] = null;
                try {
                    $log = $pdo->prepare('INSERT INTO admin_activity_log (user_id, username, action, details) VALUES (?, ?, \'auth.login_ok\', ?)');
                    $log->execute([$row['id'], $row['username'] ?? $user, $_SERVER['REMOTE_ADDR'] ?? '']);
                } catch (PDOException $e) {
                    error_log('admin login log: ' . $e->getMessage());
                }
                session_regenerate_id(true);
                $_SESSION['admin_logged'] = true;
                $_SESSION['admin_user_id'] = $row['id'];
                header('Location: dashboard.php');
                exit;
            }
            $_SESSION['login_attempts_count'] = $attempts + 1;
            $_SESSION['login_attempts_time'] = $_SESSION['login_attempts_time'] ?? time();
            try {
                $log = $pdo->prepare('INSERT INTO admin_activity_log (user_id, username, action, details) VALUES (NULL, ?, \'auth.login_fail\', ?)');
                $log->execute([$user, $_SERVER['REMOTE_ADDR'] ?? '']);
            } catch (PDOException $e) {
                error_log('admin login log: ' . $e->getMessage());
            }
            $error = 'Invalid username or password.';
        }
    }
}

$crest_rel = null;
$root = dirname(__DIR__);
if (file_exists($root . '/assets/img/vfc-crest.svg')) {
    $crest_rel = '../assets/img/vfc-crest.svg';
} elseif (file_exists($root . '/assets/img/vcf-crest.svg')) {
    $crest_rel = '../assets/img/vcf-crest.svg';
} elseif (file_exists($root . '/assets/img/vcf-crest.png')) {
    $crest_rel = '../assets/img/vcf-crest.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin Login — VCF Academy Houston</title>
  <link rel="icon" href="../assets/img/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/vcf-admin-login.css?v=1">
</head>
<body>

<div class="login-page">

  <div class="login-brand">
    <div class="brand-logo">
      <?php if ($crest_rel): ?>
      <img class="brand-logo__crest" src="<?= htmlspecialchars($crest_rel) ?>" alt="" width="44" height="58" loading="eager">
      <?php else: ?>
      <div class="brand-flag" aria-hidden="true">
        <span class="f1"></span>
        <span class="f2"></span>
        <span class="f3"></span>
      </div>
      <?php endif; ?>
      <div class="brand-text">
        <div class="t1">VCF Academy</div>
        <div class="t2">Houston &middot; Official</div>
      </div>
    </div>

    <div class="brand-main">
      <h1>Admin<br><em>Panel</em></h1>
      <p>Restricted access &middot; Authorized only</p>
    </div>

    <div class="brand-footer">
      VCF Academy Houston &copy; <?= (int) date('Y') ?> &nbsp;&middot;&nbsp;
      <a href="../index.php">View public site &rarr;</a>
    </div>
  </div>

  <div class="login-form-panel">

    <div class="form-header">
      <div class="form-header__label">Restricted Area</div>
      <div class="form-header__title">Sign In</div>
      <div class="form-header__sub">Enter your admin credentials to continue.</div>
    </div>

    <?php if (isset($_GET['timeout']) && $_GET['timeout'] === '1' && !$error): ?>
    <div class="login-alert login-alert--info">
      <div class="login-alert__title">Session expired</div>
      You were signed out due to inactivity. Please sign in again.
    </div>
    <?php endif; ?>

    <?php if ($is_locked && $lockout_remaining > 0): ?>
    <div class="login-alert login-alert--locked">
      <div class="login-alert__title">Account temporarily locked</div>
      Too many failed attempts. Please wait before trying again.
      <div class="login-alert__countdown" id="lockout-countdown"><?= (int) floor($lockout_remaining / 60) ?>m <?= (int) ($lockout_remaining % 60) ?>s</div>
    </div>
    <?php elseif ($error): ?>
    <div class="login-alert login-alert--error">
      <div class="login-alert__title">Login failed</div>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if (!$is_locked && $attempts > 0): ?>
    <div class="attempt-dots" aria-hidden="true">
      <?php for ($i = 0; $i < $max_attempts; $i++): ?>
      <div class="attempt-dot<?= $i < $attempts ? ' used' : '' ?>"></div>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="" autocomplete="off" novalidate>
      <?= csrf_field() ?>

      <div class="field">
        <label class="field__label" for="username">Username</label>
        <div class="field__wrap">
          <input
            class="field__input<?= $error && !$is_locked ? ' error' : '' ?>"
            type="text"
            id="username"
            name="username"
            autocomplete="username"
            spellcheck="false"
            <?= $is_locked ? 'disabled' : '' ?>
            <?= !$is_locked ? 'autofocus' : '' ?>
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            placeholder="Enter username">
          <span class="field__icon" aria-hidden="true">&#9635;</span>
        </div>
      </div>

      <div class="field">
        <label class="field__label" for="password">Password</label>
        <div class="field__wrap">
          <input
            class="field__input<?= $error && !$is_locked ? ' error' : '' ?>"
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            <?= $is_locked ? 'disabled' : '' ?>
            placeholder="Enter password">
          <button type="button" class="field__toggle" id="toggle-pass" tabindex="-1" aria-label="Show password">&#128065;</button>
        </div>
      </div>

      <hr class="form-divider">

      <button type="submit" class="btn-submit" <?= $is_locked ? 'disabled' : '' ?>><?= $is_locked ? 'Access blocked' : 'Log in &rarr;' ?></button>

      <div class="form-footer">
        <a href="../index.php" class="form-footer__back">&larr; Back to site</a>
        <div class="form-footer__secure">
          <span class="secure-dot"></span>
          Secure session
        </div>
      </div>
    </form>

  </div>
</div>

<script>
(function () {
  var toggle = document.getElementById('toggle-pass');
  var input = document.getElementById('password');
  if (!toggle || !input) return;
  toggle.addEventListener('click', function () {
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    toggle.innerHTML = show ? '&#128683;' : '&#128065;';
    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });
})();
(function () {
  var el = document.getElementById('lockout-countdown');
  if (!el) return;
  var remaining = <?= (int) $lockout_remaining ?>;
  function tick() {
    if (remaining <= 0) {
      el.textContent = 'Refreshing…';
      location.reload();
      return;
    }
    var m = Math.floor(remaining / 60);
    var s = remaining % 60;
    el.textContent = (m > 0 ? m + 'm ' : '') + s + 's';
    remaining--;
    setTimeout(tick, 1000);
  }
  tick();
})();
</script>
</body>
</html>
