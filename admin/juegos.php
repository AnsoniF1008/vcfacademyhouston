<?php
require __DIR__ . '/includes/auth.php';
if (!admin_can('juegos') && !admin_can('juegos_live_score')) {
    header('Location: dashboard.php?error=forbidden');
    exit;
}
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$torneo_id = isset($_GET['torneo_id']) ? (int) $_GET['torneo_id'] : 0;
if ($torneo_id <= 0) {
    header('Location: torneos.php');
    exit;
}

$torneo = $pdo->prepare("SELECT id, nombre_torneo, temporada FROM torneos_info WHERE id = ?");
$torneo->execute([$torneo_id]);
$torneo = $torneo->fetch(PDO::FETCH_ASSOC);
if (!$torneo) {
    header('Location: torneos.php');
    exit;
}

$sedes = $pdo->query("SELECT id, nombre FROM sedes ORDER BY nombre")->fetchAll();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } elseif (isset($_POST['save_scorers'], $_POST['juego_id']) && admin_can('juegos')) {
        $juego_id = (int) $_POST['juego_id'];
        $stmt = $pdo->prepare("SELECT id, goles_vcf FROM juegos WHERE id = ? AND torneo_id = ?");
        $stmt->execute([$juego_id, $torneo_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game || $game['goles_vcf'] === null) {
            $message = 'Game not found or has no score.';
            $messageType = 'danger';
        } else {
            $expected = (int) $game['goles_vcf'];
            $scorer = $_POST['scorer'] ?? [];
            $totalGoles = 0;
            $rows = [];
            foreach ($scorer as $roster_id => $vals) {
                $roster_id = (int) $roster_id;
                if ($roster_id <= 0) continue;
                $goles = isset($vals['goles']) ? max(0, (int) $vals['goles']) : 0;
                $asistencias = isset($vals['asistencias']) ? max(0, (int) $vals['asistencias']) : 0;
                if ($goles > 0 || $asistencias > 0) {
                    $totalGoles += $goles;
                    $rows[] = [$juego_id, $roster_id, $goles, $asistencias];
                }
            }
            if ($totalGoles !== $expected) {
                $message = "Total goals must equal VCF score ($expected). You entered $totalGoles.";
                $messageType = 'danger';
            } else {
                $pdo->prepare("DELETE FROM juego_goles WHERE juego_id = ?")->execute([$juego_id]);
                $ins = $pdo->prepare("INSERT INTO juego_goles (juego_id, roster_id, goles, asistencias) VALUES (?, ?, ?, ?)");
                foreach ($rows as $r) {
                    $ins->execute($r);
                }
                admin_log('juegos.scorers', 'Saved scorers for game ' . $juego_id);
                header('Location: juegos.php?torneo_id=' . $torneo_id . '&edit=' . $juego_id . '&scorers_saved=1');
                exit;
            }
        }
    } elseif (isset($_POST['delete_id']) && admin_can('juegos_delete')) {
        $id = (int) $_POST['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM juegos WHERE id = ? AND torneo_id = ?");
        $stmt->execute([$id, $torneo_id]);
        admin_log('juegos.delete', 'Deleted game ' . $id . ' (torneo ' . $torneo_id . ')');
        $message = 'Game deleted.';
        $messageType = 'success';
    } elseif (isset($_POST['live_score_only']) && admin_can('juegos_live_score') && !admin_can('juegos')) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $estado = in_array($_POST['estado'] ?? '', ['finalizado', 'live'], true) ? $_POST['estado'] : 'proximo';
            $goles_vcf = isset($_POST['goles_vcf']) && $_POST['goles_vcf'] !== '' ? (int) $_POST['goles_vcf'] : null;
            $goles_rival = isset($_POST['goles_rival']) && $_POST['goles_rival'] !== '' ? (int) $_POST['goles_rival'] : null;
            $stmt = $pdo->prepare("UPDATE juegos SET estado = ?, goles_vcf = ?, goles_rival = ? WHERE id = ? AND torneo_id = ?");
            $stmt->execute([$estado, $goles_vcf, $goles_rival, $id, $torneo_id]);
            admin_log('juegos.live_score', 'Updated score game ' . $id . ': ' . ($goles_vcf ?? '') . '-' . ($goles_rival ?? ''));
            $message = 'Score updated.';
            $messageType = 'success';
        }
    } else {
        if (!admin_can('juegos')) {
            $message = 'You can only update live score.';
            $messageType = 'danger';
        } else {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $fecha = trim($_POST['fecha'] ?? '');
        $horaRaw = trim($_POST['hora'] ?? '');
        $hora = $horaRaw !== '' ? $horaRaw . (substr_count($horaRaw, ':') === 1 ? ':00' : '') : null;
        $rival = trim($_POST['rival'] ?? '') ?: null;
        $cancha = trim($_POST['cancha'] ?? '') ?: null;
        $sede_id = isset($_POST['sede_id']) && $_POST['sede_id'] !== '' ? (int) $_POST['sede_id'] : null;
        $ubicacion_mapa_url = trim($_POST['ubicacion_mapa_url'] ?? '') ?: null;
        $estado = in_array($_POST['estado'] ?? '', ['finalizado', 'live'], true) ? $_POST['estado'] : 'proximo';
        $categoria = trim($_POST['categoria'] ?? '') ?: null;
        $goles_vcf = isset($_POST['goles_vcf']) && $_POST['goles_vcf'] !== '' ? (int) $_POST['goles_vcf'] : null;
        $goles_rival = isset($_POST['goles_rival']) && $_POST['goles_rival'] !== '' ? (int) $_POST['goles_rival'] : null;
        $rival_logo_url = trim($_POST['rival_logo_url'] ?? '') ?: null;

        if ($fecha === '') {
            $message = 'Date is required.';
            $messageType = 'danger';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE juegos SET fecha = ?, hora = ?, rival = ?, cancha = ?, sede_id = ?, ubicacion_mapa_url = ?, estado = ?, categoria = ?, goles_vcf = ?, goles_rival = ?, rival_logo_url = ? WHERE id = ? AND torneo_id = ?");
                $stmt->execute([$fecha, $hora, $rival, $cancha, $sede_id, $ubicacion_mapa_url, $estado, $categoria, $goles_vcf, $goles_rival, $rival_logo_url, $id, $torneo_id]);
                admin_log('juegos.update', 'Updated game ' . $id . ' vs ' . ($rival ?: 'TBD'));
                $message = 'Game updated.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO juegos (torneo_id, fecha, hora, rival, cancha, sede_id, ubicacion_mapa_url, estado, categoria, goles_vcf, goles_rival, rival_logo_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$torneo_id, $fecha, $hora, $rival, $cancha, $sede_id, $ubicacion_mapa_url, $estado, $categoria, $goles_vcf, $goles_rival, $rival_logo_url]);
                admin_log('juegos.create', 'Added game vs ' . ($rival ?: 'TBD') . ' (' . $fecha . ')');
                $message = 'Game added.';
            }
            $messageType = 'success';
        }
        }
    }
}

$juegos = $pdo->prepare("SELECT j.id, j.fecha, j.hora, j.rival, j.cancha, j.sede_id, j.ubicacion_mapa_url, j.estado, j.categoria, j.goles_vcf, j.goles_rival, j.rival_logo_url, s.nombre AS sede_nombre FROM juegos j LEFT JOIN sedes s ON j.sede_id = s.id WHERE j.torneo_id = ? ORDER BY j.fecha DESC, j.hora DESC");
$juegos->execute([$torneo_id]);
$juegos = $juegos->fetchAll();

$editing = null;
$editingScorers = [];
$rosterForScorers = [];
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($juegos as $j) {
        if ((int) $j['id'] === $editId) {
            $editing = $j;
            break;
        }
    }
    if ($editing && $editing['estado'] === 'finalizado' && isset($editing['goles_vcf']) && $editing['goles_vcf'] !== null && trim($editing['categoria'] ?? '') !== '') {
        $stmt = $pdo->prepare("SELECT r.id, r.nombre, r.apellido, r.dorsal FROM roster r JOIN categorias c ON c.id = r.categoria_id WHERE c.nombre = ? AND r.activo = 1 ORDER BY r.apellido, r.nombre");
        $stmt->execute([trim($editing['categoria'])]);
        $rosterForScorers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT roster_id, goles, asistencias FROM juego_goles WHERE juego_id = ?");
        $stmt->execute([$editing['id']]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $editingScorers[(int) $row['roster_id']] = ['goles' => (int) $row['goles'], 'asistencias' => (int) $row['asistencias']];
        }
    }
}

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Games - ' . htmlspecialchars($torneo['nombre_torneo']);
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Torneos', 'url' => 'torneos.php'], ['label' => 'Juegos']]) ?>
    <h1 class="mb-4 admin-page-title"><?= htmlspecialchars($torneo['nombre_torneo']) ?><?= $torneo['temporada'] ? ' — ' . htmlspecialchars($torneo['temporada']) : '' ?></h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['scorers_saved'])): ?>
        <div class="alert alert-success py-2">Scorers saved. Stats and Top Scorers update automatically on the homepage.</div>
    <?php endif; ?>
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white"><?= $editing ? 'Edit game' : 'Add game' ?></h5>
                    <form method="post" action="?torneo_id=<?= $torneo_id ?>">
                        <?= csrf_field() ?>
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label text-white small">Date</label>
                            <input type="date" class="form-control bg-dark text-white border-secondary" name="fecha" required value="<?= htmlspecialchars($editing['fecha'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Time (optional)</label>
                            <input type="time" class="form-control bg-dark text-white border-secondary" name="hora" value="<?= isset($editing['hora']) && $editing['hora'] !== null ? substr($editing['hora'], 0, 5) : '' ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Opponent (rival)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="rival" placeholder="e.g. North Shore FC" value="<?= htmlspecialchars($editing['rival'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Rival logo (URL, optional)</label>
                            <input type="url" class="form-control bg-dark text-white border-secondary" name="rival_logo_url" placeholder="https://..." value="<?= htmlspecialchars($editing['rival_logo_url'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Field / Cancha</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="cancha" placeholder="e.g. Field 7 or Meyer Park - Field 5" value="<?= htmlspecialchars($editing['cancha'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Venue (sede, optional)</label>
                            <select class="form-select bg-dark text-white border-secondary" name="sede_id">
                                <option value="">— None —</option>
                                <?php foreach ($sedes as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>" <?= (isset($editing['sede_id']) && (int) $editing['sede_id'] === (int) $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Map URL (optional)</label>
                            <input type="url" class="form-control bg-dark text-white border-secondary" name="ubicacion_mapa_url" placeholder="https://maps.google.com/..." value="<?= htmlspecialchars($editing['ubicacion_mapa_url'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Category (e.g. U10)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="categoria" placeholder="U8, U10, U12" value="<?= htmlspecialchars($editing['categoria'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Score: VCF</label>
                            <input type="number" min="0" class="form-control bg-dark text-white border-secondary" name="goles_vcf" placeholder="—" value="<?= isset($editing['goles_vcf']) && $editing['goles_vcf'] !== null ? (int) $editing['goles_vcf'] : '' ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Score: Opponent</label>
                            <input type="number" min="0" class="form-control bg-dark text-white border-secondary" name="goles_rival" placeholder="—" value="<?= isset($editing['goles_rival']) && $editing['goles_rival'] !== null ? (int) $editing['goles_rival'] : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Status</label>
                            <select class="form-select bg-dark text-white border-secondary" name="estado">
                                <option value="proximo" <?= ($editing['estado'] ?? '') === 'proximo' ? 'selected' : '' ?>>Upcoming</option>
                                <option value="live" <?= ($editing['estado'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option>
                                <option value="finalizado" <?= ($editing['estado'] ?? '') === 'finalizado' ? 'selected' : '' ?>>Finished</option>
                            </select>
                            <small class="text-muted">On the list, status is shown by date (past = Finished, today = Live, future = Upcoming). Score fields are optional for new games.</small>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;"><?= $editing ? 'Update' : 'Add' ?></button>
                        <?php if ($editing): ?>
                            <a href="juegos.php?torneo_id=<?= $torneo_id ?>" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                    <?php if ($editing && $editing['estado'] === 'finalizado' && isset($editing['goles_vcf']) && $editing['goles_vcf'] !== null): ?>
                    <hr class="border-secondary my-3">
                    <h6 class="text-white mb-2">Add Scorers / Stats</h6>
                    <p class="small text-muted">Assign goals and assists to roster players. Total goals must equal VCF score (<?= (int) $editing['goles_vcf'] ?>).</p>
                    <?php if (count($rosterForScorers) > 0): ?>
                    <form method="post" action="?torneo_id=<?= $torneo_id ?>&edit=<?= (int) $editing['id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="save_scorers" value="1">
                        <input type="hidden" name="juego_id" value="<?= (int) $editing['id'] ?>">
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead><tr><th>Player</th><th class="text-center">Goals</th><th class="text-center">Assists</th></tr></thead>
                                <tbody>
                                <?php foreach ($rosterForScorers as $r): $rid = (int) $r['id']; $cur = $editingScorers[$rid] ?? ['goles' => 0, 'asistencias' => 0]; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?><?= $r['dorsal'] !== null ? ' #' . (int) $r['dorsal'] : '' ?></td>
                                        <td class="text-center"><input type="number" min="0" class="form-control form-control-sm bg-dark text-white border-secondary text-center" name="scorer[<?= $rid ?>][goles]" value="<?= $cur['goles'] ?>" style="width:4rem;"></td>
                                        <td class="text-center"><input type="number" min="0" class="form-control form-control-sm bg-dark text-white border-secondary text-center" name="scorer[<?= $rid ?>][asistencias]" value="<?= $cur['asistencias'] ?>" style="width:4rem;"></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-warning">Save Scorers</button>
                    </form>
                    <?php else: ?>
                    <p class="small text-warning mb-0">No roster found for category "<?= htmlspecialchars($editing['categoria'] ?? '') ?>". Set the game category to match a category name (e.g. U10, B13) so players appear here.</p>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Games</h5>
                    <?php
                    $houstonNow = new DateTime('now', new DateTimeZone('America/Chicago'));
                    ?>
                    <p class="text-muted small mb-2">Reference time (Houston): <strong><?= $houstonNow->format('l, M j, Y g:i A T') ?></strong> — Status is computed from this (past = Finished, today = Live, future = Upcoming).</p>
                    <?php if (count($juegos) === 0): ?>
                        <p class="text-muted mb-0">No games yet. Add one above.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Opponent</th>
                                        <th>Score</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $nowTs = $houstonNow->getTimestamp();
                                    foreach ($juegos as $j):
                                        $lugar = $j['sede_nombre'] && $j['cancha'] ? $j['sede_nombre'] . ' - ' . $j['cancha'] : ($j['cancha'] ?? $j['sede_nombre'] ?? '—');
                                        $score = (isset($j['goles_vcf']) && $j['goles_vcf'] !== null) || (isset($j['goles_rival']) && $j['goles_rival'] !== null)
                                            ? ((int) ($j['goles_vcf'] ?? 0)) . ' – ' . ((int) ($j['goles_rival'] ?? 0)) : '—';
                                        $gameTs = strtotime($j['fecha'] . ' ' . (!empty($j['hora']) ? $j['hora'] : '23:59:59'));
                                        $isPast = $gameTs < $nowTs;
                                        $isToday = (date('Y-m-d', $gameTs) === date('Y-m-d', $nowTs));
                                        if ($isPast) {
                                            $statusLabel = 'Finished';
                                            $statusClass = 'text-secondary';
                                        } elseif ($isToday) {
                                            $statusLabel = 'Live';
                                            $statusClass = 'text-warning';
                                        } else {
                                            $statusLabel = 'Upcoming';
                                            $statusClass = 'text-info';
                                        }
                                    ?>
                                        <tr>
                                            <td><?= date('M j, Y', strtotime($j['fecha'])) ?></td>
                                            <td><?= $j['hora'] !== null ? date('g:i A', strtotime($j['hora'])) : '—' ?></td>
                                            <td><?= $j['rival'] ? htmlspecialchars($j['rival']) : '—' ?></td>
                                            <td><?= $score ?></td>
                                            <td><?= htmlspecialchars($lugar) ?></td>
                                            <td><span class="<?= $statusClass ?>"><?= $statusLabel ?></span></td>
                                            <td>
                                                <a href="juegos.php?torneo_id=<?= $torneo_id ?>&edit=<?= (int) $j['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this game?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="delete_id" value="<?= (int) $j['id'] ?>">
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
