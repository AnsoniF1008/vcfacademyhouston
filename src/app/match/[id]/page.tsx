import React from 'react';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import { getDb } from '@/lib/db';
import { getSiteSettings } from '@/lib/queries';
import { getSessionUser } from '@/lib/auth';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';
import { Calendar, MapPin, Trophy, ArrowLeft, Download, ExternalLink, Goal } from 'lucide-react';

export const dynamic = 'force-dynamic';

export default async function MatchDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const matchId = Number(id);
  if (!matchId || matchId <= 0) notFound();

  const user = await getSessionUser();
  const settings = getSiteSettings();
  const db = getDb();

  const match = db.prepare(`
    SELECT j.id, j.fecha, j.hora, j.rival, j.rival_logo_url, j.cancha, j.ubicacion_mapa_url,
           j.goles_vcf, j.goles_rival, j.estado, j.categoria,
           t.nombre_torneo, t.temporada,
           s.nombre AS sede_nombre, s.mapa_general_url
    FROM juegos j
    JOIN torneos_info t ON t.id = j.torneo_id
    LEFT JOIN sedes s ON s.id = j.sede_id
    WHERE j.id = ?
  `).get(matchId) as Record<string, unknown> | undefined;

  if (!match) notFound();

  const scorers = db.prepare(`
    SELECT g.id, g.goles, g.asistencias, g.minuto, g.tipo,
           r.nombre, r.apellido, r.dorsal
    FROM juego_goles g
    LEFT JOIN roster r ON r.id = g.roster_id
    WHERE g.juego_id = ?
    ORDER BY COALESCE(g.minuto, 99) ASC, g.goles DESC
  `).all(matchId) as {
    id: number;
    goles: number;
    asistencias: number;
    minuto: number | null;
    tipo: string | null;
    nombre: string | null;
    apellido: string | null;
    dorsal: number | null;
  }[];

  const motm = db.prepare(`
    SELECT mn.nombre, mn.foto_url
    FROM motm_votaciones mv
    JOIN motm_nominees mn ON mn.id = mv.winner_nominee_id
    WHERE mv.juego_id = ? AND mv.winner_nominee_id IS NOT NULL
    LIMIT 1
  `).get(matchId) as { nombre: string; foto_url: string | null } | undefined;

  const hasScore = match.goles_vcf !== null && match.goles_rival !== null;
  const gv = Number(match.goles_vcf ?? 0);
  const gr = Number(match.goles_rival ?? 0);
  const outcome = hasScore ? (gv > gr ? 'WIN' : gv < gr ? 'LOSS' : 'DRAW') : 'UPCOMING';
  const venue = [match.sede_nombre, match.cancha].filter(Boolean).join(' · ');
  const mapUrl = String(match.ubicacion_mapa_url || match.mapa_general_url || '');

  // Google Calendar URL
  const timePart = match.hora ? `${String(match.hora)}:00`.slice(0, 8) : '12:00:00';
  const startDt = new Date(`${String(match.fecha)}T${timePart}`);
  const endDt = new Date(startDt.getTime() + 90 * 60 * 1000);
  const fmtGoogle = (d: Date) =>
    isNaN(d.getTime()) ? '' : d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
  const googleCalUrl = `https://www.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(
    `VCF Academy Houston vs ${String(match.rival || 'TBD')}`
  )}&dates=${fmtGoogle(startDt)}/${fmtGoogle(endDt)}&location=${encodeURIComponent(venue)}`;

  return (
    <>
      <Navbar isAdminLoggedIn={Boolean(user)} />

      <main className="flex-1 py-12 bg-vcf-dark">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 space-y-6">
          {/* Breadcrumb */}
          <div className="flex items-center justify-between text-xs text-gray-400">
            <Link
              href="/#tournaments"
              className="inline-flex items-center gap-1.5 hover:text-vcf-orange transition-colors font-semibold"
            >
              <ArrowLeft className="w-3.5 h-3.5" />
              Back to Tournaments &amp; Schedule
            </Link>
            <span className="uppercase tracking-widest text-vcf-orange font-bold">
              Match #{matchId}
            </span>
          </div>

          {/* Scoreboard Banner */}
          <div className="rounded-3xl bg-vcf-surface border border-vcf-border p-8 sm:p-10 shadow-2xl relative overflow-hidden">
            <div className="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-vcf-orange via-vcf-gold to-vcf-orange" />

            <div className="text-center mb-6">
              <span className="px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs font-bold uppercase tracking-widest text-vcf-orange">
                {String(match.nombre_torneo)}
                {match.temporada ? ` · ${String(match.temporada)}` : ''}
              </span>
            </div>

            <div className="grid grid-cols-3 items-center gap-4 text-center my-6">
              {/* VCF */}
              <div className="flex flex-col items-center space-y-2">
                <img
                  src="/assets/img/vcf-crest.png"
                  alt="VCF Houston"
                  className="w-16 h-20 sm:w-20 sm:h-24 object-contain drop-shadow-[0_4px_12px_rgba(255,102,0,0.3)]"
                />
                <div className="font-display text-lg sm:text-2xl font-bold uppercase text-white">
                  VCF Houston
                </div>
                <span className="text-[11px] text-gray-400">{String(match.categoria || 'U10')}</span>
              </div>

              {/* Score / VS */}
              <div className="flex flex-col items-center justify-center">
                {hasScore ? (
                  <>
                    <div className="font-display text-5xl sm:text-6xl font-bold text-white tracking-wider">
                      <span>{gv}</span>
                      <span className="text-gray-600 mx-2">–</span>
                      <span>{gr}</span>
                    </div>
                    <span
                      className={`mt-3 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest ${
                        outcome === 'WIN'
                          ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40'
                          : outcome === 'LOSS'
                          ? 'bg-red-500/20 text-red-400 border border-red-500/40'
                          : 'bg-white/10 text-gray-300'
                      }`}
                    >
                      {outcome}
                    </span>
                  </>
                ) : (
                  <>
                    <div className="font-display text-3xl sm:text-4xl font-bold text-vcf-orange">
                      VS
                    </div>
                    <span className="mt-2 px-3 py-1 rounded-full bg-vcf-orange/15 border border-vcf-orange/30 text-xs font-bold uppercase text-vcf-orange">
                      Upcoming Match
                    </span>
                  </>
                )}
                <div className="text-xs text-gray-400 mt-3 font-medium">
                  {String(match.fecha)} {match.hora ? `· ${String(match.hora)}` : ''}
                </div>
              </div>

              {/* Rival */}
              <div className="flex flex-col items-center space-y-2">
                <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-vcf-card border border-vcf-border flex items-center justify-center font-display text-2xl font-bold text-gray-300">
                  {String(match.rival || 'TBD').slice(0, 2).toUpperCase()}
                </div>
                <div className="font-display text-lg sm:text-2xl font-bold uppercase text-white">
                  {String(match.rival || 'TBD')}
                </div>
                <span className="text-[11px] text-gray-400">Opponent</span>
              </div>
            </div>

            {/* Venue & Map Bar */}
            <div className="mt-8 pt-6 border-t border-white/10 flex flex-wrap items-center justify-between gap-4 text-xs text-gray-300">
              <div className="flex items-center gap-2">
                <MapPin className="w-4 h-4 text-vcf-orange" />
                <span>{venue || 'Venue TBD'}</span>
              </div>
              {mapUrl && (
                <a
                  href={mapUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-1.5 text-vcf-orange font-bold hover:underline"
                >
                  <span>Open GPS Directions</span>
                  <ExternalLink className="w-3.5 h-3.5" />
                </a>
              )}
            </div>
          </div>

          {/* Goal Scorers & Assists */}
          {scorers.length > 0 && (
            <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
              <h2 className="font-display text-lg font-bold uppercase tracking-wider text-vcf-orange flex items-center gap-2">
                <Goal className="w-5 h-5" />
                Match Sheet · Goals &amp; Assists
              </h2>
              <div className="divide-y divide-white/5">
                {scorers.map((sc) => (
                  <div key={sc.id} className="py-3 flex items-center justify-between text-sm">
                    <div className="flex items-center gap-3">
                      <span className="w-8 h-8 rounded-lg bg-vcf-orange/15 border border-vcf-orange/30 font-display font-bold text-xs text-vcf-orange flex items-center justify-center">
                        #{sc.dorsal ?? '–'}
                      </span>
                      <div>
                        <span className="font-bold text-white">
                          {sc.nombre} {sc.apellido}
                        </span>
                        {sc.minuto && (
                          <span className="ml-2 text-xs text-gray-400">{sc.minuto}&apos;</span>
                        )}
                      </div>
                    </div>
                    <div className="flex items-center gap-2 text-xs font-bold">
                      {sc.goles > 0 && (
                        <span className="px-2.5 py-1 rounded-md bg-emerald-500/15 text-emerald-300">
                          ⚽ {sc.goles} {sc.goles === 1 ? 'Goal' : 'Goals'}
                        </span>
                      )}
                      {sc.asistencias > 0 && (
                        <span className="px-2.5 py-1 rounded-md bg-sky-500/15 text-sky-300">
                          🎯 {sc.asistencias} {sc.asistencias === 1 ? 'Assist' : 'Assists'}
                        </span>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Man of the Match Winner */}
          {motm && (
            <div className="rounded-2xl bg-gradient-to-r from-vcf-card to-[#22170D] border border-vcf-gold/40 p-6 flex items-center justify-between gap-4">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-xl bg-vcf-gold/20 border border-vcf-gold flex items-center justify-center text-vcf-gold">
                  <Trophy className="w-6 h-6" />
                </div>
                <div>
                  <div className="text-[11px] font-bold uppercase tracking-widest text-vcf-gold">
                    Official Man of the Match
                  </div>
                  <div className="font-display text-2xl font-bold uppercase text-white">
                    {motm.nombre}
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Calendar Export Actions */}
          <div className="flex flex-wrap items-center gap-3 pt-2">
            <a
              href={googleCalUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="px-5 py-3 rounded-xl bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-xs uppercase tracking-wider flex items-center gap-2"
            >
              <Calendar className="w-4 h-4" />
              Add to Google Calendar
            </a>
            <a
              href={`/api/public/ics/${matchId}`}
              className="px-5 py-3 rounded-xl bg-vcf-surface hover:bg-white/10 border border-vcf-border text-white font-display font-bold text-xs uppercase tracking-wider flex items-center gap-2"
            >
              <Download className="w-4 h-4 text-vcf-orange" />
              Download .ICS (Apple / Outlook)
            </a>
            <Link
              href="/calendar"
              className="px-5 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-gray-300 text-xs font-semibold"
            >
              Full Monthly Calendar
            </Link>
          </div>
        </div>
      </main>

      <Footer settings={settings} />
    </>
  );
}
