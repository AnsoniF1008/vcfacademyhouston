import { getDb } from './db';

/**
 * Node.js built-in `node:sqlite` returns rows with a `null` prototype,
 * which Next.js Server->Client Component serialization rejects.
 * Convert rows into standard plain JS objects.
 */
function plain<T>(row: T): T {
  if (row === null || row === undefined || typeof row !== 'object') return row;
  if (Array.isArray(row)) return row.map((item) => plain(item)) as unknown as T;
  const out: Record<string, unknown> = {};
  for (const key of Object.keys(row as Record<string, unknown>)) {
    out[key] = plain((row as Record<string, unknown>)[key]);
  }
  return out as T;
}

function plainList<T>(rows: T[]): T[] {
  return rows.map((r) => plain(r));
}

export interface Sede {
  id: number;
  nombre: string;
  direccion: string;
  mapa_general_url: string | null;
  nota_acceso: string | null;
  canchas: Cancha[];
}

export interface Cancha {
  id: number;
  sede_id: number;
  numero_cancha: string;
  sobrenombre: string | null;
  indicaciones_extra: string | null;
  mapa_url: string | null;
}

export interface Categoria {
  id: number;
  nombre: string;
  horarios_entrenamiento: string | null;
}

export interface PlayerWithStats {
  id: number;
  nombre: string;
  apellido: string;
  dorsal: number | null;
  posicion: string | null;
  sub_posicion: string | null;
  foto_url: string | null;
  categoria_id: number;
  categoria_nombre: string;
  activo: number;
  partidos_jugados: number;
  goles: number;
  asistencias: number;
  motm: number;
  clean_sheets: number;
  pace: number;
  shooting: number;
  passing: number;
  dribbling: number;
  defense: number;
  physical: number;
  overall: number;
  group: 'GK' | 'DEF' | 'MID' | 'FWD';
  pos_short: string;
  initials: string;
}

export interface MatchRow {
  id: number;
  torneo_id: number;
  nombre_torneo: string;
  temporada: string | null;
  archivado: number;
  fecha: string;
  hora: string | null;
  rival: string | null;
  cancha: string | null;
  sede_id: number | null;
  sede_nombre: string | null;
  ubicacion_mapa_url: string | null;
  estado: 'proximo' | 'live' | 'finalizado';
  categoria: string | null;
  goles_vcf: number | null;
  goles_rival: number | null;
  rival_logo_url: string | null;
}

export function getSiteSettings(): Record<string, string> {
  const db = getDb();
  const rows = db.prepare('SELECT key, value FROM site_settings').all() as { key: string; value: string }[];
  const out: Record<string, string> = {};
  for (const r of rows) {
    out[r.key] = r.value;
  }
  return out;
}

export function getSedesWithCanchas(): Sede[] {
  const db = getDb();
  const sedes = plainList(
    db.prepare('SELECT id, nombre, direccion, mapa_general_url, nota_acceso FROM sedes ORDER BY id ASC').all() as unknown as Omit<Sede, 'canchas'>[]
  );
  const canchas = plainList(
    db.prepare('SELECT id, sede_id, numero_cancha, sobrenombre, indicaciones_extra, mapa_url FROM canchas ORDER BY sede_id ASC, id ASC').all() as unknown as Cancha[]
  );

  return sedes.map((s) => ({
    ...s,
    canchas: canchas.filter((c) => c.sede_id === s.id),
  }));
}

export function getCategorias(): Categoria[] {
  const db = getDb();
  return plainList(
    db.prepare('SELECT id, nombre, horarios_entrenamiento FROM categorias ORDER BY id ASC').all() as unknown as Categoria[]
  );
}

export function getActiveRoster(): PlayerWithStats[] {
  const db = getDb();
  const rows = db.prepare(`
    SELECT
      r.id, r.nombre, r.apellido, r.dorsal, r.posicion, r.sub_posicion, r.foto_url,
      r.categoria_id, c.nombre AS categoria_nombre, r.activo,
      COALESCE(es.partidos_jugados, 0) AS partidos_jugados,
      COALESCE(jg.calc_goles, es.goles, 0) AS goles,
      COALESCE(jg.calc_asistencias, es.asistencias, 0) AS asistencias,
      COALESCE(es.motm, 0) AS motm,
      COALESCE(es.clean_sheets, 0) AS clean_sheets,
      COALESCE(sk.pace, 7) AS pace,
      COALESCE(sk.shooting, 7) AS shooting,
      COALESCE(sk.passing, 7) AS passing,
      COALESCE(sk.dribbling, 7) AS dribbling,
      COALESCE(sk.defense, 6) AS defense,
      COALESCE(sk.physical, 7) AS physical
    FROM roster r
    JOIN categorias c ON c.id = r.categoria_id
    LEFT JOIN roster_estadisticas es ON es.roster_id = r.id
    LEFT JOIN roster_habilidades sk ON sk.roster_id = r.id
    LEFT JOIN (
      SELECT roster_id, SUM(goles) AS calc_goles, SUM(asistencias) AS calc_asistencias
      FROM juego_goles
      GROUP BY roster_id
    ) jg ON jg.roster_id = r.id
    WHERE r.activo = 1
    ORDER BY c.nombre ASC, goles DESC, r.dorsal ASC, r.apellido ASC
  `).all() as Record<string, unknown>[];

  const posMap: Record<string, 'GK' | 'DEF' | 'MID' | 'FWD'> = {
    Portero: 'GK',
    Defensa: 'DEF',
    Mediocampista: 'MID',
    Delantero: 'FWD',
  };

  return rows.map((r) => {
    const pos = String(r.posicion || 'Mediocampista');
    const group = posMap[pos] || 'MID';
    const pace = Number(r.pace || 7);
    const shooting = Number(r.shooting || 7);
    const passing = Number(r.passing || 7);
    const dribbling = Number(r.dribbling || 7);
    const defense = Number(r.defense || 6);
    const physical = Number(r.physical || 7);
    const avg10 = (pace + shooting + passing + dribbling + defense + physical) / 6;
    const overall = Math.min(99, Math.max(65, Math.round(avg10 * 10 + 4)));

    const n1 = String(r.nombre || '').trim();
    const n2 = String(r.apellido || '').trim();
    const initials = `${n1.charAt(0)}${n2.charAt(0)}`.toUpperCase() || 'VC';

    return {
      id: Number(r.id),
      nombre: n1,
      apellido: n2,
      dorsal: r.dorsal !== null && r.dorsal !== undefined ? Number(r.dorsal) : null,
      posicion: pos,
      sub_posicion: r.sub_posicion ? String(r.sub_posicion) : null,
      foto_url: r.foto_url ? String(r.foto_url) : null,
      categoria_id: Number(r.categoria_id),
      categoria_nombre: String(r.categoria_nombre || 'U10'),
      activo: Number(r.activo),
      partidos_jugados: Number(r.partidos_jugados),
      goles: Number(r.goles),
      asistencias: Number(r.asistencias),
      motm: Number(r.motm),
      clean_sheets: Number(r.clean_sheets),
      pace,
      shooting,
      passing,
      dribbling,
      defense,
      physical,
      overall,
      group,
      pos_short: group,
      initials,
    };
  });
}

export function getAllMatches(): MatchRow[] {
  const db = getDb();
  return plainList(
    db.prepare(`
      SELECT
        j.id, j.torneo_id, t.nombre_torneo, t.temporada, t.archivado,
        j.fecha, j.hora, j.rival, j.cancha, j.sede_id, s.nombre AS sede_nombre,
        j.ubicacion_mapa_url, j.estado, j.categoria,
        j.goles_vcf, j.goles_rival, j.rival_logo_url
      FROM juegos j
      JOIN torneos_info t ON t.id = j.torneo_id
      LEFT JOIN sedes s ON s.id = j.sede_id
      ORDER BY j.fecha ASC, j.hora ASC
    `).all() as unknown as MatchRow[]
  );
}

export function getSeasonStats() {
  const matches = getAllMatches();
  let W = 0, L = 0, D = 0, GF = 0, GA = 0, PTS = 0, played = 0, CS = 0;

  for (const m of matches) {
    if (m.goles_vcf !== null && m.goles_rival !== null) {
      const gv = Number(m.goles_vcf);
      const gr = Number(m.goles_rival);
      played++;
      GF += gv;
      GA += gr;
      if (gv > gr) {
        W++;
        PTS += 3;
      } else if (gv < gr) {
        L++;
      } else {
        D++;
        PTS += 1;
      }
      if (gr === 0) CS++;
    }
  }

  return { W, L, D, GF, GA, GD: GF - GA, PTS, played, CS };
}

export function getMotmData() {
  const db = getDb();
  const nowIso = new Date().toISOString();

  // Auto-close expired votations
  const expired = db.prepare(`SELECT id FROM motm_votaciones WHERE status = 'open' AND ends_at <= ?`).all(nowIso) as { id: number }[];
  for (const exp of expired) {
    const topNom = db.prepare(`
      SELECT n.id, COUNT(v.id) as votes
      FROM motm_nominees n
      LEFT JOIN motm_votes v ON v.nominee_id = n.id
      WHERE n.votacion_id = ?
      GROUP BY n.id
      ORDER BY votes DESC, n.orden ASC
      LIMIT 1
    `).get(exp.id) as { id: number; votes: number } | undefined;

    db.prepare(`UPDATE motm_votaciones SET status = 'closed', winner_nominee_id = ? WHERE id = ?`).run(
      topNom?.id ?? null,
      exp.id
    );
  }

  const openVotation = db.prepare(`
    SELECT v.id, v.juego_id, v.starts_at, v.ends_at, v.status, j.fecha, j.rival, j.goles_vcf, j.goles_rival
    FROM motm_votaciones v
    JOIN juegos j ON j.id = v.juego_id
    WHERE v.status = 'open' AND v.ends_at > ?
    ORDER BY v.ends_at ASC
    LIMIT 1
  `).get(nowIso) as Record<string, unknown> | undefined;

  if (openVotation) {
    const nominees = db.prepare(`
      SELECT n.id, n.nombre, n.foto_url, n.orden, n.roster_id, COUNT(mv.id) as votes
      FROM motm_nominees n
      LEFT JOIN motm_votes mv ON mv.nominee_id = n.id
      WHERE n.votacion_id = ?
      GROUP BY n.id
      ORDER BY n.orden ASC
    `).all(Number(openVotation.id)) as {
      id: number;
      nombre: string;
      foto_url: string | null;
      orden: number;
      roster_id: number | null;
      votes: number;
    }[];

    const totalVotes = nominees.reduce((acc, n) => acc + Number(n.votes), 0);

    return {
      open: {
        id: Number(openVotation.id),
        juego_id: Number(openVotation.juego_id),
        ends_at: String(openVotation.ends_at),
        fecha: String(openVotation.fecha),
        rival: String(openVotation.rival || 'Opponent'),
        goles_vcf: openVotation.goles_vcf !== null ? Number(openVotation.goles_vcf) : null,
        goles_rival: openVotation.goles_rival !== null ? Number(openVotation.goles_rival) : null,
        total_votes: totalVotes,
        nominees: nominees.map((n) => ({
          ...n,
          votes: Number(n.votes),
          pct: totalVotes > 0 ? Math.round((Number(n.votes) / totalVotes) * 100) : 0,
        })),
      },
      winner: null,
    };
  }

  const closedWinner = db.prepare(`
    SELECT v.id, v.winner_nominee_id, n.nombre AS winner_nombre, n.foto_url AS winner_foto, j.rival, j.fecha
    FROM motm_votaciones v
    JOIN motm_nominees n ON n.id = v.winner_nominee_id
    JOIN juegos j ON j.id = v.juego_id
    WHERE v.status = 'closed' AND v.winner_nominee_id IS NOT NULL
    ORDER BY v.ends_at DESC
    LIMIT 1
  `).get() as Record<string, unknown> | undefined;

  if (closedWinner) {
    const counts = db.prepare(`
      SELECT COUNT(*) as total, SUM(CASE WHEN nominee_id = ? THEN 1 ELSE 0 END) as w_votes
      FROM motm_votes
      WHERE votacion_id = ?
    `).get(Number(closedWinner.winner_nominee_id), Number(closedWinner.id)) as { total: number; w_votes: number };

    const total = Number(counts?.total || 0);
    const wVotes = Number(counts?.w_votes || 0);

    return {
      open: null,
      winner: {
        id: Number(closedWinner.id),
        nombre: String(closedWinner.winner_nombre),
        foto_url: closedWinner.winner_foto ? String(closedWinner.winner_foto) : null,
        rival: String(closedWinner.rival || ''),
        fecha: String(closedWinner.fecha || ''),
        total_votes: total,
        winner_votes: wVotes,
        winner_pct: total > 0 ? Math.round((wVotes / total) * 100) : 100,
      },
    };
  }

  return { open: null, winner: null };
}

export function getStarOfTheMonth() {
  const db = getDb();
  const row = db.prepare(`
    SELECT id, nombre, categoria, dorsal, foto_url, descripcion_logro, mes
    FROM jugador_mes
    ORDER BY id DESC
    LIMIT 1
  `).get() as {
    id: number;
    nombre: string;
    categoria: string;
    dorsal: number | null;
    foto_url: string | null;
    descripcion_logro: string;
    mes: string;
  } | undefined;
  return row ? plain(row) : undefined;
}

export function getHeroSlides() {
  const db = getDb();
  return plainList(
    db.prepare(`
      SELECT id, image_url, title, subtitle, button_text, button_url, orden, activo
      FROM hero_slides
      WHERE activo = 1
      ORDER BY orden ASC, id ASC
    `).all() as unknown as {
      id: number;
      image_url: string;
      title: string;
      subtitle: string | null;
      button_text: string | null;
      button_url: string | null;
      orden: number;
      activo: number;
    }[]
  );
}

export function getMatchReels() {
  const db = getDb();
  return plainList(
    db.prepare(`
      SELECT r.id, r.video_url, r.caption, r.orden, r.view_count, r.like_count,
             p.nombre AS player_nombre, p.apellido AS player_apellido,
             j.rival AS match_rival
      FROM match_reels r
      LEFT JOIN roster p ON p.id = r.player_id
      LEFT JOIN juegos j ON j.id = r.match_id
      ORDER BY r.orden ASC, r.id DESC
    `).all() as unknown as {
      id: number;
      video_url: string;
      caption: string | null;
      orden: number;
      view_count: number;
      like_count: number;
      player_nombre: string | null;
      player_apellido: string | null;
      match_rival: string | null;
    }[]
  );
}

export function getLatestNews(limit = 3, categorySlug?: string) {
  const db = getDb();
  if (categorySlug) {
    return plainList(
      db.prepare(`
        SELECT n.id, n.titulo, n.slug, n.resumen, n.contenido, n.imagen_destacada, n.imagen_alt,
               n.autor, n.fecha_publicacion, n.views, n.destacada,
               c.nombre AS categoria_nombre, c.slug AS categoria_slug, c.color AS categoria_color
        FROM noticias n
        LEFT JOIN noticias_categorias c ON c.id = n.categoria_id
        WHERE n.publicado = 1 AND c.slug = ?
        ORDER BY n.destacada DESC, n.id DESC
        LIMIT ?
      `).all(categorySlug, limit) as Record<string, unknown>[]
    );
  }
  return plainList(
    db.prepare(`
      SELECT n.id, n.titulo, n.slug, n.resumen, n.contenido, n.imagen_destacada, n.imagen_alt,
             n.autor, n.fecha_publicacion, n.views, n.destacada,
             c.nombre AS categoria_nombre, c.slug AS categoria_slug, c.color AS categoria_color
      FROM noticias n
      LEFT JOIN noticias_categorias c ON c.id = n.categoria_id
      WHERE n.publicado = 1
      ORDER BY n.destacada DESC, n.id DESC
      LIMIT ?
    `).all(limit) as Record<string, unknown>[]
  );
}

export function getNewsCategories() {
  const db = getDb();
  return plainList(
    db.prepare(`
      SELECT id, nombre, slug, color, descripcion, orden
      FROM noticias_categorias
      WHERE activa = 1
      ORDER BY orden ASC, id ASC
    `).all() as unknown as {
      id: number;
      nombre: string;
      slug: string;
      color: string;
      descripcion: string | null;
      orden: number;
    }[]
  );
}
