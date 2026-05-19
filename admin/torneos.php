<?php
require __DIR__ . '/includes/auth.php';
require_permission('torneos');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/page_cache.php';

$message     = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_archived') {
    header('Content-Type: application/json');
    if (!csrf_verify()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    $id = (int) ($_POST['id'] ?? 0);
    $archivado = (int) ($_POST['archivado'] ?? 0) === 1 ? 1 : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }
    try {
        $pdo->prepare('UPDATE torneos_info SET archivado = ? WHERE id = ?')->execute([$archivado, $id]);
        admin_log($archivado ? 'torneos.archive' : 'torneos.unarchive', 'Tournament id ' . $id . ($archivado ? ' archived' : ' activated'));
        vcf_page_cache_clear();
        echo json_encode(['success' => true, 'archivado' => $archivado]);
    } catch (PDOException $e) {
        error_log('Toggle archivado: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB error']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.'; $messageType = 'danger';
    } elseif (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        $stName = $pdo->prepare("SELECT nombre_torneo FROM torneos_info WHERE id = ?");
        $stName->execute([$id]);
        $delName = $stName->fetchColumn() ?: $id;
        $pdo->prepare("DELETE FROM torneos_info WHERE id = ?")->execute([$id]);
        admin_log('torneos.delete', 'Deleted tournament "' . $delName . '" (id ' . $id . ') and all its games');
        $message = 'Tournament deleted (and all its games).'; $messageType = 'success';
    } else {
        $id             = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $nombre_torneo  = mb_substr(trim($_POST['nombre_torneo'] ?? ''), 0, 255);
        $temporada      = mb_substr(trim($_POST['temporada']     ?? ''), 0, 100) ?: null;

        if ($nombre_torneo === '') {
            $message = 'Tournament name is required.'; $messageType = 'danger';
        } else {
            if ($id > 0) {
                $pdo->prepare("UPDATE torneos_info SET nombre_torneo=?, temporada=? WHERE id=?")
                    ->execute([$nombre_torneo, $temporada, $id]);
                admin_log('torneos.update', 'Updated tournament "' . $nombre_torneo . '" (id ' . $id . ')');
                $message = 'Tournament updated.';
            } else {
                $pdo->prepare("INSERT INTO torneos_info (nombre_torneo, temporada) VALUES (?,?)")
                    ->execute([$nombre_torneo, $temporada]);
                admin_log('torneos.create', 'Created tournament "' . $nombre_torneo . '"' . ($temporada ? ' — ' . $temporada : ''));
                $message = 'Tournament added.';
            }
            $messageType = 'success';
        }
    }
}

$hasArchivadoCol = false;
try {
    $stCol = $pdo->query("SHOW COLUMNS FROM torneos_info LIKE 'archivado'");
    $hasArchivadoCol = $stCol && $stCol->fetch();
} catch (PDOException $e) {
    // ignore
}
$archivadoSelect = $hasArchivadoCol ? ', archivado' : ', 0 AS archivado';
$torneos = $pdo->query("
    SELECT id, nombre_torneo, temporada{$archivadoSelect},
           (SELECT COUNT(*) FROM juegos WHERE torneo_id = torneos_info.id) AS total_juegos
    FROM torneos_info
    ORDER BY " . ($hasArchivadoCol ? 'archivado ASC, ' : '') . "nombre_torneo
")->fetchAll();

$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($torneos as $t) {
        if ((int) $t['id'] === $editId) { $editing = $t; break; }
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
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="nombre_torneo" required maxlength="255" placeholder="e.g. Houston Spring Cup 2026" value="<?= htmlspecialchars($editing['nombre_torneo'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Season (optional)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="temporada" maxlength="100" placeholder="e.g. Spring 2026" value="<?= htmlspecialchars($editing['temporada'] ?? '') ?>">
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
                                        <th>Games</th>
                                        <?php if ($hasArchivadoCol): ?><th>Status</th><?php endif; ?>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($torneos as $t): ?>
                                        <tr data-torneo-id="<?= (int) $t['id'] ?>" class="<?= !empty($t['archivado']) ? 'row-archived' : '' ?>">
                                            <td><?= htmlspecialchars($t['nombre_torneo']) ?></td>
                                            <td><?= $t['temporada'] !== null ? htmlspecialchars($t['temporada']) : '—' ?></td>
                                            <td><?= (int) ($t['total_juegos'] ?? 0) ?></td>
                                            <?php if ($hasArchivadoCol): ?>
                                            <td>
                                                <label class="archive-toggle" title="<?= !empty($t['archivado']) ? 'Archived — shown in past section' : 'Active on home page' ?>">
                                                    <input type="checkbox" class="js-archive-checkbox" data-id="<?= (int) $t['id'] ?>" <?= !empty($t['archivado']) ? 'checked' : '' ?>>
                                                    <span class="toggle-slider"></span>
                                                    <span class="toggle-label"><?= !empty($t['archivado']) ? 'Archived' : 'Active' ?></span>
                                                </label>
                                            </td>
                                            <?php endif; ?>
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
<?php if ($hasArchivadoCol): ?>
<script>
(function () {
    const csrfToken = <?= json_encode(csrf_token()) ?>;
    document.querySelectorAll('.js-archive-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', async function () {
            const id = this.dataset.id;
            const archivado = this.checked ? 1 : 0;
            const row = this.closest('tr');
            const label = row.querySelector('.toggle-label');
            this.disabled = true;
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_archived');
                formData.append('id', id);
                formData.append('archivado', String(archivado));
                formData.append('csrf_token', csrfToken);
                const res = await fetch(window.location.pathname, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    row.classList.toggle('row-archived', archivado === 1);
                    label.textContent = archivado === 1 ? 'Archived' : 'Active';
                } else {
                    this.checked = !this.checked;
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (err) {
                this.checked = !this.checked;
                alert('Network error: ' + err.message);
            } finally {
                this.disabled = false;
            }
        });
    });
})();
</script>
<style>
.archive-toggle { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; user-select: none; }
.archive-toggle input[type="checkbox"] { position: absolute; opacity: 0; pointer-events: none; }
.toggle-slider { position: relative; width: 36px; height: 20px; background: #00C853; border-radius: 10px; transition: background 0.2s ease; }
.toggle-slider::before { content: ''; position: absolute; width: 16px; height: 16px; background: #fff; border-radius: 50%; top: 2px; left: 2px; transition: transform 0.2s ease; }
.archive-toggle input:checked + .toggle-slider { background: #666; }
.archive-toggle input:checked + .toggle-slider::before { transform: translateX(16px); }
.toggle-label { font-size: 0.85rem; font-weight: 600; color: #ccc; }
.row-archived { opacity: 0.65; }
</style>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
