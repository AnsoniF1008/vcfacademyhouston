<?php
/**
 * VCF valenciacf-style home layout (from redesign zip) + live DB data.
 */
$b = $base ?? '';
$heroBgSlides = [];
if (!empty($heroSlides) && is_array($heroSlides)) {
    foreach ($heroSlides as $sl) {
        $u = isset($sl['image_url']) ? trim((string) $sl['image_url']) : '';
        if ($u === '') {
            continue;
        }
        if (preg_match('#^https?://#i', $u)) {
            $heroBgSlides[] = $u;
        } else {
            $heroBgSlides[] = ($b === '' ? '' : $b) . '/' . ltrim($u, '/');
        }
    }
}
if (count($heroBgSlides) === 0 && file_exists(__DIR__ . '/../assets/img/hero.jpg')) {
    $heroBgSlides[] = ($b === '' ? '' : $b) . '/assets/img/hero.jpg';
}
$heroSlideCount = count($heroBgSlides);
$statsLine = 'VCF Houston';
if (!empty($categorias[0]['nombre'])) {
    $statsLine .= ' · ' . htmlspecialchars($categorias[0]['nombre']);
}
$ft = reset($juegosPorTorneo);
if ($ft) {
    $statsLine .= ' · ' . htmlspecialchars($ft['nombre_torneo']);
}
$seasonBarLabel = '';
if ($ft && !empty($ft['temporada'])) {
    $seasonBarLabel = htmlspecialchars($ft['temporada']);
} else {
    $y = (int) date('Y');
    $seasonBarLabel = $y . '–' . substr((string) ($y + 1), -2);
}
$next_match = null;
if (!empty($proximoJuego)) {
    $nm = $proximoJuego;
    $gameTs = strtotime($nm['fecha'] . ' ' . ($nm['hora'] ?? '12:00:00'));
    $next_match = [
        'date_label' => strtoupper(date('D M j', $gameTs)),
        'time' => date('g:i A', $gameTs),
        'opponent' => $nm['rival'] ?: 'TBD',
        'venue' => $nm['sede_nombre'] ?? $nm['cancha'] ?? '',
        'datetime' => date('c', $gameTs),
    ];
}
?>
<section class="vcf-hero" id="hero">
  <?php if ($heroSlideCount > 0): ?>
  <div class="vcf-hero__slides" id="hero-slides">
    <?php foreach ($heroBgSlides as $i => $slideUrl): ?>
    <div class="vcf-hero__slide<?= $i === 0 ? ' active' : '' ?>" style="background-image:url('<?= htmlspecialchars($slideUrl, ENT_QUOTES) ?>');"></div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="vcf-hero__bg vcf-hero__bg--empty" id="hero-bg" aria-hidden="true"></div>
  <?php endif; ?>
  <div class="vcf-hero__overlay"></div>
  <div class="vcf-hero__pattern"></div>

  <?php if ($heroSlideCount > 1): ?>
  <div class="vcf-hero__dots" id="hero-dots" role="tablist" aria-label="Hero slides">
    <?php foreach ($heroBgSlides as $i => $_): ?>
    <button type="button" class="vcf-hero__dot<?= $i === 0 ? ' active' : '' ?>" data-index="<?= (int) $i ?>" aria-label="Slide <?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>></button>
    <?php endforeach; ?>
  </div>
  <button type="button" class="vcf-hero__arrow vcf-hero__arrow--prev" id="hero-prev" aria-label="Previous slide">&#8249;</button>
  <button type="button" class="vcf-hero__arrow vcf-hero__arrow--next" id="hero-next" aria-label="Next slide">&#8250;</button>
  <?php endif; ?>

  <div class="vcf-hero__content">
    <div class="vcf-hero__tag">Official VCF Academy Program</div>
    <div class="vcf-hero__eyebrow">Join &middot; Compete &middot; Grow</div>
    <h1 class="vcf-hero__title">VCF Academy<br><em>Houston</em></h1>
    <p class="vcf-hero__tagline">Sentiment &middot; Courage &middot; Fight</p>

    <div class="vcf-match-widget">
      <div>
        <?php if ($next_match): ?>
        <div class="vcf-match-widget__date">
          <?= htmlspecialchars($next_match['date_label']) ?> &middot; <?= htmlspecialchars($next_match['time']) ?>
        </div>
        <div class="vcf-match-widget__teams">
          <span class="vcf-match-widget__team">VCF Houston</span>
          <span class="vcf-match-widget__vs">vs</span>
          <span class="vcf-match-widget__team" style="color:var(--vcf-lightgray)"><?= htmlspecialchars($next_match['opponent']) ?></span>
        </div>
        <div class="vcf-match-widget__venue"><?= htmlspecialchars($next_match['venue']) ?></div>
        <?php else: ?>
        <div class="vcf-match-widget__date">Next match</div>
        <p style="font-size:13px;color:var(--vcf-gray);margin:8px 0 0;">No upcoming match scheduled yet. Check the fixtures below.</p>
        <?php endif; ?>
      </div>

      <?php if ($next_match): ?>
      <div class="vcf-match-widget__countdown" id="match-countdown" data-target="<?= htmlspecialchars($next_match['datetime']) ?>" data-kind="match">
        <div class="vcf-count"><span class="vcf-count__num" id="cd-d">--</span><span class="vcf-count__label">Days</span></div>
        <div class="vcf-count"><span class="vcf-count__num" id="cd-h">--</span><span class="vcf-count__label">Hrs</span></div>
        <div class="vcf-count"><span class="vcf-count__num" id="cd-m">--</span><span class="vcf-count__label">Min</span></div>
        <div class="vcf-count"><span class="vcf-count__num" id="cd-s">--</span><span class="vcf-count__label">Sec</span></div>
      </div>
      <?php endif; ?>

      <div class="vcf-match-widget__btns">
        <a href="<?= htmlspecialchars($b) ?>/index.php#tournaments" class="vcf-match-widget__btn vcf-match-widget__btn--primary">Details &rarr;</a>
        <a href="<?= htmlspecialchars($b) ?>/calendar.php" class="vcf-match-widget__btn vcf-match-widget__btn--secondary">Calendar</a>
      </div>
    </div>
  </div>
</section>

<div class="vcf-statsbar">
  <div class="vcf-statsbar__label">
    <span>Season <?= $seasonBarLabel ?></span>
    <span><?= $statsLine ?></span>
  </div>
  <div class="vcf-statsbar__grid">
    <div class="vcf-stat"><div class="vcf-stat__val pos"><?= (int) $seasonStats['W'] ?></div><div class="vcf-stat__lbl">Wins</div></div>
    <div class="vcf-stat"><div class="vcf-stat__val neg"><?= (int) $seasonStats['L'] ?></div><div class="vcf-stat__lbl">Losses</div></div>
    <div class="vcf-stat"><div class="vcf-stat__val"><?= (int) $seasonStats['D'] ?></div><div class="vcf-stat__lbl">Draws</div></div>
    <div class="vcf-stat"><div class="vcf-stat__val"><?= (int) $seasonStats['GF'] ?></div><div class="vcf-stat__lbl">Goals For</div></div>
    <div class="vcf-stat"><div class="vcf-stat__val"><?= (int) $seasonStats['GA'] ?></div><div class="vcf-stat__lbl">Goals Ag.</div></div>
    <div class="vcf-stat"><div class="vcf-stat__val accent"><?= (int) $seasonStats['PTS'] ?></div><div class="vcf-stat__lbl">Points</div></div>
  </div>
</div>

<?php require __DIR__ . '/partials/home-motm.php'; ?>

<?php
$vm_dna_loc = 'Katy, TX — Houston area';
if (!empty($sedes[0]['direccion'])) {
    $vm_dna_loc = $sedes[0]['direccion'];
} elseif (!empty($sedes[0]['nombre'])) {
    $vm_dna_loc = $sedes[0]['nombre'] . ' — Houston area';
}
$vm_dna_cats = count($categorias) > 0
    ? implode(' · ', array_column($categorias, 'nombre'))
    : 'Youth Academy';
$vm_gps_url = !empty($sedes[0]['mapa_general_url']) ? $sedes[0]['mapa_general_url'] : '';
?>
<section id="methodology" class="vcf-methodology">
  <div style="max-width:var(--page-max);margin:0 auto;padding:44px var(--page-pad);border-bottom:1px solid var(--vcf-border);">
    <div class="vcf-section__header">
      <h2 class="vcf-section__title">Our <em>Methodology</em></h2>
    </div>
    <p class="vcf-methodology__lead">
      <em class="vcf-methodology__quote">"Educating People, Training Footballers"</em> —
      The three official VCF Academy pillars that guide everything we do in Houston.
      Every session, every match, every player.
    </p>

    <div class="vm-pillars">
      <div class="vm-pillar" data-num="01">
        <span class="vm-pillar__icon" aria-hidden="true">🦇</span>
        <div class="vm-pillar__title">Identity</div>
        <p class="vm-pillar__desc">Building the Valencia CF identity in every young player — values, culture, pride and commitment to the crest. On and off the pitch.</p>
      </div>
      <div class="vm-pillar" data-num="02">
        <span class="vm-pillar__icon" aria-hidden="true">💪</span>
        <div class="vm-pillar__title">Effort</div>
        <p class="vm-pillar__desc">No shortcuts. Hard work and dedication in every training session, every matchday, every sprint, every rep. That is the VCF standard.</p>
      </div>
      <div class="vm-pillar" data-num="03">
        <span class="vm-pillar__icon" aria-hidden="true">🧠</span>
        <div class="vm-pillar__title">Intelligence</div>
        <p class="vm-pillar__desc">Tactical awareness and smart decision-making — reading the game, knowing your role, and anticipating play before it happens.</p>
      </div>
    </div>

    <div class="vm-dna">
      <div class="vm-dna__item">
        <span class="vm-dna__icon" aria-hidden="true">🏆</span>
        <span class="vm-dna__text">Official VCF Academy Program</span>
      </div>
      <div class="vm-dna__item">
        <span class="vm-dna__icon" aria-hidden="true">📍</span>
        <span class="vm-dna__text"><?= htmlspecialchars($vm_dna_loc) ?></span>
      </div>
      <div class="vm-dna__item">
        <span class="vm-dna__icon" aria-hidden="true">⚽</span>
        <span class="vm-dna__text"><?= htmlspecialchars($vm_dna_cats) ?></span>
      </div>
    </div>

    <?php if (count($categorias) > 0): ?>
    <div class="vm-schedules-label">Training Schedules</div>
    <div class="vm-schedules">
      <?php foreach ($categorias as $ci => $cat): ?>
      <div class="vm-schedule">
        <div class="vm-schedule__cat"><?= htmlspecialchars($cat['nombre']) ?></div>
        <div class="vm-schedule__rows">
          <div class="vm-schedule__row vm-schedule__row--block">
            <span class="vm-schedule__day">Schedule</span>
            <span class="vm-schedule__horario"><?= nl2br(htmlspecialchars($cat['horarios_entrenamiento'] ?? 'Schedule TBD', ENT_QUOTES, 'UTF-8')) ?></span>
          </div>
        </div>
        <?php if ($ci === 0 && $vm_gps_url !== ''): ?>
        <a href="<?= htmlspecialchars($vm_gps_url) ?>" target="_blank" rel="noopener noreferrer" class="vm-schedule__gps">
          <svg width="11" height="11" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2a6 6 0 0 0-6 6c0 4 6 10 6 10s6-6 6-10a6 6 0 0 0-6-6zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/></svg>
          Open GPS
        </a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (count($categorias) === 1): ?>
    <p class="vm-footnote">Currently: <?= htmlspecialchars($categorias[0]['nombre']) ?>. Additional age groups coming soon.</p>
    <?php endif; ?>
  </div>
</section>

<section id="formation">
  <div class="vcf-section--dark">
    <div class="vcf-section__inner">
      <div class="vcf-section__header">
        <h2 class="vcf-section__title">Know Your <em>Role</em></h2>
      </div>
      <p style="font-size:13px;color:var(--vcf-gray);margin-bottom:22px;max-width:520px;line-height:1.6;">
        Tap a position to learn about the role and see the assigned player. Switch formations to compare systems.
      </p>
      <div class="vcf-formation__tabs" id="formation-tabs">
        <button type="button" class="vcf-formation__tab active" data-f="433">4-3-3</button>
        <button type="button" class="vcf-formation__tab" data-f="442">4-4-2</button>
        <button type="button" class="vcf-formation__tab" data-f="352">3-5-2</button>
      </div>
      <div class="vfv-grid">
        <div>
          <div class="vfv-pitch" id="vfv-pitch">
            <svg class="vfv-pitch-svg" viewBox="0 0 100 60" preserveAspectRatio="none" aria-hidden="true">
              <rect x="1.5" y="1.5" width="97" height="57" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="0.4"/>
              <line x1="1.5" y1="30" x2="98.5" y2="30" stroke="rgba(255,255,255,0.08)" stroke-width="0.4"/>
              <circle cx="50" cy="30" r="9" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="0.4"/>
              <circle cx="50" cy="30" r="0.6" fill="rgba(255,255,255,0.2)"/>
              <rect x="1.5" y="14" width="16" height="32" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.4"/>
              <rect x="82.5" y="14" width="16" height="32" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.4"/>
              <rect x="1.5" y="21" width="6" height="18" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="0.4"/>
              <rect x="92.5" y="21" width="6" height="18" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="0.4"/>
              <g id="vfv-lines" opacity="0.18"></g>
            </svg>
          </div>
          <div class="vfv-strip-label">Assigned players — tap to highlight on pitch</div>
          <div class="vfv-strip" id="vfv-strip"></div>
        </div>
        <div class="vfv-panel">
          <div class="vfv-detail" id="vfv-detail">
            <div class="vfv-placeholder">
              <div class="vfv-placeholder__icon">&#9917;</div>
              <div class="vfv-placeholder__text">Select a position</div>
              <div class="vfv-placeholder__sub">Tap any dot on the pitch</div>
            </div>
          </div>
          <div>
            <div class="vfv-strip-label">Season stats</div>
            <div class="vfv-mini-stats">
              <div class="vfv-mini-stat"><span class="vfv-mini-val pos"><?= (int) ($seasonStats['W'] ?? 0) ?></span><span class="vfv-mini-lbl">Wins</span></div>
              <div class="vfv-mini-stat"><span class="vfv-mini-val neg"><?= (int) ($seasonStats['L'] ?? 0) ?></span><span class="vfv-mini-lbl">Losses</span></div>
              <div class="vfv-mini-stat"><span class="vfv-mini-val"><?= (int) ($seasonStats['D'] ?? 0) ?></span><span class="vfv-mini-lbl">Draws</span></div>
              <div class="vfv-mini-stat"><span class="vfv-mini-val"><?= (int) ($seasonStats['GF'] ?? 0) ?></span><span class="vfv-mini-lbl">GF</span></div>
              <div class="vfv-mini-stat"><span class="vfv-mini-val"><?= (int) ($seasonStats['GA'] ?? 0) ?></span><span class="vfv-mini-lbl">GA</span></div>
              <div class="vfv-mini-stat"><span class="vfv-mini-val accent"><?= (int) ($seasonStats['PTS'] ?? 0) ?></span><span class="vfv-mini-lbl">PTS</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<script type="application/json" id="vfv-formation-data"><?php
echo json_encode(['players' => $formation_players], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
?></script>

<?php require __DIR__ . '/partials/home-grounds.php'; ?>

<?php require __DIR__ . '/partials/home-roster-block.php'; ?>

<?php require __DIR__ . '/partials/home-tournaments-stats.php'; ?>

<?php if (!empty($reels) && is_array($reels) && count($reels) > 0): ?>
<section id="reels" class="vcf-reels-section" aria-label="Match Reels">
  <div class="vcf-reels-inner">
    <div class="vcf-reels-header">
      <span class="vcf-reels-header-bar" aria-hidden="true"></span>
      <h2 class="vcf-reels-title">MATCH <span>REELS</span></h2>
    </div>

    <div class="vcf-reels-grid">
      <?php foreach ($reels as $i => $r): ?>
      <?php
      $num = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
      $caption = trim((string) ($r['caption'] ?? ''));
      $videoUrl = htmlspecialchars($b . '/' . ltrim((string) ($r['video_url'] ?? ''), '/'));
      $titleLower = strtolower($caption);
      $type = 'highlight';
      if (strpos($titleLower, 'gol') !== false) {
          $type = 'golazo';
      } elseif (strpos($titleLower, 'train') !== false || strpos($titleLower, 'entren') !== false) {
          $type = 'entrenamiento';
      }
      $badgeText = 'HIGHLIGHT';
      if ($type === 'golazo') {
          $badgeText = 'GOLAZO';
      } elseif ($type === 'entrenamiento') {
          $badgeText = 'TRAINING';
      }
      ?>
      <article class="vcf-reel-card" data-vcf-reel-card data-index="<?= (int) $i ?>">
        <video
          class="vcf-reel-media"
          src="<?= $videoUrl ?>"
          muted
          loop
          playsinline
          preload="metadata"
          aria-label="<?= htmlspecialchars($caption !== '' ? $caption : ('Match reel ' . $num)) ?>"
        ></video>
        <div class="vcf-reel-overlay" aria-hidden="true"></div>
        <div class="vcf-reel-play-wrap" aria-hidden="true">
          <div class="vcf-reel-play-btn">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="white" aria-hidden="true">
              <polygon points="3,1 14,8 3,15"></polygon>
            </svg>
          </div>
        </div>
        <span class="vcf-reel-badge vcf-reel-badge--<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($badgeText) ?></span>
        <span class="vcf-reel-num" aria-hidden="true">#<?= htmlspecialchars($num) ?></span>
        <?php if ($caption !== ''): ?>
        <span class="vcf-reel-title"><?= htmlspecialchars($caption) ?></span>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/partials/home-star.php'; ?>
