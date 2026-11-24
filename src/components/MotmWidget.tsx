'use client';

import React, { useState, useEffect } from 'react';
import { Trophy, CheckCircle2, Sparkles, Clock } from 'lucide-react';

interface MotmNominee {
  id: number;
  nombre: string;
  foto_url: string | null;
  orden: number;
  roster_id: number | null;
  votes: number;
  pct: number;
}

interface MotmOpenData {
  id: number;
  juego_id: number;
  ends_at: string;
  fecha: string;
  rival: string;
  goles_vcf: number | null;
  goles_rival: number | null;
  total_votes: number;
  nominees: MotmNominee[];
}

interface MotmWinnerData {
  id: number;
  nombre: string;
  foto_url: string | null;
  rival: string;
  fecha: string;
  total_votes: number;
  winner_votes: number;
  winner_pct: number;
}

export function MotmWidget({
  initialOpen,
  initialWinner,
}: {
  initialOpen: MotmOpenData | null;
  initialWinner: MotmWinnerData | null;
}) {
  const [openData, setOpenData] = useState<MotmOpenData | null>(initialOpen);
  const [votedNomineeId, setVotedNomineeId] = useState<number | null>(null);
  const [statusMsg, setStatusMsg] = useState<string | null>(null);
  const [isError, setIsError] = useState(false);
  const [loading, setLoading] = useState(false);
  const [timeLeft, setTimeLeft] = useState('');

  useEffect(() => {
    if (!openData) return;
    const saved = localStorage.getItem(`vcf_motm_voted_${openData.id}`);
    if (saved) setVotedNomineeId(Number(saved));

    const updateTimer = () => {
      const diff = new Date(openData.ends_at).getTime() - Date.now();
      if (diff <= 0) {
        setTimeLeft('Voting Ended');
        return;
      }
      const h = Math.floor(diff / 3600000);
      const m = Math.floor((diff % 3600000) / 60000);
      const s = Math.floor((diff % 60000) / 1000);
      setTimeLeft(`${h}h ${m}m ${s}s remaining`);
    };
    updateTimer();
    const timer = setInterval(updateTimer, 1000);
    return () => clearInterval(timer);
  }, [openData]);

  if (!openData && !initialWinner) return null;

  const handleVote = async (nomineeId: number) => {
    if (!openData || loading) return;
    setLoading(true);
    setStatusMsg(null);
    setIsError(false);

    try {
      let fp = localStorage.getItem('vcf_device_fp');
      if (!fp) {
        fp = Math.random().toString(36).substring(2) + Date.now().toString(36);
        localStorage.setItem('vcf_device_fp', fp);
      }

      const res = await fetch('/api/public/motm-vote', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          votacion_id: openData.id,
          nominee_id: nomineeId,
          fingerprint: fp,
        }),
      });
      const data = await res.json();
      if (!res.ok || !data.ok) {
        setIsError(true);
        setStatusMsg(data.error || 'Could not register vote.');
      } else {
        setVotedNomineeId(nomineeId);
        localStorage.setItem(`vcf_motm_voted_${openData.id}`, String(nomineeId));
        setStatusMsg(data.message || 'Vote recorded! ¡Amunt!');
        setOpenData({
          ...openData,
          total_votes: data.total_votes,
          nominees: openData.nominees.map((nom) => {
            const updated = data.nominees.find((u: { id: number }) => u.id === nom.id);
            return updated ? { ...nom, votes: updated.votes, pct: updated.pct } : nom;
          }),
        });
      }
    } catch {
      setIsError(true);
      setStatusMsg('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <section id="motm" className="py-14 bg-gradient-to-b from-vcf-surface to-vcf-dark border-b border-vcf-border">
      <div className="max-w-7xl mx-auto px-4 sm:px-6">
        {openData ? (
          <div className="rounded-2xl bg-vcf-card border border-vcf-orange/30 p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
              <div>
                <div className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1">
                  <Trophy className="w-4 h-4" />
                  Live Community Poll · Man of the Match
                </div>
                <h2 className="font-display text-2xl sm:text-3xl font-bold uppercase text-white">
                  VCF Houston vs <span className="text-vcf-orange">{openData.rival}</span>
                  {openData.goles_vcf !== null && openData.goles_rival !== null && (
                    <span className="ml-3 text-lg px-2.5 py-0.5 rounded-lg bg-white/10 text-gray-200">
                      {openData.goles_vcf} – {openData.goles_rival}
                    </span>
                  )}
                </h2>
              </div>

              <div className="flex items-center gap-3 self-start md:self-auto">
                <div className="px-3.5 py-2 rounded-xl bg-black/50 border border-white/10 flex items-center gap-2 text-xs text-gray-200 font-medium">
                  <Clock className="w-4 h-4 text-vcf-orange" />
                  <span>{timeLeft}</span>
                </div>
                <div className="px-3.5 py-2 rounded-xl bg-vcf-orange/15 border border-vcf-orange/30 text-xs font-bold text-vcf-orange">
                  {openData.total_votes} Votes
                </div>
              </div>
            </div>

            {statusMsg && (
              <div
                className={`mb-5 px-4 py-3 rounded-xl text-xs font-semibold flex items-center gap-2 ${
                  isError
                    ? 'bg-red-500/15 border border-red-500/40 text-red-200'
                    : 'bg-emerald-500/15 border border-emerald-500/40 text-emerald-200'
                }`}
              >
                <CheckCircle2 className="w-4 h-4 shrink-0" />
                <span>{statusMsg}</span>
              </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {openData.nominees.map((nom) => {
                const isSelected = votedNomineeId === nom.id;
                const initials = nom.nombre
                  .split(' ')
                  .map((p) => p[0])
                  .join('')
                  .slice(0, 2)
                  .toUpperCase();

                return (
                  <div
                    key={nom.id}
                    className={`rounded-xl p-5 border transition-all flex flex-col justify-between ${
                      isSelected
                        ? 'bg-vcf-orange/15 border-vcf-orange shadow-[0_0_20px_rgba(255,102,0,0.2)]'
                        : 'bg-vcf-surface border-vcf-border hover:border-white/25'
                    }`}
                  >
                    <div>
                      <div className="flex items-center gap-3.5 mb-4">
                        {nom.foto_url ? (
                          <img
                            src={nom.foto_url}
                            alt={nom.nombre}
                            className="w-14 h-14 rounded-full object-cover border-2 border-vcf-orange"
                          />
                        ) : (
                          <div className="w-14 h-14 rounded-full bg-gradient-to-br from-vcf-orange to-vcf-orange-dark text-black font-display font-bold text-lg flex items-center justify-center">
                            {initials}
                          </div>
                        )}
                        <div>
                          <div className="text-[10px] font-bold uppercase tracking-widest text-vcf-orange">
                            Nominee #{nom.orden}
                          </div>
                          <h3 className="font-display text-lg font-bold uppercase text-white">
                            {nom.nombre}
                          </h3>
                        </div>
                      </div>

                      {/* Progress Bar */}
                      <div className="space-y-1.5 mb-4">
                        <div className="flex justify-between text-xs">
                          <span className="text-gray-400">{nom.votes} votes</span>
                          <span className="font-bold text-vcf-orange">{nom.pct}%</span>
                        </div>
                        <div className="w-full h-2 rounded-full bg-black/60 overflow-hidden">
                          <div
                            className="h-full bg-gradient-to-r from-vcf-orange to-vcf-gold transition-all duration-500"
                            style={{ width: `${nom.pct}%` }}
                          />
                        </div>
                      </div>
                    </div>

                    <button
                      type="button"
                      disabled={loading || votedNomineeId !== null}
                      onClick={() => handleVote(nom.id)}
                      className={`w-full py-2.5 rounded-lg font-display font-bold text-xs uppercase tracking-wider transition-all ${
                        isSelected
                          ? 'bg-emerald-500 text-black cursor-default'
                          : votedNomineeId !== null
                          ? 'bg-white/5 text-gray-500 cursor-not-allowed'
                          : 'bg-vcf-orange hover:bg-vcf-orange-light text-black'
                      }`}
                    >
                      {isSelected ? '✓ Your Vote' : votedNomineeId !== null ? 'Voted' : 'Vote for Player'}
                    </button>
                  </div>
                );
              })}
            </div>
          </div>
        ) : initialWinner ? (
          <div className="rounded-2xl bg-gradient-to-r from-vcf-card via-[#1F1710] to-vcf-card border border-vcf-gold/40 p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div className="flex items-center gap-5">
              <div className="w-16 h-16 rounded-2xl bg-vcf-gold/20 border border-vcf-gold/50 flex items-center justify-center text-vcf-gold shrink-0">
                <Trophy className="w-8 h-8" />
              </div>
              <div>
                <div className="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-vcf-gold">
                  <Sparkles className="w-3.5 h-3.5" />
                  Latest Man of the Match Winner
                </div>
                <h3 className="font-display text-2xl sm:text-3xl font-bold uppercase text-white mt-0.5">
                  {initialWinner.nombre}
                </h3>
                <p className="text-xs text-gray-300 mt-1">
                  vs {initialWinner.rival} · {initialWinner.winner_votes} community votes ({initialWinner.winner_pct}%)
                </p>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </section>
  );
}
