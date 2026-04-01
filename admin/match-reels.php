<?php
require __DIR__ . '/includes/auth.php';
require_permission('match_reels');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$uploadDir = __DIR__ . '/../assets/uploads/reels/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowedTypes = ['video/mp4'];
$maxSize = 80 * 1024 * 1024; // 80MB

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    $likelySizeLimit = ($contentLength > 5 * 1024 * 1024) && empty($_POST['csrf_token']);
    if ($likelySizeLimit) {
        $message = 'The upload is too large for the server (PHP post_max_size / upload_max_filesize). Use a smaller video (e.g. under 20–40 MB) or ask your host to increase these limits.';
        $messageType = 'danger';
    } elseif (!csrf_verify()) {
        $message = 'Security token invalid or expired. The form below has been refreshed — try again, or log out and log back in if it persists.';
        $messageType = 'danger';
    } elseif (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        try {
            $stmt = $pdo->prepare("SELECT video_url FROM match_reels WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['video_url']) && strpos($row['video_url'], '..') === false) {
                $path = __DIR__ . '/../' . $row['video_url'];
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
            $pdo->prepare("DELETE FROM match_reels WHERE id = ?")->execute([$id]);
            if (function_exists('admin_log')) {
                admin_log('match_reels.delete', 'Deleted reel id ' . $id);
            }
        } catch (PDOException $e) {
            $message = 'Could not delete.';
            $messageType = 'danger';
        }
        if ($message === '') {
            $message = 'Reel deleted.';
            $messageType = 'success';
        }
    } else {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $caption = trim($_POST['caption'] ?? '');
        $player_id = isset($_POST['player_id']) && $_POST['player_id'] !== '' ? (int) $_POST['player_id'] : null;
        $match_id = isset($_POST['match_id']) && $_POST['match_id'] !== '' ? (int) $_POST['match_id'] : null;
        $orden = (int) ($_POST['orden'] ?? 0);
        $video_url = null;

        if ($id === 0 && empty($_FILES['video']['name'])) {
            $message = 'Video file is required for new reel. Use MP4. Max 80MB.';
            $messageType = 'danger';
        } else {
            if (!empty($_FILES['video']['name']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['video']['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mime, $allowedTypes, true)) {
                    $message = 'Invalid file type. Use MP4 only.';
                    $messageType = 'danger';
                } elseif ($_FILES['video']['size'] > $maxSize) {
                    $message = 'Video too large. Max 80MB.';
                    $messageType = 'danger';
                } else {
                    $clientName = basename($_FILES['video']['name'] ?? '');
                    if (strpos($clientName, '..') !== false || preg_match('/\.(php|phtml|phar|htaccess)(\.|$)/i', $clientName)) {
                        $message = 'Invalid file name.';
                        $messageType = 'danger';
                    } else {
                        $ext = 'mp4';
                        $filename = 'reel-' . uniqid() . '.' . $ext;
                        $fullPath = $uploadDir . $filename;
                        if (move_uploaded_file($_FILES['video']['tmp_name'], $fullPath)) {
                            $video_url = 'assets/uploads/reels/' . $filename;
                        } else {
                            $message = 'Upload failed.';
                            $messageType = 'danger';
                        }
                    }
                }
            }
        }

        if ($message === '') {
            try {
                if ($id > 0) {
                    if ($video_url !== null) {
                        $pdo->prepare("UPDATE match_reels SET video_url = ?, player_id = ?, match_id = ?, caption = ?, orden = ? WHERE id = ?")
                            ->execute([$video_url, $player_id, $match_id, $caption ?: null, $orden, $id]);
                    } else {
                        $pdo->prepare("UPDATE match_reels SET player_id = ?, match_id = ?, caption = ?, orden = ? WHERE id = ?")
                            ->execute([$player_id, $match_id, $caption ?: null, $orden, $id]);
                    }
                    if (function_exists('admin_log')) {
                        admin_log('match_reels.update', 'Updated reel id ' . $id);
                    }
                    $message = 'Reel updated.';
                } else {
                    $pdo->prepare("INSERT INTO match_reels (video_url, player_id, match_id, caption, orden) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$video_url, $player_id, $match_id, $caption ?: null, $orden]);
                    if (function_exists('admin_log')) {
                        admin_log('match_reels.create', 'Added reel: ' . ($caption ?: 'no caption'));
                    }
                    $message = 'Reel added.';
                }
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Database error. Run sql/migrate_match_reels.sql if needed.';
                $messageType = 'danger';
            }
        }
    }
}

$reels = [];
try {
    $reels = $pdo->query("SELECT id, video_url, player_id, match_id, caption, orden, created_at FROM match_reels ORDER BY orden ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table may not exist
}

$rosterList = [];
$juegosList = [];
try {
    $rosterList = $pdo->query("SELECT r.id, r.nombre, r.apellido, c.nombre AS categoria_nombre FROM roster r JOIN categorias c ON c.id = r.categoria_id WHERE r.activo = 1 ORDER BY c.nombre ASC, r.apellido ASC, r.nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    // Quien tenga Match Reels puede elegir cualquier partido (próximos y ya jugados).
    $juegosList = $pdo->query("SELECT j.id, j.fecha, j.rival, t.nombre_torneo FROM juegos j JOIN torneos_info t ON t.id = j.torneo_id ORDER BY j.fecha DESC, j.id DESC LIMIT 2000")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($reels as $r) {
        if ((int) $r['id'] === $editId) {
            $editing = $r;
            break;
        }
    }
}

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Match Reels - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Match Reels']]) ?>
    <h1 class="mb-4 admin-page-title">Match Reels</h1>
    <p class="text-muted small">Goal clips shown below Latest Results on the homepage. Optional: link to player and match.</p>
    <div class="alert alert-info py-2 mb-3 small" role="region" aria-label="Requisitos del vídeo">
        <strong>Formato y tamaño aceptado:</strong> solo <strong>MP4</strong>, máximo <strong>80 MB</strong> por archivo.<br>
        <strong>Dimensiones recomendadas para que se vea bien en la página:</strong> vídeo en vertical (formato Reels/TikTok), relación de aspecto <strong>9:16</strong>. Por ejemplo: <strong>1080×1920 px</strong> (Full HD vertical) o <strong>720×1280 px</strong> (HD vertical). Así el clip se muestra bien en las tarjetas de la home sin recortes raros.
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white"><?= $editing ? 'Edit reel' : 'Add reel' ?></h5>
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label text-white small">Video (MP4, máx. 80 MB)</label>
                            <input type="file" class="form-control bg-dark text-white border-secondary" name="video" accept="video/mp4">
                            <p class="small text-white-50 mt-1 mb-0">Recomendado: vertical 9:16 (ej. 1080×1920 o 720×1280 px).</p>
                            <?php if ($editing && !empty($editing['video_url'])): ?>
                                <p class="small text-white-50 mt-1 mb-0">Vídeo actual cargado. Sube otro para reemplazar.</p>
                            <?php endif; ?>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Caption</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="caption" placeholder="e.g. Golazo de Mateo - Final U13" value="<?= htmlspecialchars($editing['caption'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Player (optional)</label>
                            <select class="form-select bg-dark text-white border-secondary" name="player_id">
                                <option value="">— None —</option>
                                <?php foreach ($rosterList as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>" <?= $editing && (int)$editing['player_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido'] . ' (' . $p['categoria_nombre'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Match (optional)</label>
                            <p class="small text-white-50 mb-1">Puedes vincular el reel a cualquier partido: próximos o ya jugados.</p>
                            <select class="form-select bg-dark text-white border-secondary" name="match_id">
                                <option value="">— None —</option>
                                <?php foreach ($juegosList as $j): ?>
                                    <option value="<?= (int) $j['id'] ?>" <?= $editing && (int)$editing['match_id'] === (int)$j['id'] ? 'selected' : '' ?>><?= htmlspecialchars(date('M j', strtotime($j['fecha'])) . ' vs ' . ($j['rival'] ?: 'TBD') . ' - ' . $j['nombre_torneo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Order</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" name="orden" min="0" value="<?= (int) ($editing['orden'] ?? 0) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;"><?= $editing ? 'Update' : 'Add' ?></button>
                        <?php if ($editing): ?>
                            <a href="match-reels.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Reels</h5>
                    <?php if (count($reels) === 0): ?>
                        <p class="text-muted mb-0">No reels yet. Add one to show the section below Latest Results.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Caption</th>
                                        <th>Player / Match</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reels as $r): ?>
                                        <?php
                                        $playerName = '';
                                        $matchLabel = '';
                                        if (!empty($r['player_id'])) {
                                            foreach ($rosterList as $p) {
                                                if ((int) $p['id'] === (int) $r['player_id']) {
                                                    $playerName = $p['nombre'] . ' ' . $p['apellido'];
                                                    break;
                                                }
                                            }
                                        }
                                        if (!empty($r['match_id'])) {
                                            foreach ($juegosList as $j) {
                                                if ((int) $j['id'] === (int) $r['match_id']) {
                                                    $matchLabel = date('M j', strtotime($j['fecha'])) . ' vs ' . ($j['rival'] ?: 'TBD');
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><?= (int) $r['orden'] ?></td>
                                            <td><?= htmlspecialchars($r['caption'] ?: '—') ?></td>
                                            <td class="small"><?= htmlspecialchars($playerName ?: '—') ?><?= $playerName && $matchLabel ? ' · ' : '' ?><?= htmlspecialchars($matchLabel) ?></td>
                                            <td>
                                                <a href="match-reels.php?edit=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this reel?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>">
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
