import React from 'react';
import Link from 'next/link';
import { MapPin, Mail, Phone, ExternalLink, Heart } from 'lucide-react';

export function Footer({ settings = {} }: { settings?: Record<string, string> }) {
  const email = settings.public_email || 'info@vcfacademyhouston.com';
  const phone = settings.phone || '+1 (832) 555-0199';

  return (
    <div className="mt-auto">
      {/* Support Banner Strip */}
      <div className="bg-gradient-to-r from-vcf-surface via-[#1C1410] to-vcf-surface border-t border-b border-vcf-orange/25">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-3.5 text-center sm:text-left">
            <div className="w-10 h-10 rounded-xl bg-vcf-orange/15 border border-vcf-orange/30 flex items-center justify-center text-vcf-orange shrink-0 hidden sm:flex">
              <Heart className="w-5 h-5" />
            </div>
            <div>
              <h3 className="font-display text-lg font-bold uppercase tracking-wide text-white">
                Support the <span className="text-vcf-orange">Academy Platform</span>
              </h3>
              <p className="text-xs text-gray-400">
                Help keep this community hub online — hosting, domain &amp; live features for our players and families.
              </p>
            </div>
          </div>
          <Link
            href="/support"
            className="px-5 py-2.5 rounded-lg bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-xs uppercase tracking-wider transition-all shrink-0"
          >
            Contribute &rarr;
          </Link>
        </div>
      </div>

      {/* Main Footer */}
      <footer className="bg-[#070709] text-gray-400 border-t border-white/5">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-14 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
          {/* Col 1: Brand */}
          <div className="space-y-3">
            <div className="flex items-center gap-3">
              <img src="/assets/img/vcf-crest.png" alt="VCF Crest" className="w-9 h-11 object-contain" />
              <div>
                <div className="font-display text-lg font-bold uppercase text-white tracking-wider">
                  VCF Academy <span className="text-vcf-orange">Houston</span>
                </div>
                <div className="text-[10px] uppercase tracking-widest text-gray-400">
                  Official Valencia CF Program
                </div>
              </div>
            </div>
            <p className="text-xs text-gray-400 leading-relaxed">
              Developing young footballers in Houston &amp; Katy, TX with the official Valencia CF methodology —{' '}
              <span className="text-gray-200 font-medium">Identity, Effort, and Intelligence</span>.
            </p>
            <div className="pt-2 space-y-1.5 text-xs">
              {email && (
                <a href={`mailto:${email}`} className="flex items-center gap-2 hover:text-vcf-orange transition-colors">
                  <Mail className="w-3.5 h-3.5 text-vcf-orange" />
                  <span>{email}</span>
                </a>
              )}
              {phone && (
                <a href={`tel:${phone.replace(/[^\d+]/g, '')}`} className="flex items-center gap-2 hover:text-vcf-orange transition-colors">
                  <Phone className="w-3.5 h-3.5 text-vcf-orange" />
                  <span>{phone}</span>
                </a>
              )}
            </div>
          </div>

          {/* Col 2: Academy Links */}
          <div>
            <h4 className="font-display text-xs font-bold uppercase tracking-[0.15em] text-white mb-4">
              Academy
            </h4>
            <ul className="space-y-2 text-xs">
              <li><Link href="/#hero" className="hover:text-vcf-orange transition-colors">Home</Link></li>
              <li><Link href="/#methodology" className="hover:text-vcf-orange transition-colors">Our Methodology</Link></li>
              <li><Link href="/#formation" className="hover:text-vcf-orange transition-colors">Know Your Role (Tactical Pitch)</Link></li>
              <li><Link href="/#grounds" className="hover:text-vcf-orange transition-colors">Training Grounds</Link></li>
              <li><Link href="/#roster" className="hover:text-vcf-orange transition-colors">Official Roster &amp; Stats</Link></li>
              <li><Link href="/#tournaments" className="hover:text-vcf-orange transition-colors">Tournaments &amp; Fixtures</Link></li>
              <li><Link href="/calendar" className="hover:text-vcf-orange transition-colors">Match Calendar (.ics)</Link></li>
            </ul>
          </div>

          {/* Col 3: Info & Community */}
          <div>
            <h4 className="font-display text-xs font-bold uppercase tracking-[0.15em] text-white mb-4">
              Community &amp; Info
            </h4>
            <ul className="space-y-2 text-xs">
              <li><Link href="/join" className="hover:text-vcf-orange transition-colors">Join the Academy (Tryouts)</Link></li>
              <li><Link href="/news" className="hover:text-vcf-orange transition-colors">Latest Academy News</Link></li>
              <li><Link href="/support" className="hover:text-vcf-orange transition-colors">Support the Site (Donations)</Link></li>
              <li><Link href="/contact" className="hover:text-vcf-orange transition-colors">Contact Us</Link></li>
              <li><Link href="/privacy" className="hover:text-vcf-orange transition-colors">Privacy Policy</Link></li>
              <li><Link href="/terms" className="hover:text-vcf-orange transition-colors">Terms of Use</Link></li>
              <li>
                <a
                  href="https://www.valenciacf.com"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-1 hover:text-vcf-orange transition-colors"
                >
                  <span>valenciacf.com</span>
                  <ExternalLink className="w-3 h-3" />
                </a>
              </li>
            </ul>
          </div>

          {/* Col 4: Training Headquarters */}
          <div>
            <h4 className="font-display text-xs font-bold uppercase tracking-[0.15em] text-white mb-4">
              Headquarters
            </h4>
            <div className="p-4 rounded-xl bg-vcf-surface border border-vcf-border space-y-2.5">
              <div className="font-display text-sm font-bold uppercase text-vcf-orange tracking-wide">
                B13 Sports Complex — Katy, TX
              </div>
              <p className="text-xs text-gray-300 leading-relaxed">
                2203 N Westgreen Blvd<br />
                Katy, TX 77449
              </p>
              <div className="text-[11px] text-gray-400 pt-1 border-t border-white/10">
                <strong className="text-gray-200">Sessions:</strong> Mon · Wed · Fri · 5:00 PM
              </div>
              <a
                href="https://maps.app.goo.gl/q27c1FCQw4cvspGX8"
                target="_blank"
                rel="noopener noreferrer"
                className="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-vcf-orange/15 hover:bg-vcf-orange text-vcf-orange hover:text-black font-semibold text-xs transition-all"
              >
                <MapPin className="w-3.5 h-3.5" />
                Open GPS Map
              </a>
            </div>
          </div>
        </div>

        {/* Bottom Copyright */}
        <div className="border-t border-white/5 py-5">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-500">
            <span>VCF Academy Houston &copy; {new Date().getFullYear()} · All rights reserved</span>
            <span className="font-display uppercase tracking-widest text-vcf-orange/90 font-semibold">
              ¡Amunt Valencia · Amunt Houston!
            </span>
            <span>
              Official Partner of{' '}
              <a href="https://www.valenciacf.com" target="_blank" rel="noopener noreferrer" className="text-gray-300 hover:text-vcf-orange">
                Valencia CF
              </a>
            </span>
          </div>
        </div>
      </footer>
    </div>
  );
}
