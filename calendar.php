<?php
/**
 * Calendar: monthly view of all matches + add-to-calendar / .ics download.
 * Usage:
 *   calendar.php              — monthly view (current month)
 *   calendar.php?m=2026-04   — monthly view for a specific month
 *   calendar.php?id=1         — single match export chooser
 *   calendar.php?id=1&format=ics — .ics download
 */

// Skip the page cache for the .ics download — it has its own Content-Type
// (text/calendar) and download semantics that don't fit the HTML cache.
$_calendar_is_ics = (($_GET['format'] ?? '') === 'ics');
if (!$_calendar_is_ics) {
    require __DIR__ . '/includes/page_cache.php';
    if (vcf_page_cache_try_serve(600)) {
        exit;
    }
}
require __DIR__ . '/config/database.php';
if (!$_calendar_is_ics) {
    vcf_page_cache_start(600);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ── Single match export (existing behaviour) ──────────────────
if ($id <= 0 && !isset($_GET['m'])) {
    // No id and no month = show monthly calendar for current month
}
if ($id < 0) $id = 0;

// ── Load all matches for calendar view ───────────────────────
$allMatches = [];
try {
    $s = $pdo->query("SELECT j.id, j.fecha, j.hora, j.rival, j.goles_vcf, j.goles_rival, j.estado, t.nombre_torneo, s.nombre AS sede_nombre, j.cancha FROM juegos j JOIN torneos_info t ON t.id = j.torneo_id LEFT JOIN sedes s ON s.id = j.sede_id ORDER BY j.fecha ASC, j.hora ASC");
    $allMatches = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log('Calendar matches: ' . $e->getMessage()); }

// Group by date for quick lookup
$matchesByDate = [];
foreach ($allMatches as $m) {
    $matchesByDate[$m['fecha']][] = $m;
}

// ── Single match lookup (for export) ─────────────────────────
$t = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT j.id, j.fecha, j.hora, j.rival, j.cancha, j.ubicacion_mapa_url, t.nombre_torneo, s.nombre AS sede_nombre FROM juegos j JOIN torneos_info t ON t.id = j.torneo_id LEFT JOIN sedes s ON s.id = j.sede_id WHERE j.id = ?");
    $stmt->execute([$id]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$t) {
        header('Location: calendar.php');
        exit;
    }
}

$title = $t ? (!empty($t['rival']) ? 'VCF Academy vs ' . $t['rival'] : $t['nombre_torneo']) : '';
$fecha = $t['fecha'] ?? '';
$hora = $t['hora'] ?? null;
$location = $t ? (($t['sede_nombre'] && $t['cancha']) ? $t['sede_nombre'] . ' - ' . $t['cancha'] : ($t['cancha'] ?? $t['sede_nombre'] ?? '')) : '';
$details = ($t && $t['ubicacion_mapa_url']) ? 'Map: ' . $t['ubicacion_mapa_url'] : '';

// Start/end for Google and .ics (default 1.5 hour if no time)
$startDate = $fecha . ($hora ? ' ' . $hora : ' 12:00:00');
$startTs = $fecha ? strtotime($startDate) : time();
$endTs = $startTs + (90 * 60); // +1.5h

$formatIcs = isset($_GET['format']) && $_GET['format'] === 'ics';

if ($formatIcs && $id > 0 && $t) {
    $dtStart = gmdate('Ymd\THis\Z', $startTs);
    $dtEnd = gmdate('Ymd\THis\Z', $endTs);
    $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//VCF Academy Houston//Match Calendar//EN\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:valencia-match-" . $id . "@vcfacademy\r\n";
    $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    $ics .= "DTSTART:" . $dtStart . "\r\n";
    $ics .= "DTEND:" . $dtEnd . "\r\n";
    $ics .= "SUMMARY:" . str_replace(["\r", "\n", ","], ['', ' ', '\\,'], $title) . "\r\n";
    $ics .= "LOCATION:" . str_replace(["\r", "\n", ","], ['', ' ', '\\,'], $location) . "\r\n";
    if ($details !== '') {
        $ics .= "DESCRIPTION:" . str_replace(["\r", "\n"], ['', '\\n'], $details) . "\r\n";
    }
    $ics .= "END:VEVENT\r\nEND:VCALENDAR\r\n";

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="vcf-match-' . $id . '.ics"');
    echo $ics;
    exit;
}

// Build Google Calendar URL
$gStart = date('Ymd\THis', $startTs);
$gEnd = date('Ymd\THis', $endTs);
$gUrl = 'https://www.google.com/calendar/render?action=TEMPLATE';
$gUrl .= '&text=' . rawurlencode($title);
$gUrl .= '&dates=' . $gStart . '/' . $gEnd;
$gUrl .= '&location=' . rawurlencode($location);
if ($details !== '') {
    $gUrl .= '&details=' . rawurlencode($details);
}

// ── Monthly calendar logic ────────────────────────────────────
$rawMonth = trim($_GET['m'] ?? '');
if ($rawMonth && preg_match('/^\d{4}-\d{2}$/', $rawMonth)) {
    $calYear  = (int) substr($rawMonth, 0, 4);
    $calMonth = (int) substr($rawMonth, 5, 2);
} else {
    $nowH = new DateTime('now', new DateTimeZone('America/Chicago'));
    $calYear  = (int) $nowH->format('Y');
    $calMonth = (int) $nowH->format('m');
}
$calMonth = max(1, min(12, $calMonth));
$calYear  = max(2020, min(2100, $calYear));

$firstDay   = mktime(0, 0, 0, $calMonth, 1, $calYear);
$daysInMonth = (int) date('t', $firstDay);
$startDow   = (int) date('w', $firstDay); // 0=Sun
$prevMonth  = $calMonth === 1 ? ['y' => $calYear - 1, 'm' => 12] : ['y' => $calYear, 'm' => $calMonth - 1];
$nextMonth  = $calMonth === 12 ? ['y' => $calYear + 1, 'm' => 1] : ['y' => $calYear, 'm' => $calMonth + 1];
$monthLabel = date('F Y', $firstDay);
$todayStr   = (new DateTime('now', new DateTimeZone('America/Chicago')))->format('Y-m-d');

$page_title = 'Calendar - VCF Academy Houston';
$page_description = 'Full match schedule calendar for VCF Academy Houston.';

if ($id > 0 && $t) {
    $page_title = 'Add to calendar - VCF Academy Houston';
    $page_description = 'Add a VCF Academy Houston match to Google Calendar or download an .ics file.';
}

require __DIR__ . '/includes/header.php';

if ($id > 0 && $t):
?>
<!-- Single match export view -->
<section class="vcf-section--dark vcf-page-sub vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container py-5">
        <h1 class="vcf-section__title">Add to my <em>Calendar</em></h1>
        <p class="vcf-section-desc"><?= htmlspecialchars($title) ?> — <?= date('l, F j, Y', $startTs) ?> at <?= date('g:i A', $startTs) ?></p>
        <p class="mb-3" style="color:var(--vcf-gray);"><?= htmlspecialchars($location) ?></p>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= htmlspecialchars($gUrl) ?>" class="vcf-btn-cta" target="_blank" rel="noopener noreferrer">Open in Google Calendar</a>
            <a href="calendar.php?id=<?= $id ?>&amp;format=ics" class="btn btn-outline-light">Download .ics (Apple / Outlook)</a>
            <a href="calendar.php" class="btn btn-outline-light">Full Calendar</a>
            <a href="<?= isset($base) ? $base : '' ?>/index.php#tournaments" class="btn btn-outline-light">Back to schedule</a>
        </div>
    </div>
    </div>
</section>
<?php else: ?>
<!-- Monthly calendar view -->
<section class="vcf-section--dark vcf-page-sub vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container py-5">
        <h1 class="vcf-section__title">Match <em>Calendar</em></h1>

        <!-- Month navigation -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="calendar.php?m=<?= sprintf('%04d-%02d', $prevMonth['y'], $prevMonth['m']) ?>" class="btn btn-outline-light btn-sm">&larr; <?= date('M Y', mktime(0,0,0,$prevMonth['m'],1,$prevMonth['y'])) ?></a>
            <h2 class="h5 text-white mb-0 fw-bold" style="font-family:var(--font-display,sans-serif);letter-spacing:.05em;"><?= $monthLabel ?></h2>
            <a href="calendar.php?m=<?= sprintf('%04d-%02d', $nextMonth['y'], $nextMonth['m']) ?>" class="btn btn-outline-light btn-sm"><?= date('M Y', mktime(0,0,0,$nextMonth['m'],1,$nextMonth['y'])) ?> &rarr;</a>
        </div>

        <!-- Calendar grid -->
        <div class="vcf-cal-grid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;">
            <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
            <div style="padding:6px 4px;text-align:center;font-size:11px;font-weight:700;color:var(--vcf-gray,#aaa);text-transform:uppercase;letter-spacing:.08em;"><?= $d ?></div>
            <?php endforeach; ?>

            <?php
            // Empty cells before first day
            for ($i = 0; $i < $startDow; $i++):
            ?>
            <div style="min-height:80px;background:rgba(255,255,255,0.02);border-radius:4px;"></div>
            <?php endfor; ?>

            <?php for ($day = 1; $day <= $daysInMonth; $day++):
                $dateStr = sprintf('%04d-%02d-%02d', $calYear, $calMonth, $day);
                $dayMatches = $matchesByDate[$dateStr] ?? [];
                $isToday = ($dateStr === $todayStr);
            ?>
            <div style="min-height:80px;background:<?= $isToday ? 'rgba(232,119,34,0.12)' : 'rgba(255,255,255,0.03)' ?>;border-radius:4px;border:1px solid <?= $isToday ? 'rgba(232,119,34,0.5)' : 'rgba(255,255,255,0.06)' ?>;padding:6px;">
                <div style="font-size:12px;font-weight:<?= $isToday ? '700' : '400' ?>;color:<?= $isToday ? 'var(--vcf-orange,#E87722)' : 'var(--vcf-gray,#aaa)' ?>;margin-bottom:4px;"><?= $day ?></div>
                <?php foreach ($dayMatches as $dm):
                    $isPlayed = isset($dm['goles_vcf']) && $dm['goles_vcf'] !== null;
                    $isFuture = ($dateStr > $todayStr);
                    if ($isPlayed) {
                        $gv = (int)$dm['goles_vcf']; $gr = (int)$dm['goles_rival'];
                        $outcome = $gv > $gr ? 'W' : ($gv < $gr ? 'L' : 'D');
                        $bg = $outcome === 'W' ? 'rgba(34,197,94,0.2)' : ($outcome === 'L' ? 'rgba(239,68,68,0.2)' : 'rgba(255,255,255,0.1)');
                        $badge = $outcome === 'W' ? '#22c55e' : ($outcome === 'L' ? '#ef4444' : '#aaa');
                    } else {
                        $bg = 'rgba(232,119,34,0.15)'; $badge = 'var(--vcf-orange,#E87722)';
                    }
                ?>
                <a href="calendar.php?id=<?= (int)$dm['id'] ?>" title="<?= htmlspecialchars($dm['rival'] ?: $dm['nombre_torneo']) ?>" style="display:block;background:<?= $bg ?>;border-radius:3px;padding:3px 5px;margin-bottom:2px;text-decoration:none;">
                    <div style="font-size:10px;font-weight:700;color:<?= $badge ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?php if ($isPlayed): ?>
                        <?= $gv ?>–<?= $gr ?> <?= htmlspecialchars(mb_strimwidth($dm['rival'] ?: 'vs', 0, 12, '…')) ?>
                        <?php else: ?>
                        <?= $dm['hora'] ? date('g:i A', strtotime($dm['hora'])) : '' ?> <?= htmlspecialchars(mb_strimwidth($dm['rival'] ?: 'Match', 0, 10, '…')) ?>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Legend -->
        <div class="d-flex gap-3 mt-3 flex-wrap" style="font-size:11px;color:var(--vcf-gray,#aaa);">
            <span><span style="display:inline-block;width:10px;height:10px;background:rgba(34,197,94,0.3);border-radius:2px;margin-right:4px;"></span>Win</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:rgba(239,68,68,0.3);border-radius:2px;margin-right:4px;"></span>Loss</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:rgba(255,255,255,0.15);border-radius:2px;margin-right:4px;"></span>Draw</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:rgba(232,119,34,0.25);border-radius:2px;margin-right:4px;"></span>Upcoming</span>
        </div>

        <div class="mt-4">
            <a href="<?= isset($base) ? $base : '' ?>/index.php#tournaments" class="btn btn-outline-light btn-sm">Back to schedule</a>
        </div>
    </div>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
