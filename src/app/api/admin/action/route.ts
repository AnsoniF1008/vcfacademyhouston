import { NextRequest, NextResponse } from 'next/server';
import { getSessionUser, canAccess } from '@/lib/auth';
import { getDb, logAdminActivity } from '@/lib/db';
import { revalidatePath } from 'next/cache';
import bcrypt from 'bcryptjs';

export async function GET() {
  const user = await getSessionUser();
  if (!user) {
    return NextResponse.json({ ok: false, error: 'Unauthorized' }, { status: 401 });
  }

  const db = getDb();

  const roster = db.prepare(`
    SELECT r.*, c.nombre AS categoria_nombre,
           COALESCE(es.partidos_jugados, 0) AS partidos_jugados,
           COALESCE(es.goles, 0) AS goles,
           COALESCE(es.asistencias, 0) AS asistencias,
           COALESCE(es.motm, 0) AS motm,
           COALESCE(es.clean_sheets, 0) AS clean_sheets,
           COALESCE(sk.pace, 7) AS pace,
           COALESCE(sk.shooting, 7) AS shooting,
           COALESCE(sk.passing, 7) AS passing,
           COALESCE(sk.dribbling, 7) AS dribbling,
           COALESCE(sk.defense, 6) AS defense,
           COALESCE(sk.physical, 7) AS physical
    FROM roster r
    LEFT JOIN categorias c ON c.id = r.categoria_id
    LEFT JOIN roster_estadisticas es ON es.roster_id = r.id
    LEFT JOIN roster_habilidades sk ON sk.roster_id = r.id
    ORDER BY r.categoria_id ASC, r.dorsal ASC, r.apellido ASC
  `).all();

  const categorias = db.prepare('SELECT * FROM categorias ORDER BY id ASC').all();
  const sedes = db.prepare('SELECT * FROM sedes ORDER BY id ASC').all();
  const canchas = db.prepare('SELECT * FROM canchas ORDER BY sede_id ASC, id ASC').all();
  const torneos = db.prepare('SELECT * FROM torneos_info ORDER BY id DESC').all();
  const juegos = db.prepare(`
    SELECT j.*, t.nombre_torneo, s.nombre AS sede_nombre
    FROM juegos j
    LEFT JOIN torneos_info t ON t.id = j.torneo_id
    LEFT JOIN sedes s ON s.id = j.sede_id
    ORDER BY j.fecha DESC, j.hora DESC
  `).all();
  const juegoGoles = db.prepare(`
    SELECT g.*, r.nombre, r.apellido, r.dorsal
    FROM juego_goles g
    LEFT JOIN roster r ON r.id = g.roster_id
  `).all();

  const motmVotaciones = db.prepare(`
    SELECT v.*, j.rival, j.fecha
    FROM motm_votaciones v
    LEFT JOIN juegos j ON j.id = v.juego_id
    ORDER BY v.id DESC
  `).all();

  const motmNominees = db.prepare(`
    SELECT n.*, COUNT(mv.id) as votes
    FROM motm_nominees n
    LEFT JOIN motm_votes mv ON mv.nominee_id = n.id
    GROUP BY n.id
    ORDER BY n.votacion_id DESC, n.orden ASC
  `).all();

  const star = db.prepare('SELECT * FROM jugador_mes ORDER BY id DESC LIMIT 1').get();
  const heroSlides = db.prepare('SELECT * FROM hero_slides ORDER BY orden ASC, id ASC').all();
  const matchReels = db.prepare('SELECT * FROM match_reels ORDER BY orden ASC, id DESC').all();
  const noticias = db.prepare(`
    SELECT n.*, c.nombre AS categoria_nombre, c.color AS categoria_color
    FROM noticias n
    LEFT JOIN noticias_categorias c ON c.id = n.categoria_id
    ORDER BY n.id DESC
  `).all();
  const noticiasCategorias = db.prepare('SELECT * FROM noticias_categorias ORDER BY orden ASC').all();
  const inscripciones = db.prepare('SELECT * FROM inscripciones ORDER BY id DESC').all();
  const contactMessages = db.prepare('SELECT * FROM contact_messages ORDER BY id DESC').all();

  const settingsRows = db.prepare('SELECT key, value FROM site_settings').all() as { key: string; value: string }[];
  const settings: Record<string, string> = {};
  for (const r of settingsRows) settings[r.key] = r.value;

  const users =
    user.role === 'super_admin'
      ? db.prepare('SELECT id, username, role, email, created_at FROM admin_users ORDER BY id ASC').all()
      : [];

  const activityLog = db.prepare('SELECT * FROM admin_activity_log ORDER BY id DESC LIMIT 60').all();

  return NextResponse.json({
    ok: true,
    user,
    data: {
      roster,
      categorias,
      sedes,
      canchas,
      torneos,
      juegos,
      juegoGoles,
      motmVotaciones,
      motmNominees,
      star,
      heroSlides,
      matchReels,
      noticias,
      noticiasCategorias,
      inscripciones,
      contactMessages,
      settings,
      users,
      activityLog,
    },
  });
}

export async function POST(req: NextRequest) {
  const user = await getSessionUser();
  if (!user) {
    return NextResponse.json({ ok: false, error: 'Unauthorized' }, { status: 401 });
  }

  try {
    const body = await req.json();
    const action = String(body.action || '');
    const payload = body.payload || {};
    const db = getDb();

    switch (action) {
      // ── ROSTER CRUD ──────────────────────────────────────────────
      case 'roster.save': {
        if (!canAccess(user.role, 'roster_edit')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id || 0);
        const nombre = String(payload.nombre || '').trim();
        const apellido = String(payload.apellido || '').trim();
        const dorsal = payload.dorsal !== '' && payload.dorsal !== null ? Number(payload.dorsal) : null;
        const posicion = String(payload.posicion || 'Mediocampista');
        const sub_posicion = String(payload.sub_posicion || '').trim() || null;
        const foto_url = String(payload.foto_url || '').trim() || null;
        const categoria_id = Number(payload.categoria_id || 3);
        const activo = payload.activo === undefined ? 1 : Number(payload.activo ? 1 : 0);

        let rosterId = id;
        if (id > 0) {
          db.prepare(`
            UPDATE roster
            SET nombre=?, apellido=?, dorsal=?, posicion=?, sub_posicion=?, foto_url=?, categoria_id=?, activo=?
            WHERE id=?
          `).run(nombre, apellido, dorsal, posicion, sub_posicion, foto_url, categoria_id, activo, id);
        } else {
          const r = db.prepare(`
            INSERT INTO roster (nombre, apellido, dorsal, posicion, sub_posicion, foto_url, categoria_id, activo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
          `).run(nombre, apellido, dorsal, posicion, sub_posicion, foto_url, categoria_id, activo);
          rosterId = Number(r.lastInsertRowid);
        }

        // Save stats & radar skills
        db.prepare(`
          INSERT INTO roster_estadisticas (roster_id, partidos_jugados, goles, asistencias, motm, clean_sheets)
          VALUES (?, ?, ?, ?, ?, ?)
          ON CONFLICT(roster_id) DO UPDATE SET
            partidos_jugados=excluded.partidos_jugados,
            goles=excluded.goles,
            asistencias=excluded.asistencias,
            motm=excluded.motm,
            clean_sheets=excluded.clean_sheets
        `).run(
          rosterId,
          Number(payload.partidos_jugados || 0),
          Number(payload.goles || 0),
          Number(payload.asistencias || 0),
          Number(payload.motm || 0),
          Number(payload.clean_sheets || 0)
        );

        db.prepare(`
          INSERT INTO roster_habilidades (roster_id, pace, shooting, passing, dribbling, defense, physical)
          VALUES (?, ?, ?, ?, ?, ?, ?)
          ON CONFLICT(roster_id) DO UPDATE SET
            pace=excluded.pace,
            shooting=excluded.shooting,
            passing=excluded.passing,
            dribbling=excluded.dribbling,
            defense=excluded.defense,
            physical=excluded.physical
        `).run(
          rosterId,
          Math.min(10, Math.max(1, Number(payload.pace || 7))),
          Math.min(10, Math.max(1, Number(payload.shooting || 7))),
          Math.min(10, Math.max(1, Number(payload.passing || 7))),
          Math.min(10, Math.max(1, Number(payload.dribbling || 7))),
          Math.min(10, Math.max(1, Number(payload.defense || 6))),
          Math.min(10, Math.max(1, Number(payload.physical || 7)))
        );

        logAdminActivity(user.id, user.username, 'roster.save', `${nombre} ${apellido} (#${dorsal ?? '-'})`);
        break;
      }

      case 'roster.delete': {
        if (user.role !== 'super_admin') {
          return NextResponse.json({ ok: false, error: 'Only Super Admin can delete players' }, { status: 403 });
        }
        const id = Number(payload.id);
        db.prepare('DELETE FROM roster WHERE id = ?').run(id);
        logAdminActivity(user.id, user.username, 'roster.delete', `Deleted player ID ${id}`);
        break;
      }

      // ── TORNEOS & JUEGOS CRUD ────────────────────────────────────
      case 'torneo.save': {
        if (!canAccess(user.role, 'torneos')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id || 0);
        const nombre_torneo = String(payload.nombre_torneo || '').trim();
        const temporada = String(payload.temporada || '').trim() || null;
        const archivado = payload.archivado ? 1 : 0;

        if (id > 0) {
          db.prepare('UPDATE torneos_info SET nombre_torneo=?, temporada=?, archivado=? WHERE id=?').run(
            nombre_torneo,
            temporada,
            archivado,
            id
          );
        } else {
          db.prepare('INSERT INTO torneos_info (nombre_torneo, temporada, archivado) VALUES (?, ?, ?)').run(
            nombre_torneo,
            temporada,
            archivado
          );
        }
        logAdminActivity(user.id, user.username, 'torneo.save', nombre_torneo);
        break;
      }

      case 'torneo.delete': {
        if (!canAccess(user.role, 'torneos')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id);
        db.prepare('DELETE FROM torneos_info WHERE id = ?').run(id);
        logAdminActivity(user.id, user.username, 'torneo.delete', `Deleted tournament ID ${id}`);
        break;
      }

      case 'juego.save': {
        if (!canAccess(user.role, 'juegos') && !canAccess(user.role, 'juegos_live_score')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id || 0);
        const torneo_id = Number(payload.torneo_id || 1);
        const fecha = String(payload.fecha || new Date().toISOString().slice(0, 10));
        const hora = String(payload.hora || '').trim() || null;
        const rival = String(payload.rival || '').trim() || 'TBD';
        const cancha = String(payload.cancha || '').trim() || null;
        const sede_id = payload.sede_id ? Number(payload.sede_id) : null;
        const ubicacion_mapa_url = String(payload.ubicacion_mapa_url || '').trim() || null;
        const estado = String(payload.estado || 'proximo');
        const categoria = String(payload.categoria || 'U10').trim();
        const goles_vcf =
          payload.goles_vcf !== '' && payload.goles_vcf !== null && payload.goles_vcf !== undefined
            ? Number(payload.goles_vcf)
            : null;
        const goles_rival =
          payload.goles_rival !== '' && payload.goles_rival !== null && payload.goles_rival !== undefined
            ? Number(payload.goles_rival)
            : null;
        const rival_logo_url = String(payload.rival_logo_url || '').trim() || null;

        let juegoId = id;
        if (id > 0) {
          db.prepare(`
            UPDATE juegos
            SET torneo_id=?, fecha=?, hora=?, rival=?, cancha=?, sede_id=?, ubicacion_mapa_url=?,
                estado=?, categoria=?, goles_vcf=?, goles_rival=?, rival_logo_url=?
            WHERE id=?
          `).run(
            torneo_id,
            fecha,
            hora,
            rival,
            cancha,
            sede_id,
            ubicacion_mapa_url,
            estado,
            categoria,
            goles_vcf,
            goles_rival,
            rival_logo_url,
            id
          );
        } else {
          const res = db.prepare(`
            INSERT INTO juegos (torneo_id, fecha, hora, rival, cancha, sede_id, ubicacion_mapa_url, estado, categoria, goles_vcf, goles_rival, rival_logo_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
          `).run(
            torneo_id,
            fecha,
            hora,
            rival,
            cancha,
            sede_id,
            ubicacion_mapa_url,
            estado,
            categoria,
            goles_vcf,
            goles_rival,
            rival_logo_url
          );
          juegoId = Number(res.lastInsertRowid);
        }

        // Optional scorers array
        if (Array.isArray(payload.scorers)) {
          db.prepare('DELETE FROM juego_goles WHERE juego_id = ?').run(juegoId);
          const insGol = db.prepare(
            'INSERT INTO juego_goles (juego_id, roster_id, goles, asistencias, minuto, tipo) VALUES (?, ?, ?, ?, ?, ?)'
          );
          for (const sc of payload.scorers) {
            if (sc.roster_id && (Number(sc.goles) > 0 || Number(sc.asistencias) > 0)) {
              insGol.run(
                juegoId,
                Number(sc.roster_id),
                Number(sc.goles || 0),
                Number(sc.asistencias || 0),
                sc.minuto ? Number(sc.minuto) : null,
                sc.tipo || 'normal'
              );
            }
          }
        }

        logAdminActivity(user.id, user.username, 'juego.save', `VCF vs ${rival} (${fecha})`);
        break;
      }

      case 'juego.delete': {
        if (!canAccess(user.role, 'juegos')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id);
        db.prepare('DELETE FROM juegos WHERE id = ?').run(id);
        logAdminActivity(user.id, user.username, 'juego.delete', `Deleted match ID ${id}`);
        break;
      }

      // ── MOTM VOTING CRUD ─────────────────────────────────────────
      case 'motm.create': {
        if (!canAccess(user.role, 'motm')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const juego_id = Number(payload.juego_id);
        const hours = Math.max(1, Number(payload.hours || 24));
        const nomineeIds: number[] = Array.isArray(payload.roster_ids) ? payload.roster_ids.map(Number) : [];

        if (!juego_id || nomineeIds.length < 2) {
          return NextResponse.json(
            { ok: false, error: 'Select a match and at least 2 players to nominate.' },
            { status: 400 }
          );
        }

        // Close any open polls first
        db.prepare(`UPDATE motm_votaciones SET status = 'closed' WHERE status = 'open'`).run();

        const startsAt = new Date().toISOString();
        const endsAt = new Date(Date.now() + hours * 3600 * 1000).toISOString();

        const res = db
          .prepare('INSERT INTO motm_votaciones (juego_id, starts_at, ends_at, status) VALUES (?, ?, ?, ?)')
          .run(juego_id, startsAt, endsAt, 'open');
        const votId = Number(res.lastInsertRowid);

        const insNom = db.prepare(
          'INSERT INTO motm_nominees (votacion_id, nombre, foto_url, orden, roster_id) VALUES (?, ?, ?, ?, ?)'
        );
        nomineeIds.slice(0, 4).forEach((rid, idx) => {
          const p = db.prepare('SELECT id, nombre, apellido, foto_url FROM roster WHERE id = ?').get(rid) as {
            id: number;
            nombre: string;
            apellido: string;
            foto_url: string | null;
          } | undefined;
          if (p) {
            insNom.run(votId, `${p.nombre} ${p.apellido}`, p.foto_url, idx + 1, p.id);
          }
        });

        logAdminActivity(user.id, user.username, 'motm.create', `Opened MOTM poll #${votId} for match #${juego_id}`);
        break;
      }

      case 'motm.close': {
        if (!canAccess(user.role, 'motm')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id);
        const topNom = db.prepare(`
          SELECT n.id, n.roster_id, COUNT(v.id) as votes
          FROM motm_nominees n
          LEFT JOIN motm_votes v ON v.nominee_id = n.id
          WHERE n.votacion_id = ?
          GROUP BY n.id
          ORDER BY votes DESC, n.orden ASC
          LIMIT 1
        `).get(id) as { id: number; roster_id: number | null; votes: number } | undefined;

        db.prepare(`UPDATE motm_votaciones SET status = 'closed', winner_nominee_id = ? WHERE id = ?`).run(
          topNom?.id ?? null,
          id
        );

        if (topNom?.roster_id) {
          db.prepare('UPDATE roster_estadisticas SET motm = motm + 1 WHERE roster_id = ?').run(topNom.roster_id);
        }
        logAdminActivity(user.id, user.username, 'motm.close', `Closed MOTM poll #${id}`);
        break;
      }

      case 'motm.delete': {
        if (!canAccess(user.role, 'motm')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id);
        db.prepare('DELETE FROM motm_votaciones WHERE id = ?').run(id);
        logAdminActivity(user.id, user.username, 'motm.delete', `Deleted MOTM poll #${id}`);
        break;
      }

      // ── STAR OF THE MONTH ────────────────────────────────────────
      case 'star.save': {
        if (!canAccess(user.role, 'jugador_mes')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const nombre = String(payload.nombre || '').trim();
        const categoria = String(payload.categoria || 'U10').trim();
        const dorsal = payload.dorsal ? Number(payload.dorsal) : null;
        const foto_url = String(payload.foto_url || '').trim() || null;
        const descripcion_logro = String(payload.descripcion_logro || '').trim();
        const mes = String(payload.mes || 'Current Month').trim();

        const existing = db.prepare('SELECT id FROM jugador_mes ORDER BY id DESC LIMIT 1').get() as { id: number } | undefined;
        if (existing) {
          db.prepare(`
            UPDATE jugador_mes
            SET nombre=?, categoria=?, dorsal=?, foto_url=?, descripcion_logro=?, mes=?
            WHERE id=?
          `).run(nombre, categoria, dorsal, foto_url, descripcion_logro, mes, existing.id);
        } else {
          db.prepare(`
            INSERT INTO jugador_mes (nombre, categoria, dorsal, foto_url, descripcion_logro, mes)
            VALUES (?, ?, ?, ?, ?, ?)
          `).run(nombre, categoria, dorsal, foto_url, descripcion_logro, mes);
        }
        logAdminActivity(user.id, user.username, 'star.save', `Updated Star of the Month: ${nombre}`);
        break;
      }

      // ── SEDES & CANCHAS ──────────────────────────────────────────
      case 'sede.save': {
        if (!canAccess(user.role, 'sedes')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id || 0);
        const nombre = String(payload.nombre || '').trim();
        const direccion = String(payload.direccion || '').trim();
        const mapa_general_url = String(payload.mapa_general_url || '').trim() || null;
        const nota_acceso = String(payload.nota_acceso || '').trim() || null;

        if (id > 0) {
          db.prepare('UPDATE sedes SET nombre=?, direccion=?, mapa_general_url=?, nota_acceso=? WHERE id=?').run(
            nombre,
            direccion,
            mapa_general_url,
            nota_acceso,
            id
          );
        } else {
          db.prepare('INSERT INTO sedes (nombre, direccion, mapa_general_url, nota_acceso) VALUES (?, ?, ?, ?)').run(
            nombre,
            direccion,
            mapa_general_url,
            nota_acceso
          );
        }
        logAdminActivity(user.id, user.username, 'sede.save', nombre);
        break;
      }

      case 'sede.delete': {
        if (!canAccess(user.role, 'sedes')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        db.prepare('DELETE FROM sedes WHERE id = ?').run(Number(payload.id));
        logAdminActivity(user.id, user.username, 'sede.delete', `Deleted sede #${payload.id}`);
        break;
      }

      case 'cancha.save': {
        if (!canAccess(user.role, 'sedes')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id || 0);
        const sede_id = Number(payload.sede_id);
        const numero_cancha = String(payload.numero_cancha || '').trim();
        const sobrenombre = String(payload.sobrenombre || '').trim() || null;
        const indicaciones_extra = String(payload.indicaciones_extra || '').trim() || null;
        const mapa_url = String(payload.mapa_url || '').trim() || null;

        if (id > 0) {
          db.prepare(
            'UPDATE canchas SET sede_id=?, numero_cancha=?, sobrenombre=?, indicaciones_extra=?, mapa_url=? WHERE id=?'
          ).run(sede_id, numero_cancha, sobrenombre, indicaciones_extra, mapa_url, id);
        } else {
          db.prepare(
            'INSERT INTO canchas (sede_id, numero_cancha, sobrenombre, indicaciones_extra, mapa_url) VALUES (?, ?, ?, ?, ?)'
          ).run(sede_id, numero_cancha, sobrenombre, indicaciones_extra, mapa_url);
        }
        logAdminActivity(user.id, user.username, 'cancha.save', numero_cancha);
        break;
      }

      case 'cancha.delete': {
        if (!canAccess(user.role, 'sedes')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        db.prepare('DELETE FROM canchas WHERE id = ?').run(Number(payload.id));
        break;
      }

      // ── CATEGORIAS ───────────────────────────────────────────────
      case 'categoria.save': {
        if (!canAccess(user.role, 'categorias')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id || 0);
        const nombre = String(payload.nombre || '').trim();
        const horarios_entrenamiento = String(payload.horarios_entrenamiento || '').trim();

        if (id > 0) {
          db.prepare('UPDATE categorias SET nombre=?, horarios_entrenamiento=? WHERE id=?').run(
            nombre,
            horarios_entrenamiento,
            id
          );
        } else {
          db.prepare('INSERT INTO categorias (nombre, horarios_entrenamiento) VALUES (?, ?)').run(
            nombre,
            horarios_entrenamiento
          );
        }
        logAdminActivity(user.id, user.username, 'categoria.save', nombre);
        break;
      }

      case 'categoria.delete': {
        if (!canAccess(user.role, 'categorias')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        db.prepare('DELETE FROM categorias WHERE id = ?').run(Number(payload.id));
        break;
      }

      // ── HERO SLIDER & REELS ──────────────────────────────────────
      case 'hero.save': {
        if (!canAccess(user.role, 'hero_slider')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id || 0);
        const image_url = String(payload.image_url || '').trim();
        const title = String(payload.title || '').trim();
        const subtitle = String(payload.subtitle || '').trim() || null;
        const button_text = String(payload.button_text || 'Read More').trim();
        const button_url = String(payload.button_url || '').trim() || null;
        const orden = Number(payload.orden || 0);
        const activo = payload.activo === undefined ? 1 : Number(payload.activo ? 1 : 0);

        if (id > 0) {
          db.prepare(
            'UPDATE hero_slides SET image_url=?, title=?, subtitle=?, button_text=?, button_url=?, orden=?, activo=? WHERE id=?'
          ).run(image_url, title, subtitle, button_text, button_url, orden, activo, id);
        } else {
          db.prepare(
            'INSERT INTO hero_slides (image_url, title, subtitle, button_text, button_url, orden, activo) VALUES (?, ?, ?, ?, ?, ?, ?)'
          ).run(image_url, title, subtitle, button_text, button_url, orden, activo);
        }
        logAdminActivity(user.id, user.username, 'hero.save', title);
        break;
      }

      case 'hero.delete': {
        if (!canAccess(user.role, 'hero_slider')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        db.prepare('DELETE FROM hero_slides WHERE id = ?').run(Number(payload.id));
        break;
      }

      case 'reel.save': {
        if (!canAccess(user.role, 'match_reels')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id || 0);
        const video_url = String(payload.video_url || '').trim();
        const caption = String(payload.caption || '').trim() || null;
        const orden = Number(payload.orden || 0);

        if (id > 0) {
          db.prepare('UPDATE match_reels SET video_url=?, caption=?, orden=? WHERE id=?').run(
            video_url,
            caption,
            orden,
            id
          );
        } else {
          db.prepare('INSERT INTO match_reels (video_url, caption, orden) VALUES (?, ?, ?)').run(
            video_url,
            caption,
            orden
          );
        }
        logAdminActivity(user.id, user.username, 'reel.save', caption || video_url);
        break;
      }

      case 'reel.delete': {
        if (!canAccess(user.role, 'match_reels')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        db.prepare('DELETE FROM match_reels WHERE id = ?').run(Number(payload.id));
        break;
      }

      // ── NOTICIAS / BLOG ──────────────────────────────────────────
      case 'noticia.save': {
        if (!canAccess(user.role, 'noticias')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const id = Number(payload.id || 0);
        const titulo = String(payload.titulo || '').trim();
        const rawSlug = String(payload.slug || titulo)
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/(^-|-$)/g, '');
        const slug = rawSlug || `news-${Date.now()}`;
        const categoria_id = payload.categoria_id ? Number(payload.categoria_id) : null;
        const resumen = String(payload.resumen || '').trim() || null;
        const contenido = String(payload.contenido || '').trim();
        const imagen_destacada = String(payload.imagen_destacada || '').trim() || null;
        const autor = String(payload.autor || 'VCF Academy Houston').trim();
        const publicado = payload.publicado === undefined ? 1 : Number(payload.publicado ? 1 : 0);
        const destacada = payload.destacada ? 1 : 0;

        if (id > 0) {
          db.prepare(`
            UPDATE noticias
            SET titulo=?, slug=?, categoria_id=?, resumen=?, contenido=?, imagen_destacada=?,
                autor=?, publicado=?, destacada=?, updated_at=datetime('now')
            WHERE id=?
          `).run(titulo, slug, categoria_id, resumen, contenido, imagen_destacada, autor, publicado, destacada, id);
        } else {
          db.prepare(`
            INSERT INTO noticias (titulo, slug, categoria_id, resumen, contenido, imagen_destacada, autor, publicado, destacada)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
          `).run(titulo, slug, categoria_id, resumen, contenido, imagen_destacada, autor, publicado, destacada);
        }
        logAdminActivity(user.id, user.username, 'noticia.save', titulo);
        break;
      }

      case 'noticia.delete': {
        if (!canAccess(user.role, 'noticias')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        db.prepare('DELETE FROM noticias WHERE id = ?').run(Number(payload.id));
        logAdminActivity(user.id, user.username, 'noticia.delete', `Deleted article #${payload.id}`);
        break;
      }

      // ── INSCRIPCIONES & CONTACT MESSAGES ─────────────────────────
      case 'inscripcion.status': {
        if (!canAccess(user.role, 'inscripciones_view')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        db.prepare('UPDATE inscripciones SET estado = ? WHERE id = ?').run(
          String(payload.estado || 'contactado'),
          Number(payload.id)
        );
        break;
      }

      case 'inscripcion.delete': {
        if (!canAccess(user.role, 'inscripciones_view')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        db.prepare('DELETE FROM inscripciones WHERE id = ?').run(Number(payload.id));
        break;
      }

      case 'contact.read': {
        if (!canAccess(user.role, 'contact_messages_view')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        db.prepare('UPDATE contact_messages SET leido = 1 WHERE id = ?').run(Number(payload.id));
        break;
      }

      case 'contact.delete': {
        if (!canAccess(user.role, 'contact_messages_view')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        db.prepare('DELETE FROM contact_messages WHERE id = ?').run(Number(payload.id));
        break;
      }

      // ── SITE SETTINGS & DONATIONS ────────────────────────────────
      case 'settings.save': {
        if (!canAccess(user.role, 'support_donations')) {
          return NextResponse.json({ ok: false, error: 'Permission denied' }, { status: 403 });
        }
        const upsert = db.prepare(`
          INSERT INTO site_settings (key, value) VALUES (?, ?)
          ON CONFLICT(key) DO UPDATE SET value = excluded.value
        `);
        for (const [k, v] of Object.entries(payload)) {
          if (typeof v === 'string') {
            upsert.run(k, v.trim());
          }
        }
        logAdminActivity(user.id, user.username, 'settings.save', 'Updated site & support settings');
        break;
      }

      // ── USERS & PASSWORD ─────────────────────────────────────────
      case 'user.create': {
        if (user.role !== 'super_admin') {
          return NextResponse.json({ ok: false, error: 'Only Super Admin can manage users' }, { status: 403 });
        }
        const username = String(payload.username || '').trim();
        const password = String(payload.password || '');
        const role = String(payload.role || 'editor_coach');
        const email = String(payload.email || '').trim() || null;

        if (!username || password.length < 6) {
          return NextResponse.json(
            { ok: false, error: 'Username and password (min 6 chars) are required.' },
            { status: 400 }
          );
        }
        const hash = bcrypt.hashSync(password, 10);
        db.prepare('INSERT INTO admin_users (username, password_hash, role, email) VALUES (?, ?, ?, ?)').run(
          username,
          hash,
          role,
          email
        );
        logAdminActivity(user.id, user.username, 'user.create', `Created user ${username} (${role})`);
        break;
      }

      case 'user.delete': {
        if (user.role !== 'super_admin') {
          return NextResponse.json({ ok: false, error: 'Only Super Admin can delete users' }, { status: 403 });
        }
        const targetId = Number(payload.id);
        if (targetId === user.id) {
          return NextResponse.json({ ok: false, error: 'You cannot delete your own account.' }, { status: 400 });
        }
        db.prepare('DELETE FROM admin_users WHERE id = ?').run(targetId);
        logAdminActivity(user.id, user.username, 'user.delete', `Deleted user ID ${targetId}`);
        break;
      }

      case 'password.change': {
        const newPassword = String(payload.new_password || '');
        if (newPassword.length < 6) {
          return NextResponse.json(
            { ok: false, error: 'New password must be at least 6 characters.' },
            { status: 400 }
          );
        }
        const hash = bcrypt.hashSync(newPassword, 10);
        db.prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?').run(hash, user.id);
        logAdminActivity(user.id, user.username, 'password.change', 'Changed own password');
        break;
      }

      default:
        return NextResponse.json({ ok: false, error: `Unknown action: ${action}` }, { status: 400 });
    }

    // Revalidate public Next.js routes so changes appear immediately
    revalidatePath('/');
    revalidatePath('/news');
    revalidatePath('/calendar');
    revalidatePath('/join');
    revalidatePath('/contact');
    revalidatePath('/support');

    return NextResponse.json({ ok: true });
  } catch (err) {
    console.error('Admin action error:', err);
    return NextResponse.json(
      { ok: false, error: err instanceof Error ? err.message : 'Operation failed' },
      { status: 500 }
    );
  }
}
