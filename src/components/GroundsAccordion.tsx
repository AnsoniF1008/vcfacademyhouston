'use client';

import React, { useState } from 'react';
import { MapPin, ChevronDown, Info, Compass } from 'lucide-react';
import type { Sede } from '@/lib/queries';

export function GroundsAccordion({ sedes }: { sedes: Sede[] }) {
  const [openId, setOpenId] = useState<number>(sedes[0]?.id ?? 0);

  return (
    <section id="grounds" className="py-16 bg-vcf-surface border-b border-vcf-border">
      <div className="max-w-7xl mx-auto px-4 sm:px-6">
        <div className="mb-8">
          <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1">
            Official Training Locations
          </div>
          <h2 className="font-display text-3xl sm:text-4xl font-bold uppercase text-white">
            Training <span className="text-vcf-orange italic">Grounds &amp; Pitches</span>
          </h2>
          <p className="text-xs sm:text-sm text-gray-400 mt-1 max-w-2xl">
            We bring the Mestalla and Paterna experience to your neighborhood. Find our official training venues across Katy and Houston, equipped with top-tier facilities for our youth categories.
          </p>
        </div>

        <div className="space-y-4">
          {sedes.map((sede) => {
            const isOpen = openId === sede.id;
            return (
              <div
                key={sede.id}
                className="rounded-2xl bg-vcf-card border border-vcf-border overflow-hidden transition-all"
              >
                <div className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <button
                    type="button"
                    onClick={() => setOpenId(isOpen ? 0 : sede.id)}
                    className="flex items-start sm:items-center gap-3.5 text-left flex-1 focus:outline-none"
                  >
                    <div className="w-10 h-10 rounded-xl bg-vcf-orange/15 border border-vcf-orange/30 flex items-center justify-center text-vcf-orange shrink-0">
                      <MapPin className="w-5 h-5" />
                    </div>
                    <div>
                      <h3 className="font-display text-xl font-bold uppercase text-white flex items-center gap-2">
                        <span>{sede.nombre}</span>
                      </h3>
                      <p className="text-xs text-gray-400 mt-0.5">{sede.direccion}</p>
                    </div>
                  </button>

                  <div className="flex items-center gap-2.5 self-end sm:self-auto">
                    {sede.mapa_general_url && (
                      <a
                        href={sede.mapa_general_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="px-3.5 py-2 rounded-xl bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-xs uppercase tracking-wider flex items-center gap-1.5 transition-all"
                      >
                        <Compass className="w-3.5 h-3.5" />
                        Open GPS
                      </a>
                    )}
                    <button
                      type="button"
                      onClick={() => setOpenId(isOpen ? 0 : sede.id)}
                      className="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-gray-300"
                      aria-label="Toggle Venue Details"
                    >
                      <ChevronDown
                        className={`w-4 h-4 transition-transform duration-200 ${
                          isOpen ? 'rotate-180 text-vcf-orange' : ''
                        }`}
                      />
                    </button>
                  </div>
                </div>

                {isOpen && (
                  <div className="px-5 pb-5 pt-2 border-t border-white/10 space-y-4">
                    {sede.nota_acceso && (
                      <div className="p-3.5 rounded-xl bg-vcf-orange/10 border border-vcf-orange/25 flex items-start gap-2.5 text-xs text-gray-200">
                        <Info className="w-4 h-4 text-vcf-orange shrink-0 mt-0.5" />
                        <span>{sede.nota_acceso}</span>
                      </div>
                    )}

                    {sede.canchas.length > 0 ? (
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {sede.canchas.map((c) => (
                          <div
                            key={c.id}
                            className="p-4 rounded-xl bg-vcf-surface border border-vcf-border flex items-center justify-between gap-3"
                          >
                            <div>
                              <div className="font-display text-sm font-bold uppercase text-white">
                                {c.numero_cancha}
                                {c.sobrenombre && (
                                  <span className="ml-2 text-vcf-orange font-semibold">
                                    ({c.sobrenombre})
                                  </span>
                                )}
                              </div>
                              {c.indicaciones_extra && (
                                <p className="text-xs text-gray-400 mt-1">{c.indicaciones_extra}</p>
                              )}
                            </div>
                            {c.mapa_url && (
                              <a
                                href={c.mapa_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-vcf-orange hover:text-black border border-white/10 text-xs font-semibold text-gray-200 shrink-0 transition-colors"
                              >
                                Pitch Pin &rarr;
                              </a>
                            )}
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="text-xs text-gray-400">
                        Use the main entrance GPS button above for directions.
                      </p>
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
