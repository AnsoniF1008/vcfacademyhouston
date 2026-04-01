<?php
/**
 * VCF Academy Houston — calendar.php
 * Rediseño inspirado en valenciacf.com
 * Mantiene la funcionalidad de "Add to Calendar" original
 */

// ── Mantén aquí tu lógica PHP original de calendario ──
// include 'config.php'; etc.
// Si tenías lógica para añadir al calendario del usuario, consérvala aquí

$page_active = 'calendar';
$page_title  = 'Calendar';
$base_url    = '/';
include 'includes/header.php';

// ── Datos de partidos y entrenamientos ──
// Reemplaza con tus queries reales
$schedule_events = [
  ['date'=>'2026-04-11','type'=>'match',    'title'=>'VCF Houston vs AHFC 13B',          'time'=>'4:00 PM','venue'=>'Zube Park Field 1',     'cal_id'=>6],
  ['date'=>'2026-04-13','type'=>'training', 'title'=>'Training Session',                  'time'=>'5:00 PM','venue'=>'N Westgreen Blvd','cal_id'=>null],
  ['date'=>'2026-04-15','type'=>'training', 'title'=>'Training Session',                  'time'=>'5:00 PM','venue'=>'N Westgreen Blvd','cal_id'=>null],
  ['date'=>'2026-04-17','type'=>'training', 'title'=>'Training Session',                  'time'=>'5:00 PM','venue'=>'N Westgreen Blvd','cal_id'=>null],
  ['date'=>'2026-04-20','type'=>'training', 'title'=>'Training Session',                  'time'=>'5:00 PM','venue'=>'N Westgreen Blvd','cal_id'=>null],
  ['date'=>'2026-04-22','type'=>'training', 'title'=>'Training Session',                  'time'=>'5:00 PM','venue'=>'N Westgreen Blvd','cal_id'=>null],
  ['date'=>'2026-04-24','type'=>'training', 'title'=>'Training Session',                  'time'=>'5:00 PM','venue'=>'N Westgreen Blvd','cal_id'=>null],
  ['date'=>'2026-04-25','type'=>'match',    'title'=>'VCF Houston vs North Shore FC White','time'=>'4:00 PM','venue'=>'Meyer Park Field 6',     'cal_id'=>7],
  ['date'=>'2026-05-02','type'=>'match',    'title'=>'VCF Houston vs Challenge United',   'time'=>'12:00 PM','venue'=>'Burroughs Park Field 7','cal_id'=>8],
];

// Handle "add to calendar" (iCal download) — mantiene tu lógica original
if (isset($_GET['id'])) {
  $cal_id = (int)$_GET['id'];
  $event  = null;
  foreach ($schedule_events as $e) {
    if ($e['cal_id'] == $cal_id) { $event = $e; break; }
  }
  if ($event) {
    $dtstart = str_replace('-','',$event['date']).'T'.str_replace([':',' ','AM','PM'],['','','',''],$event['time']).'00';
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="vcf-match-'.$cal_id.'.ics"');
    echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//VCF Academy Houston//EN\r\nBEGIN:VEVENT\r\n";
    echo "DTSTART:".$dtstart."\r\n";
    echo "SUMMARY:".htmlspecialchars($event['title'])."\r\n";
    echo "LOCATION:".htmlspecialchars($event['venue'])."\r\n";
    echo "DESCRIPTION:VCF Academy Houston Match\r\n";
    echo "END:VEVENT\r\nEND:VCALENDAR\r\n";
    exit;
  }
}

// Build calendar grid for current/next month
$year  = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$month = max(1, min(12, $month));
$first_day  = mktime(0,0,0,$month,1,$year);
$days_month = (int)date('t', $first_day);
$start_dow  = (int)date('w', $first_day); // 0=Sun
$month_name = date('F Y', $first_day);
$prev = $month == 1 ? ['y'=>$year-1,'m'=>12] : ['y'=>$year,'m'=>$month-1];
$next = $month ==12 ? ['y'=>$year+1,'m'=>1]  : ['y'=>$year,'m'=>$month+1];
$today_str  = date('Y-m-d');

// Index events by date
$events_by_date = [];
foreach ($schedule_events as $e) {
  $events_by_date[$e['date']][] = $e;
}
?>

<!-- Page Header -->
<div class="vcf-page-header">
  <div class="vcf-page-header__inner">
    <div class="vcf-page-header__label">VCF Academy Houston</div>
    <h1 class="vcf-page-header__title">Team <em>Calendar</em></h1>
  </div>
</div>

<!-- ══ CALENDAR + UPCOMING LIST ══ -->
<main style="max-width:var(--page-max);margin:0 auto;padding:36px var(--page-pad);">

  <!-- Legend + nav -->
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
    <div style="display:flex;gap:16px;align-items:center;">
      <span class="vcf-tag vcf-tag--orange">&#9679; Match</span>
      <span class="vcf-tag vcf-tag--gray">&#9679; Training</span>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="?y=<?= $prev['y'] ?>&m=<?= $prev['m'] ?>" class="vcf-btn vcf-btn--outline" style="padding:8px 16px;">&#8592;</a>
      <span style="font-family:var(--font-display);font-size:18px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:var(--vcf-white);min-width:180px;text-align:center;">
        <?= htmlspecialchars($month_name) ?>
      </span>
      <a href="?y=<?= $next['y'] ?>&m=<?= $next['m'] ?>" class="vcf-btn vcf-btn--outline" style="padding:8px 16px;">&#8594;</a>
    </div>
  </div>

  <!-- Calendar grid -->
  <div class="vcf-cal-grid">
    <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
      <div class="vcf-cal-head"><?= $d ?></div>
    <?php endforeach; ?>

    <?php
    // Empty cells before first day
    for ($i = 0; $i < $start_dow; $i++) {
      echo '<div class="vcf-cal-day vcf-cal-day--empty"></div>';
    }
    // Day cells
    for ($day = 1; $day <= $days_month; $day++) {
      $date_str  = sprintf('%04d-%02d-%02d', $year, $month, $day);
      $is_today  = $date_str === $today_str;
      $day_events= $events_by_date[$date_str] ?? [];
      $cls = 'vcf-cal-day' . ($is_today ? ' vcf-cal-day--today' : '') . (empty($day_events) ? '' : ' vcf-cal-day--has-event');
      echo '<div class="'.$cls.'">';
      echo '<div class="vcf-cal-day__num">'.$day.'</div>';
      foreach ($day_events as $ev) {
        $color = $ev['type']==='match' ? 'var(--vcf-orange)' : 'rgba(255,255,255,0.3)';
        $border= $ev['type']==='match' ? 'var(--vcf-orange)' : 'rgba(255,255,255,0.2)';
        echo '<div class="vcf-cal-event" style="border-color:'.$border.';color:'.$color.'">';
        echo htmlspecialchars($ev['time']).' '.htmlspecialchars(explode(' vs ',$ev['title'])[0] ?? $ev['title']);
        echo '</div>';
      }
      echo '</div>';
    }
    // Empty cells after last day
    $total_cells = $start_dow + $days_month;
    $remaining   = (7 - ($total_cells % 7)) % 7;
    for ($i = 0; $i < $remaining; $i++) {
      echo '<div class="vcf-cal-day vcf-cal-day--empty"></div>';
    }
    ?>
  </div>

  <!-- Upcoming events list -->
  <div style="margin-top:48px;">
    <h2 class="vcf-section__title" style="margin-bottom:24px;">Upcoming <em>Events</em></h2>
    <div style="display:flex;flex-direction:column;gap:4px;">
      <?php
      $upcoming = array_filter($schedule_events, function($e) use ($today_str){ return $e['date'] >= $today_str; });
      usort($upcoming, function($a,$b){ return strcmp($a['date'],$b['date']); });
      foreach ($upcoming as $ev):
        $is_match = $ev['type'] === 'match';
      ?>
      <div style="background:var(--vcf-dark2);padding:16px 20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap;border-left:3px solid <?= $is_match ? 'var(--vcf-orange)' : 'var(--vcf-border2)' ?>;">
        <div style="flex-shrink:0;min-width:90px;">
          <div style="font-family:var(--font-display);font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:<?= $is_match ? 'var(--vcf-orange)' : 'var(--vcf-gray)' ?>;">
            <?= date('D, M j', strtotime($ev['date'])) ?>
          </div>
          <div style="font-family:var(--font-display);font-size:13px;font-weight:700;color:var(--vcf-gray);">
            <?= htmlspecialchars($ev['time']) ?>
          </div>
        </div>
        <div style="flex:1;">
          <div style="font-family:var(--font-display);font-size:15px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--vcf-white);">
            <?= htmlspecialchars($ev['title']) ?>
          </div>
          <div style="font-family:var(--font-display);font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--vcf-gray);margin-top:2px;">
            <?= htmlspecialchars($ev['venue']) ?>
          </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
          <?php if ($is_match): ?>
            <span class="vcf-tag vcf-tag--orange">Match</span>
          <?php else: ?>
            <span class="vcf-tag vcf-tag--gray">Training</span>
          <?php endif; ?>
          <?php if ($ev['cal_id']): ?>
            <a href="?id=<?= (int)$ev['cal_id'] ?>" class="vcf-cal-btn">+ Add to Calendar</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</main>

<?php include 'includes/footer.php'; ?>
