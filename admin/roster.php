<?php
require __DIR__ . '/includes/auth.php';
require_permission('roster_edit');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/upload_helper.php';
require __DIR__ . '/../config/database.php';

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize      = 5 * 1024 * 1024;

$message     = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['stats_saved']) && (string) $_GET['stats_saved'] === '1') {
    $message     = 'Estadísticas y habilidades guardadas correctamente.';
    $messageType = 'success';
}

// ── Schema detection (cached per-request via static) ──────────────────────
static $hasSubPosicion = null;
if ($hasSubPosicion === null) {
    try {
        $st = $pdo->query("SHOW COLUMNS FROM roster LIKE 'sub_posicion'");
        $hasSubPosicion = (bool)($st && $st->fetch());
    } catch (PDOException $e) { $hasSubPosicion = false; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.'; $messageType = 'danger';
    } elseif (isset($_POST['delete_id']) && admin_can('roster_delete')) {
        $id = (int) $_POST['delete_id'];
        // Grab old photo before deleting
        $stOld = $pdo->prepare("SELECT foto_url FROM roster WHERE id = ?");
        $stOld->execute([$id]);
        $oldPhoto = $stOld->fetchColumn();
        $pdo->prepare("DELETE FROM roster WHERE id = ?")->execute([$id]);
        if ($oldPhoto) delete_upload($oldPhoto);
        admin_log('roster.delete', 'Deleted roster id ' . $id);
        $message = 'Player removed from roster.'; $messageType = 'success';
    } elseif (isset($_POST['save_stats']) && isset($_POST['roster_id'])) {
        // Check roster_stats permission
        if (!admin_can('roster_stats')) {
            $message = 'You do not have permission to edit stats.'; $messageType = 'danger';
        } else {
            $rid          = (int) $_POST['roster_id'];
            $partidos     = (int) ($_POST['partidos_jugados'] ?? 0);
            $goles        = (int) ($_POST['goles']       ?? 0);
            $asistencias  = (int) ($_POST['asistencias']  ?? 0);
            $clean_sheets = (int) ($_POST['clean_sheets'] ?? 0);
            $pace         = max(1, min(10, (int) ($_POST['pace']      ?? 5)));
            $shooting     = max(1, min(10, (int) ($_POST['shooting']  ?? 5)));
            $passing      = max(1, min(10, (int) ($_POST['passing']   ?? 5)));
            $dribbling    = max(1, min(10, (int) ($_POST['dribbling'] ?? 5)));
            $defense      = max(1, min(10, (int) ($_POST['defense']   ?? 5)));
            $physical     = max(1, min(10, (int) ($_POST['physical']  ?? 5)));
            $st = $pdo->prepare("SELECT 1 FROM roster WHERE id = ?"); $st->execute([$rid]);
            if ($st->fetch()) {
                $pdo->prepare("INSERT INTO roster_estadisticas (roster_id, partidos_jugados, goles, asistencias, motm, clean_sheets) VALUES (?,?,?,?,0,?) ON DUPLICATE KEY UPDATE partidos_jugados=VALUES(partidos_jugados), goles=VALUES(goles), asistencias=VALUES(asistencias), clean_sheets=VALUES(clean_sheets)")->execute([$rid, $partidos, $goles, $asistencias, $clean_sheets]);
                $pdo->prepare("INSERT INTO roster_habilidades (roster_id, pace, shooting, passing, dribbling, defense, physical) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE pace=VALUES(pace), shooting=VALUES(shooting), passing=VALUES(passing), dribbling=VALUES(dribbling), defense=VALUES(defense), physical=VALUES(physical)")->execute([$rid, $pace, $shooting, $passing, $dribbling, $defense, $physical]);
                admin_log('roster.stats', 'Updated stats/skills for roster id ' . $rid);
                $redirectQ = ['edit' => $rid, 'stats_saved' => '1'];
                if (!empty($_GET['categoria_id']) && (int) $_GET['categoria_id'] > 0) $redirectQ['categoria_id'] = (int) $_GET['categoria_id'];
                if (!empty($_GET['page'])) $redirectQ['page'] = (int) $_GET['page'];
                header('Location: roster.php?' . http_build_query($redirectQ));
                exit;
            }
            $message = 'Player not found.'; $messageType = 'danger';
        }
    } else {
        $id          = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $nombre      = mb_substr(trim($_POST['nombre']   ?? ''), 0, 100);
        $apellido    = mb_substr(trim($_POST['apellido'] ?? ''), 0, 100);
        $dorsal      = isset($_POST['dorsal']) && $_POST['dorsal'] !== '' ? (int) $_POST['dorsal'] : null;
        $posicion    = trim($_POST['posicion']    ?? '');
        $sub_posicion = trim($_POST['sub_posicion'] ?? '');
        $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
        $activo      = isset($_POST['activo']) ? 1 : 0;
        $foto_url    = null;

        $subPosicionesPermitidas = [
            'Portero'       => ['Portero'],
            'Defensa'       => ['Lateral izquierdo','Central izquierdo','Central derecho','Lateral derecho'],
            'Mediocampista' => ['Pivote','Interior izquierdo','Interior derecho'],
            'Delantero'     => ['Extremo izquierdo','Delantero centro (9)','Extremo derecho'],
        ];
        if ($sub_posicion !== '' && (!isset($subPosicionesPermitidas[$posicion]) || !in_array($sub_posicion, $subPosicionesPermitidas[$posicion], true))) {
            $sub_posicion = '';
        }
        if ($posicion === '') $sub_posicion = '';

        if ($nombre === '' || $apellido === '' || $categoria_id <= 0) {
            $message = 'Name, last name and category are required.'; $messageType = 'danger';
        } else {
            $posicionVal = in_array($posicion, ['Portero','Defensa','Mediocampista','Delantero'], true) ? $posicion : null;

            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $_FILES['foto']['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mime, $allowedTypes, true)) {
                    $message = 'Invalid image type. Use JPG, PNG or WebP.'; $messageType = 'danger';
                } elseif ($_FILES['foto']['size'] > $maxSize) {
                    $message = 'Image too large. Max 5MB.'; $messageType = 'danger';
                } else {
                    $clientName = basename($_FILES['foto']['name'] ?? '');
                    if (strpos($clientName, '..') !== false || preg_match('/\.(php|phtml|php3|php4|php5|phar|htaccess)(\.|$)/i', $clientName)) {
                        $message = 'Invalid file name.'; $messageType = 'danger';
                    } else {
                        $ext = match ($mime) { 'image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', default=>'jpg' };
                        $ext = in_array($ext, ['jpg','png','webp'], true) ? $ext : 'jpg';
                        $filename = 'roster-' . uniqid() . '.' . $ext;
                        if (strpos($filename, '..') !== false || strpbrk($filename, '/\\') !== false) {
                            $message = 'Invalid file name.'; $messageType = 'danger';
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
                    if ($foto_url !== null) {
                        // Delete old photo before replacing
                        $stOld = $pdo->prepare("SELECT foto_url FROM roster WHERE id = ?");
                        $stOld->execute([$id]);
                        $oldPhoto = $stOld->fetchColumn();
                        if ($oldPhoto) delete_upload($oldPhoto);
                    }
                    if ($hasSubPosicion) {
                        if ($foto_url !== null) {
                            $pdo->prepare("UPDATE roster SET nombre=?, apellido=?, dorsal=?, posicion=?, sub_posicion=?, categoria_id=?, foto_url=?, activo=? WHERE id=?")
                                ->execute([$nombre, $apellido, $dorsal, $posicionVal, $subPosicionVal, $categoria_id, $foto_url, $activo, $id]);
                        } else {
                            $pdo->prepare("UPDATE roster SET nombre=?, apellido=?, dorsal=?, posicion=?, sub_posicion=?, categoria_id=?, activo=? WHERE id=?")
                                ->execute([$nombre, $apellido, $dorsal, $posicionVal, $subPosicionVal, $categoria_id, $activo, $id]);
                        }
                    } else {
                        if ($foto_url !== null) {
                            $pdo->prepare("UPDATE roster SET nombre=?, apellido=?, dorsal=?, posicion=?, categoria_id=?, foto_url=?, activo=? WHERE id=?")
                                ->execute([$nombre, $apellido, $dorsal, $posicionVal, $categoria_id, $foto_url, $activo, $id]);
                        } else {
                            $pdo->prepare("UPDATE roster SET nombre=?, apellido=?, dorsal=?, posicion=?, categoria_id=?, activo=? WHERE id=?")
                                ->execute([$nombre, $apellido, $dorsal, $posicionVal, $categoria_id, $activo, $id]);
                        }
                    }
                    admin_log('roster.update', 'Updated player: ' . $nombre . ' ' . $apellido . ' (id ' . $id . ')');
                    $message = 'Player updated.';
                } else {
                    if ($hasSubPosicion) {
                        $pdo->prepare("INSERT INTO roster (nombre, apellido, dorsal, posicion, sub_posicion, categoria_id, foto_url, activo) VALUES (?,?,?,?,?,?,?,?)")
                            ->execute([$nombre, $apellido, $dorsal, $posicionVal, $subPosicionVal, $categoria_id, $foto_url ?? '', $activo]);
                    } else {
                        $pdo->prepare("INSERT INTO roster (nombre, apellido, dorsal, posicion, categoria_id, foto_url, activo) VALUES (?,?,?,?,?,?,?)")
                            ->execute([$nombre, $apellido, $dorsal, $posicionVal, $categoria_id, $foto_url ?? '', $activo]);
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

// ── Pagination ─────────────────────────────────────────────────────────────
$filterCat = isset($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : 0;
$per_page  = 25;
$page      = max(1, (int) ($_GET['page'] ?? 1));

$whereClause = $filterCat > 0 ? " AND r.categoria_id = ?" : "";
$params      = $filterCat > 0 ? [$filterCat] : [];

$stCount = $pdo->prepare("SELECT COUNT(*) FROM roster r JOIN categorias c ON c.id = r.categoria_id WHERE 1=1" . $whereClause);
$stCount->execute($params);
$total_rows  = (int) $stCount->fetchColumn();
$total_pages = max(1, (int) ceil($total_rows / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$sql = "SELECT r.*, c.nombre AS categoria_nombre FROM roster r JOIN categorias c ON c.id = r.categoria_id WHERE 1=1" . $whereClause
     . " ORDER BY c.nombre ASC, r.dorsal ASC, r.apellido ASC LIMIT " . $per_page . " OFFSET " . $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$roster = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Editing ────────────────────────────────────────────────────────────────
$editing      = null;
$editingStats = null;
$editingSkills = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    // Search current page first, then fall back to direct DB query
    foreach ($roster as $r) {
        if ((int) $r['id'] === $editId) { $editing = $r; break; }
    }
    if (!$editing) {
        $stmt = $pdo->prepare("SELECT r.*, c.nombre AS categoria_nombre FROM roster r JOIN categorias c ON c.id = r.categoria_id WHERE r.id = ?");
        $stmt->execute([$editId]);
        $editing = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if ($editing && admin_can('roster_stats')) {
        $stmt = $pdo->prepare("SELECT partidos_jugados, goles, asistencias, motm, clean_sheets FROM roster_estadisticas WHERE roster_id = ?");
        $stmt->execute([$editing['id']]);
        $editingStats = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$editingStats) $editingStats = ['partidos_jugados'=>0,'goles'=>0,'asistencias'=>0,'motm'=>0,'clean_sheets'=>0];
        $stmt = $pdo->prepare("SELECT pace, shooting, passing, dribbling, defense, physical FROM roster_habilidades WHERE roster_id = ?");
        $stmt->execute([$editing['id']]);
        $editingSkills = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$editingSkills) $editingSkills = ['pace'=>5,'shooting'=>5,'passing'=>5,'dribbling'=>5,'defense'=>5,'physical'=>5];
    }
}

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Roster / Plantilla - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';

/** Build pagination URL keeping current filters. */
function rosterPageUrl(int $p, int $cat, int $edit = 0): string {
    $q = [];
    if ($cat > 0)  $q['categoria_id'] = $cat;
    if ($edit > 0) $q['edit']         = $edit;
    if ($p > 1)    $q['page']         = $p;
    return 'roster.php' . ($q ? '?' . http_build_query($q) : '');
}
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Roster']]) ?>
    <h1 class="mb-4 admin-page-title">Roster / Plantilla</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- ── Add / Edit form ─────────────────────────────────────────── -->
        <div class="col-lg-5 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white"><?= $editing ? 'Edit player' : 'Add player' ?></h5>
                    <form method="post" action="" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label text-white small">First name</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" name="nombre" required maxlength="100" value="<?= htmlspecialchars($editing['nombre'] ?? '') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-white small">Last name</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" name="apellido" required maxlength="100" value="<?= htmlspecialchars($editing['apellido'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-2 mt-2">
                            <label class="form-label text-white small">Jersey number (optional)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" name="dorsal" min="0" max="99" placeholder="—" value="<?= $editing && $editing['dorsal'] !== null ? (int) $editing['dorsal'] : '' ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Position (optional)</label>
                            <select class="form-select bg-dark text-white border-secondary" name="posicion" id="roster_posicion">
                                <option value="">—</option>
                                <option value="Portero"       <?= ($editing['posicion'] ?? '') === 'Portero'       ? 'selected' : '' ?>>Portero</option>
                                <option value="Defensa"       <?= ($editing['posicion'] ?? '') === 'Defensa'       ? 'selected' : '' ?>>Defensa</option>
                                <option value="Mediocampista" <?= ($editing['posicion'] ?? '') === 'Mediocampista' ? 'selected' : '' ?>>Mediocampista</option>
                                <option value="Delantero"     <?= ($editing['posicion'] ?? '') === 'Delantero'     ? 'selected' : '' ?>>Delantero</option>
                            </select>
                        </div>
                        <?php if ($hasSubPosicion): ?>
                        <div class="mb-2" id="roster_sub_posicion_wrap">
                            <label class="form-label text-white small">Sub-position</label>
                            <select class="form-select bg-dark text-white border-secondary" name="sub_posicion" id="roster_sub_posicion"></select>
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
                                <p class="small text-white-50 mt-1 mb-0">Current photo set. Upload to replace (old file will be deleted).</p>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="activo" id="activo" value="1" <?= (!$editing || (int) $editing['activo'] === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label text-white small" for="activo">Active (show on public roster)</label>
                        </div>
                        <button type="submit" class="btn btn-primary me-2" style="background:#FF6600;border:none;"><?= $editing ? 'Update' : 'Add' ?></button>
                        <?php if ($editing): ?><a href="roster.php<?= $filterCat > 0 ? '?categoria_id=' . $filterCat : '' ?>" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                    </form>
                    <?php if ($hasSubPosicion): ?>
                    <script>
                    (function() {
                        var subOpts = {
                            ''            : [],
                            'Portero'     : ['Portero'],
                            'Defensa'     : ['Lateral izquierdo','Central izquierdo','Central derecho','Lateral derecho'],
                            'Mediocampista': ['Pivote','Interior izquierdo','Interior derecho'],
                            'Delantero'   : ['Extremo izquierdo','Delantero centro (9)','Extremo derecho']
                        };
                        var posSelect = document.getElementById('roster_posicion');
                        var subSelect = document.getElementById('roster_sub_posicion');
                        var currentSub = <?= json_encode($editing['sub_posicion'] ?? '') ?>;
                        function fillSub() {
                            var pos = posSelect ? posSelect.value : '';
                            var opts = subOpts[pos] || [];
                            if (!subSelect) return;
                            subSelect.innerHTML = '';
                            var empty = document.createElement('option'); empty.value=''; empty.textContent='—'; subSelect.appendChild(empty);
                            opts.forEach(function(label) {
                                var o = document.createElement('option'); o.value=label; o.textContent=label;
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

            <!-- ── Stats / Radar (only if user has roster_stats permission) ── -->
            <?php if ($editing && $editingStats !== null && admin_can('roster_stats')): ?>
            <div class="card bg-dark border border-secondary rounded-3 mt-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Estadísticas y radar (ficha pública)</h5>
                    <p class="small text-white-50 mb-3">Si este jugador tiene datos en <strong>Juegos</strong>, en la web se mostrarán esos totales y no los números de aquí.</p>
                    <?php
                    $statsQ = ['edit' => (int) $editing['id']];
                    if ($filterCat > 0) $statsQ['categoria_id'] = $filterCat;
                    if ($page > 1)      $statsQ['page'] = $page;
                    ?>
                    <form method="post" action="roster.php?<?= http_build_query($statsQ) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="save_stats" value="1">
                        <input type="hidden" name="roster_id" value="<?= (int) $editing['id'] ?>">
                        <div class="row g-2 mb-2">
                            <div class="col-6 col-md"><label class="form-label text-white small">Partidos (Apps)</label><input type="number" class="form-control bg-dark text-white border-secondary" name="partidos_jugados" min="0" value="<?= (int) $editingStats['partidos_jugados'] ?>"></div>
                            <div class="col-6 col-md"><label class="form-label text-white small">Goles</label><input type="number" class="form-control bg-dark text-white border-secondary" name="goles" min="0" value="<?= (int) $editingStats['goles'] ?>"></div>
                            <div class="col-6 col-md"><label class="form-label text-white small">Asistencias</label><input type="number" class="form-control bg-dark text-white border-secondary" name="asistencias" min="0" value="<?= (int) $editingStats['asistencias'] ?>"></div>
                            <div class="col-6 col-md"><label class="form-label text-white small">Porterías a 0</label><input type="number" class="form-control bg-dark text-white border-secondary" name="clean_sheets" min="0" value="<?= (int) $editingStats['clean_sheets'] ?>"></div>
                        </div>
                        <p class="text-white small mb-1 mt-2">Radar (1–10): pace, shooting, passing, dribbling, defense, physical</p>
                        <div class="row g-2 mb-2">
                            <?php foreach (['pace','shooting','passing','dribbling','defense','physical'] as $sk): ?>
                            <div class="col-4 col-md-2"><label class="form-label text-white small"><?= ucfirst($sk) ?></label><input type="number" class="form-control bg-dark text-white border-secondary" name="<?= $sk ?>" min="1" max="10" value="<?= (int) $editingSkills[$sk] ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-warning">Guardar estadísticas y radar</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Player list ─────────────────────────────────────────────── -->
        <div class="col-lg-7">
            <!-- Category filter -->
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <label class="text-white small mb-0">Filter:</label>
                <a href="roster.php" class="btn btn-sm <?= $filterCat === 0 ? 'btn-warning' : 'btn-outline-secondary' ?>">All</a>
                <?php foreach ($categorias as $c): ?>
                    <a href="roster.php?categoria_id=<?= (int) $c['id'] ?>" class="btn btn-sm <?= $filterCat === (int) $c['id'] ? 'btn-warning' : 'btn-outline-secondary' ?>"><?= htmlspecialchars($c['nombre']) ?></a>
                <?php endforeach; ?>
            </div>
            <!-- Client-side name search (within the current page) -->
            <div class="mb-3">
                <input type="search" id="rosterPlayerSearch" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Buscar jugador en esta página…" autocomplete="off">
            </div>
            <div class="table-responsive">
                <table id="rosterPlayersTable" class="table table-dark table-striped table-bordered">
                    <thead>
                        <tr><th>Photo</th><th>Name</th><th>#</th><th>Position</th><th>Category</th><th>Active</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roster as $r): ?>
                            <tr data-player-name="<?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido'], ENT_QUOTES, 'UTF-8') ?>">
                                <td><?php if (!empty($r['foto_url'])): ?><img src="../<?= htmlspecialchars($r['foto_url']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px;"><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
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
                                    <a href="roster.php?edit=<?= (int) $r['id'] ?><?= $filterCat > 0 ? '&categoria_id=' . $filterCat : '' ?><?= $page > 1 ? '&page=' . $page : '' ?>" class="btn btn-sm btn-outline-light">Edit</a>
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
                        <?php if (empty($roster)): ?>
                        <tr><td colspan="7" class="text-muted">No players found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Roster pagination" class="mt-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <p class="text-muted small mb-0">Page <?= $page ?> of <?= $total_pages ?> &middot; <?= $total_rows ?> players</p>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link bg-dark border-secondary text-white" href="<?= htmlspecialchars(rosterPageUrl($page - 1, $filterCat)) ?>">Previous</a>
                        </li>
                        <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link bg-dark border-secondary text-white" href="<?= htmlspecialchars(rosterPageUrl($p, $filterCat)) ?>"><?= $p ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link bg-dark border-secondary text-white" href="<?= htmlspecialchars(rosterPageUrl($page + 1, $filterCat)) ?>">Next</a>
                        </li>
                    </ul>
                </div>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
(function () {
    var inp = document.getElementById('rosterPlayerSearch');
    if (!inp) return;
    inp.addEventListener('input', function () {
        var q = (inp.value || '').trim().toLowerCase();
        document.querySelectorAll('#rosterPlayersTable tbody tr[data-player-name]').forEach(function (tr) {
            var n = (tr.getAttribute('data-player-name') || '').toLowerCase();
            tr.style.display = !q || n.indexOf(q) !== -1 ? '' : 'none';
        });
    });
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
