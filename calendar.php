<?php
/**
 * Add match to calendar: Google Calendar link and .ics download.
 * Usage: calendar.php?id=1  (chooser page) or calendar.php?id=1&format=ics (download .ics)
 */
require __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php#tournaments');
    exit;
}

$stmt = $pdo->prepare("SELECT j.id, j.fecha, j.hora, j.rival, j.cancha, j.ubicacion_mapa_url, t.nombre_torneo, s.nombre AS sede_nombre FROM juegos j JOIN torneos_info t ON t.id = j.torneo_id LEFT JOIN sedes s ON s.id = j.sede_id WHERE j.id = ?");
$stmt->execute([$id]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$t) {
    header('Location: index.php#tournaments');
    exit;
}

$title = !empty($t['rival']) ? 'VCF Academy vs ' . $t['rival'] : $t['nombre_torneo'];
$fecha = $t['fecha'];
$hora = $t['hora'];
$location = ($t['sede_nombre'] && $t['cancha']) ? $t['sede_nombre'] . ' - ' . $t['cancha'] : ($t['cancha'] ?? $t['sede_nombre'] ?? '');
$details = $t['ubicacion_mapa_url'] ? 'Map: ' . $t['ubicacion_mapa_url'] : '';

// Start/end for Google and .ics (default 1.5 hour if no time)
$startDate = $fecha . ($hora ? ' ' . $hora : ' 12:00:00');
$startTs = strtotime($startDate);
$endTs = $startTs + (90 * 60); // +1.5h

$formatIcs = isset($_GET['format']) && $_GET['format'] === 'ics';

if ($formatIcs) {
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

$page_title = 'Add to calendar - VCF Academy Houston';
$page_description = 'Add a VCF Academy Houston match to Google Calendar or download an .ics file.';
require __DIR__ . '/includes/header.php';
?>
<section class="vcf-section--dark vcf-page-sub vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container py-5">
        <h1 class="vcf-section__title">Add to my <em>Calendar</em></h1>
        <p class="vcf-section-desc"><?= htmlspecialchars($title) ?> — <?= date('l, F j, Y', $startTs) ?> at <?= date('g:i A', $startTs) ?></p>
        <p class="mb-3" style="color:var(--vcf-gray);"><?= htmlspecialchars($location) ?></p>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= htmlspecialchars($gUrl) ?>" class="vcf-btn-cta" target="_blank" rel="noopener noreferrer">Open in Google Calendar</a>
            <a href="calendar.php?id=<?= $id ?>&amp;format=ics" class="btn btn-outline-light">Download .ics (Apple / Outlook)</a>
            <a href="<?= isset($base) ? $base : '' ?>/index.php#tournaments" class="btn btn-outline-light">Back to schedule</a>
        </div>
    </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
