import { DatabaseSync } from 'node:sqlite';
import path from 'node:path';
import fs from 'node:fs';
import bcrypt from 'bcryptjs';

const DATA_DIR = path.join(process.cwd(), 'data');
const DB_PATH = path.join(DATA_DIR, 'vcf.db');

declare global {
  // eslint-disable-next-line no-var
  var __vcf_db: DatabaseSync | undefined;
}

export function getDb(): DatabaseSync {
  if (!global.__vcf_db) {
    if (!fs.existsSync(DATA_DIR)) {
      fs.mkdirSync(DATA_DIR, { recursive: true });
    }
    const db = new DatabaseSync(DB_PATH);
    db.exec('PRAGMA journal_mode = WAL;');
    db.exec('PRAGMA foreign_keys = ON;');
    initSchemaAndSeed(db);
    global.__vcf_db = db;
  }
  return global.__vcf_db;
}

function initSchemaAndSeed(db: DatabaseSync) {
  db.exec(`
    CREATE TABLE IF NOT EXISTS admin_users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT NOT NULL UNIQUE,
      password_hash TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'super_admin',
      email TEXT,
      created_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS admin_activity_log (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER,
      username TEXT,
      action TEXT NOT NULL,
      details TEXT,
      created_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS sedes (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nombre TEXT NOT NULL,
      direccion TEXT NOT NULL,
      mapa_general_url TEXT,
      nota_acceso TEXT
    );

    CREATE TABLE IF NOT EXISTS canchas (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      sede_id INTEGER NOT NULL,
      numero_cancha TEXT NOT NULL,
      sobrenombre TEXT,
      indicaciones_extra TEXT,
      mapa_url TEXT,
      FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS categorias (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nombre TEXT NOT NULL,
      horarios_entrenamiento TEXT
    );

    CREATE TABLE IF NOT EXISTS categoria_horarios (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      categoria_id INTEGER NOT NULL,
      dia_semana INTEGER NOT NULL,
      hora TEXT NOT NULL,
      FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS roster (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nombre TEXT NOT NULL,
      apellido TEXT NOT NULL,
      dorsal INTEGER,
      posicion TEXT,
      sub_posicion TEXT,
      foto_url TEXT,
      categoria_id INTEGER NOT NULL,
      activo INTEGER NOT NULL DEFAULT 1,
      created_at TEXT DEFAULT (datetime('now')),
      FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS roster_estadisticas (
      roster_id INTEGER PRIMARY KEY,
      partidos_jugados INTEGER NOT NULL DEFAULT 0,
      goles INTEGER NOT NULL DEFAULT 0,
      asistencias INTEGER NOT NULL DEFAULT 0,
      motm INTEGER NOT NULL DEFAULT 0,
      clean_sheets INTEGER NOT NULL DEFAULT 0,
      FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS roster_habilidades (
      roster_id INTEGER PRIMARY KEY,
      pace INTEGER NOT NULL DEFAULT 7,
      shooting INTEGER NOT NULL DEFAULT 7,
      passing INTEGER NOT NULL DEFAULT 7,
      dribbling INTEGER NOT NULL DEFAULT 7,
      defense INTEGER NOT NULL DEFAULT 6,
      physical INTEGER NOT NULL DEFAULT 7,
      FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS torneos_info (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nombre_torneo TEXT NOT NULL,
      temporada TEXT,
      archivado INTEGER NOT NULL DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS juegos (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      torneo_id INTEGER NOT NULL,
      fecha TEXT NOT NULL,
      hora TEXT,
      rival TEXT,
      cancha TEXT,
      sede_id INTEGER,
      ubicacion_mapa_url TEXT,
      estado TEXT NOT NULL DEFAULT 'proximo',
      categoria TEXT,
      goles_vcf INTEGER,
      goles_rival INTEGER,
      rival_logo_url TEXT,
      FOREIGN KEY (torneo_id) REFERENCES torneos_info(id) ON DELETE CASCADE,
      FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS juego_goles (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      juego_id INTEGER NOT NULL,
      roster_id INTEGER NOT NULL,
      goles INTEGER NOT NULL DEFAULT 0,
      asistencias INTEGER NOT NULL DEFAULT 0,
      minuto INTEGER,
      tipo TEXT DEFAULT 'normal',
      UNIQUE(juego_id, roster_id),
      FOREIGN KEY (juego_id) REFERENCES juegos(id) ON DELETE CASCADE,
      FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS jugador_mes (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nombre TEXT NOT NULL,
      categoria TEXT NOT NULL,
      dorsal INTEGER,
      foto_url TEXT,
      descripcion_logro TEXT NOT NULL,
      mes TEXT NOT NULL,
      created_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS motm_votaciones (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      juego_id INTEGER NOT NULL,
      starts_at TEXT NOT NULL,
      ends_at TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'open',
      winner_nominee_id INTEGER,
      created_at TEXT DEFAULT (datetime('now')),
      FOREIGN KEY (juego_id) REFERENCES juegos(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS motm_nominees (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      votacion_id INTEGER NOT NULL,
      nombre TEXT NOT NULL,
      foto_url TEXT,
      orden INTEGER NOT NULL,
      roster_id INTEGER,
      FOREIGN KEY (votacion_id) REFERENCES motm_votaciones(id) ON DELETE CASCADE,
      FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS motm_votes (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      votacion_id INTEGER NOT NULL,
      nominee_id INTEGER NOT NULL,
      ip_address TEXT NOT NULL,
      fingerprint_hash TEXT,
      created_at TEXT DEFAULT (datetime('now')),
      UNIQUE(votacion_id, ip_address),
      FOREIGN KEY (votacion_id) REFERENCES motm_votaciones(id) ON DELETE CASCADE,
      FOREIGN KEY (nominee_id) REFERENCES motm_nominees(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS hero_slides (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      image_url TEXT NOT NULL,
      title TEXT NOT NULL,
      subtitle TEXT,
      button_text TEXT DEFAULT 'Read More',
      button_url TEXT,
      orden INTEGER NOT NULL DEFAULT 0,
      activo INTEGER NOT NULL DEFAULT 1,
      created_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS match_reels (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      video_url TEXT NOT NULL,
      player_id INTEGER,
      match_id INTEGER,
      caption TEXT,
      orden INTEGER NOT NULL DEFAULT 0,
      view_count INTEGER NOT NULL DEFAULT 0,
      like_count INTEGER NOT NULL DEFAULT 0,
      created_at TEXT DEFAULT (datetime('now')),
      FOREIGN KEY (player_id) REFERENCES roster(id) ON DELETE SET NULL,
      FOREIGN KEY (match_id) REFERENCES juegos(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS match_reel_like_visitors (
      reel_id INTEGER NOT NULL,
      fingerprint_hash TEXT NOT NULL,
      created_at TEXT DEFAULT (datetime('now')),
      PRIMARY KEY (reel_id, fingerprint_hash),
      FOREIGN KEY (reel_id) REFERENCES match_reels(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS noticias_categorias (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nombre TEXT NOT NULL,
      slug TEXT NOT NULL UNIQUE,
      color TEXT DEFAULT '#FF6600',
      descripcion TEXT,
      orden INTEGER DEFAULT 0,
      activa INTEGER NOT NULL DEFAULT 1,
      created_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS noticias (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      titulo TEXT NOT NULL,
      slug TEXT NOT NULL UNIQUE,
      categoria_id INTEGER,
      resumen TEXT,
      contenido TEXT NOT NULL,
      imagen_destacada TEXT,
      imagen_alt TEXT,
      autor TEXT DEFAULT 'VCF Academy Houston',
      publicado INTEGER NOT NULL DEFAULT 1,
      destacada INTEGER NOT NULL DEFAULT 0,
      fecha_publicacion TEXT DEFAULT (datetime('now')),
      views INTEGER NOT NULL DEFAULT 0,
      meta_description TEXT,
      created_at TEXT DEFAULT (datetime('now')),
      updated_at TEXT DEFAULT (datetime('now')),
      FOREIGN KEY (categoria_id) REFERENCES noticias_categorias(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS contact_messages (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL,
      subject TEXT NOT NULL,
      message TEXT NOT NULL,
      leido INTEGER NOT NULL DEFAULT 0,
      created_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS inscripciones (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      parent_name TEXT NOT NULL,
      email TEXT NOT NULL,
      phone TEXT,
      child_name TEXT NOT NULL,
      child_category TEXT NOT NULL,
      message TEXT,
      estado TEXT NOT NULL DEFAULT 'pendiente',
      created_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS site_settings (
      key TEXT PRIMARY KEY,
      value TEXT NOT NULL
    );
  `);

  const adminCount = db.prepare('SELECT COUNT(*) as c FROM admin_users').get() as { c: number };
  if (adminCount.c > 0) {
    return;
  }

  // 1. Seed default admin user (admin / password)
  const defaultHash = bcrypt.hashSync('password', 10);
  db.prepare(
    'INSERT INTO admin_users (username, password_hash, role, email) VALUES (?, ?, ?, ?)'
  ).run('admin', defaultHash, 'super_admin', 'admin@vcfacademyhouston.com');

  db.prepare(
    'INSERT INTO admin_users (username, password_hash, role, email) VALUES (?, ?, ?, ?)'
  ).run('coach', defaultHash, 'editor_coach', 'coach@vcfacademyhouston.com');

  // 2. Seed Sedes & Canchas
  db.prepare(
    'INSERT INTO sedes (nombre, direccion, mapa_general_url, nota_acceso) VALUES (?, ?, ?, ?)'
  ).run(
    'B13 Sports Complex — Katy',
    '2203 N Westgreen Blvd, Katy, TX 77449',
    'https://maps.app.goo.gl/q27c1FCQw4cvspGX8',
    'Main entrance on N Westgreen Blvd. Free family parking available next to pitches.'
  );
  db.prepare(
    'INSERT INTO sedes (nombre, direccion, mapa_general_url, nota_acceso) VALUES (?, ?, ?, ?)'
  ).run(
    'Bear Creek Pioneers Park',
    '3535 War Memorial Dr, Houston, TX 77084',
    'https://maps.google.com/?q=Bear+Creek+Park+Houston',
    'Use North Entrance via Clay Rd for faster access to fields #5 and #12.'
  );

  const insertCancha = db.prepare(
    'INSERT INTO canchas (sede_id, numero_cancha, sobrenombre, indicaciones_extra, mapa_url) VALUES (?, ?, ?, ?, ?)'
  );
  insertCancha.run(1, 'Field #1 (U8–U10)', 'The Mestalla Pitch', 'Main illuminated turf pitch right in front of the clubhouse.', 'https://maps.app.goo.gl/q27c1FCQw4cvspGX8');
  insertCancha.run(1, 'Field #3 (U10–U12)', 'Paterna Training Ground', 'Near the main parking lot, south entrance.', 'https://maps.app.goo.gl/q27c1FCQw4cvspGX8');
  insertCancha.run(2, 'Field #5 (Tournament Pitch)', 'El Turia Arena', 'Near the playground area.', 'https://maps.google.com/?q=29.8260,-95.6238');
  insertCancha.run(2, 'Field #12 (U12–U14)', null, 'Behind the soccer office building.', 'https://maps.google.com/?q=29.8260,-95.6238');

  // 3. Seed Categorias
  const insertCat = db.prepare('INSERT INTO categorias (nombre, horarios_entrenamiento) VALUES (?, ?)');
  insertCat.run('U6', 'Tue / Thu · 4:30 PM – 5:30 PM');
  insertCat.run('U8', 'Mon / Wed · 5:00 PM – 6:15 PM');
  insertCat.run('U10', 'Mon / Wed / Fri · 5:00 PM – 6:30 PM');
  insertCat.run('U12', 'Mon / Wed / Fri · 6:00 PM – 7:30 PM');

  // 4. Seed 18 Official U10 Players (from seed_roster_players.sql) with dorsals, sub_positions, stats, and radar skills
  const players = [
    { nombre: 'Tiziano', apellido: 'Barrio', dorsal: 10, pos: 'Mediocampista', sub: 'Attacking Midfielder (CAM)', pj: 12, g: 7, a: 8, motm: 2, cs: 0, sk: [8, 8, 9, 9, 5, 7] },
    { nombre: 'Vicente', apellido: 'Ortegano Bracho', dorsal: 1, pos: 'Portero', sub: 'Goalkeeper (GK)', pj: 12, g: 0, a: 1, motm: 1, cs: 5, sk: [7, 4, 8, 6, 9, 8] },
    { nombre: 'Alexander', apellido: 'Morejon', dorsal: 9, pos: 'Delantero', sub: 'Center Forward (ST)', pj: 12, g: 11, a: 4, motm: 3, cs: 0, sk: [9, 9, 7, 8, 4, 8] },
    { nombre: 'Juan', apellido: 'Morales', dorsal: 8, pos: 'Mediocampista', sub: 'Central Midfielder (CM)', pj: 11, g: 4, a: 6, motm: 1, cs: 0, sk: [7, 7, 9, 8, 6, 7] },
    { nombre: 'Alessandro', apellido: 'Montilla Mejilla', dorsal: 4, pos: 'Defensa', sub: 'Center Back (CB)', pj: 12, g: 1, a: 2, motm: 1, cs: 5, sk: [7, 5, 7, 6, 9, 8] },
    { nombre: 'Mateo', apellido: 'Mata Altuve', dorsal: 6, pos: 'Mediocampista', sub: 'Defensive Midfielder (CDM)', pj: 11, g: 3, a: 5, motm: 0, cs: 0, sk: [7, 6, 8, 7, 8, 8] },
    { nombre: 'Juan', apellido: 'Lopez Rincon', dorsal: 2, pos: 'Defensa', sub: 'Right Back (RB)', pj: 10, g: 1, a: 3, motm: 0, cs: 4, sk: [8, 5, 7, 7, 8, 7] },
    { nombre: 'Antoine', apellido: 'Haddad', dorsal: 5, pos: 'Defensa', sub: 'Center Back (CB)', pj: 11, g: 2, a: 1, motm: 0, cs: 4, sk: [6, 5, 7, 6, 9, 8] },
    { nombre: 'Miguel', apellido: 'Gonzalez Gonzalez', dorsal: 14, pos: 'Mediocampista', sub: 'Box-to-Box Midfielder', pj: 10, g: 3, a: 4, motm: 0, cs: 0, sk: [8, 7, 8, 7, 6, 7] },
    { nombre: 'Jayden', apellido: 'Gomez', dorsal: 16, pos: 'Mediocampista', sub: 'Right Midfielder (RM)', pj: 10, g: 2, a: 3, motm: 0, cs: 0, sk: [8, 6, 7, 8, 5, 6] },
    { nombre: 'Leonardo', apellido: 'Garcia Fuenmayor', dorsal: 7, pos: 'Delantero', sub: 'Right Winger (RW)', pj: 12, g: 8, a: 5, motm: 1, cs: 0, sk: [9, 8, 7, 9, 4, 7] },
    { nombre: 'Jacob', apellido: 'Diaz', dorsal: 18, pos: 'Mediocampista', sub: 'Central Midfielder (CM)', pj: 9, g: 2, a: 2, motm: 0, cs: 0, sk: [7, 6, 8, 7, 6, 6] },
    { nombre: 'Sebastian', apellido: 'Brito-Stirpe', dorsal: 3, pos: 'Defensa', sub: 'Left Back (LB)', pj: 11, g: 0, a: 4, motm: 0, cs: 4, sk: [8, 5, 7, 7, 8, 7] },
    { nombre: 'Mateo', apellido: 'Briceno Diaz', dorsal: 11, pos: 'Delantero', sub: 'Left Winger (LW)', pj: 11, g: 6, a: 4, motm: 1, cs: 0, sk: [9, 8, 7, 8, 4, 7] },
    { nombre: 'Rodrigo', apellido: 'Bermudez Portillo', dorsal: 13, pos: 'Portero', sub: 'Goalkeeper (GK)', pj: 9, g: 0, a: 0, motm: 0, cs: 3, sk: [6, 4, 7, 5, 8, 8] },
    { nombre: 'Fabian', apellido: 'Perez Marcano', dorsal: 19, pos: 'Delantero', sub: 'Striker (ST)', pj: 10, g: 5, a: 2, motm: 0, cs: 0, sk: [8, 8, 6, 7, 4, 7] },
    { nombre: 'Santiago', apellido: 'Yepez Moreno', dorsal: 20, pos: 'Mediocampista', sub: 'Attacking Midfielder', pj: 10, g: 3, a: 3, motm: 0, cs: 0, sk: [7, 7, 8, 8, 5, 6] },
    { nombre: 'Justin', apellido: 'Flores', dorsal: 15, pos: 'Defensa', sub: 'Defender (CB/RB)', pj: 10, g: 1, a: 1, motm: 0, cs: 3, sk: [7, 5, 6, 6, 8, 8] }
  ];

  const insertRoster = db.prepare(
    'INSERT INTO roster (nombre, apellido, dorsal, posicion, sub_posicion, foto_url, categoria_id, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
  );
  const insertStats = db.prepare(
    'INSERT INTO roster_estadisticas (roster_id, partidos_jugados, goles, asistencias, motm, clean_sheets) VALUES (?, ?, ?, ?, ?, ?)'
  );
  const insertSkills = db.prepare(
    'INSERT INTO roster_habilidades (roster_id, pace, shooting, passing, dribbling, defense, physical) VALUES (?, ?, ?, ?, ?, ?, ?)'
  );

  for (const p of players) {
    const res = insertRoster.run(p.nombre, p.apellido, p.dorsal, p.pos, p.sub, null, 3);
    const rId = Number(res.lastInsertRowid);
    insertStats.run(rId, p.pj, p.g, p.a, p.motm, p.cs);
    insertSkills.run(rId, p.sk[0], p.sk[1], p.sk[2], p.sk[3], p.sk[4], p.sk[5]);
  }

  // 5. Seed Tournaments & Matches
  const insertTorneo = db.prepare('INSERT INTO torneos_info (nombre_torneo, temporada, archivado) VALUES (?, ?, ?)');
  insertTorneo.run('STXCL Premier League U10', 'Fall / Spring 2026', 0);
  insertTorneo.run('Houston Cup Invitational', '2026', 0);
  insertTorneo.run('Katy Champions Trophy', 'Winter 2026', 1);

  const now = new Date();
  const fmtDate = (offsetDays: number) => {
    const d = new Date(now.getTime() + offsetDays * 86400000);
    return d.toISOString().slice(0, 10);
  };

  const insertJuego = db.prepare(`
    INSERT INTO juegos (torneo_id, fecha, hora, rival, cancha, sede_id, ubicacion_mapa_url, estado, categoria, goles_vcf, goles_rival)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `);

  // Past games (with scores)
  insertJuego.run(1, fmtDate(-21), '10:00', 'Baytown United FC', 'Field #1', 1, 'https://maps.app.goo.gl/q27c1FCQw4cvspGX8', 'finalizado', 'U10', 4, 1);
  insertJuego.run(1, fmtDate(-14), '11:30', 'Katy Elite SC', 'Field #3', 1, 'https://maps.app.goo.gl/q27c1FCQw4cvspGX8', 'finalizado', 'U10', 3, 2);
  insertJuego.run(1, fmtDate(-7), '09:30', 'Houston Rangers FC', 'Field #5', 2, 'https://maps.google.com/?q=Bear+Creek+Park+Houston', 'finalizado', 'U10', 5, 0);
  insertJuego.run(3, fmtDate(-35), '14:00', 'Cy-Fair Texans', 'Field #1', 1, 'https://maps.app.goo.gl/q27c1FCQw4cvspGX8', 'finalizado', 'U10', 2, 2);
  insertJuego.run(3, fmtDate(-28), '16:00', 'Sugar Land Atletico', 'Field #1', 1, 'https://maps.app.goo.gl/q27c1FCQw4cvspGX8', 'finalizado', 'U10', 3, 1);

  // Upcoming games
  insertJuego.run(1, fmtDate(3), '10:30', 'North Shore FC', 'Field #1 (The Mestalla Pitch)', 1, 'https://maps.app.goo.gl/q27c1FCQw4cvspGX8', 'proximo', 'U10', null, null);
  insertJuego.run(1, fmtDate(10), '12:00', 'Challenge United', 'Field #5 (El Turia Arena)', 2, 'https://maps.google.com/?q=Bear+Creek+Park+Houston', 'proximo', 'U10', null, null);
  insertJuego.run(2, fmtDate(17), '15:00', 'Dynamo Youth West', 'Field #1 (The Mestalla Pitch)', 1, 'https://maps.app.goo.gl/q27c1FCQw4cvspGX8', 'proximo', 'U10', null, null);

  // 6. Seed Scorers (juego_goles)
  const insertGol = db.prepare('INSERT INTO juego_goles (juego_id, roster_id, goles, asistencias, minuto, tipo) VALUES (?, ?, ?, ?, ?, ?)');
  insertGol.run(1, 3, 2, 1, 14, 'normal'); // Alexander Morejon
  insertGol.run(1, 1, 1, 2, 28, 'normal'); // Tiziano Barrio
  insertGol.run(1, 11, 1, 0, 42, 'normal'); // Leonardo Garcia
  insertGol.run(2, 3, 2, 0, 19, 'normal');
  insertGol.run(2, 14, 1, 1, 47, 'normal'); // Mateo Briceno
  insertGol.run(3, 3, 2, 1, 11, 'normal');
  insertGol.run(3, 1, 2, 2, 25, 'normal');
  insertGol.run(3, 11, 1, 1, 39, 'normal');
  insertGol.run(4, 4, 1, 1, 22, 'normal');
  insertGol.run(4, 16, 1, 0, 50, 'normal');
  insertGol.run(5, 3, 2, 0, 15, 'normal');
  insertGol.run(5, 11, 1, 2, 44, 'normal');

  // 7. Seed MOTM Voting (1 active open voting + 1 closed winner)
  const startsAt = new Date(now.getTime() - 3600 * 1000).toISOString();
  const endsAt = new Date(now.getTime() + 48 * 3600 * 1000).toISOString();
  const motmRes = db.prepare(
    'INSERT INTO motm_votaciones (juego_id, starts_at, ends_at, status) VALUES (?, ?, ?, ?)'
  ).run(3, startsAt, endsAt, 'open');
  const motmId = Number(motmRes.lastInsertRowid);

  const insertNom = db.prepare(
    'INSERT INTO motm_nominees (votacion_id, nombre, foto_url, orden, roster_id) VALUES (?, ?, ?, ?, ?)'
  );
  const nom1 = Number(insertNom.run(motmId, 'Alexander Morejon', null, 1, 3).lastInsertRowid);
  const nom2 = Number(insertNom.run(motmId, 'Tiziano Barrio', null, 2, 1).lastInsertRowid);
  const nom3 = Number(insertNom.run(motmId, 'Vicente Ortegano Bracho', null, 3, 2).lastInsertRowid);

  const insertVote = db.prepare('INSERT INTO motm_votes (votacion_id, nominee_id, ip_address) VALUES (?, ?, ?)');
  insertVote.run(motmId, nom1, '10.0.0.1');
  insertVote.run(motmId, nom1, '10.0.0.2');
  insertVote.run(motmId, nom1, '10.0.0.3');
  insertVote.run(motmId, nom2, '10.0.0.4');
  insertVote.run(motmId, nom2, '10.0.0.5');
  insertVote.run(motmId, nom3, '10.0.0.6');

  // 8. Seed Star of the Month
  db.prepare(
    'INSERT INTO jugador_mes (nombre, categoria, dorsal, foto_url, descripcion_logro, mes) VALUES (?, ?, ?, ?, ?, ?)'
  ).run(
    'Alexander Morejon',
    'U10',
    9,
    null,
    'Outstanding leadership, relentless pressing, and clinical finishing throughout the month. Embodying the true Valencia CF DNA: Sentiment, Courage, and Fight. ¡Amunt Valencia!',
    'November 2026'
  );

  // 9. Seed Hero Slides
  const insertSlide = db.prepare(
    'INSERT INTO hero_slides (image_url, title, subtitle, button_text, button_url, orden, activo) VALUES (?, ?, ?, ?, ?, ?, 1)'
  );
  insertSlide.run(
    '/assets/img/news-placeholder-editorial.jpg',
    'Official Valencia CF Methodology in Houston',
    'Identity · Effort · Intelligence — Training the next generation in Katy & Houston, TX',
    'Join the Academy',
    '/join',
    1
  );
  insertSlide.run(
    '/assets/img/news-placeholder.jpg',
    'Compete in Premier Texas Youth Leagues',
    'Follow our U6–U12 squads across STXCL and Houston tournaments',
    'View Match Schedule',
    '/#tournaments',
    2
  );

  // 10. Seed News Categories & Articles
  const insertNewsCat = db.prepare(
    'INSERT INTO noticias_categorias (nombre, slug, color, descripcion, orden, activa) VALUES (?, ?, ?, ?, ?, 1)'
  );
  insertNewsCat.run('Match Recaps', 'match-recaps', '#FF6600', 'Match reports, scores, and tactical highlights', 1);
  insertNewsCat.run('Tournaments', 'tournaments', '#10B981', 'Tournament coverage and championship updates', 2);
  insertNewsCat.run('Academy News', 'academy-news', '#3B82F6', 'Official VCF Academy Houston announcements', 3);
  insertNewsCat.run('Player Stories', 'player-stories', '#F59E0B', 'Spotlights on our players and families', 4);
  insertNewsCat.run('Training & Methodology', 'training', '#8B5CF6', 'Valencia CF coaching philosophy and drills', 5);

  const insertNoticia = db.prepare(`
    INSERT INTO noticias (titulo, slug, categoria_id, resumen, contenido, imagen_destacada, imagen_alt, autor, publicado, destacada, views)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'VCF Academy Houston', 1, ?, ?)
  `);

  insertNoticia.run(
    'Statement 5–0 Victory Over Houston Rangers Showcases VCF Identity',
    'statement-5-0-victory-over-houston-rangers',
    1,
    'Our U10 squad delivered a masterclass in high pressing, quick ball circulation, and teamwork at Bear Creek Park.',
    '<p>Our U10 squad delivered an unforgettable performance this weekend, securing a <strong>5–0 victory</strong> against Houston Rangers FC with goals from Alexander Morejon (2), Tiziano Barrio (2), and Leonardo Garcia.</p><p>From the opening whistle, the team applied the core principles of the Paterna methodology: building cleanly from the back, pressing as a compact unit after losing possession, and supporting teammates in every zone of the pitch.</p><p>Goalkeeper Vicente Ortegano Bracho and the defensive line anchored by Alessandro Montilla and Antoine Haddad earned a well-deserved clean sheet.</p>',
    '/assets/img/news-placeholder-editorial.jpg',
    'VCF Academy Houston Match Recap',
    1,
    142
  );

  insertNoticia.run(
    'Open Tryouts & Registration Now Open for U6, U8, U10, and U12',
    'open-tryouts-registration-open-2026',
    3,
    'Join the official Valencia CF youth development program at B13 Sports Complex in Katy, TX.',
    '<p>VCF Academy Houston is expanding its squads for the upcoming season! Families in Katy and Greater Houston are invited to register for an evaluation session at our primary headquarters at <strong>B13 Sports Complex (2203 N Westgreen Blvd, Katy, TX)</strong>.</p><p>Our coaching staff guides players through technical mastery, tactical intelligence, and competitive character in an inspiring environment.</p>',
    '/assets/img/news-placeholder.jpg',
    'VCF Academy Tryouts',
    1,
    98
  );

  insertNoticia.run(
    'Inside the VCF Methodology: Educating People, Training Footballers',
    'inside-vcf-methodology-educating-people-training-footballers',
    5,
    'Discover how Identity, Effort, and Intelligence shape every training session in Houston.',
    '<p>For decades, Valencia CF’s Academia in Paterna has produced some of Europe’s finest footballers. Here in Houston, we bring those exact three pillars to every training session:</p><ul><li><strong>Identity:</strong> Respect, humility, camaraderie, and pride in the bat crest.</li><li><strong>Effort:</strong> Intensity in every drill, resilience in adversity, and discipline.</li><li><strong>Intelligence:</strong> Scanning the field before receiving, understanding spatial superiority, and making smart autonomous decisions on the pitch.</li></ul>',
    '/assets/img/news-placeholder-editorial.jpg',
    'VCF Methodology',
    0,
    76
  );

  // 11. Seed Site Settings (Support / Donations / Contact)
  const insertSetting = db.prepare('INSERT INTO site_settings (key, value) VALUES (?, ?)');
  const defaultSettings: Record<string, string> = {
    public_email: 'info@vcfacademyhouston.com',
    phone: '+1 (832) 555-0199',
    instagram_url: 'https://instagram.com/vcfacademyhouston',
    facebook_url: 'https://facebook.com/vcfacademyhouston',
    youtube_url: 'https://youtube.com',
    x_url: 'https://x.com/valenciacf',
    donation_paypal_url: 'https://paypal.me/vcfacademyhouston',
    donation_venmo_url: 'https://venmo.com/',
    donation_kofi_url: '',
    donation_zelle_email: 'support@vcfacademyhouston.com',
    donation_other_note: 'All contributions go 100% toward server hosting, domain renewal, and platform improvements for our players and families.',
    donation_hosting_due_date: '2027-04-01',
    donation_hosting_paid_amount: '$129.00 / year',
    donation_domain_due_date: '2027-03-15',
    donation_domain_paid_amount: '$19.99 / year',
    donation_parent_contributions: [
      '04/03/2026|Francis Moreno de Yepez|$15.00',
      '04/03/2026|Yeneli Farinas|$20.00',
      '04/03/2026|Luis F Gonzalez Luna|$20.00',
      '04/03/2026|Vanesa Montero (Tiziano)|$20.00'
    ].join('\n')
  };

  for (const [k, v] of Object.entries(defaultSettings)) {
    insertSetting.run(k, v);
  }
}

export function logAdminActivity(userId: number | null, username: string | null, action: string, details?: string) {
  try {
    const db = getDb();
    db.prepare(
      'INSERT INTO admin_activity_log (user_id, username, action, details) VALUES (?, ?, ?, ?)'
    ).run(userId, username, action, details ?? null);
  } catch {
    // ignore logging errors
  }
}
