<?php
require __DIR__ . '/includes/auth.php';
require_permission('motm');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/motm_close_expired.php';

if (isset($pdo)) {
    vcf_motm_close_expired($pdo);
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } else {
        $juego_id = (int) ($_POST['juego_id'] ?? 0);
        $roster_ids = [
            (int) ($_POST['roster_1'] ?? 0),
            (int) ($_POST['roster_2'] ?? 0),
            (int) ($_POST['roster_3'] ?? 0),
        ];

        if ($juego_id <= 0 || $roster_ids[0] <= 0 || $roster_ids[1] <= 0 || $roster_ids[2] <= 0) {
            $message = 'Please select a game and three different players from the roster.';
            $messageType = 'danger';
        } elseif ($roster_ids[0] === $roster_ids[1] || $roster_ids[1] === $roster_ids[2] || $roster_ids[0] === $roster_ids[2]) {
            $message = 'Each nominee must be a different player.';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare("SELECT id, nombre, apellido, foto_url FROM roster WHERE id = ? AND activo = 1");
            $nomineesData = [];
            foreach ($roster_ids as $rid) {
                $stmt->execute([$rid]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    $message = 'Invalid or inactive roster player selected.';
                    $messageType = 'danger';
                    break;
                }
                $nomineesData[] = [
                    'nombre' => trim($row['nombre'] . ' ' . $row['apellido']),
                    'foto_url' => $row['foto_url'] ?? '',
                    'roster_id' => $rid,
                ];
            }

            if ($message === '' && count($nomineesData) === 3) {
                $stmt = $pdo->prepare("SELECT id FROM juegos WHERE id = ?");
                $stmt->execute([$juego_id]);
                if (!$stmt->fetch()) {
                    $message = 'Game not found.';
                    $messageType = 'danger';
                } else {
                    $starts_at = date('Y-m-d H:i:s');
                    $ends_at = date('Y-m-d H:i:s', strtotime('+2 hours'));

                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare("INSERT INTO motm_votaciones (juego_id, starts_at, ends_at, status) VALUES (?, ?, ?, 'open')")->execute([$juego_id, $starts_at, $ends_at]);
                        $votacion_id = (int) $pdo->lastInsertId();
                        $ins = $pdo->prepare("INSERT INTO motm_nominees (votacion_id, nombre, foto_url, orden, roster_id) VALUES (?, ?, ?, ?, ?)");
                        foreach ($nomineesData as $i => $n) {
                            $ins->execute([$votacion_id, $n['nombre'], $n['foto_url'], $i + 1, $n['roster_id']]);
                        }
                        $pdo->commit();
                        admin_log('motm.start', 'Started voting for game ' . $juego_id . ' (closes in 2h)');
                        $message = 'Voting started. It will close in 2 hours.';
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $message = 'Could not start voting. Please try again.';
                        $messageType = 'danger';
                    }
                }
            }
        }
    }
}

$votaciones = $pdo->query("
    SELECT v.id, v.juego_id, v.starts_at, v.ends_at, v.status, v.winner_nominee_id,
           j.fecha, j.rival, t.nombre_torneo
    FROM motm_votaciones v
    JOIN juegos j ON j.id = v.juego_id
    JOIN torneos_info t ON t.id = j.torneo_id
    ORDER BY v.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$winnerNames = [];
foreach ($votaciones as $v) {
    if ($v['winner_nominee_id']) {
        $wn = $pdo->prepare("SELECT nombre FROM motm_nominees WHERE id = ?");
        $wn->execute([$v['winner_nominee_id']]);
        $winnerNames[$v['id']] = $wn->fetchColumn() ?: '—';
    }
}

$juegos = $pdo->query("
    SELECT j.id, j.fecha, j.rival, t.nombre_torneo
    FROM juegos j
    JOIN torneos_info t ON t.id = j.torneo_id
    ORDER BY j.fecha DESC, j.hora DESC
")->fetchAll(PDO::FETCH_ASSOC);

$rosterOptions = $pdo->query("
    SELECT r.id, r.nombre, r.apellido, r.dorsal, c.nombre AS categoria_nombre
    FROM roster r
    JOIN categorias c ON c.id = r.categoria_id
    WHERE r.activo = 1
    ORDER BY c.nombre, r.dorsal, r.apellido
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Man of the Match - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'MOTM']]) ?>
    <h1 class="mb-4 admin-page-title">Man of the Match</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Start new voting (2 hours)</h5>
                    <p class="text-muted small">Select a game and three players from the roster. Voting will close automatically after 2 hours.</p>
                    <form method="post" action="">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label text-white small">Game</label>
                            <select class="form-select bg-dark text-white border-secondary" name="juego_id" required>
                                <option value="">Select game</option>
                                <?php foreach ($juegos as $j): ?>
                                    <option value="<?= (int) $j['id'] ?>"><?= htmlspecialchars($j['fecha'] . ' – ' . ($j['rival'] ?: 'TBD') . ' (' . $j['nombre_torneo'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Nominee 1 (from roster)</label>
                            <select class="form-select bg-dark text-white border-secondary" name="roster_1" required>
                                <option value="">Select player</option>
                                <?php foreach ($rosterOptions as $r): ?>
                                    <option value="<?= (int) $r['id'] ?>"><?= htmlspecialchars($r['apellido'] . ', ' . $r['nombre'] . ($r['dorsal'] !== null ? ' #' . $r['dorsal'] : '') . ' – ' . $r['categoria_nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Nominee 2 (from roster)</label>
                            <select class="form-select bg-dark text-white border-secondary" name="roster_2" required>
                                <option value="">Select player</option>
                                <?php foreach ($rosterOptions as $r): ?>
                                    <option value="<?= (int) $r['id'] ?>"><?= htmlspecialchars($r['apellido'] . ', ' . $r['nombre'] . ($r['dorsal'] !== null ? ' #' . $r['dorsal'] : '') . ' – ' . $r['categoria_nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Nominee 3 (from roster)</label>
                            <select class="form-select bg-dark text-white border-secondary" name="roster_3" required>
                                <option value="">Select player</option>
                                <?php foreach ($rosterOptions as $r): ?>
                                    <option value="<?= (int) $r['id'] ?>"><?= htmlspecialchars($r['apellido'] . ', ' . $r['nombre'] . ($r['dorsal'] !== null ? ' #' . $r['dorsal'] : '') . ' – ' . $r['categoria_nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;">Start voting (2 hours)</button>
                    </form>
                    <?php if (count($rosterOptions) === 0): ?>
                    <p class="small text-warning mt-2 mb-0">Add players in Roster first to select nominees.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <h5 class="text-white mb-3">Votaciones</h5>
            <div class="table-responsive">
                <table class="table table-dark table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Game</th>
                            <th>Starts</th>
                            <th>Ends</th>
                            <th>Status</th>
                            <th>Winner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($votaciones as $v): ?>
                            <tr>
                                <td><?= htmlspecialchars($v['fecha'] . ' vs ' . ($v['rival'] ?: 'TBD')) ?></td>
                                <td><?= date('M j, g:i A', strtotime($v['starts_at'])) ?></td>
                                <td><?= date('M j, g:i A', strtotime($v['ends_at'])) ?></td>
                                <td><span class="badge <?= $v['status'] === 'open' ? 'bg-success' : 'bg-secondary' ?>"><?= $v['status'] ?></span></td>
                                <td><?= $v['winner_nominee_id'] ? htmlspecialchars($winnerNames[$v['id']] ?? '—') : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($votaciones) === 0): ?>
                <p class="text-muted">No votations yet. Start one with the form.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
