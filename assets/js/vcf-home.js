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

  /* Formation viewer (enhanced: pitch, connections, roster avatars) */
  (function () {
    var dataEl = document.getElementById('vfv-formation-data');
    var pitch = document.getElementById('vfv-pitch');
    if (!dataEl || !pitch) return;

    var PLAYERS = {};
    try {
      var parsed = JSON.parse(dataEl.textContent || '{}');
      PLAYERS = parsed.players || {};
    } catch (e) {
      return;
    }

    var FORMATIONS = {
      '433': [
        { key: 'GK', pos: 'GK', x: 8, y: 50, name: 'Goalkeeper', desc: 'Last line of defense. Commands the penalty area, organizes the backline, and distributes play from deep to start attacks.', attrs: ['Reflexes', 'Distribution', 'Command', 'Leadership'] },
        { key: 'RB', pos: 'RB', x: 22, y: 20, name: 'Right Back', desc: 'Defensive solidity with the freedom to overlap and join wide attacks. Must track back quickly and deny space to opposing wingers.', attrs: ['Tackling', 'Crossing', 'Stamina', 'Positioning'] },
        { key: 'CBR', pos: 'CB', x: 22, y: 40, name: 'Centre Back (R)', desc: 'Wins aerial duels, blocks shots, and starts build-up play. Must read the game and communicate constantly with the defensive line.', attrs: ['Heading', 'Tackling', 'Strength', 'Composure'] },
        { key: 'CBL', pos: 'CB', x: 22, y: 60, name: 'Centre Back (L)', desc: 'Covers central zones, sweeps behind the line, and is comfortable carrying the ball out of defense under pressure.', attrs: ['Pace', 'Interceptions', 'Ball Play', 'Positioning'] },
        { key: 'LB', pos: 'LB', x: 22, y: 80, name: 'Left Back', desc: 'Covers the entire left flank, balancing defensive duties with attacking support — providing width when the team is in possession.', attrs: ['Crossing', 'Stamina', 'Tackling', 'Speed'] },
        { key: 'CMR', pos: 'CM', x: 52, y: 28, name: 'Right CM', desc: 'Creative midfielder. Carries from deep, threads passes between lines, and arrives late in the box to add a goal threat.', attrs: ['Vision', 'Passing', 'Dribbling', 'Late Runs'] },
        { key: 'CMC', pos: 'CM', x: 52, y: 50, name: 'Central CM', desc: 'Box-to-box engine. Controls tempo, presses high, wins second balls, and supports both defensive and attacking phases equally.', attrs: ['Work Rate', 'Passing', 'Pressing', 'Balance'] },
        { key: 'CML', pos: 'CM', x: 52, y: 72, name: 'Left CM', desc: 'Defensive-minded midfielder. Screens the back four, disrupts opposition attacks, and recycles possession intelligently.', attrs: ['Tackling', 'Positioning', 'Short Pass', 'Strength'] },
        { key: 'RW', pos: 'RW', x: 80, y: 18, name: 'Right Winger', desc: 'Pace and directness on the right. Takes on defenders 1v1, delivers dangerous crosses, and cuts inside to create goal opportunities.', attrs: ['Pace', 'Dribbling', 'Crossing', 'Finishing'] },
        { key: 'ST', pos: 'ST', x: 86, y: 50, name: 'Striker', desc: 'Leads the line — makes intelligent runs in behind, holds up play to bring others into the game, and finishes chances calmly.', attrs: ['Finishing', 'Movement', 'Hold-up', 'Heading'] },
        { key: 'LW', pos: 'LW', x: 80, y: 82, name: 'Left Winger', desc: 'Cuts inside from the left onto the stronger foot to create shooting opportunities. Provides width and delivers from deep positions.', attrs: ['Dribbling', 'Pace', 'Shooting', 'Creativity'] },
      ],
      '442': [
        { key: 'GK', pos: 'GK', x: 8, y: 50, name: 'Goalkeeper', desc: 'Commands the box and organizes the four-man defense. Distribution is key to transition play.', attrs: ['Reflexes', 'Distribution', 'Command', 'Shot-stopping'] },
        { key: 'RB', pos: 'RB', x: 25, y: 18, name: 'Right Back', desc: 'Provides defensive width and supports attacks down the right flank with crosses and overlapping runs.', attrs: ['Tackling', 'Crossing', 'Stamina', 'Positioning'] },
        { key: 'CBR', pos: 'CB', x: 25, y: 38, name: 'CB Right', desc: 'Wins aerial duels and blocks attacks through the center. Strong and reliable in 1v1 defending.', attrs: ['Heading', 'Strength', 'Reading', 'Composure'] },
        { key: 'CBL', pos: 'CB', x: 25, y: 62, name: 'CB Left', desc: 'Covers the left channel and builds from the back. Comfortable under pressure with the ball at their feet.', attrs: ['Pace', 'Tackling', 'Ball Play', 'Positioning'] },
        { key: 'LB', pos: 'LB', x: 25, y: 82, name: 'Left Back', desc: 'Defensive cover on the left with a license to join attacks. Must balance positioning and timing of forward runs.', attrs: ['Speed', 'Crossing', 'Stamina', 'Tackling'] },
        { key: 'CMR', pos: 'RM', x: 50, y: 18, name: 'Right Mid', desc: 'Works the right channel tirelessly — delivers crosses, creates chances, and tracks back to support the full-back defensively.', attrs: ['Stamina', 'Crossing', 'Tackling', 'Pace'] },
        { key: 'CML', pos: 'CM', x: 50, y: 40, name: 'Central CM (R)', desc: 'Dynamic midfielder who covers ground in both directions, linking play and arriving into the box to support attacks.', attrs: ['Work Rate', 'Passing', 'Pressing', 'Runs'] },
        { key: 'CMC', pos: 'CM', x: 50, y: 60, name: 'Central CM (L)', desc: 'Screens the defense and distributes possession calmly. Keeps the team organized and sets the tempo.', attrs: ['Positioning', 'Short Pass', 'Tackling', 'Vision'] },
        { key: 'LM', pos: 'LM', x: 50, y: 82, name: 'Left Mid', desc: 'Provides width on the left and supports both attacking and defensive phases. High work rate required.', attrs: ['Pace', 'Dribbling', 'Crossing', 'Work Rate'] },
        { key: 'ST', pos: 'ST', x: 80, y: 35, name: 'Striker (R)', desc: 'Runs in behind and finishes. Partners the second striker to press defenders and create space through movement.', attrs: ['Finishing', 'Speed', 'Movement', 'Pressing'] },
        { key: 'LW', pos: 'ST', x: 80, y: 65, name: 'Striker (L)', desc: 'Hold-up striker who brings teammates into play, links midfield and attack, and attacks the far post on crosses.', attrs: ['Strength', 'Hold-up', 'Heading', 'Link Play'] },
      ],
      '352': [
        { key: 'GK', pos: 'GK', x: 8, y: 50, name: 'Goalkeeper', desc: 'Sweeper-keeper in a back 3 system. Must be comfortable with the ball and command a high defensive line confidently.', attrs: ['Sweeping', 'Distribution', 'Command', 'Reflexes'] },
        { key: 'RB', pos: 'CB', x: 25, y: 28, name: 'Right CB', desc: 'Covers wide areas in a back 3. Must be aggressive, quick, and able to handle 1v1 situations out wide without support.', attrs: ['Pace', 'Tackling', 'Heading', '1v1 Defending'] },
        { key: 'CBR', pos: 'CB', x: 25, y: 50, name: 'Central CB', desc: 'Leader of the three-man defense. Sweeper role — reads the game and organizes the defensive structure constantly.', attrs: ['Leadership', 'Heading', 'Reading', 'Composure'] },
        { key: 'CBL', pos: 'CB', x: 25, y: 72, name: 'Left CB', desc: 'Left of the back three — covers wide threats and is comfortable bringing the ball forward into midfield zones.', attrs: ['Tackling', 'Ball Play', 'Pace', 'Positioning'] },
        { key: 'CMR', pos: 'WB', x: 50, y: 10, name: 'Right Wing-Back', desc: 'Covers the entire right flank — must defend and attack with relentless energy and pace. Key to width in this system.', attrs: ['Stamina', 'Crossing', 'Tackling', 'Pace'] },
        { key: 'CML', pos: 'CM', x: 50, y: 35, name: 'Right CM', desc: 'One of three midfielders. Supports attacks on the right side and covers when the wing-back advances forward.', attrs: ['Passing', 'Runs', 'Work Rate', 'Vision'] },
        { key: 'CMC', pos: 'CM', x: 50, y: 50, name: 'Pivot', desc: 'Deep-lying playmaker. Controls tempo, screens the back three, and distributes from central deep positions.', attrs: ['Positioning', 'Short Pass', 'Tackling', 'Composure'] },
        { key: 'LB', pos: 'CM', x: 50, y: 65, name: 'Left CM', desc: 'Creative midfielder left of center. Carries from deep and links midfield to attack with intelligent movement.', attrs: ['Dribbling', 'Vision', 'Passing', 'Movement'] },
        { key: 'LW', pos: 'WB', x: 50, y: 90, name: 'Left Wing-Back', desc: 'Covers the entire left flank. Needs relentless stamina and technical quality to beat defenders and deliver crosses.', attrs: ['Stamina', 'Pace', 'Crossing', 'Defending'] },
        { key: 'ST', pos: 'ST', x: 82, y: 35, name: 'Striker (R)', desc: 'Combines with partner striker. Makes runs in behind, presses from the front, and creates space through movement.', attrs: ['Pace', 'Finishing', 'Pressing', 'Movement'] },
        { key: 'RW', pos: 'ST', x: 82, y: 65, name: 'Striker (L)', desc: 'Target striker. Holds up play, brings teammates into the game, and attacks crosses from both flanks.', attrs: ['Strength', 'Hold-up', 'Heading', 'Link Play'] },
      ],
    };

    var CONNECTIONS = {
      '433': [[0, 1], [0, 2], [0, 3], [0, 4], [1, 5], [2, 5], [2, 6], [3, 6], [3, 7], [4, 7], [5, 8], [6, 9], [7, 10], [5, 6], [6, 7], [8, 9], [9, 10]],
      '442': [[0, 1], [0, 2], [0, 3], [0, 4], [1, 5], [2, 6], [3, 7], [4, 8], [5, 6], [6, 7], [7, 8], [5, 9], [6, 9], [7, 10], [8, 10], [9, 10]],
      '352': [[0, 1], [0, 2], [0, 3], [1, 4], [2, 4], [2, 5], [2, 6], [2, 7], [3, 8], [3, 6], [4, 5], [5, 6], [6, 7], [7, 8], [5, 9], [6, 9], [7, 10], [8, 10], [9, 10]],
    };

    var currentF = '433';
    var activeIdx = null;

    function renderFormation(key) {
      currentF = key;
      activeIdx = null;

      var strip = document.getElementById('vfv-strip');
      if (!strip) return;

      pitch.querySelectorAll('.vfv-pos').forEach(function (d) {
        d.remove();
      });

      var positions = FORMATIONS[key];
      if (!positions) return;

      var connG = document.getElementById('vfv-lines');
      if (connG) {
        connG.innerHTML = '';
        (CONNECTIONS[key] || []).forEach(function (pair) {
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
        var player = PLAYERS[p.key] || null;

        var div = document.createElement('div');
        div.className = 'vfv-pos';
        div.style.left = p.x + '%';
        div.style.top = p.y + '%';
        div.dataset.index = String(i);

        var avHtml = '';
        if (player && player.photo) {
          avHtml =
            '<img src="' +
            player.photo +
            '" alt="' +
            (player.name || '') +
            '" onerror="this.style.display=\'none\';this.nextSibling.style.display=\'flex\'">' +
            '<span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">' +
            (player.initials || p.pos) +
            '</span>';
        } else {
          avHtml = player ? player.initials || p.pos : p.pos;
        }

        div.innerHTML =
          '<div class="vfv-pos__dot">' + avHtml + '</div>' + '<span class="vfv-pos__label">' + p.pos + '</span>';

        div.addEventListener('click', function () {
          selectPos(i);
        });
        pitch.appendChild(div);
      });

      strip.innerHTML = '';
      positions.forEach(function (p, i) {
        var player = PLAYERS[p.key] || null;
        var chip = document.createElement('div');
        chip.className = 'vfv-chip';
        chip.dataset.index = String(i);
        var parts = player ? player.name.split(' ') : [];
        var chipName = player ? parts[0] + ' ' + (parts[1] || '') : p.pos;
        var numHtml = player && player.num ? '<span class="vfv-chip__num">#' + player.num + '</span>' : '';
        chip.innerHTML = numHtml + chipName;
        chip.addEventListener('click', function () {
          selectPos(i);
        });
        strip.appendChild(chip);
      });

      resetDetail();
    }

    function selectPos(index) {
      activeIdx = index;
      var p = FORMATIONS[currentF][index];
      var player = PLAYERS[p.key] || null;
      var fname = currentF.replace('433', '4-3-3').replace('442', '4-4-2').replace('352', '3-5-2');

      document.querySelectorAll('.vfv-pos').forEach(function (d) {
        d.classList.remove('active');
      });
      document.querySelectorAll('.vfv-chip').forEach(function (d) {
        d.classList.remove('active');
      });
      var dot = document.querySelector('.vfv-pos[data-index="' + index + '"]');
      var chip = document.querySelector('.vfv-chip[data-index="' + index + '"]');
      if (dot) dot.classList.add('active');
      if (chip) {
        chip.classList.add('active');
        chip.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }

      var attrsHtml = p.attrs
        .map(function (a) {
          return '<div class="vfv-attr">' + a + '</div>';
        })
        .join('');

      var assignedHtml = '';
      if (player && player.name) {
        var avInner = '';
        if (player.photo) {
          avInner =
            '<img src="' +
            player.photo +
            '" alt="' +
            player.name +
            '" onerror="this.style.display=\'none\';this.parentNode.textContent=\'' +
            (player.initials || '') +
            '\'">';
        } else {
          avInner = player.initials || '';
        }
        assignedHtml =
          '<div class="vfv-assigned">' +
          '<div class="vfv-assigned__av">' +
          avInner +
          '</div>' +
          '<div>' +
          '<div class="vfv-assigned__name">' +
          player.name +
          '</div>' +
          '<div class="vfv-assigned__num">' +
          (player.num ? '#' + player.num + ' · ' : '') +
          'VCF Houston</div>' +
          '</div>' +
          '</div>';
      }

      var detail = document.getElementById('vfv-detail');
      if (detail) {
        detail.innerHTML =
          '<div class="vfv-detail-inner">' +
          '<div class="vfv-detail__tag">' +
          p.pos +
          ' &middot; ' +
          fname +
          '</div>' +
          '<div class="vfv-detail__name">' +
          p.name +
          '</div>' +
          '<div class="vfv-detail__desc">' +
          p.desc +
          '</div>' +
          assignedHtml +
          '<div class="vfv-attrs">' +
          attrsHtml +
          '</div>' +
          '</div>';
      }
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

    document.querySelectorAll('#formation-tabs .vcf-formation__tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('#formation-tabs .vcf-formation__tab').forEach(function (b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        var f = btn.getAttribute('data-f');
        if (f) renderFormation(f);
      });
    });

    renderFormation('433');
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

  /* Reels: tap to play/pause */
  document.querySelectorAll('[data-vcf-reel]').forEach(function (wrap) {
    var v = wrap.querySelector('video');
    if (!v) return;
    wrap.addEventListener('click', function () {
      if (v.paused) {
        v.play().catch(function () {});
      } else {
        v.pause();
      }
    });
  });
})();
