'use client';

import React, { useState, useMemo } from 'react';
import { RotateCcw, ArrowLeftRight, Sparkles } from 'lucide-react';
import type { PlayerWithStats } from '@/lib/queries';

interface PitchSlot {
  key: string;
  pos: string;
  group: 'GK' | 'DEF' | 'MID' | 'FWD';
  x: number;
  y: number;
  name: string;
  desc: string;
  attrs: string[];
}

interface FormationDef {
  label: string;
  positions: PitchSlot[];
  connections: [number, number][];
}

const FORMATIONS: Record<string, FormationDef> = {
  '433': {
    label: '4-3-3',
    positions: [
      { key: 'GK', pos: 'GK', group: 'GK', x: 10, y: 50, name: 'Goalkeeper', desc: 'Last line of defense. Commands the penalty area, organizes the backline, and initiates build-up play from deep.', attrs: ['Reflexes', 'Distribution', 'Command', 'Leadership'] },
      { key: 'RB', pos: 'RB', group: 'DEF', x: 26, y: 18, name: 'Right Back', desc: 'Defensive solidity with freedom to overlap and join wide attacks. Tracks back rapidly to deny space.', attrs: ['Tackling', 'Crossing', 'Stamina', 'Positioning'] },
      { key: 'CBR', pos: 'CB', group: 'DEF', x: 24, y: 39, name: 'Centre Back (R)', desc: 'Wins aerial duels, blocks shots, and steps into midfield to progress possession.', attrs: ['Heading', 'Tackling', 'Strength', 'Composure'] },
      { key: 'CBL', pos: 'CB', group: 'DEF', x: 24, y: 61, name: 'Centre Back (L)', desc: 'Covers central zones, sweeps behind the defensive line, and builds cleanly under pressure.', attrs: ['Pace', 'Interceptions', 'Ball Play', 'Positioning'] },
      { key: 'LB', pos: 'LB', group: 'DEF', x: 26, y: 82, name: 'Left Back', desc: 'Covers the left flank, balancing defensive discipline with overlapping width.', attrs: ['Crossing', 'Stamina', 'Tackling', 'Speed'] },
      { key: 'CMR', pos: 'CM', group: 'MID', x: 54, y: 27, name: 'Right Interior (CM)', desc: 'Creative interior midfielder. Carries from deep, threads passes between lines, and arrives in the box.', attrs: ['Vision', 'Passing', 'Dribbling', 'Late Runs'] },
      { key: 'CDM', pos: 'CDM', group: 'MID', x: 47, y: 50, name: 'Pivot (CDM)', desc: 'Tactical anchor. Dictates tempo, screens the back four, and recycles possession.', attrs: ['Positioning', 'Short Pass', 'Pressing', 'Balance'] },
      { key: 'CML', pos: 'CM', group: 'MID', x: 54, y: 73, name: 'Left Interior (CM)', desc: 'Box-to-box engine. Supports both attacking combinations and high pressing recovery.', attrs: ['Work Rate', 'Passing', 'Tackling', 'Strength'] },
      { key: 'RW', pos: 'RW', group: 'FWD', x: 80, y: 20, name: 'Right Winger', desc: 'Pace and 1v1 directness on the right wing. Stretches the pitch and attacks the back post.', attrs: ['Pace', 'Dribbling', 'Crossing', 'Finishing'] },
      { key: 'ST', pos: 'ST', group: 'FWD', x: 86, y: 50, name: 'Center Striker', desc: 'Leads the line — makes sharp runs in behind, holds up play, and finishes clinically.', attrs: ['Finishing', 'Movement', 'Hold-up', 'Heading'] },
      { key: 'LW', pos: 'LW', group: 'FWD', x: 80, y: 80, name: 'Left Winger', desc: 'Cuts inside onto the front foot to shoot or combine with the striker and midfielders.', attrs: ['Dribbling', 'Pace', 'Shooting', 'Creativity'] },
    ],
    connections: [[0, 2], [0, 3], [1, 2], [2, 3], [3, 4], [1, 5], [2, 6], [3, 6], [4, 7], [5, 6], [6, 7], [5, 8], [6, 9], [7, 10], [8, 9], [9, 10]],
  },
  '4231': {
    label: '4-2-3-1',
    positions: [
      { key: 'GK', pos: 'GK', group: 'GK', x: 10, y: 50, name: 'Goalkeeper', desc: 'Sweeper-keeper behind a structured four-man backline.', attrs: ['Reflexes', 'Distribution', 'Command', 'Sweeping'] },
      { key: 'RB', pos: 'RB', group: 'DEF', x: 25, y: 18, name: 'Right Back', desc: 'Overlapping full-back providing width on the right channel.', attrs: ['Stamina', 'Crossing', 'Pace', 'Tackling'] },
      { key: 'CBR', pos: 'CB', group: 'DEF', x: 23, y: 39, name: 'Centre Back (R)', desc: 'Progressive ball-playing defender.', attrs: ['Tackling', 'Reading', 'Ball Play', 'Composure'] },
      { key: 'CBL', pos: 'CB', group: 'DEF', x: 23, y: 61, name: 'Centre Back (L)', desc: 'Cover defender commanding the penalty box.', attrs: ['Pace', 'Heading', 'Positioning', 'Strength'] },
      { key: 'LB', pos: 'LB', group: 'DEF', x: 25, y: 82, name: 'Left Back', desc: 'Modern full-back linking defense and wide attack.', attrs: ['Crossing', 'Speed', 'Stamina', 'Tackling'] },
      { key: 'CDMR', pos: 'CDM', group: 'MID', x: 44, y: 37, name: 'Double Pivot (R)', desc: 'Screens the defense and breaks up opposition counters.', attrs: ['Positioning', 'Short Pass', 'Tackling', 'Composure'] },
      { key: 'CDML', pos: 'CDM', group: 'MID', x: 44, y: 63, name: 'Double Pivot (L)', desc: 'Links the backline to the attacking band of three.', attrs: ['Work Rate', 'Passing', 'Pressing', 'Strength'] },
      { key: 'RAM', pos: 'RAM', group: 'FWD', x: 68, y: 22, name: 'Right Attacking Mid', desc: 'Inside forward combining with the #10 and striker.', attrs: ['Dribbling', 'Vision', 'Finishing', 'Movement'] },
      { key: 'CAM', pos: 'CAM', group: 'MID', x: 66, y: 50, name: 'Playmaker (#10)', desc: 'Operates between the lines to unlock compact defenses with final passes.', attrs: ['Vision', 'Passing', 'Dribbling', 'Creativity'] },
      { key: 'LAM', pos: 'LAM', group: 'FWD', x: 68, y: 78, name: 'Left Attacking Mid', desc: 'Attacks half-spaces and creates shooting angles.', attrs: ['Pace', 'Shooting', 'Creativity', 'Dribbling'] },
      { key: 'ST', pos: 'ST', group: 'FWD', x: 86, y: 50, name: 'Lone Striker (#9)', desc: 'Focal point of the attack, finishing chances and pressing high.', attrs: ['Hold-up', 'Finishing', 'Heading', 'Movement'] },
    ],
    connections: [[0, 2], [0, 3], [1, 2], [2, 3], [3, 4], [2, 5], [3, 6], [5, 6], [5, 7], [5, 8], [6, 8], [6, 9], [7, 8], [8, 9], [8, 10]],
  },
  '442': {
    label: '4-4-2',
    positions: [
      { key: 'GK', pos: 'GK', group: 'GK', x: 10, y: 50, name: 'Goalkeeper', desc: 'Organizes the two compact banks of four.', attrs: ['Reflexes', 'Distribution', 'Command', 'Shot-stopping'] },
      { key: 'RB', pos: 'RB', group: 'DEF', x: 26, y: 18, name: 'Right Back', desc: 'Supports the right midfielder with overlapping runs.', attrs: ['Tackling', 'Crossing', 'Stamina', 'Positioning'] },
      { key: 'CBR', pos: 'CB', group: 'DEF', x: 24, y: 39, name: 'Centre Back (R)', desc: 'Dominates central duels and aerial balls.', attrs: ['Heading', 'Strength', 'Reading', 'Composure'] },
      { key: 'CBL', pos: 'CB', group: 'DEF', x: 24, y: 61, name: 'Centre Back (L)', desc: 'Maintains line compactness and distributes cleanly.', attrs: ['Pace', 'Tackling', 'Ball Play', 'Positioning'] },
      { key: 'LB', pos: 'LB', group: 'DEF', x: 26, y: 82, name: 'Left Back', desc: 'Provides defensive balance and wide support on the left.', attrs: ['Speed', 'Crossing', 'Stamina', 'Tackling'] },
      { key: 'RM', pos: 'RM', group: 'MID', x: 53, y: 18, name: 'Right Midfielder', desc: 'Works the right touchline in both phases of play.', attrs: ['Stamina', 'Crossing', 'Tackling', 'Pace'] },
      { key: 'CMR', pos: 'CM', group: 'MID', x: 51, y: 39, name: 'Central Mid (R)', desc: 'Box-to-box presence linking midfield and the strike duo.', attrs: ['Work Rate', 'Passing', 'Pressing', 'Runs'] },
      { key: 'CML', pos: 'CM', group: 'MID', x: 51, y: 61, name: 'Central Mid (L)', desc: 'Controls central zones and wins second balls.', attrs: ['Positioning', 'Short Pass', 'Tackling', 'Vision'] },
      { key: 'LM', pos: 'LM', group: 'MID', x: 53, y: 82, name: 'Left Midfielder', desc: 'Delivers crosses and attacks the far post.', attrs: ['Pace', 'Dribbling', 'Crossing', 'Work Rate'] },
      { key: 'STR', pos: 'ST', group: 'FWD', x: 82, y: 37, name: 'Second Striker', desc: 'Drops into pockets to link play and stretch defenders.', attrs: ['Finishing', 'Speed', 'Movement', 'Pressing'] },
      { key: 'STL', pos: 'ST', group: 'FWD', x: 82, y: 63, name: 'Target Striker', desc: 'Pins center-backs and finishes crosses inside the box.', attrs: ['Strength', 'Hold-up', 'Heading', 'Link Play'] },
    ],
    connections: [[1, 2], [2, 3], [3, 4], [1, 5], [2, 6], [3, 7], [4, 8], [5, 6], [6, 7], [7, 8], [6, 9], [7, 10], [9, 10]],
  },
  '352': {
    label: '3-5-2',
    positions: [
      { key: 'GK', pos: 'GK', group: 'GK', x: 10, y: 50, name: 'Goalkeeper', desc: 'Sweeps behind the back three and initiates wide build-up.', attrs: ['Reflexes', 'Distribution', 'Sweeping', 'Command'] },
      { key: 'RCB', pos: 'CB', group: 'DEF', x: 25, y: 26, name: 'Right Center Back', desc: 'Wide center-back defending the right channel and stepping forward.', attrs: ['Pace', 'Tackling', 'Ball Play', 'Agility'] },
      { key: 'CCB', pos: 'CB', group: 'DEF', x: 23, y: 50, name: 'Central Sweeper (CB)', desc: 'Leader of the back three, reading play and organizing coverage.', attrs: ['Leadership', 'Heading', 'Reading', 'Composure'] },
      { key: 'LCB', pos: 'CB', group: 'DEF', x: 25, y: 74, name: 'Left Center Back', desc: 'Covers the left channel and progresses passes into midfield.', attrs: ['Pace', 'Tackling', 'Passing', 'Positioning'] },
      { key: 'RWB', pos: 'RWB', group: 'MID', x: 52, y: 14, name: 'Right Wing-Back', desc: 'Patrols the entire right flank, providing width in attack and defense.', attrs: ['Stamina', 'Crossing', 'Speed', 'Work Rate'] },
      { key: 'CMR', pos: 'CM', group: 'MID', x: 54, y: 36, name: 'Right Interior', desc: 'Combines with the wing-back and supports the two strikers.', attrs: ['Passing', 'Vision', 'Running', 'Pressing'] },
      { key: 'CDM', pos: 'CDM', group: 'MID', x: 46, y: 50, name: 'Regista / Pivot', desc: 'Heart of the 3-5-2, switching play from side to side.', attrs: ['Long Pass', 'Vision', 'Positioning', 'Composure'] },
      { key: 'CML', pos: 'CM', group: 'MID', x: 54, y: 64, name: 'Left Interior', desc: 'Arrives into the penalty box from the second line.', attrs: ['Dribbling', 'Shooting', 'Work Rate', 'Passing'] },
      { key: 'LWB', pos: 'LWB', group: 'MID', x: 52, y: 86, name: 'Left Wing-Back', desc: 'Relentless runner stretching the opposition defense.', attrs: ['Stamina', 'Crossing', 'Speed', 'Tackling'] },
      { key: 'STR', pos: 'ST', group: 'FWD', x: 83, y: 37, name: 'Mobile Striker', desc: 'Attacks space in behind and presses the build-up.', attrs: ['Pace', 'Finishing', 'Movement', 'Dribbling'] },
      { key: 'STL', pos: 'ST', group: 'FWD', x: 83, y: 63, name: 'Penalty-Box Striker', desc: 'Combines closely with strike partner to finish attacks.', attrs: ['Finishing', 'Hold-up', 'Strength', 'Heading'] },
    ],
    connections: [[0, 2], [1, 2], [2, 3], [1, 4], [1, 5], [2, 6], [3, 7], [3, 8], [5, 6], [6, 7], [5, 9], [7, 10], [9, 10]],
  },
};

export function FormationViewer({ players }: { players: PlayerWithStats[] }) {
  const [formationKey, setFormationKey] = useState<string>('433');
  const [selectedSlotIdx, setSelectedSlotIdx] = useState<number>(9); // Default to Striker
  const [customOverrides, setCustomOverrides] = useState<Record<string, number>>({});

  const formation = FORMATIONS[formationKey] || FORMATIONS['433'];

  // Auto-assign 11 players to the formation slots
  const assignedPlayers = useMemo(() => {
    const usedIds = new Set<number>();
    const result: (PlayerWithStats | null)[] = new Array(formation.positions.length).fill(null);

    // 1. Apply manual overrides first
    formation.positions.forEach((slot, idx) => {
      const overrideId = customOverrides[`${formationKey}:${slot.key}`];
      if (overrideId) {
        const found = players.find((p) => p.id === overrideId);
        if (found && !usedIds.has(found.id)) {
          result[idx] = found;
          usedIds.add(found.id);
        }
      }
    });

    // 2. Match by natural group (GK, DEF, MID, FWD)
    formation.positions.forEach((slot, idx) => {
      if (result[idx]) return;
      const candidate = players.find((p) => p.group === slot.group && !usedIds.has(p.id));
      if (candidate) {
        result[idx] = candidate;
        usedIds.add(candidate.id);
      }
    });

    // 3. Fill any remaining slot with available squad players
    formation.positions.forEach((_, idx) => {
      if (result[idx]) return;
      const fallback = players.find((p) => !usedIds.has(p.id));
      if (fallback) {
        result[idx] = fallback;
        usedIds.add(fallback.id);
      }
    });

    return result;
  }, [formation, formationKey, players, customOverrides]);

  const benchPlayers = useMemo(() => {
    const activeIds = new Set(assignedPlayers.filter(Boolean).map((p) => p!.id));
    return players.filter((p) => !activeIds.has(p.id));
  }, [players, assignedPlayers]);

  const activeSlot = formation.positions[selectedSlotIdx] || formation.positions[0];
  const activePlayer = assignedPlayers[selectedSlotIdx] || null;

  const handleSubstitute = (benchPlayerId: number) => {
    if (!activeSlot) return;
    setCustomOverrides((prev) => ({
      ...prev,
      [`${formationKey}:${activeSlot.key}`]: benchPlayerId,
    }));
  };

  return (
    <section id="formation" className="py-16 bg-vcf-dark border-b border-vcf-border">
      <div className="max-w-7xl mx-auto px-4 sm:px-6">
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
          <div>
            <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1">
              Interactive Tactical Lab
            </div>
            <h2 className="font-display text-3xl sm:text-4xl font-bold uppercase text-white">
              Know Your <span className="text-vcf-orange italic">Role</span>
            </h2>
            <p className="text-xs sm:text-sm text-gray-400 mt-1 max-w-xl">
              Tap any position on the pitch to inspect its tactical responsibilities in the Valencia CF system, or click a bench player to make a live substitution.
            </p>
          </div>

          {/* Formation Tabs */}
          <div className="flex flex-wrap items-center gap-2">
            {Object.entries(FORMATIONS).map(([key, def]) => (
              <button
                key={key}
                type="button"
                onClick={() => {
                  setFormationKey(key);
                  setSelectedSlotIdx(0);
                }}
                className={`px-3.5 py-2 rounded-xl font-display font-bold text-xs uppercase tracking-wider transition-all ${
                  formationKey === key
                    ? 'bg-vcf-orange text-black shadow-[0_0_15px_rgba(255,102,0,0.35)]'
                    : 'bg-vcf-surface border border-vcf-border text-gray-300 hover:text-white'
                }`}
              >
                {def.label}
              </button>
            ))}
            <button
              type="button"
              onClick={() => setCustomOverrides({})}
              title="Reset Lineup"
              className="p-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 hover:text-vcf-orange transition-colors"
            >
              <RotateCcw className="w-4 h-4" />
            </button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          {/* Pitch SVG & Interactive Dots */}
          <div className="lg:col-span-8 space-y-4">
            <div className="relative w-full aspect-[16/10] rounded-2xl bg-gradient-to-br from-[#0F291A] via-[#0B2014] to-[#08170E] border-2 border-emerald-500/25 shadow-2xl overflow-hidden select-none">
              {/* Pitch markings SVG */}
              <svg
                className="absolute inset-0 w-full h-full pointer-events-none"
                viewBox="0 0 100 60"
                preserveAspectRatio="none"
              >
                {/* Turf stripes */}
                {[0, 1, 2, 3, 4, 5, 6, 7, 8, 9].map((i) => (
                  <rect
                    key={i}
                    x={i * 10}
                    y="0"
                    width="10"
                    height="60"
                    fill={i % 2 === 0 ? 'rgba(255,255,255,0.015)' : 'transparent'}
                  />
                ))}
                {/* Outer boundary */}
                <rect x="2" y="2" width="96" height="56" fill="none" stroke="rgba(255,255,255,0.22)" strokeWidth="0.4" />
                {/* Halfway line & circle */}
                <line x1="50" y1="2" x2="50" y2="58" stroke="rgba(255,255,255,0.22)" strokeWidth="0.4" />
                <circle cx="50" cy="30" r="8.5" fill="none" stroke="rgba(255,255,255,0.22)" strokeWidth="0.4" />
                <circle cx="50" cy="30" r="0.6" fill="rgba(255,255,255,0.4)" />
                {/* Penalty areas */}
                <rect x="2" y="13" width="15" height="34" fill="none" stroke="rgba(255,255,255,0.2)" strokeWidth="0.4" />
                <rect x="83" y="13" width="15" height="34" fill="none" stroke="rgba(255,255,255,0.2)" strokeWidth="0.4" />
                <rect x="2" y="21" width="5.5" height="18" fill="none" stroke="rgba(255,255,255,0.18)" strokeWidth="0.4" />
                <rect x="92.5" y="21" width="5.5" height="18" fill="none" stroke="rgba(255,255,255,0.18)" strokeWidth="0.4" />

                {/* Tactical Passing Links */}
                {formation.connections.map(([a, b], idx) => {
                  const pA = formation.positions[a];
                  const pB = formation.positions[b];
                  if (!pA || !pB) return null;
                  const isHighlighted = a === selectedSlotIdx || b === selectedSlotIdx;
                  return (
                    <line
                      key={idx}
                      x1={pA.x}
                      y1={(pA.y * 60) / 100}
                      x2={pB.x}
                      y2={(pB.y * 60) / 100}
                      stroke={isHighlighted ? 'rgba(255, 102, 0, 0.65)' : 'rgba(255, 255, 255, 0.12)'}
                      strokeWidth={isHighlighted ? '0.55' : '0.3'}
                      strokeDasharray={isHighlighted ? 'none' : '1,1'}
                    />
                  );
                })}
              </svg>

              {/* Player Nodes */}
              {formation.positions.map((slot, idx) => {
                const p = assignedPlayers[idx];
                const isSelected = idx === selectedSlotIdx;
                return (
                  <button
                    key={slot.key}
                    type="button"
                    onClick={() => setSelectedSlotIdx(idx)}
                    style={{ left: `${slot.x}%`, top: `${slot.y}%` }}
                    className={`absolute -translate-x-1/2 -translate-y-1/2 flex flex-col items-center group focus:outline-none transition-transform ${
                      isSelected ? 'scale-110 z-20' : 'hover:scale-105 z-10'
                    }`}
                  >
                    <div
                      className={`w-9 h-9 sm:w-11 sm:h-11 rounded-full flex items-center justify-center font-display font-bold text-xs sm:text-sm border-2 transition-all shadow-lg ${
                        isSelected
                          ? 'bg-vcf-orange text-black border-white ring-4 ring-vcf-orange/40'
                          : slot.group === 'GK'
                          ? 'bg-amber-500 text-black border-white/80'
                          : 'bg-[#121216]/95 text-white border-vcf-orange group-hover:border-white'
                      }`}
                    >
                      {p?.dorsal ?? slot.pos}
                    </div>
                    <div
                      className={`mt-1 px-2 py-0.5 rounded text-[10px] font-bold tracking-wide whitespace-nowrap backdrop-blur-sm ${
                        isSelected
                          ? 'bg-vcf-orange text-black'
                          : 'bg-black/75 text-gray-100 border border-white/10'
                      }`}
                    >
                      {p ? `${p.nombre.split(' ')[0]} ${p.apellido.charAt(0)}.` : slot.pos}
                    </div>
                  </button>
                );
              })}
            </div>

            {/* Bench Substitution Strip */}
            {benchPlayers.length > 0 && (
              <div className="rounded-xl bg-vcf-surface border border-vcf-border p-4">
                <div className="flex items-center justify-between gap-2 mb-3">
                  <span className="text-xs font-bold uppercase tracking-wider text-white flex items-center gap-1.5">
                    <ArrowLeftRight className="w-3.5 h-3.5 text-vcf-orange" />
                    Squad Bench ({benchPlayers.length})
                  </span>
                  <span className="text-[11px] text-gray-400">
                    Click a bench player to sub into <strong className="text-vcf-orange">{activeSlot?.pos}</strong>
                  </span>
                </div>
                <div className="flex flex-wrap gap-2">
                  {benchPlayers.map((bp) => (
                    <button
                      key={bp.id}
                      type="button"
                      onClick={() => handleSubstitute(bp.id)}
                      className="px-3 py-1.5 rounded-lg bg-vcf-card hover:bg-vcf-orange/20 border border-vcf-border hover:border-vcf-orange/50 text-xs text-gray-200 flex items-center gap-2 transition-all"
                    >
                      <span className="font-display font-bold text-vcf-orange">
                        #{bp.dorsal ?? '–'}
                      </span>
                      <span className="font-medium">
                        {bp.nombre} {bp.apellido.split(' ')[0]}
                      </span>
                      <span className="text-[10px] px-1.5 py-0.2 rounded bg-white/5 text-gray-400">
                        {bp.pos_short}
                      </span>
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Right Column: Tactical Role Inspector */}
          <div className="lg:col-span-4">
            <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-5 sticky top-28">
              <div className="flex items-center justify-between border-b border-white/10 pb-4">
                <div>
                  <span className="px-2.5 py-0.5 rounded-md bg-vcf-orange/20 text-vcf-orange font-display font-bold text-xs uppercase tracking-wider">
                    {activeSlot.pos} · {formation.label}
                  </span>
                  <h3 className="font-display text-2xl font-bold uppercase text-white mt-2">
                    {activeSlot.name}
                  </h3>
                </div>
                <div className="w-12 h-12 rounded-xl bg-vcf-card border border-vcf-border flex items-center justify-center font-display text-xl font-bold text-vcf-orange">
                  {activePlayer?.dorsal ? `#${activePlayer.dorsal}` : activeSlot.pos}
                </div>
              </div>

              {activePlayer && (
                <div className="p-3.5 rounded-xl bg-vcf-card border border-white/10 flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-vcf-orange/20 border border-vcf-orange text-vcf-orange font-display font-bold text-sm flex items-center justify-center">
                      {activePlayer.initials}
                    </div>
                    <div>
                      <div className="text-[10px] uppercase tracking-wider text-gray-400">Assigned Player</div>
                      <div className="font-display text-base font-bold text-white">
                        {activePlayer.nombre} {activePlayer.apellido}
                      </div>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="font-display text-lg font-bold text-vcf-gold">{activePlayer.overall}</div>
                    <div className="text-[9px] uppercase tracking-widest text-gray-400">OVR</div>
                  </div>
                </div>
              )}

              <div>
                <h4 className="text-[11px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">
                  Tactical Role in VCF System
                </h4>
                <p className="text-xs sm:text-sm text-gray-300 leading-relaxed">
                  {activeSlot.desc}
                </p>
              </div>

              <div>
                <h4 className="text-[11px] font-bold uppercase tracking-widest text-gray-400 mb-2">
                  Key Attributes Required
                </h4>
                <div className="flex flex-wrap gap-2">
                  {activeSlot.attrs.map((attr) => (
                    <span
                      key={attr}
                      className="px-3 py-1 rounded-lg bg-black/50 border border-vcf-orange/30 text-xs font-semibold text-vcf-orange flex items-center gap-1"
                    >
                      <Sparkles className="w-3 h-3" />
                      {attr}
                    </span>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
