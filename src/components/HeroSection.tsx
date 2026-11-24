'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { Calendar, ChevronLeft, ChevronRight, MapPin, ArrowRight, Flame } from 'lucide-react';
import type { MatchRow } from '@/lib/queries';

interface HeroSlide {
  id: number;
  image_url: string;
  title: string;
  subtitle: string | null;
  button_text: string | null;
  button_url: string | null;
}

export function HeroSection({
  slides,
  nextMatch,
}: {
  slides: HeroSlide[];
  nextMatch: MatchRow | null;
}) {
  const [currentSlide, setCurrentSlide] = useState(0);
  const [countdown, setCountdown] = useState({ d: '--', h: '--', m: '--', s: '--', isLive: false });

  useEffect(() => {
    if (slides.length <= 1) return;
    const timer = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slides.length);
    }, 6000);
    return () => clearInterval(timer);
  }, [slides.length]);

  useEffect(() => {
    if (!nextMatch) return;
    const timeStr = nextMatch.hora ? `${nextMatch.hora}:00`.slice(0, 8) : '12:00:00';
    const target = new Date(`${nextMatch.fecha}T${timeStr}`).getTime();
    if (isNaN(target)) return;

    const pad = (n: number) => (n < 10 ? `0${n}` : `${n}`);
    const tick = () => {
      const diff = target - Date.now();
      if (diff <= 0) {
        setCountdown({ d: '00', h: '00', m: '00', s: '00', isLive: true });
        return;
      }
      setCountdown({
        d: pad(Math.floor(diff / 86400000)),
        h: pad(Math.floor((diff % 86400000) / 3600000)),
        m: pad(Math.floor((diff % 3600000) / 60000)),
        s: pad(Math.floor((diff % 60000) / 1000)),
        isLive: false,
      });
    };
    tick();
    const interval = setInterval(tick, 1000);
    return () => clearInterval(interval);
  }, [nextMatch]);

  const activeSlide = slides[currentSlide] || null;

  return (
    <section id="hero" className="relative min-h-[600px] lg:min-h-[660px] flex items-center overflow-hidden border-b border-vcf-border">
      {/* Background Slides */}
      {slides.length > 0 ? (
        slides.map((s, idx) => (
          <div
            key={s.id}
            className={`absolute inset-0 bg-cover bg-center transition-all duration-1000 ease-out ${
              idx === currentSlide ? 'opacity-35 scale-100' : 'opacity-0 scale-105'
            }`}
            style={{ backgroundImage: `url('${s.image_url}')` }}
          />
        ))
      ) : (
        <div className="absolute inset-0 bg-gradient-to-br from-[#1C0D06] via-vcf-dark to-black" />
      )}

      {/* Overlays */}
      <div className="absolute inset-0 bg-gradient-to-r from-vcf-dark via-vcf-dark/90 to-vcf-dark/65" />
      <div className="absolute inset-0 bg-vcf-pattern opacity-40 pointer-events-none" />
      <div className="absolute -top-32 -right-32 w-96 h-96 bg-vcf-orange/15 rounded-full blur-3xl pointer-events-none" />

      {/* Main Content Container */}
      <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-16 lg:py-24 w-full grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
        {/* Left Column: Headline & Call to Action */}
        <div className="lg:col-span-7 space-y-6">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-vcf-orange/15 border border-vcf-orange/40 text-vcf-orange text-xs font-bold uppercase tracking-widest">
            <Flame className="w-3.5 h-3.5" />
            Official VCF Academy Program · Houston, TX
          </div>

          <div className="space-y-2">
            <p className="text-xs sm:text-sm font-bold uppercase tracking-[0.25em] text-gray-400">
              Join · Compete · Grow
            </p>
            <h1 className="font-display text-5xl sm:text-6xl lg:text-7xl font-bold uppercase tracking-tight text-white leading-[0.95]">
              VCF Academy{' '}
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-vcf-orange via-vcf-orange-light to-vcf-gold italic">
                Houston
              </span>
            </h1>
          </div>

          <p className="text-base sm:text-lg text-gray-300 max-w-xl leading-relaxed">
            {activeSlide?.subtitle ||
              'Educating People, Training Footballers. Experience the official Valencia CF youth development methodology in Katy and Houston, Texas.'}
          </p>

          <div className="flex flex-wrap items-center gap-3 pt-2">
            <Link
              href={activeSlide?.button_url || '/join'}
              className="px-6 py-3.5 rounded-xl bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-sm uppercase tracking-wider transition-all shadow-[0_0_25px_rgba(255,102,0,0.35)] flex items-center gap-2"
            >
              <span>{activeSlide?.button_text || 'Join the Academy'}</span>
              <ArrowRight className="w-4 h-4" />
            </Link>
            <Link
              href="/#roster"
              className="px-6 py-3.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 text-white font-display font-bold text-sm uppercase tracking-wider transition-all"
            >
              Explore Squad &amp; Stats
            </Link>
          </div>

          {/* Slide Controls */}
          {slides.length > 1 && (
            <div className="flex items-center gap-3 pt-4">
              <button
                type="button"
                onClick={() => setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length)}
                className="w-8 h-8 rounded-lg bg-white/5 hover:bg-vcf-orange hover:text-black border border-white/10 flex items-center justify-center transition-colors"
                aria-label="Previous Slide"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>
              <div className="flex items-center gap-1.5">
                {slides.map((s, idx) => (
                  <button
                    key={s.id}
                    type="button"
                    onClick={() => setCurrentSlide(idx)}
                    className={`h-1.5 rounded-full transition-all ${
                      idx === currentSlide ? 'w-7 bg-vcf-orange' : 'w-2 bg-white/25 hover:bg-white/40'
                    }`}
                    aria-label={`Slide ${idx + 1}`}
                  />
                ))}
              </div>
              <button
                type="button"
                onClick={() => setCurrentSlide((prev) => (prev + 1) % slides.length)}
                className="w-8 h-8 rounded-lg bg-white/5 hover:bg-vcf-orange hover:text-black border border-white/10 flex items-center justify-center transition-colors"
                aria-label="Next Slide"
              >
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          )}
        </div>

        {/* Right Column: Next Match Countdown Card */}
        <div className="lg:col-span-5">
          <div className="rounded-2xl bg-vcf-surface/90 backdrop-blur-xl border border-vcf-border p-6 sm:p-7 shadow-2xl relative overflow-hidden">
            <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-vcf-orange via-vcf-gold to-vcf-orange" />

            <div className="flex items-center justify-between gap-2 mb-4">
              <span className="text-[11px] font-bold uppercase tracking-[0.2em] text-vcf-orange flex items-center gap-1.5">
                <span className="w-2 h-2 rounded-full bg-vcf-orange animate-ping" />
                {countdown.isLive ? 'Matchday Live' : 'Next Official Fixture'}
              </span>
              {nextMatch && (
                <span className="px-2.5 py-0.5 rounded-full bg-white/5 border border-white/10 text-[11px] text-gray-300 font-medium">
                  {nextMatch.categoria || 'U10'} · {nextMatch.nombre_torneo}
                </span>
              )}
            </div>

            {nextMatch ? (
              <>
                <div className="my-5 py-4 px-4 rounded-xl bg-black/40 border border-white/5 flex items-center justify-between gap-3">
                  <div className="flex items-center gap-3">
                    <img src="/assets/img/vcf-crest.png" alt="VCF" className="w-10 h-12 object-contain" />
                    <div>
                      <div className="font-display text-base sm:text-lg font-bold uppercase text-white">
                        VCF Houston
                      </div>
                      <div className="text-[11px] text-vcf-orange font-semibold">Home / Academy</div>
                    </div>
                  </div>

                  <div className="px-3 py-1 rounded-lg bg-vcf-orange/15 border border-vcf-orange/30 font-display text-xs font-bold uppercase text-vcf-orange">
                    VS
                  </div>

                  <div className="text-right">
                    <div className="font-display text-base sm:text-lg font-bold uppercase text-gray-100">
                      {nextMatch.rival || 'TBD'}
                    </div>
                    <div className="text-[11px] text-gray-400">Challenger</div>
                  </div>
                </div>

                {/* Date & Venue */}
                <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-300 mb-5">
                  <div className="flex items-center gap-1.5">
                    <Calendar className="w-3.5 h-3.5 text-vcf-orange" />
                    <span className="font-semibold">{nextMatch.fecha}</span>
                    {nextMatch.hora && <span className="text-gray-400">· {nextMatch.hora}</span>}
                  </div>
                  {(nextMatch.sede_nombre || nextMatch.cancha) && (
                    <div className="flex items-center gap-1.5 text-gray-400">
                      <MapPin className="w-3.5 h-3.5 text-vcf-orange" />
                      <span>{[nextMatch.sede_nombre, nextMatch.cancha].filter(Boolean).join(' · ')}</span>
                    </div>
                  )}
                </div>

                {/* Countdown Grid */}
                <div className="grid grid-cols-4 gap-2.5 mb-6">
                  {[
                    { val: countdown.d, label: 'Days' },
                    { val: countdown.h, label: 'Hours' },
                    { val: countdown.m, label: 'Min' },
                    { val: countdown.s, label: 'Sec' },
                  ].map((item) => (
                    <div
                      key={item.label}
                      className="rounded-xl bg-vcf-card border border-vcf-border py-3 text-center"
                    >
                      <div className="font-display text-2xl sm:text-3xl font-bold text-white tracking-wider">
                        {item.val}
                      </div>
                      <div className="text-[10px] uppercase tracking-widest text-gray-400 mt-0.5">
                        {item.label}
                      </div>
                    </div>
                  ))}
                </div>

                <div className="flex items-center gap-2.5">
                  <Link
                    href={`/match/${nextMatch.id}`}
                    className="flex-1 py-2.5 px-4 rounded-xl bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-xs uppercase tracking-wider text-center transition-all"
                  >
                    Match Center &rarr;
                  </Link>
                  <Link
                    href={`/calendar?id=${nextMatch.id}`}
                    className="py-2.5 px-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 text-xs font-semibold uppercase tracking-wider text-white transition-colors"
                  >
                    + Calendar
                  </Link>
                </div>
              </>
            ) : (
              <div className="py-8 text-center space-y-3">
                <p className="text-sm text-gray-300">No upcoming match scheduled right now.</p>
                <Link
                  href="/#tournaments"
                  className="inline-block px-4 py-2 rounded-lg bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider"
                >
                  View Season Results
                </Link>
              </div>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
