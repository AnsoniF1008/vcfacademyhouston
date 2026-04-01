<?php
/**
 * Match detail page.
 * Usage: match.php?id=1
 */
require __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php#tournaments');
    exit;
}

// Load match data
$match = null;
try {
    $s = $pdo->prepare("
        SELECT j.id, j.fecha, j.hora, j.rival, j.rival_logo_url, j.cancha, j.ubicacion_mapa_url,
               j.goles_vcf, j.goles_rival, j.estado, j.categoria,
               t.nombre_torneo, t.temporada,
               s.nombre AS sede_nombre, s.mapa_general_url
        FROM juegos j
        JOIN torneos_info t ON t.id = j.torneo_id
        LEFT JOIN sedes s ON s.id = j.sede_id
        WHERE j.id = ?
    ");
    $s->execute([$id]);
    $match = $s->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Match detail: ' . $e->getMessage());
}

if (!$match) {
    header('Location: index.php#tournaments');
    exit;
}

// Load scorers
$scorers = [];
try {
    $s = $pdo->prepare("
        SELECT g.minuto, g.tipo, r.nombre, r.apellido, r.dorsal
        FROM juego_goles g
        LEFT JOIN roster r ON r.id = g.jugador_id
        WHERE g.juego_id = ?
        ORDER BY g.minuto ASC
    ");
    $s->execute([$id]);
    $scorers = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* table may not exist */ }

// Load MOTM winner if any
$motm = null;
try {
    $s = $pdo->prepare("
        SELECT mn.nombre, mn.foto_url
        FROM motm_votaciones mv
        JOIN motm_nominees mn ON mn.id = mv.winner_nominee_id
        WHERE mv.juego_id = ? AND mv.winner_nominee_id IS NOT NULL
        LIMIT 1
    ");
    $s->execute([$id]);
    $motm = $s->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* table may not exist */ }

// Computed values
$hasScore = isset($match['goles_vcf']) && $match['goles_vcf'] !== null;
$gameTs   = strtotime($match['fecha'] . ' ' . ($match['hora'] ?? '12:00:00'));
$isPast   = ($gameTs < time());
$rival    = $match['rival'] ?: 'TBD';
$location = ($match['sede_nombre'] && $match['cancha'])
    ? $match['sede_nombre'] . ' · ' . $match['cancha']
    : ($match['cancha'] ?? $match['sede_nombre'] ?? '');

if ($hasScore) {
    $gv = (int)$match['goles_vcf'];
    $gr = (int)$match['goles_rival'];
    $outcome = $gv > $gr ? 'W' : ($gv < $gr ? 'L' : 'D');
    $outcomeLabel = $gv > $gr ? 'Win' : ($gv < $gr ? 'Loss' : 'Draw');
    $outcomeColor = $gv > $gr ? '#22c55e' : ($gv < $gr ? '#ef4444' : '#aaaaaa');
} else {
    $outcome = null;
    $outcomeLabel = $isPast ? 'Finished' : 'Upcoming';
    $outcomeColor = 'var(--vcf-orange,#E87722)';
}

$vcf_crest_file = file_exists(__DIR__ . '/assets/img/vcf-crest.png') ? 'vcf-crest.png' : (file_exists(__DIR__ . '/assets/img/vfc-crest.svg') ? 'vfc-crest.svg' : null);

$page_title = 'VCF Houston vs ' . $rival . ' — ' . date('M j, Y', $gameTs) . ' | VCF Academy Houston';
$page_description = 'Match details: VCF Academy Houston vs ' . $rival . ' on ' . date('F j, Y', $gameTs) . '. ' . ($hasScore ? "Final score: $gv–$gr." : 'Upcoming match.');
$og_image = $match['rival_logo_url'] ?: '';
require __DIR__ . '/includes/header.php';
?>

<section class="vcf-section--dark vcf-page-sub vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container py-5" style="max-width:760px;">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb" style="background:transparent;padding:0;font-size:12px;color:var(--vcf-gray);">
                <li class="breadcrumb-item"><a href="<?= $base ?? '' ?>/index.php" style="color:var(--vcf-gray);text-decoration:none;">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= $base ?? '' ?>/index.php#tournaments" style="color:var(--vcf-gray);text-decoration:none;">Schedule</a></li>
                <li class="breadcrumb-item active" style="color:var(--vcf-orange,#E87722);" aria-current="page">Match #<?= $id ?></li>
            </ol>
        </nav>

        <!-- Tournament / date header -->
        <div class="mb-3" style="font-size:12px;color:var(--vcf-gray);text-transform:uppercase;letter-spacing:.1em;font-weight:700;">
            <?= htmlspecialchars($match['nombre_torneo']) ?><?= $match['temporada'] ? ' · ' . htmlspecialchars($match['temporada']) : '' ?>
        </div>

        <!-- Score card -->
        <div class="card mb-4" style="background:#111;border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:32px 24px;">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <!-- VCF side -->
                <div class="text-center" style="flex:1;">
                    <?php if ($vcf_crest_file): ?>
                    <img src="<?= $base ?? '' ?>/assets/img/<?= $vcf_crest_file ?>" alt="VCF Houston" width="56" height="56" style="object-fit:contain;margin-bottom:8px;">
                    <?php else: ?>
                    <div style="width:56px;height:56px;background:rgba(255,255,255,0.05);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;"><i class="fas fa-shield" style="color:var(--vcf-orange);font-size:24px;"></i></div>
                    <?php endif; ?>
                    <div class="fw-bold text-white" style="font-size:14px;">VCF Houston</div>
                </div>

                <!-- Score / vs -->
                <div class="text-center" style="min-width:100px;">
                    <?php if ($hasScore): ?>
                    <div style="font-size:48px;font-weight:900;font-family:var(--font-display,sans-serif);line-height:1;color:#fff;">
                        <span style="color:<?= $gv >= $gr ? '#fff' : 'var(--vcf-gray,#aaa)' ?>"><?= $gv ?></span>
                        <span style="color:var(--vcf-gray,#555);font-size:32px;"> – </span>
                        <span style="color:<?= $gr > $gv ? '#fff' : 'var(--vcf-gray,#aaa)' ?>"><?= $gr ?></span>
                    </div>
                    <div style="font-size:11px;font-weight:700;letter-spacing:.12em;margin-top:6px;color:<?= $outcomeColor ?>;"><?= $outcomeLabel ?></div>
                    <?php else: ?>
                    <div style="font-size:28px;font-weight:700;color:var(--vcf-gray,#555);line-height:1;">vs</div>
                    <div style="font-size:11px;font-weight:700;letter-spacing:.12em;margin-top:8px;color:<?= $outcomeColor ?>;"><?= $outcomeLabel ?></div>
                    <?php endif; ?>
                    <div style="font-size:10px;color:var(--vcf-gray);margin-top:4px;"><?= date('D, M j Y', $gameTs) ?></div>
                    <?php if ($match['hora']): ?>
                    <div style="font-size:10px;color:var(--vcf-gray);"><?= date('g:i A', $gameTs) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Rival side -->
                <div class="text-center" style="flex:1;">
                    <?php if ($match['rival_logo_url']): ?>
                    <img src="<?= htmlspecialchars($match['rival_logo_url']) ?>" alt="<?= htmlspecialchars($rival) ?>" width="56" height="56" style="object-fit:contain;margin-bottom:8px;" onerror="this.style.display='none'">
                    <?php else: ?>
                    <div style="width:56px;height:56px;background:rgba(255,255,255,0.05);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;"><i class="fas fa-shield" style="color:var(--vcf-gray);font-size:24px;"></i></div>
                    <?php endif; ?>
                    <div class="fw-bold text-white" style="font-size:14px;"><?= htmlspecialchars($rival) ?></div>
                </div>
            </div>
        </div>

        <!-- Match info -->
        <div class="card mb-4" style="background:#111;border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:20px 24px;">
            <div class="row g-3" style="font-size:13px;">
                <?php if ($location): ?>
                <div class="col-sm-6">
                    <div style="color:var(--vcf-gray,#aaa);font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;margin-bottom:3px;">Venue</div>
                    <div class="text-white"><?= htmlspecialchars($location) ?></div>
                    <?php if ($match['ubicacion_mapa_url']): ?>
                    <a href="<?= htmlspecialchars($match['ubicacion_mapa_url']) ?>" target="_blank" rel="noopener noreferrer" style="font-size:11px;color:var(--vcf-orange);">Open GPS &rarr;</a>
                    <?php elseif ($match['mapa_general_url']): ?>
                    <a href="<?= htmlspecialchars($match['mapa_general_url']) ?>" target="_blank" rel="noopener noreferrer" style="font-size:11px;color:var(--vcf-orange);">Open GPS &rarr;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($match['categoria']): ?>
                <div class="col-sm-6">
                    <div style="color:var(--vcf-gray,#aaa);font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;margin-bottom:3px;">Category</div>
                    <div class="text-white"><?= htmlspecialchars($match['categoria']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Scorers -->
        <?php if (!empty($scorers)): ?>
        <div class="card mb-4" style="background:#111;border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:20px 24px;">
            <h2 class="h6 mb-3" style="color:var(--vcf-orange);font-family:var(--font-display,sans-serif);text-transform:uppercase;letter-spacing:.08em;font-size:11px;">Goals</h2>
            <?php foreach ($scorers as $g): ?>
            <div class="d-flex align-items-center gap-2 mb-2" style="font-size:13px;">
                <span style="color:var(--vcf-orange);">
                    <?php if (($g['tipo'] ?? '') === 'og'): ?>
                    <i class="fas fa-futbol" style="opacity:.5;" aria-hidden="true"></i>
                    <?php elseif (($g['tipo'] ?? '') === 'penalty'): ?>
                    <i class="fas fa-futbol" aria-hidden="true"></i>
                    <?php else: ?>
                    <i class="fas fa-futbol" aria-hidden="true"></i>
                    <?php endif; ?>
                </span>
                <span class="text-white">
                    <?= htmlspecialchars(trim(($g['nombre'] ?? '') . ' ' . ($g['apellido'] ?? '')) ?: 'Unknown') ?>
                    <?= $g['dorsal'] !== null ? '<span style="color:var(--vcf-gray);font-size:11px;"> #' . (int)$g['dorsal'] . '</span>' : '' ?>
                </span>
                <?php if ($g['minuto']): ?><span style="color:var(--vcf-gray);font-size:11px;"><?= (int)$g['minuto'] ?>'</span><?php endif; ?>
                <?php if (($g['tipo'] ?? '') === 'og'): ?><span style="color:var(--vcf-gray);font-size:10px;">(OG)</span><?php endif; ?>
                <?php if (($g['tipo'] ?? '') === 'penalty'): ?><span style="color:var(--vcf-gray);font-size:10px;">(P)</span><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Man of the Match -->
        <?php if ($motm): ?>
        <div class="card mb-4" style="background:#111;border:1px solid rgba(232,119,34,0.3);border-radius:10px;padding:20px 24px;display:flex;align-items:center;gap:16px;">
            <?php if ($motm['foto_url']): ?>
            <img src="<?= htmlspecialchars($motm['foto_url']) ?>" alt="MOTM" width="52" height="52" style="border-radius:50%;object-fit:cover;border:2px solid var(--vcf-orange);">
            <?php endif; ?>
            <div>
                <div style="color:var(--vcf-orange);font-size:10px;text-transform:uppercase;letter-spacing:.1em;font-weight:700;">Man of the Match</div>
                <div class="text-white fw-bold"><?= htmlspecialchars($motm['nombre']) ?></div>
            </div>
            <i class="fas fa-trophy ms-auto" style="color:var(--vcf-orange);font-size:20px;" aria-hidden="true"></i>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="d-flex flex-wrap gap-2 mt-2">
            <?php if (!$isPast): ?>
            <a href="calendar.php?id=<?= $id ?>" class="vcf-btn-cta" style="font-size:13px;">+ Add to Calendar</a>
            <?php endif; ?>
            <a href="<?= $base ?? '' ?>/index.php#tournaments" class="btn btn-outline-light btn-sm">Back to Schedule</a>
            <a href="calendar.php" class="btn btn-outline-light btn-sm">Full Calendar</a>
        </div>

    </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
