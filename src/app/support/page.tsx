import React from 'react';
import Link from 'next/link';
import { getSiteSettings } from '@/lib/queries';
import { getSessionUser } from '@/lib/auth';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';
import { CopyZelleButton } from '@/components/CopyZelleButton';
import { Heart, Server, Globe, Sparkles, ShieldCheck, ExternalLink } from 'lucide-react';

export const dynamic = 'force-dynamic';

export default async function SupportPage() {
  const user = await getSessionUser();
  const settings = getSiteSettings();

  const rawContributions = settings.donation_parent_contributions || '';
  const contributions = rawContributions
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => {
      const parts = line.split('|').map((s) => s.trim());
      if (parts.length >= 3) {
        return { date: parts[0], name: parts[1], amount: parts[2] };
      }
      return { date: '—', name: parts[0] || 'Supporter', amount: parts[1] || '' };
    });

  return (
    <>
      <Navbar isAdminLoggedIn={Boolean(user)} />

      <main className="flex-1 py-12 bg-vcf-dark">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 space-y-12">
          {/* Hero */}
          <div className="rounded-3xl bg-gradient-to-r from-[#1D120B] via-vcf-surface to-vcf-card border border-vcf-orange/30 p-8 sm:p-12 space-y-4">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-vcf-orange/15 border border-vcf-orange/30 text-vcf-orange text-xs font-bold uppercase tracking-widest">
              <Heart className="w-3.5 h-3.5" />
              Community Powered Platform
            </div>
            <h1 className="font-display text-4xl sm:text-6xl font-bold uppercase text-white">
              Support the <span className="text-vcf-orange italic">Site</span>
            </h1>
            <p className="text-sm sm:text-base text-gray-300 max-w-2xl leading-relaxed">
              This platform is built and maintained voluntarily for our players, coaches, and families. Every contribution — big or small — goes directly into keeping this website online, fast, and improving.
            </p>
          </div>

          {/* What Your Support Covers */}
          <div className="space-y-4">
            <h2 className="font-display text-xl font-bold uppercase tracking-wider text-white">
              What Your <span className="text-vcf-orange">Support Covers</span>
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-5 space-y-2.5">
                <Server className="w-6 h-6 text-vcf-orange" />
                <h3 className="font-display text-lg font-bold uppercase text-white">Server Hosting</h3>
                <p className="text-xs text-gray-400 leading-relaxed">
                  High-speed cloud hosting to keep match schedules, stats, and reels online 24/7.
                </p>
                {settings.donation_hosting_due_date && (
                  <div className="pt-2 border-t border-white/10 text-[11px] text-gray-300">
                    <strong>Next Renewal:</strong> {settings.donation_hosting_due_date}
                  </div>
                )}
                {settings.donation_hosting_paid_amount && (
                  <div className="text-[11px] text-vcf-orange font-bold">
                    Cost: {settings.donation_hosting_paid_amount}
                  </div>
                )}
              </div>

              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-5 space-y-2.5">
                <Globe className="w-6 h-6 text-vcf-orange" />
                <h3 className="font-display text-lg font-bold uppercase text-white">Official Domain</h3>
                <p className="text-xs text-gray-400 leading-relaxed">
                  Annual renewal of our official web address so families and scouts always find us.
                </p>
                {settings.donation_domain_due_date && (
                  <div className="pt-2 border-t border-white/10 text-[11px] text-gray-300">
                    <strong>Next Renewal:</strong> {settings.donation_domain_due_date}
                  </div>
                )}
                {settings.donation_domain_paid_amount && (
                  <div className="text-[11px] text-vcf-orange font-bold">
                    Cost: {settings.donation_domain_paid_amount}
                  </div>
                )}
              </div>

              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-5 space-y-2.5">
                <Sparkles className="w-6 h-6 text-vcf-orange" />
                <h3 className="font-display text-lg font-bold uppercase text-white">Live Features</h3>
                <p className="text-xs text-gray-400 leading-relaxed">
                  Interactive FIFA player cards, tactical pitch sandbox, MOTM polls, and video reels.
                </p>
              </div>

              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-5 space-y-2.5">
                <ShieldCheck className="w-6 h-6 text-vcf-orange" />
                <h3 className="font-display text-lg font-bold uppercase text-white">100% For Players</h3>
                <p className="text-xs text-gray-400 leading-relaxed">
                  Every dollar is reinvested directly into digital tools for VCF Academy Houston families.
                </p>
              </div>
            </div>
          </div>

          {/* Contribution Methods */}
          <div className="rounded-3xl bg-vcf-surface border border-vcf-border p-6 sm:p-8 space-y-6">
            <h2 className="font-display text-2xl font-bold uppercase text-white">
              How to <span className="text-vcf-orange">Contribute</span>
            </h2>

            {settings.donation_zelle_email && (
              <div className="p-5 rounded-2xl bg-vcf-card border border-vcf-orange/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange">
                    Zelle (Instant · Zero Fees)
                  </div>
                  <div className="font-display text-xl font-bold text-white mt-1">
                    {settings.donation_zelle_email}
                  </div>
                  <p className="text-xs text-gray-400 mt-0.5">
                    Copy this email address into your bank&apos;s Zelle app to send a direct contribution.
                  </p>
                </div>
                <CopyZelleButton email={settings.donation_zelle_email} />
              </div>
            )}

            <div className="flex flex-wrap items-center gap-3">
              {settings.donation_paypal_url && (
                <a
                  href={settings.donation_paypal_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-5 py-3 rounded-xl bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-xs uppercase tracking-wider flex items-center gap-2"
                >
                  <span>Contribute via PayPal</span>
                  <ExternalLink className="w-3.5 h-3.5" />
                </a>
              )}
              {settings.donation_venmo_url && (
                <a
                  href={settings.donation_venmo_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-5 py-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/15 text-white font-display font-bold text-xs uppercase tracking-wider flex items-center gap-2"
                >
                  <span>Venmo</span>
                  <ExternalLink className="w-3.5 h-3.5" />
                </a>
              )}
            </div>

            {settings.donation_other_note && (
              <p className="text-xs text-gray-400 pt-2 border-t border-white/10">
                {settings.donation_other_note}
              </p>
            )}
          </div>

          {/* Parent Contributions Honor Roll */}
          {contributions.length > 0 && (
            <div className="rounded-3xl bg-vcf-surface border border-vcf-border overflow-hidden">
              <div className="p-6 border-b border-vcf-border flex items-center justify-between">
                <div>
                  <h2 className="font-display text-2xl font-bold uppercase text-white">
                    Family &amp; Parent <span className="text-vcf-orange">Contributions</span>
                  </h2>
                  <p className="text-xs text-gray-400 mt-0.5">
                    Thank you to the families helping keep our academy platform running strong!
                  </p>
                </div>
              </div>

              <div className="divide-y divide-white/5">
                {contributions.map((item, idx) => (
                  <div
                    key={idx}
                    className="px-6 py-4 flex items-center justify-between text-xs sm:text-sm hover:bg-white/[0.02]"
                  >
                    <div className="flex items-center gap-4">
                      <span className="text-gray-500 font-mono text-xs">{item.date}</span>
                      <span className="font-bold text-white">{item.name}</span>
                    </div>
                    <span className="font-display text-base font-bold text-vcf-orange">
                      {item.amount}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}

          <div className="text-center">
            <Link
              href="/"
              className="inline-block px-6 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-300"
            >
              &larr; Back to Home
            </Link>
          </div>
        </div>
      </main>

      <Footer settings={settings} />
    </>
  );
}
