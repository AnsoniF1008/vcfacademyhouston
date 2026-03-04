<?php
require __DIR__ . '/includes/auth.php';
require_permission('activity_log_view');
require __DIR__ . '/../config/database.php';

$table_exists = false;
try {
    $pdo->query("SELECT 1 FROM admin_activity_log LIMIT 1");
    $table_exists = true;
} catch (PDOException $e) {}

if (!$table_exists) {
    $page_title = 'Activity Log - VCF Academy Houston';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5"><div class="alert alert-warning">Run the RBAC migration first: <code>sql/migrate_rbac.sql</code> to create the activity log table.</div><p><a href="dashboard.php">&larr; Dashboard</a></p></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$limit = min(500, max(50, (int) ($_GET['limit'] ?? 100)));
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$filter_user = isset($_GET['user']) ? trim($_GET['user']) : '';
$count_sql = "SELECT COUNT(*) FROM admin_activity_log";
$list_sql = "SELECT id, user_id, username, action, details, created_at FROM admin_activity_log";
$params = [];
if ($filter_user !== '') {
    $count_sql .= " WHERE username = ?";
    $list_sql .= " WHERE username = ?";
    $params[] = $filter_user;
}
$list_sql .= " ORDER BY created_at DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset;

$st = $pdo->prepare($count_sql);
$st->execute($params);
$total = (int) $st->fetchColumn();

$stmt = $pdo->prepare($list_sql);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Activity Log - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <h1 class="mb-4" style="color: #FF6600;">Activity Log</h1>
    <p><a href="dashboard.php" class="text-decoration-none" style="color: #FF6600;">&larr; Dashboard</a></p>

    <form method="get" class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <label class="text-white small">Filter by user</label>
        <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" name="user" value="<?= htmlspecialchars($filter_user) ?>" placeholder="username">
        <input type="hidden" name="limit" value="<?= (int) $limit ?>">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
        <a href="activity-log.php" class="btn btn-sm btn-outline-secondary">Clear</a>
    </form>

    <div class="card bg-dark border border-secondary rounded-3">
        <div class="card-body">
            <p class="text-muted small mb-2">Who did what and when. Last <?= (int) $limit ?> entries<?= $filter_user ? ' for user "' . htmlspecialchars($filter_user) . '"' : '' ?>.</p>
            <div class="table-responsive">
                <table class="table table-dark table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $e): ?>
                        <tr>
                            <td class="text-nowrap small"><?= htmlspecialchars($e['created_at']) ?></td>
                            <td><?= htmlspecialchars($e['username']) ?></td>
                            <td><code class="small"><?= htmlspecialchars($e['action']) ?></code></td>
                            <td class="small"><?= htmlspecialchars($e['details']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($entries)): ?>
                        <tr><td colspan="4" class="text-muted">No entries yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total > $limit): ?>
            <p class="text-muted small mt-2">Showing <?= count($entries) ?> of <?= $total ?> total.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
