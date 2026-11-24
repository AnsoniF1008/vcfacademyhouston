import React from 'react';
import Link from 'next/link';
import { getLatestNews, getNewsCategories, getSiteSettings } from '@/lib/queries';
import { getSessionUser } from '@/lib/auth';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';
import { Newspaper, Eye, Calendar } from 'lucide-react';

export const dynamic = 'force-dynamic';

export default async function NewsListingPage({
  searchParams,
}: {
  searchParams: Promise<{ cat?: string }>;
}) {
  const sp = await searchParams;
  const user = await getSessionUser();
  const settings = getSiteSettings();
  const categories = getNewsCategories();
  const activeCat = sp.cat || undefined;
  const articles = getLatestNews(50, activeCat);

  return (
    <>
      <Navbar isAdminLoggedIn={Boolean(user)} />

      <main className="flex-1 py-12 bg-vcf-dark">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 space-y-8">
          <div>
            <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1 flex items-center gap-1.5">
              <Newspaper className="w-3.5 h-3.5" />
              Official Academy Press
            </div>
            <h1 className="font-display text-3xl sm:text-5xl font-bold uppercase text-white">
              Academy <span className="text-vcf-orange italic">News &amp; Stories</span>
            </h1>
            <p className="text-xs sm:text-sm text-gray-400 mt-1">
              Match recaps, tournament coverage, training methodology, and official announcements.
            </p>
          </div>

          {/* Category Filter Chips */}
          <div className="flex flex-wrap items-center gap-2">
            <Link
              href="/news"
              className={`px-4 py-2 rounded-xl font-display font-bold text-xs uppercase tracking-wider transition-all ${
                !activeCat
                  ? 'bg-vcf-orange text-black'
                  : 'bg-vcf-surface border border-vcf-border text-gray-300 hover:text-white'
              }`}
            >
              All Articles
            </Link>
            {categories.map((cat) => {
              const selected = activeCat === cat.slug;
              return (
                <Link
                  key={cat.id}
                  href={`/news?cat=${cat.slug}`}
                  className={`px-4 py-2 rounded-xl font-display font-bold text-xs uppercase tracking-wider transition-all ${
                    selected
                      ? 'bg-vcf-orange text-black'
                      : 'bg-vcf-surface border border-vcf-border text-gray-300 hover:text-white'
                  }`}
                >
                  {cat.nombre}
                </Link>
              );
            })}
          </div>

          {/* Articles Grid */}
          {articles.length === 0 ? (
            <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-12 text-center">
              <p className="text-sm text-gray-400">No articles found in this category yet.</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {articles.map((item) => (
                <Link
                  key={String(item.id)}
                  href={`/news/${item.slug}`}
                  className="group rounded-2xl bg-vcf-surface border border-vcf-border hover:border-vcf-orange/60 overflow-hidden flex flex-col justify-between transition-all hover:-translate-y-1"
                >
                  <div>
                    <div className="relative h-52 bg-vcf-card overflow-hidden">
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
                      <h2 className="font-display text-xl font-bold uppercase text-white group-hover:text-vcf-orange transition-colors line-clamp-2">
                        {String(item.titulo)}
                      </h2>
                      {Boolean(item.resumen) && (
                        <p className="text-xs text-gray-400 line-clamp-3 leading-relaxed">
                          {String(item.resumen)}
                        </p>
                      )}
                    </div>
                  </div>
                  <div className="px-5 pb-4 pt-3 border-t border-white/5 flex items-center justify-between text-[11px] text-gray-400">
                    <span className="flex items-center gap-1">
                      <Calendar className="w-3 h-3 text-vcf-orange" />
                      {String(item.fecha_publicacion || '').slice(0, 10)}
                    </span>
                    <span className="flex items-center gap-1">
                      <Eye className="w-3 h-3 text-vcf-orange" />
                      {Number(item.views || 0)} views
                    </span>
                  </div>
                </Link>
              ))}
            </div>
          )}
        </div>
      </main>

      <Footer settings={settings} />
    </>
  );
}
