<?php
require __DIR__ . '/includes/auth.php';
require_permission('jugador_mes');
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

$hasDorsal = false;
try {
    $st = $pdo->query("SHOW COLUMNS FROM jugador_mes LIKE 'dorsal'");
    $hasDorsal = $st && $st->fetch();
} catch (PDOException $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } else {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $dorsal = isset($_POST['dorsal']) && $_POST['dorsal'] !== '' ? (int) $_POST['dorsal'] : null;
    $descripcion_logro = trim($_POST['descripcion_logro'] ?? '');
    $mes = trim($_POST['mes'] ?? '');
    $foto_url = null;
    $roster_foto = trim($_POST['roster_foto_url'] ?? '');

    if ($nombre === '' || $categoria === '' || $descripcion_logro === '' || $mes === '') {
        $message = 'All fields (except photo) are required.';
        $messageType = 'danger';
    } else {
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
                $ext = match($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };
                $filename = 'star-' . uniqid() . '.' . $ext;
                $path = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $path)) {
                    $foto_url = 'assets/uploads/' . $filename;
                }
            }
        }
        if ($foto_url === null && $roster_foto !== '' && preg_match('/^assets\/uploads\/[a-zA-Z0-9_.-]+$/', $roster_foto)) {
            $foto_url = $roster_foto;
        }

        if ($message === '') {
            if ($hasDorsal) {
                if ($id > 0) {
                    if ($foto_url !== null) {
                        $stmt = $pdo->prepare("UPDATE jugador_mes SET nombre = ?, categoria = ?, dorsal = ?, foto_url = ?, descripcion_logro = ?, mes = ? WHERE id = ?");
                        $stmt->execute([$nombre, $categoria, $dorsal, $foto_url, $descripcion_logro, $mes, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE jugador_mes SET nombre = ?, categoria = ?, dorsal = ?, descripcion_logro = ?, mes = ? WHERE id = ?");
                        $stmt->execute([$nombre, $categoria, $dorsal, $descripcion_logro, $mes, $id]);
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO jugador_mes (nombre, categoria, dorsal, foto_url, descripcion_logro, mes) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nombre, $categoria, $dorsal, $foto_url ?? '', $descripcion_logro, $mes]);
                }
            } else {
                if ($id > 0) {
                    if ($foto_url !== null) {
                        $stmt = $pdo->prepare("UPDATE jugador_mes SET nombre = ?, categoria = ?, foto_url = ?, descripcion_logro = ?, mes = ? WHERE id = ?");
                        $stmt->execute([$nombre, $categoria, $foto_url, $descripcion_logro, $mes, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE jugador_mes SET nombre = ?, categoria = ?, descripcion_logro = ?, mes = ? WHERE id = ?");
                        $stmt->execute([$nombre, $categoria, $descripcion_logro, $mes, $id]);
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO jugador_mes (nombre, categoria, foto_url, descripcion_logro, mes) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nombre, $categoria, $foto_url ?? '', $descripcion_logro, $mes]);
                }
            }
            admin_log($id > 0 ? 'jugador_mes.update' : 'jugador_mes.create', ($id > 0 ? 'Updated' : 'Added') . ' Star of the Month: ' . $nombre . ' (' . $mes . ')');
            $message = $id > 0 ? 'Star of the Month updated.' : 'Star of the Month added.';
            $messageType = 'success';
        }
    }
    }
}

$jugadorMesCols = 'id, nombre, categoria, foto_url, descripcion_logro, mes';
if ($hasDorsal) $jugadorMesCols = 'id, nombre, categoria, dorsal, foto_url, descripcion_logro, mes';
$stmt = $pdo->query("SELECT $jugadorMesCols FROM jugador_mes ORDER BY created_at DESC");
$current = $stmt->fetch();

$rosterCols = 'r.id, r.nombre, r.apellido, r.foto_url, c.nombre AS categoria_nombre';
if ($hasDorsal) $rosterCols = 'r.id, r.nombre, r.apellido, r.dorsal, r.foto_url, c.nombre AS categoria_nombre';
$rosterForStar = $pdo->query("
    SELECT $rosterCols
    FROM roster r
    JOIN categorias c ON c.id = r.categoria_id
    WHERE r.activo = 1
    ORDER BY c.nombre ASC, r.apellido ASC, r.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Jugador del Mes - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <h1 class="mb-4" style="color: #FF6600;">VCF Star of the Month</h1>
    <p><a href="dashboard.php" class="text-decoration-none" style="color: #FF6600;">&larr; Dashboard</a></p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Set / Update Star of the Month</h5>
                    <form method="post" action="" enctype="multipart/form-data" id="formStarMes">
                        <?= csrf_field() ?>
                        <?php if ($current): ?>
                            <input type="hidden" name="id" value="<?= (int) $current['id'] ?>">
                        <?php endif; ?>
                        <input type="hidden" name="roster_foto_url" id="rosterFotoUrl" value="">
                        <?php if (count($rosterForStar) > 0): ?>
                        <div class="mb-3">
                            <label class="form-label text-white small">Fill from roster</label>
                            <select class="form-select bg-dark text-white border-secondary" id="selectRosterStar" aria-label="Select player to fill name and category">
                                <option value="">— Select a player —</option>
                                <?php foreach ($rosterForStar as $r): ?>
                                    <option value="<?= (int) $r['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($r['nombre']) ?>"
                                        data-apellido="<?= htmlspecialchars($r['apellido']) ?>"
                                        data-categoria="<?= htmlspecialchars($r['categoria_nombre']) ?>"
                                        data-foto="<?= htmlspecialchars($r['foto_url'] ?? '') ?>"
                                        data-dorsal="<?= isset($r['dorsal']) && $r['dorsal'] !== null ? (int) $r['dorsal'] : '' ?>">
                                        <?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?> · <?= htmlspecialchars($r['categoria_nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="small text-muted mt-1 mb-0">Choosing a player fills Name and Category and uses their roster photo if you don't upload another.</p>
                        </div>
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label text-white small">Name</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="nombre" id="starNombre" required placeholder="Player full name" value="<?= htmlspecialchars($current['nombre'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Category (e.g. U10)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="categoria" id="starCategoria" required placeholder="U6, U8, U10, U12" value="<?= htmlspecialchars($current['categoria'] ?? '') ?>">
                        </div>
                        <?php if ($hasDorsal): ?>
                        <div class="mb-2">
                            <label class="form-label text-white small">Jersey number (optional)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" name="dorsal" id="starDorsal" min="0" max="99" placeholder="—" value="<?= isset($current['dorsal']) && $current['dorsal'] !== null ? (int) $current['dorsal'] : '' ?>">
                        </div>
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label text-white small">Month (e.g. March 2026)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="mes" id="starMes" required placeholder="March 2026" value="<?= htmlspecialchars($current['mes'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Achievement description</label>
                            <textarea class="form-control bg-dark text-white border-secondary" name="descripcion_logro" id="starDescripcion" rows="3" required placeholder="Brief description of the player's achievement..."><?= htmlspecialchars($current['descripcion_logro'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Photo (JPG/PNG/WebP, max 5MB)</label>
                            <input type="file" class="form-control bg-dark text-white border-secondary" name="foto" id="starFoto" accept="image/jpeg,image/png,image/webp">
                            <?php if (!empty($current['foto_url'])): ?>
                                <p class="small text-white-50 mt-1 mb-0">Current photo is set. Upload a new file to replace.</p>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;"><?= $current ? 'Update' : 'Save' ?></button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <style>
                .vcf-star-preview-card { background: #1a1a1a; border: 2px solid #FF6600; border-radius: 16px; overflow: hidden; max-width: 360px; box-shadow: 0 12px 40px rgba(255,102,0,0.25); }
                .vcf-star-preview-photo-wrap { position: relative; aspect-ratio: 1; background: #2d2d2d; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 12px; }
                .vcf-star-preview-photo-wrap::before { content: ''; position: absolute; inset: 0; border-radius: 12px; padding: 3px; background: linear-gradient(135deg, #FF6600, #ff8833, #FF6600); -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
                .vcf-star-preview-photo { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; display: block; }
                .vcf-star-preview-photo-placeholder { width: 100%; height: 100%; background: #2d2d2d; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #FF6600; font-size: 4rem; opacity: 0.6; }
                .vcf-star-preview-dorsal { position: absolute; bottom: 8px; right: 12px; font-size: 3rem; font-weight: 800; color: rgba(255,255,255,0.25); line-height: 1; font-family: Oswald, sans-serif; }
                .vcf-star-preview-body { padding: 1.25rem; }
                .vcf-star-preview-nombre { color: #FF6600; font-size: 1.35rem; margin: 0 0 0.25rem; font-weight: 700; }
                .vcf-star-preview-meta { color: #8a8a8a; font-size: 0.9rem; margin-bottom: 0.5rem; }
                .vcf-star-preview-desc { color: #fff; font-size: 0.85rem; line-height: 1.45; margin: 0; }
            </style>
            <h5 class="text-white mb-3">Live preview — Cromo</h5>
            <div id="starPreviewCard" class="vcf-star-preview-card">
                <div class="vcf-star-preview-photo-wrap">
                    <img id="starPreviewPhoto" src="<?= !empty($current['foto_url']) ? '../' . htmlspecialchars($current['foto_url']) : '' ?>" alt="" class="vcf-star-preview-photo">
                    <div id="starPreviewPhotoPlaceholder" class="vcf-star-preview-photo-placeholder" style="<?= !empty($current['foto_url']) ? 'display:none' : '' ?>"><i class="fas fa-user"></i></div>
                    <?php if ($hasDorsal): ?><span id="starPreviewDorsal" class="vcf-star-preview-dorsal"><?= isset($current['dorsal']) && $current['dorsal'] !== null ? (int) $current['dorsal'] : '' ?></span><?php endif; ?>
                </div>
                <div class="vcf-star-preview-body">
                    <h3 id="starPreviewNombre" class="vcf-star-preview-nombre"><?= htmlspecialchars($current['nombre'] ?? 'Player name') ?></h3>
                    <p id="starPreviewMeta" class="vcf-star-preview-meta"><?= htmlspecialchars($current['categoria'] ?? '') ?> · <?= htmlspecialchars($current['mes'] ?? '') ?></p>
                    <p id="starPreviewDesc" class="vcf-star-preview-desc"><?= nl2br(htmlspecialchars(mb_substr($current['descripcion_logro'] ?? '', 0, 150))) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    var photoBase = '../';
    var previewPhoto = document.getElementById('starPreviewPhoto');
    var previewPlaceholder = document.getElementById('starPreviewPhotoPlaceholder');
    var previewDorsal = document.getElementById('starPreviewDorsal');
    var previewNombre = document.getElementById('starPreviewNombre');
    var previewMeta = document.getElementById('starPreviewMeta');
    var previewDesc = document.getElementById('starPreviewDesc');
    function updatePreview() {
        if (previewNombre) previewNombre.textContent = document.getElementById('starNombre').value || 'Player name';
        if (previewMeta) previewMeta.textContent = (document.getElementById('starCategoria').value || '') + ' · ' + (document.getElementById('starMes').value || '');
        if (previewDesc) previewDesc.innerHTML = (document.getElementById('starDescripcion').value || '').substring(0, 150).replace(/\n/g, '<br>');
        if (previewDorsal) {
            var d = document.getElementById('starDorsal');
            var v = d && d.value !== '' ? d.value : '';
            previewDorsal.textContent = v;
            previewDorsal.style.display = v ? '' : 'none';
        }
    }
    ['starNombre','starCategoria','starMes','starDescripcion','starDorsal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener('input', updatePreview); el.addEventListener('change', updatePreview); }
    });
    updatePreview();
    document.getElementById('starFoto').addEventListener('change', function() {
        var f = this.files && this.files[0];
        if (!f) return;
        var r = new FileReader();
        r.onload = function() { previewPhoto.src = r.result; previewPhoto.style.display = ''; if (previewPlaceholder) previewPlaceholder.style.display = 'none'; };
        r.readAsDataURL(f);
    });
    var selectRoster = document.getElementById('selectRosterStar');
    if (selectRoster) {
        selectRoster.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (opt.value === '') return;
            document.getElementById('starNombre').value = (opt.getAttribute('data-nombre') || '') + ' ' + (opt.getAttribute('data-apellido') || '');
            document.getElementById('starCategoria').value = opt.getAttribute('data-categoria') || '';
            document.getElementById('rosterFotoUrl').value = opt.getAttribute('data-foto') || '';
            if (document.getElementById('starDorsal')) document.getElementById('starDorsal').value = opt.getAttribute('data-dorsal') || '';
            var foto = opt.getAttribute('data-foto');
            if (foto && previewPhoto) { previewPhoto.src = photoBase + foto; previewPhoto.style.display = ''; if (previewPlaceholder) previewPlaceholder.style.display = 'none'; }
            updatePreview();
        });
    }
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
