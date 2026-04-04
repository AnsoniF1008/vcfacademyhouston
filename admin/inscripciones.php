<?php
require __DIR__ . '/includes/auth.php';
require_permission('inscripciones_view');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$message     = '';
$messageType = '';

// ── Detect columns once per request ───────────────────────────────────────
static $columns = null;
if ($columns === null) {
    try {
        $st = $pdo->query("SHOW COLUMNS FROM inscripciones");
        $columns = $st->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $columns = [];
    }
}

// ── Delete (super_admin only) ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && admin_can('*')) {
    if (!csrf_verify()) {
        $message = 'Invalid request.'; $messageType = 'danger';
    } else {
        $id = (int) $_POST['delete_id'];
        $pdo->prepare("DELETE FROM inscripciones WHERE id = ?")->execute([$id]);
        admin_log('inscripciones.delete', 'Deleted inscription id ' . $id);
        $message = 'Inscription deleted.'; $messageType = 'success';
    }
}

// ── Pagination & search ───────────────────────────────────────────────────
$per_page = 25;
$page     = max(1, (int) ($_GET['page'] ?? 1));
$search   = trim($_GET['q'] ?? '');

$inscripciones = [];
$total_rows    = 0;
$total_pages   = 1;

if (empty($columns)) {
    $tableExists = false;
} else {
    $tableExists = true;

    // Build a safe WHERE clause using only columns that actually exist
    $whereClause = '';
    $params      = [];
    if ($search !== '') {
        $searchable = array_intersect($columns, ['nombre', 'email', 'telefono', 'categoria', 'mensaje', 'padre', 'parent_name', 'player_name']);
        $clauses    = [];
        foreach ($searchable as $col) {
            $clauses[] = "`$col` LIKE ?";
            $params[]  = '%' . $search . '%';
        }
        if ($clauses) {
            $whereClause = ' WHERE (' . implode(' OR ', $clauses) . ')';
        }
    }

    $orderCol = in_array('created_at', $columns, true) ? 'created_at' : 'id';

    $stCount = $pdo->prepare("SELECT COUNT(*) FROM inscripciones" . $whereClause);
    $stCount->execute($params);
    $total_rows  = (int) $stCount->fetchColumn();
    $total_pages = max(1, (int) ceil($total_rows / $per_page));
    $page        = min($page, $total_pages);
    $offset      = ($page - 1) * $per_page;

    $stData = $pdo->prepare("SELECT * FROM inscripciones" . $whereClause . " ORDER BY `$orderCol` DESC LIMIT $per_page OFFSET $offset");
    $stData->execute($params);
    $inscripciones = $stData->fetchAll(PDO::FETCH_ASSOC);
}

// Columns to display (exclude id, internal timestamps aside from created_at)
$displayColumns = array_filter($columns, function ($c) {
    return !in_array($c, ['id', 'updated_at'], true);
});
// Move created_at to the end
$displayColumns = array_values(array_filter($displayColumns, fn($c) => $c !== 'created_at'));
if (in_array('created_at', $columns, true)) $displayColumns[] = 'created_at';

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Inscripciones - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Inscripciones']]) ?>
    <h1 class="mb-4 admin-page-title">Inscripciones</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!$tableExists): ?>
        <div class="alert alert-warning">
            The <code>inscripciones</code> table does not exist yet. Create it in your database and the records will appear here automatically.
        </div>
    <?php else: ?>

    <!-- Search bar -->
    <form method="get" class="mb-3 d-flex gap-2">
        <input type="search" name="q" class="form-control bg-dark text-white border-secondary" placeholder="Search by name, email, category…" value="<?= htmlspecialchars($search) ?>" style="max-width:360px;">
        <button type="submit" class="btn btn-outline-warning">Search</button>
        <?php if ($search !== ''): ?><a href="inscripciones.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
    </form>

    <p class="text-muted small mb-3"><?= $total_rows ?> inscription<?= $total_rows !== 1 ? 's' : '' ?> found<?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>.</p>

    <?php if (empty($inscripciones)): ?>
        <p class="text-muted">No inscriptions yet<?= $search ? ' matching your search' : '' ?>.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-dark table-bordered table-sm align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <?php foreach ($displayColumns as $col): ?>
                        <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $col))) ?></th>
                    <?php endforeach; ?>
                    <?php if (admin_can('*')): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inscripciones as $row): ?>
                <tr>
                    <td class="text-muted small"><?= (int) $row['id'] ?></td>
                    <?php foreach ($displayColumns as $col): ?>
                    <td>
                        <?php
                        $val = $row[$col] ?? '';
                        if ($col === 'created_at' && $val) {
                            echo htmlspecialchars(date('M j, Y g:i A', strtotime($val)));
                        } elseif ($col === 'email' && $val) {
                            echo '<a href="mailto:' . htmlspecialchars($val) . '" class="text-info">' . htmlspecialchars($val) . '</a>';
                        } elseif ($col === 'telefono' || $col === 'phone') {
                            echo '<a href="tel:' . htmlspecialchars(preg_replace('/[^+\d]/', '', $val)) . '" class="text-info">' . htmlspecialchars($val) . '</a>';
                        } elseif (strlen($val) > 120) {
                            echo '<span title="' . htmlspecialchars($val) . '">' . htmlspecialchars(mb_substr($val, 0, 120)) . '…</span>';
                        } else {
                            echo htmlspecialchars($val);
                        }
                        ?>
                    </td>
                    <?php endforeach; ?>
                    <?php if (admin_can('*')): ?>
                    <td>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this inscription?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="delete_id" value="<?= (int) $row['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Inscripciones pagination" class="mt-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <p class="text-muted small mb-0">Page <?= $page ?> of <?= $total_pages ?></p>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link bg-dark border-secondary text-white" href="?page=<?= $page - 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?>">Previous</a>
                </li>
                <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link bg-dark border-secondary text-white" href="?page=<?= $p ?><?= $search ? '&q=' . urlencode($search) : '' ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link bg-dark border-secondary text-white" href="?page=<?= $page + 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?>">Next</a>
                </li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
