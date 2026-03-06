<?php
require __DIR__ . '/includes/auth.php';
require_permission('roster_edit');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

$message = '';
$messageType = '';

$hasSubPosicion = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM roster LIKE 'sub_posicion'");
    $hasSubPosicion = $stmt && $stmt->fetch();
} catch (PDOException $e) {
    // table or column missing
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } elseif (isset($_POST['delete_id']) && admin_can('roster_delete')) {
        $id = (int) $_POST['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM roster WHERE id = ?");
        $stmt->execute([$id]);
        admin_log('roster.delete', 'Deleted roster id ' . $id);
        $message = 'Player removed from roster.';
        $messageType = 'success';
    } elseif (isset($_POST['save_stats']) && isset($_POST['roster_id'])) {
        $rid = (int) $_POST['roster_id'];
        $partidos = (int) ($_POST['partidos_jugados'] ?? 0);
        $goles = (int) ($_POST['goles'] ?? 0);
        $asistencias = (int) ($_POST['asistencias'] ?? 0);
        $clean_sheets = (int) ($_POST['clean_sheets'] ?? 0);
        $pace = max(1, min(10, (int) ($_POST['pace'] ?? 5)));
        $shooting = max(1, min(10, (int) ($_POST['shooting'] ?? 5)));
        $passing = max(1, min(10, (int) ($_POST['passing'] ?? 5)));
        $dribbling = max(1, min(10, (int) ($_POST['dribbling'] ?? 5)));
        $defense = max(1, min(10, (int) ($_POST['defense'] ?? 5)));
        $physical = max(1, min(10, (int) ($_POST['physical'] ?? 5)));
        $stmt = $pdo->prepare("SELECT 1 FROM roster WHERE id = ?");
        $stmt->execute([$rid]);
        if ($stmt->fetch()) {
            $pdo->prepare("INSERT INTO roster_estadisticas (roster_id, partidos_jugados, goles, asistencias, motm, clean_sheets) VALUES (?, ?, ?, ?, 0, ?) ON DUPLICATE KEY UPDATE partidos_jugados = VALUES(partidos_jugados), goles = VALUES(goles), asistencias = VALUES(asistencias), clean_sheets = VALUES(clean_sheets)")->execute([$rid, $partidos, $goles, $asistencias, $clean_sheets]);
            $pdo->prepare("INSERT INTO roster_habilidades (roster_id, pace, shooting, passing, dribbling, defense, physical) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE pace = VALUES(pace), shooting = VALUES(shooting), passing = VALUES(passing), dribbling = VALUES(dribbling), defense = VALUES(defense), physical = VALUES(physical)")->execute([$rid, $pace, $shooting, $passing, $dribbling, $defense, $physical]);
            admin_log('roster.stats', 'Updated stats/skills for roster id ' . $rid);
            $message = 'Stats and skills saved. MOTM is updated automatically from votes.';
            $messageType = 'success';
        }
    } else {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $dorsal = isset($_POST['dorsal']) && $_POST['dorsal'] !== '' ? (int) $_POST['dorsal'] : null;
        $posicion = trim($_POST['posicion'] ?? '');
        $sub_posicion = trim($_POST['sub_posicion'] ?? '');
        $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $foto_url = null;

        $subPosicionesPermitidas = [
            'Portero' => ['Portero'],
            'Defensa' => ['Lateral izquierdo', 'Central izquierdo', 'Central derecho', 'Lateral derecho'],
            'Mediocampista' => ['Pivote', 'Interior izquierdo', 'Interior derecho'],
            'Delantero' => ['Extremo izquierdo', 'Delantero centro (9)', 'Extremo derecho'],
        ];
        if ($sub_posicion !== '' && (!isset($subPosicionesPermitidas[$posicion]) || !in_array($sub_posicion, $subPosicionesPermitidas[$posicion], true))) {
            $sub_posicion = '';
        }
        if ($posicion === '') {
            $sub_posicion = '';
        }

        if ($nombre === '' || $apellido === '' || $categoria_id <= 0) {
            $message = 'Name, last name and category are required.';
            $messageType = 'danger';
        } else {
            $posicionVal = in_array($posicion, ['Portero', 'Defensa', 'Mediocampista', 'Delantero'], true) ? $posicion : null;

            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['foto']['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, $allowedTypes, true)) {
                    $message = 'Invalid image type. Use JPG, PNG or WebP.';
                    $messageType = 'danger';
                } elseif ($_FILES['foto']['size'] > $maxSize) {
                    $message = 'Image too large. Max 5MB.';
                    $messageType = 'danger';
                } else {
                    $clientName = basename($_FILES['foto']['name'] ?? '');
                    if (strpos($clientName, '..') !== false || preg_match('/\.(php|phtml|php3|php4|php5|phar|htaccess)(\.|$)/i', $clientName)) {
                        $message = 'Invalid file name.';
                        $messageType = 'danger';
                    } else {
                    $ext = match ($mime) {
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        default => 'jpg',
                    };
                    $ext = in_array($ext, ['jpg', 'png', 'webp'], true) ? $ext : 'jpg';
                    $filename = 'roster-' . uniqid() . '.' . $ext;
                    if (strpos($filename, '..') !== false || strpbrk($filename, '/\\') !== false) {
                        $message = 'Invalid file name.';
                        $messageType = 'danger';
                    } else {
                    $path = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $path)) {
                        $foto_url = 'assets/uploads/' . $filename;
                    }
                    }
                    }
                }
            }

            if ($message === '') {
                $subPosicionVal = $hasSubPosicion && $sub_posicion !== '' ? $sub_posicion : null;
                if ($id > 0) {
                    if ($hasSubPosicion) {
                        if ($foto_url !== null) {
                            $stmt = $pdo->prepare("UPDATE roster SET nombre = ?, apellido = ?, dorsal = ?, posicion = ?, sub_posicion = ?, categoria_id = ?, foto_url = ?, activo = ? WHERE id = ?");
                            $stmt->execute([$nombre, $apellido, $dorsal, $posicionVal, $subPosicionVal, $categoria_id, $foto_url, $activo, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE roster SET nombre = ?, apellido = ?, dorsal = ?, posicion = ?, sub_posicion = ?, categoria_id = ?, activo = ? WHERE id = ?");
                            $stmt->execute([$nombre, $apellido, $dorsal, $posicionVal, $subPosicionVal, $categoria_id, $activo, $id]);
                        }
                    } else {
                        if ($foto_url !== null) {
                            $stmt = $pdo->prepare("UPDATE roster SET nombre = ?, apellido = ?, dorsal = ?, posicion = ?, categoria_id = ?, foto_url = ?, activo = ? WHERE id = ?");
                            $stmt->execute([$nombre, $apellido, $dorsal, $posicionVal, $categoria_id, $foto_url, $activo, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE roster SET nombre = ?, apellido = ?, dorsal = ?, posicion = ?, categoria_id = ?, activo = ? WHERE id = ?");
                            $stmt->execute([$nombre, $apellido, $dorsal, $posicionVal, $categoria_id, $activo, $id]);
                        }
                    }
                    admin_log('roster.update', 'Updated player: ' . $nombre . ' ' . $apellido . ' (id ' . $id . ')');
                    $message = 'Player updated.';
                } else {
                    if ($hasSubPosicion) {
                        $stmt = $pdo->prepare("INSERT INTO roster (nombre, apellido, dorsal, posicion, sub_posicion, categoria_id, foto_url, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nombre, $apellido, $dorsal, $posicionVal, $subPosicionVal, $categoria_id, $foto_url ?? '', $activo]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO roster (nombre, apellido, dorsal, posicion, categoria_id, foto_url, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nombre, $apellido, $dorsal, $posicionVal, $categoria_id, $foto_url ?? '', $activo]);
                    }
                    admin_log('roster.create', 'Added player: ' . $nombre . ' ' . $apellido);
                    $message = 'Player added to roster.';
                }
                $messageType = 'success';
            }
        }
    }
}

$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$filterCat = isset($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : 0;
$sql = "SELECT r.*, c.nombre AS categoria_nombre FROM roster r JOIN categorias c ON c.id = r.categoria_id WHERE 1=1";
$params = [];
if ($filterCat > 0) {
    $sql .= " AND r.categoria_id = ?";
    $params[] = $filterCat;
}
$sql .= " ORDER BY c.nombre ASC, r.dorsal ASC, r.apellido ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$roster = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editing = null;
$editingStats = null;
$editingSkills = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($roster as $r) {
        if ((int) $r['id'] === $editId) {
            $editing = $r;
            break;
        }
    }
    if (!$editing) {
        $stmt = $pdo->prepare("SELECT r.*, c.nombre AS categoria_nombre FROM roster r JOIN categorias c ON c.id = r.categoria_id WHERE r.id = ?");
        $stmt->execute([$editId]);
        $editing = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if ($editing) {
        $stmt = $pdo->prepare("SELECT partidos_jugados, goles, asistencias, motm, clean_sheets FROM roster_estadisticas WHERE roster_id = ?");
        $stmt->execute([$editing['id']]);
        $editingStats = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$editingStats) $editingStats = ['partidos_jugados' => 0, 'goles' => 0, 'asistencias' => 0, 'motm' => 0, 'clean_sheets' => 0];
        $stmt = $pdo->prepare("SELECT pace, shooting, passing, dribbling, defense, physical FROM roster_habilidades WHERE roster_id = ?");
        $stmt->execute([$editing['id']]);
        $editingSkills = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$editingSkills) $editingSkills = ['pace' => 5, 'shooting' => 5, 'passing' => 5, 'dribbling' => 5, 'defense' => 5, 'physical' => 5];
    }
}

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Roster / Plantilla - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Roster']]) ?>
    <h1 class="mb-4 admin-page-title">Roster / Plantilla</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white"><?= $editing ? 'Edit player' : 'Add player' ?></h5>
                    <form method="post" action="" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                        <?php endif; ?>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label text-white small">First name</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" name="nombre" required value="<?= htmlspecialchars($editing['nombre'] ?? '') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-white small">Last name</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" name="apellido" required value="<?= htmlspecialchars($editing['apellido'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Jersey number (optional)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" name="dorsal" min="0" max="99" placeholder="—" value="<?= $editing && $editing['dorsal'] !== null ? (int) $editing['dorsal'] : '' ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Position (optional)</label>
                            <select class="form-select bg-dark text-white border-secondary" name="posicion" id="roster_posicion">
                                <option value="">—</option>
                                <option value="Portero" <?= ($editing['posicion'] ?? '') === 'Portero' ? 'selected' : '' ?>>Portero</option>
                                <option value="Defensa" <?= ($editing['posicion'] ?? '') === 'Defensa' ? 'selected' : '' ?>>Defensa</option>
                                <option value="Mediocampista" <?= ($editing['posicion'] ?? '') === 'Mediocampista' ? 'selected' : '' ?>>Mediocampista</option>
                                <option value="Delantero" <?= ($editing['posicion'] ?? '') === 'Delantero' ? 'selected' : '' ?>>Delantero</option>
                            </select>
                        </div>
                        <?php if ($hasSubPosicion): ?>
                        <div class="mb-2" id="roster_sub_posicion_wrap">
                            <label class="form-label text-white small">Sub-position / Specific role</label>
                            <select class="form-select bg-dark text-white border-secondary" name="sub_posicion" id="roster_sub_posicion">
                                <option value="">—</option>
                            </select>
                            <p class="small text-white-50 mt-1 mb-0">e.g. Defensa → Lateral derecho; Delantero → Extremo izquierdo or Delantero centro (9).</p>
                        </div>
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label text-white small">Category</label>
                            <select class="form-select bg-dark text-white border-secondary" name="categoria_id" required>
                                <option value="">Select category</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= ($editing && (int) $editing['categoria_id'] === (int) $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Photo (JPG/PNG/WebP, max 5MB)</label>
                            <input type="file" class="form-control bg-dark text-white border-secondary" name="foto" accept="image/jpeg,image/png,image/webp">
                            <?php if (!empty($editing['foto_url'])): ?>
                                <p class="small text-white-50 mt-1 mb-0">Current photo set. Upload to replace.</p>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="activo" id="activo" value="1" <?= (!$editing || (int) $editing['activo'] === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label text-white small" for="activo">Active (show on public roster)</label>
                        </div>
                        <button type="submit" class="btn btn-primary me-2" style="background:#FF6600;border:none;"><?= $editing ? 'Update' : 'Add' ?></button>
                        <?php if ($editing): ?>
                            <a href="roster.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                    <?php if ($hasSubPosicion): ?>
                    <script>
                    (function() {
                        var subOpts = {
                            '': [],
                            'Portero': ['Portero'],
                            'Defensa': ['Lateral izquierdo', 'Central izquierdo', 'Central derecho', 'Lateral derecho'],
                            'Mediocampista': ['Pivote', 'Interior izquierdo', 'Interior derecho'],
                            'Delantero': ['Extremo izquierdo', 'Delantero centro (9)', 'Extremo derecho']
                        };
                        var posSelect = document.getElementById('roster_posicion');
                        var subSelect = document.getElementById('roster_sub_posicion');
                        var currentSub = <?= json_encode($editing['sub_posicion'] ?? '') ?>;
                        function fillSub() {
                            var pos = posSelect ? posSelect.value : '';
                            var opts = subOpts[pos] || [];
                            if (!subSelect) return;
                            subSelect.innerHTML = '';
                            var empty = document.createElement('option');
                            empty.value = '';
                            empty.textContent = '—';
                            subSelect.appendChild(empty);
                            opts.forEach(function(label) {
                                var o = document.createElement('option');
                                o.value = label;
                                o.textContent = label;
                                if (label === currentSub) o.selected = true;
                                subSelect.appendChild(o);
                            });
                            subSelect.disabled = opts.length === 0;
                        }
                        if (posSelect) posSelect.addEventListener('change', fillSub);
                        fillSub();
                    })();
                    </script>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($editing && $editingStats !== null): ?>
            <div class="card bg-dark border border-secondary rounded-3 mt-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Stats &amp; Skills (Player Card)</h5>
                    <p class="small text-muted">MOTM is updated automatically from Man of the Match votes.</p>
                    <form method="post" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="save_stats" value="1">
                        <input type="hidden" name="roster_id" value="<?= (int) $editing['id'] ?>">
                        <div class="row g-2 mb-2">
                            <div class="col-6 col-md"><label class="form-label text-white small">Apps</label><input type="number" class="form-control bg-dark text-white border-secondary" name="partidos_jugados" min="0" value="<?= (int) $editingStats['partidos_jugados'] ?>"></div>
                            <div class="col-6 col-md"><label class="form-label text-white small">Goals</label><input type="number" class="form-control bg-dark text-white border-secondary" name="goles" min="0" value="<?= (int) $editingStats['goles'] ?>"></div>
                            <div class="col-6 col-md"><label class="form-label text-white small">Assists</label><input type="number" class="form-control bg-dark text-white border-secondary" name="asistencias" min="0" value="<?= (int) $editingStats['asistencias'] ?>"></div>
                            <div class="col-6 col-md"><label class="form-label text-white small">Clean Sheets</label><input type="number" class="form-control bg-dark text-white border-secondary" name="clean_sheets" min="0" value="<?= (int) $editingStats['clean_sheets'] ?>"></div>
                        </div>
                        <p class="text-white small mb-1 mt-2">Radar (1–10): Pace, Shooting, Passing, Dribbling, Defense, Physical</p>
                        <div class="row g-2 mb-2">
                            <div class="col-4 col-md-2"><label class="form-label text-white small">Pace</label><input type="number" class="form-control bg-dark text-white border-secondary" name="pace" min="1" max="10" value="<?= (int) $editingSkills['pace'] ?>"></div>
                            <div class="col-4 col-md-2"><label class="form-label text-white small">Shooting</label><input type="number" class="form-control bg-dark text-white border-secondary" name="shooting" min="1" max="10" value="<?= (int) $editingSkills['shooting'] ?>"></div>
                            <div class="col-4 col-md-2"><label class="form-label text-white small">Passing</label><input type="number" class="form-control bg-dark text-white border-secondary" name="passing" min="1" max="10" value="<?= (int) $editingSkills['passing'] ?>"></div>
                            <div class="col-4 col-md-2"><label class="form-label text-white small">Dribbling</label><input type="number" class="form-control bg-dark text-white border-secondary" name="dribbling" min="1" max="10" value="<?= (int) $editingSkills['dribbling'] ?>"></div>
                            <div class="col-4 col-md-2"><label class="form-label text-white small">Defense</label><input type="number" class="form-control bg-dark text-white border-secondary" name="defense" min="1" max="10" value="<?= (int) $editingSkills['defense'] ?>"></div>
                            <div class="col-4 col-md-2"><label class="form-label text-white small">Physical</label><input type="number" class="form-control bg-dark text-white border-secondary" name="physical" min="1" max="10" value="<?= (int) $editingSkills['physical'] ?>"></div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-warning">Save Stats &amp; Skills</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <label class="text-white small mb-0">Filter:</label>
                <a href="roster.php" class="btn btn-sm <?= $filterCat === 0 ? 'btn-warning' : 'btn-outline-secondary' ?>">All</a>
                <?php foreach ($categorias as $c): ?>
                    <a href="roster.php?categoria_id=<?= (int) $c['id'] ?>" class="btn btn-sm <?= $filterCat === (int) $c['id'] ? 'btn-warning' : 'btn-outline-secondary' ?>"><?= htmlspecialchars($c['nombre']) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>#</th>
                            <th>Position</th>
                            <th>Category</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roster as $r): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($r['foto_url'])): ?>
                                        <img src="../<?= htmlspecialchars($r['foto_url']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></td>
                                <td><?= $r['dorsal'] !== null ? (int) $r['dorsal'] : '—' ?></td>
                                <td><?php
                                    $p = $r['posicion'] ?? '';
                                    $sp = ($hasSubPosicion && isset($r['sub_posicion'])) ? $r['sub_posicion'] : '';
                                    if ($p && $sp) echo htmlspecialchars($p . ' · ' . $sp);
                                    elseif ($p) echo htmlspecialchars($p);
                                    else echo '—';
                                ?></td>
                                <td><?= htmlspecialchars($r['categoria_nombre']) ?></td>
                                <td><?= (int) $r['activo'] === 1 ? 'Yes' : 'No' ?></td>
                                <td>
                                    <a href="roster.php?edit=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-light">Edit</a>
                                    <?php if (admin_can('roster_delete')): ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Remove this player from roster?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($roster) === 0): ?>
                <p class="text-muted">No players in roster yet. Add players with the form.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
