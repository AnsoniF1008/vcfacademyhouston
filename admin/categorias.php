<?php
require __DIR__ . '/includes/auth.php';
require_permission('categorias');
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
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Category deleted.';
        $messageType = 'success';
    } else {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $horarios_entrenamiento = trim($_POST['horarios_entrenamiento'] ?? '');

        if ($nombre === '') {
            $message = 'Category name is required.';
            $messageType = 'danger';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, horarios_entrenamiento = ? WHERE id = ?");
                $stmt->execute([$nombre, $horarios_entrenamiento ?: null, $id]);
                $message = 'Category updated.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO categorias (nombre, horarios_entrenamiento) VALUES (?, ?)");
                $stmt->execute([$nombre, $horarios_entrenamiento ?: null]);
                $message = 'Category added.';
            }
            $messageType = 'success';
        }
    }
}

$categorias = $pdo->query("SELECT id, nombre, horarios_entrenamiento FROM categorias ORDER BY nombre")->fetchAll();

$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($categorias as $c) {
        if ((int) $c['id'] === $editId) {
            $editing = $c;
            break;
        }
    }
}

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Manage Categories - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Categories']]) ?>
    <h1 class="mb-4 admin-page-title">Categories / Training Schedules</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white"><?= $editing ? 'Edit category' : 'Add category' ?></h5>
                    <form method="post" action="">
                        <?= csrf_field() ?>
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label text-white small">Name (e.g. U6, U8, U10)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="nombre" required placeholder="U6, U8, U10, U12" value="<?= htmlspecialchars($editing['nombre'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Training schedule (e.g. Tue/Thu 4:00 PM - 5:00 PM)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="horarios_entrenamiento" placeholder="Mon/Wed 4:30 PM - 5:30 PM" value="<?= htmlspecialchars($editing['horarios_entrenamiento'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;"><?= $editing ? 'Update' : 'Add' ?></button>
                        <?php if ($editing): ?>
                            <a href="categorias.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Current categories</h5>
                    <p class="text-muted small mb-3">These appear in the Methodology section and in the Join form.</p>
                    <?php if (count($categorias) === 0): ?>
                        <p class="text-muted mb-0">No categories yet. Add one with the form.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Schedule</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categorias as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($c['nombre']) ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($c['horarios_entrenamiento'] ?? '-') ?></td>
                                            <td>
                                                <a href="categorias.php?edit=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="delete_id" value="<?= (int) $c['id'] ?>">
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
