<?php
require __DIR__ . '/config/database.php';

$sedes = [];
$juegosPorTorneo = [];
$proximoJuego = null;
$proximosJuegos = [];
$jugadorMes = null;
$categorias = [];

$canchasBySede = [];
try {
    $stmt = $pdo->query("SELECT id, nombre, direccion, mapa_general_url, nota_acceso FROM sedes ORDER BY nombre");
    $sedes = $stmt->fetchAll();
    if (count($sedes) > 0) {
        $ids = array_column($sedes, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, sede_id, numero_cancha, sobrenombre, indicaciones_extra, mapa_url FROM canchas WHERE sede_id IN ($placeholders) ORDER BY sede_id, numero_cancha");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $c) {
            $canchasBySede[$c['sede_id']][] = $c;
        }
    }
} catch (PDOException $e) {
    error_log('Sedes/Canchas query: ' . $e->getMessage());
}

try {
    $stmt = $pdo->query("
        SELECT j.id, j.torneo_id, j.fecha, j.hora, j.rival, j.cancha, j.sede_id, j.ubicacion_mapa_url, j.estado, j.categoria,
               t.nombre_torneo, t.temporada, s.nombre AS sede_nombre
        FROM juegos j
        JOIN torneos_info t ON t.id = j.torneo_id
        LEFT JOIN sedes s ON s.id = j.sede_id
        ORDER BY j.fecha ASC, j.hora ASC
    ");
    $todosJuegos = $stmt->fetchAll();
    foreach ($todosJuegos as $j) {
        $tid = (int) $j['torneo_id'];
        if (!isset($juegosPorTorneo[$tid])) {
            $juegosPorTorneo[$tid] = ['nombre_torneo' => $j['nombre_torneo'], 'temporada' => $j['temporada'], 'juegos' => []];
        }
        $juegosPorTorneo[$tid]['juegos'][] = $j;
    }
} catch (PDOException $e) {
    error_log('Juegos query: ' . $e->getMessage());
}

try {
    $nowHouston = new DateTime('now', new DateTimeZone('America/Chicago'));
    $today = $nowHouston->format('Y-m-d');
    $timeNow = $nowHouston->format('H:i:s');
    $stmt = $pdo->prepare("
        SELECT j.id, j.fecha, j.hora, j.rival, j.cancha, j.sede_id, t.nombre_torneo, t.temporada, s.nombre AS sede_nombre
        FROM juegos j
        JOIN torneos_info t ON t.id = j.torneo_id
        LEFT JOIN sedes s ON s.id = j.sede_id
        WHERE (j.fecha > ?) OR (j.fecha = ? AND (j.hora IS NULL OR j.hora > ?))
        ORDER BY j.fecha ASC, j.hora ASC
        LIMIT 2
    ");
    $stmt->execute([$today, $today, $timeNow]);
    $proximosJuegos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($proximosJuegos as $i => $j) {
        $ts = strtotime($j['fecha'] . ' ' . ($j['hora'] ?? '12:00:00'));
        if ($ts <= time()) {
            unset($proximosJuegos[$i]);
        }
    }
    $proximosJuegos = array_values($proximosJuegos);
    $proximoJuego = $proximosJuegos[0] ?? null;
} catch (PDOException $e) {
    error_log('Proximo juego query: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Proximo juego datetime: ' . $e->getMessage());
}

$ultimosResultados = [];
try {
    $stmtHouston = new DateTime('now', new DateTimeZone('America/Chicago'));
    $todayRes = $stmtHouston->format('Y-m-d');
    $timeNowRes = $stmtHouston->format('H:i:s');
    $stmt = $pdo->prepare("
        SELECT j.id, j.fecha, j.hora, j.rival, j.cancha, j.goles_vcf, j.goles_rival, j.rival_logo_url, s.nombre AS sede_nombre
        FROM juegos j
        LEFT JOIN sedes s ON s.id = j.sede_id
        WHERE (j.fecha < ?) OR (j.fecha = ? AND (j.hora IS NOT NULL AND j.hora < ?))
        ORDER BY j.fecha DESC, j.hora DESC
        LIMIT 3
    ");
    $stmt->execute([$todayRes, $todayRes, $timeNowRes]);
    $ultimosResultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Ultimos resultados query: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Ultimos resultados datetime: ' . $e->getMessage());
}

$seasonStats = ['W' => 0, 'L' => 0, 'D' => 0, 'GF' => 0, 'GA' => 0, 'PTS' => 0, 'played' => 0, 'CS' => 0, 'on_fire' => false];
$partidosConScore = [];
try {
    $stmt = $pdo->query("
        SELECT id, fecha, hora, goles_vcf, goles_rival
        FROM juegos
        WHERE goles_vcf IS NOT NULL AND goles_rival IS NOT NULL
        ORDER BY fecha ASC, hora ASC
    ");
    $partidosConScore = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $idsConScore = array_column($partidosConScore, 'id');
    foreach ($partidosConScore as $p) {
        $gv = (int) $p['goles_vcf'];
        $gr = (int) $p['goles_rival'];
        $seasonStats['played']++;
        $seasonStats['GA'] += $gr;
        if ($gv > $gr) {
            $seasonStats['W']++;
            $seasonStats['PTS'] += 3;
        } elseif ($gv < $gr) {
            $seasonStats['L']++;
        } else {
            $seasonStats['D']++;
            $seasonStats['PTS'] += 1;
        }
        if ($gr === 0) {
            $seasonStats['CS']++;
        }
    }
    // GF from aggregate (scorers) when available, else from goles_vcf
    if (count($idsConScore) > 0) {
        $placeholders = implode(',', array_fill(0, count($idsConScore), '?'));
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(goles), 0) FROM juego_goles WHERE juego_id IN ($placeholders)");
        $stmt->execute($idsConScore);
        $gfFromScorers = (int) $stmt->fetchColumn();
        $seasonStats['GF'] = $gfFromScorers > 0 ? $gfFromScorers : array_sum(array_column($partidosConScore, 'goles_vcf'));
    } else {
        $seasonStats['GF'] = 0;
    }
    $seasonStats['GD'] = $seasonStats['GF'] - $seasonStats['GA'];
    $seasonStats['PPG'] = $seasonStats['played'] > 0
        ? round($seasonStats['PTS'] / $seasonStats['played'], 2)
        : null;
    $lastThree = array_slice(array_reverse($partidosConScore), 0, 3);
    if (count($lastThree) === 3) {
        $wins = 0;
        foreach ($lastThree as $p) {
            if ((int) $p['goles_vcf'] > (int) $p['goles_rival']) $wins++;
        }
        $seasonStats['on_fire'] = ($wins === 3);
    }
} catch (PDOException $e) {
    error_log('Season stats query: ' . $e->getMessage());
}

$jugadorMes = null;
try {
    $st = $pdo->query("SHOW COLUMNS FROM jugador_mes LIKE 'dorsal'");
    $hasStarDorsal = $st && $st->fetch();
    $jmCols = $hasStarDorsal ? 'id, nombre, categoria, dorsal, foto_url, descripcion_logro, mes' : 'id, nombre, categoria, foto_url, descripcion_logro, mes';
    $stmt = $pdo->query("SELECT $jmCols FROM jugador_mes ORDER BY created_at DESC LIMIT 1");
    $jugadorMes = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Jugador mes query: ' . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT id, nombre, horarios_entrenamiento FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Categorias query: ' . $e->getMessage());
}

$rosterPorCategoria = [];
$topScorers = [];
$rosterHasSubPosicion = false;
try {
    $st = $pdo->query("SHOW COLUMNS FROM roster LIKE 'sub_posicion'");
    $rosterHasSubPosicion = $st && $st->fetch();
} catch (PDOException $e) {
}
$rosterSelectCols = 'r.id, r.nombre, r.apellido, r.dorsal, r.posicion, r.foto_url, r.categoria_id, c.nombre AS categoria_nombre';
if ($rosterHasSubPosicion) {
    $rosterSelectCols = 'r.id, r.nombre, r.apellido, r.dorsal, r.posicion, r.sub_posicion, r.foto_url, r.categoria_id, c.nombre AS categoria_nombre';
}
try {
    $stmt = $pdo->query("
        SELECT $rosterSelectCols,
               (SELECT COALESCE(SUM(goles), 0) FROM juego_goles WHERE roster_id = r.id) AS total_goles
        FROM roster r
        JOIN categorias c ON c.id = r.categoria_id
        WHERE r.activo = 1
        ORDER BY c.nombre ASC, total_goles DESC, r.dorsal ASC, r.apellido ASC
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cid = (int) $row['categoria_id'];
        if (!isset($rosterPorCategoria[$cid])) {
            $rosterPorCategoria[$cid] = ['nombre' => $row['categoria_nombre'], 'jugadores' => []];
        }
        $rosterPorCategoria[$cid]['jugadores'][] = $row;
    }
    // Pichichi / Top Scorers (aggregate from juego_goles)
    $stmt = $pdo->query("
        SELECT r.id, r.nombre, r.apellido, r.dorsal, c.nombre AS categoria_nombre, SUM(jg.goles) AS goles
        FROM juego_goles jg
        JOIN roster r ON r.id = jg.roster_id
        JOIN categorias c ON c.id = r.categoria_id
        WHERE r.activo = 1
        GROUP BY r.id, r.nombre, r.apellido, r.dorsal, c.nombre
        HAVING goles > 0
        ORDER BY goles DESC, r.apellido ASC
        LIMIT 10
    ");
    $topScorers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Roster query: ' . $e->getMessage());
}

$motmOpen = null;
$motmWinner = null;
try {
    $now = date('Y-m-d H:i:s');
    $stmtExp = $pdo->prepare("SELECT id FROM motm_votaciones WHERE status = 'open' AND ends_at <= ?");
    $stmtExp->execute([$now]);
    $expired = $stmtExp->fetchAll(PDO::FETCH_COLUMN);
    foreach ($expired as $vid) {
        $vid = (int) $vid;
        $top = $pdo->prepare("SELECT nominee_id FROM motm_votes WHERE votacion_id = ? GROUP BY nominee_id ORDER BY COUNT(*) DESC, nominee_id ASC LIMIT 1");
        $top->execute([$vid]);
        $winner_nominee_id = $top->fetchColumn();
        if ($winner_nominee_id) {
            $pdo->prepare("UPDATE motm_votaciones SET status = 'closed', winner_nominee_id = ? WHERE id = ?")->execute([$winner_nominee_id, $vid]);
        } else {
            $pdo->prepare("UPDATE motm_votaciones SET status = 'closed' WHERE id = ?")->execute([$vid]);
        }
    }

    $stmt = $pdo->prepare("SELECT v.id, v.ends_at, v.juego_id, j.fecha, j.rival FROM motm_votaciones v JOIN juegos j ON j.id = v.juego_id WHERE v.status = 'open' AND v.ends_at > ? ORDER BY v.ends_at ASC LIMIT 1");
    $stmt->execute([$now]);
    $motmOpen = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($motmOpen) {
        $stmt2 = $pdo->prepare("SELECT id, nombre, foto_url, orden FROM motm_nominees WHERE votacion_id = ? ORDER BY orden ASC");
        $stmt2->execute([$motmOpen['id']]);
        $motmOpen['nominees'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    if (!$motmOpen) {
        $stmt = $pdo->query("SELECT v.id, v.winner_nominee_id, n.nombre AS winner_nombre, n.foto_url AS winner_foto FROM motm_votaciones v LEFT JOIN motm_nominees n ON n.id = v.winner_nominee_id WHERE v.status = 'closed' AND v.winner_nominee_id IS NOT NULL ORDER BY v.ends_at DESC LIMIT 1");
        $motmWinner = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($motmWinner) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM motm_votes WHERE votacion_id = ?");
            $st->execute([$motmWinner['id']]);
            $motmWinner['total_votes'] = (int) $st->fetchColumn();
            $st = $pdo->prepare("SELECT COUNT(*) FROM motm_votes WHERE votacion_id = ? AND nominee_id = ?");
            $st->execute([$motmWinner['id'], $motmWinner['winner_nominee_id']]);
            $motmWinner['winner_votes'] = (int) $st->fetchColumn();
            $motmWinner['winner_pct'] = $motmWinner['total_votes'] > 0 ? round($motmWinner['winner_votes'] / $motmWinner['total_votes'] * 100, 1) : 0;
        }
    }
} catch (PDOException $e) {
    error_log('MOTM query: ' . $e->getMessage());
}

$hero_video_path = __DIR__ . '/assets/video/hero.mp4';
$hero_video_exists = file_exists($hero_video_path);

$heroSlides = [];
try {
    $heroSlides = $pdo->query("SELECT id, image_url, title, button_text, button_url FROM hero_slides WHERE activo = 1 ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // tabla puede no existir aún
}
$vcf_crest_file = null;
if (file_exists(__DIR__ . '/assets/img/vcf-crest.svg')) {
    $vcf_crest_file = 'vcf-crest.svg';
} elseif (file_exists(__DIR__ . '/assets/img/vcf-crest.png')) {
    $vcf_crest_file = 'vcf-crest.png';
} elseif (file_exists(__DIR__ . '/assets/img/vfc-crest.svg')) {
    $vcf_crest_file = 'vfc-crest.svg';
}

$preload_image_path = null;
$hero_mobile_path = null;
if (count($heroSlides) > 0 && !empty($heroSlides[0]['image_url'])) {
    $preload_image_path = $heroSlides[0]['image_url'];
} elseif (file_exists(__DIR__ . '/assets/img/hero.jpg')) {
    $preload_image_path = 'assets/img/hero.jpg';
    if (file_exists(__DIR__ . '/assets/img/hero-mobile.jpg')) {
        $hero_mobile_path = 'assets/img/hero-mobile.jpg';
    }
}

$page_title = 'VCF Academy Houston';
require __DIR__ . '/includes/header.php';
?>

<?php if (count($heroSlides) > 0): ?>
<section id="hero" class="vcf-hero-slider-section" aria-label="Hero slider">
    <div class="swiper vcf-hero-swiper">
        <div class="swiper-wrapper">
            <?php foreach ($heroSlides as $slide): ?>
            <div class="swiper-slide vcf-hero-slide">
                <div class="vcf-hero-slide-bg" style="background-image: url('<?= htmlspecialchars($base ?? '') ?>/<?= htmlspecialchars($slide['image_url']) ?>');"></div>
                <div class="vcf-hero-slide-overlay"></div>
                <div class="vcf-hero-slide-content">
                    <h2 class="vcf-hero-slide-title"><?= htmlspecialchars($slide['title']) ?></h2>
                    <?php if (!empty($slide['button_url'])): ?>
                    <a href="<?= htmlspecialchars($slide['button_url']) ?>" class="vcf-btn-cta vcf-hero-slide-btn" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($slide['button_text'] ?? 'Read More') ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="swiper-button-prev vcf-hero-arrow" aria-label="Previous"></button>
        <button type="button" class="swiper-button-next vcf-hero-arrow" aria-label="Next"></button>
        <div class="swiper-pagination vcf-hero-pagination"></div>
        <div class="vcf-hero-progress" aria-hidden="true"><span class="vcf-hero-progress-bar"></span></div>
    </div>
</section>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<?php else: ?>
<?php if (!empty($hero_mobile_path)): ?>
<style>@media (max-width: 768px) { .vcf-hero { background-image: url('<?= $base ?? '' ?>/<?= htmlspecialchars($hero_mobile_path) ?>'), linear-gradient(rgba(13, 13, 13, 0.75), rgba(13, 13, 13, 0.88)); } }</style>
<?php endif; ?>
<section id="hero" class="vcf-hero">
    <div class="container vcf-hero-inner">
        <div class="vcf-hero-content">
            <h1>The Sentiment Arrives in Houston.</h1>
            <p class="lead">Training the next generation of soccer stars under the world-renowned Valencia CF Methodology.</p>
            <a href="<?= $base ?? '' ?>/join.php" class="vcf-btn-cta">Join the Academy</a>
        </div>
        <?php if ($hero_video_exists): ?>
        <div class="vcf-hero-video-wrap">
            <video class="vcf-hero-video" autoplay muted loop playsinline>
                <source src="<?= $base ?? '' ?>/assets/video/hero.mp4" type="video/mp4">
            </video>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (count($proximosJuegos) > 0): ?>
<section class="vcf-nextmatch-strip" aria-label="Upcoming matches countdown">
    <div class="container">
        <div class="vcf-nextmatch-grid<?= count($proximosJuegos) === 1 ? ' vcf-nextmatch-grid--single' : '' ?>" style="display: flex; flex-direction: row; flex-wrap: nowrap; width: 100%; gap: 1rem;">
            <?php foreach ($proximosJuegos as $idx => $juego): ?>
            <?php $gameTs = strtotime($juego['fecha'] . ' ' . ($juego['hora'] ?? '12:00:00')); ?>
            <div class="vcf-nextmatch-item" style="flex: 1 1 0%; min-width: 0;">
                <p class="vcf-nextmatch-label"><?= $idx === 0 ? 'NEXT MATCH' : 'UPCOMING' ?>: <?= !empty($juego['rival']) ? htmlspecialchars($juego['rival']) : 'TBD' ?> — Starts in:</p>
                <div class="vcf-countdown-wrap" data-countdown-iso="<?= date('c', $gameTs) ?>" data-countdown-unix="<?= $gameTs ?>" data-countdown-target="<?= date('M j, Y g:i A', $gameTs) ?> CST">
                    <div class="vcf-countdown" aria-live="polite">
                        <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-days>0</span> <span class="vcf-countdown-unit">Days</span></span>
                        <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-hours>0</span> <span class="vcf-countdown-unit">Hours</span></span>
                        <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-minutes>0</span> <span class="vcf-countdown-unit">Min</span></span>
                        <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-seconds>0</span> <span class="vcf-countdown-unit">Sec</span></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php else: ?>
<section class="vcf-nextmatch-strip" aria-label="Next match placeholder">
    <div class="container d-flex flex-wrap align-items-center justify-content-center gap-3 py-3">
        <i class="fas fa-futbol me-2" style="color: var(--vcf-orange); font-size: 1.5rem;" aria-hidden="true"></i>
        <p class="mb-0 text-center" style="color: var(--vcf-white); font-weight: 600; font-size: 1.1rem;">Next Match Coming Soon</p>
    </div>
</section>
<?php endif; ?>

<?php if ($motmOpen || $motmWinner): ?>
<section id="motm" class="vcf-section motm-section">
    <div class="container">
        <?php if ($motmOpen): ?>
            <?php
            $motmEndTs = strtotime($motmOpen['ends_at']);
            $motmEndIso = date('c', $motmEndTs);
            ?>
            <h2 class="vcf-section-title">Man of the Match</h2>
            <p class="vcf-section-desc">Vote for the player of the match. Voting closes when the timer below reaches zero. One vote per person.</p>
            <div class="vcf-countdown-wrap motm-countdown-wrap mb-3" data-countdown-iso="<?= $motmEndIso ?>" data-countdown-unix="<?= $motmEndTs ?>">
                <div class="vcf-countdown" aria-live="polite">
                    <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-days>0</span> <span class="vcf-countdown-unit">Days</span></span>
                    <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-hours>0</span> <span class="vcf-countdown-unit">Hours</span></span>
                    <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-minutes>0</span> <span class="vcf-countdown-unit">Min</span></span>
                    <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-seconds>0</span> <span class="vcf-countdown-unit">Sec</span></span>
                </div>
            </div>
            <div class="row g-4 motm-nominees" data-votacion-id="<?= (int) $motmOpen['id'] ?>" data-vote-url="<?= htmlspecialchars($base ?? '') ?>/api/motm-vote.php">
                <?php foreach ($motmOpen['nominees'] as $nom): ?>
                <div class="col-md-4">
                    <div class="motm-card">
                        <?php if (!empty($nom['foto_url'])): ?>
                            <img src="<?= htmlspecialchars($base ?? '') ?>/<?= htmlspecialchars($nom['foto_url']) ?>" alt="" class="motm-card-photo">
                        <?php else: ?>
                            <div class="motm-card-photo motm-card-photo-placeholder"><i class="fas fa-user" aria-hidden="true"></i></div>
                        <?php endif; ?>
                        <p class="motm-card-name"><?= htmlspecialchars($nom['nombre']) ?></p>
                        <button type="button" class="btn vcf-btn-cta motm-vote-btn" data-nominee-id="<?= (int) $nom['id'] ?>">Vote</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="text-muted small mt-2 mb-0">One vote per device. Results will be shown when voting ends.</p>
        <?php elseif ($motmWinner): ?>
            <h2 class="vcf-section-title">Man of the Match</h2>
            <div class="motm-winner-card">
                <?php if (!empty($motmWinner['winner_foto'])): ?>
                    <img src="<?= htmlspecialchars($base ?? '') ?>/<?= htmlspecialchars($motmWinner['winner_foto']) ?>" alt="" class="motm-winner-photo">
                <?php else: ?>
                    <div class="motm-winner-photo motm-winner-photo-placeholder"><i class="fas fa-trophy" aria-hidden="true"></i></div>
                <?php endif; ?>
                <div class="motm-winner-body">
                    <p class="motm-winner-label">MAN OF THE MATCH</p>
                    <h3 class="motm-winner-name"><?= htmlspecialchars($motmWinner['winner_nombre']) ?></h3>
                    <p class="motm-winner-pct"><?= (int) $motmWinner['winner_votes'] ?> votes (<?= (float) $motmWinner['winner_pct'] ?>%)</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<section id="methodology" class="vcf-section">
    <div class="container">
        <h2 class="vcf-section-title">Our Methodology: <span class="vcf-accent">"Educating People, Training Footballers"</span></h2>
        <p class="vcf-section-desc">In Houston, we don't just play soccer; we live it. Our program follows the official VCF Academy pillars: Identity, Effort, and Intelligence. We focus on technical excellence and tactical awareness, ensuring every child understands the game while developing the values of teamwork and respect that define the Valencia CF spirit.</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="vcf-methodology-card">
                    <i class="fas fa-fingerprint"></i>
                    <h3>Identity</h3>
                    <p>Building the Valencia CF identity in every young player.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="vcf-methodology-card">
                    <i class="fas fa-dumbbell"></i>
                    <h3>Effort</h3>
                    <p>Hard work and dedication in every training session.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="vcf-methodology-card">
                    <i class="fas fa-brain"></i>
                    <h3>Intelligence</h3>
                    <p>Tactical awareness and decision-making on the pitch.</p>
                </div>
            </div>
        </div>
        <?php if (count($categorias) > 0): ?>
        <div class="mt-5">
            <h3 class="vcf-section-title mb-3">Training Schedules by Category</h3>
            <div class="row g-3">
                <?php foreach ($categorias as $cat): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="vcf-sede-card p-3 text-center">
                        <h4 class="mb-1" style="color: var(--vcf-orange); font-size: 1.25rem;"><?= htmlspecialchars($cat['nombre']) ?></h4>
                        <p class="mb-0 small text-muted"><?= htmlspecialchars($cat['horarios_entrenamiento'] ?? 'Schedule TBD') ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-5">
            <h3 class="vcf-section-title mb-3">Know Your Role</h3>
            <p class="vcf-section-desc mb-4">Tap each position on the pitch to learn the role and typical plays. Formation 4-3-3. VCF Academy methodology.</p>
            <div class="vcf-pitch-wrap">
                <div class="vcf-pitch" id="vcfInteractivePitch" aria-label="Interactive field positions (4-3-3)">
                    <div class="vcf-pitch-lines"></div>
                    <!-- 1 Portero -->
                    <button type="button" class="vcf-position-point vcf-pos-gk" data-role="goalkeeper" aria-label="Goalkeeper 1"><span class="vcf-position-point-dot"><i class="fas fa-hand-paper" aria-hidden="true"></i></span></button>
                    <!-- 4 Defensas -->
                    <button type="button" class="vcf-position-point vcf-pos-lb" data-role="lateral_izq" aria-label="Left back 2"><span class="vcf-position-point-dot"><i class="fas fa-shield-alt" aria-hidden="true"></i></span></button>
                    <button type="button" class="vcf-position-point vcf-pos-cbl" data-role="central_izq" aria-label="Left centre-back 3"><span class="vcf-position-point-dot"><i class="fas fa-shield-alt" aria-hidden="true"></i></span></button>
                    <button type="button" class="vcf-position-point vcf-pos-cbr" data-role="central_der" aria-label="Right centre-back 4"><span class="vcf-position-point-dot"><i class="fas fa-shield-alt" aria-hidden="true"></i></span></button>
                    <button type="button" class="vcf-position-point vcf-pos-rb" data-role="lateral_der" aria-label="Right back 5"><span class="vcf-position-point-dot"><i class="fas fa-shield-alt" aria-hidden="true"></i></span></button>
                    <!-- 3 Medios -->
                    <button type="button" class="vcf-position-point vcf-pos-dm" data-role="pivote" aria-label="Defensive mid 6"><span class="vcf-position-point-dot"><i class="fas fa-circle-notch" aria-hidden="true"></i></span></button>
                    <button type="button" class="vcf-position-point vcf-pos-ml" data-role="interior_izq" aria-label="Left midfielder 7"><span class="vcf-position-point-dot"><i class="fas fa-futbol" aria-hidden="true"></i></span></button>
                    <button type="button" class="vcf-position-point vcf-pos-mr" data-role="interior_der" aria-label="Right midfielder 8"><span class="vcf-position-point-dot"><i class="fas fa-futbol" aria-hidden="true"></i></span></button>
                    <!-- 3 Delanteros -->
                    <button type="button" class="vcf-position-point vcf-pos-wl" data-role="extremo_izq" aria-label="Left winger 9"><span class="vcf-position-point-dot"><i class="fas fa-bolt" aria-hidden="true"></i></span></button>
                    <button type="button" class="vcf-position-point vcf-pos-st" data-role="nueve" aria-label="Striker 10"><span class="vcf-position-point-dot"><i class="fas fa-bolt" aria-hidden="true"></i></span></button>
                    <button type="button" class="vcf-position-point vcf-pos-wr" data-role="extremo_der" aria-label="Right winger 11"><span class="vcf-position-point-dot"><i class="fas fa-bolt" aria-hidden="true"></i></span></button>
                    <div class="vcf-position-popover" id="vcfPositionPopover" role="dialog" aria-live="polite" hidden>
                        <h4 class="vcf-position-popover-title" id="vcfPositionPopoverTitle"></h4>
                        <p class="vcf-position-popover-desc" id="vcfPositionPopoverDesc"></p>
                        <p class="vcf-position-popover-pro small mb-1" id="vcfPositionPopoverPro"></p>
                        <p class="vcf-position-popover-jugada small mb-0" id="vcfPositionPopoverJugada"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="grounds" class="vcf-section">
    <div class="container my-5">
        <h2 class="vcf-section-title vcf-section-title-line">Training Grounds</h2>
        <p class="vcf-section-desc">We bring the Mestalla experience to your neighborhood. Find our official training locations across the Houston area, equipped with top-tier facilities for our youth categories.</p>
        <p class="vcf-section-desc mb-4">Houston is big, but we make it easy to find us. Check the specific field number for your kid's category below.</p>
        <?php if (count($sedes) > 0): ?>
        <div class="accordion" id="accordionSedes">
            <?php foreach ($sedes as $i => $sede): ?>
            <?php
                $sid = (int) $sede['id'];
                $canchas = $canchasBySede[$sid] ?? [];
                $accordionId = 'sede-' . $sid;
                $isFirst = ($i === 0);
            ?>
            <div class="accordion-item mb-3">
                <h2 class="accordion-header vcf-accordion-header">
                    <button class="accordion-button <?= $isFirst ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $accordionId ?>" aria-expanded="<?= $isFirst ? 'true' : 'false' ?>" aria-controls="<?= $accordionId ?>">
                        <strong class="text-uppercase"><?= htmlspecialchars($sede['nombre']) ?></strong>
                        <span class="ms-3 vcf-accordion-address">— <?= htmlspecialchars($sede['direccion']) ?></span>
                    </button>
                    <?php if (!empty($sede['mapa_general_url'])): ?>
                    <a href="<?= htmlspecialchars($sede['mapa_general_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm vcf-btn-gps vcf-accordion-gps">Open GPS</a>
                    <?php endif; ?>
                </h2>
                <div id="<?= $accordionId ?>" class="accordion-collapse collapse <?= $isFirst ? 'show' : '' ?>" data-bs-parent="#accordionSedes">
                    <div class="accordion-body vcf-accordion-body">
                        <?php if (!empty($sede['nota_acceso'])): ?>
                        <p class="text-muted mb-3"><i class="fas fa-info-circle me-2" style="color: var(--vcf-orange);"></i><?= htmlspecialchars($sede['nota_acceso']) ?></p>
                        <?php endif; ?>
                        <?php if (count($canchas) > 0): ?>
                        <ul class="list-group list-group-flush vcf-list-canchas">
                            <?php foreach ($canchas as $c): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center vcf-cancha-item">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-futbol me-2 vcf-cancha-icon" aria-hidden="true"></i>
                                    <div>
                                        <strong><?= htmlspecialchars($c['numero_cancha']) ?><?= !empty($c['sobrenombre']) ? ' <span class="vcf-sobrenombre">(' . htmlspecialchars($c['sobrenombre']) . ')</span>' : '' ?></strong>
                                        <?php if (!empty($c['indicaciones_extra'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($c['indicaciones_extra']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($c['mapa_url'])): ?>
                                <a href="<?= htmlspecialchars($c['mapa_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm vcf-btn-gps">Open GPS</a>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-muted small mb-0"><i class="fas fa-info-circle me-2" style="color: var(--vcf-orange);"></i>No specific fields listed yet. Use the main entrance GPS above.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="vcf-empty-state">
            <i class="fas fa-map-marker-alt vcf-empty-state-icon" aria-hidden="true"></i>
            <p>Training locations will be listed here soon.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if (count($rosterPorCategoria) > 0): ?>
<section id="roster" class="vcf-section" data-base-url="<?= htmlspecialchars($base ?? '') ?>" data-player-api="<?= htmlspecialchars(($base ?? '') . '/api/roster-player.php') ?>" data-crest-url="<?= htmlspecialchars(($base ?? '') . '/assets/img/' . ($vcf_crest_file ?? 'vcf-crest.svg')) ?>">
    <div class="container">
        <h2 class="vcf-section-title">Roster / Plantilla</h2>
        <p class="vcf-section-desc">Click on a player to see their stats. Our players by category. VCF Academy Houston.</p>
        <?php foreach ($rosterPorCategoria as $catId => $catData): ?>
        <h3 class="vcf-torneo-title mt-4 mb-3"><?= htmlspecialchars($catData['nombre']) ?></h3>
        <div class="row g-3 mb-4">
            <?php foreach ($catData['jugadores'] as $j): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="roster-card roster-card-clickable" role="button" tabindex="0" data-roster-id="<?= (int) $j['id'] ?>" aria-label="View <?= htmlspecialchars($j['nombre'] . ' ' . $j['apellido']) ?> stats">
                    <div class="roster-photo-wrap">
                        <?php if (!empty($j['foto_url'])): ?>
                            <img src="<?= htmlspecialchars($base ?? '') ?>/<?= htmlspecialchars($j['foto_url']) ?>" alt="" class="roster-photo">
                        <?php else: ?>
                            <div class="roster-photo roster-photo-placeholder"><span class="roster-initials"><?= mb_substr($j['nombre'], 0, 1) ?><?= mb_substr($j['apellido'], 0, 1) ?></span></div>
                        <?php endif; ?>
                        <?php if ($j['dorsal'] !== null): ?>
                            <span class="roster-dorsal" aria-hidden="true"><?= (int) $j['dorsal'] ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="roster-name"><?= htmlspecialchars($j['nombre'] . ' ' . $j['apellido']) ?></p>
                    <?php
                        $posToRole = ['Portero' => 'goalkeeper', 'Defensa' => 'defense', 'Mediocampista' => 'midfield', 'Delantero' => 'forward'];
                        $subPosToRole = ['Portero' => 'goalkeeper', 'Lateral izquierdo' => 'lateral_izq', 'Central izquierdo' => 'central_izq', 'Central derecho' => 'central_der', 'Lateral derecho' => 'lateral_der', 'Pivote' => 'pivote', 'Interior izquierdo' => 'interior_izq', 'Interior derecho' => 'interior_der', 'Extremo izquierdo' => 'extremo_izq', 'Delantero centro (9)' => 'nueve', 'Extremo derecho' => 'extremo_der'];
                        $posLabel = $j['posicion'] ?? '';
                        $subPos = $j['sub_posicion'] ?? '';
                        if ($subPos) $posLabel = $posLabel ? $posLabel . ' · ' . $subPos : $subPos;
                        $roleKey = ($subPos && isset($subPosToRole[$subPos])) ? $subPosToRole[$subPos] : ($posToRole[$j['posicion'] ?? ''] ?? '');
                    if ($posLabel): ?>
                    <p class="roster-pos small mb-0 d-flex align-items-center justify-content-center gap-1 flex-wrap">
                        <span><?= htmlspecialchars($posLabel) ?></span>
                        <?php if ($roleKey): ?>
                        <span class="roster-pos-info" data-role="<?= htmlspecialchars($roleKey) ?>" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-trigger="hover focus" title="" role="button" tabindex="0" aria-label="Role description"><i class="bi bi-info-circle" aria-hidden="true"></i></span>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="modal fade player-modal" id="playerCardModal" tabindex="-1" aria-labelledby="playerCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content player-modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="player-modal-photo-wrap">
                            <img id="playerModalPhoto" src="" alt="" class="player-modal-photo">
                            <div id="playerModalPhotoPlaceholder" class="player-modal-photo-placeholder d-none"><span id="playerModalInitials"></span></div>
                            <div class="player-modal-watermark" aria-hidden="true"><img id="playerModalCrest" src="" alt="" style="width:80px;height:80px;opacity:0.15;pointer-events:none;"></div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h2 id="playerModalName" class="player-modal-name mb-1"></h2>
                        <p id="playerModalMeta" class="player-modal-meta small mb-3"></p>
                        <div class="player-modal-stats">
                            <div class="player-stat-row"><span class="player-stat-label">Apps</span><span class="player-stat-value" id="statApps">0</span><div class="player-stat-bar"><div class="player-stat-fill" id="barApps" style="width:0%"></div></div></div>
                            <div class="player-stat-row"><span class="player-stat-label">Goals</span><span class="player-stat-value" id="statGoals">0</span><div class="player-stat-bar"><div class="player-stat-fill" id="barGoals" style="width:0%"></div></div></div>
                            <div class="player-stat-row"><span class="player-stat-label">Assists</span><span class="player-stat-value" id="statAssists">0</span><div class="player-stat-bar"><div class="player-stat-fill" id="barAssists" style="width:0%"></div></div></div>
                            <div class="player-stat-row"><span class="player-stat-label">MOTM</span><span class="player-stat-value" id="statMotm">0</span><div class="player-stat-bar"><div class="player-stat-fill" id="barMotm" style="width:0%"></div></div></div>
                            <div class="player-stat-row"><span class="player-stat-label">Clean Sheets</span><span class="player-stat-value" id="statCS">0</span><div class="player-stat-bar"><div class="player-stat-fill" id="barCS" style="width:0%"></div></div></div>
                        </div>
                        <div id="playerModalRadarWrap" class="player-modal-radar-wrap mt-3 d-none">
                            <p class="small text-muted mb-1">Skills (1–10)</p>
                            <svg id="playerModalRadar" class="player-modal-radar" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <polygon id="playerRadarGrid" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="0.5" points="50,10 77,27.5 77,72.5 50,90 23,72.5 23,27.5"/>
                                <polygon id="playerRadarGrid2" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.3" points="50,22 65.5,31.25 65.5,68.75 50,78 34.5,68.75 34.5,31.25"/>
                                <line x1="50" y1="50" x2="50" y2="10" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <line x1="50" y1="50" x2="77" y2="27.5" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <line x1="50" y1="50" x2="77" y2="72.5" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <line x1="50" y1="50" x2="23" y2="72.5" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <line x1="50" y1="50" x2="23" y2="27.5" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <line x1="50" y1="50" x2="50" y2="90" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <polygon id="playerRadarPolygon" fill="rgba(255,102,0,0.35)" stroke="rgba(255,102,0,0.9)" stroke-width="1" points=""/>
                                <text x="50" y="8" text-anchor="middle" class="player-radar-label" fill="rgba(255,255,255,0.7)" font-size="5">Pace</text>
                                <text x="80" y="28" text-anchor="middle" class="player-radar-label" fill="rgba(255,255,255,0.7)" font-size="5">Shoot</text>
                                <text x="80" y="74" text-anchor="middle" class="player-radar-label" fill="rgba(255,255,255,0.7)" font-size="5">Drib</text>
                                <text x="50" y="93" text-anchor="middle" class="player-radar-label" fill="rgba(255,255,255,0.7)" font-size="5">Def</text>
                                <text x="20" y="74" text-anchor="middle" class="player-radar-label" fill="rgba(255,255,255,0.7)" font-size="5">Phys</text>
                                <text x="20" y="28" text-anchor="middle" class="player-radar-label" fill="rgba(255,255,255,0.7)" font-size="5">Pass</text>
                            </svg>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-light btn-sm" id="playerModalShare"><i class="fas fa-share-alt me-1"></i> Share Player</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<section id="tournaments" class="vcf-section">
    <div class="container">
        <h2 class="vcf-section-title">Upcoming Tournaments & Matchday</h2>
        <p class="vcf-section-desc">Track our teams' progress as they compete in Houston's premier youth leagues. Check schedules, field locations, and results here.</p>

        <?php if (count($juegosPorTorneo) > 0): ?>
            <?php foreach ($juegosPorTorneo as $tid => $bloque): ?>
                <h3 class="vcf-torneo-title mt-4 mb-2"><?= htmlspecialchars($bloque['nombre_torneo']) ?><?= $bloque['temporada'] ? ' — ' . htmlspecialchars($bloque['temporada']) : '' ?></h3>
                <div class="vcf-table-wrap">
                    <table class="vcf-table">
                        <thead>
                            <tr>
                                <th>Day &amp; Date</th>
                                <th>Time</th>
                                <th>Opponent</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloque['juegos'] as $j): ?>
                                <?php
                                $lugar = ($j['sede_nombre'] && $j['cancha']) ? $j['sede_nombre'] . ' - ' . $j['cancha'] : ($j['cancha'] ?? $j['sede_nombre'] ?? '—');
                                $gameTs = strtotime($j['fecha'] . ' ' . (!empty($j['hora']) ? $j['hora'] : '23:59:59'));
                                $isPast = $gameTs < time();
                                if ($isPast) {
                                    $estado = 'finalizado';
                                    $canAddCalendar = false;
                                } else {
                                    $estado = $j['estado'] ?? 'proximo';
                                    $canAddCalendar = ($estado === 'proximo' || $estado === 'live');
                                }
                                ?>
                                <tr>
                                    <td><?= date('D, M j', strtotime($j['fecha'])) ?></td>
                                    <td><?= (!empty($j['hora'])) ? date('g:i A', strtotime($j['hora'])) : '—' ?></td>
                                    <td><?php if (!empty($j['rival'])): ?><span class="vcf-opponent"><?= htmlspecialchars($j['rival']) ?></span><?php else: ?>—<?php endif; ?></td>
                                    <td><?= htmlspecialchars($lugar) ?></td>
                                    <td>
                                        <?php if ($estado === 'live'): ?>
                                            <span class="vcf-badge-live">Live</span>
                                        <?php elseif ($estado === 'finalizado'): ?>
                                            <span class="vcf-badge-finalizado">Finished</span>
                                        <?php else: ?>
                                            <span class="vcf-badge-proximo">Upcoming</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($canAddCalendar): ?>
                                            <a href="<?= htmlspecialchars($base ?? '') ?>/calendar.php?id=<?= (int) $j['id'] ?>" class="vcf-btn-calendar" target="_blank" rel="noopener noreferrer" title="Add to my calendar">Add to calendar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="stats-container mt-5 mb-4">
            <h3 class="stats-title">
                SEASON STATS
                <?php if ($seasonStats['on_fire']): ?>
                    <span class="stats-on-fire"><i class="fas fa-fire" aria-hidden="true"></i> ON FIRE</span>
                <?php endif; ?>
            </h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">W</span>
                    <span class="stat-value"><?= $seasonStats['W'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">L</span>
                    <span class="stat-value"><?= $seasonStats['L'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">D</span>
                    <span class="stat-value"><?= $seasonStats['D'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">GF</span>
                    <span class="stat-value"><?= $seasonStats['GF'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">GA</span>
                    <span class="stat-value"><?= $seasonStats['GA'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">GD</span>
                    <span class="stat-value"><?= $seasonStats['GD'] >= 0 ? '+' . $seasonStats['GD'] : (string) $seasonStats['GD'] ?></span>
                </div>
                <div class="stat-item highlight">
                    <span class="stat-label">PTS</span>
                    <span class="stat-value"><?= $seasonStats['PTS'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">PPG</span>
                    <span class="stat-value"><?= $seasonStats['PPG'] !== null ? number_format($seasonStats['PPG'], 2) : '—' ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">CS</span>
                    <span class="stat-value"><?= $seasonStats['CS'] ?></span>
                </div>
            </div>
        </div>

        <?php if (count($topScorers) > 0): ?>
        <div class="mt-4 mb-4">
            <h3 class="stats-title">Top Scorers <span class="vcf-accent">(Pichichi)</span></h3>
            <div class="vcf-table-wrap">
                <table class="vcf-table table table-dark">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Player</th>
                            <th>Category</th>
                            <th class="text-center">Goals</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topScorers as $i => $ts): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($ts['nombre'] . ' ' . $ts['apellido']) ?><?= $ts['dorsal'] !== null ? ' #' . (int) $ts['dorsal'] : '' ?></td>
                            <td><?= htmlspecialchars($ts['categoria_nombre']) ?></td>
                            <td class="text-center"><strong><?= (int) $ts['goles'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($ultimosResultados) > 0): ?>
            <h3 class="vcf-torneo-title mt-5 mb-2">Latest Results</h3>
            <div class="vcf-table-wrap">
                <table class="vcf-table vcf-table-results">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Local</th>
                            <th>Score</th>
                            <th>Visitante</th>
                            <th>Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosResultados as $r): ?>
                            <?php
                            $gv = (int) ($r['goles_vcf'] ?? 0);
                            $gr = (int) ($r['goles_rival'] ?? 0);
                            $hasScore = (isset($r['goles_vcf']) && $r['goles_vcf'] !== null) || (isset($r['goles_rival']) && $r['goles_rival'] !== null);
                            if ($hasScore) {
                                if ($gv > $gr) {
                                    $resultOutcome = 'W';
                                    $vcfClass = 'vcf-result-winner';
                                    $rivalClass = 'vcf-result-loser';
                                    $scoreVcfClass = 'vcf-result-winner';
                                    $scoreRivalClass = 'vcf-result-loser';
                                } elseif ($gv < $gr) {
                                    $resultOutcome = 'L';
                                    $vcfClass = 'vcf-result-loser';
                                    $rivalClass = 'vcf-result-winner';
                                    $scoreVcfClass = 'vcf-result-loser';
                                    $scoreRivalClass = 'vcf-result-winner';
                                } else {
                                    $resultOutcome = 'D';
                                    $vcfClass = $rivalClass = 'vcf-result-draw';
                                    $scoreVcfClass = $scoreRivalClass = 'vcf-result-draw';
                                }
                            } else {
                                $resultOutcome = null;
                                $vcfClass = $rivalClass = $scoreVcfClass = $scoreRivalClass = 'vcf-result-draw';
                            }
                            $rivalName = !empty($r['rival']) ? htmlspecialchars($r['rival']) : '—';
                            $rivalLogoUrl = !empty($r['rival_logo_url']) ? $r['rival_logo_url'] : null;
                            ?>
                            <tr>
                                <td><?= date('M j', strtotime($r['fecha'])) ?></td>
                                <td>
                                    <span class="vcf-result-team">
                                        <?php if ($vcf_crest_file): ?>
                                            <img src="<?= $base ?? '' ?>/assets/img/<?= $vcf_crest_file ?>" alt="" class="vcf-team-logo" width="28" height="28">
                                        <?php else: ?>
                                            <span class="vcf-team-logo vcf-team-logo-placeholder" aria-hidden="true"><i class="fas fa-shield"></i></span>
                                        <?php endif; ?>
                                        <span class="<?= $vcfClass ?>">VCF Houston</span>
                                    </span>
                                </td>
                                <td class="vcf-score-cell">
                                    <?php if ($hasScore): ?>
                                        <span class="<?= $scoreVcfClass ?>"><?= $gv ?></span> – <span class="<?= $scoreRivalClass ?>"><?= $gr ?></span>
                                    <?php else: ?>
                                        <span class="vcf-result-draw">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="vcf-result-team">
                                        <?php if ($rivalLogoUrl): ?>
                                            <img src="<?= htmlspecialchars($rivalLogoUrl) ?>" alt="" class="vcf-team-logo" width="28" height="28" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                                            <span class="vcf-team-logo vcf-team-logo-placeholder" style="display:none;" aria-hidden="true"><i class="fas fa-shield"></i></span>
                                        <?php else: ?>
                                            <span class="vcf-team-logo vcf-team-logo-placeholder" aria-hidden="true"><i class="fas fa-shield"></i></span>
                                        <?php endif; ?>
                                        <span class="<?= $rivalClass ?>"><?= $rivalName ?></span>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($resultOutcome === 'W'): ?>
                                        <span class="vcf-badge-w">W</span>
                                    <?php elseif ($resultOutcome === 'L'): ?>
                                        <span class="vcf-badge-l">L</span>
                                    <?php elseif ($resultOutcome === 'D'): ?>
                                        <span class="vcf-badge-d">D</span>
                                    <?php else: ?>
                                        <span class="vcf-result-draw">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (count($juegosPorTorneo) === 0): ?>
            <div class="vcf-empty-state">
                <i class="fas fa-futbol vcf-empty-state-icon" aria-hidden="true"></i>
                <p>No matches scheduled yet.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section id="star" class="vcf-section">
    <div class="container">
        <h2 class="vcf-section-title">VCF Star of the Month</h2>
        <p class="vcf-section-desc">Recognizing hard work, discipline, and the VCF spirit.</p>
        <?php if ($jugadorMes): ?>
            <div class="vcf-star-card">
                <div class="photo-wrap">
                    <?php if (!empty($jugadorMes['foto_url'])): ?>
                        <img src="<?= htmlspecialchars(($base ?? '') ? rtrim($base, '/') . '/' . $jugadorMes['foto_url'] : $jugadorMes['foto_url']) ?>" alt="<?= htmlspecialchars($jugadorMes['nombre']) ?>">
                    <?php else: ?>
                        <img src="<?= $base ?? '' ?>/assets/img/star-default.svg" alt="VCF Star" class="star-default-img">
                    <?php endif; ?>
                    <?php if (!empty($jugadorMes['dorsal'])): ?>
                        <span class="star-dorsal" aria-hidden="true"><?= (int) $jugadorMes['dorsal'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h3><?= htmlspecialchars($jugadorMes['nombre']) ?></h3>
                    <p class="categoria"><?= htmlspecialchars($jugadorMes['categoria']) ?> · <?= htmlspecialchars($jugadorMes['mes']) ?></p>
                    <p class="descripcion"><?= nl2br(htmlspecialchars($jugadorMes['descripcion_logro'])) ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="vcf-empty-state">
                <i class="fas fa-trophy vcf-empty-state-icon" aria-hidden="true"></i>
                <p>Star of the Month coming soon.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (count($heroSlides) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
(function() {
    var el = document.querySelector('.vcf-hero-swiper');
    if (!el) return;
    var progressBar = el.querySelector('.vcf-hero-progress-bar');
    var autoplayDelay = 5000;
    var totalSlides = el.querySelectorAll('.swiper-slide').length;
    var swiper = new Swiper('.vcf-hero-swiper', {
        loop: true,
        speed: 600,
        autoplay: { delay: autoplayDelay, disableOnInteraction: false },
        pagination: {
            el: '.vcf-hero-pagination',
            clickable: true,
            type: 'custom',
            renderCustom: function(swiper, current, total) {
                var html = '';
                for (var i = 1; i <= total; i++) {
                    html += '<span class="swiper-pagination-bullet' + (i === current ? ' swiper-pagination-bullet-active' : '') + '" data-index="' + (i - 1) + '">' + i + '</span>';
                }
                return html;
            }
        },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        on: {
            init: function() { resetProgress(); runProgress(); },
            slideChangeTransitionStart: function() { resetProgress(); runProgress(); }
        }
    });
    el.querySelector('.vcf-hero-pagination').addEventListener('click', function(e) {
        var bullet = e.target.closest('.swiper-pagination-bullet');
        if (bullet && bullet.dataset.index !== undefined) swiper.slideToLoop(parseInt(bullet.dataset.index, 10));
    });
    swiper.on('slideChange', function() {
        var idx = swiper.realIndex + 1;
        el.querySelectorAll('.vcf-hero-pagination .swiper-pagination-bullet').forEach(function(b, i) {
            b.classList.toggle('swiper-pagination-bullet-active', i + 1 === idx);
        });
    });
    el.addEventListener('mouseenter', function() { swiper.autoplay.stop(); if (progressInterval) clearInterval(progressInterval); });
    el.addEventListener('mouseleave', function() { swiper.autoplay.start(); resetProgress(); runProgress(); });
    var progressInterval;
    function resetProgress() {
        if (progressBar) progressBar.style.width = '0%';
        if (progressInterval) clearInterval(progressInterval);
    }
    function runProgress() {
        if (!progressBar) return;
        var start = Date.now();
        progressInterval = setInterval(function() {
            var elapsed = Date.now() - start;
            var pct = Math.min(100, (elapsed / autoplayDelay) * 100);
            progressBar.style.width = pct + '%';
            if (pct >= 100) clearInterval(progressInterval);
        }, 50);
    }
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
