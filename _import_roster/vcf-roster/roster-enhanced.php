<?php
/**
 * VCF Academy Houston — Roster Enhanced
 * =======================================
 * Drop-in replacement para la sección #roster en tu index.php
 *
 * INSTRUCCIONES:
 * 1. En tu index.php busca la sección: <section id="roster">
 * 2. Reemplaza TODO ese bloque con este código
 * 3. Ajusta el array $players con los datos reales de tu DB
 *
 * MEJORAS INCLUIDAS:
 * - Cards verticales estilo FIFA (foto full + barra inferior)
 * - Filtro por posición: All / GK / DEF / MID / FWD
 * - Búsqueda por nombre en tiempo real
 * - Leaderboard: Top Scorers / Top Assists / MOTM
 * - Posiciones en inglés (corregidas)
 * - Badge "Star" para jugador del mes
 * - Hover overlay con "View Stats →"
 */

// ── JUGADORES ──────────────────────────────────────────────────
// Ajusta estos datos con los de tu base de datos.
// 'group' => 'GK' | 'DEF' | 'MID' | 'FWD'  (para el filtro)
// 'pos'   => posición corta en inglés
// 'pos_full' => posición completa en inglés
// 'star'  => true si es Star of the Month
// 'captain' => true si es capitán
$players = [
  // — Sin foto —
  ['num'=>'',  'initials'=>'RB','name'=>'Rodrigo Bermudez Portillo', 'pos'=>'GK',  'pos_full'=>'Goalkeeper',              'group'=>'GK',  'photo'=>'',                          'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>5,'shoot'=>3,'drib'=>4,'def'=>5,'phys'=>6,'pass'=>5,'star'=>false,'captain'=>false],
  ['num'=>'',  'initials'=>'JD','name'=>'Jacob Diaz',                'pos'=>'RW',  'pos_full'=>'Forward · Right Winger',  'group'=>'FWD', 'photo'=>'',                          'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>8,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>6,'star'=>false,'captain'=>false],
  ['num'=>'',  'initials'=>'JF','name'=>'Justin Flores',             'pos'=>'CDM', 'pos_full'=>'Midfielder · Defensive',  'group'=>'MID', 'photo'=>'',                          'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>5,'drib'=>5,'def'=>7,'phys'=>7,'pass'=>7,'star'=>false,'captain'=>false],
  ['num'=>'',  'initials'=>'AH','name'=>'Antoine Haddad',            'pos'=>'CB',  'pos_full'=>'Defender · Left CB',      'group'=>'DEF', 'photo'=>'',                          'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>4,'drib'=>4,'def'=>8,'phys'=>7,'pass'=>5,'star'=>false,'captain'=>false],
  ['num'=>'',  'initials'=>'JL','name'=>'Juan Lopez Rincon',         'pos'=>'DEF', 'pos_full'=>'Defender',                'group'=>'DEF', 'photo'=>'',                          'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>4,'drib'=>4,'def'=>7,'phys'=>6,'pass'=>5,'star'=>false,'captain'=>false],
  ['num'=>'',  'initials'=>'AM','name'=>'Alessandro Montilla Mejilla','pos'=>'RB', 'pos_full'=>'Defender · Right Back',   'group'=>'DEF', 'photo'=>'',                          'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>4,'drib'=>5,'def'=>7,'phys'=>6,'pass'=>6,'star'=>false,'captain'=>false],
  // — Con foto —
  ['num'=>'2', 'initials'=>'LG','name'=>'Leonardo Garcia Fuenmayor', 'pos'=>'LW',  'pos_full'=>'Forward · Left Winger',   'group'=>'FWD', 'photo'=>'roster-69b02caa5d404.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>8,'shoot'=>6,'drib'=>8,'def'=>3,'phys'=>5,'pass'=>6,'star'=>false,'captain'=>false],
  ['num'=>'6', 'initials'=>'SB','name'=>'Sebastian Brito-Stirpe',    'pos'=>'RB',  'pos_full'=>'Defender · Right Back',   'group'=>'DEF', 'photo'=>'roster-69af7584136ff.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>4,'drib'=>5,'def'=>7,'phys'=>6,'pass'=>6,'star'=>false,'captain'=>false],
  ['num'=>'11','initials'=>'AM','name'=>'Alexander Morejon',         'pos'=>'LW',  'pos_full'=>'Forward · Left Winger',   'group'=>'FWD', 'photo'=>'roster-69af578163a2a.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>8,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>6,'star'=>false,'captain'=>false],
  ['num'=>'14','initials'=>'SY','name'=>'Santiago Yepez Moreno',     'pos'=>'CM',  'pos_full'=>'Midfielder · Left CM',    'group'=>'MID', 'photo'=>'roster-69af6f07b414b.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>6,'drib'=>7,'def'=>5,'phys'=>6,'pass'=>7,'star'=>false,'captain'=>false],
  ['num'=>'16','initials'=>'VO','name'=>'Vicente Ortegano Bracho',   'pos'=>'RW',  'pos_full'=>'Forward · Right Winger',  'group'=>'FWD', 'photo'=>'roster-69b0a16b5e874.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>5,'star'=>false,'captain'=>false],
  ['num'=>'20','initials'=>'MB','name'=>'Mateo Briceno Diaz',        'pos'=>'LW',  'pos_full'=>'Forward · Left Winger',   'group'=>'FWD', 'photo'=>'roster-69b098dcb966b.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>5,'star'=>false,'captain'=>false],
  ['num'=>'23','initials'=>'MA','name'=>'Matías Astorino',           'pos'=>'GK',  'pos_full'=>'Goalkeeper',              'group'=>'GK',  'photo'=>'roster-69af42a639f04.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>5,'shoot'=>3,'drib'=>4,'def'=>5,'phys'=>6,'pass'=>5,'star'=>false,'captain'=>false],
  ['num'=>'29','initials'=>'MM','name'=>'Mateo Mata',                'pos'=>'RW',  'pos_full'=>'Forward · Right Winger',  'group'=>'FWD', 'photo'=>'roster-69aa27b820910.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>8,'shoot'=>7,'drib'=>8,'def'=>3,'phys'=>5,'pass'=>6,'star'=>false,'captain'=>false],
  ['num'=>'31','initials'=>'MG','name'=>'Miguel Gonzalez Gonzalez',  'pos'=>'CB',  'pos_full'=>'Defender · Right CB',     'group'=>'DEF', 'photo'=>'roster-69ab621c869f2.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>4,'drib'=>4,'def'=>8,'phys'=>7,'pass'=>5,'star'=>false,'captain'=>false],
  ['num'=>'45','initials'=>'JG','name'=>'Jayden Gomez',              'pos'=>'LB',  'pos_full'=>'Defender · Left Back',    'group'=>'DEF', 'photo'=>'roster-69b099cdb1035.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>4,'drib'=>5,'def'=>7,'phys'=>6,'pass'=>6,'star'=>false,'captain'=>false],
  ['num'=>'48','initials'=>'FP','name'=>'Fabian Perez Marcano',      'pos'=>'LW',  'pos_full'=>'Forward · Left Winger',   'group'=>'FWD', 'photo'=>'roster-69af431c21c12.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>5,'star'=>false,'captain'=>false],
  ['num'=>'67','initials'=>'MA','name'=>'Manuel Alvarez',            'pos'=>'LW',  'pos_full'=>'Forward · Left Winger',   'group'=>'FWD', 'photo'=>'roster-69b09977384cd.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>6,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>5,'star'=>false,'captain'=>false],
  ['num'=>'72','initials'=>'TB','name'=>'Tiziano Barrio',            'pos'=>'CM',  'pos_full'=>'Midfielder · Right CM',   'group'=>'MID', 'photo'=>'roster-69b709db5e85f.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>6,'drib'=>7,'def'=>5,'phys'=>6,'pass'=>7,'star'=>false,'captain'=>false],
  ['num'=>'80','initials'=>'JM','name'=>'Juan Marco',                'pos'=>'RW',  'pos_full'=>'Forward · Right Winger',  'group'=>'FWD', 'photo'=>'roster-69af6fdc9493b.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>7,'shoot'=>7,'drib'=>7,'def'=>3,'phys'=>5,'pass'=>6,'star'=>false,'captain'=>false],
  ['num'=>'93','initials'=>'JM','name'=>'Juan Morales',              'pos'=>'CM',  'pos_full'=>'Midfielder · Left CM',    'group'=>'MID', 'photo'=>'roster-69b0a15194846.jpg', 'apps'=>0,'goals'=>0,'assists'=>0,'motm'=>0,'clean_sheets'=>0,'pace'=>6,'shoot'=>5,'drib'=>6,'def'=>6,'phys'=>6,'pass'=>7,'star'=>false,'captain'=>false],
];

// ── LEADERBOARD: top 3 por categoría ──────────────────────────
function top3($players, $stat) {
  $sorted = $players;
  usort($sorted, fn($a,$b) => $b[$stat] - $a[$stat]);
  return array_slice($sorted, 0, 3);
}
$top_scorers  = top3($players, 'goals');
$top_assists  = top3($players, 'assists');
$top_motm     = top3($players, 'motm');
$has_any_stat = array_sum(array_column($players,'goals')) > 0
             || array_sum(array_column($players,'assists')) > 0;
?>

<!-- ══════════════════════════════════════════
     ROSTER SECTION — Enhanced
══════════════════════════════════════════ -->
<section id="roster">
<div style="border-bottom:1px solid var(--vcf-border);">
<div style="max-width:var(--page-max);margin:0 auto;padding:44px var(--page-pad);">

  <!-- Header -->
  <div class="vcf-section__header">
    <h2 class="vcf-section__title">Roster <em>B13</em></h2>
    <span style="font-family:var(--font-display);font-size:11px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:#555;">
      <?= count($players) ?> Players · Season 2025–26
    </span>
  </div>

  <?php if ($has_any_stat): ?>
  <!-- ── LEADERBOARD ── -->
  <div class="vr-leaderboard">
    <?php
    $boards = [
      ['title'=>'Top Scorers',   'icon'=>'⚽', 'data'=>$top_scorers,  'stat'=>'goals',   'lbl'=>'goals'],
      ['title'=>'Top Assists',   'icon'=>'🎯', 'data'=>$top_assists,  'stat'=>'assists',  'lbl'=>'ast'],
      ['title'=>'Man of the Match','icon'=>'⭐','data'=>$top_motm,    'stat'=>'motm',    'lbl'=>'MOTM'],
    ];
    foreach ($boards as $b):
    ?>
    <div class="vr-board">
      <div class="vr-board__title"><?= $b['icon'] ?> <?= $b['title'] ?></div>
      <?php foreach ($b['data'] as $rank => $p): ?>
      <div class="vr-board__row <?= $rank===0?'top':'' ?>">
        <span class="vr-board__rank"><?= $rank+1 ?></span>
        <div class="vr-board__av">
          <?php if (!empty($p['photo'])): ?>
            <img src="assets/uploads/<?= htmlspecialchars($p['photo']) ?>"
                 alt="<?= htmlspecialchars($p['name']) ?>"
                 onerror="this.style.display='none';this.nextSibling.style.display='flex'">
            <span style="display:none"><?= htmlspecialchars($p['initials']) ?></span>
          <?php else: ?>
            <?= htmlspecialchars($p['initials']) ?>
          <?php endif; ?>
        </div>
        <span class="vr-board__name"><?= htmlspecialchars(explode(' ',$p['name'])[0]) ?> <?= htmlspecialchars(explode(' ',$p['name'])[1]??'') ?></span>
        <span class="vr-board__val"><?= (int)$p[$b['stat']] ?></span>
        <span class="vr-board__lbl"><?= $b['lbl'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── SEARCH + FILTER ── -->
  <div class="vr-controls">
    <div class="vr-search-wrap">
      <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" style="color:#555;flex-shrink:0;"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
      <input type="text" id="vr-search" placeholder="Search player..." class="vr-search" autocomplete="off" spellcheck="false">
      <button id="vr-search-clear" class="vr-search-clear" style="display:none">&#x2715;</button>
    </div>
    <div class="vr-filters" id="vr-filters">
      <button class="vr-filter active" data-group="all">All <span class="vr-filter__count"><?= count($players) ?></span></button>
      <button class="vr-filter" data-group="GK">GK <span class="vr-filter__count"><?= count(array_filter($players,fn($p)=>$p['group']==='GK')) ?></span></button>
      <button class="vr-filter" data-group="DEF">DEF <span class="vr-filter__count"><?= count(array_filter($players,fn($p)=>$p['group']==='DEF')) ?></span></button>
      <button class="vr-filter" data-group="MID">MID <span class="vr-filter__count"><?= count(array_filter($players,fn($p)=>$p['group']==='MID')) ?></span></button>
      <button class="vr-filter" data-group="FWD">FWD <span class="vr-filter__count"><?= count(array_filter($players,fn($p)=>$p['group']==='FWD')) ?></span></button>
    </div>
  </div>

  <!-- ── ROSTER GRID ── -->
  <div class="vr-grid" id="vr-grid">
    <?php foreach ($players as $i => $p): ?>
    <div class="vr-card"
         data-index="<?= $i ?>"
         data-group="<?= htmlspecialchars($p['group']) ?>"
         data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>"
         data-player='<?= htmlspecialchars(json_encode([
           'num'      => $p['num'],
           'initials' => $p['initials'],
           'name'     => $p['name'],
           'pos'      => $p['pos'],
           'pos_full' => $p['pos_full'],
           'photo'    => !empty($p['photo']) ? 'assets/uploads/'.$p['photo'] : '',
           'apps'     => $p['apps'],
           'goals'    => $p['goals'],
           'assists'  => $p['assists'],
           'motm'     => $p['motm'],
           'clean_sheets' => $p['clean_sheets'],
           'pace'  => $p['pace'],
           'shoot' => $p['shoot'],
           'drib'  => $p['drib'],
           'def'   => $p['def'],
           'phys'  => $p['phys'],
           'pass'  => $p['pass'],
         ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'>

      <!-- Photo area -->
      <div class="vr-card__photo">
        <?php if (!empty($p['photo'])): ?>
          <img src="assets/uploads/<?= htmlspecialchars($p['photo']) ?>"
               alt="<?= htmlspecialchars($p['name']) ?>"
               loading="lazy"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="vr-card__initials" style="display:none"><?= htmlspecialchars($p['initials']) ?></div>
        <?php else: ?>
          <div class="vr-card__initials"><?= htmlspecialchars($p['initials']) ?></div>
        <?php endif; ?>

        <!-- Number -->
        <?php if (!empty($p['num'])): ?>
          <div class="vr-card__num"><?= htmlspecialchars($p['num']) ?></div>
        <?php endif; ?>

        <!-- Badges -->
        <?php if ($p['star']): ?>
          <div class="vr-card__badge vr-card__badge--star">&#9733; Star</div>
        <?php endif; ?>
        <?php if ($p['captain']): ?>
          <div class="vr-card__badge vr-card__badge--cap">C</div>
        <?php endif; ?>

        <!-- Hover overlay -->
        <div class="vr-card__overlay">
          <span>View Stats</span>
        </div>
      </div>

      <!-- Info bar -->
      <div class="vr-card__bar">
        <div class="vr-card__name">
          <?= htmlspecialchars(explode(' ',$p['name'])[0]) ?>
          <?= htmlspecialchars(explode(' ',$p['name'])[1]??'') ?>
        </div>
        <div class="vr-card__pos"><?= htmlspecialchars($p['pos']) ?><?= $p['goals']>0 ? ' · '.$p['goals'].' ⚽' : '' ?></div>
      </div>

    </div>
    <?php endforeach; ?>

    <!-- Empty state -->
    <div class="vr-empty" id="vr-empty" style="display:none;">
      <div class="vr-empty__icon">&#128269;</div>
      <div class="vr-empty__text">No players found</div>
    </div>
  </div>

</div>
</div>
</section>

<!-- ══ PLAYER MODAL ══ -->
<div class="vr-modal-bg" id="vr-modal">
  <div class="vr-modal">
    <button class="vr-modal__close" id="vr-modal-close">&#x2715;</button>
    <div class="vr-modal__left">
      <div class="vr-modal__photo" id="vr-modal-photo"></div>
      <div class="vr-modal__num"   id="vr-modal-num"></div>
    </div>
    <div class="vr-modal__right">
      <div class="vr-modal__pos"  id="vr-modal-pos"></div>
      <div class="vr-modal__name" id="vr-modal-name"></div>
      <div class="vr-modal__stats">
        <div class="vr-modal__stat"><div class="vr-modal__stat-val" id="vm-apps">0</div><div class="vr-modal__stat-lbl">Apps</div></div>
        <div class="vr-modal__stat"><div class="vr-modal__stat-val" id="vm-goals">0</div><div class="vr-modal__stat-lbl">Goals</div></div>
        <div class="vr-modal__stat"><div class="vr-modal__stat-val" id="vm-ast">0</div><div class="vr-modal__stat-lbl">Assists</div></div>
        <div class="vr-modal__stat"><div class="vr-modal__stat-val" id="vm-motm">0</div><div class="vr-modal__stat-lbl">MOTM</div></div>
        <div class="vr-modal__stat"><div class="vr-modal__stat-val" id="vm-cs">0</div><div class="vr-modal__stat-lbl">CS</div></div>
      </div>
      <div class="vr-skills">
        <div style="font-family:var(--font-display);font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#555;margin-bottom:10px;">Skills (1–10)</div>
        <?php foreach (['pace'=>'Pace','shoot'=>'Shoot','drib'=>'Drib','def'=>'Def','phys'=>'Phys','pass'=>'Pass'] as $k=>$lbl): ?>
        <div class="vr-skill">
          <span class="vr-skill__lbl"><?= $lbl ?></span>
          <div class="vr-skill__bar"><div class="vr-skill__fill" id="vrs-<?= $k ?>" style="width:0%"></div></div>
          <span class="vr-skill__val" id="vrsv-<?= $k ?>">0</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>


<!-- ══ CSS ══ -->
<style>
/* ── FIFA-STYLE CARDS ── */
.vr-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 3px;
}
.vr-card {
  background: #181818;
  cursor: pointer;
  position: relative;
  border-radius: 2px;
  overflow: hidden;
  transition: transform 0.18s;
}
.vr-card:hover { transform: translateY(-3px); }
.vr-card[data-visible="false"] { display: none; }

/* Photo area */
.vr-card__photo {
  aspect-ratio: 3 / 4;
  background: linear-gradient(145deg, #1E1916 0%, #2a2420 100%);
  position: relative;
  overflow: hidden;
}
.vr-card__photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top center;
  display: block;
  transition: transform 0.4s ease;
}
.vr-card:hover .vr-card__photo img { transform: scale(1.05); }
.vr-card__initials {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 32px;
  font-weight: 900;
  color: rgba(255,107,0,0.25);
}

/* Number badge */
.vr-card__num {
  position: absolute;
  top: 8px;
  right: 10px;
  font-family: var(--font-display);
  font-size: 28px;
  font-weight: 900;
  color: rgba(255,255,255,0.07);
  line-height: 1;
  pointer-events: none;
  user-select: none;
}

/* Star / Captain badges */
.vr-card__badge {
  position: absolute;
  top: 8px;
  left: 8px;
  font-family: var(--font-display);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 2px 7px;
  border-radius: 2px;
}
.vr-card__badge--star { background: #FF6B00; color: #fff; }
.vr-card__badge--cap  { background: rgba(255,255,255,0.15); color: #fff; }

/* Hover overlay */
.vr-card__overlay {
  position: absolute;
  inset: 0;
  background: rgba(255,107,0,0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.18s;
}
.vr-card:hover .vr-card__overlay { opacity: 1; }
.vr-card__overlay span {
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  background: #FF6B00;
  color: #fff;
  padding: 5px 12px;
  border-radius: 2px;
}

/* Info bar */
.vr-card__bar {
  background: #111;
  border-top: 2px solid #FF6B00;
  padding: 8px 10px 9px;
}
.vr-card__name {
  font-family: var(--font-display);
  font-size: 11.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #F5F0E8;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.vr-card__pos {
  font-family: var(--font-display);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #FF6B00;
  margin-top: 2px;
}

/* Empty state */
.vr-empty {
  grid-column: 1 / -1;
  padding: 48px 20px;
  text-align: center;
}
.vr-empty__icon { font-size: 28px; opacity: 0.2; margin-bottom: 8px; }
.vr-empty__text {
  font-family: var(--font-display);
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #444;
}

/* ── SEARCH + FILTER ── */
.vr-controls {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}
.vr-search-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #181818;
  border: 1px solid rgba(255,255,255,0.1);
  padding: 8px 12px;
  border-radius: 2px;
  flex: 1;
  min-width: 180px;
  max-width: 280px;
  transition: border-color 0.15s;
}
.vr-search-wrap:focus-within { border-color: #FF6B00; }
.vr-search {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  font-family: var(--font-body);
  font-size: 13px;
  color: #F5F0E8;
}
.vr-search::placeholder { color: #444; }
.vr-search-clear {
  background: none;
  border: none;
  color: #555;
  font-size: 12px;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  transition: color 0.15s;
}
.vr-search-clear:hover { color: #FF6B00; }

.vr-filters {
  display: flex;
  gap: 3px;
  flex-wrap: wrap;
}
.vr-filter {
  font-family: var(--font-display);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 7px 12px;
  border: 1px solid rgba(255,255,255,0.1);
  color: #AAAAAA;
  background: transparent;
  border-radius: 2px;
  cursor: pointer;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  gap: 5px;
}
.vr-filter:hover  { border-color: #FF6B00; color: #FF6B00; }
.vr-filter.active { background: #FF6B00; border-color: #FF6B00; color: #fff; }
.vr-filter__count {
  font-size: 10px;
  opacity: 0.7;
  font-weight: 600;
}

/* ── LEADERBOARD ── */
.vr-leaderboard {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 3px;
  margin-bottom: 24px;
}
.vr-board {
  background: #181818;
  border: 1px solid rgba(255,255,255,0.06);
  border-top: 2px solid #FF6B00;
  overflow: hidden;
}
.vr-board__title {
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: #FF6B00;
  padding: 8px 12px;
  background: #111;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.vr-board__row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  transition: background 0.15s;
}
.vr-board__row:hover { background: rgba(255,255,255,0.02); }
.vr-board__row.top { background: rgba(255,107,0,0.05); }
.vr-board__rank {
  font-family: var(--font-display);
  font-size: 13px;
  font-weight: 900;
  color: #333;
  width: 14px;
  flex-shrink: 0;
}
.vr-board__row.top .vr-board__rank { color: #FF6B00; }
.vr-board__av {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #222;
  border: 1.5px solid rgba(255,107,0,0.4);
  overflow: hidden;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 800;
  color: #FF6B00;
}
.vr-board__av img { width: 100%; height: 100%; object-fit: cover; }
.vr-board__name {
  font-family: var(--font-display);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #AAAAAA;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.vr-board__val {
  font-family: var(--font-display);
  font-size: 18px;
  font-weight: 900;
  color: #FF6B00;
  flex-shrink: 0;
}
.vr-board__lbl {
  font-family: var(--font-display);
  font-size: 8px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #444;
  width: 24px;
  flex-shrink: 0;
}

/* ── MODAL ── */
.vr-modal-bg {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.88);
  z-index: 600;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
  backdrop-filter: blur(8px);
}
.vr-modal-bg.open { display: flex; }
.vr-modal {
  background: #161210;
  border: 1px solid rgba(255,255,255,0.1);
  border-top: 3px solid #FF6B00;
  width: 100%;
  max-width: 520px;
  display: grid;
  grid-template-columns: 160px 1fr;
  position: relative;
  overflow: hidden;
  border-radius: 2px;
}
.vr-modal__close {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 30px;
  height: 30px;
  background: #222;
  border: none;
  border-radius: 50%;
  color: #888;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
  z-index: 2;
}
.vr-modal__close:hover { background: #FF6B00; color: #fff; }
.vr-modal__left {
  background: linear-gradient(145deg, #1E1916, #2a2420);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  position: relative;
}
.vr-modal__photo {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  border: 3px solid #FF6B00;
  overflow: hidden;
  background: #222;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 28px;
  font-weight: 900;
  color: #FF6B00;
  margin-bottom: 8px;
}
.vr-modal__photo img { width: 100%; height: 100%; object-fit: cover; }
.vr-modal__num {
  font-family: var(--font-display);
  font-size: 48px;
  font-weight: 900;
  color: rgba(255,107,0,0.15);
  line-height: 1;
}
.vr-modal__right { padding: 24px 20px 20px; overflow-y: auto; max-height: 90vh; }
.vr-modal__pos {
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: #FF6B00;
  margin-bottom: 4px;
}
.vr-modal__name {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #F5F0E8;
  line-height: 1.1;
  margin-bottom: 16px;
  padding-right: 30px;
}
.vr-modal__stats {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 2px;
  margin-bottom: 18px;
}
.vr-modal__stat {
  background: #1E1916;
  padding: 10px 4px;
  text-align: center;
}
.vr-modal__stat-val {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 900;
  color: #F5F0E8;
  line-height: 1;
}
.vr-modal__stat-lbl {
  font-family: var(--font-display);
  font-size: 8px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #555;
  margin-top: 3px;
}
.vr-skills { }
.vr-skill {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 7px;
}
.vr-skill__lbl {
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #AAAAAA;
  width: 38px;
  flex-shrink: 0;
}
.vr-skill__bar {
  flex: 1;
  height: 4px;
  background: #2A2420;
  border-radius: 2px;
  overflow: hidden;
}
.vr-skill__fill {
  height: 100%;
  background: #FF6B00;
  border-radius: 2px;
  transition: width 0.5s ease;
}
.vr-skill__val {
  font-family: var(--font-display);
  font-size: 11px;
  font-weight: 800;
  color: #FF6B00;
  width: 18px;
  text-align: right;
  flex-shrink: 0;
}

/* ── RESPONSIVE ── */
@media (max-width: 700px) {
  .vr-leaderboard { grid-template-columns: 1fr; }
  .vr-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
  .vr-modal { grid-template-columns: 1fr; }
  .vr-modal__left { padding: 20px; flex-direction: row; gap: 16px; justify-content: flex-start; }
  .vr-modal__num { font-size: 36px; }
  .vr-controls { flex-direction: column; align-items: flex-start; }
  .vr-search-wrap { max-width: 100%; width: 100%; }
}
@media (max-width: 480px) {
  .vr-grid { grid-template-columns: repeat(3, 1fr); }
}
</style>


<!-- ══ JAVASCRIPT ══ -->
<script>
(function(){

/* ── Filter & Search ── */
var activeGroup = 'all';
var searchVal   = '';

function applyFilters() {
  var cards   = document.querySelectorAll('.vr-card');
  var visible = 0;
  cards.forEach(function(card){
    var groupMatch = activeGroup === 'all' || card.dataset.group === activeGroup;
    var nameMatch  = card.dataset.name.indexOf(searchVal) !== -1;
    var show = groupMatch && nameMatch;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  var empty = document.getElementById('vr-empty');
  if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
}

/* Filter buttons */
document.querySelectorAll('.vr-filter').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.vr-filter').forEach(function(b){ b.classList.remove('active'); });
    this.classList.add('active');
    activeGroup = this.dataset.group;
    applyFilters();
  });
});

/* Search input */
var searchInput = document.getElementById('vr-search');
var searchClear = document.getElementById('vr-search-clear');
if (searchInput) {
  searchInput.addEventListener('input', function(){
    searchVal = this.value.toLowerCase().trim();
    searchClear.style.display = searchVal ? 'block' : 'none';
    applyFilters();
  });
}
if (searchClear) {
  searchClear.addEventListener('click', function(){
    searchInput.value = '';
    searchVal = '';
    this.style.display = 'none';
    searchInput.focus();
    applyFilters();
  });
}

/* ── Player Modal ── */
var modal    = document.getElementById('vr-modal');
var closeBtn = document.getElementById('vr-modal-close');

document.querySelectorAll('.vr-card').forEach(function(card){
  card.addEventListener('click', function(){
    var p = JSON.parse(this.dataset.player);
    openModal(p);
  });
});

function openModal(p) {
  /* Photo */
  var photoEl = document.getElementById('vr-modal-photo');
  photoEl.innerHTML = '';
  if (p.photo) {
    var img = document.createElement('img');
    img.src = p.photo;
    img.alt = p.name;
    img.onerror = function(){ this.remove(); photoEl.textContent = p.initials; };
    photoEl.appendChild(img);
  } else {
    photoEl.textContent = p.initials;
  }

  document.getElementById('vr-modal-num').textContent  = p.num  || '';
  document.getElementById('vr-modal-pos').textContent  = p.pos_full;
  document.getElementById('vr-modal-name').textContent = p.name;
  document.getElementById('vm-apps').textContent   = p.apps;
  document.getElementById('vm-goals').textContent  = p.goals;
  document.getElementById('vm-ast').textContent    = p.assists;
  document.getElementById('vm-motm').textContent   = p.motm;
  document.getElementById('vm-cs').textContent     = p.clean_sheets;

  /* Skills with animation */
  ['pace','shoot','drib','def','phys','pass'].forEach(function(k){
    var val = parseInt(p[k]) || 0;
    document.getElementById('vrsv-'+k).textContent = val;
    setTimeout(function(){
      document.getElementById('vrs-'+k).style.width = (val * 10) + '%';
    }, 60);
  });

  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  modal.classList.remove('open');
  document.body.style.overflow = '';
  /* Reset skill bars */
  ['pace','shoot','drib','def','phys','pass'].forEach(function(k){
    var el = document.getElementById('vrs-'+k);
    if (el) el.style.width = '0%';
  });
}

if (closeBtn) closeBtn.addEventListener('click', closeModal);
if (modal)    modal.addEventListener('click', function(e){ if(e.target === modal) closeModal(); });
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });

})();
</script>
