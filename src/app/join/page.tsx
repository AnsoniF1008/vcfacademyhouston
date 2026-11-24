import React from 'react';
import { getCategorias, getSiteSettings } from '@/lib/queries';
import { getSessionUser } from '@/lib/auth';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';
import { JoinFormClient } from '@/components/JoinFormClient';
import { UserPlus } from 'lucide-react';

export const dynamic = 'force-dynamic';

export default async function JoinPage() {
  const user = await getSessionUser();
  const categorias = getCategorias();
  const settings = getSiteSettings();

  return (
    <>
      <Navbar isAdminLoggedIn={Boolean(user)} />

      <main className="flex-1 py-12 bg-vcf-dark">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 space-y-8">
          <div>
            <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1 flex items-center gap-1.5">
              <UserPlus className="w-3.5 h-3.5" />
              Tryouts &amp; Player Registration
            </div>
            <h1 className="font-display text-3xl sm:text-5xl font-bold uppercase text-white">
              Join the <span className="text-vcf-orange italic">Academy</span>
            </h1>
            <p className="text-xs sm:text-sm text-gray-400 mt-2 leading-relaxed">
              Register your interest in joining VCF Academy Houston. Fill out the form below and our coaching staff will contact you regarding evaluation sessions, tryouts, and program details.
            </p>
          </div>

          <JoinFormClient categorias={categorias} />
        </div>
      </main>

      <Footer settings={settings} />
    </>
  );
}
