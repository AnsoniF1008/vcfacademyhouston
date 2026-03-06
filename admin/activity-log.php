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
    require_once __DIR__ . '/includes/breadcrumb.php';
    $page_title = 'Activity Log - VCF Academy Houston';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5">' . admin_breadcrumb([['label' => 'Activity log']]) . '<div class="alert alert-warning">Run the RBAC migration first: <code>sql/migrate_rbac.sql</code> to create the activity log table.</div><p><a href="dashboard.php">&larr; Dashboard</a></p></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$limit_raw = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
$limit = in_array($limit_raw, [50, 100, 200], true) ? $limit_raw : 100;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$filter_user = isset($_GET['user']) ? trim($_GET['user']) : '';
$count_sql = "SELECT COUNT(*) FROM admin_activity_log";
$list_base_sql = "SELECT id, user_id, username, action, details, created_at FROM admin_activity_log";
$params = [];
if ($filter_user !== '') {
    $count_sql .= " WHERE username = ?";
    $list_base_sql .= " WHERE username = ?";
    $params[] = $filter_user;
}
$list_base_sql .= " ORDER BY created_at DESC";

$st = $pdo->prepare($count_sql);
$st->execute($params);
$total = (int) $st->fetchColumn();
$total_pages = ($total > 0 && $limit > 0) ? (int) ceil($total / $limit) : 1;
$page = min(max(1, $page), $total_pages);
$offset = ($page - 1) * $limit;

$list_sql = $list_base_sql . " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
$stmt = $pdo->prepare($list_sql);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Activity Log - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Activity log']]) ?>
    <h1 class="mb-4 admin-page-title">Activity Log</h1>

    <form method="get" class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <label class="text-white small">Filter by user</label>
        <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" name="user" value="<?= htmlspecialchars($filter_user) ?>" placeholder="username">
        <label class="text-white small ms-2">Limit</label>
        <select name="limit" class="form-select form-select-sm bg-dark text-white border-secondary" style="width: auto;">
            <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100</option>
            <option value="200" <?= $limit === 200 ? 'selected' : '' ?>>200</option>
        </select>
        <input type="hidden" name="page" value="1">
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
                            <td class="text-nowrap small"><?= htmlspecialchars($e['created_at'] ?? '') ?></td>
                            <td><?= htmlspecialchars($e['username'] ?? '') ?></td>
                            <td><code class="small"><?= htmlspecialchars($e['action'] ?? '') ?></code></td>
                            <td class="small"><?= htmlspecialchars($e['details'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($entries)): ?>
                        <tr><td colspan="4" class="text-muted">No entries yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                <p class="text-muted small mb-0">Page <?= (int) $page ?> of <?= (int) $total_pages ?><?= $total_pages > 0 ? ' &middot; ' . (int) $total . ' total entries' : '' ?></p>
                <nav aria-label="Activity log pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link bg-dark border-secondary text-white" href="?page=<?= $page - 1 ?>&limit=<?= (int) $limit ?><?= $filter_user !== '' ? '&user=' . urlencode($filter_user) : '' ?>">Previous</a>
                        </li>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link bg-dark border-secondary text-white" href="?page=<?= $page + 1 ?>&limit=<?= (int) $limit ?><?= $filter_user !== '' ? '&user=' . urlencode($filter_user) : '' ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
