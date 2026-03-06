<?php
require __DIR__ . '/includes/auth.php';
require_permission('torneos');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } elseif (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM torneos_info WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Tournament deleted (and all its games).';
        $messageType = 'success';
    } else {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $nombre_torneo = trim($_POST['nombre_torneo'] ?? '');
        $temporada = trim($_POST['temporada'] ?? '') ?: null;

        if ($nombre_torneo === '') {
            $message = 'Tournament name is required.';
            $messageType = 'danger';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE torneos_info SET nombre_torneo = ?, temporada = ? WHERE id = ?");
                $stmt->execute([$nombre_torneo, $temporada, $id]);
                $message = 'Tournament updated.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO torneos_info (nombre_torneo, temporada) VALUES (?, ?)");
                $stmt->execute([$nombre_torneo, $temporada]);
                $message = 'Tournament added.';
            }
            $messageType = 'success';
        }
    }
}

$torneos = $pdo->query("SELECT id, nombre_torneo, temporada FROM torneos_info ORDER BY nombre_torneo")->fetchAll();

$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($torneos as $t) {
        if ((int) $t['id'] === $editId) {
            $editing = $t;
            break;
        }
    }
}

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Manage Tournaments - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Torneos']]) ?>
    <h1 class="mb-4 admin-page-title">Tournaments (Torneos)</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row admin-torneos-row">
        <div class="col-md-6 col-lg-6 mb-4">
            <div class="card bg-dark border border-secondary rounded-3 h-100">
                <div class="card-body">
                    <h5 class="card-title text-white"><?= $editing ? 'Edit tournament' : 'Add tournament' ?></h5>
                    <form method="post" action="">
                        <?= csrf_field() ?>
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label text-white small">Tournament name</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="nombre_torneo" required placeholder="e.g. Houston Spring Cup 2026" value="<?= htmlspecialchars($editing['nombre_torneo'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Season (optional)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="temporada" placeholder="e.g. Spring 2026" value="<?= htmlspecialchars($editing['temporada'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-admin-primary"><?= $editing ? 'Update' : 'Add' ?></button>
                        <?php if ($editing): ?>
                            <a href="torneos.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-6">
            <div class="card bg-dark border border-secondary rounded-3 admin-card-list h-100">
                <div class="card-body">
                    <h5 class="card-title text-white">Tournaments</h5>
                    <?php if (count($torneos) === 0): ?>
                        <p class="text-muted mb-0">No tournaments yet. Add one, then manage its games.</p>
                    <?php else: ?>
                        <div class="table-responsive admin-table-wrap">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Tournament</th>
                                        <th>Season</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($torneos as $t): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['nombre_torneo']) ?></td>
                                            <td><?= $t['temporada'] !== null ? htmlspecialchars($t['temporada']) : '—' ?></td>
                                            <td>
                                                <a href="juegos.php?torneo_id=<?= (int) $t['id'] ?>" class="btn btn-sm btn-admin-primary">Manage games</a>
                                                <a href="torneos.php?edit=<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this tournament and all its games?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="delete_id" value="<?= (int) $t['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
