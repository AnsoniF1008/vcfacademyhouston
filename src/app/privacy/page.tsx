import React from 'react';
import { getSiteSettings } from '@/lib/queries';
import { getSessionUser } from '@/lib/auth';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';

export default async function PrivacyPage() {
  const user = await getSessionUser();
  const settings = getSiteSettings();

  return (
    <>
      <Navbar isAdminLoggedIn={Boolean(user)} />
      <main className="flex-1 py-14 bg-vcf-dark">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 space-y-6">
          <h1 className="font-display text-4xl font-bold uppercase text-white">
            Privacy <span className="text-vcf-orange">Policy</span>
          </h1>
          <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 sm:p-8 space-y-4 text-xs sm:text-sm text-gray-300 leading-relaxed">
            <p>
              <strong>VCF Academy Houston</strong> respects the privacy of our players, parents, and visitors. Information submitted through our Join the Academy or Contact forms (such as parent name, email, phone number, and child age group) is used strictly by academy staff to respond to your inquiry and coordinate tryouts or registration.
            </p>
            <p>
              We do not sell, rent, or share personal contact data with third-party marketers. Anonymous technical identifiers (such as IP hashes) are used solely to prevent duplicate voting in community polls (Man of the Match) and protect forms from automated spam.
            </p>
          </div>
        </div>
      </main>
      <Footer settings={settings} />
    </>
  );
}
