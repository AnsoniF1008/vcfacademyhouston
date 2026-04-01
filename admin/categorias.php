<?php
require __DIR__ . '/includes/auth.php';
require_permission('categorias');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';

$dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
$dayShort = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

function format_slot_display($dia_semana, $hora, $dayShort) {
    $day = $dayShort[(int) $dia_semana] ?? '';
    if ($hora === null || $hora === '') return $day;
    $t = is_string($hora) ? $hora : $hora;
    if (preg_match('/^(\d{1,2}):(\d{2})/', $t, $m)) {
        $h = (int) $m[1];
        $min = (int) $m[2];
        $ampm = $h >= 12 ? 'PM' : 'AM';
        $h12 = $h > 12 ? $h - 12 : ($h === 0 ? 12 : $h);
        return $day . ' ' . $h12 . ':' . str_pad($min, 2, '0') . ' ' . $ampm;
    }
    return $day . ' ' . $t;
}

$hasHorariosTable = false;
try {
    $pdo->query("SELECT 1 FROM categoria_horarios LIMIT 1");
    $hasHorariosTable = true;
} catch (Exception $e) {
    // table may not exist yet
}

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

        if ($nombre === '') {
            $message = 'Category name is required.';
            $messageType = 'danger';
        } else {
            $horariosLegacy = trim($_POST['horarios_entrenamiento'] ?? '');
            if ($id > 0) {
                if ($hasHorariosTable) {
                    $stmt = $pdo->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
                    $stmt->execute([$nombre, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, horarios_entrenamiento = ? WHERE id = ?");
                    $stmt->execute([$nombre, $horariosLegacy ?: null, $id]);
                }
                $catId = $id;
                $message = 'Category updated.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO categorias (nombre, horarios_entrenamiento) VALUES (?, ?)");
                $stmt->execute([$nombre, $horariosLegacy ?: null]);
                $catId = (int) $pdo->lastInsertId();
                $message = 'Category added.';
            }
            $messageType = 'success';

            if ($hasHorariosTable && $catId > 0) {
                $pdo->prepare("DELETE FROM categoria_horarios WHERE categoria_id = ?")->execute([$catId]);
                $slotDias = $_POST['slot_dia'] ?? [];
                $slotHoras = $_POST['slot_hora'] ?? [];
                if (is_array($slotDias) && is_array($slotHoras)) {
                    $ins = $pdo->prepare("INSERT INTO categoria_horarios (categoria_id, dia_semana, hora) VALUES (?, ?, ?)");
                    foreach ($slotDias as $i => $dia) {
                        $dia = (int) $dia;
                        $hora = isset($slotHoras[$i]) ? trim($slotHoras[$i]) : '';
                        if ($dia >= 1 && $dia <= 7 && $hora !== '') {
                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $hora, $m)) {
                                $h = (int) $m[1];
                                $min = (int) $m[2];
                                if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59) {
                                    $timeVal = strlen($hora) === 5 ? $hora . ':00' : $hora;
                                    $ins->execute([$catId, $dia, $timeVal]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

$categorias = $pdo->query("SELECT id, nombre, horarios_entrenamiento FROM categorias ORDER BY nombre")->fetchAll();

$slotsByCat = [];
if ($hasHorariosTable) {
    $slots = $pdo->query("SELECT categoria_id, dia_semana, hora FROM categoria_horarios ORDER BY categoria_id, dia_semana, hora")->fetchAll();
    foreach ($slots as $s) {
        $cid = (int) $s['categoria_id'];
        if (!isset($slotsByCat[$cid])) $slotsByCat[$cid] = [];
        $slotsByCat[$cid][] = ['dia_semana' => (int) $s['dia_semana'], 'hora' => $s['hora']];
    }
}

function format_schedule_display($categoria_id, $slotsByCat, $dayShort, $horarios_entrenamiento) {
    if (isset($slotsByCat[$categoria_id]) && count($slotsByCat[$categoria_id]) > 0) {
        $parts = [];
        foreach ($slotsByCat[$categoria_id] as $s) {
            $parts[] = format_slot_display($s['dia_semana'], $s['hora'], $dayShort);
        }
        return implode(', ', $parts);
    }
    return $horarios_entrenamiento !== null && $horarios_entrenamiento !== '' ? $horarios_entrenamiento : '—';
}

$editing = null;
$editingSlots = [];
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($categorias as $c) {
        if ((int) $c['id'] === $editId) {
            $editing = $c;
            break;
        }
    }
    if ($editing && $hasHorariosTable && isset($slotsByCat[(int) $editing['id']])) {
        $editingSlots = $slotsByCat[(int) $editing['id']];
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

    <?php if (!$hasHorariosTable): ?>
        <div class="alert alert-warning py-2">Run the migration <code>sql/migrate_categoria_horarios.sql</code> to manage multiple training days per category.</div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white"><?= $editing ? 'Edit category' : 'Add category' ?></h5>
                    <form method="post" action="" id="formCategoria">
                        <?= csrf_field() ?>
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label text-white small">Name (e.g. U6, U8, U10, B13)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="nombre" required placeholder="U6, U8, U10, B13" value="<?= htmlspecialchars($editing['nombre'] ?? '') ?>">
                        </div>
                        <?php if (!$hasHorariosTable): ?>
                        <div class="mb-3">
                            <label class="form-label text-white small">Training schedule (e.g. Tue/Thu 4:00 PM - 5:00 PM)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="horarios_entrenamiento" placeholder="Mon/Wed 4:30 PM - 5:30 PM" value="<?= htmlspecialchars($editing['horarios_entrenamiento'] ?? '') ?>">
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label text-white small">Training schedule (day + time per slot)</label>
                            <div id="slotsContainer">
                                <?php
                                $slotsToShow = count($editingSlots) > 0 ? $editingSlots : [['dia_semana' => 1, 'hora' => '17:00']];
                                foreach ($slotsToShow as $idx => $slot):
                                    $h = $slot['hora'];
                                    if (preg_match('/^(\d{2}):(\d{2})/', $h, $m)) $h = $m[1] . ':' . $m[2];
                                    else $h = '17:00';
                                ?>
                                <div class="slot-row d-flex gap-2 align-items-center mb-2">
                                    <select class="form-select form-select-sm bg-dark text-white border-secondary" name="slot_dia[]" style="max-width: 130px;">
                                        <?php for ($d = 1; $d <= 7; $d++): ?>
                                        <option value="<?= $d ?>" <?= ((int)($slot['dia_semana'] ?? 1) === $d) ? 'selected' : '' ?>><?= $dayNames[$d] ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <input type="time" class="form-control form-control-sm bg-dark text-white border-secondary" name="slot_hora[]" value="<?= htmlspecialchars($h) ?>" style="max-width: 110px;">
                                    <button type="button" class="btn btn-sm btn-outline-secondary slot-remove" aria-label="Remove slot">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-light mt-1" id="slotAdd">+ Add slot</button>
                        </div>
                        <?php endif; ?>
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
                    <p class="text-muted small mb-3">Add several training days per category (e.g. Monday 5pm, Wednesday 6pm, Friday 6pm).</p>
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
                                            <td class="small text-muted"><?= htmlspecialchars(format_schedule_display((int) $c['id'], $slotsByCat, $dayShort, $c['horarios_entrenamiento'] ?? null)) ?></td>
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
<?php if ($hasHorariosTable): ?>
<script>
(function() {
    var container = document.getElementById('slotsContainer');
    var addBtn = document.getElementById('slotAdd');
    if (!container || !addBtn) return;
    var dayOpts = <?= json_encode(array_map(function($d) use ($dayNames) { return '<option value="' . $d . '">' . $dayNames[$d] . '</option>'; }, range(1, 7))) ?>;
    addBtn.addEventListener('click', function() {
        var row = document.createElement('div');
        row.className = 'slot-row d-flex gap-2 align-items-center mb-2';
        row.innerHTML = '<select class="form-select form-select-sm bg-dark text-white border-secondary" name="slot_dia[]" style="max-width:130px">' + dayOpts.join('') + '</select>' +
            '<input type="time" class="form-control form-control-sm bg-dark text-white border-secondary" name="slot_hora[]" value="17:00" style="max-width:110px">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary slot-remove" aria-label="Remove slot">&times;</button>';
        container.appendChild(row);
        row.querySelector('.slot-remove').addEventListener('click', function() {
            if (container.querySelectorAll('.slot-row').length > 1) row.remove();
        });
    });
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('slot-remove') && container.querySelectorAll('.slot-row').length > 1) {
            e.target.closest('.slot-row').remove();
        }
    });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
