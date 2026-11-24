import React from 'react';
import { getSiteSettings } from '@/lib/queries';
import { getSessionUser } from '@/lib/auth';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';
import { ContactFormClient } from '@/components/ContactFormClient';
import { Mail, Phone, MapPin } from 'lucide-react';

export const dynamic = 'force-dynamic';

export default async function ContactPage() {
  const user = await getSessionUser();
  const settings = getSiteSettings();

  return (
    <>
      <Navbar isAdminLoggedIn={Boolean(user)} />

      <main className="flex-1 py-12 bg-vcf-dark">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 space-y-10">
          <div>
            <div className="text-xs font-bold uppercase tracking-widest text-vcf-orange mb-1">
              Get in Touch
            </div>
            <h1 className="font-display text-3xl sm:text-5xl font-bold uppercase text-white">
              Contact <span className="text-vcf-orange italic">VCF Academy Houston</span>
            </h1>
            <p className="text-xs sm:text-sm text-gray-400 mt-2 max-w-2xl">
              Have questions about our youth development programs, training schedules, or upcoming tournaments? Reach out to our team directly.
            </p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div className="lg:col-span-4 space-y-4">
              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                <h2 className="font-display text-lg font-bold uppercase text-vcf-orange">
                  Direct Contact
                </h2>
                <div className="space-y-3 text-xs text-gray-300">
                  {settings.public_email && (
                    <a
                      href={`mailto:${settings.public_email}`}
                      className="flex items-center gap-3 p-3 rounded-xl bg-vcf-card border border-vcf-border hover:border-vcf-orange/50"
                    >
                      <Mail className="w-4 h-4 text-vcf-orange shrink-0" />
                      <span>{settings.public_email}</span>
                    </a>
                  )}
                  {settings.phone && (
                    <a
                      href={`tel:${settings.phone.replace(/[^\d+]/g, '')}`}
                      className="flex items-center gap-3 p-3 rounded-xl bg-vcf-card border border-vcf-border hover:border-vcf-orange/50"
                    >
                      <Phone className="w-4 h-4 text-vcf-orange shrink-0" />
                      <span>{settings.phone}</span>
                    </a>
                  )}
                  <div className="flex items-start gap-3 p-3 rounded-xl bg-vcf-card border border-vcf-border">
                    <MapPin className="w-4 h-4 text-vcf-orange shrink-0 mt-0.5" />
                    <div>
                      <div className="font-bold text-white">B13 Sports Complex</div>
                      <div className="text-gray-400 mt-0.5">2203 N Westgreen Blvd, Katy, TX 77449</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="lg:col-span-8">
              <ContactFormClient />
            </div>
          </div>
        </div>
      </main>

      <Footer settings={settings} />
    </>
  );
}
