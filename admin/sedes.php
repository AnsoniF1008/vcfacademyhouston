<?php
require __DIR__ . '/includes/auth.php';
require_permission('sedes');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } elseif (isset($_POST['delete_sede_id'])) {
        $id = (int) $_POST['delete_sede_id'];
        $stmt = $pdo->prepare("DELETE FROM sedes WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Sede deleted (and its fields).';
        $messageType = 'success';
    } elseif (isset($_POST['delete_cancha_id'])) {
        $id = (int) $_POST['delete_cancha_id'];
        $stmt = $pdo->prepare("DELETE FROM canchas WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Field deleted.';
        $messageType = 'success';
    } elseif (isset($_POST['save_sede'])) {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $mapa_general_url = trim($_POST['mapa_general_url'] ?? '');
        $nota_acceso = trim($_POST['nota_acceso'] ?? '');

        if ($nombre === '' || $direccion === '') {
            $message = 'Name and address are required.';
            $messageType = 'danger';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE sedes SET nombre = ?, direccion = ?, mapa_general_url = ?, nota_acceso = ? WHERE id = ?");
                $stmt->execute([$nombre, $direccion, $mapa_general_url ?: null, $nota_acceso ?: null, $id]);
                $message = 'Sede updated.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO sedes (nombre, direccion, mapa_general_url, nota_acceso) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nombre, $direccion, $mapa_general_url ?: null, $nota_acceso ?: null]);
                $message = 'Sede added.';
            }
            $messageType = 'success';
        }
    } elseif (isset($_POST['save_cancha'])) {
        $cancha_id = isset($_POST['cancha_id']) ? (int) $_POST['cancha_id'] : 0;
        $sede_id = (int) ($_POST['sede_id'] ?? 0);
        $numero_cancha = trim($_POST['numero_cancha'] ?? '');
        $sobrenombre = trim($_POST['sobrenombre'] ?? '');
        $indicaciones_extra = trim($_POST['indicaciones_extra'] ?? '');
        $mapa_url = trim($_POST['mapa_url'] ?? '');

        if ($sede_id <= 0 || $numero_cancha === '') {
            $message = 'Sede and field number/name are required.';
            $messageType = 'danger';
        } else {
            if ($cancha_id > 0) {
                $stmt = $pdo->prepare("UPDATE canchas SET numero_cancha = ?, sobrenombre = ?, indicaciones_extra = ?, mapa_url = ? WHERE id = ?");
                $stmt->execute([$numero_cancha, $sobrenombre ?: null, $indicaciones_extra ?: null, $mapa_url ?: null, $cancha_id]);
                $message = 'Field updated.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO canchas (sede_id, numero_cancha, sobrenombre, indicaciones_extra, mapa_url) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$sede_id, $numero_cancha, $sobrenombre ?: null, $indicaciones_extra ?: null, $mapa_url ?: null]);
                $message = 'Field added.';
            }
            $messageType = 'success';
        }
    }
}

$sedes = $pdo->query("SELECT id, nombre, direccion, mapa_general_url, nota_acceso FROM sedes ORDER BY nombre")->fetchAll();

$editing = null;
$canchasBySede = [];
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($sedes as $s) {
        if ((int) $s['id'] === $editId) {
            $editing = $s;
            break;
        }
    }
    if ($editing) {
        $stmt = $pdo->prepare("SELECT id, sede_id, numero_cancha, sobrenombre, indicaciones_extra, mapa_url FROM canchas WHERE sede_id = ? ORDER BY numero_cancha");
        $stmt->execute([$editing['id']]);
        $canchasBySede = $stmt->fetchAll();
    }
}

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Manage Sedes & Fields - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Sedes']]) ?>
    <h1 class="mb-4 admin-page-title">Sedes / Training Grounds</h1>
    <p><a href="dashboard.php" class="text-decoration-none" style="color: #FF6600;">&larr; Dashboard</a></p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white"><?= $editing ? 'Edit sede' : 'Add sede' ?></h5>
                    <p class="small text-white-50">Park/venue name and main entrance. Add specific fields below when editing.</p>
                    <form method="post" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="save_sede" value="1">
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label text-white small">Park name</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="nombre" required placeholder="e.g. Bear Creek Park" value="<?= htmlspecialchars($editing['nombre'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Address</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="direccion" required placeholder="e.g. 3535 War Memorial Dr, Houston, TX" value="<?= htmlspecialchars($editing['direccion'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Main entrance map URL</label>
                            <input type="url" class="form-control bg-dark text-white border-secondary" name="mapa_general_url" placeholder="https://maps.google.com/... or Plus Code" value="<?= htmlspecialchars($editing['mapa_general_url'] ?? '') ?>">
                            <span class="small text-white-50">Link to park entrance. Plus Codes or long-press pin work best.</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Access note (optional)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="nota_acceso" placeholder="e.g. Use the North Entrance for easier access" value="<?= htmlspecialchars($editing['nota_acceso'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;"><?= $editing ? 'Update' : 'Add' ?></button>
                        <?php if ($editing): ?>
                            <a href="sedes.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if ($editing): ?>
            <div class="card bg-dark border border-secondary rounded-3 mt-3">
                <div class="card-body">
                    <h6 class="card-title text-white">Add field (cancha)</h6>
                    <p class="small text-white-50">Specific field number/name and exact map pin so parents find the right spot.</p>
                    <form method="post" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="save_cancha" value="1">
                        <input type="hidden" name="sede_id" value="<?= (int) $editing['id'] ?>">
                        <div class="mb-2">
                            <label class="form-label text-white small">Field number/name</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="numero_cancha" required placeholder="e.g. Field #5 (U8-U10)">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Nickname (optional)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="sobrenombre" placeholder="e.g. The Mestalla Pitch">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">How to get there from parking</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="indicaciones_extra" placeholder="e.g. Near playground area">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Exact map URL (Plus Code or pin)</label>
                            <input type="url" class="form-control bg-dark text-white border-secondary" name="mapa_url" placeholder="e.g. https://maps.google.com/?q=R5M2%2B8X Houston">
                            <span class="small text-white-50">Google Plus Code or long-press pin so parents get to the sideline.</span>
                        </div>
                        <button type="submit" class="btn btn-outline-warning btn-sm">Add field</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-7">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Current sedes</h5>
                    <?php if (count($sedes) === 0): ?>
                        <p class="text-muted mb-0">No sedes yet. Add one with the form.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Address</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sedes as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['nombre']) ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($s['direccion']) ?></td>
                                            <td>
                                                <a href="sedes.php?edit=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit / Fields</a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this sede and all its fields?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="delete_sede_id" value="<?= (int) $s['id'] ?>">
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
            <?php if ($editing && count($canchasBySede) > 0): ?>
            <div class="card bg-dark border border-secondary rounded-3 mt-3">
                <div class="card-body">
                    <h6 class="card-title text-white">Fields for <?= htmlspecialchars($editing['nombre']) ?></h6>
                    <div class="table-responsive">
                        <table class="table table-dark table-sm">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Reference</th>
                                    <th>Map</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($canchasBySede as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['numero_cancha']) ?><?= !empty($c['sobrenombre']) ? ' <span class="text-white-50">(' . htmlspecialchars($c['sobrenombre']) . ')</span>' : '' ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($c['indicaciones_extra'] ?? '-') ?></td>
                                        <td><?= !empty($c['mapa_url']) ? '<a href="' . htmlspecialchars($c['mapa_url']) . '" target="_blank" class="small">Link</a>' : '-' ?></td>
                                        <td>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this field?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="delete_cancha_id" value="<?= (int) $c['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
