/**
 * VCF Academy Houston - Main JS
 * Navbar collapse, smooth scroll for anchor links
 */

var VCF_ROLES = {
    goalkeeper: { title: 'Goalkeeper (1)', desc: 'The only player allowed to touch the ball with their hands inside the area. Your role: keep the ball out of the net. Key skills: reflexes, communication to organise the defence, and good footwork to start the attack.', pro: 'Pro: Command your area and talk to the back line.', jugada: 'Typical play: Start a counter with a quick throw or pass to the full-back.', vcfRef: 'Like Giorgi Mamardashvili (VCF)' },
    lateral_izq: { title: 'Left back (2)', desc: 'Plays on the left flank. Besides defending, you support the attack by overlapping and sending crosses into the box.', pro: 'Pro: Carrilero — join the attack when we have the ball.', jugada: 'Typical play: Overlap, receive from the interior, cross to the 9.', vcfRef: 'Like José Gaya (VCF academy)' },
    central_izq: { title: 'Left centre-back (3)', desc: 'In front of the keeper. Your main job is to stop opposition attacks. Central defenders are usually strong in the air and cut out passes.', pro: 'Pro: Win the first ball and play out from the back.', jugada: 'Typical play: Win the ball and play out to the pivote or full-back.', vcfRef: 'Like Gabriel Paulista (VCF)' },
    central_der: { title: 'Right centre-back (4)', desc: 'Partner in the middle of the back four. Neutralise attacks, win aerial duels, and intercept passes. Build from the back.', pro: 'Pro: Stay compact with your centre-back partner.', jugada: 'Typical play: Win the ball and play out to the pivote or full-back.', vcfRef: 'Like Mouctar Diakhaby (VCF)' },
    lateral_der: { title: 'Right back (5)', desc: 'Plays on the right flank. Defend your side and support the attack with runs down the wing and quality crosses.', pro: 'Pro: Carrilero — join the attack when we have the ball.', jugada: 'Typical play: Overlap, receive from the interior, cross to the 9.', vcfRef: 'Like Thierry Correia (VCF)' },
    pivote: { title: 'Defensive midfielder (6)', desc: 'The engine and balance of the team. You help the defence, win the ball back, and connect defence with attack.', pro: 'Pro: The pivote is the team\'s balance — protect the back four.', jugada: 'Typical play: Recover the ball and play the first pass to an interior or winger.', vcfRef: 'Like Javi Guerra (VCF academy)' },
    interior_izq: { title: 'Left central midfielder (7)', desc: 'Interior or box-to-box midfielder. You distribute the ball and need great stamina to cover the whole pitch.', pro: 'Pro: Receive between the lines and turn to play forward.', jugada: 'Typical play: Receive from the pivote, turn, and play to the winger or 9.', vcfRef: 'Like Pepelu (VCF)' },
    interior_der: { title: 'Right central midfielder (8)', desc: 'Interior or box-to-box midfielder. You distribute play and link the pivote with the wingers and striker.', pro: 'Pro: The mediapunta is the creative one — the "last pass" or shot.', jugada: 'Typical play: Receive from the pivote, turn, and play to the winger or 9.', vcfRef: 'Like André Almeida (VCF)' },
    extremo_izq: { title: 'Left winger (9)', desc: 'Your home is the opposition half. Fast and skilful, you play on the wing to beat the full-back and create or score.', pro: 'Pro: Take on your defender and either cross or cut inside.', jugada: 'Typical play: Take on the defender, cross to the 9 or cut inside to shoot.', vcfRef: 'Like Ferran Torres (VCF academy)' },
    nueve: { title: 'Striker (10)', desc: 'The reference up front. Your success is measured in goals. Finish moves with your feet or head.', pro: 'Pro: Hold up the ball and bring wingers into play.', jugada: 'Typical play: Hold the ball, lay off to wingers, or finish in the box.', vcfRef: 'Like Hugo Duro (VCF)' },
    extremo_der: { title: 'Right winger (11)', desc: 'Fast and skilful on the right wing. Your job is to beat the full-back and create chances or score.', pro: 'Pro: Take on your defender and either cross or cut inside.', jugada: 'Typical play: Take on the defender, cross to the 9 or cut inside to shoot.', vcfRef: 'Like Diego López (VCF academy)' }
};
var POS_TO_ROLE = { 'Portero': 'goalkeeper', 'Defensa': 'defense', 'Mediocampista': 'midfield', 'Delantero': 'forward' };
var SUB_POS_TO_ROLE = { 'Portero': 'goalkeeper', 'Lateral izquierdo': 'lateral_izq', 'Central izquierdo': 'central_izq', 'Central derecho': 'central_der', 'Lateral derecho': 'lateral_der', 'Pivote': 'pivote', 'Interior izquierdo': 'interior_izq', 'Interior derecho': 'interior_der', 'Extremo izquierdo': 'extremo_izq', 'Delantero centro (9)': 'nueve', 'Extremo derecho': 'extremo_der' };
window.vcfCloseRoleModal = function () {};
/* Generic roles for roster/modal tooltips when only main position is known */
VCF_ROLES.defense = { title: 'Defense', desc: 'The wall. Central defenders and full-backs protect the zone, win duels, and recover the ball. They build from the back and support the midfield.', pro: 'Pro: Carrileros join the attack when the team has possession.', jugada: '', vcfRef: '' };
VCF_ROLES.midfield = { title: 'Midfield', desc: 'The engine room. Pivotes and creative midfielders control the tempo, keep the ball, and give balance. They connect defense and attack.', pro: 'Pro: The enganche (number 10) is the playmaker in the final third.', jugada: '', vcfRef: '' };
VCF_ROLES.forward = { title: 'Forward', desc: 'The reference in attack. The number 9 and wingers finish plays and score goals. Their job is to create danger and convert chances.', pro: 'Pro: A false 9 drops into space to create room for wingers.', jugada: '', vcfRef: '' };

/** Posiciones en el campo por formación: cada clave es clase vcf-pos-* (gk, lb, cbl, cbr, rb, dm, ml, mr, wl, st, wr). Valores: { top: 'X%', left: 'Y%' } */
var VCF_FORMATION_POSITIONS = {
    433: { gk: '90%,50%', lb: '70%,16%', cbl: '70%,36%', cbr: '70%,64%', rb: '70%,84%', dm: '48%,50%', ml: '48%,28%', mr: '48%,72%', wl: '20%,18%', st: '20%,50%', wr: '20%,82%' },
    442: { gk: '90%,50%', lb: '70%,16%', cbl: '70%,36%', cbr: '70%,64%', rb: '70%,84%', dm: '48%,42%', ml: '48%,22%', mr: '48%,58%', wr: '48%,78%', wl: '20%,38%', st: '20%,62%' },
    352: { gk: '90%,50%', lb: '52%,12%', cbl: '72%,28%', cbr: '72%,50%', rb: '72%,72%', dm: '52%,50%', ml: '52%,35%', mr: '52%,65%', wr: '52%,88%', wl: '22%,38%', st: '22%,62%' }
};

function applyFormationToPitch(pitchEl, formation) {
    if (!pitchEl || !VCF_FORMATION_POSITIONS[formation]) return;
    var posMap = VCF_FORMATION_POSITIONS[formation];
    pitchEl.querySelectorAll('.vcf-position-point').forEach(function (point) {
        var match = point.className.match(/\bvcf-pos-(\w+)\b/);
        if (match && posMap[match[1]]) {
            var parts = posMap[match[1]].split(',');
            point.style.top = parts[0];
            point.style.left = parts[1];
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Smooth scroll for anchor links (same page)
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Close navbar on mobile after click
                const navbar = document.querySelector('#navbarNav');
                if (navbar && navbar.classList.contains('show') && typeof bootstrap !== 'undefined') {
                    try {
                        const bsCollapse = bootstrap.Collapse.getInstance(navbar) || new bootstrap.Collapse(navbar, { toggle: false });
                        bsCollapse.hide();
                    } catch (e) {}
                }
            }
        });
    });

    // Countdown: run for every .vcf-countdown-wrap on page (next match and/or MOTM)
    document.querySelectorAll('.vcf-countdown-wrap').forEach(function (countdownWrap) {
        var unix = countdownWrap.getAttribute('data-countdown-unix');
        var iso = countdownWrap.getAttribute('data-countdown-iso');
        var kind = countdownWrap.getAttribute('data-countdown-kind') || '';
        var end = null;
        if (unix) {
            var sec = parseInt(unix, 10);
            if (!isNaN(sec)) end = new Date(sec * 1000);
        }
        if (!end && iso) end = new Date(iso);
        if (end && !isNaN(end.getTime())) {
            var daysEl = countdownWrap.querySelector('[data-days]');
            var hoursEl = countdownWrap.querySelector('[data-hours]');
            var minutesEl = countdownWrap.querySelector('[data-minutes]');
            var secondsEl = countdownWrap.querySelector('[data-seconds]');
            if (!daysEl) daysEl = countdownWrap.querySelectorAll('.vcf-countdown-num')[0];
            if (!hoursEl) hoursEl = countdownWrap.querySelectorAll('.vcf-countdown-num')[1];
            if (!minutesEl) minutesEl = countdownWrap.querySelectorAll('.vcf-countdown-num')[2];
            if (!secondsEl) secondsEl = countdownWrap.querySelectorAll('.vcf-countdown-num')[3];
            var numWrap = countdownWrap.querySelector('[data-countdown-numbers]');
            var expiredMsg = countdownWrap.querySelector('[data-countdown-expired-msg]');
            var labelsEl = countdownWrap.querySelector('.vcf-match-card-countdown-labels');
            var intervalId = null;
            function sameLocalDay(a, b) {
                return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
            }
            function tick() {
                var now = new Date();
                var diff = end.getTime() - now.getTime();
                if (diff <= 0) {
                    if (kind === 'match-start' && expiredMsg) {
                        var elapsed = now.getTime() - end.getTime();
                        var twoH = 120 * 60 * 1000;
                        var msg = 'FINAL';
                        if (elapsed >= 0 && elapsed < twoH) msg = 'LIVE';
                        else if (sameLocalDay(now, end)) msg = 'GAME DAY';
                        expiredMsg.textContent = msg;
                        expiredMsg.hidden = false;
                        expiredMsg.style.display = '';
                        if (numWrap) numWrap.style.display = 'none';
                        if (labelsEl) labelsEl.style.display = 'none';
                    } else {
                        if (daysEl) daysEl.textContent = '0';
                        if (hoursEl) hoursEl.textContent = '0';
                        if (minutesEl) minutesEl.textContent = '0';
                        if (secondsEl) secondsEl.textContent = '0';
                    }
                    if (intervalId) clearInterval(intervalId);
                    return;
                }
                var s = Math.floor(diff / 1000) % 60;
                var m = Math.floor(diff / 60000) % 60;
                var h = Math.floor(diff / 3600000) % 24;
                var d = Math.floor(diff / 86400000);
                if (daysEl) daysEl.textContent = String(d);
                if (hoursEl) hoursEl.textContent = String(h);
                if (minutesEl) minutesEl.textContent = String(m);
                if (secondsEl) secondsEl.textContent = String(s);
            }
            tick();
            intervalId = setInterval(tick, 1000);
        }
    });

    // MOTM vote buttons
    var motmContainer = document.querySelector('.motm-nominees');
    if (motmContainer) {
        var votacionId = motmContainer.getAttribute('data-votacion-id');
        var voteUrl = motmContainer.getAttribute('data-vote-url');
        var votedKey = 'motm_voted_' + votacionId;
        if (localStorage.getItem(votedKey)) {
            motmContainer.classList.add('voted');
            motmContainer.querySelectorAll('.motm-vote-btn').forEach(function (btn) { btn.disabled = true; btn.textContent = 'Already voted'; });
        } else {
            motmContainer.querySelectorAll('.motm-vote-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var nomineeId = this.getAttribute('data-nominee-id');
                    if (!nomineeId || !voteUrl) return;
                    this.disabled = true;
                    this.textContent = '...';
                    var body = JSON.stringify({ votacion_id: parseInt(votacionId, 10), nominee_id: parseInt(nomineeId, 10) });
                    fetch(voteUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: body
                    }).then(function (r) { return r.json(); }).then(function (data) {
                        if (data.success) {
                            localStorage.setItem(votedKey, '1');
                            motmContainer.classList.add('voted');
                            motmContainer.querySelectorAll('.motm-vote-btn').forEach(function (b) { b.disabled = true; b.textContent = 'Already voted'; });
                        } else {
                            if (data.error === 'already_voted') {
                                localStorage.setItem(votedKey, '1');
                                motmContainer.classList.add('voted');
                                motmContainer.querySelectorAll('.motm-vote-btn').forEach(function (b) { b.disabled = true; b.textContent = 'Already voted'; });
                            } else {
                                btn.disabled = false;
                                btn.textContent = 'Vote';
                                alert(data.error === 'closed' ? 'Voting has ended.' : 'Could not submit vote. Try again.');
                            }
                        }
                    }).catch(function () {
                        btn.disabled = false;
                        btn.textContent = 'Vote';
                        alert('Network error. Try again.');
                    });
                });
            });
        }
    }

    // Player card modal: click roster card -> fetch stats -> show modal
    var rosterSection = document.querySelector('#roster');
    var playerModalEl = document.getElementById('playerCardModal');
    if (rosterSection && playerModalEl) {
        var baseUrl = rosterSection.getAttribute('data-base-url') || '';
        var apiUrl = rosterSection.getAttribute('data-player-api') || (baseUrl + '/api/roster-player.php');
        var crestUrl = rosterSection.getAttribute('data-crest-url') || (baseUrl + '/assets/img/vcf-crest.svg');
        var modalCrest = document.getElementById('playerModalCrest');
        if (modalCrest) modalCrest.src = crestUrl;

        function fillPlayerModal(data) {
            var p = data.player;
            var s = data.stats;
            var nameEl = document.getElementById('playerModalName');
            var metaEl = document.getElementById('playerModalMeta');
            var photoEl = document.getElementById('playerModalPhoto');
            var photoPl = document.getElementById('playerModalPhotoPlaceholder');
            var initialsEl = document.getElementById('playerModalInitials');
            if (nameEl) nameEl.textContent = p.nombre + ' ' + p.apellido;
            var metaParts = [];
            metaParts.push(document.createTextNode(p.categoria_nombre || ''));
            var posLabel = p.posicion_display || (p.posicion ? (p.sub_posicion ? p.posicion + ' · ' + p.sub_posicion : p.posicion) : (p.sub_posicion || ''));
            if (posLabel) {
                var roleKey = (p.sub_posicion && SUB_POS_TO_ROLE[p.sub_posicion]) ? SUB_POS_TO_ROLE[p.sub_posicion] : (POS_TO_ROLE[p.posicion] || '');
                metaParts.push(document.createTextNode(' · '));
                metaParts.push(document.createTextNode(posLabel));
                if (roleKey && VCF_ROLES[roleKey]) {
                    metaParts.push(document.createTextNode(' '));
                    var infoSpan = document.createElement('span');
                    infoSpan.className = 'player-modal-pos-info';
                    infoSpan.setAttribute('data-role', roleKey);
                    infoSpan.setAttribute('data-bs-toggle', 'tooltip');
                    infoSpan.setAttribute('data-bs-placement', 'top');
                    infoSpan.setAttribute('title', (VCF_ROLES[roleKey] && VCF_ROLES[roleKey].desc) ? VCF_ROLES[roleKey].desc : '');
                    infoSpan.setAttribute('role', 'button');
                    infoSpan.setAttribute('tabindex', '0');
                    infoSpan.setAttribute('aria-label', 'Role description');
                    var icon = document.createElement('i');
                    icon.className = 'bi bi-info-circle';
                    icon.setAttribute('aria-hidden', 'true');
                    infoSpan.appendChild(icon);
                    metaParts.push(infoSpan);
                }
            }
            if (p.dorsal != null) metaParts.push(document.createTextNode(' · #' + p.dorsal));
            if (metaEl) {
                metaEl.innerHTML = '';
                metaParts.forEach(function (node) { metaEl.appendChild(node); });
            }
            if (p.foto_url) {
                photoEl.src = baseUrl + '/' + p.foto_url;
                photoEl.classList.remove('d-none');
                if (photoPl) photoPl.classList.add('d-none');
            } else {
                photoEl.classList.add('d-none');
                if (photoPl) {
                    photoPl.classList.remove('d-none');
                    if (initialsEl) initialsEl.textContent = (p.nombre.charAt(0) || '') + (p.apellido.charAt(0) || '');
                }
            }
            var maxVals = { partidos_jugados: 20, goles: 15, asistencias: 10, motm: 5, clean_sheets: 10 };
            var ids = { partidos_jugados: ['statApps', 'barApps'], goles: ['statGoals', 'barGoals'], asistencias: ['statAssists', 'barAssists'], motm: ['statMotm', 'barMotm'], clean_sheets: ['statCS', 'barCS'] };
            ['partidos_jugados', 'goles', 'asistencias', 'motm', 'clean_sheets'].forEach(function (key) {
                var val = s[key] != null ? parseInt(s[key], 10) : 0;
                var statEl = document.getElementById(ids[key][0]);
                var barEl = document.getElementById(ids[key][1]);
                if (statEl) statEl.textContent = val;
                if (barEl) barEl.style.width = Math.min(100, (val / maxVals[key]) * 100) + '%';
            });
            var radarWrap = document.getElementById('playerModalRadarWrap');
            var radarPoly = document.getElementById('playerRadarPolygon');
            var sk = data.skills;
            if (radarWrap && radarPoly && sk) {
                var order = ['pace', 'shooting', 'passing', 'dribbling', 'defense', 'physical'];
                var cx = 50; var cy = 50; var rMax = 40;
                var points = [];
                for (var i = 0; i < 6; i++) {
                    var val = Math.max(0, Math.min(10, parseInt(sk[order[i]], 10) || 0));
                    var r = (val / 10) * rMax;
                    var angle = (i * 60 - 90) * Math.PI / 180;
                    points.push((cx + r * Math.cos(angle)).toFixed(2) + ',' + (cy + r * Math.sin(angle)).toFixed(2));
                }
                if (typeof vcfAnimateRadar === 'function') {
                    vcfAnimateRadar(radarWrap, radarPoly, points.join(' '));
                } else {
                    radarPoly.setAttribute('points', points.join(' '));
                    radarWrap.classList.remove('d-none');
                }
            } else if (radarWrap) {
                radarWrap.classList.add('d-none');
            }
        }

        rosterSection.querySelectorAll('.roster-card-clickable').forEach(function (card) {
            card.addEventListener('click', function () {
                var id = this.getAttribute('data-roster-id');
                if (!id) return;
                fetch(apiUrl + '?id=' + encodeURIComponent(id)).then(function (r) { return r.json(); }).then(function (data) {
                    if (data.error) return;
                    fillPlayerModal(data);
                    var modal = bootstrap.Modal.getOrCreateInstance(playerModalEl);
                    modal.show();
                    playerModalEl.setAttribute('data-current-roster-id', id);
                }).catch(function () {});
            });
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });

        document.getElementById('playerModalShare').addEventListener('click', function () {
            var id = playerModalEl.getAttribute('data-current-roster-id');
            var url = window.location.href.split('#')[0].split('?')[0] + '#roster?player=' + (id || '');
            if (navigator.share) {
                navigator.share({ title: 'VCF Academy Houston - Player', url: url }).catch(function () { navigator.clipboard.writeText(url); });
            } else {
                navigator.clipboard.writeText(url).then(function () { alert('Link copied to clipboard'); });
            }
        });

        function closePlayerModal() {
            var modal = bootstrap.Modal.getInstance(playerModalEl);
            if (modal) modal.hide();
        }
        var closeBtn = playerModalEl.querySelector('.modal-header .btn-close[data-bs-dismiss="modal"]');
        if (closeBtn) {
            closeBtn.addEventListener('click', closePlayerModal);
        }
        var backToRosterBtn = document.getElementById('playerModalBackToRoster');
        if (backToRosterBtn) {
            backToRosterBtn.addEventListener('click', closePlayerModal);
            backToRosterBtn.addEventListener('touchend', function (e) { e.preventDefault(); closePlayerModal(); }, { passive: false });
        }
    }

    // Know Your Role - interactive pitch: open Modal (Player Card cromo) with role + roster
    var pitch = document.getElementById('vcfInteractivePitch');
    var pitchWrap = pitch ? pitch.closest('.vcf-pitch-wrap') : null;
    var roleModalEl = document.getElementById('vcfRoleModal');
    var rosterByRole = {};
    var baseUrl = '';
    if (pitchWrap) {
        try {
            var dataJson = pitchWrap.getAttribute('data-roster-by-role');
            if (dataJson) rosterByRole = JSON.parse(dataJson);
            baseUrl = pitchWrap.getAttribute('data-base-url') || '';
        } catch (err) {}
    }
    if (pitch) {
        var popover = document.getElementById('vcfPositionPopover');
        var titleEl = document.getElementById('vcfPositionPopoverTitle');
        var descEl = document.getElementById('vcfPositionPopoverDesc');
        var proEl = document.getElementById('vcfPositionPopoverPro');
        var jugadaEl = document.getElementById('vcfPositionPopoverJugada');
        function showRole(btn) {
            var role = btn.getAttribute('data-role');
            var r = VCF_ROLES[role];
            if (!r) return;
            pitch.querySelectorAll('.vcf-position-point').forEach(function (b) { b.classList.remove('vcf-position-point-active'); });
            btn.classList.add('vcf-position-point-active');
            if (popover && titleEl) {
                titleEl.textContent = r.title;
                descEl.textContent = r.desc;
                proEl.textContent = r.pro || '';
                proEl.style.display = (r.pro && r.pro.length) ? '' : 'none';
                if (jugadaEl) {
                    jugadaEl.textContent = r.jugada || '';
                    jugadaEl.style.display = (r.jugada && r.jugada.length) ? '' : 'none';
                }
                popover.removeAttribute('hidden');
            }
            if (roleModalEl) {
                var modalTitle = roleModalEl.querySelector('#vcfRoleModalTitle');
                var modalDesc = roleModalEl.querySelector('.vcf-role-modal-desc');
                var modalPro = roleModalEl.querySelector('.vcf-role-modal-pro');
                var modalJugada = roleModalEl.querySelector('.vcf-role-modal-jugada');
                var modalVcfRef = roleModalEl.querySelector('.vcf-role-modal-vcfref');
                var modalPhotoWrap = roleModalEl.querySelector('.vcf-role-modal-photo-wrap');
                var modalPhoto = roleModalEl.querySelector('.vcf-role-modal-photo');
                var modalPlayers = roleModalEl.querySelector('.vcf-role-modal-players');
                if (modalTitle) modalTitle.textContent = r.title;
                if (modalDesc) modalDesc.textContent = r.desc;
                if (modalPro) {
                    modalPro.textContent = r.pro || '';
                    modalPro.style.display = (r.pro && r.pro.length) ? '' : 'none';
                }
                if (modalJugada) {
                    modalJugada.textContent = r.jugada || '';
                    modalJugada.style.display = (r.jugada && r.jugada.length) ? '' : 'none';
                }
                if (modalVcfRef) {
                    modalVcfRef.textContent = r.vcfRef ? '⭐ ' + r.vcfRef : '';
                    modalVcfRef.style.display = (r.vcfRef && r.vcfRef.length) ? '' : 'none';
                }
                var players = rosterByRole[role] || [];
                if (modalPlayers) {
                    modalPlayers.textContent = players.length ? players.map(function (p) { return p.nombre; }).join(', ') : 'No players assigned to this role yet.';
                }
                if (modalPhotoWrap && modalPhoto) {
                    var firstWithPhoto = players.filter(function (p) { return p.foto_url; })[0];
                    if (firstWithPhoto && firstWithPhoto.foto_url) {
                        var src = (baseUrl ? baseUrl + '/' : '') + firstWithPhoto.foto_url.replace(/^\//, '');
                        modalPhoto.onerror = function () {
                            modalPhoto.src = '';
                            modalPhotoWrap.classList.add('d-none');
                        };
                        modalPhoto.src = src;
                        modalPhoto.alt = firstWithPhoto.nombre;
                        modalPhotoWrap.classList.remove('d-none');
                    } else {
                        modalPhotoWrap.classList.add('d-none');
                    }
                }
                roleModalEl.classList.add('show');
                roleModalEl.style.display = 'block';
                roleModalEl.setAttribute('aria-hidden', 'false');
                roleModalEl.setAttribute('aria-modal', 'true');
                document.body.classList.add('vcf-role-modal-open');
                document.body.style.overflow = 'hidden';
                pitch.classList.remove('vcf-pitch-popover-open');
            }
        }
        function hidePopover() {
            pitch.querySelectorAll('.vcf-position-point').forEach(function (b) { b.classList.remove('vcf-position-point-active'); });
            pitch.classList.remove('vcf-pitch-popover-open');
            if (popover) popover.setAttribute('hidden', '');
        }
        pitch.addEventListener('click', function (e) {
            var btn = e.target.closest('.vcf-position-point');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            showRole(btn);
        });
        pitch.addEventListener('touchend', function (e) {
            var btn = e.target.closest('.vcf-position-point');
            if (!btn) return;
            e.preventDefault();
            showRole(btn);
        }, { passive: false });
        document.addEventListener('click', function (e) {
            if (popover && !popover.hasAttribute('hidden') && !pitch.contains(e.target)) {
                hidePopover();
            }
        });
        if (roleModalEl) {
            function closeRoleModal() {
                roleModalEl.classList.remove('show');
                roleModalEl.style.display = 'none';
                roleModalEl.setAttribute('aria-hidden', 'true');
                roleModalEl.removeAttribute('aria-modal');
                document.body.classList.remove('modal-open', 'vcf-role-modal-open');
                document.body.style.removeProperty('padding-right');
                document.body.style.removeProperty('overflow');
                document.querySelectorAll('.vcf-role-backdrop').forEach(function (el) { el.remove(); });
                hidePopover();
            }
            window.vcfCloseRoleModal = closeRoleModal;
            function closeIfCloseControl(e) {
                if (!roleModalEl || !roleModalEl.classList.contains('show')) return;
                var t = e.target;
                var el = t && t.closest ? t.closest('[data-close-role-modal]') : null;
                if (el && roleModalEl.contains(el)) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeRoleModal();
                }
            }
            roleModalEl.addEventListener('click', closeIfCloseControl, true);
            roleModalEl.addEventListener('mousedown', closeIfCloseControl, true);
            roleModalEl.addEventListener('pointerup', closeIfCloseControl, true);
            roleModalEl.addEventListener('touchend', closeIfCloseControl, true);
            document.addEventListener('click', closeIfCloseControl, true);
            document.addEventListener('mousedown', closeIfCloseControl, true);
            document.addEventListener('pointerup', closeIfCloseControl, true);
            document.addEventListener('touchend', closeIfCloseControl, { capture: true, passive: false });
            function pointInRect(x, y, r) {
                return x >= r.left && x <= r.right && y >= r.top && y <= r.bottom;
            }
            function handleRoleModalClose(e) {
                if (!roleModalEl || !roleModalEl.classList.contains('show')) return;
                var t = e.target;
                var onBackdrop = t && (t.classList && (t.classList.contains('modal-backdrop') || t.classList.contains('vcf-role-backdrop')) || (t.getAttribute && t.getAttribute('data-vcf-role-backdrop')));
                var onCloseControl = t && t.closest && t.closest('#vcfRoleModalBackBtn, #vcfRoleModal .modal-header .btn-close');
                if (onBackdrop || (onCloseControl && roleModalEl.contains(onCloseControl))) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeRoleModal();
                    return;
                }
                var x = e.clientX != null ? e.clientX : (e.touches && e.touches[0] ? e.touches[0].clientX : (e.changedTouches && e.changedTouches[0] ? e.changedTouches[0].clientX : null));
                var y = e.clientY != null ? e.clientY : (e.touches && e.touches[0] ? e.touches[0].clientY : (e.changedTouches && e.changedTouches[0] ? e.changedTouches[0].clientY : null));
                if (x != null && y != null) {
                    var backBtn = document.getElementById('vcfRoleModalBackBtn');
                    var closeX = roleModalEl.querySelector('.modal-header .btn-close');
                    if (backBtn) {
                        var r = backBtn.getBoundingClientRect();
                        if (pointInRect(x, y, r)) { e.preventDefault(); e.stopPropagation(); closeRoleModal(); return; }
                    }
                    if (closeX) {
                        var rx = closeX.getBoundingClientRect();
                        if (pointInRect(x, y, rx)) { e.preventDefault(); e.stopPropagation(); closeRoleModal(); return; }
                    }
                }
            }
            document.addEventListener('click', handleRoleModalClose, true);
            document.addEventListener('pointerup', handleRoleModalClose, true);
            document.addEventListener('mouseup', handleRoleModalClose, true);
            document.addEventListener('touchend', handleRoleModalClose, { capture: true, passive: false });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && roleModalEl && roleModalEl.classList.contains('show')) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeRoleModal();
                }
            }, true);
        }
        // Selector de formación: 4-3-3, 4-4-2, 3-5-2
        var formationSelector = document.querySelector('.vcf-formation-selector');
        if (formationSelector && pitch) {
            formationSelector.addEventListener('click', function (e) {
                var btn = e.target.closest('.vcf-formation-btn');
                if (!btn || btn.hasAttribute('disabled')) return;
                var formation = btn.getAttribute('data-formation');
                if (!formation) return;
                // Asegurar que actualizamos el pitch del mismo bloque (siguiente hermano = .vcf-pitch-wrap)
                var wrap = formationSelector.nextElementSibling;
                var pitchEl = (wrap && wrap.classList.contains('vcf-pitch-wrap')) ? wrap.querySelector('.vcf-pitch') : pitch;
                if (!pitchEl) pitchEl = pitch;
                formationSelector.querySelectorAll('.vcf-formation-btn').forEach(function (b) {
                    b.classList.remove('active');
                    b.setAttribute('aria-pressed', 'false');
                });
                btn.classList.add('active');
                btn.setAttribute('aria-pressed', 'true');
                pitchEl.classList.remove('vcf-formation-433', 'vcf-formation-442', 'vcf-formation-352');
                pitchEl.classList.add('vcf-formation-' + formation);
                var label = formation === '433' ? '4-3-3' : formation === '442' ? '4-4-2' : '3-5-2';
                pitchEl.setAttribute('aria-label', 'Interactive field positions (' + label + ')');
                pitchEl.setAttribute('data-formation', formation);
                applyFormationToPitch(pitchEl, formation);
            });
            applyFormationToPitch(pitch, '433');
        }
    }

    // Role tooltips on roster cards (set title from VCF_ROLES and init Bootstrap tooltip)
    document.querySelectorAll('.roster-pos-info').forEach(function (el) {
        var role = el.getAttribute('data-role');
        var r = VCF_ROLES[role];
        if (r && r.desc) {
            el.setAttribute('title', r.desc);
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                new bootstrap.Tooltip(el, { container: 'body' });
            }
        }
        el.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    // Modal: init tooltip on .player-modal-pos-info when modal is shown, dispose when hidden
    var playerModalEl = document.getElementById('playerCardModal');
    if (playerModalEl) {
        // Evitar aviso ARIA: quitar foco del modal antes de que Bootstrap ponga aria-hidden
        playerModalEl.addEventListener('hide.bs.modal', function () {
            if (playerModalEl.contains(document.activeElement)) {
                document.body.setAttribute('tabindex', '-1');
                document.body.focus();
            }
        });
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            var modalPosTooltip = null;
            playerModalEl.addEventListener('shown.bs.modal', function () {
                var infoEl = playerModalEl.querySelector('.player-modal-pos-info');
                if (infoEl) {
                    modalPosTooltip = new bootstrap.Tooltip(infoEl, { container: 'body' });
                }
            });
            playerModalEl.addEventListener('hidden.bs.modal', function () {
                if (modalPosTooltip) {
                    modalPosTooltip.dispose();
                    modalPosTooltip = null;
                }
            });
        }
    }

    // --- GSAP ScrollTrigger + parallax (skipped when prefers-reduced-motion) ---
    var motionOk = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
    if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined' && motionOk) {
        gsap.registerPlugin(ScrollTrigger);
        var isHome = document.body.classList.contains('vcf-page-home');

        // 1) Section reveal on scroll; home uses staggered match cards instead of whole strip
        var sectionList = gsap.utils.toArray(isHome ? '.vcf-section' : '.vcf-section, .vcf-cards-strip');
        sectionList.forEach(function (section) {
            gsap.from(section, {
                scrollTrigger: { trigger: section, start: 'top 88%', toggleActions: 'play none none none' },
                y: 36,
                opacity: 0,
                duration: 0.6,
                ease: 'power2.out'
            });
        });
        if (isHome) {
            document.querySelectorAll('.vcf-cards-strip').forEach(function (strip) {
                var cards = strip.querySelectorAll('.vcf-cards-row > .vcf-match-card');
                if (!cards.length) return;
                gsap.from(cards, {
                    scrollTrigger: { trigger: strip, start: 'top 88%', toggleActions: 'play none none none' },
                    y: 24,
                    opacity: 0,
                    stagger: 0.12,
                    duration: 0.5,
                    ease: 'power2.out'
                });
            });
            gsap.utils.toArray('main .vcf-section .vcf-section-title-line').forEach(function (title) {
                var section = title.closest('.vcf-section');
                if (!section) return;
                gsap.from(title, {
                    scrollTrigger: { trigger: section, start: 'top 87%', toggleActions: 'play none none none' },
                    y: 16,
                    opacity: 0,
                    duration: 0.45,
                    ease: 'power2.out'
                });
            });
            var headerEl = document.querySelector('.vcf-header');
            if (headerEl) {
                var headerScrollTicking = false;
                function updateHeaderScrolled() {
                    headerScrollTicking = false;
                    var y = window.scrollY || document.documentElement.scrollTop;
                    headerEl.classList.toggle('vcf-header-scrolled', y > 24);
                }
                window.addEventListener('scroll', function () {
                    if (!headerScrollTicking) {
                        headerScrollTicking = true;
                        requestAnimationFrame(updateHeaderScrolled);
                    }
                }, { passive: true });
                updateHeaderScrolled();
            }
        }

        // 2) Roster carousels: animate each category carousel on scroll
        var rosterCarousels = document.querySelectorAll('#roster .roster-category-swiper');
        if (rosterCarousels.length) {
            gsap.from(rosterCarousels, {
                scrollTrigger: { trigger: '#roster', start: 'top 85%', toggleActions: 'play none none none' },
                y: 28,
                opacity: 0,
                stagger: 0.08,
                duration: 0.45,
                ease: 'power2.out'
            });
        }

        // 3) Hero parallax (mouse move): move active slide bg and content
        var heroSwiper = document.querySelector('.vcf-hero-swiper');
        if (heroSwiper) {
            function onHeroMove(e) {
                var active = heroSwiper.querySelector('.swiper-slide-active');
                if (!active) return;
                var heroBg = active.querySelector('.vcf-hero-slide-bg, .vcf-hero-slide-lcp');
                var heroContent = active.querySelector('.vcf-hero-slide-content');
                var rect = heroSwiper.getBoundingClientRect();
                var x = (e.clientX - rect.left) / rect.width - 0.5;
                var y = (e.clientY - rect.top) / rect.height - 0.5;
                var move = 10;
                if (heroBg) gsap.to(heroBg, { x: x * move, y: y * move, duration: 0.5, ease: 'power2.out' });
                if (heroContent) gsap.to(heroContent, { x: -x * (move * 0.4), y: -y * (move * 0.4), duration: 0.5, ease: 'power2.out' });
            }
            function onHeroLeave() {
                heroSwiper.querySelectorAll('.vcf-hero-slide-bg, .vcf-hero-slide-lcp, .vcf-hero-slide-content').forEach(function (el) {
                    gsap.to(el, { x: 0, y: 0, duration: 0.7, ease: 'power2.out' });
                });
            }
            heroSwiper.addEventListener('mousemove', onHeroMove);
            heroSwiper.addEventListener('mouseleave', onHeroLeave);
        }
    }
});

// Radar chart: draw animation + label hover (called from fillPlayerModal when GSAP available)
function vcfAnimateRadar(radarWrap, radarPoly, pointsStr) {
    if (!radarWrap || !radarPoly) return;
    var centerPoints = '50,50 50,50 50,50 50,50 50,50 50,50';
    radarPoly.setAttribute('points', centerPoints);
    radarWrap.classList.remove('d-none');
    var radarMotionOk = typeof window.matchMedia === 'undefined' || window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
    if (typeof gsap !== 'undefined' && radarMotionOk) {
        gsap.to(radarPoly, {
            attr: { points: pointsStr },
            duration: 0.9,
            ease: 'back.out(1.4)'
        });
    } else {
        radarPoly.setAttribute('points', pointsStr);
    }
    radarWrap.removeAttribute('data-hover-axis');
    radarWrap.querySelectorAll('.player-radar-label').forEach(function (label) {
        label.removeEventListener('mouseenter', label._vcfRadarEnter);
        label.removeEventListener('mouseleave', label._vcfRadarLeave);
        var axis = label.getAttribute('data-axis');
        label._vcfRadarEnter = function () { radarWrap.setAttribute('data-hover-axis', axis); };
        label._vcfRadarLeave = function () { radarWrap.removeAttribute('data-hover-axis'); };
        label.addEventListener('mouseenter', label._vcfRadarEnter);
        label.addEventListener('mouseleave', label._vcfRadarLeave);
    });
}

    // Star of the Month voting: vote button + live results bar chart
    (function () {
        var starSection = document.querySelector('#star');
        if (!starSection) return;
        var votacionId = starSection.getAttribute('data-star-votacion-id');
        var resultsUrl = starSection.getAttribute('data-star-results-url');
        var voteUrl = starSection.getAttribute('data-star-vote-url');
        if (!votacionId || !resultsUrl || !voteUrl) return;

        function updateStarResults() {
            fetch(resultsUrl + '?votacion_id=' + encodeURIComponent(votacionId))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var container = document.getElementById('star-results-container');
                    if (!container || !Array.isArray(data)) return;
                    var maxVotes = 1;
                    data.forEach(function (p) { if (p.total_votes > maxVotes) maxVotes = p.total_votes; });
                    container.innerHTML = data.map(function (p) {
                        var pct = maxVotes > 0 ? (p.total_votes / maxVotes) * 100 : 0;
                        return '<div class="vote-bar-container">' +
                            '<div class="player-label"><span>' + (p.nombre || '') + '</span><span>' + p.total_votes + ' votes</span></div>' +
                            '<div class="bar-bg"><div class="bar-fill" style="width:' + pct + '%"></div></div></div>';
                    }).join('');
                })
                .catch(function () {});
        }

        starSection.querySelectorAll('.star-vote-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var nomineeId = this.getAttribute('data-nominee-id');
                if (!nomineeId || this.disabled) return;
                this.disabled = true;
                var formData = new FormData();
                formData.append('votacion_id', votacionId);
                formData.append('nominee_id', nomineeId);
                fetch(voteUrl, { method: 'POST', body: formData })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.status === 'success') {
                            updateStarResults();
                            if (typeof alert !== 'undefined') alert(res.message || 'Vote recorded.');
                        } else {
                            if (typeof alert !== 'undefined') alert(res.message || 'You have already voted.');
                        }
                    })
                    .catch(function () {
                        if (typeof alert !== 'undefined') alert('Could not submit vote. Try again.');
                    })
                    .finally(function () { btn.disabled = false; });
            });
        });

        updateStarResults();
        setInterval(updateStarResults, 5000);
    })();
