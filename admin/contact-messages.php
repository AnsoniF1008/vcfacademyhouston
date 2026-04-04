<?php
require __DIR__ . '/includes/auth.php';
require_permission('contact_messages_view');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$message     = '';
$messageType = '';

// ── Detect columns once per request ───────────────────────────────────────
static $columns = null;
if ($columns === null) {
    try {
        $st = $pdo->query("SHOW COLUMNS FROM contact_messages");
        $columns = $st->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $columns = [];
    }
}

// ── Mark as read (super_admin / editor_coach) ─────────────────────────────
$hasReadCol = in_array('is_read', $columns, true) || in_array('read_at', $columns, true);
$readColName = in_array('is_read', $columns, true) ? 'is_read' : (in_array('read_at', $columns, true) ? 'read_at' : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request.'; $messageType = 'danger';
    } elseif (isset($_POST['delete_id']) && admin_can('*')) {
        $id = (int) $_POST['delete_id'];
        $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
        admin_log('contact_messages.delete', 'Deleted message id ' . $id);
        $message = 'Message deleted.'; $messageType = 'success';
    } elseif (isset($_POST['mark_read_id']) && $readColName) {
        $id = (int) $_POST['mark_read_id'];
        if ($readColName === 'is_read') {
            $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
        } else {
            $pdo->prepare("UPDATE contact_messages SET read_at = NOW() WHERE id = ? AND read_at IS NULL")->execute([$id]);
        }
        $message = 'Marked as read.'; $messageType = 'success';
    }
}

// ── Pagination & search ───────────────────────────────────────────────────
$per_page = 25;
$page     = max(1, (int) ($_GET['page'] ?? 1));
$search   = trim($_GET['q'] ?? '');

$messages_list = [];
$total_rows    = 0;
$total_pages   = 1;

if (empty($columns)) {
    $tableExists = false;
} else {
    $tableExists = true;

    $whereClause = '';
    $params      = [];
    if ($search !== '') {
        $searchable = array_intersect($columns, ['nombre', 'email', 'mensaje', 'name', 'message', 'subject', 'telefono', 'phone']);
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

    $stCount = $pdo->prepare("SELECT COUNT(*) FROM contact_messages" . $whereClause);
    $stCount->execute($params);
    $total_rows  = (int) $stCount->fetchColumn();
    $total_pages = max(1, (int) ceil($total_rows / $per_page));
    $page        = min($page, $total_pages);
    $offset      = ($page - 1) * $per_page;

    $stData = $pdo->prepare("SELECT * FROM contact_messages" . $whereClause . " ORDER BY `$orderCol` DESC LIMIT $per_page OFFSET $offset");
    $stData->execute($params);
    $messages_list = $stData->fetchAll(PDO::FETCH_ASSOC);
}

// Display columns (exclude internal, put created_at last)
$displayColumns = array_values(array_filter($columns, fn($c) => !in_array($c, ['id', 'updated_at', 'created_at'], true)));
if (in_array('created_at', $columns, true)) $displayColumns[] = 'created_at';

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Contact Messages - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Contact Messages']]) ?>
    <h1 class="mb-4 admin-page-title">Contact Messages</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!$tableExists): ?>
        <div class="alert alert-warning">
            The <code>contact_messages</code> table does not exist yet. Create it in your database and messages will appear here automatically.
        </div>
    <?php else: ?>

    <!-- Search bar -->
    <form method="get" class="mb-3 d-flex gap-2">
        <input type="search" name="q" class="form-control bg-dark text-white border-secondary" placeholder="Search by name, email, message…" value="<?= htmlspecialchars($search) ?>" style="max-width:360px;">
        <button type="submit" class="btn btn-outline-warning">Search</button>
        <?php if ($search !== ''): ?><a href="contact-messages.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
    </form>

    <p class="text-muted small mb-3"><?= $total_rows ?> message<?= $total_rows !== 1 ? 's' : '' ?> found<?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>.</p>

    <?php if (empty($messages_list)): ?>
        <p class="text-muted">No messages yet<?= $search ? ' matching your search' : '' ?>.</p>
    <?php else: ?>

    <!-- Card layout — easier to read long messages -->
    <div class="row g-3">
        <?php foreach ($messages_list as $row): ?>
        <?php
        $isUnread = $readColName && !$row[$readColName];
        $nombre   = $row['nombre'] ?? $row['name'] ?? '—';
        $email    = $row['email'] ?? '';
        $tel      = $row['telefono'] ?? $row['phone'] ?? '';
        $asunto   = $row['subject'] ?? $row['asunto'] ?? '';
        $mensaje  = $row['mensaje'] ?? $row['message'] ?? '';
        $createdAt = isset($row['created_at']) && $row['created_at'] ? date('M j, Y g:i A', strtotime($row['created_at'])) : '';
        // Collect remaining fields
        $known = ['id','nombre','name','email','telefono','phone','subject','asunto','mensaje','message','created_at','updated_at','is_read','read_at'];
        $extra = array_diff_key($row, array_flip($known));
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 bg-dark border border-<?= $isUnread ? 'warning' : 'secondary' ?> rounded-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-0 text-white"><?= htmlspecialchars($nombre) ?></h6>
                            <?php if ($email): ?><small><a href="mailto:<?= htmlspecialchars($email) ?>" class="text-info"><?= htmlspecialchars($email) ?></a></small><?php endif; ?>
                            <?php if ($tel): ?><br><small><a href="tel:<?= htmlspecialchars(preg_replace('/[^+\d]/', '', $tel)) ?>" class="text-info"><?= htmlspecialchars($tel) ?></a></small><?php endif; ?>
                        </div>
                        <span class="badge <?= $isUnread ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $isUnread ? 'New' : 'Read' ?></span>
                    </div>
                    <?php if ($asunto): ?><p class="text-warning small mb-1"><strong><?= htmlspecialchars($asunto) ?></strong></p><?php endif; ?>
                    <?php if ($mensaje): ?>
                    <p class="text-white-50 small mb-2" style="white-space:pre-wrap;"><?= htmlspecialchars(mb_substr($mensaje, 0, 300)) ?><?= mb_strlen($mensaje) > 300 ? '…' : '' ?></p>
                    <?php endif; ?>
                    <?php foreach ($extra as $k => $v): ?>
                    <?php if ($v !== null && $v !== ''): ?>
                    <p class="text-muted small mb-1"><em><?= htmlspecialchars(ucwords(str_replace('_', ' ', $k))) ?>:</em> <?= htmlspecialchars(mb_substr($v, 0, 100)) ?></p>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($createdAt): ?><p class="text-muted small mb-0 mt-2"><?= $createdAt ?></p><?php endif; ?>
                </div>
                <?php if (admin_can('*') || ($readColName && $isUnread)): ?>
                <div class="card-footer bg-transparent border-secondary d-flex gap-2">
                    <?php if ($isUnread && $readColName): ?>
                    <form method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="mark_read_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-warning">Mark read</button>
                    </form>
                    <?php endif; ?>
                    <?php if (admin_can('*')): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this message?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Messages pagination" class="mt-4">
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
