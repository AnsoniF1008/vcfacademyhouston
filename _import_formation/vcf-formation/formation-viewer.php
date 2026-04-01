<?php
/**
 * VCF Academy Houston — Formation Viewer (Enhanced)
 * ====================================================
 * INSTRUCCIONES:
 * 1. En tu index.php, busca la sección:
 *      <!-- ══ FORMATION VIEWER ══ -->  (o "Know Your Role")
 * 2. Reemplaza TODO ese bloque con este código
 * 3. Ajusta el array $formation_players con los jugadores reales de tu DB
 *
 * Este archivo es un "partial" — NO incluye header/footer.
 * Solo pega el bloque entre los comments de inicio y fin.
 */

// ── JUGADORES POR POSICIÓN ──────────────────────────────────────
// Ajusta estos datos con los de tu base de datos.
// 'photo' => nombre del archivo en assets/uploads/
// 'num'   => número de dorsal (string vacío si no tiene)
$formation_players = [
  'GK'  => ['name' => 'Matías Astorino',       'initials' => 'MA', 'num' => '23', 'photo' => 'roster-69af42a639f04.jpg'],
  'RB'  => ['name' => 'Sebastian Brito-Stirpe', 'initials' => 'SB', 'num' => '6',  'photo' => 'roster-69af7584136ff.jpg'],
  'CBR' => ['name' => 'Miguel Gonzalez',         'initials' => 'MG', 'num' => '31', 'photo' => 'roster-69ab621c869f2.jpg'],
  'CBL' => ['name' => 'Antoine Haddad',          'initials' => 'AH', 'num' => '',   'photo' => ''],
  'LB'  => ['name' => 'Jayden Gomez',            'initials' => 'JG', 'num' => '45', 'photo' => 'roster-69b099cdb1035.jpg'],
  'CMR' => ['name' => 'Tiziano Barrio',          'initials' => 'TB', 'num' => '72', 'photo' => 'roster-69b709db5e85f.jpg'],
  'CMC' => ['name' => 'Juan Morales',            'initials' => 'JM', 'num' => '93', 'photo' => 'roster-69b0a15194846.jpg'],
  'CML' => ['name' => 'Santiago Yepez Moreno',   'initials' => 'SY', 'num' => '14', 'photo' => 'roster-69af6f07b414b.jpg'],
  'RW'  => ['name' => 'Mateo Mata',              'initials' => 'MM', 'num' => '29', 'photo' => 'roster-69aa27b820910.jpg'],
  'ST'  => ['name' => 'Juan Marco',              'initials' => 'JM', 'num' => '80', 'photo' => 'roster-69af6fdc9493b.jpg'],
  'LW'  => ['name' => 'Alexander Morejon',       'initials' => 'AM', 'num' => '11', 'photo' => 'roster-69af578163a2a.jpg'],
];

// Helper: encode player data for JS
function player_json($p) {
  return json_encode([
    'name'     => $p['name'],
    'initials' => $p['initials'],
    'num'      => $p['num'],
    'photo'    => !empty($p['photo']) ? 'assets/uploads/' . $p['photo'] : '',
  ]);
}
?>

<!-- ══════════════════════════════════════════════════════════════
     FORMATION VIEWER — Enhanced
     Inicio del bloque — reemplaza tu sección "Know Your Role"
══════════════════════════════════════════════════════════════ -->
<section id="formation">
<div class="vcf-section--dark" style="padding:44px 0;">
<div style="max-width:var(--page-max);margin:0 auto;padding:0 var(--page-pad);">

  <!-- Header -->
  <div class="vcf-section__header">
    <h2 class="vcf-section__title">Know Your <em>Role</em></h2>
  </div>
  <p style="font-size:13px;color:#AAAAAA;margin-bottom:22px;max-width:520px;line-height:1.6;">
    Tap a position to learn about the role and see the assigned player.
    Switch formations to compare systems.
  </p>

  <!-- Formation tabs -->
  <div class="vcf-formation__tabs" id="formation-tabs">
    <button class="vcf-formation__tab active" data-f="433">4-3-3</button>
    <button class="vcf-formation__tab"        data-f="442">4-4-2</button>
    <button class="vcf-formation__tab"        data-f="352">3-5-2</button>
  </div>

  <!-- Grid: pitch + info panel -->
  <div class="vfv-grid">

    <!-- PITCH -->
    <div>
      <div class="vfv-pitch" id="vfv-pitch">

        <!-- SVG: field lines + connection lines -->
        <svg class="vfv-pitch-svg" viewBox="0 0 100 60" preserveAspectRatio="none">
          <!-- Field markings -->
          <rect x="1.5" y="1.5" width="97" height="57" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="0.4"/>
          <line x1="1.5" y1="30" x2="98.5" y2="30" stroke="rgba(255,255,255,0.08)" stroke-width="0.4"/>
          <circle cx="50" cy="30" r="9" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="0.4"/>
          <circle cx="50" cy="30" r="0.6" fill="rgba(255,255,255,0.2)"/>
          <!-- Penalty boxes -->
          <rect x="1.5" y="14" width="16" height="32" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.4"/>
          <rect x="82.5" y="14" width="16" height="32" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.4"/>
          <!-- Goal areas -->
          <rect x="1.5" y="21" width="6" height="18" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="0.4"/>
          <rect x="92.5" y="21" width="6" height="18" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="0.4"/>
          <!-- Connection lines (updated by JS) -->
          <g id="vfv-lines" opacity="0.18"></g>
        </svg>

        <!-- Player dots (rendered by JS) -->
      </div>

      <!-- Players strip -->
      <div class="vfv-strip-label">Assigned players — tap to highlight on pitch</div>
      <div class="vfv-strip" id="vfv-strip"></div>
    </div>

    <!-- INFO PANEL -->
    <div class="vfv-panel">

      <!-- Position detail (filled by JS) -->
      <div class="vfv-detail" id="vfv-detail">
        <div class="vfv-placeholder">
          <div class="vfv-placeholder__icon">&#9917;</div>
          <div class="vfv-placeholder__text">Select a position</div>
          <div class="vfv-placeholder__sub">Tap any dot on the pitch</div>
        </div>
      </div>

      <!-- Team season stats -->
      <div>
        <div class="vfv-strip-label">Season stats</div>
        <div class="vfv-mini-stats">
          <div class="vfv-mini-stat"><span class="vfv-mini-val pos">1</span><span class="vfv-mini-lbl">Wins</span></div>
          <div class="vfv-mini-stat"><span class="vfv-mini-val neg">2</span><span class="vfv-mini-lbl">Losses</span></div>
          <div class="vfv-mini-stat"><span class="vfv-mini-val">2</span><span class="vfv-mini-lbl">Draws</span></div>
          <div class="vfv-mini-stat"><span class="vfv-mini-val">13</span><span class="vfv-mini-lbl">GF</span></div>
          <div class="vfv-mini-stat"><span class="vfv-mini-val">17</span><span class="vfv-mini-lbl">GA</span></div>
          <div class="vfv-mini-stat"><span class="vfv-mini-val accent">5</span><span class="vfv-mini-lbl">PTS</span></div>
        </div>
      </div>

    </div><!-- /vfv-panel -->
  </div><!-- /vfv-grid -->

</div>
</div>
</section>

<!-- ══ CSS DEL FORMATION VIEWER ══
     Pega esto en assets/css/vcf-style.css
     (o dentro de un <style> tag en includes/header.php)
══ -->
<style>
/* ── FORMATION VIEWER ── */
.vfv-grid {
  display: grid;
  grid-template-columns: 1fr 280px;
  gap: 16px;
  align-items: start;
}
.vfv-pitch {
  background: linear-gradient(180deg, #143614 0%, #1a4a1a 50%, #143614 100%);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 4px;
  position: relative;
  overflow: hidden;
  aspect-ratio: 16 / 9;
}
.vfv-pitch-svg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

/* Player dot */
.vfv-pos {
  position: absolute;
  transform: translate(-50%, -50%);
  cursor: pointer;
  z-index: 3;
}
.vfv-pos__dot {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #FF6B00;
  border: 2.5px solid rgba(255,255,255,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 9px;
  font-weight: 900;
  color: #fff;
  text-transform: uppercase;
  transition: transform 0.18s, box-shadow 0.18s, background 0.18s, border-color 0.18s;
  overflow: hidden;
}
.vfv-pos__dot img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}
.vfv-pos:hover .vfv-pos__dot,
.vfv-pos.active .vfv-pos__dot {
  transform: scale(1.28);
  box-shadow: 0 0 18px rgba(255,107,0,0.65);
  border-color: rgba(255,255,255,0.6);
}
.vfv-pos.active .vfv-pos__dot {
  background: #fff;
  color: #FF6B00;
}
.vfv-pos__label {
  position: absolute;
  top: 37px;
  left: 50%;
  transform: translateX(-50%);
  font-family: var(--font-display);
  font-size: 7.5px;
  font-weight: 700;
  text-transform: uppercase;
  color: rgba(255,255,255,0.5);
  white-space: nowrap;
  background: rgba(0,0,0,0.6);
  padding: 1px 5px;
  border-radius: 2px;
  pointer-events: none;
  letter-spacing: 0.06em;
}

/* Players strip */
.vfv-strip-label {
  font-family: var(--font-display);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: #444;
  margin: 10px 0 6px;
}
.vfv-strip {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}
.vfv-chip {
  display: flex;
  align-items: center;
  gap: 5px;
  background: #181818;
  border: 1px solid rgba(255,255,255,0.06);
  padding: 5px 10px;
  cursor: pointer;
  border-radius: 2px;
  font-family: var(--font-display);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #AAAAAA;
  transition: all 0.15s;
  white-space: nowrap;
}
.vfv-chip:hover,
.vfv-chip.active {
  border-color: #FF6B00;
  color: #FF6B00;
  background: #1E1916;
}
.vfv-chip__num {
  font-size: 9px;
  color: #555;
}
.vfv-chip.active .vfv-chip__num { color: rgba(255,107,0,0.6); }

/* Panel */
.vfv-panel {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.vfv-detail {
  background: #181818;
  border: 1px solid rgba(255,255,255,0.07);
  border-top: 3px solid #FF6B00;
  min-height: 160px;
}

/* Placeholder */
.vfv-placeholder {
  padding: 32px 20px;
  text-align: center;
}
.vfv-placeholder__icon {
  font-size: 28px;
  opacity: 0.18;
  margin-bottom: 8px;
}
.vfv-placeholder__text {
  font-family: var(--font-display);
  font-size: 14px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #555;
}
.vfv-placeholder__sub {
  font-size: 11px;
  color: #333;
  margin-top: 4px;
}

/* Detail content */
.vfv-detail-inner { padding: 18px; }
.vfv-detail__tag {
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: #FF6B00;
  margin-bottom: 4px;
}
.vfv-detail__name {
  font-family: var(--font-display);
  font-size: 18px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #F5F0E8;
  line-height: 1.1;
  margin-bottom: 10px;
}
.vfv-detail__desc {
  font-size: 12px;
  color: #AAAAAA;
  line-height: 1.7;
  margin-bottom: 14px;
}
/* Assigned player card */
.vfv-assigned {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #0f0f0f;
  border: 1px solid rgba(255,107,0,0.2);
  padding: 10px 12px;
  margin-bottom: 12px;
}
.vfv-assigned__av {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  border: 2px solid #FF6B00;
  overflow: hidden;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #1a1a1a;
  font-family: var(--font-display);
  font-size: 14px;
  font-weight: 800;
  color: #FF6B00;
}
.vfv-assigned__av img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.vfv-assigned__name {
  font-family: var(--font-display);
  font-size: 14px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #F5F0E8;
  line-height: 1.2;
}
.vfv-assigned__num {
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #FF6B00;
  margin-top: 2px;
}
/* Key attributes */
.vfv-attrs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3px;
}
.vfv-attr {
  background: #111;
  padding: 6px 8px;
  border-left: 2px solid #FF6B00;
  font-family: var(--font-display);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #AAAAAA;
}

/* Mini stats */
.vfv-mini-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
}
.vfv-mini-stat {
  background: #181818;
  padding: 10px 6px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.vfv-mini-val {
  font-family: var(--font-display);
  font-size: 24px;
  font-weight: 900;
  color: #F5F0E8;
  line-height: 1;
}
.vfv-mini-val.pos    { color: #4ade80; }
.vfv-mini-val.neg    { color: #f87171; }
.vfv-mini-val.accent { color: #FF6B00; }
.vfv-mini-lbl {
  font-family: var(--font-display);
  font-size: 8px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #555;
  margin-top: 3px;
}

/* Responsive */
@media (max-width: 860px) {
  .vfv-grid { grid-template-columns: 1fr; }
  .vfv-panel { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
}
@media (max-width: 500px) {
  .vfv-panel { grid-template-columns: 1fr; }
  .vfv-pos__dot { width: 26px; height: 26px; font-size: 7px; }
}
</style>


<!-- ══ JAVASCRIPT DEL FORMATION VIEWER ══
     Pega esto justo ANTES del cierre </body>
     o al final del bloque <script> existente
══ -->
<script>
(function(){

/* ── Data de posiciones por formación ── */
var PLAYERS = <?php
echo json_encode([
  'GK'  => $formation_players['GK'],
  'RB'  => $formation_players['RB'],
  'CBR' => $formation_players['CBR'],
  'CBL' => $formation_players['CBL'],
  'LB'  => $formation_players['LB'],
  'CMR' => $formation_players['CMR'],
  'CMC' => $formation_players['CMC'],
  'CML' => $formation_players['CML'],
  'RW'  => $formation_players['RW'],
  'ST'  => $formation_players['ST'],
  'LW'  => $formation_players['LW'],
], JSON_UNESCAPED_UNICODE);
?>;

var FORMATIONS = {
  '433': [
    {key:'GK',  pos:'GK',  x:8,  y:50, name:'Goalkeeper',       desc:'Last line of defense. Commands the penalty area, organizes the backline, and distributes play from deep to start attacks.', attrs:['Reflexes','Distribution','Command','Leadership']},
    {key:'RB',  pos:'RB',  x:22, y:20, name:'Right Back',        desc:'Defensive solidity with the freedom to overlap and join wide attacks. Must track back quickly and deny space to opposing wingers.', attrs:['Tackling','Crossing','Stamina','Positioning']},
    {key:'CBR', pos:'CB',  x:22, y:40, name:'Centre Back (R)',   desc:'Wins aerial duels, blocks shots, and starts build-up play. Must read the game and communicate constantly with the defensive line.', attrs:['Heading','Tackling','Strength','Composure']},
    {key:'CBL', pos:'CB',  x:22, y:60, name:'Centre Back (L)',   desc:'Covers central zones, sweeps behind the line, and is comfortable carrying the ball out of defense under pressure.', attrs:['Pace','Interceptions','Ball Play','Positioning']},
    {key:'LB',  pos:'LB',  x:22, y:80, name:'Left Back',         desc:'Covers the entire left flank, balancing defensive duties with attacking support — providing width when the team is in possession.', attrs:['Crossing','Stamina','Tackling','Speed']},
    {key:'CMR', pos:'CM',  x:52, y:28, name:'Right CM',          desc:'Creative midfielder. Carries from deep, threads passes between lines, and arrives late in the box to add a goal threat.', attrs:['Vision','Passing','Dribbling','Late Runs']},
    {key:'CMC', pos:'CM',  x:52, y:50, name:'Central CM',        desc:'Box-to-box engine. Controls tempo, presses high, wins second balls, and supports both defensive and attacking phases equally.', attrs:['Work Rate','Passing','Pressing','Balance']},
    {key:'CML', pos:'CM',  x:52, y:72, name:'Left CM',           desc:'Defensive-minded midfielder. Screens the back four, disrupts opposition attacks, and recycles possession intelligently.', attrs:['Tackling','Positioning','Short Pass','Strength']},
    {key:'RW',  pos:'RW',  x:80, y:18, name:'Right Winger',      desc:'Pace and directness on the right. Takes on defenders 1v1, delivers dangerous crosses, and cuts inside to create goal opportunities.', attrs:['Pace','Dribbling','Crossing','Finishing']},
    {key:'ST',  pos:'ST',  x:86, y:50, name:'Striker',           desc:'Leads the line — makes intelligent runs in behind, holds up play to bring others into the game, and finishes chances calmly.', attrs:['Finishing','Movement','Hold-up','Heading']},
    {key:'LW',  pos:'LW',  x:80, y:82, name:'Left Winger',       desc:'Cuts inside from the left onto the stronger foot to create shooting opportunities. Provides width and delivers from deep positions.', attrs:['Dribbling','Pace','Shooting','Creativity']},
  ],
  '442': [
    {key:'GK',  pos:'GK',  x:8,  y:50, name:'Goalkeeper',     desc:'Commands the box and organizes the four-man defense. Distribution is key to transition play.', attrs:['Reflexes','Distribution','Command','Shot-stopping']},
    {key:'RB',  pos:'RB',  x:25, y:18, name:'Right Back',      desc:'Provides defensive width and supports attacks down the right flank with crosses and overlapping runs.', attrs:['Tackling','Crossing','Stamina','Positioning']},
    {key:'CBR', pos:'CB',  x:25, y:38, name:'CB Right',        desc:'Wins aerial duels and blocks attacks through the center. Strong and reliable in 1v1 defending.', attrs:['Heading','Strength','Reading','Composure']},
    {key:'CBL', pos:'CB',  x:25, y:62, name:'CB Left',         desc:'Covers the left channel and builds from the back. Comfortable under pressure with the ball at their feet.', attrs:['Pace','Tackling','Ball Play','Positioning']},
    {key:'LB',  pos:'LB',  x:25, y:82, name:'Left Back',       desc:'Defensive cover on the left with a license to join attacks. Must balance positioning and timing of forward runs.', attrs:['Speed','Crossing','Stamina','Tackling']},
    {key:'CMR', pos:'RM',  x:50, y:18, name:'Right Mid',       desc:'Works the right channel tirelessly — delivers crosses, creates chances, and tracks back to support the full-back defensively.', attrs:['Stamina','Crossing','Tackling','Pace']},
    {key:'CML', pos:'CM',  x:50, y:40, name:'Central CM (R)',  desc:'Dynamic midfielder who covers ground in both directions, linking play and arriving into the box to support attacks.', attrs:['Work Rate','Passing','Pressing','Runs']},
    {key:'CMC', pos:'CM',  x:50, y:60, name:'Central CM (L)',  desc:'Screens the defense and distributes possession calmly. Keeps the team organized and sets the tempo.', attrs:['Positioning','Short Pass','Tackling','Vision']},
    {key:'LB',  pos:'LM',  x:50, y:82, name:'Left Mid',        desc:'Provides width on the left and supports both attacking and defensive phases. High work rate required.', attrs:['Pace','Dribbling','Crossing','Work Rate']},
    {key:'ST',  pos:'ST',  x:80, y:35, name:'Striker (R)',     desc:'Runs in behind and finishes. Partners the second striker to press defenders and create space through movement.', attrs:['Finishing','Speed','Movement','Pressing']},
    {key:'LW',  pos:'ST',  x:80, y:65, name:'Striker (L)',     desc:'Hold-up striker who brings teammates into play, links midfield and attack, and attacks the far post on crosses.', attrs:['Strength','Hold-up','Heading','Link Play']},
  ],
  '352': [
    {key:'GK',  pos:'GK',  x:8,  y:50, name:'Goalkeeper',       desc:'Sweeper-keeper in a back 3 system. Must be comfortable with the ball and command a high defensive line confidently.', attrs:['Sweeping','Distribution','Command','Reflexes']},
    {key:'RB',  pos:'CB',  x:25, y:28, name:'Right CB',          desc:'Covers wide areas in a back 3. Must be aggressive, quick, and able to handle 1v1 situations out wide without support.', attrs:['Pace','Tackling','Heading','1v1 Defending']},
    {key:'CBR', pos:'CB',  x:25, y:50, name:'Central CB',        desc:'Leader of the three-man defense. Sweeper role — reads the game and organizes the defensive structure constantly.', attrs:['Leadership','Heading','Reading','Composure']},
    {key:'CBL', pos:'CB',  x:25, y:72, name:'Left CB',           desc:'Left of the back three — covers wide threats and is comfortable bringing the ball forward into midfield zones.', attrs:['Tackling','Ball Play','Pace','Positioning']},
    {key:'CMR', pos:'WB',  x:50, y:10, name:'Right Wing-Back',   desc:'Covers the entire right flank — must defend and attack with relentless energy and pace. Key to width in this system.', attrs:['Stamina','Crossing','Tackling','Pace']},
    {key:'CML', pos:'CM',  x:50, y:35, name:'Right CM',          desc:'One of three midfielders. Supports attacks on the right side and covers when the wing-back advances forward.', attrs:['Passing','Runs','Work Rate','Vision']},
    {key:'CMC', pos:'CM',  x:50, y:50, name:'Pivot',             desc:'Deep-lying playmaker. Controls tempo, screens the back three, and distributes from central deep positions.', attrs:['Positioning','Short Pass','Tackling','Composure']},
    {key:'LB',  pos:'CM',  x:50, y:65, name:'Left CM',           desc:'Creative midfielder left of center. Carries from deep and links midfield to attack with intelligent movement.', attrs:['Dribbling','Vision','Passing','Movement']},
    {key:'LW',  pos:'WB',  x:50, y:90, name:'Left Wing-Back',    desc:'Covers the entire left flank. Needs relentless stamina and technical quality to beat defenders and deliver crosses.', attrs:['Stamina','Pace','Crossing','Defending']},
    {key:'ST',  pos:'ST',  x:82, y:35, name:'Striker (R)',       desc:'Combines with partner striker. Makes runs in behind, presses from the front, and creates space through movement.', attrs:['Pace','Finishing','Pressing','Movement']},
    {key:'RW',  pos:'ST',  x:82, y:65, name:'Striker (L)',       desc:'Target striker. Holds up play, brings teammates into the game, and attacks crosses from both flanks.', attrs:['Strength','Hold-up','Heading','Link Play']},
  ]
};

/* Connection line pairs (index in positions array) */
var CONNECTIONS = {
  '433': [[0,1],[0,2],[0,3],[0,4],[1,5],[2,5],[2,6],[3,6],[3,7],[4,7],[5,8],[6,9],[7,10],[5,6],[6,7],[8,9],[9,10]],
  '442': [[0,1],[0,2],[0,3],[0,4],[1,5],[2,6],[3,7],[4,8],[5,6],[6,7],[7,8],[5,9],[6,9],[7,10],[8,10],[9,10]],
  '352': [[0,1],[0,2],[0,3],[1,4],[2,4],[2,5],[2,6],[2,7],[3,8],[3,6],[4,5],[5,6],[6,7],[7,8],[5,9],[6,9],[7,10],[8,10],[9,10]]
};

var currentF = '433';
var activeIdx = null;

/* ── Render formation ── */
function renderFormation(key) {
  currentF  = key;
  activeIdx = null;

  var pitch = document.getElementById('vfv-pitch');
  var strip = document.getElementById('vfv-strip');

  /* Remove old dots */
  pitch.querySelectorAll('.vfv-pos').forEach(function(d){ d.remove(); });

  var positions = FORMATIONS[key];

  /* Draw connection lines */
  var connG = document.getElementById('vfv-lines');
  connG.innerHTML = '';
  (CONNECTIONS[key] || []).forEach(function(pair){
    var a = positions[pair[0]], b = positions[pair[1]];
    if (!a || !b) return;
    var line = document.createElementNS('http://www.w3.org/2000/svg','line');
    line.setAttribute('x1', a.x); line.setAttribute('y1', a.y);
    line.setAttribute('x2', b.x); line.setAttribute('y2', b.y);
    line.setAttribute('stroke','#FF6B00');
    line.setAttribute('stroke-width','0.5');
    connG.appendChild(line);
  });

  /* Render position dots */
  positions.forEach(function(p, i){
    var player = PLAYERS[p.key] || null;

    var div = document.createElement('div');
    div.className = 'vfv-pos';
    div.style.left = p.x + '%';
    div.style.top  = p.y + '%';
    div.dataset.index = i;

    /* Avatar: photo or initials */
    var avHtml = '';
    if (player && player.photo) {
      avHtml = '<img src="' + player.photo + '" alt="' + (player.name||'') + '" onerror="this.style.display=\'none\';this.nextSibling.style.display=\'flex\'">'
             + '<span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">' + (player.initials||p.pos) + '</span>';
    } else {
      avHtml = player ? (player.initials || p.pos) : p.pos;
    }

    div.innerHTML = '<div class="vfv-pos__dot">' + avHtml + '</div>'
                  + '<span class="vfv-pos__label">' + p.pos + '</span>';

    div.addEventListener('click', function(){ selectPos(i); });
    pitch.appendChild(div);
  });

  /* Players strip */
  strip.innerHTML = '';
  positions.forEach(function(p, i){
    var player = PLAYERS[p.key] || null;
    var chip = document.createElement('div');
    chip.className = 'vfv-chip';
    chip.dataset.index = i;
    var numHtml = player && player.num ? '<span class="vfv-chip__num">#' + player.num + '</span>' : '';
    chip.innerHTML = numHtml + (player ? player.name.split(' ')[0] + ' ' + (player.name.split(' ')[1]||'') : p.pos);
    chip.addEventListener('click', function(){ selectPos(i); });
    strip.appendChild(chip);
  });

  resetDetail();
}

/* ── Select position ── */
function selectPos(index) {
  activeIdx = index;
  var p      = FORMATIONS[currentF][index];
  var player = PLAYERS[p.key] || null;
  var fname  = currentF.replace('433','4-3-3').replace('442','4-4-2').replace('352','3-5-2');

  /* Highlight dot + chip */
  document.querySelectorAll('.vfv-pos').forEach(function(d){ d.classList.remove('active'); });
  document.querySelectorAll('.vfv-chip').forEach(function(d){ d.classList.remove('active'); });
  var dot  = document.querySelector('.vfv-pos[data-index="' + index + '"]');
  var chip = document.querySelector('.vfv-chip[data-index="' + index + '"]');
  if (dot)  dot.classList.add('active');
  if (chip) { chip.classList.add('active'); chip.scrollIntoView({behavior:'smooth',block:'nearest',inline:'center'}); }

  /* Build attributes html */
  var attrsHtml = p.attrs.map(function(a){
    return '<div class="vfv-attr">' + a + '</div>';
  }).join('');

  /* Build assigned player html */
  var assignedHtml = '';
  if (player) {
    var avInner = '';
    if (player.photo) {
      avInner = '<img src="' + player.photo + '" alt="' + player.name + '" '
              + 'onerror="this.style.display=\'none\';this.parentNode.textContent=\'' + player.initials + '\'">';
    } else {
      avInner = player.initials;
    }
    assignedHtml = '<div class="vfv-assigned">'
      + '<div class="vfv-assigned__av">' + avInner + '</div>'
      + '<div>'
      +   '<div class="vfv-assigned__name">' + player.name + '</div>'
      +   '<div class="vfv-assigned__num">' + (player.num ? '#'+player.num+' · ' : '') + 'VCF Houston</div>'
      + '</div>'
      + '</div>';
  }

  document.getElementById('vfv-detail').innerHTML =
    '<div class="vfv-detail-inner">'
    + '<div class="vfv-detail__tag">' + p.pos + ' &middot; ' + fname + '</div>'
    + '<div class="vfv-detail__name">' + p.name + '</div>'
    + '<div class="vfv-detail__desc">' + p.desc + '</div>'
    + assignedHtml
    + '<div class="vfv-attrs">' + attrsHtml + '</div>'
    + '</div>';
}

/* ── Reset detail panel ── */
function resetDetail() {
  document.getElementById('vfv-detail').innerHTML =
    '<div class="vfv-placeholder">'
    + '<div class="vfv-placeholder__icon">&#9917;</div>'
    + '<div class="vfv-placeholder__text">Select a position</div>'
    + '<div class="vfv-placeholder__sub">Tap any dot on the pitch</div>'
    + '</div>';
}

/* ── Formation tab buttons ── */
document.querySelectorAll('#formation-tabs .vcf-formation__tab').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('#formation-tabs .vcf-formation__tab')
      .forEach(function(b){ b.classList.remove('active'); });
    this.classList.add('active');
    renderFormation(this.dataset.f);
  });
});

/* ── Init ── */
renderFormation('433');

})();
</script>

<!-- ══════════════════════════════════════════════════════════════
     Fin del bloque Formation Viewer
══════════════════════════════════════════════════════════════ -->
