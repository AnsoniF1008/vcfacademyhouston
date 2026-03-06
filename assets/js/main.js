/**
 * VCF Academy Houston - Main JS
 * Navbar collapse, smooth scroll for anchor links
 */

var VCF_ROLES = {
    goalkeeper: { title: 'Goalkeeper (1)', desc: 'The only player allowed to touch the ball with their hands inside the area. Your role: keep the ball out of the net. Key skills: reflexes, communication to organise the defence, and good footwork to start the attack.', pro: 'Pro: Command your area and talk to the back line.', jugada: 'Typical play: Start a counter with a quick throw or pass to the full-back.' },
    lateral_izq: { title: 'Left Back (2)', desc: 'Plays on the left flank. Besides defending, you support the attack by overlapping and sending crosses into the box.', pro: 'Pro: Carrilero — join the attack when we have the ball.', jugada: 'Typical play: Overlap, receive from the interior, cross to the 9.' },
    central_izq: { title: 'Left Centre-Back (3)', desc: 'In front of the keeper. Your main job is to stop opposition attacks. Central defenders are usually strong in the air and cut out passes.', pro: 'Pro: Win the first ball and play out from the back.', jugada: 'Typical play: Win the ball and play out to the pivote or full-back.' },
    central_der: { title: 'Right Centre-Back (4)', desc: 'Partner in the middle of the back four. Neutralise attacks, win aerial duels, and intercept passes. Build from the back.', pro: 'Pro: Stay compact with your centre-back partner.', jugada: 'Typical play: Win the ball and play out to the pivote or full-back.' },
    lateral_der: { title: 'Right Back (5)', desc: 'Plays on the right flank. Defend your side and support the attack with runs down the wing and quality crosses.', pro: 'Pro: Carrilero — join the attack when we have the ball.', jugada: 'Typical play: Overlap, receive from the interior, cross to the 9.' },
    pivote: { title: 'Defensive Midfielder – Pivote (6)', desc: 'The engine and balance of the team. You help the defence, win the ball back, and connect defence with attack.', pro: 'Pro: The pivote is the team\'s balance — protect the back four.', jugada: 'Typical play: Recover the ball and play the first pass to an interior or winger.' },
    interior_izq: { title: 'Left Central Midfielder (7)', desc: 'Interior or box-to-box midfielder. You distribute the ball and need great stamina to cover the whole pitch.', pro: 'Pro: Receive between the lines and turn to play forward.', jugada: 'Typical play: Receive from the pivote, turn, and play to the winger or 9.' },
    interior_der: { title: 'Right Central Midfielder (8)', desc: 'Interior or box-to-box midfielder. You distribute play and link the pivote with the wingers and striker.', pro: 'Pro: The mediapunta is the creative one — the "last pass" or shot.', jugada: 'Typical play: Receive from the pivote, turn, and play to the winger or 9.' },
    extremo_izq: { title: 'Left Winger (9)', desc: 'Your home is the opposition half. Fast and skilful, you play on the wing to beat the full-back and create or score.', pro: 'Pro: Take on your defender and either cross or cut inside.', jugada: 'Typical play: Take on the defender, cross to the 9 or cut inside to shoot.' },
    nueve: { title: 'Striker – Delantero Centro (10)', desc: 'The reference up front. Your success is measured in goals. Finish moves with your feet or head.', pro: 'Pro: Hold up the ball and bring wingers into play.', jugada: 'Typical play: Hold the ball, lay off to wingers, or finish in the box.' },
    extremo_der: { title: 'Right Winger (11)', desc: 'Fast and skilful on the right wing. Your job is to beat the full-back and create chances or score.', pro: 'Pro: Take on your defender and either cross or cut inside.', jugada: 'Typical play: Take on the defender, cross to the 9 or cut inside to shoot.' }
};
var POS_TO_ROLE = { 'Portero': 'goalkeeper', 'Defensa': 'defense', 'Mediocampista': 'midfield', 'Delantero': 'forward' };
var SUB_POS_TO_ROLE = { 'Portero': 'goalkeeper', 'Lateral izquierdo': 'lateral_izq', 'Central izquierdo': 'central_izq', 'Central derecho': 'central_der', 'Lateral derecho': 'lateral_der', 'Pivote': 'pivote', 'Interior izquierdo': 'interior_izq', 'Interior derecho': 'interior_der', 'Extremo izquierdo': 'extremo_izq', 'Delantero centro (9)': 'nueve', 'Extremo derecho': 'extremo_der' };
/* Generic roles for roster/modal tooltips when only main position is known */
VCF_ROLES.defense = { title: 'Defense', desc: 'The wall. Central defenders and full-backs protect the zone, win duels, and recover the ball. They build from the back and support the midfield.', pro: 'Pro: Carrileros join the attack when the team has possession.', jugada: '' };
VCF_ROLES.midfield = { title: 'Midfield', desc: 'The engine room. Pivotes and creative midfielders control the tempo, keep the ball, and give balance. They connect defense and attack.', pro: 'Pro: The enganche (number 10) is the playmaker in the final third.', jugada: '' };
VCF_ROLES.forward = { title: 'Forward', desc: 'The reference in attack. The number 9 and wingers finish plays and score goals. Their job is to create danger and convert chances.', pro: 'Pro: A false 9 drops into space to create room for wingers.', jugada: '' };

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
            var intervalId = null;
            function tick() {
                var now = new Date();
                var diff = end.getTime() - now.getTime();
                if (diff <= 0) {
                    if (daysEl) daysEl.textContent = '0';
                    if (hoursEl) hoursEl.textContent = '0';
                    if (minutesEl) minutesEl.textContent = '0';
                    if (secondsEl) secondsEl.textContent = '0';
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
            var posLabel = p.posicion ? (p.sub_posicion ? p.posicion + ' · ' + p.sub_posicion : p.posicion) : (p.sub_posicion || '');
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

        var closeBtn = playerModalEl.querySelector('.modal-header .btn-close[data-bs-dismiss="modal"]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                var modal = bootstrap.Modal.getInstance(playerModalEl);
                if (modal) modal.hide();
            });
        }
    }

    // Know Your Role - interactive pitch popover (delegation so clicks always reach the pitch)
    var pitch = document.getElementById('vcfInteractivePitch');
    if (pitch) {
        var popover = document.getElementById('vcfPositionPopover');
        var titleEl = document.getElementById('vcfPositionPopoverTitle');
        var descEl = document.getElementById('vcfPositionPopoverDesc');
        var proEl = document.getElementById('vcfPositionPopoverPro');
        var jugadaEl = document.getElementById('vcfPositionPopoverJugada');
        function showRole(btn) {
            var role = btn.getAttribute('data-role');
            var r = VCF_ROLES[role];
            if (!r || !popover) return;
            pitch.querySelectorAll('.vcf-position-point').forEach(function (b) { b.classList.remove('vcf-position-point-active'); });
            btn.classList.add('vcf-position-point-active');
            pitch.classList.add('vcf-pitch-popover-open');
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
        function hidePopover() {
            pitch.querySelectorAll('.vcf-position-point').forEach(function (b) { b.classList.remove('vcf-position-point-active'); });
            pitch.classList.remove('vcf-pitch-popover-open');
            popover.setAttribute('hidden', '');
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
    if (playerModalEl && typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
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

    // --- Champions League animations: GSAP ScrollTrigger + Parallax + Radar ---
    if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);

        // 1) Section reveal on scroll (slide up + blur clear)
        gsap.utils.toArray('.vcf-section, .vcf-cards-strip').forEach(function (section) {
            gsap.from(section, {
                scrollTrigger: { trigger: section, start: 'top 88%', toggleActions: 'play none none none' },
                y: 36,
                opacity: 0,
                duration: 0.6,
                ease: 'power2.out'
            });
        });

        // 2) Roster cards: stagger effect (0.1s between each)
        var rosterCards = document.querySelectorAll('#roster .roster-card');
        if (rosterCards.length) {
            gsap.from(rosterCards, {
                scrollTrigger: { trigger: '#roster', start: 'top 85%', toggleActions: 'play none none none' },
                y: 28,
                opacity: 0,
                stagger: 0.1,
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
    if (typeof gsap !== 'undefined') {
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
