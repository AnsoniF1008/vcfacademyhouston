'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { Shield, Lock, User, AlertCircle, ArrowLeft } from 'lucide-react';

export default function AdminLoginPage() {
  const router = useRouter();
  const [username, setUsername] = useState('admin');
  const [password, setPassword] = useState('password');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password }),
      });
      const data = await res.json();
      if (!res.ok || !data.ok) {
        setError(data.error || 'Invalid credentials');
      } else {
        router.push('/admin');
        router.refresh();
      }
    } catch {
      setError('Network error while signing in.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-vcf-dark flex flex-col justify-center items-center px-4 relative overflow-hidden">
      <div className="absolute -top-40 -left-40 w-96 h-96 bg-vcf-orange/15 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute -bottom-40 -right-40 w-96 h-96 bg-vcf-gold/10 rounded-full blur-3xl pointer-events-none" />

      <div className="w-full max-w-md space-y-6 relative z-10">
        <Link
          href="/"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400 hover:text-vcf-orange transition-colors"
        >
          <ArrowLeft className="w-3.5 h-3.5" />
          Back to Public Website
        </Link>

        <div className="rounded-3xl bg-vcf-surface border border-vcf-border p-8 shadow-2xl space-y-6">
          <div className="flex flex-col items-center text-center space-y-2">
            <img src="/assets/img/vcf-crest.png" alt="VCF" className="w-14 h-16 object-contain" />
            <div className="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-vcf-orange/15 border border-vcf-orange/30 text-vcf-orange text-[10px] font-bold uppercase tracking-widest">
              <Shield className="w-3 h-3" />
              Staff &amp; Coach Portal
            </div>
            <h1 className="font-display text-3xl font-bold uppercase text-white">
              Admin <span className="text-vcf-orange">Sign In</span>
            </h1>
          </div>

          {error && (
            <div className="p-3.5 rounded-xl bg-red-500/15 border border-red-500/40 text-xs text-red-200 flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{error}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-1.5">
                Username
              </label>
              <div className="relative">
                <User className="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  required
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  className="w-full pl-10 pr-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-1.5">
                Password
              </label>
              <div className="relative">
                <Lock className="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full pl-10 pr-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 rounded-xl bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-sm uppercase tracking-wider transition-all shadow-[0_0_20px_rgba(255,102,0,0.3)] disabled:opacity-50"
            >
              {loading ? 'Signing In...' : 'Enter Admin Dashboard'}
            </button>
          </form>

          <div className="pt-3 border-t border-white/10 text-center text-[11px] text-gray-400">
            Default credentials pre-filled: <code className="text-vcf-orange">admin</code> /{' '}
            <code className="text-vcf-orange">password</code>
          </div>
        </div>
      </div>
    </div>
  );
}
