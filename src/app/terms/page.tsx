import React from 'react';
import { getSiteSettings } from '@/lib/queries';
import { getSessionUser } from '@/lib/auth';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';

export default async function TermsPage() {
  const user = await getSessionUser();
  const settings = getSiteSettings();

  return (
    <>
      <Navbar isAdminLoggedIn={Boolean(user)} />
      <main className="flex-1 py-14 bg-vcf-dark">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 space-y-6">
          <h1 className="font-display text-4xl font-bold uppercase text-white">
            Terms of <span className="text-vcf-orange">Use</span>
          </h1>
          <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 sm:p-8 space-y-4 text-xs sm:text-sm text-gray-300 leading-relaxed">
            <p>
              Welcome to the official digital platform of <strong>VCF Academy Houston</strong>. By accessing and using this website, you agree to use its features—including match schedules, community polls, and registration forms—in good faith and in accordance with the values of Valencia CF.
            </p>
            <p>
              All academy logos, crests, photographs, and training content are the property of their respective owners and may not be reproduced commercially without prior written consent.
            </p>
          </div>
        </div>
      </main>
      <Footer settings={settings} />
    </>
  );
}
