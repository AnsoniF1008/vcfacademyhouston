import React from 'react';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import { getDb } from '@/lib/db';
import { getSiteSettings } from '@/lib/queries';
import { getSessionUser } from '@/lib/auth';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';
import { ArrowLeft, Calendar, User, Eye } from 'lucide-react';

export const dynamic = 'force-dynamic';

export default async function NewsArticlePage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const user = await getSessionUser();
  const settings = getSiteSettings();
  const db = getDb();

  const article = db.prepare(`
    SELECT n.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug, c.color AS categoria_color
    FROM noticias n
    LEFT JOIN noticias_categorias c ON c.id = n.categoria_id
    WHERE n.slug = ?
  `).get(slug) as Record<string, unknown> | undefined;

  if (!article) notFound();

  db.prepare('UPDATE noticias SET views = views + 1 WHERE id = ?').run(Number(article.id));

  return (
    <>
      <Navbar isAdminLoggedIn={Boolean(user)} />

      <main className="flex-1 py-12 bg-vcf-dark">
        <article className="max-w-4xl mx-auto px-4 sm:px-6 space-y-8">
          <Link
            href="/news"
            className="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400 hover:text-vcf-orange transition-colors"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            Back to All News
          </Link>

          <div className="space-y-4">
            {Boolean(article.categoria_nombre) && (
              <span
                className="inline-block px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wider text-black"
                style={{ backgroundColor: String(article.categoria_color || '#FF6600') }}
              >
                {String(article.categoria_nombre)}
              </span>
            )}
            <h1 className="font-display text-3xl sm:text-5xl font-bold uppercase text-white leading-tight">
              {String(article.titulo)}
            </h1>
            <div className="flex flex-wrap items-center gap-5 text-xs text-gray-400">
              <span className="flex items-center gap-1.5">
                <Calendar className="w-3.5 h-3.5 text-vcf-orange" />
                {String(article.fecha_publicacion || '').slice(0, 10)}
              </span>
              {Boolean(article.autor) && (
                <span className="flex items-center gap-1.5">
                  <User className="w-3.5 h-3.5 text-vcf-orange" />
                  {String(article.autor)}
                </span>
              )}
              <span className="flex items-center gap-1.5">
                <Eye className="w-3.5 h-3.5 text-vcf-orange" />
                {Number(article.views || 0) + 1} views
              </span>
            </div>
          </div>

          <div className="rounded-3xl overflow-hidden border border-vcf-border bg-vcf-surface">
            <img
              src={String(article.imagen_destacada || '/assets/img/news-placeholder-editorial.jpg')}
              alt={String(article.titulo)}
              className="w-full max-h-[420px] object-cover"
            />
            <div className="p-6 sm:p-10 space-y-6">
              {Boolean(article.resumen) && (
                <p className="text-base sm:text-lg font-medium text-vcf-orange/90 border-l-4 border-vcf-orange pl-4">
                  {String(article.resumen)}
                </p>
              )}

              <div
                className="prose prose-invert max-w-none text-sm sm:text-base text-gray-200 leading-relaxed space-y-4"
                dangerouslySetInnerHTML={{
                  __html: String(article.contenido || '').replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, ''),
                }}
              />
            </div>
          </div>
        </article>
      </main>

      <Footer settings={settings} />
    </>
  );
}
