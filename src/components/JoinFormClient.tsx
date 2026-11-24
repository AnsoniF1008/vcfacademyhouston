'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { CheckCircle2, AlertCircle, Send } from 'lucide-react';
import type { Categoria } from '@/lib/queries';

export function JoinFormClient({ categorias }: { categorias: Categoria[] }) {
  const [form, setForm] = useState({
    parent_name: '',
    email: '',
    phone: '',
    child_name: '',
    child_category: categorias[0]?.nombre || 'U10',
    message: '',
    website: '', // honeypot
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const res = await fetch('/api/public/join', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });
      const data = await res.json();
      if (!res.ok || !data.ok) {
        setError(data.error || 'Could not submit registration.');
      } else {
        setSuccessMsg(data.message);
      }
    } catch {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  if (successMsg) {
    return (
      <div className="rounded-3xl bg-vcf-surface border border-emerald-500/40 p-8 sm:p-12 text-center space-y-5">
        <div className="w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center mx-auto">
          <CheckCircle2 className="w-8 h-8" />
        </div>
        <h2 className="font-display text-3xl font-bold uppercase text-white">
          Registration <span className="text-vcf-orange">Received!</span>
        </h2>
        <p className="text-sm text-gray-300 max-w-md mx-auto leading-relaxed">{successMsg}</p>
        <div className="pt-2">
          <Link
            href="/"
            className="inline-block px-6 py-3 rounded-xl bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider"
          >
            Back to Home
          </Link>
        </div>
      </div>
    );
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="rounded-3xl bg-vcf-surface border border-vcf-border p-6 sm:p-10 space-y-6 shadow-2xl"
    >
      {error && (
        <div className="p-4 rounded-xl bg-red-500/15 border border-red-500/40 text-xs text-red-200 flex items-center gap-2">
          <AlertCircle className="w-4 h-4 shrink-0" />
          <span>{error}</span>
        </div>
      )}

      {/* Honeypot */}
      <div className="hidden" aria-hidden="true">
        <input
          type="text"
          tabIndex={-1}
          autoComplete="off"
          value={form.website}
          onChange={(e) => setForm({ ...form, website: e.target.value })}
        />
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">
            Parent / Guardian Name *
          </label>
          <input
            type="text"
            required
            value={form.parent_name}
            onChange={(e) => setForm({ ...form, parent_name: e.target.value })}
            placeholder="e.g. Carlos Rodriguez"
            className="w-full px-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
          />
        </div>

        <div>
          <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">
            Email Address *
          </label>
          <input
            type="email"
            required
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
            placeholder="parent@example.com"
            className="w-full px-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
          />
        </div>

        <div>
          <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">
            Phone / WhatsApp (Optional)
          </label>
          <input
            type="tel"
            value={form.phone}
            onChange={(e) => setForm({ ...form, phone: e.target.value })}
            placeholder="+1 (832) 000-0000"
            className="w-full px-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
          />
        </div>

        <div>
          <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">
            Player / Child&apos;s Full Name *
          </label>
          <input
            type="text"
            required
            value={form.child_name}
            onChange={(e) => setForm({ ...form, child_name: e.target.value })}
            placeholder="Player name"
            className="w-full px-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
          />
        </div>

        <div className="sm:col-span-2">
          <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">
            Age Category &amp; Training Schedule *
          </label>
          <select
            required
            value={form.child_category}
            onChange={(e) => setForm({ ...form, child_category: e.target.value })}
            className="w-full px-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
          >
            {categorias.map((c) => (
              <option key={c.id} value={c.nombre}>
                {c.nombre} {c.horarios_entrenamiento ? `— ${c.horarios_entrenamiento}` : ''}
              </option>
            ))}
          </select>
        </div>

        <div className="sm:col-span-2">
          <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">
            Playing Experience / Position / Notes (Optional)
          </label>
          <textarea
            rows={4}
            value={form.message}
            onChange={(e) => setForm({ ...form, message: e.target.value })}
            placeholder="Tell us about your child's current age, preferred position, or any questions..."
            className="w-full px-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
          />
        </div>
      </div>

      <div className="flex flex-wrap items-center gap-3 pt-2">
        <button
          type="submit"
          disabled={loading}
          className="px-6 py-3.5 rounded-xl bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-xs uppercase tracking-wider flex items-center gap-2 transition-all disabled:opacity-50"
        >
          <Send className="w-4 h-4" />
          {loading ? 'Submitting...' : 'Submit Academy Registration'}
        </button>
        <Link
          href="/"
          className="px-5 py-3.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-300"
        >
          Cancel
        </Link>
      </div>
    </form>
  );
}
