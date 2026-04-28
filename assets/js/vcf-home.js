/**
 * VCF public redesign: hero slideshow, countdown, formation tabs, match reels tap-to-play.
 */
(function () {
  'use strict';

  /* Hero background slideshow */
  (function () {
    var slides = document.querySelectorAll('.vcf-hero__slide');
    var dots = document.querySelectorAll('.vcf-hero__dot');
    var prevBtn = document.getElementById('hero-prev');
    var nextBtn = document.getElementById('hero-next');
    if (!slides.length || slides.length < 2) return;

    var current = 0;
    var timer = null;
    var DELAY = 5000;
    var PAUSE_ON_HOVER = true;

    function goTo(targetIndex) {
      var n = slides.length;
      targetIndex = ((targetIndex % n) + n) % n;
      if (targetIndex === current) return;

      var leaving = slides[current];
      leaving.classList.remove('active');
      leaving.classList.add('leaving');
      if (dots[current]) {
        dots[current].classList.remove('active');
        dots[current].removeAttribute('aria-current');
      }

      setTimeout(function () {
        leaving.classList.remove('leaving');
      }, 1400);

      current = targetIndex;
      slides[current].classList.add('active');
      if (dots[current]) {
        dots[current].classList.add('active');
        dots[current].setAttribute('aria-current', 'true');
      }
    }

    function next() {
      goTo(current + 1);
    }
    function prev() {
      goTo(current - 1);
    }

    function startAuto() {
      clearInterval(timer);
      timer = setInterval(next, DELAY);
    }
    function stopAuto() {
      clearInterval(timer);
    }

    startAuto();

    if (nextBtn) nextBtn.addEventListener('click', function () { next(); startAuto(); });
    if (prevBtn) prevBtn.addEventListener('click', function () { prev(); startAuto(); });

    dots.forEach(function (dot, i) {
      dot.addEventListener('click', function () {
        goTo(i);
        startAuto();
      });
    });

    if (PAUSE_ON_HOVER) {
      var hero = document.getElementById('hero');
      if (hero) {
        hero.addEventListener('mouseenter', stopAuto);
        hero.addEventListener('mouseleave', startAuto);
      }
    }

    var touchStartX = 0;
    var heroEl = document.getElementById('hero');
    if (heroEl) {
      heroEl.addEventListener(
        'touchstart',
        function (e) {
          touchStartX = e.touches[0].clientX;
        },
        { passive: true }
      );
      heroEl.addEventListener(
        'touchend',
        function (e) {
          var diff = touchStartX - e.changedTouches[0].clientX;
          if (Math.abs(diff) > 40) {
            if (diff > 0) next();
            else prev();
            startAuto();
          }
        },
        { passive: true }
      );
    }
  })();

  /* Hero countdown */
  (function () {
    var el = document.getElementById('match-countdown');
    if (!el) return;

    var raw = el.getAttribute('data-target');
    if (!raw) return;

    var target = new Date(raw);
    if (isNaN(target.getTime())) {
      // Fallback: parse manually for browsers that mishandle ISO 8601
      var parts = raw.replace('T', ' ').split(/[- :]/);
      target = new Date(
        parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10),
        parseInt(parts[3] || 0, 10), parseInt(parts[4] || 0, 10), parseInt(parts[5] || 0, 10)
      );
    }
    if (isNaN(target.getTime())) return;

    var dEl = document.getElementById('cd-d');
    var hEl = document.getElementById('cd-h');
    var mEl = document.getElementById('cd-m');
    var sEl = document.getElementById('cd-s');
    if (!dEl || !hEl || !mEl || !sEl) return;

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function tick() {
      var diff = target.getTime() - Date.now();

      if (diff <= 0) {
        dEl.textContent = '00';
        hEl.textContent = '00';
        mEl.textContent = '00';
        sEl.textContent = '00';
        el.setAttribute('data-ended', '1');
        if (!el.querySelector('.vcf-match-widget__gameday')) {
          var label = document.createElement('div');
          label.className = 'vcf-match-widget__gameday';
          label.style.cssText = 'grid-column:1/-1;text-align:center;font-size:11px;font-weight:700;letter-spacing:0.15em;color:var(--vcf-orange);margin-top:8px;';
          label.textContent = 'GAME DAY';
          el.appendChild(label);
        }
        return;
      }

      dEl.textContent = pad(Math.floor(diff / 86400000));
      hEl.textContent = pad(Math.floor((diff % 86400000) / 3600000));
      mEl.textContent = pad(Math.floor((diff % 3600000) / 60000));
      sEl.textContent = pad(Math.floor((diff % 60000) / 1000));
    }

    tick();
    setInterval(tick, 1000);
  })();

  window.addEventListener('load', function () {
    var bg = document.getElementById('hero-bg');
    if (bg) bg.classList.add('loaded');
  });

  /* Formation viewer
   *
   * Public-facing "Know Your Role" sandbox. Lets visitors:
   *  - Switch between 7 common formations (4-3-3, 4-2-3-1, 4-4-2, 4-1-4-1,
   *    3-4-3, 3-5-2, 5-3-2).
   *  - Filter by squad / age category.
   *  - See an auto-assigned starting XI based on each player's natural
   *    position + dorsal.
   *  - View the bench (everyone in the squad not in the XI).
   *  - Tap a position → tap a bench player to substitute. Reset returns
   *    to the auto-assigned default.
   *
   * State is in-memory only (intentionally — this is a demo, not the
   * coach's actual lineup). The admin panel will get a persistent
   * formation builder later.
   */
  (function () {
    var dataEl = document.getElementById('vfv-formation-data');
    var pitch = document.getElementById('vfv-pitch');
    if (!dataEl || !pitch) return;

    var POOL = { all: [] };
    var CATEGORIES = [];
    try {
      var parsed = JSON.parse(dataEl.textContent || '{}');
      POOL = parsed.pool || { all: [] };
      CATEGORIES = parsed.categories || [];
    } catch (e) {
      return;
    }

    /* Each formation entry has 11 slots. `role` says which type of player
     * naturally fits there (for the auto-assignment). `pos` is the label
     * displayed under the dot on the pitch. `desc` and `attrs` are the
     * "Know Your Role" copy shown in the detail panel. */
    var FORMATIONS = {
      '433': {
        label: '4-3-3',
        positions: [
          { key: 'GK',  role: 'GK',  pos: 'GK', x: 8,  y: 50, name: 'Goalkeeper',     desc: 'Last line of defense. Commands the penalty area, organizes the backline, and distributes play from deep to start attacks.', attrs: ['Reflexes','Distribution','Command','Leadership'] },
          { key: 'RB',  role: 'RB',  pos: 'RB', x: 22, y: 20, name: 'Right Back',     desc: 'Defensive solidity with the freedom to overlap and join wide attacks. Must track back quickly and deny space to opposing wingers.', attrs: ['Tackling','Crossing','Stamina','Positioning'] },
          { key: 'CBR', role: 'CB',  pos: 'CB', x: 22, y: 40, name: 'Centre Back (R)', desc: 'Wins aerial duels, blocks shots, and starts build-up play. Must read the game and communicate constantly with the defensive line.', attrs: ['Heading','Tackling','Strength','Composure'] },
          { key: 'CBL', role: 'CB',  pos: 'CB', x: 22, y: 60, name: 'Centre Back (L)', desc: 'Covers central zones, sweeps behind the line, and is comfortable carrying the ball out of defense under pressure.', attrs: ['Pace','Interceptions','Ball Play','Positioning'] },
          { key: 'LB',  role: 'LB',  pos: 'LB', x: 22, y: 80, name: 'Left Back',      desc: 'Covers the entire left flank, balancing defensive duties with attacking support — providing width when the team is in possession.', attrs: ['Crossing','Stamina','Tackling','Speed'] },
          { key: 'CMR', role: 'CM',  pos: 'CM', x: 52, y: 28, name: 'Right CM',       desc: 'Creative midfielder. Carries from deep, threads passes between lines, and arrives late in the box to add a goal threat.', attrs: ['Vision','Passing','Dribbling','Late Runs'] },
          { key: 'CMC', role: 'CM',  pos: 'CM', x: 52, y: 50, name: 'Central CM',     desc: 'Box-to-box engine. Controls tempo, presses high, wins second balls, and supports both defensive and attacking phases equally.', attrs: ['Work Rate','Passing','Pressing','Balance'] },
          { key: 'CML', role: 'CM',  pos: 'CM', x: 52, y: 72, name: 'Left CM',        desc: 'Defensive-minded midfielder. Screens the back four, disrupts opposition attacks, and recycles possession intelligently.', attrs: ['Tackling','Positioning','Short Pass','Strength'] },
          { key: 'RW',  role: 'RW',  pos: 'RW', x: 80, y: 18, name: 'Right Winger',   desc: 'Pace and directness on the right. Takes on defenders 1v1, delivers dangerous crosses, and cuts inside to create goal opportunities.', attrs: ['Pace','Dribbling','Crossing','Finishing'] },
          { key: 'ST',  role: 'ST',  pos: 'ST', x: 86, y: 50, name: 'Striker',        desc: 'Leads the line — makes intelligent runs in behind, holds up play to bring others into the game, and finishes chances calmly.', attrs: ['Finishing','Movement','Hold-up','Heading'] },
          { key: 'LW',  role: 'LW',  pos: 'LW', x: 80, y: 82, name: 'Left Winger',    desc: 'Cuts inside from the left onto the stronger foot to create shooting opportunities. Provides width and delivers from deep positions.', attrs: ['Dribbling','Pace','Shooting','Creativity'] },
        ],
        connections: [[0,1],[0,2],[0,3],[0,4],[1,5],[2,5],[2,6],[3,6],[3,7],[4,7],[5,8],[6,9],[7,10],[5,6],[6,7],[8,9],[9,10]],
      },
      '4231': {
        label: '4-2-3-1',
        positions: [
          { key: 'GK',  role: 'GK',  pos: 'GK',  x: 8,  y: 50, name: 'Goalkeeper',          desc: 'Sweeper-keeper behind a high line. Distribution and command of the area set the tempo for the rest of the side.', attrs: ['Reflexes','Distribution','Command','Sweeping'] },
          { key: 'RB',  role: 'RB',  pos: 'RB',  x: 22, y: 18, name: 'Right Back',          desc: 'Modern overlapping full-back. Provides width when the right winger drifts inside, and tracks back to deny crosses.', attrs: ['Stamina','Crossing','Pace','Tackling'] },
          { key: 'CBR', role: 'CB',  pos: 'CB',  x: 22, y: 38, name: 'Centre Back (R)',     desc: 'Aggressive ball-playing centre back. Steps out of the line to break up attacks and starts build-up with progressive passes.', attrs: ['Tackling','Reading','Ball Play','Composure'] },
          { key: 'CBL', role: 'CB',  pos: 'CB',  x: 22, y: 62, name: 'Centre Back (L)',     desc: 'Cover defender. Reads danger, sweeps behind the high line, and stays disciplined to keep the back four organized.', attrs: ['Pace','Heading','Positioning','Strength'] },
          { key: 'LB',  role: 'LB',  pos: 'LB',  x: 22, y: 82, name: 'Left Back',           desc: 'Overlapping full-back on the left. Combines with the wide attacker, delivers crosses, and recovers behind the press.', attrs: ['Crossing','Speed','Stamina','Tackling'] },
          { key: 'CMR', role: 'CDM', pos: 'CDM', x: 42, y: 38, name: 'Defensive Mid (R)',   desc: 'Double pivot — half of a two-man midfield shield. Recycles possession, screens the defense, and breaks up counter-attacks.', attrs: ['Positioning','Short Pass','Tackling','Composure'] },
          { key: 'CML', role: 'CDM', pos: 'CDM', x: 42, y: 62, name: 'Defensive Mid (L)',   desc: 'Other half of the double pivot. Box-to-box runner — links defense to the attacking band of three with quick vertical passes.', attrs: ['Work Rate','Passing','Pressing','Strength'] },
          { key: 'RW',  role: 'RW',  pos: 'RAM', x: 70, y: 22, name: 'Right Attacking Mid', desc: 'Inside forward on the right — drifts narrow to combine with the striker and the central #10. Creates and finishes.', attrs: ['Dribbling','Vision','Finishing','Movement'] },
          { key: 'CMC', role: 'CAM', pos: 'CAM', x: 70, y: 50, name: 'Number 10',           desc: 'Pure playmaker between the lines. Receives between midfield and defense, threads through balls, and arrives late in the box.', attrs: ['Vision','Passing','Dribbling','Creativity'] },
          { key: 'LW',  role: 'LW',  pos: 'LAM', x: 70, y: 78, name: 'Left Attacking Mid',  desc: 'Inside forward on the left — cuts in onto stronger foot to shoot and combine. Stretches the defense by attacking the channels.', attrs: ['Pace','Shooting','Creativity','Dribbling'] },
          { key: 'ST',  role: 'ST',  pos: 'ST',  x: 88, y: 50, name: 'Lone Striker',        desc: 'Reference point of the attack. Holds up play to bring the band of three forward, and finishes chances coldly when isolated.', attrs: ['Hold-up','Finishing','Heading','Movement'] },
        ],
        connections: [[0,1],[0,2],[0,3],[0,4],[1,5],[2,5],[2,6],[3,5],[3,6],[4,6],[5,6],[5,7],[5,8],[6,8],[6,9],[7,8],[8,9],[7,10],[8,10],[9,10]],
      },
      '442': {
        label: '4-4-2',
        positions: [
          { key: 'GK',  role: 'GK', pos: 'GK', x: 8,  y: 50, name: 'Goalkeeper',  desc: 'Commands the box and organizes the four-man defense. Distribution is key to transition play.', attrs: ['Reflexes','Distribution','Command','Shot-stopping'] },
          { key: 'RB',  role: 'RB', pos: 'RB', x: 25, y: 18, name: 'Right Back',  desc: 'Provides defensive width and supports attacks down the right flank with crosses and overlapping runs.', attrs: ['Tackling','Crossing','Stamina','Positioning'] },
          { key: 'CBR', role: 'CB', pos: 'CB', x: 25, y: 38, name: 'CB Right',    desc: 'Wins aerial duels and blocks attacks through the center. Strong and reliable in 1v1 defending.', attrs: ['Heading','Strength','Reading','Composure'] },
          { key: 'CBL', role: 'CB', pos: 'CB', x: 25, y: 62, name: 'CB Left',     desc: 'Covers the left channel and builds from the back. Comfortable under pressure with the ball at their feet.', attrs: ['Pace','Tackling','Ball Play','Positioning'] },
          { key: 'LB',  role: 'LB', pos: 'LB', x: 25, y: 82, name: 'Left Back',   desc: 'Defensive cover on the left with a license to join attacks. Must balance positioning and timing of forward runs.', attrs: ['Speed','Crossing','Stamina','Tackling'] },
          { key: 'RM',  role: 'RW', pos: 'RM', x: 50, y: 18, name: 'Right Mid',   desc: 'Works the right channel tirelessly — delivers crosses, creates chances, and tracks back to support the full-back defensively.', attrs: ['Stamina','Crossing','Tackling','Pace'] },
          { key: 'CMR', role: 'CM', pos: 'CM', x: 50, y: 40, name: 'Central CM (R)', desc: 'Dynamic midfielder who covers ground in both directions, linking play and arriving into the box to support attacks.', attrs: ['Work Rate','Passing','Pressing','Runs'] },
          { key: 'CML', role: 'CM', pos: 'CM', x: 50, y: 60, name: 'Central CM (L)', desc: 'Screens the defense and distributes possession calmly. Keeps the team organized and sets the tempo.', attrs: ['Positioning','Short Pass','Tackling','Vision'] },
          { key: 'LM',  role: 'LW', pos: 'LM', x: 50, y: 82, name: 'Left Mid',    desc: 'Provides width on the left and supports both attacking and defensive phases. High work rate required.', attrs: ['Pace','Dribbling','Crossing','Work Rate'] },
          { key: 'STR', role: 'ST', pos: 'ST', x: 80, y: 35, name: 'Striker (R)', desc: 'Runs in behind and finishes. Partners the second striker to press defenders and create space through movement.', attrs: ['Finishing','Speed','Movement','Pressing'] },
          { key: 'STL', role: 'ST', pos: 'ST', x: 80, y: 65, name: 'Striker (L)', desc: 'Hold-up striker who brings teammates into play, links midfield and attack, and attacks the far post on crosses.', attrs: ['Strength','Hold-up','Heading','Link Play'] },
        ],
        connections: [[0,1],[0,2],[0,3],[0,4],[1,5],[2,6],[3,7],[4,8],[5,6],[6,7],[7,8],[5,9],[6,9],[7,10],[8,10],[9,10]],
      },
      '4141': {
        label: '4-1-4-1',
        positions: [
          { key: 'GK',  role: 'GK',  pos: 'GK',  x: 8,  y: 50, name: 'Goalkeeper',          desc: 'Controls a compact, mid-block defense. Quick distribution to the wings starts counter-attacks.', attrs: ['Reflexes','Distribution','Command','Positioning'] },
          { key: 'RB',  role: 'RB',  pos: 'RB',  x: 22, y: 18, name: 'Right Back',          desc: 'Disciplined full-back — joins attacks selectively because the system already has width through the wide midfielders.', attrs: ['Positioning','Tackling','Crossing','Stamina'] },
          { key: 'CBR', role: 'CB',  pos: 'CB',  x: 22, y: 38, name: 'Centre Back (R)',     desc: 'Aerial duels and physical defending. The single pivot in front gives both centre-backs more cover than usual.', attrs: ['Heading','Strength','Tackling','Reading'] },
          { key: 'CBL', role: 'CB',  pos: 'CB',  x: 22, y: 62, name: 'Centre Back (L)',     desc: 'Composed on the ball — initiates build-up under pressure with safe outlets through the holding midfielder.', attrs: ['Composure','Ball Play','Heading','Strength'] },
          { key: 'LB',  role: 'LB',  pos: 'LB',  x: 22, y: 82, name: 'Left Back',           desc: 'Mirror of the right back. Solid base; chooses moments to overlap when the structure allows it.', attrs: ['Stamina','Tackling','Crossing','Pace'] },
          { key: 'CDM', role: 'CDM', pos: 'CDM', x: 42, y: 50, name: 'Single Pivot (#6)',   desc: 'The defensive shield. Anchors the midfield, screens the back four, and recycles possession from a deep central role.', attrs: ['Positioning','Short Pass','Tackling','Composure'] },
          { key: 'RM',  role: 'RW',  pos: 'RM',  x: 64, y: 18, name: 'Right Midfielder',    desc: 'Wide midfielder with attacking license. Supplies crosses, presses high, and tracks the opposing full-back.', attrs: ['Pace','Crossing','Stamina','Dribbling'] },
          { key: 'CMR', role: 'CM',  pos: 'CM',  x: 64, y: 38, name: 'Right CM',            desc: 'Box-to-box engine on the right of the band of four. Runs beyond the striker and supports defensive transitions.', attrs: ['Work Rate','Passing','Late Runs','Pressing'] },
          { key: 'CML', role: 'CM',  pos: 'CM',  x: 64, y: 62, name: 'Left CM',             desc: 'Creator. Picks up between lines, links the pivot to the front, and threads passes for the wide players.', attrs: ['Vision','Passing','Dribbling','Composure'] },
          { key: 'LM',  role: 'LW',  pos: 'LM',  x: 64, y: 82, name: 'Left Midfielder',     desc: 'Inside forward / wide midfielder. Combines with the left back, attacks the half-space, and cuts inside to shoot.', attrs: ['Dribbling','Shooting','Pace','Creativity'] },
          { key: 'ST',  role: 'ST',  pos: 'ST',  x: 88, y: 50, name: 'Lone Striker',        desc: 'Isolated #9. Hold-up play and intelligent movement are essential; brings the band of four into attacking positions.', attrs: ['Hold-up','Finishing','Movement','Heading'] },
        ],
        connections: [[0,1],[0,2],[0,3],[0,4],[1,5],[2,5],[3,5],[4,5],[5,6],[5,7],[5,8],[5,9],[6,7],[7,8],[8,9],[6,10],[7,10],[8,10],[9,10]],
      },
      '343': {
        label: '3-4-3',
        positions: [
          { key: 'GK',  role: 'GK', pos: 'GK', x: 8,  y: 50, name: 'Goalkeeper',          desc: 'Sweeper-keeper behind a high back three. Comfort with the ball is mandatory — must support the build-up under pressure.', attrs: ['Sweeping','Distribution','Reflexes','Command'] },
          { key: 'CBR', role: 'CB', pos: 'CB', x: 22, y: 28, name: 'Right Centre Back',   desc: 'Wide of the back three. Aggressive, quick, and able to defend 1v1 in space when the wing-backs push high.', attrs: ['Pace','Tackling','Heading','1v1 Defending'] },
          { key: 'CBC', role: 'CB', pos: 'CB', x: 22, y: 50, name: 'Central CB',          desc: 'Leader of the back three. Reads danger, organizes the line, and steps into midfield with the ball when possible.', attrs: ['Leadership','Reading','Composure','Heading'] },
          { key: 'CBL', role: 'CB', pos: 'CB', x: 22, y: 72, name: 'Left Centre Back',    desc: 'Left-sided defender. Comfortable carrying the ball forward and starting attacks down the left channel.', attrs: ['Ball Play','Tackling','Pace','Positioning'] },
          { key: 'RWB', role: 'RB', pos: 'RWB', x: 45, y: 12, name: 'Right Wing-Back',    desc: 'Owns the right flank from box to box. The system stretches with both wing-backs flying forward simultaneously.', attrs: ['Stamina','Crossing','Pace','Tackling'] },
          { key: 'CMR', role: 'CM', pos: 'CM', x: 50, y: 38, name: 'Right CM',            desc: 'Half of the central pair. Box-to-box runner — links defense and attack, and supports the wing-back in transition.', attrs: ['Work Rate','Passing','Late Runs','Pressing'] },
          { key: 'CML', role: 'CM', pos: 'CM', x: 50, y: 62, name: 'Left CM',             desc: 'Other half of the pair. Deep-lying playmaker — controls tempo and provides the screen in front of the back three.', attrs: ['Positioning','Vision','Short Pass','Composure'] },
          { key: 'LWB', role: 'LB', pos: 'LWB', x: 45, y: 88, name: 'Left Wing-Back',     desc: 'Mirrors the right wing-back. Provides width on the left, presses high, and recovers all the way back when needed.', attrs: ['Stamina','Pace','Crossing','Tackling'] },
          { key: 'RW',  role: 'RW', pos: 'RW', x: 78, y: 22, name: 'Right Forward',       desc: 'Inside forward on the right of the front three. Combines with the wing-back and central striker to attack the box.', attrs: ['Dribbling','Pace','Finishing','Movement'] },
          { key: 'ST',  role: 'ST', pos: 'ST', x: 86, y: 50, name: 'Central Striker',     desc: 'Spearhead of the front three. Stretches the defense with runs in behind and finishes the chances created from the wide channels.', attrs: ['Finishing','Movement','Heading','Pressing'] },
          { key: 'LW',  role: 'LW', pos: 'LW', x: 78, y: 78, name: 'Left Forward',        desc: 'Inside forward on the left. Cuts inside onto stronger foot, links with the left wing-back, and attacks the back post on crosses.', attrs: ['Dribbling','Shooting','Pace','Creativity'] },
        ],
        connections: [[0,1],[0,2],[0,3],[1,4],[1,5],[2,5],[2,6],[3,6],[3,7],[4,5],[5,6],[6,7],[4,8],[5,8],[5,9],[6,9],[6,10],[7,10],[8,9],[9,10]],
      },
      '352': {
        label: '3-5-2',
        positions: [
          { key: 'GK',  role: 'GK', pos: 'GK', x: 8,  y: 50, name: 'Goalkeeper',           desc: 'Sweeper-keeper in a back 3 system. Must be comfortable with the ball and command a high defensive line confidently.', attrs: ['Sweeping','Distribution','Command','Reflexes'] },
          { key: 'CBR', role: 'CB', pos: 'CB', x: 25, y: 28, name: 'Right CB',             desc: 'Covers wide areas in a back 3. Must be aggressive, quick, and able to handle 1v1 situations out wide without support.', attrs: ['Pace','Tackling','Heading','1v1 Defending'] },
          { key: 'CBC', role: 'CB', pos: 'CB', x: 25, y: 50, name: 'Central CB',           desc: 'Leader of the three-man defense. Sweeper role — reads the game and organizes the defensive structure constantly.', attrs: ['Leadership','Heading','Reading','Composure'] },
          { key: 'CBL', role: 'CB', pos: 'CB', x: 25, y: 72, name: 'Left CB',              desc: 'Left of the back three — covers wide threats and is comfortable bringing the ball forward into midfield zones.', attrs: ['Tackling','Ball Play','Pace','Positioning'] },
          { key: 'RWB', role: 'RB', pos: 'RWB', x: 50, y: 10, name: 'Right Wing-Back',     desc: 'Covers the entire right flank — must defend and attack with relentless energy and pace. Key to width in this system.', attrs: ['Stamina','Crossing','Tackling','Pace'] },
          { key: 'CMR', role: 'CM', pos: 'CM', x: 50, y: 35, name: 'Right CM',             desc: 'One of three midfielders. Supports attacks on the right side and covers when the wing-back advances forward.', attrs: ['Passing','Runs','Work Rate','Vision'] },
          { key: 'CMC', role: 'CM', pos: 'CM', x: 50, y: 50, name: 'Pivot (#6)',           desc: 'Deep-lying playmaker. Controls tempo, screens the back three, and distributes from central deep positions.', attrs: ['Positioning','Short Pass','Tackling','Composure'] },
          { key: 'CML', role: 'CM', pos: 'CM', x: 50, y: 65, name: 'Left CM',              desc: 'Creative midfielder left of center. Carries from deep and links midfield to attack with intelligent movement.', attrs: ['Dribbling','Vision','Passing','Movement'] },
          { key: 'LWB', role: 'LB', pos: 'LWB', x: 50, y: 90, name: 'Left Wing-Back',      desc: 'Covers the entire left flank. Needs relentless stamina and technical quality to beat defenders and deliver crosses.', attrs: ['Stamina','Pace','Crossing','Defending'] },
          { key: 'STR', role: 'ST', pos: 'ST', x: 82, y: 35, name: 'Striker (R)',          desc: 'Combines with partner striker. Makes runs in behind, presses from the front, and creates space through movement.', attrs: ['Pace','Finishing','Pressing','Movement'] },
          { key: 'STL', role: 'ST', pos: 'ST', x: 82, y: 65, name: 'Striker (L)',          desc: 'Target striker. Holds up play, brings teammates into the game, and attacks crosses from both flanks.', attrs: ['Strength','Hold-up','Heading','Link Play'] },
        ],
        connections: [[0,1],[0,2],[0,3],[1,4],[2,4],[2,5],[2,6],[2,7],[3,8],[3,6],[4,5],[5,6],[6,7],[7,8],[5,9],[6,9],[7,10],[8,10],[9,10]],
      },
      '532': {
        label: '5-3-2',
        positions: [
          { key: 'GK',  role: 'GK', pos: 'GK', x: 8,  y: 50, name: 'Goalkeeper',          desc: 'Last man behind a deep five-man defense. Reflexes and command of the box are essential; play absorbs pressure before counter-attacking.', attrs: ['Reflexes','Command','Positioning','Distribution'] },
          { key: 'RWB', role: 'RB', pos: 'RWB', x: 22, y: 12, name: 'Right Wing-Back',    desc: 'Wide right defender that becomes a midfielder when in possession. Discipline matters — out of possession the team is a back five.', attrs: ['Stamina','Tackling','Crossing','Positioning'] },
          { key: 'CBR', role: 'CB', pos: 'CB', x: 22, y: 35, name: 'Right CB',            desc: 'Right of the back three. Aggressive on duels and able to step out to follow runners into midfield.', attrs: ['Tackling','Heading','Strength','Reading'] },
          { key: 'CBC', role: 'CB', pos: 'CB', x: 22, y: 50, name: 'Central CB',          desc: 'Central anchor of the back three. Reads the game and organizes the defense — the most important defensive voice on the pitch.', attrs: ['Leadership','Reading','Heading','Composure'] },
          { key: 'CBL', role: 'CB', pos: 'CB', x: 22, y: 65, name: 'Left CB',             desc: 'Left of the back three. Steps out with the ball comfortably and starts attacks with progressive passing.', attrs: ['Ball Play','Tackling','Pace','Positioning'] },
          { key: 'LWB', role: 'LB', pos: 'LWB', x: 22, y: 88, name: 'Left Wing-Back',     desc: 'Wide left defender / midfielder. Defensive solidity comes first; attacking width is the bonus.', attrs: ['Stamina','Tackling','Crossing','Pace'] },
          { key: 'CMR', role: 'CM', pos: 'CM', x: 52, y: 32, name: 'Right CM',            desc: 'Box-to-box on the right of the central three. Adds running power and supports the right wing-back in attack.', attrs: ['Work Rate','Passing','Late Runs','Pressing'] },
          { key: 'CMC', role: 'CM', pos: 'CM', x: 52, y: 50, name: 'Pivot',               desc: 'Holding midfielder at the heart of the team. Screens the back three, recycles possession, and dictates tempo.', attrs: ['Positioning','Short Pass','Tackling','Composure'] },
          { key: 'CML', role: 'CM', pos: 'CM', x: 52, y: 68, name: 'Left CM',             desc: 'Creator of the central three. Picks up between lines and links the back three to the front two via vertical passes.', attrs: ['Vision','Passing','Dribbling','Composure'] },
          { key: 'STR', role: 'ST', pos: 'ST', x: 84, y: 38, name: 'Striker (R)',         desc: 'Counter-attacking forward. Pace in transition and clinical finishing turn defensive solidity into goals.', attrs: ['Pace','Finishing','Movement','Pressing'] },
          { key: 'STL', role: 'ST', pos: 'ST', x: 84, y: 62, name: 'Striker (L)',         desc: 'Target striker. Holds up play, brings the wing-backs into the attack, and finishes from second-ball opportunities.', attrs: ['Strength','Hold-up','Heading','Link Play'] },
        ],
        connections: [[0,1],[0,2],[0,3],[0,4],[0,5],[1,2],[2,3],[3,4],[4,5],[1,6],[2,6],[3,7],[4,8],[5,8],[6,7],[7,8],[6,9],[7,9],[7,10],[8,10],[9,10]],
      },
    };

    /* Map raw roster `posicion` (Spanish DB values) to a coarse role family. */
    function posToFamily(pos) {
      if (!pos) return 'OTHER';
      switch (pos) {
        case 'Portero':       return 'GK';
        case 'Defensa':       return 'DEF';
        case 'Mediocampista': return 'MID';
        case 'Delantero':     return 'FWD';
        default:              return 'OTHER';
      }
    }

    /* Each formation slot's "natural" role family — for auto-assignment. */
    function slotFamily(role) {
      if (role === 'GK') return 'GK';
      if (role === 'CB' || role === 'RB' || role === 'LB') return 'DEF';
      if (role === 'CM' || role === 'CDM' || role === 'CAM') return 'MID';
      if (role === 'RW' || role === 'LW' || role === 'ST')   return 'FWD';
      return 'MID';
    }

    /* Sort by dorsal ascending (then by id as a stable tiebreaker). */
    function byDorsal(a, b) {
      var na = parseInt(a.num || '999', 10);
      var nb = parseInt(b.num || '999', 10);
      if (na === nb) return (a.id || 0) - (b.id || 0);
      return na - nb;
    }

    /* Greedy auto-assignment.
     *
     * Walks the formation slots in declaration order, popping the best-fit
     * available player from each role bucket (GK / DEF / MID / FWD).
     * If a bucket runs out we fall through to the next one and finally to
     * "anyone", so a small or unbalanced squad still fills the XI.
     */
    function autoAssign(positions, players) {
      var buckets = { GK: [], DEF: [], MID: [], FWD: [], OTHER: [] };
      players.forEach(function (p) { buckets[posToFamily(p.pos)].push(p); });
      Object.keys(buckets).forEach(function (k) { buckets[k].sort(byDorsal); });
      var fallbackOrder = {
        GK:  ['GK', 'DEF', 'MID', 'FWD', 'OTHER'],
        DEF: ['DEF', 'MID', 'OTHER', 'FWD'],
        MID: ['MID', 'DEF', 'FWD', 'OTHER'],
        FWD: ['FWD', 'MID', 'OTHER', 'DEF'],
      };

      var assignment = {};
      positions.forEach(function (slot) {
        var fam = slotFamily(slot.role);
        var order = fallbackOrder[fam] || ['MID','DEF','FWD','OTHER','GK'];
        for (var i = 0; i < order.length; i++) {
          var bucket = buckets[order[i]];
          if (bucket && bucket.length) {
            assignment[slot.key] = bucket.shift();
            return;
          }
        }
        assignment[slot.key] = null;
      });
      return assignment;
    }

    /* ── State ──────────────────────────────────────────────────────── */
    var currentF = '433';
    var currentCat = 'all';
    var activeIdx = null;
    var lineup = {};      // slot key → player object (or null)
    var pendingSwap = false;

    function currentPool() {
      return POOL[currentCat] || POOL.all || [];
    }

    function rebuildLineup() {
      var positions = FORMATIONS[currentF].positions;
      lineup = autoAssign(positions, currentPool());
    }

    function computeBench() {
      var inUse = {};
      Object.keys(lineup).forEach(function (k) {
        var pl = lineup[k];
        if (pl && pl.id) inUse[pl.id] = true;
      });
      return currentPool().filter(function (p) { return !inUse[p.id]; }).sort(byDorsal);
    }

    /* ── Render ─────────────────────────────────────────────────────── */
    function avatarHtml(player, fallbackLabel) {
      if (!player) return fallbackLabel;
      if (player.photo) {
        return '<img src="' + player.photo + '" alt="' + (player.name || '') +
          '" onerror="this.style.display=\'none\';this.nextSibling.style.display=\'flex\'">' +
          '<span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">' +
          (player.initials || fallbackLabel) + '</span>';
      }
      return player.initials || fallbackLabel;
    }

    function renderFormation() {
      activeIdx = null;
      pendingSwap = false;

      var strip = document.getElementById('vfv-strip');
      if (!strip) return;

      pitch.querySelectorAll('.vfv-pos').forEach(function (d) { d.remove(); });

      var formation = FORMATIONS[currentF];
      if (!formation) return;
      var positions = formation.positions;

      var connG = document.getElementById('vfv-lines');
      if (connG) {
        connG.innerHTML = '';
        (formation.connections || []).forEach(function (pair) {
          var a = positions[pair[0]];
          var b = positions[pair[1]];
          if (!a || !b) return;
          var line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
          line.setAttribute('x1', a.x);
          line.setAttribute('y1', a.y);
          line.setAttribute('x2', b.x);
          line.setAttribute('y2', b.y);
          line.setAttribute('stroke', '#FF6B00');
          line.setAttribute('stroke-width', '0.5');
          connG.appendChild(line);
        });
      }

      positions.forEach(function (p, i) {
        var player = lineup[p.key] || null;
        var div = document.createElement('div');
        div.className = 'vfv-pos';
        div.style.left = p.x + '%';
        div.style.top = p.y + '%';
        div.dataset.index = String(i);
        div.innerHTML =
          '<div class="vfv-pos__dot">' + avatarHtml(player, p.pos) + '</div>' +
          '<span class="vfv-pos__label">' + p.pos + '</span>';
        div.addEventListener('click', function () { selectPos(i); });
        pitch.appendChild(div);
      });

      strip.innerHTML = '';
      positions.forEach(function (p, i) {
        var player = lineup[p.key] || null;
        var chip = document.createElement('div');
        chip.className = 'vfv-chip';
        chip.dataset.index = String(i);
        var parts = player ? player.name.split(' ') : [];
        var chipName = player ? parts[0] + ' ' + (parts[1] || '') : p.pos;
        var numHtml = player && player.num ? '<span class="vfv-chip__num">#' + player.num + '</span>' : '';
        chip.innerHTML = numHtml + chipName;
        chip.addEventListener('click', function () { selectPos(i); });
        strip.appendChild(chip);
      });

      renderBench();
      resetDetail();
    }

    function renderBench() {
      var container = document.getElementById('vfv-bench');
      var list = document.getElementById('vfv-bench-list');
      var hint = document.getElementById('vfv-bench-hint');
      if (!container || !list) return;
      var bench = computeBench();
      if (bench.length === 0) {
        container.hidden = true;
        return;
      }
      container.hidden = false;
      list.innerHTML = '';
      if (hint) {
        hint.textContent = pendingSwap
          ? 'Tap a bench player to swap into the highlighted position'
          : 'Pick a position on the pitch to substitute';
      }
      bench.forEach(function (p) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'vfv-bench__chip';
        item.dataset.playerId = String(p.id);
        var avatar = '<span class="vfv-bench__avatar">' +
          (p.photo ? '<img src="' + p.photo + '" alt="' + p.name + '" onerror="this.style.display=\'none\';this.nextSibling.style.display=\'flex\'"><span class="vfv-bench__avatar-fb" style="display:none;">' + p.initials + '</span>' : '<span class="vfv-bench__avatar-fb">' + p.initials + '</span>') +
          '</span>';
        var meta = '<span class="vfv-bench__meta">' +
          '<span class="vfv-bench__name">' + p.name + '</span>' +
          '<span class="vfv-bench__sub">' +
          (p.num ? '#' + p.num + ' · ' : '') + (p.pos || '—') +
          '</span></span>';
        item.innerHTML = avatar + meta;
        item.addEventListener('click', function () { applySubstitution(p.id); });
        list.appendChild(item);
      });
    }

    function selectPos(index) {
      activeIdx = index;
      pendingSwap = true;
      var formation = FORMATIONS[currentF];
      var p = formation.positions[index];
      var player = lineup[p.key] || null;
      var fname = formation.label;

      document.querySelectorAll('.vfv-pos').forEach(function (d) { d.classList.remove('active'); });
      document.querySelectorAll('.vfv-chip').forEach(function (d) { d.classList.remove('active'); });
      var dot = document.querySelector('.vfv-pos[data-index="' + index + '"]');
      var chip = document.querySelector('.vfv-chip[data-index="' + index + '"]');
      if (dot) dot.classList.add('active');
      if (chip) {
        chip.classList.add('active');
        chip.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }

      var attrsHtml = p.attrs.map(function (a) { return '<div class="vfv-attr">' + a + '</div>'; }).join('');

      var assignedHtml = '';
      if (player && player.name) {
        var avInner = player.photo
          ? '<img src="' + player.photo + '" alt="' + player.name + '" onerror="this.style.display=\'none\';this.parentNode.textContent=\'' + (player.initials || '') + '\'">'
          : (player.initials || '');
        assignedHtml =
          '<div class="vfv-assigned">' +
          '<div class="vfv-assigned__av">' + avInner + '</div>' +
          '<div class="vfv-assigned__body">' +
          '<div class="vfv-assigned__name">' + player.name + '</div>' +
          '<div class="vfv-assigned__num">' + (player.num ? '#' + player.num + ' · ' : '') + 'VCF Houston</div>' +
          '</div>' +
          '<button type="button" class="vfv-assigned__sub" data-action="open-bench">Substitute</button>' +
          '</div>';
      } else {
        assignedHtml =
          '<div class="vfv-assigned vfv-assigned--empty">' +
          '<div>No player assigned to this slot.</div>' +
          '<button type="button" class="vfv-assigned__sub" data-action="open-bench">Pick player</button>' +
          '</div>';
      }

      var detail = document.getElementById('vfv-detail');
      if (detail) {
        detail.innerHTML =
          '<div class="vfv-detail-inner">' +
          '<div class="vfv-detail__tag">' + p.pos + ' &middot; ' + fname + '</div>' +
          '<div class="vfv-detail__name">' + p.name + '</div>' +
          '<div class="vfv-detail__desc">' + p.desc + '</div>' +
          assignedHtml +
          '<div class="vfv-attrs">' + attrsHtml + '</div>' +
          '</div>';

        var subBtn = detail.querySelector('[data-action="open-bench"]');
        if (subBtn) {
          subBtn.addEventListener('click', function () {
            var benchEl = document.getElementById('vfv-bench');
            if (benchEl) benchEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          });
        }
      }
      renderBench();
    }

    function applySubstitution(playerId) {
      if (activeIdx === null) return;
      var positions = FORMATIONS[currentF].positions;
      var slot = positions[activeIdx];
      if (!slot) return;
      var newPlayer = currentPool().find(function (p) { return p.id === playerId; });
      if (!newPlayer) return;

      // If the incoming player is currently on the pitch in another slot,
      // swap them — the previous occupant of `slot` takes the other slot.
      var current = lineup[slot.key] || null;
      var otherSlotKey = null;
      Object.keys(lineup).forEach(function (k) {
        var pl = lineup[k];
        if (pl && pl.id === playerId) otherSlotKey = k;
      });

      lineup[slot.key] = newPlayer;
      if (otherSlotKey) {
        lineup[otherSlotKey] = current;
      }

      renderFormation();
      // Re-highlight the slot we just substituted, with the new player.
      selectPos(activeIdx);
    }

    function resetDetail() {
      var detail = document.getElementById('vfv-detail');
      if (!detail) return;
      detail.innerHTML =
        '<div class="vfv-placeholder">' +
        '<div class="vfv-placeholder__icon">&#9917;</div>' +
        '<div class="vfv-placeholder__text">Select a position</div>' +
        '<div class="vfv-placeholder__sub">Tap any dot on the pitch</div>' +
        '</div>';
    }

    /* ── Wiring ─────────────────────────────────────────────────────── */
    document.querySelectorAll('#formation-tabs .vcf-formation__tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('#formation-tabs .vcf-formation__tab').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var f = btn.getAttribute('data-f');
        if (f && FORMATIONS[f]) {
          currentF = f;
          rebuildLineup();
          renderFormation();
        }
      });
    });

    var catSelect = document.getElementById('formation-cat');
    if (catSelect) {
      catSelect.addEventListener('change', function () {
        currentCat = catSelect.value || 'all';
        rebuildLineup();
        renderFormation();
      });
    }

    var resetBtn = document.getElementById('formation-reset');
    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        rebuildLineup();
        renderFormation();
      });
    }

    rebuildLineup();
    renderFormation();
  })();

  /* Roster: search + position filters */
  (function () {
    var roster = document.getElementById('roster');
    var searchInput = document.getElementById('vr-search');
    if (!roster || !searchInput) return;

    var activeGroup = 'all';
    var searchVal = '';

    function applyFilters() {
      var cards = roster.querySelectorAll('.vr-card');
      var visible = 0;
      cards.forEach(function (card) {
        var groupMatch = activeGroup === 'all' || card.getAttribute('data-group') === activeGroup;
        var nameAttr = card.getAttribute('data-name') || '';
        var nameMatch = nameAttr.indexOf(searchVal) !== -1;
        var show = groupMatch && nameMatch;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
      });
      var empty = document.getElementById('vr-empty');
      if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
    }

    roster.querySelectorAll('.vr-filter').forEach(function (btn) {
      btn.addEventListener('click', function () {
        roster.querySelectorAll('.vr-filter').forEach(function (b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        activeGroup = btn.getAttribute('data-group') || 'all';
        applyFilters();
      });
    });

    searchInput.addEventListener('input', function () {
      searchVal = this.value.toLowerCase().trim();
      var clearBtn = document.getElementById('vr-search-clear');
      if (clearBtn) clearBtn.style.display = searchVal ? 'block' : 'none';
      applyFilters();
    });

    var searchClear = document.getElementById('vr-search-clear');
    if (searchClear) {
      searchClear.addEventListener('click', function () {
        searchInput.value = '';
        searchVal = '';
        this.style.display = 'none';
        searchInput.focus();
        applyFilters();
      });
    }
  })();

  /* Reels: entrance animation + hover/tap play */
  (function () {
    var cards = document.querySelectorAll('[data-vcf-reel-card]');
    if (!cards.length) return;

    function initReelAnimations() {
      if (typeof IntersectionObserver === 'undefined') {
        cards.forEach(function (card) {
          card.classList.add('vcf-reel-visible');
        });
        return;
      }

      var observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var card = entry.target;
            var idx = parseInt(card.getAttribute('data-index') || '0', 10);
            setTimeout(function () {
              card.classList.add('vcf-reel-visible');
            }, idx * 60);
            observer.unobserve(card);
          });
        },
        { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
      );

      cards.forEach(function (card) {
        observer.observe(card);
      });
    }

    function initVideoInteraction() {
      cards.forEach(function (card) {
        var video = card.querySelector('video.vcf-reel-media');
        if (!video) return;

        card.addEventListener('mouseenter', function () {
          video.play().catch(function () {});
        });

        card.addEventListener('mouseleave', function () {
          video.pause();
          video.currentTime = 0;
        });

        card.addEventListener('click', function () {
          if (video.paused) {
            video.play().catch(function () {});
          } else {
            video.pause();
            video.currentTime = 0;
          }
        });
      });
    }

    initReelAnimations();
    initVideoInteraction();
  })();
})();
