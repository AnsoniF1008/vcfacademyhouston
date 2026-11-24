import React from 'react';
import Link from 'next/link';
import {
  getHeroSlides,
  getAllMatches,
  getSeasonStats,
  getMotmData,
  getCategorias,
  getSedesWithCanchas,
  getActiveRoster,
  getStarOfTheMonth,
  getLatestNews,
  getMatchReels,
  getSiteSettings,
} from '@/lib/queries';
import { getSessionUser } from '@/lib/auth';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';
import { HeroSection } from '@/components/HeroSection';
import { MotmWidget } from '@/components/MotmWidget';
import { FormationViewer } from '@/components/FormationViewer';
import { GroundsAccordion } from '@/components/GroundsAccordion';
import { RosterSection } from '@/components/RosterSection';
import { ReelsCarousel } from '@/components/ReelsCarousel';
import {
  Trophy,
  Calendar,
  MapPin,
  ArrowRight,
  Clock,
  Star,
  Newspaper,
  CheckCircle2,
} from 'lucide-react';

export const dynamic = 'force-dynamic';

export default async function HomePage() {
  const user = await getSessionUser();
  const slides = getHeroSlides();
  const matches = getAllMatches();
  const seasonStats = getSeasonStats();
  const motmData = getMotmData();
  const categorias = getCategorias();
  const sedes = getSedesWithCanchas();
  const roster = getActiveRoster();
  const star = getStarOfTheMonth();
  const news = getLatestNews(3);
  const reels = getMatchReels();
  const settings = getSiteSettings();

  const today = new Date().toISOString().slice(0, 10);
  const upcomingMatches = matches.filter(
    (m) => m.fecha >= today && m.goles_vcf === null
  );
  const nextMatch = upcomingMatches[0] || null;

  // Group matches by tournament
  const tournamentsMap = new Map<
    number,
    {
      id: number;
      nombre_torneo: string;
      temporada: string | null;
      archivado: number;
      matches: typeof matches;
      isActive: boolean;
    }
  >();

  for (const m of matches) {
    if (!tournamentsMap.has(m.torneo_id)) {
      tournamentsMap.set(m.torneo_id, {
        id: m.torneo_id,
        nombre_torneo: m.nombre_torneo,
        temporada: m.temporada,
        archivado: m.archivado,
        matches: [],
        isActive: false,
      });
    }
    const t = tournamentsMap.get(m.torneo_id)!;
    t.matches.push(m);
    if (m.fecha >= today && m.archivado === 0) {
      t.isActive = true;
    }
  }

  const allTournaments = Array.from(tournamentsMap.values());
  const activeTournaments = allTournaments.filter((t) => t.isActive);
  const pastTournaments = allTournaments.filter((t) => !t.isActive);

  return (
    <>
      <Navbar isAdminLoggedIn={Boolean(user)} />

      <main className="flex-1">
        {/* 1. Hero + Next Match Countdown */}
        <HeroSection slides={slides} nextMatch={nextMatch} />

        {/* 2. Season Stats Strip */}
        <div className="bg-vcf-surface border-b border-vcf-border">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 py-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <span className="px-3 py-1 rounded-lg bg-vcf-orange/15 border border-vcf-orange/30 font-display font-bold text-xs uppercase tracking-wider text-vcf-orange">
                Season 2026 Record
              </span>
              <span className="text-xs text-gray-400 font-medium">
                VCF Academy Houston · Official Match Statistics
              </span>
            </div>

            <div className="grid grid-cols-3 sm:grid-cols-7 gap-3 sm:gap-5">
              {[
                { label: 'Played', val: seasonStats.played, color: 'text-white' },
                { label: 'Wins', val: seasonStats.W, color: 'text-emerald-400' },
                { label: 'Draws', val: seasonStats.D, color: 'text-gray-300' },
                { label: 'Losses', val: seasonStats.L, color: 'text-red-400' },
                { label: 'Goals For', val: seasonStats.GF, color: 'text-white' },
                { label: 'Clean Sheets', val: seasonStats.CS, color: 'text-sky-400' },
                { label: 'Points', val: seasonStats.PTS, color: 'text-vcf-orange' },
              ].map((st) => (
                <div
                  key={st.label}
                  className="px-3 py-2 rounded-xl bg-vcf-card border border-vcf-border text-center"
                >
                  <div className={`font-display text-xl sm:text-2xl font-bold ${st.color}`}>
                    {st.val}
                  </div>
                  <div className="text-[10px] uppercase tracking-wider text-gray-400">
                    {st.label}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* 3. Live Man of the Match Widget */}
        <MotmWidget initialOpen={motmData.open} initialWinner={motmData.winner} />

        {/* 4. Methodology & Training Schedules */}
        <section id="methodology" className="py-16 bg-vcf-surface border-b border-vcf-border">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 space-y-12">
            <div className="max-w-3xl">
              <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1">
                Paterna Philosophy in Texas
              </div>
              <h2 className="font-display text-3xl sm:text-4xl font-bold uppercase text-white">
                Our <span className="text-vcf-orange italic">Methodology</span>
              </h2>
              <p className="text-sm sm:text-base text-gray-300 mt-2 leading-relaxed">
                <em className="text-vcf-orange font-semibold">
                  &ldquo;Educating People, Training Footballers&rdquo;
                </em>{' '}
                — The three official Valencia CF Academy pillars that guide every training session, every matchday, and every young player in Houston.
              </p>
            </div>

            {/* 3 Pillars */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {[
                {
                  num: '01',
                  icon: '🦇',
                  title: 'Identity',
                  desc: 'Building the Valencia CF identity in every young player — values, respect, humility, camaraderie, and pride in the crest on and off the pitch.',
                },
                {
                  num: '02',
                  icon: '💪',
                  title: 'Effort',
                  desc: 'No shortcuts. Intensity, discipline, and dedication in every drill, every matchday, every sprint, and every recovery. That is the VCF standard.',
                },
                {
                  num: '03',
                  icon: '🧠',
                  title: 'Intelligence',
                  desc: 'Tactical awareness and smart autonomous decision-making — scanning the field, understanding space, and anticipating play before it happens.',
                },
              ].map((pillar) => (
                <div
                  key={pillar.num}
                  className="relative rounded-2xl bg-vcf-card border border-vcf-border hover:border-vcf-orange/50 p-6 transition-all group"
                >
                  <span className="absolute top-4 right-5 font-display text-4xl font-bold text-white/5 group-hover:text-vcf-orange/15 transition-colors">
                    {pillar.num}
                  </span>
                  <div className="text-3xl mb-4">{pillar.icon}</div>
                  <h3 className="font-display text-xl font-bold uppercase text-white mb-2">
                    {pillar.title}
                  </h3>
                  <p className="text-xs sm:text-sm text-gray-400 leading-relaxed">
                    {pillar.desc}
                  </p>
                </div>
              ))}
            </div>

            {/* Training Schedules by Category */}
            <div>
              <div className="flex items-center justify-between mb-4">
                <h3 className="font-display text-lg font-bold uppercase tracking-wider text-white flex items-center gap-2">
                  <Clock className="w-4 h-4 text-vcf-orange" />
                  Official Training Schedules by Age Category
                </h3>
                <Link
                  href="/join"
                  className="text-xs font-bold uppercase tracking-wider text-vcf-orange hover:underline"
                >
                  Register for Tryouts &rarr;
                </Link>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {categorias.map((cat) => (
                  <div
                    key={cat.id}
                    className="rounded-xl bg-vcf-card border border-vcf-border p-4 flex flex-col justify-between"
                  >
                    <div>
                      <span className="inline-block px-2.5 py-0.5 rounded-md bg-vcf-orange text-black font-display font-bold text-sm uppercase">
                        {cat.nombre}
                      </span>
                      <p className="text-xs text-gray-300 mt-3 font-medium leading-relaxed">
                        {cat.horarios_entrenamiento || 'Schedule TBD'}
                      </p>
                    </div>
                    <div className="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-[11px] text-gray-400">
                      <span>B13 Sports Complex</span>
                      <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" />
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>

        {/* 5. Interactive Tactical Pitch ("Know Your Role") */}
        <FormationViewer players={roster} />

        {/* 6. Training Grounds Accordion */}
        <GroundsAccordion sedes={sedes} />

        {/* 7. Squad Roster & FIFA Player Cards */}
        <RosterSection players={roster} starDorsal={star?.dorsal} />

        {/* 8. Tournaments & Matchday Schedule */}
        <section id="tournaments" className="py-16 bg-vcf-surface border-b border-vcf-border">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 space-y-10">
            <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
              <div>
                <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1">
                  Fixtures &amp; Standings
                </div>
                <h2 className="font-display text-3xl sm:text-4xl font-bold uppercase text-white">
                  Tournaments &amp; <span className="text-vcf-orange italic">Matchday Center</span>
                </h2>
                <p className="text-xs sm:text-sm text-gray-400 mt-1">
                  Track our teams across Houston’s premier leagues. Click any match for full scorers, GPS venue, and calendar export.
                </p>
              </div>
              <Link
                href="/calendar"
                className="px-4 py-2.5 rounded-xl bg-vcf-card hover:bg-vcf-orange hover:text-black border border-vcf-border font-display font-bold text-xs uppercase tracking-wider text-white flex items-center gap-2 self-start sm:self-auto transition-all"
              >
                <Calendar className="w-4 h-4" />
                Open Monthly Calendar
              </Link>
            </div>

            {/* Active Tournaments */}
            <div className="space-y-6">
              {activeTournaments.map((torneo) => (
                <div
                  key={torneo.id}
                  className="rounded-2xl bg-vcf-card border border-vcf-border overflow-hidden"
                >
                  <div className="px-6 py-4 bg-black/40 border-b border-vcf-border flex flex-wrap items-center justify-between gap-2">
                    <div className="flex items-center gap-3">
                      <span className="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse" />
                      <h3 className="font-display text-xl font-bold uppercase text-white">
                        {torneo.nombre_torneo}
                      </h3>
                      {torneo.temporada && (
                        <span className="text-xs text-gray-400">· {torneo.temporada}</span>
                      )}
                    </div>
                    <span className="px-2.5 py-0.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-[11px] font-bold uppercase text-emerald-400">
                      Active Competition
                    </span>
                  </div>

                  <div className="divide-y divide-white/5">
                    {torneo.matches.map((m) => {
                      const hasScore = m.goles_vcf !== null && m.goles_rival !== null;
                      const isWin = hasScore && Number(m.goles_vcf) > Number(m.goles_rival);
                      const isLoss = hasScore && Number(m.goles_vcf) < Number(m.goles_rival);

                      return (
                        <div
                          key={m.id}
                          className="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-white/[0.02] transition-colors"
                        >
                          <div className="flex items-center gap-4">
                            <div className="w-24 shrink-0">
                              <div className="text-xs font-bold text-white">{m.fecha}</div>
                              <div className="text-[11px] text-gray-400">{m.hora || 'TBD'}</div>
                            </div>
                            <div>
                              <div className="font-display text-base sm:text-lg font-bold uppercase text-white flex items-center gap-2">
                                <span>VCF Houston</span>
                                <span className="text-vcf-orange text-xs">vs</span>
                                <span>{m.rival || 'TBD'}</span>
                              </div>
                              <div className="text-xs text-gray-400 flex items-center gap-2 mt-0.5">
                                <span>{m.categoria || 'U10'}</span>
                                {(m.sede_nombre || m.cancha) && (
                                  <>
                                    <span>·</span>
                                    <span className="flex items-center gap-1">
                                      <MapPin className="w-3 h-3 text-vcf-orange" />
                                      {[m.sede_nombre, m.cancha].filter(Boolean).join(' - ')}
                                    </span>
                                  </>
                                )}
                              </div>
                            </div>
                          </div>

                          <div className="flex items-center gap-3 self-end sm:self-auto">
                            {hasScore ? (
                              <span
                                className={`px-3 py-1 rounded-lg font-display font-bold text-sm ${
                                  isWin
                                    ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                                    : isLoss
                                    ? 'bg-red-500/20 text-red-300 border border-red-500/30'
                                    : 'bg-white/10 text-gray-200 border border-white/20'
                                }`}
                              >
                                {m.goles_vcf} – {m.goles_rival}{' '}
                                <span className="text-[10px] ml-1">
                                  ({isWin ? 'W' : isLoss ? 'L' : 'D'})
                                </span>
                              </span>
                            ) : (
                              <span className="px-3 py-1 rounded-lg bg-vcf-orange/15 border border-vcf-orange/30 text-vcf-orange text-xs font-bold uppercase">
                                Upcoming
                              </span>
                            )}
                            <Link
                              href={`/match/${m.id}`}
                              className="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-vcf-orange hover:text-black text-xs font-semibold text-gray-200 transition-colors"
                            >
                              Details &rarr;
                            </Link>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>

            {/* Past / Completed Tournaments */}
            {pastTournaments.length > 0 && (
              <div className="pt-4">
                <h3 className="font-display text-lg font-bold uppercase tracking-wider text-gray-300 mb-4 flex items-center gap-2">
                  <Trophy className="w-4 h-4 text-vcf-gold" />
                  Completed &amp; Archived Tournaments
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {pastTournaments.map((torneo) => (
                    <div
                      key={torneo.id}
                      className="rounded-2xl bg-vcf-card border border-vcf-border p-5 space-y-3"
                    >
                      <div className="flex items-center justify-between">
                        <div>
                          <h4 className="font-display text-lg font-bold uppercase text-white">
                            {torneo.nombre_torneo}
                          </h4>
                          <p className="text-xs text-gray-400">{torneo.temporada}</p>
                        </div>
                        <span className="px-2.5 py-0.5 rounded bg-white/5 text-[10px] font-bold uppercase text-gray-400">
                          Completed
                        </span>
                      </div>
                      <div className="space-y-2 pt-2 border-t border-white/10">
                        {torneo.matches.map((m) => (
                          <Link
                            key={m.id}
                            href={`/match/${m.id}`}
                            className="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-lg hover:bg-white/5 transition-colors"
                          >
                            <span className="text-gray-300">
                              {m.fecha} · vs <strong className="text-white">{m.rival}</strong>
                            </span>
                            <span className="font-display font-bold text-vcf-orange">
                              {m.goles_vcf ?? '–'} : {m.goles_rival ?? '–'}
                            </span>
                          </Link>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </section>

        {/* 9. Latest News Section */}
        {news.length > 0 && (
          <section id="news" className="py-16 bg-vcf-dark border-b border-vcf-border">
            <div className="max-w-7xl mx-auto px-4 sm:px-6">
              <div className="flex items-end justify-between gap-4 mb-8">
                <div>
                  <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1 flex items-center gap-1.5">
                    <Newspaper className="w-3.5 h-3.5" />
                    Press &amp; Stories
                  </div>
                  <h2 className="font-display text-3xl sm:text-4xl font-bold uppercase text-white">
                    Latest <span className="text-vcf-orange italic">Academy News</span>
                  </h2>
                </div>
                <Link
                  href="/news"
                  className="text-xs font-bold uppercase tracking-wider text-vcf-orange hover:underline flex items-center gap-1"
                >
                  <span>View All News</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </Link>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {news.map((item) => (
                  <Link
                    key={String(item.id)}
                    href={`/news/${item.slug}`}
                    className="group rounded-2xl bg-vcf-surface border border-vcf-border hover:border-vcf-orange/60 overflow-hidden flex flex-col justify-between transition-all hover:-translate-y-1"
                  >
                    <div>
                      <div className="relative h-48 bg-vcf-card overflow-hidden">
                        <img
                          src={String(item.imagen_destacada || '/assets/img/news-placeholder-editorial.jpg')}
                          alt={String(item.titulo)}
                          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                        {Boolean(item.categoria_nombre) && (
                          <span
                            className="absolute top-3 left-3 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider text-black"
                            style={{ backgroundColor: String(item.categoria_color || '#FF6600') }}
                          >
                            {String(item.categoria_nombre)}
                          </span>
                        )}
                      </div>
                      <div className="p-5 space-y-2.5">
                        <h3 className="font-display text-xl font-bold uppercase text-white group-hover:text-vcf-orange transition-colors line-clamp-2">
                          {String(item.titulo)}
                        </h3>
                        {Boolean(item.resumen) && (
                          <p className="text-xs text-gray-400 line-clamp-3 leading-relaxed">
                            {String(item.resumen)}
                          </p>
                        )}
                      </div>
                    </div>
                    <div className="px-5 pb-4 pt-2 border-t border-white/5 flex items-center justify-between text-[11px] text-gray-500">
                      <span>{String(item.fecha_publicacion || '').slice(0, 10)}</span>
                      <span className="text-vcf-orange font-semibold group-hover:translate-x-0.5 transition-transform">
                        Read Article &rarr;
                      </span>
                    </div>
                  </Link>
                ))}
              </div>
            </div>
          </section>
        )}

        {/* 10. Match Reels */}
        <ReelsCarousel initialReels={reels} />

        {/* 11. VCF Star of the Month */}
        {star && (
          <section id="star" className="py-16 bg-gradient-to-b from-vcf-surface to-vcf-dark border-b border-vcf-border">
            <div className="max-w-7xl mx-auto px-4 sm:px-6">
              <div className="rounded-3xl bg-gradient-to-r from-[#1E140C] via-vcf-card to-vcf-surface border border-vcf-gold/40 p-8 sm:p-10 shadow-2xl relative overflow-hidden">
                <div className="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                  <div className="md:col-span-4 flex flex-col items-center text-center relative">
                    <div className="relative w-36 h-36 rounded-full bg-gradient-to-br from-vcf-gold to-vcf-orange p-1 shadow-[0_0_30px_rgba(212,175,55,0.3)]">
                      {star.foto_url ? (
                        <img
                          src={star.foto_url}
                          alt={star.nombre}
                          className="w-full h-full rounded-full object-cover"
                        />
                      ) : (
                        <div className="w-full h-full rounded-full bg-vcf-dark flex items-center justify-center">
                          <img src="/assets/img/star-default.svg" alt="Star" className="w-20 h-20" />
                        </div>
                      )}
                      {star.dorsal && (
                        <span className="absolute -bottom-2 -right-2 w-11 h-11 rounded-full bg-vcf-orange text-black font-display font-bold text-lg flex items-center justify-center border-2 border-black">
                          #{star.dorsal}
                        </span>
                      )}
                    </div>
                  </div>

                  <div className="md:col-span-8 space-y-3 text-center md:text-left">
                    <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-vcf-gold/20 border border-vcf-gold/40 text-vcf-gold text-xs font-bold uppercase tracking-widest">
                      <Star className="w-3.5 h-3.5 fill-current" />
                      VCF Star of the Month · {star.mes}
                    </div>
                    <h2 className="font-display text-3xl sm:text-5xl font-bold uppercase text-white">
                      {star.nombre}
                    </h2>
                    <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange">
                      Division {star.categoria} · Official Academy Recognition
                    </div>
                    <p className="text-sm sm:text-base text-gray-200 leading-relaxed max-w-2xl pt-1">
                      {star.descripcion_logro}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </section>
        )}
      </main>

      <Footer settings={settings} />
    </>
  );
}
