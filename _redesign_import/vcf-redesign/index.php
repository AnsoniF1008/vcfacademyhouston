<?php
/**
 * VCF Academy Houston — index.php (Main Page)
 * Rediseño inspirado en valenciacf.com
 *
 * INSTRUCCIONES:
 * 1. Reemplaza tu index.php actual con este archivo
 * 2. Asegúrate de que includes/header.php, includes/footer.php y assets/css/vcf-style.css existen
 * 3. Mantén tu lógica PHP existente (DB queries, etc.) — solo cambia el HTML/CSS de salida
 */

// ── Mantén aquí toda tu lógica PHP existente ──────────────────────────────────
// Ejemplo: include 'config.php'; $db = new PDO(...); etc.
// Copia tus queries de partidos, jugadores, etc. desde el index.php original
// ──────────────────────────────────────────────────────────────────────────────

$page_active = 'home';
$page_title  = 'Official VCF Academy Houston';
include 'includes/header.php';
?>

<!-- ══════════════════════════════════════════
     HERO
══════════════════════════════════════════ -->
<section class="vcf-hero" id="hero">
  <div class="vcf-hero__bg" id="hero-bg"
       style="background-image: url('assets/uploads/hero-69a9bf305146f.jpg');">
  </div>
  <div class="vcf-hero__overlay"></div>
  <div class="vcf-hero__pattern"></div>

  <div class="vcf-hero__content">

    <div class="vcf-hero__tag">Official VCF Academy Program</div>
    <div class="vcf-hero__eyebrow">Join &middot; Compete &middot; Grow</div>
    <h1 class="vcf-hero__title">VCF Academy<br><em>Houston</em></h1>
    <p class="vcf-hero__tagline">Sentiment &middot; Courage &middot; Fight</p>

    <?php
    // ── PRÓXIMO PARTIDO ──
    // Reemplaza esta sección con tu query PHP real para obtener el próximo partido.
    // Estructura esperada: $next_match = ['date_label'=>'...','time'=>'...','opponent'=>'...','venue'=>'...','datetime'=>'2026-04-11 16:00:00','url'=>'...']
    // Por ahora usamos datos de ejemplo compatibles con lo que ya tenías:
    $next_match = [
      'date_label' => 'SAT APR 11',
      'time'       => '4:00 PM',
      'opponent'   => 'AHFC 13B PREMIER 1C',
      'venue'      => 'ZUBE PARK FIELD 1',
      'datetime'   => '2026-04-11T16:00:00',
      'info_url'   => '#tournaments',
    ];
    ?>
    <div class="vcf-match-widget">
      <div>
        <div class="vcf-match-widget__date">
          <?= htmlspecialchars($next_match['date_label']) ?> &middot; <?= htmlspecialchars($next_match['time']) ?>
        </div>
        <div class="vcf-match-widget__teams">
          <span class="vcf-match-widget__team">VCF Houston</span>
          <span class="vcf-match-widget__vs">vs</span>
          <span class="vcf-match-widget__team" style="color:var(--vcf-lightgray)">
            <?= htmlspecialchars($next_match['opponent']) ?>
          </span>
        </div>
        <div class="vcf-match-widget__venue">
          <?= htmlspecialchars($next_match['venue']) ?>
        </div>
      </div>

      <div class="vcf-match-widget__countdown" id="match-countdown"
           data-target="<?= htmlspecialchars($next_match['datetime']) ?>">
        <div class="vcf-count"><span class="vcf-count__num" id="cd-d">--</span><span class="vcf-count__label">Days</span></div>
        <div class="vcf-count"><span class="vcf-count__num" id="cd-h">--</span><span class="vcf-count__label">Hrs</span></div>
        <div class="vcf-count"><span class="vcf-count__num" id="cd-m">--</span><span class="vcf-count__label">Min</span></div>
        <div class="vcf-count"><span class="vcf-count__num" id="cd-s">--</span><span class="vcf-count__label">Sec</span></div>
      </div>

      <div class="vcf-match-widget__btns">
        <a href="<?= $base_url ?>index.php<?= htmlspecialchars($next_match['info_url']) ?>" class="vcf-match-widget__btn vcf-match-widget__btn--primary">Details &rarr;</a>
        <a href="<?= $base_url ?>calendar.php" class="vcf-match-widget__btn vcf-match-widget__btn--secondary">Calendar</a>
      </div>
    </div>

  </div>
</section>

<!-- ══════════════════════════════════════════
     SEASON STATS BAR
══════════════════════════════════════════ -->
<?php
// ── Reemplaza con tu query real de stats de temporada ──
$stats = ['W'=>1,'L'=>2,'D'=>2,'GF'=>13,'GA'=>17,'PTS'=>5];
?>
<div class="vcf-statsbar">
  <div class="vcf-statsbar__label">
    <span>Season 2025–26</span>
    <span>VCF Houston Orange &middot; STXCL EC &middot; B13 Premier</span>
  </div>
  <div class="vcf-statsbar__grid">
    <div class="vcf-stat"><div class="vcf-stat__val pos"><?= $stats['W'] ?></div><div class="vcf-stat__lbl">Wins</div></div>
    <div class="vcf-stat"><div class="vcf-stat__val neg"><?= $stats['L'] ?></div><div class="vcf-stat__lbl">Losses</div></div>
    <div class="vcf-stat"><div class="vcf-stat__val"><?= $stats['D'] ?></div><div class="vcf-stat__lbl">Draws</div></div>
    <div class="vcf-stat"><div class="vcf-stat__val"><?= $stats['GF'] ?></div><div class="vcf-stat__lbl">Goals For</div></div>
    <div class="vcf-stat"><div class="vcf-stat__val"><?= $stats['GA'] ?></div><div class="vcf-stat__lbl">Goals Ag.</div></div>
    <div class="vcf-stat"><div class="vcf-stat__val accent"><?= $stats['PTS'] ?></div><div class="vcf-stat__lbl">Points</div></div>
  </div>
</div>


<!-- ══════════════════════════════════════════
     METHODOLOGY
══════════════════════════════════════════ -->
<section id="methodology">
  <div class="vcf-section" style="border-bottom:1px solid var(--vcf-border);">
    <div class="vcf-section__header">
      <h2 class="vcf-section__title">Our <em>Methodology</em></h2>
    </div>
    <p style="font-size:13px;color:var(--vcf-gray);max-width:600px;margin-bottom:28px;line-height:1.7;">
      "Educating People, Training Footballers" — In Houston, we don't just play soccer; we live it.
      Our program follows the official VCF Academy pillars, ensuring every player develops
      on and off the pitch.
    </p>
    <div class="vcf-pillars">
      <div class="vcf-pillar">
        <div class="vcf-pillar__num">01</div>
        <div class="vcf-pillar__title">Identity</div>
        <p class="vcf-pillar__desc">Building the Valencia CF identity in every young player — values, culture, pride and commitment to the crest.</p>
      </div>
      <div class="vcf-pillar">
        <div class="vcf-pillar__num">02</div>
        <div class="vcf-pillar__title">Effort</div>
        <p class="vcf-pillar__desc">Hard work and dedication in every training session, every matchday, every sprint, every rep — no shortcuts.</p>
      </div>
      <div class="vcf-pillar">
        <div class="vcf-pillar__num">03</div>
        <div class="vcf-pillar__title">Intelligence</div>
        <p class="vcf-pillar__desc">Tactical awareness and smart decision-making — reading the game, knowing your role, anticipating play.</p>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     FORMATION VIEWER
══════════════════════════════════════════ -->
<section id="formation">
  <div class="vcf-section--dark">
    <div class="vcf-section__inner">
      <div class="vcf-section__header">
        <h2 class="vcf-section__title">Know Your <em>Role</em></h2>
      </div>
      <p style="font-size:13px;color:var(--vcf-gray);margin-bottom:22px;">
        Tap each position to learn the role. VCF Academy methodology — Formation 4-3-3.
      </p>

      <!-- Formation tabs -->
      <div class="vcf-formation__tabs">
        <button class="vcf-formation__tab active" data-formation="433">4-3-3</button>
        <button class="vcf-formation__tab" data-formation="442">4-4-2</button>
        <button class="vcf-formation__tab" data-formation="352">3-5-2</button>
      </div>

      <div class="vcf-formation">
        <!-- Pitch -->
        <div class="vcf-pitch" id="vcf-pitch">
          <div class="vcf-pitch__lines">
            <svg viewBox="0 0 400 320" preserveAspectRatio="none">
              <!-- field lines -->
              <rect x="10" y="10" width="380" height="300" fill="none" stroke="rgba(255,255,255,0.09)" stroke-width="1"/>
              <line x1="10" y1="160" x2="390" y2="160" stroke="rgba(255,255,255,0.09)" stroke-width="1"/>
              <circle cx="200" cy="160" r="40" fill="none" stroke="rgba(255,255,255,0.09)" stroke-width="1"/>
              <circle cx="200" cy="160" r="2" fill="rgba(255,255,255,0.15)"/>
              <!-- penalty boxes -->
              <rect x="110" y="10" width="180" height="60" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
              <rect x="110" y="250" width="180" height="60" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
              <!-- goal areas -->
              <rect x="155" y="10" width="90" height="25" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
              <rect x="155" y="285" width="90" height="25" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
            </svg>
          </div>
          <!-- Positions rendered by JS -->
        </div>

        <!-- Info panel -->
        <div>
          <div class="vcf-formation__info active" id="pos-info-default">
            <h4>Select a Position</h4>
            <p>Tap any player dot on the pitch to learn about their role in the VCF Academy 4-3-3 system.</p>
          </div>
          <div class="vcf-formation__info" id="pos-info-detail" style="display:none;">
            <h4 id="pos-info-title">GK</h4>
            <p id="pos-info-desc">Description here.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════════
     TRAINING GROUNDS
══════════════════════════════════════════ -->
<section id="grounds">
  <div class="vcf-section">
    <div class="vcf-section__header">
      <h2 class="vcf-section__title">Training <em>Grounds</em></h2>
    </div>
    <p style="font-size:13px;color:var(--vcf-gray);max-width:540px;margin-bottom:24px;line-height:1.7;">
      We bring the Mestalla spirit to your neighborhood. Find our official training locations across the Houston area.
    </p>
    <div class="vcf-grounds">
      <div class="vcf-ground">
        <div class="vcf-ground__name">N West Green Blvd</div>
        <div class="vcf-ground__addr">2203 N Westgreen Blvd<br>Katy, TX 77449</div>
        <div class="vcf-ground__schedule">
          <div class="vcf-ground__schedule-row">
            <span>Category</span>
            <span>B13</span>
          </div>
          <div class="vcf-ground__schedule-row">
            <span>Monday</span>
            <span>5:00 PM</span>
          </div>
          <div class="vcf-ground__schedule-row">
            <span>Wednesday</span>
            <span>5:00 PM</span>
          </div>
          <div class="vcf-ground__schedule-row">
            <span>Friday</span>
            <span>5:00 PM</span>
          </div>
        </div>
        <a href="https://maps.app.goo.gl/q27c1FCQw4cvspGX8" target="_blank" rel="noopener" class="vcf-ground__gps">
          <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" style="flex-shrink:0"><path d="M10 2a6 6 0 0 0-6 6c0 4 6 10 6 10s6-6 6-10a6 6 0 0 0-6-6zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/></svg>
          Open in GPS
        </a>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════════
     ROSTER
══════════════════════════════════════════ -->
<section id="roster">
  <div class="vcf-section">
    <div class="vcf-section__header">
      <h2 class="vcf-section__title">Roster <em>B13</em></h2>
      <span style="font-family:var(--font-display);font-size:11px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--vcf-gray);">
        Click a player for stats
      </span>
    </div>

    <?php
    // ── JUGADORES ──
    // Reemplaza con tu query real. Estructura del array:
    // ['num'=>'23','initials'=>'MA','name'=>'Matías Astorino','pos'=>'Goalkeeper','pos_short'=>'GK','photo'=>'roster-xxx.jpg',
    //  'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,
    //  'pace'=>7,'shoot'=>4,'drib'=>5,'def'=>6,'phys'=>7,'pass'=>6]
    $players = [
      ['num'=>'23','initials'=>'MA','name'=>'Matías Astorino',       'pos'=>'Goalkeeper',          'pos_short'=>'GK', 'photo'=>'roster-69af42a639f04.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>5,'shoot'=>3,'drib'=>4,'def'=>5,'phys'=>6,'pass'=>5],
      ['num'=>'6', 'initials'=>'SB','name'=>'Sebastian Brito-Stirpe','pos'=>'Defender · Right Back', 'pos_short'=>'RB', 'photo'=>'roster-69af7584136ff.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>4,'drib'=>5,'def'=>7,'phys'=>6,'pass'=>6],
      ['num'=>'31','initials'=>'MG','name'=>'Miguel Gonzalez',        'pos'=>'Defender · Centre Back','pos_short'=>'CB', 'photo'=>'roster-69ab621c869f2.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>4,'drib'=>4,'def'=>8,'phys'=>7,'pass'=>5],
      ['num'=>'45','initials'=>'JG','name'=>'Jayden Gomez',           'pos'=>'Defender · Left Back',  'pos_short'=>'LB', 'photo'=>'roster-69b099cdb1035.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>4,'drib'=>5,'def'=>7,'phys'=>6,'pass'=>6],
      ['num'=>'14','initials'=>'SY','name'=>'Santiago Yepez Moreno',  'pos'=>'Midfielder · Left CM',  'pos_short'=>'CM', 'photo'=>'roster-69af6f07b414b.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>6,'drib'=>7,'def'=>5,'phys'=>6,'pass'=>7],
      ['num'=>'72','initials'=>'TB','name'=>'Tiziano Barrio',         'pos'=>'Midfielder · Right CM', 'pos_short'=>'CM', 'photo'=>'roster-69b709db5e85f.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>6,'drib'=>7,'def'=>5,'phys'=>6,'pass'=>7],
      ['num'=>'93','initials'=>'JM','name'=>'Juan Morales',           'pos'=>'Midfielder · Left CM',  'pos_short'=>'CM', 'photo'=>'roster-69b0a15194846.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>5,'drib'=>6,'def'=>6,'phys'=>6,'pass'=>7],
      ['num'=>'29','initials'=>'MM','name'=>'Mateo Mata',             'pos'=>'Forward · Right Wing',  'pos_short'=>'RW', 'photo'=>'roster-69aa27b820910.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>8,'shoot'=>7,'drib'=>8,'def'=>3,'phys'=>5,'pass'=>6],
      ['num'=>'80','initials'=>'JM','name'=>'Juan Marco',             'pos'=>'Forward · Right Wing',  'pos_short'=>'RW', 'photo'=>'roster-69af6fdc9493b.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>7,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>6],
      ['num'=>'16','initials'=>'VO','name'=>'Vicente Ortegano',       'pos'=>'Forward · Right Wing',  'pos_short'=>'RW', 'photo'=>'roster-69b0a16b5e874.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>5],
      ['num'=>'11','initials'=>'AM','name'=>'Alexander Morejon',      'pos'=>'Forward · Left Wing',   'pos_short'=>'LW', 'photo'=>'roster-69af578163a2a.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>8,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>6],
      ['num'=>'20','initials'=>'MB','name'=>'Mateo Briceno Diaz',     'pos'=>'Forward · Left Wing',   'pos_short'=>'LW', 'photo'=>'roster-69b098dcb966b.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>5],
      ['num'=>'2', 'initials'=>'LG','name'=>'Leonardo Garcia',        'pos'=>'Forward · Left Wing',   'pos_short'=>'LW', 'photo'=>'roster-69b02caa5d404.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>8,'shoot'=>6,'drib'=>8,'def'=>3,'phys'=>5,'pass'=>6],
      ['num'=>'48','initials'=>'FP','name'=>'Fabian Perez Marcano',   'pos'=>'Forward · Left Wing',   'pos_short'=>'LW', 'photo'=>'roster-69af431c21c12.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>5],
      ['num'=>'67','initials'=>'MA','name'=>'Manuel Alvarez',         'pos'=>'Forward · Left Wing',   'pos_short'=>'LW', 'photo'=>'roster-69b09977384cd.jpg','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>5],
      ['num'=>'',  'initials'=>'RB','name'=>'Rodrigo Bermudez',       'pos'=>'Goalkeeper',            'pos_short'=>'GK', 'photo'=>'','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>5,'shoot'=>3,'drib'=>4,'def'=>5,'phys'=>6,'pass'=>5],
      ['num'=>'',  'initials'=>'JD','name'=>'Jacob Diaz',             'pos'=>'Forward · Right Wing',  'pos_short'=>'RW', 'photo'=>'','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>8,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>6],
      ['num'=>'',  'initials'=>'JF','name'=>'Justin Flores',          'pos'=>'Midfielder · Pivot',    'pos_short'=>'CDM','photo'=>'','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>5,'drib'=>5,'def'=>7,'phys'=>7,'pass'=>7],
      ['num'=>'',  'initials'=>'AH','name'=>'Antoine Haddad',         'pos'=>'Defender · Left CB',    'pos_short'=>'CB', 'photo'=>'','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>4,'drib'=>4,'def'=>8,'phys'=>7,'pass'=>5],
      ['num'=>'',  'initials'=>'JL','name'=>'Juan Lopez Rincon',      'pos'=>'Defender',              'pos_short'=>'DEF','photo'=>'','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>4,'drib'=>4,'def'=>7,'phys'=>6,'pass'=>5],
      ['num'=>'',  'initials'=>'AM','name'=>'Alessandro Montilla',    'pos'=>'Defender · Right Back', 'pos_short'=>'RB', 'photo'=>'','apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>4,'drib'=>5,'def'=>7,'phys'=>6,'pass'=>6],
    ];
    ?>

    <div class="vcf-roster-grid" id="roster-grid">
      <?php foreach ($players as $i => $p): ?>
      <div class="vcf-player"
           data-index="<?= $i ?>"
           data-player='<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>'>
        <div class="vcf-player__num"><?= htmlspecialchars($p['num']) ?></div>
        <div class="vcf-player__avatar">
          <?php if (!empty($p['photo'])): ?>
            <img src="assets/uploads/<?= htmlspecialchars($p['photo']) ?>"
                 alt="<?= htmlspecialchars($p['name']) ?>"
                 loading="lazy"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <span style="display:none;"><?= htmlspecialchars($p['initials']) ?></span>
          <?php else: ?>
            <?= htmlspecialchars($p['initials']) ?>
          <?php endif; ?>
        </div>
        <div class="vcf-player__name"><?= htmlspecialchars(explode(' ', $p['name'])[0]).' '.htmlspecialchars(explode(' ', $p['name'])[1] ?? '') ?></div>
        <div class="vcf-player__pos"><?= htmlspecialchars($p['pos_short']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Player Modal -->
<div class="vcf-modal-bg" id="player-modal">
  <div class="vcf-modal">
    <button class="vcf-modal__close" id="modal-close">&times;</button>
    <div class="vcf-modal__header">
      <div class="vcf-modal__avatar" id="modal-avatar"></div>
      <div class="vcf-modal__num" id="modal-num"></div>
      <div class="vcf-modal__info">
        <div class="vcf-modal__name" id="modal-name"></div>
        <div class="vcf-modal__pos"  id="modal-pos"></div>
      </div>
    </div>
    <div class="vcf-modal__stats">
      <div class="vcf-modal__stat"><div class="vcf-modal__stat-val" id="modal-apps">0</div><div class="vcf-modal__stat-lbl">Apps</div></div>
      <div class="vcf-modal__stat"><div class="vcf-modal__stat-val" id="modal-goals">0</div><div class="vcf-modal__stat-lbl">Goals</div></div>
      <div class="vcf-modal__stat"><div class="vcf-modal__stat-val" id="modal-assists">0</div><div class="vcf-modal__stat-lbl">Assists</div></div>
      <div class="vcf-modal__stat"><div class="vcf-modal__stat-val" id="modal-motm">0</div><div class="vcf-modal__stat-lbl">MOTM</div></div>
      <div class="vcf-modal__stat"><div class="vcf-modal__stat-val" id="modal-cs">0</div><div class="vcf-modal__stat-lbl">Clean Sh.</div></div>
    </div>
    <div class="vcf-skills">
      <div class="vcf-skills__title">Skills (1–10)</div>
      <?php foreach (['pace'=>'Pace','shoot'=>'Shoot','drib'=>'Drib','def'=>'Def','phys'=>'Phys','pass'=>'Pass'] as $key => $label): ?>
      <div class="vcf-skill-row">
        <span class="vcf-skill-row__lbl"><?= $label ?></span>
        <div class="vcf-skill-row__bar"><div class="vcf-skill-row__fill" id="skill-<?= $key ?>" style="width:0%"></div></div>
        <span class="vcf-skill-row__val" id="skillv-<?= $key ?>">0</span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════
     TOURNAMENTS
══════════════════════════════════════════ -->
<section id="tournaments">
  <div class="vcf-section--dark">
    <div class="vcf-section__inner">
      <div class="vcf-section__header">
        <h2 class="vcf-section__title">Upcoming <em>Fixtures</em></h2>
        <a href="<?= $base_url ?>calendar.php" class="vcf-section__more">Full calendar &rarr;</a>
      </div>

      <div style="font-family:var(--font-display);font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--vcf-orange);margin-bottom:16px;">
        VCF Houston Orange STXCL EC — 13B
      </div>

      <?php
      // ── PARTIDOS ──
      // Reemplaza con tu query real. Estructura:
      // ['date'=>'Sat, Apr 11','time'=>'4:00 PM','opponent'=>'AHFC 13B PREMIER 1C','location'=>'ZUBE PARK FIELD 1','status'=>'upcoming','score_home'=>null,'score_away'=>null,'cal_id'=>6]
      $matches = [
        ['date'=>'Sat, Feb 21','time'=>'12:00 PM','opponent'=>'NORTH SHORE FC SELECT WHITE 2013','location'=>'MEYER PARK FIELD 5',    'status'=>'finished', 'score_home'=>2,  'score_away'=>5,  'cal_id'=>null],
        ['date'=>'Sat, Feb 28','time'=>'10:00 AM','opponent'=>'CHALLENGE UNITED STXCL EC',        'location'=>'BURROUGHS PARK FIELD 7','status'=>'finished', 'score_home'=>4,  'score_away'=>3,  'cal_id'=>null],
        ['date'=>'Sun, Mar 1', 'time'=>'12:00 PM','opponent'=>'HOUSTON WOLVES SC BLACK STXCL',   'location'=>'DYESS PARK FIELD 9',    'status'=>'finished', 'score_home'=>3,  'score_away'=>3,  'cal_id'=>null],
        ['date'=>'Sat, Mar 21','time'=>'2:00 PM', 'opponent'=>'DYNAMOS STXCL EC 13B',             'location'=>'DYESS PARK FIELD 1',    'status'=>'finished', 'score_home'=>4,  'score_away'=>4,  'cal_id'=>null],
        ['date'=>'Sat, Mar 28','time'=>'12:00 PM','opponent'=>'AHFC 13B PREMIER 1C',              'location'=>'ZUBE PARK FIELD 2',     'status'=>'finished', 'score_home'=>0,  'score_away'=>2,  'cal_id'=>null],
        ['date'=>'Sat, Apr 11','time'=>'4:00 PM', 'opponent'=>'AHFC 13B PREMIER 1C',              'location'=>'ZUBE PARK FIELD 1',     'status'=>'upcoming', 'score_home'=>null,'score_away'=>null,'cal_id'=>6],
        ['date'=>'Sat, Apr 25','time'=>'4:00 PM', 'opponent'=>'NORTH SHORE FC SELECT WHITE 2013', 'location'=>'MEYER PARK FIELD 6',    'status'=>'upcoming', 'score_home'=>null,'score_away'=>null,'cal_id'=>7],
        ['date'=>'Sat, May 2', 'time'=>'12:00 PM','opponent'=>'CHALLENGE UNITED STXCL EC',        'location'=>'BURROUGHS PARK FIELD 7','status'=>'upcoming', 'score_home'=>null,'score_away'=>null,'cal_id'=>8],
      ];
      ?>

      <div class="vcf-table-wrap">
        <table class="vcf-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Time</th>
              <th>Opponent</th>
              <th>Venue</th>
              <th style="text-align:center">Score</th>
              <th></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($matches as $m):
              // Determine result badge
              $badge = '';
              if ($m['status'] === 'finished' && $m['score_home'] !== null) {
                if ($m['score_home'] > $m['score_away'])       { $badge = '<span class="vcf-result vcf-result--W">W</span>'; }
                elseif ($m['score_home'] < $m['score_away'])   { $badge = '<span class="vcf-result vcf-result--L">L</span>'; }
                else                                            { $badge = '<span class="vcf-result vcf-result--D">D</span>'; }
              }
              $score_html = ($m['score_home'] !== null)
                ? '<span class="score">'.(int)$m['score_home'].' – '.(int)$m['score_away'].'</span>'
                : '<span style="color:#444;">vs</span>';
              $row_class = $m['status'] === 'upcoming' ? 'upcoming' : '';
            ?>
            <tr class="<?= $row_class ?>">
              <td><?= htmlspecialchars($m['date']) ?></td>
              <td><?= htmlspecialchars($m['time']) ?></td>
              <td style="font-weight:600;color:<?= $m['status']==='upcoming' ? 'var(--vcf-lightgray)' : 'var(--vcf-gray)' ?>">
                <?= htmlspecialchars($m['opponent']) ?>
              </td>
              <td style="font-size:12px;"><?= htmlspecialchars($m['location']) ?></td>
              <td style="text-align:center;"><?= $score_html ?></td>
              <td><?= $badge ?></td>
              <td>
                <?php if ($m['status']==='upcoming' && $m['cal_id']): ?>
                  <a href="<?= $base_url ?>calendar.php?id=<?= (int)$m['cal_id'] ?>" class="vcf-cal-btn">+ Cal</a>
                <?php elseif ($m['status']==='upcoming'): ?>
                  <span class="vcf-badge-upcoming">Upcoming</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════════
     MATCH REELS
══════════════════════════════════════════ -->
<section id="reels">
  <div class="vcf-section">
    <div class="vcf-section__header">
      <h2 class="vcf-section__title">Match <em>Reels</em></h2>
    </div>
    <?php
    // ── Reemplaza con tus videos reales ──
    $reels = [
      ['file'=>'reel-69bf5f537601c.mp4','title'=>'Goal Clip'],
      ['file'=>'reel-69bf5e394c7af.mp4','title'=>'Goal Clip'],
      ['file'=>'reel-69bf5da085be8.mp4','title'=>'Goal Clip'],
      ['file'=>'reel-69bf5d52da036.mp4','title'=>'Goal Clip'],
      ['file'=>'reel-69af592254e20.mp4','title'=>'Goal Clip'],
      ['file'=>'reel-69af4b427ff59.mp4','title'=>'Golazo'],
      ['file'=>'reel-69af53cfd2a28.mp4','title'=>'Goal Clip'],
    ];
    ?>
    <div class="vcf-reels">
      <?php foreach ($reels as $r): ?>
      <div class="vcf-reel" onclick="this.querySelector('video').paused ? this.querySelector('video').play() : this.querySelector('video').pause()">
        <video src="assets/uploads/reels/<?= htmlspecialchars($r['file']) ?>"
               muted loop playsinline preload="metadata"></video>
        <div class="vcf-reel__overlay">
          <div class="vcf-reel__play">
            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
          </div>
          <div class="vcf-reel__title"><?= htmlspecialchars($r['title']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════════
     STAR OF THE MONTH
══════════════════════════════════════════ -->
<section id="star">
  <div class="vcf-section--dark">
    <div class="vcf-section__inner">
      <div class="vcf-section__header">
        <h2 class="vcf-section__title">VCF Star of <em>the Month</em></h2>
      </div>
      <div class="vcf-star">
        <span class="vcf-star__icon">&#9733;</span>
        <div class="vcf-star__coming">Recognizing hard work, discipline, and the VCF spirit.<br><br>Coming soon.</div>
      </div>
    </div>
  </div>
</section>


<?php include 'includes/footer.php'; ?>


<!-- ══════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════ -->
<script>
/* ── Countdown ── */
(function(){
  const el = document.getElementById('match-countdown');
  if (!el) return;
  const target = new Date(el.dataset.target);
  function tick(){
    const diff = target - new Date();
    if (diff <= 0){
      ['d','h','m','s'].forEach(k => document.getElementById('cd-'+k).textContent = '00');
      return;
    }
    document.getElementById('cd-d').textContent = String(Math.floor(diff/86400000)).padStart(2,'0');
    document.getElementById('cd-h').textContent = String(Math.floor((diff%86400000)/3600000)).padStart(2,'0');
    document.getElementById('cd-m').textContent = String(Math.floor((diff%3600000)/60000)).padStart(2,'0');
    document.getElementById('cd-s').textContent = String(Math.floor((diff%60000)/1000)).padStart(2,'0');
  }
  tick(); setInterval(tick, 1000);
})();

/* ── Hero bg zoom ── */
window.addEventListener('load', function(){
  const bg = document.getElementById('hero-bg');
  if (bg) bg.classList.add('loaded');
});

/* ── Player modal ── */
(function(){
  const modal   = document.getElementById('player-modal');
  const closeBtn = document.getElementById('modal-close');

  document.querySelectorAll('.vcf-player').forEach(function(card){
    card.addEventListener('click', function(){
      const p = JSON.parse(this.dataset.player);

      // Avatar
      const av = document.getElementById('modal-avatar');
      av.innerHTML = '';
      if (p.photo){
        const img = document.createElement('img');
        img.src = 'assets/uploads/' + p.photo;
        img.alt = p.name;
        img.onerror = function(){ this.remove(); av.textContent = p.initials; };
        av.appendChild(img);
      } else {
        av.textContent = p.initials;
      }

      document.getElementById('modal-num').textContent  = p.num || '';
      document.getElementById('modal-name').textContent = p.name;
      document.getElementById('modal-pos').textContent  = p.pos;
      document.getElementById('modal-apps').textContent    = p.apps;
      document.getElementById('modal-goals').textContent   = p.goals;
      document.getElementById('modal-assists').textContent = p.assists;
      document.getElementById('modal-motm').textContent    = p.motm;
      document.getElementById('modal-cs').textContent      = p.clean_sheets;

      // Skills
      ['pace','shoot','drib','def','phys','pass'].forEach(function(k){
        const val = parseInt(p[k]) || 0;
        setTimeout(function(){
          document.getElementById('skill-'+k).style.width  = (val*10)+'%';
        }, 80);
        document.getElementById('skillv-'+k).textContent = val;
      });

      modal.classList.add('open');
    });
  });

  function closeModal(){ modal.classList.remove('open'); }
  closeBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', function(e){ if(e.target === modal) closeModal(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });
})();

/* ── Formation viewer ── */
(function(){
  const formations = {
    '433': [
      {pos:'GK', x:50, y:88, desc:'Goalkeeper. Last line of defense. Commands the box, organizes the defense, and distributes play.'},
      {pos:'RB', x:18, y:70, desc:'Right Back. Defensive discipline with license to overlap and support wide attacks.'},
      {pos:'CB', x:38, y:72, desc:'Centre Back. Defends the central zone, wins aerial duels, starts build-up from deep.'},
      {pos:'CB', x:62, y:72, desc:'Centre Back. Aggressive in duels, reads the game, communicates with the backline.'},
      {pos:'LB', x:82, y:70, desc:'Left Back. Covers the left flank, joins attacks when possible, tracks back diligently.'},
      {pos:'CM', x:28, y:50, desc:'Central Midfielder. Engine of the team — tackles, links play, and supports both phases.'},
      {pos:'CM', x:50, y:44, desc:'Central Midfielder. Box-to-box. Controls tempo, presses high, arrives late into the box.'},
      {pos:'CM', x:72, y:50, desc:'Central Midfielder. Creative — turns, carries, and delivers through balls to the forwards.'},
      {pos:'RW', x:18, y:22, desc:'Right Winger. Pace and directness. Takes on defenders 1v1 and delivers dangerous crosses.'},
      {pos:'ST', x:50, y:14, desc:'Striker. Leads the line. Holds up play, makes runs in behind, and finishes chances.'},
      {pos:'LW', x:82, y:22, desc:'Left Winger. Cuts inside onto the stronger foot. Creates and finishes chances from wide.'},
    ],
    '442': [
      {pos:'GK', x:50, y:88, desc:'Goalkeeper.'},
      {pos:'RB', x:18, y:70, desc:'Right Back.'},
      {pos:'CB', x:38, y:72, desc:'Centre Back.'},
      {pos:'CB', x:62, y:72, desc:'Centre Back.'},
      {pos:'LB', x:82, y:70, desc:'Left Back.'},
      {pos:'RM', x:16, y:46, desc:'Right Midfielder. Wide presence, recovers and attacks down the flank.'},
      {pos:'CM', x:38, y:48, desc:'Central Midfielder. Defensive anchor — breaks up play and recycles possession.'},
      {pos:'CM', x:62, y:48, desc:'Central Midfielder. Drives forward, supports the strikers, dynamic runner.'},
      {pos:'LM', x:84, y:46, desc:'Left Midfielder. Wide presence, delivers crosses, and tracks back.'},
      {pos:'ST', x:36, y:18, desc:'Striker. Movement, hold-up play, and finishing.'},
      {pos:'ST', x:64, y:18, desc:'Striker. Partners the lead striker, makes runs in behind.'},
    ],
    '352': [
      {pos:'GK', x:50, y:88, desc:'Goalkeeper.'},
      {pos:'CB', x:28, y:72, desc:'Right Centre Back. In a back 3, wins duels and covers wide areas.'},
      {pos:'CB', x:50, y:75, desc:'Central Centre Back. Leader of the defensive line. Sweeper.'},
      {pos:'CB', x:72, y:72, desc:'Left Centre Back. Covers wide areas and is comfortable on the ball.'},
      {pos:'WB', x:12, y:50, desc:'Right Wing Back. Covers the entire flank — defends and attacks with energy.'},
      {pos:'CM', x:32, y:46, desc:'Central Midfielder. Box-to-box engine.'},
      {pos:'CM', x:50, y:42, desc:'Central Midfielder. Pivot — controls the tempo, screens the defense.'},
      {pos:'CM', x:68, y:46, desc:'Central Midfielder. Creative playmaker, carries from deep.'},
      {pos:'WB', x:88, y:50, desc:'Left Wing Back. Covers the entire left flank — defends and attacks.'},
      {pos:'ST', x:36, y:18, desc:'Striker. Leads the line.'},
      {pos:'ST', x:64, y:18, desc:'Second Striker. Support striker, links midfield and attack.'},
    ],
  };

  let currentFormation = '433';

  function renderFormation(key){
    const pitch = document.getElementById('vcf-pitch');
    // Remove old dots
    pitch.querySelectorAll('.vcf-pos').forEach(function(d){ d.remove(); });
    const positions = formations[key];
    positions.forEach(function(p){
      const div = document.createElement('div');
      div.className = 'vcf-pos';
      div.style.left = p.x + '%';
      div.style.top  = p.y + '%';
      div.innerHTML = '<div class="vcf-pos__dot">'+p.pos+'</div><span class="vcf-pos__name">'+p.pos+'</span>';
      div.addEventListener('click', function(){
        document.getElementById('pos-info-default').style.display = 'none';
        const detail = document.getElementById('pos-info-detail');
        detail.style.display = 'block';
        detail.classList.add('active');
        document.getElementById('pos-info-title').textContent = p.pos;
        document.getElementById('pos-info-desc').textContent  = p.desc;
      });
      pitch.appendChild(div);
    });
  }

  renderFormation(currentFormation);

  document.querySelectorAll('.vcf-formation__tab').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.vcf-formation__tab').forEach(function(b){ b.classList.remove('active'); });
      this.classList.add('active');
      currentFormation = this.dataset.formation;
      renderFormation(currentFormation);
      document.getElementById('pos-info-default').style.display = 'block';
      document.getElementById('pos-info-detail').style.display  = 'none';
    });
  });
})();
</script>
