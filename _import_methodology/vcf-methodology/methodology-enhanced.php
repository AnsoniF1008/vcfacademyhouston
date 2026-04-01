<?php
/**
 * VCF Academy Houston — Methodology Section (Enhanced)
 * ======================================================
 * INSTRUCCIONES:
 * 1. En tu index.php busca la sección <section id="methodology">
 * 2. Reemplaza TODO ese bloque con este código
 *
 * TAMBIÉN INCLUYE:
 * - Fix del fondo gris (agrega al inicio de vcf-style.css o header.php)
 */
?>

<!-- ══════════════════════════════════════════
     FIX CRÍTICO — Fondo gris
     Agrega esto al INICIO de assets/css/vcf-style.css
     (o dentro de <style> en includes/header.php)
══════════════════════════════════════════ -->
<style>
/* ── BACKGROUND FIX ──
   El fondo gris se debe a que el body no hereda el color negro.
   Este fix lo corrige globalmente. */
html, body {
  background: #080808 !important;
  color: #F5F0E8;
}
/* Asegurar que todas las secciones tienen fondo oscuro */
section,
.vcf-section,
.vcf-section--dark,
#methodology,
#formation,
#grounds,
#roster,
#tournaments,
#reels,
#star {
  background: inherit;
}

/* ── METHODOLOGY SECTION ── */
#methodology {
  background: #0a0a0a;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}

/* Pillar enhancements */
.vm-pillars {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
  margin-bottom: 20px;
}
.vm-pillar {
  background: #181818;
  padding: 28px 22px;
  border-top: 3px solid rgba(255,107,0,0.2);
  position: relative;
  overflow: hidden;
  transition: background 0.18s, border-top-color 0.18s;
}
.vm-pillar:nth-child(1) { border-top-color: #FF6B00; }
.vm-pillar:nth-child(2) { border-top-color: rgba(255,107,0,0.55); }
.vm-pillar:nth-child(3) { border-top-color: rgba(255,107,0,0.25); }
.vm-pillar:hover        { background: #1E1916; }
.vm-pillar:hover        { border-top-color: #FF6B00; }

/* Watermark number in background */
.vm-pillar::before {
  content: attr(data-num);
  position: absolute;
  bottom: -10px;
  right: 8px;
  font-family: var(--font-display);
  font-size: 72px;
  font-weight: 900;
  color: rgba(255,107,0,0.06);
  line-height: 1;
  pointer-events: none;
  user-select: none;
}
.vm-pillar:hover::before { color: rgba(255,107,0,0.10); }

.vm-pillar__icon {
  font-size: 22px;
  margin-bottom: 10px;
  display: block;
  line-height: 1;
}
.vm-pillar__title {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #F5F0E8;
  margin-bottom: 8px;
}
.vm-pillar__desc {
  font-size: 13px;
  color: #AAAAAA;
  line-height: 1.65;
}

/* DNA strip */
.vm-dna {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
  margin-bottom: 20px;
}
.vm-dna__item {
  background: #111;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  border-left: 2px solid rgba(255,107,0,0.25);
  transition: border-color 0.15s;
}
.vm-dna__item:hover { border-left-color: #FF6B00; }
.vm-dna__icon {
  font-size: 16px;
  flex-shrink: 0;
  opacity: 0.8;
}
.vm-dna__text {
  font-family: var(--font-display);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #AAAAAA;
}

/* Schedule table */
.vm-schedules {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 2px;
}
.vm-schedule {
  background: #181818;
  border-left: 3px solid #FF6B00;
  padding: 18px 20px;
}
.vm-schedule__cat {
  font-family: var(--font-display);
  font-size: 16px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #FF6B00;
  margin-bottom: 10px;
}
.vm-schedule__rows { display: flex; flex-direction: column; gap: 4px; }
.vm-schedule__row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 5px 0;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.vm-schedule__row:last-child { border-bottom: none; }
.vm-schedule__day {
  font-family: var(--font-display);
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #555;
}
.vm-schedule__time {
  font-family: var(--font-display);
  font-size: 12px;
  font-weight: 700;
  color: #AAAAAA;
}
.vm-schedule__gps {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 12px;
  font-family: var(--font-display);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #555;
  border: 1px solid rgba(255,255,255,0.08);
  padding: 5px 12px;
  border-radius: 2px;
  text-decoration: none;
  transition: all 0.15s;
}
.vm-schedule__gps:hover { color: #FF6B00; border-color: #FF6B00; }

/* Responsive */
@media (max-width: 700px) {
  .vm-pillars { grid-template-columns: 1fr; }
  .vm-dna     { grid-template-columns: 1fr; }
}
</style>


<!-- ══════════════════════════════════════════
     METHODOLOGY SECTION — Enhanced
     Reemplaza <section id="methodology"> ... </section>
══════════════════════════════════════════ -->
<section id="methodology">
<div style="max-width:var(--page-max);margin:0 auto;padding:44px var(--page-pad);">

  <!-- Header -->
  <div class="vcf-section__header">
    <h2 class="vcf-section__title">Our <em>Methodology</em></h2>
  </div>

  <p style="font-size:13px;color:#AAAAAA;max-width:560px;margin-bottom:28px;line-height:1.7;">
    <em style="font-style:italic;color:#666;">"Educating People, Training Footballers"</em> —
    The three official VCF Academy pillars that guide everything we do in Houston.
    Every session, every match, every player.
  </p>

  <!-- ── PILLARS ── -->
  <div class="vm-pillars">

    <div class="vm-pillar" data-num="01">
      <span class="vm-pillar__icon">🦇</span>
      <div class="vm-pillar__title">Identity</div>
      <p class="vm-pillar__desc">
        Building the Valencia CF identity in every young player —
        values, culture, pride and commitment to the crest. On and off the pitch.
      </p>
    </div>

    <div class="vm-pillar" data-num="02">
      <span class="vm-pillar__icon">💪</span>
      <div class="vm-pillar__title">Effort</div>
      <p class="vm-pillar__desc">
        No shortcuts. Hard work and dedication in every training session,
        every matchday, every sprint, every rep. That's the VCF standard.
      </p>
    </div>

    <div class="vm-pillar" data-num="03">
      <span class="vm-pillar__icon">🧠</span>
      <div class="vm-pillar__title">Intelligence</div>
      <p class="vm-pillar__desc">
        Tactical awareness and smart decision-making — reading the game,
        knowing your role, and anticipating play before it happens.
      </p>
    </div>

  </div>

  <!-- ── DNA STRIP ── -->
  <div class="vm-dna">
    <div class="vm-dna__item">
      <span class="vm-dna__icon">🏆</span>
      <span class="vm-dna__text">Official VCF Academy Program</span>
    </div>
    <div class="vm-dna__item">
      <span class="vm-dna__icon">📍</span>
      <span class="vm-dna__text">Katy, TX — Houston Area</span>
    </div>
    <div class="vm-dna__item">
      <span class="vm-dna__icon">⚽</span>
      <span class="vm-dna__text">B13 · Born 2013</span>
    </div>
  </div>

  <!-- ── TRAINING SCHEDULE ── -->
  <div style="font-family:var(--font-display);font-size:10px;font-weight:800;letter-spacing:0.22em;text-transform:uppercase;color:#555;margin-bottom:12px;">
    Training Schedules
  </div>

  <div class="vm-schedules">
    <div class="vm-schedule">
      <div class="vm-schedule__cat">B13</div>
      <div class="vm-schedule__rows">
        <div class="vm-schedule__row">
          <span class="vm-schedule__day">Monday</span>
          <span class="vm-schedule__time">5:00 PM</span>
        </div>
        <div class="vm-schedule__row">
          <span class="vm-schedule__day">Wednesday</span>
          <span class="vm-schedule__time">5:00 PM</span>
        </div>
        <div class="vm-schedule__row">
          <span class="vm-schedule__day">Friday</span>
          <span class="vm-schedule__time">5:00 PM</span>
        </div>
        <div class="vm-schedule__row">
          <span class="vm-schedule__day">Location</span>
          <span class="vm-schedule__time">2203 N Westgreen Blvd, Katy TX</span>
        </div>
      </div>
      <a href="https://maps.app.goo.gl/q27c1FCQw4cvspGX8"
         target="_blank" rel="noopener"
         class="vm-schedule__gps">
        <svg width="11" height="11" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 0 0-6 6c0 4 6 10 6 10s6-6 6-10a6 6 0 0 0-6-6zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/></svg>
        Open GPS
      </a>
    </div>
    <!-- Agrega más categorías aquí cuando crezca la academia -->
    <!-- Ejemplo para B11:
    <div class="vm-schedule">
      <div class="vm-schedule__cat">B11 <span style="font-size:10px;color:#444;">(Coming Soon)</span></div>
      ...
    </div>
    -->
  </div>

</div>
</section>
