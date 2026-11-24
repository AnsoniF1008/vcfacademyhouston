import React from 'react';
import Link from 'next/link';
import { getAllMatches, getSiteSettings } from '@/lib/queries';
import { getSessionUser } from '@/lib/auth';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight, Download, ExternalLink } from 'lucide-react';

export const dynamic = 'force-dynamic';

export default async function CalendarPage({
  searchParams,
}: {
  searchParams: Promise<{ m?: string; id?: string }>;
}) {
  const sp = await searchParams;
  const user = await getSessionUser();
  const settings = getSiteSettings();
  const allMatches = getAllMatches();

  const singleMatchId = sp.id ? Number(sp.id) : 0;
  const singleMatch = singleMatchId > 0 ? allMatches.find((m) => m.id === singleMatchId) : null;

  // Determine month to display
  const now = new Date();
  let year = now.getFullYear();
  let month = now.getMonth() + 1; // 1..12

  if (sp.m && /^\d{4}-\d{2}$/.test(sp.m)) {
    year = Number(sp.m.slice(0, 4));
    month = Number(sp.m.slice(5, 7));
  } else if (allMatches.length > 0) {
    // Default to current month
  }

  month = Math.max(1, Math.min(12, month));
  const firstDayOfMonth = new Date(year, month - 1, 1);
  const daysInMonth = new Date(year, month, 0).getDate();
  const startDow = firstDayOfMonth.getDay(); // 0=Sun

  const prevYear = month === 1 ? year - 1 : year;
  const prevMonth = month === 1 ? 12 : month - 1;
  const nextYear = month === 12 ? year + 1 : year;
  const nextMonth = month === 12 ? 1 : month + 1;

  const pad2 = (n: number) => (n < 10 ? `0${n}` : `${n}`);
  const prevParam = `${prevYear}-${pad2(prevMonth)}`;
  const nextParam = `${nextYear}-${pad2(nextMonth)}`;
  const monthLabel = firstDayOfMonth.toLocaleString('en-US', { month: 'long', year: 'numeric' });

  const matchesByDate = new Map<string, typeof allMatches>();
  for (const m of allMatches) {
    if (!matchesByDate.has(m.fecha)) {
      matchesByDate.set(m.fecha, []);
    }
    matchesByDate.get(m.fecha)!.push(m);
  }

  const todayStr = now.toISOString().slice(0, 10);

  return (
    <>
      <Navbar isAdminLoggedIn={Boolean(user)} />

      <main className="flex-1 py-12 bg-vcf-dark">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 space-y-8">
          <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
              <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1 flex items-center gap-1.5">
                <CalendarIcon className="w-3.5 h-3.5" />
                Official Schedule &amp; Sync
              </div>
              <h1 className="font-display text-3xl sm:text-5xl font-bold uppercase text-white">
                Match <span className="text-vcf-orange italic">Calendar</span>
              </h1>
            </div>

            {/* Month Switcher */}
            <div className="flex items-center gap-3 bg-vcf-surface border border-vcf-border p-1.5 rounded-xl self-start sm:self-auto">
              <Link
                href={`/calendar?m=${prevParam}`}
                className="p-2 rounded-lg hover:bg-white/10 text-gray-300 hover:text-white transition-colors"
              >
                <ChevronLeft className="w-4 h-4" />
              </Link>
              <span className="font-display text-base font-bold uppercase tracking-wider text-white px-3 min-w-[140px] text-center">
                {monthLabel}
              </span>
              <Link
                href={`/calendar?m=${nextParam}`}
                className="p-2 rounded-lg hover:bg-white/10 text-gray-300 hover:text-white transition-colors"
              >
                <ChevronRight className="w-4 h-4" />
              </Link>
            </div>
          </div>

          {/* Optional Single Match Quick Export Banner */}
          {singleMatch && (
            <div className="rounded-2xl bg-vcf-card border border-vcf-orange p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <div className="text-xs font-bold uppercase tracking-wider text-vcf-orange">
                  Add Match to Your Personal Calendar
                </div>
                <h2 className="font-display text-2xl font-bold uppercase text-white mt-1">
                  VCF Houston vs {singleMatch.rival || 'TBD'} · {singleMatch.fecha}
                </h2>
                <p className="text-xs text-gray-400 mt-0.5">
                  {[singleMatch.sede_nombre, singleMatch.cancha].filter(Boolean).join(' · ')}
                </p>
              </div>
              <div className="flex flex-wrap items-center gap-2.5">
                <a
                  href={`/api/public/ics/${singleMatch.id}`}
                  className="px-4 py-2.5 rounded-xl bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider flex items-center gap-1.5"
                >
                  <Download className="w-3.5 h-3.5" />
                  Download .ICS
                </a>
                <Link
                  href={`/match/${singleMatch.id}`}
                  className="px-4 py-2.5 rounded-xl bg-white/10 text-white text-xs font-semibold flex items-center gap-1.5"
                >
                  <span>Match Center</span>
                  <ExternalLink className="w-3.5 h-3.5" />
                </Link>
              </div>
            </div>
          )}

          {/* Calendar Grid */}
          <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-4 sm:p-6">
            <div className="grid grid-cols-7 gap-2 mb-2 text-center">
              {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((d) => (
                <div
                  key={d}
                  className="text-[11px] font-bold uppercase tracking-widest text-gray-400 py-1"
                >
                  {d}
                </div>
              ))}
            </div>

            <div className="grid grid-cols-7 gap-2">
              {Array.from({ length: startDow }).map((_, i) => (
                <div
                  key={`empty-${i}`}
                  className="min-h-[90px] sm:min-h-[110px] rounded-xl bg-black/20 border border-white/[0.03]"
                />
              ))}

              {Array.from({ length: daysInMonth }).map((_, idx) => {
                const day = idx + 1;
                const dateStr = `${year}-${pad2(month)}-${pad2(day)}`;
                const dayMatches = matchesByDate.get(dateStr) || [];
                const isToday = dateStr === todayStr;

                return (
                  <div
                    key={dateStr}
                    className={`min-h-[90px] sm:min-h-[110px] rounded-xl p-2 border transition-colors ${
                      isToday
                        ? 'bg-vcf-orange/10 border-vcf-orange'
                        : 'bg-vcf-card border-vcf-border hover:border-white/20'
                    }`}
                  >
                    <div
                      className={`text-xs font-bold mb-1.5 ${
                        isToday ? 'text-vcf-orange' : 'text-gray-400'
                      }`}
                    >
                      {day}
                    </div>
                    <div className="space-y-1.5">
                      {dayMatches.map((dm) => {
                        const played = dm.goles_vcf !== null && dm.goles_rival !== null;
                        const win = played && Number(dm.goles_vcf) > Number(dm.goles_rival);
                        const loss = played && Number(dm.goles_vcf) < Number(dm.goles_rival);

                        return (
                          <Link
                            key={dm.id}
                            href={`/match/${dm.id}`}
                            className={`block px-2 py-1 rounded-lg text-[10px] font-bold truncate transition-transform hover:scale-[1.02] ${
                              played
                                ? win
                                  ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                                  : loss
                                  ? 'bg-red-500/20 text-red-300 border border-red-500/30'
                                  : 'bg-white/10 text-gray-200'
                                : 'bg-vcf-orange/20 text-vcf-orange border border-vcf-orange/40'
                            }`}
                          >
                            {played
                              ? `${dm.goles_vcf}–${dm.goles_rival} vs ${dm.rival}`
                              : `${dm.hora || ''} vs ${dm.rival}`}
                          </Link>
                        );
                      })}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      </main>

      <Footer settings={settings} />
    </>
  );
}
