'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { CheckCircle2, AlertCircle, Send } from 'lucide-react';

export function ContactFormClient() {
  const [form, setForm] = useState({
    name: '',
    email: '',
    subject: '',
    message: '',
    website: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const res = await fetch('/api/public/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });
      const data = await res.json();
      if (!res.ok || !data.ok) {
        setError(data.error || 'Could not send message.');
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
          Message <span className="text-vcf-orange">Sent!</span>
        </h2>
        <p className="text-sm text-gray-300 max-w-md mx-auto">{successMsg}</p>
        <Link
          href="/"
          className="inline-block px-6 py-3 rounded-xl bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider"
        >
          Back to Home
        </Link>
      </div>
    );
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="rounded-3xl bg-vcf-surface border border-vcf-border p-6 sm:p-8 space-y-5 shadow-2xl"
    >
      {error && (
        <div className="p-4 rounded-xl bg-red-500/15 border border-red-500/40 text-xs text-red-200 flex items-center gap-2">
          <AlertCircle className="w-4 h-4 shrink-0" />
          <span>{error}</span>
        </div>
      )}

      <div className="hidden" aria-hidden="true">
        <input
          type="text"
          tabIndex={-1}
          autoComplete="off"
          value={form.website}
          onChange={(e) => setForm({ ...form, website: e.target.value })}
        />
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">
            Your Name *
          </label>
          <input
            type="text"
            required
            value={form.name}
            onChange={(e) => setForm({ ...form, name: e.target.value })}
            placeholder="Full name"
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
            placeholder="you@example.com"
            className="w-full px-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
          />
        </div>
      </div>

      <div>
        <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">
          Subject *
        </label>
        <input
          type="text"
          required
          value={form.subject}
          onChange={(e) => setForm({ ...form, subject: e.target.value })}
          placeholder="How can we help?"
          className="w-full px-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
        />
      </div>

      <div>
        <label className="block text-xs font-bold uppercase tracking-wider text-gray-300 mb-2">
          Message *
        </label>
        <textarea
          rows={5}
          required
          value={form.message}
          onChange={(e) => setForm({ ...form, message: e.target.value })}
          placeholder="Write your message here..."
          className="w-full px-4 py-3 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white focus:outline-none focus:border-vcf-orange"
        />
      </div>

      <button
        type="submit"
        disabled={loading}
        className="px-6 py-3.5 rounded-xl bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-xs uppercase tracking-wider flex items-center gap-2 transition-all disabled:opacity-50"
      >
        <Send className="w-4 h-4" />
        {loading ? 'Sending...' : 'Send Message'}
      </button>
    </form>
  );
}
