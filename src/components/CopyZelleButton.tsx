'use client';

import React, { useState } from 'react';
import { Copy, Check } from 'lucide-react';

export function CopyZelleButton({ email }: { email: string }) {
  const [copied, setCopied] = useState(false);

  const handleCopy = () => {
    if (navigator.clipboard?.writeText) {
      navigator.clipboard.writeText(email);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  return (
    <button
      type="button"
      onClick={handleCopy}
      className="px-4 py-2 rounded-lg bg-vcf-orange hover:bg-vcf-orange-light text-black font-display font-bold text-xs uppercase tracking-wider flex items-center gap-1.5 transition-all shrink-0"
    >
      {copied ? <Check className="w-3.5 h-3.5" /> : <Copy className="w-3.5 h-3.5" />}
      <span>{copied ? 'Copied!' : 'Copy Email'}</span>
    </button>
  );
}
