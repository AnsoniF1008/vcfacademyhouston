'use client';

import React, { useState, useMemo } from 'react';
import { Search, X, Trophy, Target, Award, Share2, Sparkles } from 'lucide-react';
import type { PlayerWithStats } from '@/lib/queries';

export function RosterSection({
  players,
  starDorsal,
}: {
  players: PlayerWithStats[];
  starDorsal?: number | null;
}) {
  const [search, setSearch] = useState('');
  const [groupFilter, setGroupFilter] = useState<'ALL' | 'GK' | 'DEF' | 'MID' | 'FWD'>('ALL');
  const [selectedPlayer, setSelectedPlayer] = useState<PlayerWithStats | null>(null);
  const [copiedShare, setCopiedShare] = useState(false);

  const counts = useMemo(() => {
    return {
      ALL: players.length,
      GK: players.filter((p) => p.group === 'GK').length,
      DEF: players.filter((p) => p.group === 'DEF').length,
      MID: players.filter((p) => p.group === 'MID').length,
      FWD: players.filter((p) => p.group === 'FWD').length,
    };
  }, [players]);

  const filteredPlayers = useMemo(() => {
    return players.filter((p) => {
      if (groupFilter !== 'ALL' && p.group !== groupFilter) return false;
      if (search.trim() !== '') {
        const q = search.toLowerCase();
        const full = `${p.nombre} ${p.apellido} ${p.dorsal ?? ''} ${p.posicion ?? ''}`.toLowerCase();
        return full.includes(q);
      }
      return true;
    });
  }, [players, groupFilter, search]);

  const topScorers = useMemo(
    () => [...players].sort((a, b) => b.goles - a.goles).slice(0, 3),
    [players]
  );
  const topAssists = useMemo(
    () => [...players].sort((a, b) => b.asistencias - a.asistencias).slice(0, 3),
    [players]
  );
  const topMotm = useMemo(
    () => [...players].sort((a, b) => b.motm - a.motm).slice(0, 3),
    [players]
  );

  // Compute 6-axis radar polygon points for the selected player
  const getRadarPoints = (p: PlayerWithStats) => {
    const values = [p.pace, p.shooting, p.dribbling, p.defense, p.physical, p.passing];
    const angles = [-90, -30, 30, 90, 150, 210];
    const cx = 50;
    const cy = 50;
    const maxR = 36;

    return values
      .map((val, idx) => {
        const r = (Math.min(10, Math.max(1, val)) / 10) * maxR;
        const rad = (angles[idx] * Math.PI) / 180;
        const x = (cx + r * Math.cos(rad)).toFixed(1);
        const y = (cy + r * Math.sin(rad)).toFixed(1);
        return `${x},${y}`;
      })
      .join(' ');
  };

  const handleSharePlayer = (p: PlayerWithStats) => {
    const text = `${p.nombre} ${p.apellido} (#${p.dorsal ?? '-'}) — VCF Academy Houston ${p.categoria_nombre} | ${p.goles} Goals · ${p.asistencias} Assists · ${p.overall} OVR`;
    if (navigator.clipboard?.writeText) {
      navigator.clipboard.writeText(text);
      setCopiedShare(true);
      setTimeout(() => setCopiedShare(false), 2000);
    }
  };

  return (
    <section id="roster" className="py-16 bg-vcf-dark border-b border-vcf-border">
      <div className="max-w-7xl mx-auto px-4 sm:px-6">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-10">
          <div>
            <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1">
              Official Academy Squad
            </div>
            <h2 className="font-display text-3xl sm:text-4xl font-bold uppercase text-white">
              Squad <span className="text-vcf-orange italic">Roster &amp; FUT Cards</span>
            </h2>
            <p className="text-xs sm:text-sm text-gray-400 mt-1">
              Click any player card to inspect full season statistics and their 6-axis tactical skill radar.
            </p>
          </div>
          <div className="text-xs font-semibold text-gray-400 bg-vcf-surface border border-vcf-border px-4 py-2 rounded-xl">
            {players.length} Active Players · Season 2026
          </div>
        </div>

        {/* Leaderboards Row */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
          {[
            { title: 'Top Scorers (Pichichi)', icon: Trophy, list: topScorers, key: 'goles' as const, unit: 'Goals' },
            { title: 'Playmakers (Assists)', icon: Target, list: topAssists, key: 'asistencias' as const, unit: 'Ast' },
            { title: 'Man of the Match', icon: Award, list: topMotm, key: 'motm' as const, unit: 'MOTM' },
          ].map((board) => {
            const Icon = board.icon;
            return (
              <div
                key={board.title}
                className="rounded-2xl bg-vcf-surface border border-vcf-border p-5 space-y-3"
              >
                <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-vcf-orange pb-2 border-b border-white/10">
                  <Icon className="w-4 h-4" />
                  <span>{board.title}</span>
                </div>
                <div className="space-y-2.5">
                  {board.list.map((p, idx) => (
                    <button
                      key={p.id}
                      type="button"
                      onClick={() => setSelectedPlayer(p)}
                      className="w-full flex items-center justify-between p-2 rounded-xl hover:bg-white/5 transition-colors text-left"
                    >
                      <div className="flex items-center gap-3">
                        <span
                          className={`w-6 h-6 rounded-full font-display font-bold text-xs flex items-center justify-center ${
                            idx === 0
                              ? 'bg-vcf-gold text-black'
                              : 'bg-vcf-card text-gray-400 border border-white/10'
                          }`}
                        >
                          {idx + 1}
                        </span>
                        <div className="w-8 h-8 rounded-full bg-vcf-orange/20 border border-vcf-orange/40 text-vcf-orange font-display font-bold text-xs flex items-center justify-center">
                          {p.initials}
                        </div>
                        <div>
                          <div className="text-xs font-bold text-white">
                            {p.nombre} {p.apellido.split(' ')[0]}
                          </div>
                          <div className="text-[10px] text-gray-400">
                            #{p.dorsal ?? '–'} · {p.pos_short}
                          </div>
                        </div>
                      </div>
                      <div className="text-right">
                        <span className="font-display text-lg font-bold text-vcf-orange">
                          {p[board.key]}
                        </span>
                        <span className="text-[10px] text-gray-400 uppercase ml-1">
                          {board.unit}
                        </span>
                      </div>
                    </button>
                  ))}
                </div>
              </div>
            );
          })}
        </div>

        {/* Search & Position Line Filters */}
        <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 mb-8">
          <div className="relative flex-1 max-w-md">
            <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search player by name, #dorsal, or position..."
              className="w-full pl-10 pr-9 py-2.5 rounded-xl bg-vcf-surface border border-vcf-border text-xs sm:text-sm text-white placeholder-gray-500 focus:outline-none focus:border-vcf-orange"
            />
            {search && (
              <button
                type="button"
                onClick={() => setSearch('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white"
              >
                <X className="w-4 h-4" />
              </button>
            )}
          </div>

          <div className="flex flex-wrap items-center gap-2">
            {(['ALL', 'GK', 'DEF', 'MID', 'FWD'] as const).map((grp) => (
              <button
                key={grp}
                type="button"
                onClick={() => setGroupFilter(grp)}
                className={`px-3.5 py-2 rounded-xl font-display font-bold text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 ${
                  groupFilter === grp
                    ? 'bg-vcf-orange text-black'
                    : 'bg-vcf-surface border border-vcf-border text-gray-300 hover:text-white'
                }`}
              >
                <span>{grp}</span>
                <span
                  className={`px-1.5 py-0.2 rounded text-[10px] ${
                    groupFilter === grp ? 'bg-black/20 text-black' : 'bg-white/5 text-gray-400'
                  }`}
                >
                  {counts[grp]}
                </span>
              </button>
            ))}
          </div>
        </div>

        {/* FIFA-style Player Cards Grid */}
        {filteredPlayers.length === 0 ? (
          <div className="py-16 text-center rounded-2xl bg-vcf-surface border border-vcf-border">
            <p className="text-sm text-gray-400">No players match your search filter.</p>
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            {filteredPlayers.map((p) => {
              const isStar = starDorsal && p.dorsal === starDorsal;
              return (
                <button
                  key={p.id}
                  type="button"
                  onClick={() => setSelectedPlayer(p)}
                  className="group relative rounded-2xl bg-gradient-to-b from-[#231F20] via-vcf-card to-[#0E0E12] border border-vcf-border hover:border-vcf-orange p-4 text-left transition-all duration-200 hover:-translate-y-1.5 hover:shadow-[0_12px_30px_rgba(255,102,0,0.2)] overflow-hidden fut-card-shine"
                >
                  {/* Top Row: OVR + Position + Jersey */}
                  <div className="flex items-start justify-between mb-3">
                    <div>
                      <div className="font-display text-2xl font-bold text-vcf-gold leading-none">
                        {p.overall}
                      </div>
                      <div className="text-[10px] font-bold uppercase tracking-wider text-vcf-orange mt-0.5">
                        {p.pos_short}
                      </div>
                    </div>
                    <div className="flex flex-col items-end gap-1">
                      {p.dorsal !== null && (
                        <span className="font-display text-lg font-bold text-white/30 group-hover:text-vcf-orange transition-colors">
                          #{p.dorsal}
                        </span>
                      )}
                      {isStar && (
                        <span className="px-1.5 py-0.5 rounded bg-vcf-gold/20 border border-vcf-gold text-[9px] font-bold uppercase text-vcf-gold">
                          ★ Star
                        </span>
                      )}
                    </div>
                  </div>

                  {/* Avatar / Photo */}
                  <div className="relative w-20 h-20 mx-auto my-2 rounded-full bg-gradient-to-br from-vcf-orange/25 to-black border-2 border-vcf-orange/40 group-hover:border-vcf-orange flex items-center justify-center overflow-hidden">
                    {p.foto_url ? (
                      <img src={p.foto_url} alt={p.nombre} className="w-full h-full object-cover" />
                    ) : (
                      <span className="font-display text-2xl font-bold text-white">
                        {p.initials}
                      </span>
                    )}
                  </div>

                  {/* Name & Category */}
                  <div className="text-center mt-3 pt-2.5 border-t border-white/10">
                    <div className="font-display text-sm font-bold uppercase text-white truncate">
                      {p.nombre} {p.apellido.split(' ')[0]}
                    </div>
                    <div className="text-[10px] text-gray-400 truncate mt-0.5">
                      {p.categoria_nombre} · {p.goles}G {p.asistencias}A
                    </div>
                  </div>

                  {/* Mini FUT Attributes */}
                  <div className="mt-3 pt-2 border-t border-white/5 grid grid-cols-3 gap-1 text-center text-[10px]">
                    <div>
                      <span className="text-gray-500">PAC </span>
                      <strong className="text-gray-200">{p.pace * 10}</strong>
                    </div>
                    <div>
                      <span className="text-gray-500">SHO </span>
                      <strong className="text-gray-200">{p.shooting * 10}</strong>
                    </div>
                    <div>
                      <span className="text-gray-500">PAS </span>
                      <strong className="text-gray-200">{p.passing * 10}</strong>
                    </div>
                  </div>
                </button>
              );
            })}
          </div>
        )}

        {/* FIFA Player Card & Radar Modal */}
        {selectedPlayer && (
          <div
            className="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4"
            onClick={() => setSelectedPlayer(null)}
          >
            <div
              className="relative w-full max-w-2xl rounded-3xl bg-vcf-surface border border-vcf-orange/40 p-6 sm:p-8 shadow-2xl overflow-hidden"
              onClick={(e) => e.stopPropagation()}
            >
              <button
                type="button"
                onClick={() => setSelectedPlayer(null)}
                className="absolute top-5 right-5 w-9 h-9 rounded-full bg-white/5 hover:bg-vcf-orange hover:text-black flex items-center justify-center text-gray-300 transition-colors"
                aria-label="Close modal"
              >
                <X className="w-5 h-5" />
              </button>

              <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                {/* Left: FUT Card Preview & Radar */}
                <div className="md:col-span-5 flex flex-col items-center text-center p-5 rounded-2xl bg-vcf-card border border-vcf-border relative">
                  <div className="flex items-center justify-between w-full mb-2">
                    <div className="text-left">
                      <div className="font-display text-3xl font-bold text-vcf-gold">
                        {selectedPlayer.overall}
                      </div>
                      <div className="text-xs font-bold uppercase text-vcf-orange">
                        {selectedPlayer.pos_short}
                      </div>
                    </div>
                    <img src="/assets/img/vcf-crest.png" alt="VCF" className="w-9 h-11 object-contain" />
                  </div>

                  <div className="w-24 h-24 rounded-full bg-gradient-to-br from-vcf-orange to-vcf-orange-dark text-black font-display text-3xl font-bold flex items-center justify-center my-2 border-4 border-white/20">
                    {selectedPlayer.foto_url ? (
                      <img
                        src={selectedPlayer.foto_url}
                        alt={selectedPlayer.nombre}
                        className="w-full h-full rounded-full object-cover"
                      />
                    ) : (
                      selectedPlayer.initials
                    )}
                  </div>

                  <h3 className="font-display text-2xl font-bold uppercase text-white mt-2">
                    {selectedPlayer.nombre} {selectedPlayer.apellido}
                  </h3>
                  <p className="text-xs text-vcf-orange font-semibold">
                    #{selectedPlayer.dorsal ?? '–'} · {selectedPlayer.sub_posicion || selectedPlayer.posicion}
                  </p>

                  {/* 6-Axis Radar SVG */}
                  <div className="w-44 h-44 mt-4">
                    <svg viewBox="0 0 100 100" className="w-full h-full">
                      <polygon
                        fill="none"
                        stroke="rgba(255,255,255,0.15)"
                        strokeWidth="0.5"
                        points="50,14 81.2,32 81.2,68 50,86 18.8,68 18.8,32"
                      />
                      <polygon
                        fill="none"
                        stroke="rgba(255,255,255,0.08)"
                        strokeWidth="0.4"
                        points="50,26 70.8,38 70.8,62 50,74 29.2,62 29.2,38"
                      />
                      <line x1="50" y1="50" x2="50" y2="14" stroke="rgba(255,255,255,0.15)" strokeWidth="0.4" />
                      <line x1="50" y1="50" x2="81.2" y2="32" stroke="rgba(255,255,255,0.15)" strokeWidth="0.4" />
                      <line x1="50" y1="50" x2="81.2" y2="68" stroke="rgba(255,255,255,0.15)" strokeWidth="0.4" />
                      <line x1="50" y1="50" x2="50" y2="86" stroke="rgba(255,255,255,0.15)" strokeWidth="0.4" />
                      <line x1="50" y1="50" x2="18.8" y2="68" stroke="rgba(255,255,255,0.15)" strokeWidth="0.4" />
                      <line x1="50" y1="50" x2="18.8" y2="32" stroke="rgba(255,255,255,0.15)" strokeWidth="0.4" />
                      <polygon
                        fill="rgba(255, 102, 0, 0.38)"
                        stroke="#FF6600"
                        strokeWidth="1.4"
                        points={getRadarPoints(selectedPlayer)}
                      />
                      <text x="50" y="9" textAnchor="middle" fill="#E5E7EB" fontSize="5.5" fontWeight="bold">
                        PAC ({selectedPlayer.pace})
                      </text>
                      <text x="87" y="31" textAnchor="middle" fill="#E5E7EB" fontSize="5.5" fontWeight="bold">
                        SHO ({selectedPlayer.shooting})
                      </text>
                      <text x="87" y="73" textAnchor="middle" fill="#E5E7EB" fontSize="5.5" fontWeight="bold">
                        DRI ({selectedPlayer.dribbling})
                      </text>
                      <text x="50" y="95" textAnchor="middle" fill="#E5E7EB" fontSize="5.5" fontWeight="bold">
                        DEF ({selectedPlayer.defense})
                      </text>
                      <text x="13" y="73" textAnchor="middle" fill="#E5E7EB" fontSize="5.5" fontWeight="bold">
                        PHY ({selectedPlayer.physical})
                      </text>
                      <text x="13" y="31" textAnchor="middle" fill="#E5E7EB" fontSize="5.5" fontWeight="bold">
                        PAS ({selectedPlayer.passing})
                      </text>
                    </svg>
                  </div>
                </div>

                {/* Right: Season Stats & Progress Bars */}
                <div className="md:col-span-7 space-y-5">
                  <div>
                    <span className="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-vcf-orange">
                      <Sparkles className="w-3.5 h-3.5" />
                      Official Player Profile · {selectedPlayer.categoria_nombre}
                    </span>
                    <h4 className="font-display text-2xl font-bold uppercase text-white mt-1">
                      Season Performance
                    </h4>
                  </div>

                  <div className="space-y-3">
                    {[
                      { label: 'Appearances (Matches)', val: selectedPlayer.partidos_jugados, max: 15 },
                      { label: 'Goals Scored', val: selectedPlayer.goles, max: 15 },
                      { label: 'Assists Provided', val: selectedPlayer.asistencias, max: 12 },
                      { label: 'Man of the Match Awards', val: selectedPlayer.motm, max: 5 },
                      { label: 'Clean Sheets', val: selectedPlayer.clean_sheets, max: 10 },
                    ].map((st) => {
                      const pct = Math.min(100, Math.round((st.val / st.max) * 100));
                      return (
                        <div key={st.label} className="space-y-1">
                          <div className="flex justify-between text-xs">
                            <span className="text-gray-300 font-medium">{st.label}</span>
                            <span className="font-display font-bold text-vcf-orange text-sm">
                              {st.val}
                            </span>
                          </div>
                          <div className="w-full h-2 rounded-full bg-black/60 overflow-hidden">
                            <div
                              className="h-full bg-gradient-to-r from-vcf-orange to-vcf-gold rounded-full transition-all duration-500"
                              style={{ width: `${pct}%` }}
                            />
                          </div>
                        </div>
                      );
                    })}
                  </div>

                  <div className="pt-3 flex items-center gap-3">
                    <button
                      type="button"
                      onClick={() => handleSharePlayer(selectedPlayer)}
                      className="px-4 py-2.5 rounded-xl bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-xs uppercase tracking-wider flex items-center gap-2 transition-all"
                    >
                      <Share2 className="w-3.5 h-3.5" />
                      {copiedShare ? 'Copied Player Card!' : 'Share Player Stats'}
                    </button>
                    <button
                      type="button"
                      onClick={() => setSelectedPlayer(null)}
                      className="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-300"
                    >
                      Close
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </section>
  );
}
