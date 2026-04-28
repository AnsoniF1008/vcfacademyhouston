<?php
require __DIR__ . '/includes/auth.php';
require_permission('jugador_mes');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/upload_helper.php';
require __DIR__ . '/../config/database.php';

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize      = 5 * 1024 * 1024;

$message     = '';
$messageType = '';

// Schema detection (cached per-request)
static $hasStarVotingTable = null;
static $hasDorsal = null;
if ($hasStarVotingTable === null) {
    try { $pdo->query("SELECT 1 FROM star_votaciones LIMIT 1"); $hasStarVotingTable = true; }
    catch (PDOException $e) { $hasStarVotingTable = false; }
}
if ($hasDorsal === null) {
    try { $st = $pdo->query("SHOW COLUMNS FROM jugador_mes LIKE 'dorsal'"); $hasDorsal = (bool)($st && $st->fetch()); }
    catch (PDOException $e) { $hasDorsal = false; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.'; $messageType = 'danger';

    } elseif (isset($_POST['clear_jugador_mes'])) {
        // Fetch old photo before clearing
        $stOld = $pdo->query("SELECT foto_url FROM jugador_mes ORDER BY created_at DESC LIMIT 1");
        $oldPhoto = $stOld ? $stOld->fetchColumn() : null;
        $pdo->exec("DELETE FROM jugador_mes ORDER BY created_at DESC LIMIT 1");
        if ($oldPhoto) delete_upload($oldPhoto);
        admin_log('jugador_mes.clear', 'Cleared current Jugador del Mes');
        $message = 'Jugador del Mes borrado. La web mostrará "coming soon" hasta que asignes uno.'; $messageType = 'success';

    } elseif ($hasStarVotingTable && isset($_POST['close_star_id'])) {
        $vid = (int) $_POST['close_star_id'];
        $st  = $pdo->prepare("SELECT id, status FROM star_votaciones WHERE id = ?"); $st->execute([$vid]);
        $v   = $st->fetch(PDO::FETCH_ASSOC);
        if ($v && $v['status'] === 'open') {
            $top = $pdo->prepare("SELECT nominee_id FROM star_votes WHERE votacion_id = ? GROUP BY nominee_id ORDER BY COUNT(*) DESC, nominee_id ASC LIMIT 1");
            $top->execute([$vid]);
            $winnerId = $top->fetchColumn();
            if ($winnerId) {
                $pdo->prepare("UPDATE star_votaciones SET status='closed', winner_nominee_id=? WHERE id=?")->execute([$winnerId, $vid]);
                $nom = $pdo->prepare("SELECT nombre, categoria, foto_url, descripcion_logro FROM star_nominees WHERE id=?"); $nom->execute([$winnerId]);
                $winner = $nom->fetch(PDO::FETCH_ASSOC);
                $vot    = $pdo->prepare("SELECT mes FROM star_votaciones WHERE id=?"); $vot->execute([$vid]);
                $mesLabel = $vot->fetchColumn() ?: date('Y-m');
                if ($winner && !empty($_POST['copy_to_jugador_mes'])) {
                    $pdo->prepare("INSERT INTO jugador_mes (nombre, categoria, foto_url, descripcion_logro, mes) VALUES (?,?,?,?,?)")
                        ->execute([$winner['nombre'], $winner['categoria']??'', $winner['foto_url']??null, $winner['descripcion_logro']??'', $mesLabel]);
                }
                $message = 'Votación cerrada. Ganador: ' . $winner['nombre'] . '.';
            } else {
                $pdo->prepare("UPDATE star_votaciones SET status='closed' WHERE id=?")->execute([$vid]);
                $message = 'Votación cerrada sin votos.';
            }
            $messageType = 'success';
        }

    } elseif ($hasStarVotingTable && isset($_POST['start_star_voting'], $_POST['star_mes'], $_POST['star_starts'], $_POST['star_ends'])) {
        $mes    = mb_substr(trim($_POST['star_mes']),    0, 50);
        $starts = trim($_POST['star_starts']);
        $ends   = trim($_POST['star_ends']);
        $r1 = (int)($_POST['star_roster_1']??0);
        $r2 = (int)($_POST['star_roster_2']??0);
        $r3 = (int)($_POST['star_roster_3']??0);
        if ($mes !== '' && $starts !== '' && $ends !== '' && $r1 > 0 && $r2 > 0 && $r3 > 0 && $r1!==$r2 && $r2!==$r3 && $r1!==$r3) {
            $rosterStmt = $pdo->prepare("SELECT r.id, r.nombre, r.apellido, r.foto_url, c.nombre AS categoria_nombre FROM roster r JOIN categorias c ON c.id=r.categoria_id WHERE r.id=? AND r.activo=1");
            $nominees = [];
            foreach ([$r1,$r2,$r3] as $rid) {
                $rosterStmt->execute([$rid]); $row = $rosterStmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) break;
                $nominees[] = ['nombre'=>trim($row['nombre'].' '.$row['apellido']), 'categoria'=>$row['categoria_nombre'], 'foto_url'=>$row['foto_url']??'', 'descripcion_logro'=>'', 'roster_id'=>$rid];
            }
            if (count($nominees) === 3) {
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("INSERT INTO star_votaciones (mes, starts_at, ends_at, status) VALUES (?,?,?,'open')")->execute([$mes,$starts,$ends]);
                    $votacionId = (int)$pdo->lastInsertId();
                    $ins = $pdo->prepare("INSERT INTO star_nominees (votacion_id, nombre, categoria, foto_url, descripcion_logro, orden, roster_id) VALUES (?,?,?,?,?,?,?)");
                    foreach ($nominees as $i => $n) { $ins->execute([$votacionId, $n['nombre'], $n['categoria'], $n['foto_url'], $n['descripcion_logro'], $i+1, $n['roster_id']]); }
                    $pdo->commit();
                    $message = 'Votación iniciada. Los padres pueden votar en la web.'; $messageType = 'success';
                } catch (Exception $e) { $pdo->rollBack(); $message = 'No se pudo crear la votación.'; $messageType = 'danger'; }
            } else { $message = 'Selecciona 3 jugadores distintos del roster.'; $messageType = 'danger'; }
        } else { $message = 'Completa mes, fechas y 3 jugadores distintos.'; $messageType = 'danger'; }

    } else {
        $id              = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $nombre          = mb_substr(trim($_POST['nombre']          ?? ''), 0, 255);
        $categoria       = mb_substr(trim($_POST['categoria']       ?? ''), 0, 100);
        $dorsal          = isset($_POST['dorsal']) && $_POST['dorsal'] !== '' ? (int) $_POST['dorsal'] : null;
        $descripcion_logro = mb_substr(trim($_POST['descripcion_logro'] ?? ''), 0, 1000);
        $mes             = mb_substr(trim($_POST['mes']             ?? ''), 0, 50);
        $foto_url        = null;
        $roster_foto     = trim($_POST['roster_foto_url'] ?? '');

        if ($nombre === '' || $categoria === '' || $descripcion_logro === '' || $mes === '') {
            $message = 'All fields (except photo) are required.'; $messageType = 'danger';
        } else {
            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE); $mime = finfo_file($finfo, $_FILES['foto']['tmp_name']); finfo_close($finfo);
                if (!in_array($mime, $allowedTypes, true)) { $message = 'Invalid image type.'; $messageType = 'danger'; }
                elseif ($_FILES['foto']['size'] > $maxSize) { $message = 'Image too large. Max 5MB.'; $messageType = 'danger'; }
                else {
                    $clientName = basename($_FILES['foto']['name'] ?? '');
                    if (strpos($clientName,'..') !== false || preg_match('/\.(php|phtml|php3|php4|php5|php7|phps|phar|htaccess|pl|py|jsp|asp|aspx|cgi|fcgi)(\.|$)/i', $clientName)) {
                        $message = 'Invalid file name.'; $messageType = 'danger';
                    } else {
                        $ext = match($mime){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>'jpg'};
                        $ext = in_array($ext,['jpg','png','webp'],true)?$ext:'jpg';
                        $filename = 'star-' . uniqid() . '.' . $ext;
                        $path     = $uploadDir . $filename;
                        if (move_uploaded_file($_FILES['foto']['tmp_name'], $path)) { $foto_url = 'assets/uploads/' . $filename; }
                    }
                }
            }
            if ($foto_url === null && $roster_foto !== '' && preg_match('/^assets\/uploads\/[a-zA-Z0-9_.\-]+$/', $roster_foto)) {
                $foto_url = $roster_foto;
            }

            if ($message === '') {
                // Cleanup old photo if we're replacing it
                if ($foto_url !== null && $id > 0) {
                    $stOld = $pdo->prepare("SELECT foto_url FROM jugador_mes WHERE id = ?"); $stOld->execute([$id]);
                    $oldPhoto = $stOld->fetchColumn();
                    if ($oldPhoto && $oldPhoto !== $foto_url) delete_upload($oldPhoto);
                }

                if ($hasDorsal) {
                    if ($id > 0) {
                        if ($foto_url !== null) { $pdo->prepare("UPDATE jugador_mes SET nombre=?, categoria=?, dorsal=?, foto_url=?, descripcion_logro=?, mes=? WHERE id=?")->execute([$nombre,$categoria,$dorsal,$foto_url,$descripcion_logro,$mes,$id]); }
                        else { $pdo->prepare("UPDATE jugador_mes SET nombre=?, categoria=?, dorsal=?, descripcion_logro=?, mes=? WHERE id=?")->execute([$nombre,$categoria,$dorsal,$descripcion_logro,$mes,$id]); }
                    } else { $pdo->prepare("INSERT INTO jugador_mes (nombre, categoria, dorsal, foto_url, descripcion_logro, mes) VALUES (?,?,?,?,?,?)")->execute([$nombre,$categoria,$dorsal,$foto_url??'',$descripcion_logro,$mes]); }
                } else {
                    if ($id > 0) {
                        if ($foto_url !== null) { $pdo->prepare("UPDATE jugador_mes SET nombre=?, categoria=?, foto_url=?, descripcion_logro=?, mes=? WHERE id=?")->execute([$nombre,$categoria,$foto_url,$descripcion_logro,$mes,$id]); }
                        else { $pdo->prepare("UPDATE jugador_mes SET nombre=?, categoria=?, descripcion_logro=?, mes=? WHERE id=?")->execute([$nombre,$categoria,$descripcion_logro,$mes,$id]); }
                    } else { $pdo->prepare("INSERT INTO jugador_mes (nombre, categoria, foto_url, descripcion_logro, mes) VALUES (?,?,?,?,?)")->execute([$nombre,$categoria,$foto_url??'',$descripcion_logro,$mes]); }
                }
                admin_log($id > 0 ? 'jugador_mes.update' : 'jugador_mes.create', ($id > 0 ? 'Updated' : 'Added') . ' Star of the Month: ' . $nombre . ' (' . $mes . ')');
                $message = $id > 0 ? 'Star of the Month updated.' : 'Star of the Month added.'; $messageType = 'success';
            }
        }
    }
}

$starVotaciones = [];
if ($hasStarVotingTable) {
    $starVotaciones = $pdo->query("SELECT v.id, v.mes, v.starts_at, v.ends_at, v.status, v.winner_nominee_id, n.nombre AS winner_nombre FROM star_votaciones v LEFT JOIN star_nominees n ON n.id=v.winner_nominee_id ORDER BY v.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}

$jugadorMesCols = 'id, nombre, categoria, foto_url, descripcion_logro, mes';
if ($hasDorsal) $jugadorMesCols = 'id, nombre, categoria, dorsal, foto_url, descripcion_logro, mes';
$current = $pdo->query("SELECT $jugadorMesCols FROM jugador_mes ORDER BY created_at DESC")->fetch();

$rosterCols = 'r.id, r.nombre, r.apellido, r.foto_url, c.nombre AS categoria_nombre';
if ($hasDorsal) $rosterCols = 'r.id, r.nombre, r.apellido, r.dorsal, r.foto_url, c.nombre AS categoria_nombre';
$rosterForStar = $pdo->query("SELECT $rosterCols FROM roster r JOIN categorias c ON c.id=r.categoria_id WHERE r.activo=1 ORDER BY c.nombre ASC, r.apellido ASC, r.nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Jugador del Mes - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Jugador del Mes']]) ?>
    <h1 class="mb-4 admin-page-title">VCF Star of the Month</h1>

    <?php if ($message): ?><div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Set / Update Star of the Month</h5>
                    <form method="post" action="" enctype="multipart/form-data" id="formStarMes">
                        <?= csrf_field() ?>
                        <?php if ($current): ?><input type="hidden" name="id" value="<?= (int) $current['id'] ?>"><?php endif; ?>
                        <input type="hidden" name="roster_foto_url" id="rosterFotoUrl" value="">
                        <?php if (count($rosterForStar) > 0): ?>
                        <div class="mb-3">
                            <label class="form-label text-white small">Fill from roster</label>
                            <select class="form-select bg-dark text-white border-secondary" id="selectRosterStar">
                                <option value="">— Select a player —</option>
                                <?php foreach ($rosterForStar as $r): ?>
                                    <option value="<?= (int) $r['id'] ?>" data-nombre="<?= htmlspecialchars($r['nombre']) ?>" data-apellido="<?= htmlspecialchars($r['apellido']) ?>" data-categoria="<?= htmlspecialchars($r['categoria_nombre']) ?>" data-foto="<?= htmlspecialchars($r['foto_url']??'') ?>" data-dorsal="<?= isset($r['dorsal'])&&$r['dorsal']!==null?(int)$r['dorsal']:'' ?>">
                                        <?= htmlspecialchars($r['nombre'].' '.$r['apellido']) ?> · <?= htmlspecialchars($r['categoria_nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="mb-2"><label class="form-label text-white small">Name</label><input type="text" class="form-control bg-dark text-white border-secondary" name="nombre" id="starNombre" required maxlength="255" placeholder="Player full name" value="<?= htmlspecialchars($current['nombre']??'') ?>"></div>
                        <div class="mb-2"><label class="form-label text-white small">Category (e.g. U10)</label><input type="text" class="form-control bg-dark text-white border-secondary" name="categoria" id="starCategoria" required maxlength="100" placeholder="U6, U8, U10, U12" value="<?= htmlspecialchars($current['categoria']??'') ?>"></div>
                        <?php if ($hasDorsal): ?><div class="mb-2"><label class="form-label text-white small">Jersey number (optional)</label><input type="number" class="form-control bg-dark text-white border-secondary" name="dorsal" id="starDorsal" min="0" max="99" placeholder="—" value="<?= isset($current['dorsal'])&&$current['dorsal']!==null?(int)$current['dorsal']:'' ?>"></div><?php endif; ?>
                        <div class="mb-2"><label class="form-label text-white small">Month (e.g. March 2026)</label><input type="text" class="form-control bg-dark text-white border-secondary" name="mes" id="starMes" required maxlength="50" placeholder="March 2026" value="<?= htmlspecialchars($current['mes']??'') ?>"></div>
                        <div class="mb-2"><label class="form-label text-white small">Achievement description</label><textarea class="form-control bg-dark text-white border-secondary" name="descripcion_logro" id="starDescripcion" rows="3" required maxlength="1000" placeholder="Brief description..."><?= htmlspecialchars($current['descripcion_logro']??'') ?></textarea></div>
                        <div class="mb-3">
                            <label class="form-label text-white small">Photo (JPG/PNG/WebP, max 5MB)</label>
                            <input type="file" class="form-control bg-dark text-white border-secondary" name="foto" id="starFoto" accept="image/jpeg,image/png,image/webp">
                            <?php if (!empty($current['foto_url'])): ?><p class="small text-white-50 mt-1 mb-0">Current photo set. Upload to replace (old file will be deleted).</p><?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;"><?= $current ? 'Update' : 'Save' ?></button>
                        <?php if ($current): ?>
                        <div class="mt-3 pt-3 border-top border-secondary">
                            <p class="small text-muted mb-2">Reiniciar: quitar el Jugador del Mes actual (su foto será eliminada del servidor).</p>
                            <form method="post" class="d-inline" onsubmit="return confirm('¿Borrar el Jugador del Mes actual?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="clear_jugador_mes" value="1">
                                <button type="submit" class="btn btn-outline-danger btn-sm">Borrar Jugador del Mes actual</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <style>
                .vcf-star-preview-card{background:#1a1a1a;border:2px solid #FF6600;border-radius:16px;overflow:hidden;max-width:360px;box-shadow:0 12px 40px rgba(255,102,0,.25)}
                .vcf-star-preview-photo-wrap{position:relative;aspect-ratio:1;background:#2d2d2d;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:12px}
                .vcf-star-preview-photo{width:100%;height:100%;object-fit:cover;border-radius:10px;display:block}
                .vcf-star-preview-photo-placeholder{width:100%;height:100%;background:#2d2d2d;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#FF6600;font-size:4rem;opacity:.6}
                .vcf-star-preview-dorsal{position:absolute;bottom:8px;right:12px;font-size:3rem;font-weight:800;color:rgba(255,255,255,.25);line-height:1}
                .vcf-star-preview-body{padding:1.25rem}
                .vcf-star-preview-nombre{color:#FF6600;font-size:1.35rem;margin:0 0 .25rem;font-weight:700}
                .vcf-star-preview-meta{color:#8a8a8a;font-size:.9rem;margin-bottom:.5rem}
                .vcf-star-preview-desc{color:#fff;font-size:.85rem;line-height:1.45;margin:0}
            </style>
            <h5 class="text-white mb-3">Live preview — Cromo</h5>
            <div id="starPreviewCard" class="vcf-star-preview-card">
                <div class="vcf-star-preview-photo-wrap">
                    <img id="starPreviewPhoto" src="<?= !empty($current['foto_url']) ? '../'.htmlspecialchars($current['foto_url']) : '' ?>" alt="" class="vcf-star-preview-photo">
                    <div id="starPreviewPhotoPlaceholder" class="vcf-star-preview-photo-placeholder" style="<?= !empty($current['foto_url'])?'display:none':'' ?>"><i class="fas fa-user"></i></div>
                    <?php if ($hasDorsal): ?><span id="starPreviewDorsal" class="vcf-star-preview-dorsal"><?= isset($current['dorsal'])&&$current['dorsal']!==null?(int)$current['dorsal']:'' ?></span><?php endif; ?>
                </div>
                <div class="vcf-star-preview-body">
                    <h3 id="starPreviewNombre" class="vcf-star-preview-nombre"><?= htmlspecialchars($current['nombre']??'Player name') ?></h3>
                    <p  id="starPreviewMeta"   class="vcf-star-preview-meta"><?= htmlspecialchars($current['categoria']??'') ?> · <?= htmlspecialchars($current['mes']??'') ?></p>
                    <p  id="starPreviewDesc"   class="vcf-star-preview-desc"><?= nl2br(htmlspecialchars(mb_substr($current['descripcion_logro']??'',0,150))) ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($hasStarVotingTable): ?>
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Votación comunitaria (Star of the Month)</h5>
                    <form method="post" class="row g-3 mb-4">
                        <?= csrf_field() ?><input type="hidden" name="start_star_voting" value="1">
                        <div class="col-md-2"><label class="form-label text-white small">Mes (ej. 2026-03)</label><input type="text" class="form-control bg-dark text-white border-secondary" name="star_mes" maxlength="50" placeholder="2026-03" value="<?= date('Y-m') ?>"></div>
                        <div class="col-md-2"><label class="form-label text-white small">Inicio</label><input type="datetime-local" class="form-control bg-dark text-white border-secondary" name="star_starts" required></div>
                        <div class="col-md-2"><label class="form-label text-white small">Fin</label><input type="datetime-local" class="form-control bg-dark text-white border-secondary" name="star_ends" required></div>
                        <?php foreach ([1,2,3] as $n): ?>
                        <div class="col-md-2"><label class="form-label text-white small">Nominado <?= $n ?></label><select class="form-select bg-dark text-white border-secondary" name="star_roster_<?= $n ?>" required><option value="">—</option><?php foreach ($rosterForStar as $r): ?><option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nombre'].' '.$r['apellido']) ?></option><?php endforeach; ?></select></div>
                        <?php endforeach; ?>
                        <div class="col-12"><button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;">Iniciar votación</button></div>
                    </form>
                    <h6 class="text-white mb-2">Votaciones</h6>
                    <?php if (count($starVotaciones) === 0): ?><p class="text-muted small mb-0">No hay votaciones. Crea una arriba.</p>
                    <?php else: ?>
                    <div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Mes</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Ganador</th><th></th></tr></thead><tbody>
                    <?php foreach ($starVotaciones as $sv): ?>
                        <tr>
                            <td><?= htmlspecialchars($sv['mes']) ?></td>
                            <td><?= date('M j, g:i A', strtotime($sv['starts_at'])) ?></td>
                            <td><?= date('M j, g:i A', strtotime($sv['ends_at'])) ?></td>
                            <td><span class="badge <?= $sv['status']==='open'?'bg-success':'bg-secondary' ?>"><?= $sv['status'] ?></span></td>
                            <td><?= $sv['winner_nombre'] ? htmlspecialchars($sv['winner_nombre']) : '—' ?></td>
                            <td><?php if ($sv['status']==='open'): ?>
                                <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="close_star_id" value="<?= (int)$sv['id'] ?>">
                                <label class="small text-white-50 me-2"><input type="checkbox" name="copy_to_jugador_mes" value="1"> Copiar ganador a Jugador del Mes</label>
                                <button type="submit" class="btn btn-sm btn-warning">Cerrar votación</button></form>
                            <?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
(function() {
    var photoBase = '../';
    var previewPhoto       = document.getElementById('starPreviewPhoto');
    var previewPlaceholder = document.getElementById('starPreviewPhotoPlaceholder');
    var previewDorsal      = document.getElementById('starPreviewDorsal');
    var previewNombre      = document.getElementById('starPreviewNombre');
    var previewMeta        = document.getElementById('starPreviewMeta');
    var previewDesc        = document.getElementById('starPreviewDesc');
    function updatePreview() {
        if (previewNombre) previewNombre.textContent = document.getElementById('starNombre').value || 'Player name';
        if (previewMeta) previewMeta.textContent = (document.getElementById('starCategoria').value||'') + ' · ' + (document.getElementById('starMes').value||'');
        if (previewDesc) previewDesc.innerHTML = (document.getElementById('starDescripcion').value||'').substring(0,150).replace(/\n/g,'<br>');
        if (previewDorsal) { var d=document.getElementById('starDorsal'); var v=d&&d.value!==''?d.value:''; previewDorsal.textContent=v; previewDorsal.style.display=v?'':'none'; }
    }
    ['starNombre','starCategoria','starMes','starDescripcion','starDorsal'].forEach(function(id) { var el=document.getElementById(id); if(el){el.addEventListener('input',updatePreview);el.addEventListener('change',updatePreview);} });
    updatePreview();
    var fotoInput = document.getElementById('starFoto');
    if (fotoInput) fotoInput.addEventListener('change', function() {
        var f=this.files&&this.files[0]; if(!f) return;
        var r=new FileReader(); r.onload=function(){ previewPhoto.src=r.result; previewPhoto.style.display=''; if(previewPlaceholder) previewPlaceholder.style.display='none'; }; r.readAsDataURL(f);
    });
    var selectRoster = document.getElementById('selectRosterStar');
    if (selectRoster) selectRoster.addEventListener('change', function() {
        var opt=this.options[this.selectedIndex]; if(opt.value==='') return;
        document.getElementById('starNombre').value   = (opt.getAttribute('data-nombre')||'') + ' ' + (opt.getAttribute('data-apellido')||'');
        document.getElementById('starCategoria').value = opt.getAttribute('data-categoria')||'';
        document.getElementById('rosterFotoUrl').value = opt.getAttribute('data-foto')||'';
        if (document.getElementById('starDorsal')) document.getElementById('starDorsal').value = opt.getAttribute('data-dorsal')||'';
        var foto=opt.getAttribute('data-foto'); if(foto&&previewPhoto){ previewPhoto.src=photoBase+foto; previewPhoto.style.display=''; if(previewPlaceholder) previewPlaceholder.style.display='none'; }
        updatePreview();
    });
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
