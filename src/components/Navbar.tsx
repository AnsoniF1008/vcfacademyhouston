'use client';

import React, { useState, useRef } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { Menu, X, Shield, Calendar, Heart, UserPlus, Newspaper, PhoneCall } from 'lucide-react';

export function Navbar({ isAdminLoggedIn = false }: { isAdminLoggedIn?: boolean }) {
  const pathname = usePathname();
  const [mobileOpen, setMobileOpen] = useState(false);
  const logoRef = useRef<HTMLAnchorElement>(null);
  const [tilt, setTilt] = useState({ x: 0, y: 0 });

  const handleMouseMove = (e: React.MouseEvent<HTMLAnchorElement>) => {
    if (!logoRef.current) return;
    const rect = logoRef.current.getBoundingClientRect();
    const px = (e.clientX - rect.left) / Math.max(rect.width, 1);
    const py = (e.clientY - rect.top) / Math.max(rect.height, 1);
    const nx = px * 2 - 1;
    const ny = py * 2 - 1;
    setTilt({ x: +(ny * -10).toFixed(2), y: +(nx * 8).toFixed(2) });
  };

  const handleMouseLeave = () => {
    setTilt({ x: 0, y: 0 });
  };

  const navItems = [
    { label: 'Home', href: '/#hero' },
    { label: 'Methodology', href: '/#methodology' },
    { label: 'Role & Pitch', href: '/#formation' },
    { label: 'Grounds', href: '/#grounds' },
    { label: 'Roster', href: '/#roster' },
    { label: 'Tournaments', href: '/#tournaments' },
    { label: 'News', href: '/news', active: pathname.startsWith('/news') },
    { label: 'Calendar', href: '/calendar', active: pathname.startsWith('/calendar') },
    { label: 'Support', href: '/support', active: pathname.startsWith('/support') },
  ];

  return (
    <>
      {/* Topbar */}
      <div className="bg-black/90 border-b border-white/10 text-[11px] text-gray-400 hidden md:block">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 h-8 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <span className="inline-flex items-center gap-1.5 text-vcf-orange font-semibold uppercase tracking-wider">
              <span className="w-1.5 h-1.5 rounded-full bg-vcf-orange animate-pulse" />
              Official Valencia CF Academy Program
            </span>
            <span className="text-white/20">|</span>
            <span>Sentiment · Courage · Fight</span>
          </div>
          <div className="flex items-center gap-4">
            <span className="text-gray-300 font-medium">B13 Sports Complex · Katy &amp; Houston, TX</span>
            <span className="text-white/20">|</span>
            <Link
              href={isAdminLoggedIn ? '/admin' : '/admin/login'}
              className="hover:text-vcf-orange transition-colors flex items-center gap-1 font-medium"
            >
              <Shield className="w-3 h-3 text-vcf-orange" />
              {isAdminLoggedIn ? 'Admin Dashboard' : 'Staff Portal'}
            </Link>
          </div>
        </div>
      </div>

      {/* Main Sticky Header */}
      <header className="sticky top-0 z-50 bg-vcf-dark/90 backdrop-blur-md border-b border-vcf-border">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between gap-4">
          {/* Brand with 3D Tilt */}
          <Link
            ref={logoRef}
            href="/"
            onMouseMove={handleMouseMove}
            onMouseLeave={handleMouseLeave}
            className="group flex items-center gap-3.5 focus:outline-none"
            style={{ perspective: '600px' }}
          >
            <div
              className="flex items-center gap-3 transition-transform duration-150 ease-out"
              style={{
                transform: `rotateX(${tilt.x}deg) rotateY(${tilt.y}deg)`,
              }}
            >
              <div className="relative w-11 h-14 flex items-center justify-center">
                <div className="absolute inset-0 bg-vcf-orange/25 rounded-full blur-md opacity-0 group-hover:opacity-100 transition-opacity" />
                <img
                  src="/assets/img/vcf-crest.png"
                  alt="Valencia CF Crest"
                  className="w-10 h-12 object-contain relative z-10 drop-shadow-[0_2px_8px_rgba(255,102,0,0.35)]"
                />
              </div>
              <div className="flex flex-col">
                <span className="font-display text-xl sm:text-2xl font-bold uppercase tracking-wider text-white leading-none">
                  VCF Academy
                </span>
                <span className="text-[11px] font-bold uppercase tracking-[0.2em] text-vcf-orange mt-1">
                  Houston · Official
                </span>
              </div>
            </div>
          </Link>

          {/* Desktop Navigation */}
          <nav className="hidden lg:flex items-center gap-1">
            {navItems.map((item) => (
              <Link
                key={item.label}
                href={item.href}
                className={`px-3 py-2 rounded-lg text-xs font-semibold uppercase tracking-wider transition-all ${
                  item.active
                    ? 'text-vcf-orange bg-vcf-orange/10'
                    : 'text-gray-300 hover:text-white hover:bg-white/5'
                }`}
              >
                {item.label}
              </Link>
            ))}
          </nav>

          {/* Actions */}
          <div className="hidden sm:flex items-center gap-2.5">
            <Link
              href="/join"
              className="px-4 py-2.5 rounded-lg bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-xs uppercase tracking-wider transition-all shadow-[0_0_20px_rgba(255,102,0,0.3)] hover:shadow-[0_0_25px_rgba(255,102,0,0.5)] flex items-center gap-1.5"
            >
              <UserPlus className="w-3.5 h-3.5" />
              Join Academy
            </Link>
            <Link
              href="/contact"
              className="px-3.5 py-2.5 rounded-lg border border-white/15 hover:border-vcf-orange/60 text-xs font-semibold uppercase tracking-wider text-white hover:text-vcf-orange transition-colors"
            >
              Contact
            </Link>
          </div>

          {/* Mobile Burger Button */}
          <button
            type="button"
            onClick={() => setMobileOpen(!mobileOpen)}
            className="lg:hidden p-2.5 rounded-lg bg-vcf-surface border border-vcf-border text-gray-200 hover:text-vcf-orange"
            aria-label="Toggle Menu"
          >
            {mobileOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
        </div>

        {/* Mobile Drawer */}
        {mobileOpen && (
          <div className="lg:hidden bg-vcf-surface border-b border-vcf-border px-4 pt-3 pb-6 space-y-3">
            <div className="grid grid-cols-2 gap-1.5">
              {navItems.map((item) => (
                <Link
                  key={item.label}
                  href={item.href}
                  onClick={() => setMobileOpen(false)}
                  className="px-3 py-2.5 rounded-lg text-xs font-semibold uppercase tracking-wider text-gray-200 hover:bg-white/5 hover:text-vcf-orange"
                >
                  {item.label}
                </Link>
              ))}
            </div>
            <div className="pt-3 border-t border-white/10 flex flex-wrap gap-2">
              <Link
                href="/join"
                onClick={() => setMobileOpen(false)}
                className="flex-1 text-center py-2.5 rounded-lg bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider"
              >
                Join the Academy
              </Link>
              <Link
                href="/contact"
                onClick={() => setMobileOpen(false)}
                className="flex-1 text-center py-2.5 rounded-lg border border-white/20 text-white font-display font-bold text-xs uppercase tracking-wider"
              >
                Contact Us
              </Link>
              <Link
                href={isAdminLoggedIn ? '/admin' : '/admin/login'}
                onClick={() => setMobileOpen(false)}
                className="w-full text-center py-2 rounded-lg bg-white/5 text-gray-300 text-xs font-medium"
              >
                {isAdminLoggedIn ? 'Open Admin Dashboard' : 'Staff / Coach Login'}
              </Link>
            </div>
          </div>
        )}
      </header>
    </>
  );
}
