<?php
require __DIR__ . '/includes/auth.php';
require_super_admin();
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$has_role_col = false;
try {
    $st = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'role'");
    $has_role_col = $st && $st->fetch();
} catch (PDOException $e) {}

if (!$has_role_col) {
    $page_title = 'Users - VCF Academy Houston';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5"><div class="alert alert-warning">Run the RBAC migration first: <code>sql/migrate_rbac.sql</code>.</div><p><a href="dashboard.php">&larr; Dashboard</a></p></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$message     = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.'; $messageType = 'danger';

    } elseif (isset($_POST['delete_user'])) {
        $uid = (int) $_POST['delete_user'];
        if ($uid === (int) $_SESSION['admin_user_id']) {
            $message = 'You cannot delete your own account.'; $messageType = 'danger';
        } else {
            $pdo->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$uid]);
            admin_log('users.delete', 'Deleted user id ' . $uid);
            $message = 'User removed.'; $messageType = 'success';
        }

    } elseif (isset($_POST['add_user'])) {
        $username = mb_substr(trim($_POST['username'] ?? ''), 0, 100);
        $email    = mb_substr(trim($_POST['email']    ?? ''), 0, 255) ?: null;
        $password = $_POST['password'] ?? '';
        $role     = in_array($_POST['role'] ?? '', ['super_admin','editor_coach','staff_campo'], true) ? $_POST['role'] : 'staff_campo';
        if ($username === '') {
            $message = 'Username is required.'; $messageType = 'danger';
        } elseif (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters.'; $messageType = 'danger';
        } elseif (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $message = 'Password must contain at least one letter and one number.'; $messageType = 'danger';
        } else {
            $st = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?"); $st->execute([$username]);
            if ($st->fetch()) {
                $message = 'Username already exists.'; $messageType = 'danger';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO admin_users (username, password_hash, role, email) VALUES (?,?,?,?)")
                    ->execute([$username, $hash, $role, $email]);
                admin_log('users.create', 'Created user: ' . $username . ' (role: ' . $role . ')');
                $message = 'User added. They can log in with this username and password.'; $messageType = 'success';
            }
        }

    } elseif (isset($_POST['edit_role'])) {
        $uid  = (int) $_POST['edit_role'];
        $role = in_array($_POST['role'] ?? '', ['super_admin','editor_coach','staff_campo'], true) ? $_POST['role'] : 'staff_campo';
        if ($uid === (int) $_SESSION['admin_user_id'] && $role !== 'super_admin') {
            $message = 'You cannot demote yourself from Super Admin.'; $messageType = 'danger';
        } else {
            $pdo->prepare("UPDATE admin_users SET role = ? WHERE id = ?")->execute([$role, $uid]);
            admin_log('users.edit_role', 'User id ' . $uid . ' role set to ' . $role);
            $message = 'Role updated.'; $messageType = 'success';
        }

    } elseif (isset($_POST['reset_password'])) {
        // ── Password reset (super_admin only) ──────────────────────────
        $uid      = (int) $_POST['reset_uid'];
        $newPass  = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($uid <= 0) {
            $message = 'Invalid user.'; $messageType = 'danger';
        } elseif (strlen($newPass) < 8) {
            $message = 'New password must be at least 8 characters.'; $messageType = 'danger';
        } elseif (!preg_match('/[a-zA-Z]/', $newPass) || !preg_match('/[0-9]/', $newPass)) {
            $message = 'Password must contain at least one letter and one number.'; $messageType = 'danger';
        } elseif ($newPass !== $confirm) {
            $message = 'Passwords do not match.'; $messageType = 'danger';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")->execute([$hash, $uid]);
            admin_log('users.reset_password', 'Reset password for user id ' . $uid);
            $message = 'Password reset successfully.'; $messageType = 'success';
        }
    }
}

$users = $pdo->query("SELECT id, username, email, role, created_at FROM admin_users ORDER BY role='super_admin' DESC, username ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Users & Roles - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Users & Roles']]) ?>
    <h1 class="mb-4 admin-page-title">Users &amp; Roles</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Add user -->
        <div class="col-lg-5 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Invite staff</h5>
                    <p class="small text-muted">Create a user with email and password. Assign role: Super Admin, Editor/Coach, or Staff de Campo.</p>
                    <form method="post" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="add_user" value="1">
                        <div class="mb-2">
                            <label class="form-label text-white small">Username (login)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="username" required maxlength="100" placeholder="e.g. coach_juan">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Email (optional)</label>
                            <input type="email" class="form-control bg-dark text-white border-secondary" name="email" maxlength="255" placeholder="juan@academy.com">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Password (min 8 chars, one letter + one number)</label>
                            <input type="password" class="form-control bg-dark text-white border-secondary" name="password" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Role</label>
                            <select class="form-select bg-dark text-white border-secondary" name="role">
                                <option value="staff_campo">Staff de Campo (Star of the Month, live score only)</option>
                                <option value="editor_coach">Editor / Coach (scores, MOTM, roster)</option>
                                <option value="super_admin">Super Admin (full access)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;">Add user</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- User table -->
        <div class="col-lg-7">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Users</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-bordered mb-0 align-middle">
                            <thead>
                                <tr><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                    <td><?= $u['email'] ? htmlspecialchars($u['email']) : '—' ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($u['role']) ?></span></td>
                                    <td>
                                        <!-- Role change — uses explicit Save button, not autosubmit -->
                                        <form method="post" class="mb-2" onsubmit="return confirm('Change role for <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="edit_role" value="<?= (int) $u['id'] ?>">
                                            <div class="d-flex gap-1">
                                                <select name="role" class="form-select form-select-sm bg-dark text-white border-secondary" style="min-width:9rem;">
                                                    <option value="super_admin"  <?= $u['role']==='super_admin'  ? 'selected':'' ?>>Super Admin</option>
                                                    <option value="editor_coach" <?= $u['role']==='editor_coach' ? 'selected':'' ?>>Editor / Coach</option>
                                                    <option value="staff_campo"  <?= $u['role']==='staff_campo'  ? 'selected':'' ?>>Staff de Campo</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                            </div>
                                        </form>

                                        <!-- Password reset -->
                                        <details class="mb-2">
                                            <summary class="small text-warning" style="cursor:pointer;">Reset password</summary>
                                            <form method="post" class="mt-2" onsubmit="return confirm('Reset password for <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="reset_password" value="1">
                                                <input type="hidden" name="reset_uid" value="<?= (int) $u['id'] ?>">
                                                <div class="mb-1">
                                                    <input type="password" class="form-control form-control-sm bg-dark text-white border-secondary" name="new_password" minlength="8" placeholder="New password" required>
                                                </div>
                                                <div class="mb-1">
                                                    <input type="password" class="form-control form-control-sm bg-dark text-white border-secondary" name="confirm_password" minlength="8" placeholder="Confirm password" required>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-outline-warning">Set password</button>
                                            </form>
                                        </details>

                                        <!-- Delete -->
                                        <?php if ((int) $u['id'] !== (int) $_SESSION['admin_user_id']): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Remove this user? They will no longer be able to log in.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="delete_user" value="<?= (int) $u['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
