<?php
require __DIR__ . '/includes/page_cache.php';
// Aggressive TTL: home content changes in scale of hours, not seconds.
// Admins get an instant "Clear cache" button on the dashboard if they
// need fresh data right now.
if (vcf_page_cache_try_serve(300)) {
    exit;
}
require __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/vcf_foto_url.php';
vcf_page_cache_start(300);

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
        SELECT j.id, j.fecha, j.hora, j.rival, j.rival_logo_url, j.cancha, j.sede_id, t.nombre_torneo, t.temporada, s.nombre AS sede_nombre
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
        LIMIT 12
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
    if ($jugadorMes && !empty($jugadorMes['foto_url'])) {
        $jugadorMes['foto_url'] = vcf_normalize_foto_url($jugadorMes['foto_url']);
    }
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
               COALESCE(jg.total_goles, 0) AS total_goles
        FROM roster r
        JOIN categorias c ON c.id = r.categoria_id
        LEFT JOIN (
            SELECT roster_id, SUM(goles) AS total_goles
            FROM juego_goles
            GROUP BY roster_id
        ) jg ON jg.roster_id = r.id
        WHERE r.activo = 1
        ORDER BY c.nombre ASC, total_goles DESC, r.dorsal ASC, r.apellido ASC
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!empty($row['foto_url'])) {
            $row['foto_url'] = vcf_normalize_foto_url($row['foto_url']);
        }
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

$formationBase = rtrim(dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '')), '/');

/**
 * Normalize a single roster row into the compact shape the JS formation
 * viewer consumes. Includes `id` + `pos` (raw position) so the client side
 * can auto-assign across multiple formation systems and handle swaps.
 */
$fpRow = static function (array $j) use ($formationBase): array {
    $name = trim(($j['nombre'] ?? '') . ' ' . ($j['apellido'] ?? ''));
    $n1 = trim($j['nombre'] ?? '');
    $n2 = trim($j['apellido'] ?? '');
    if (function_exists('mb_substr')) {
        $ini = mb_strtoupper(mb_substr($n1, 0, 1)) . mb_strtoupper(mb_substr($n2, 0, 1));
    } else {
        $ini = strtoupper(substr($n1, 0, 1)) . strtoupper(substr($n2, 0, 1));
    }
    $photo = '';
    $fu = vcf_normalize_foto_url($j['foto_url'] ?? '');
    if ($fu !== '') {
        if (preg_match('#^https?://#i', $fu)) {
            $photo = $fu;
        } else {
            $photo = ($formationBase === '' ? '' : $formationBase) . '/' . ltrim($fu, '/');
        }
    }
    $dorsal = $j['dorsal'] ?? null;
    $num = ($dorsal !== null && $dorsal !== '') ? (string) (int) $dorsal : '';

    return [
        'id'       => (int) ($j['id'] ?? 0),
        'name'     => $name,
        'initials' => $ini !== '' ? $ini : '?',
        'num'      => $num,
        'photo'    => $photo,
        'pos'      => $j['posicion'] ?? '',
    ];
};

// Build pool: full list of available players, both for "All squad" and per
// category. The JS does the auto-assignment per formation and the bench
// computation, so PHP only needs to ship the raw payload (one player per
// roster row, with the fields above).
$formation_pool = ['all' => []];
$formation_categories = [];
foreach ($rosterPorCategoria as $cid => $cat) {
    if (empty($cat['jugadores'])) {
        continue;
    }
    $key = (string) $cid;
    $formation_pool[$key] = [];
    foreach ($cat['jugadores'] as $j) {
        $row = $fpRow($j);
        $formation_pool[$key][]  = $row;
        $formation_pool['all'][] = $row;
    }
    $formation_categories[] = [
        'id'    => $key,
        'name'  => $cat['nombre'] ?? ('Cat ' . $cid),
        'count' => count($cat['jugadores']),
    ];
}

$motmOpen = null;
$motmWinner = null;
try {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT v.id, v.ends_at, v.juego_id, j.fecha, j.rival FROM motm_votaciones v JOIN juegos j ON j.id = v.juego_id WHERE v.status = 'open' AND v.ends_at > ? ORDER BY v.ends_at ASC LIMIT 1");
    $stmt->execute([$now]);
    $motmOpen = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($motmOpen) {
        $stmt2 = $pdo->prepare("SELECT id, nombre, foto_url, orden FROM motm_nominees WHERE votacion_id = ? ORDER BY orden ASC");
        $stmt2->execute([$motmOpen['id']]);
        $motmOpen['nominees'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($motmOpen['nominees'] as &$mn) {
            if (!empty($mn['foto_url'])) {
                $mn['foto_url'] = vcf_normalize_foto_url($mn['foto_url']);
            }
        }
        unset($mn);
    }

    if (!$motmOpen) {
        $stmt = $pdo->query("SELECT v.id, v.winner_nominee_id, n.nombre AS winner_nombre, n.foto_url AS winner_foto FROM motm_votaciones v LEFT JOIN motm_nominees n ON n.id = v.winner_nominee_id WHERE v.status = 'closed' AND v.winner_nominee_id IS NOT NULL ORDER BY v.ends_at DESC LIMIT 1");
        $motmWinner = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($motmWinner && !empty($motmWinner['winner_foto'])) {
            $motmWinner['winner_foto'] = vcf_normalize_foto_url($motmWinner['winner_foto']);
        }
        if ($motmWinner) {
            $st = $pdo->prepare("SELECT COUNT(*) AS total, SUM(nominee_id = ?) AS winner_votes FROM motm_votes WHERE votacion_id = ?");
            $st->execute([$motmWinner['winner_nominee_id'], $motmWinner['id']]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'winner_votes' => 0];
            $motmWinner['total_votes'] = (int) $row['total'];
            $motmWinner['winner_votes'] = (int) $row['winner_votes'];
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
    // tabla puede no existir aÃºn
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


$page_title = 'VCF Academy Houston | Official Valencia CF Academy — TX';
$page_description = 'Official Valencia CF youth academy in Katy & Houston, TX. Competitive training, VCF methodology, tournaments & community. Programs and schedules online.';
// OG/Twitter image: use header defaults (vcf-crest.png) for WhatsApp preview — do not override with hero here.
$page_active = 'home';
$star_section_visible = !empty($jugadorMes);
$reels = [];
try {
    $reels = $pdo->query(
        'SELECT id, video_url, caption, orden, view_count, like_count FROM match_reels ORDER BY orden ASC, id DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $reels = $pdo->query(
            'SELECT id, video_url, caption, orden FROM match_reels ORDER BY orden ASC, id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($reels as &$vcf_reel_row) {
            $vcf_reel_row['view_count'] = 0;
            $vcf_reel_row['like_count'] = 0;
        }
        unset($vcf_reel_row);
    } catch (PDOException $e2) {
        $reels = [];
    }
}
$vcf_home_scripts = true;
$vcf_nav_context_loaded = true;
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/home-redesign-body.php';
require __DIR__ . '/includes/footer.php';
