'use client';

import React, { useState } from 'react';
import { Heart, Eye, Play, Film } from 'lucide-react';

interface ReelItem {
  id: number;
  video_url: string;
  caption: string | null;
  orden: number;
  view_count: number;
  like_count: number;
}

export function ReelsCarousel({ initialReels }: { initialReels: ReelItem[] }) {
  const [reels, setReels] = useState(initialReels);
  const [likedIds, setLikedIds] = useState<Set<number>>(new Set());

  if (!reels || reels.length === 0) return null;

  const handleInteraction = async (reelId: number, action: 'view' | 'like') => {
    try {
      let fp = localStorage.getItem('vcf_device_fp');
      if (!fp) {
        fp = Math.random().toString(36).substring(2) + Date.now().toString(36);
        localStorage.setItem('vcf_device_fp', fp);
      }
      if (action === 'like') {
        setLikedIds((prev) => new Set(prev).add(reelId));
      }
      const res = await fetch('/api/public/reel-stats', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reel_id: reelId, action, fingerprint: fp }),
      });
      const data = await res.json();
      if (data.ok) {
        setReels((prev) =>
          prev.map((r) =>
            r.id === reelId ? { ...r, view_count: data.view_count, like_count: data.like_count } : r
          )
        );
      }
    } catch {
      // ignore
    }
  };

  return (
    <section id="reels" className="py-16 bg-vcf-dark border-b border-vcf-border">
      <div className="max-w-7xl mx-auto px-4 sm:px-6">
        <div className="flex items-center justify-between mb-8">
          <div>
            <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1 flex items-center gap-1.5">
              <Film className="w-3.5 h-3.5" />
              Highlights &amp; Skills
            </div>
            <h2 className="font-display text-3xl sm:text-4xl font-bold uppercase text-white">
              Match <span className="text-vcf-orange italic">Reels</span>
            </h2>
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
          {reels.map((r, idx) => {
            const caption = r.caption || `Match Highlight #${idx + 1}`;
            const lower = caption.toLowerCase();
            const badge = lower.includes('gol')
              ? 'GOLAZO'
              : lower.includes('train') || lower.includes('entren')
              ? 'TRAINING'
              : 'HIGHLIGHT';

            return (
              <div
                key={r.id}
                className="group rounded-2xl bg-vcf-surface border border-vcf-border overflow-hidden flex flex-col justify-between"
              >
                <div className="relative aspect-[9/14] bg-black overflow-hidden">
                  <video
                    src={r.video_url}
                    controls
                    playsInline
                    preload="metadata"
                    onPlay={() => handleInteraction(r.id, 'view')}
                    className="w-full h-full object-cover"
                  />
                  <span className="absolute top-3 left-3 px-2.5 py-1 rounded-md bg-vcf-orange text-black font-display font-bold text-[10px] uppercase tracking-wider pointer-events-none">
                    {badge}
                  </span>
                </div>

                <div className="p-4 flex items-center justify-between gap-2">
                  <div className="text-xs font-semibold text-white truncate" title={caption}>
                    {caption}
                  </div>
                  <div className="flex items-center gap-3 shrink-0 text-xs text-gray-400">
                    <span className="flex items-center gap-1">
                      <Eye className="w-3.5 h-3.5" />
                      {r.view_count}
                    </span>
                    <button
                      type="button"
                      onClick={() => handleInteraction(r.id, 'like')}
                      className={`flex items-center gap-1 transition-colors ${
                        likedIds.has(r.id) ? 'text-vcf-orange font-bold' : 'hover:text-vcf-orange'
                      }`}
                    >
                      <Heart className="w-3.5 h-3.5 fill-current" />
                      {r.like_count}
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
