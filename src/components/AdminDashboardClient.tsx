'use client';

import React, { useState, useEffect, useCallback } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import {
  LayoutDashboard,
  Users,
  Trophy,
  Award,
  Newspaper,
  MapPin,
  Image as ImageIcon,
  Inbox,
  Settings,
  Shield,
  LogOut,
  Plus,
  Trash2,
  Edit3,
  Upload,
  CheckCircle2,
  ExternalLink,
  RefreshCw,
} from 'lucide-react';

type TabKey =
  | 'overview'
  | 'roster'
  | 'matches'
  | 'motm'
  | 'news'
  | 'grounds'
  | 'media'
  | 'inbox'
  | 'settings';

export function AdminDashboardClient() {
  const router = useRouter();
  const [activeTab, setActiveTab] = useState<TabKey>('overview');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [toast, setToast] = useState<string | null>(null);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const [state, setState] = useState<any>(null);

  // Forms state
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const [editingPlayer, setEditingPlayer] = useState<any | null>(null);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const [editingMatch, setEditingMatch] = useState<any | null>(null);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const [editingTorneo, setEditingTorneo] = useState<any | null>(null);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const [editingNews, setEditingNews] = useState<any | null>(null);
  const [motmForm, setMotmForm] = useState<{ juego_id: number; hours: number; roster_ids: number[] }>({
    juego_id: 0,
    hours: 24,
    roster_ids: [],
  });
  const [newUserForm, setNewUserForm] = useState({
    username: '',
    password: '',
    role: 'editor_coach',
    email: '',
  });
  const [newPassword, setNewPassword] = useState('');

  const showToast = (msg: string) => {
    setToast(msg);
    setTimeout(() => setToast(null), 3200);
  };

  const fetchAdminData = useCallback(async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/admin/action');
      if (res.status === 401) {
        router.push('/admin/login');
        return;
      }
      const json = await res.json();
      if (json.ok) {
        setState(json);
      }
    } finally {
      setLoading(false);
    }
  }, [router]);

  useEffect(() => {
    fetchAdminData();
  }, [fetchAdminData]);

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const runAction = async (action: string, payload: any, successMessage = 'Saved successfully!') => {
    setSaving(true);
    try {
      const res = await fetch('/api/admin/action', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, payload }),
      });
      const json = await res.json();
      if (!res.ok || !json.ok) {
        alert(json.error || 'Operation failed');
        return false;
      }
      showToast(successMessage);
      await fetchAdminData();
      return true;
    } finally {
      setSaving(false);
    }
  };

  const handleFileUpload = async (file: File): Promise<string | null> => {
    const fd = new FormData();
    fd.append('file', file);
    const res = await fetch('/api/admin/upload', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.ok && json.url) {
      showToast('File uploaded!');
      return json.url;
    }
    alert(json.error || 'Upload failed');
    return null;
  };

  const handleLogout = async () => {
    await fetch('/api/auth/logout', { method: 'POST' });
    router.push('/admin/login');
  };

  if (loading && !state) {
    return (
      <div className="min-h-screen bg-vcf-dark flex items-center justify-center text-gray-400 text-sm">
        Loading Admin Command Center...
      </div>
    );
  }

  const d = state?.data || {};
  const user = state?.user || { username: 'admin', role: 'super_admin' };

  const navItems: { id: TabKey; label: string; icon: React.ElementType; badge?: number }[] = [
    { id: 'overview', label: 'Overview & KPIs', icon: LayoutDashboard },
    { id: 'roster', label: 'Squad & FUT Skills', icon: Users, badge: d.roster?.length },
    { id: 'matches', label: 'Tournaments & Matches', icon: Trophy, badge: d.juegos?.length },
    { id: 'motm', label: 'MOTM & Star of Month', icon: Award },
    { id: 'news', label: 'News & Blog', icon: Newspaper, badge: d.noticias?.length },
    { id: 'grounds', label: 'Grounds & Categories', icon: MapPin },
    { id: 'media', label: 'Hero Slider & Reels', icon: ImageIcon },
    {
      id: 'inbox',
      label: 'Registrations & Inbox',
      icon: Inbox,
      badge: (d.inscripciones?.length || 0) + (d.contactMessages?.length || 0),
    },
    { id: 'settings', label: 'Donations & Staff', icon: Settings },
  ];

  return (
    <div className="min-h-screen bg-vcf-dark text-white flex flex-col lg:flex-row">
      {/* Toast Notification */}
      {toast && (
        <div className="fixed bottom-6 right-6 z-50 px-5 py-3 rounded-2xl bg-emerald-500 text-black font-display font-bold text-xs uppercase tracking-wider shadow-2xl flex items-center gap-2">
          <CheckCircle2 className="w-4 h-4" />
          <span>{toast}</span>
        </div>
      )}

      {/* Sidebar */}
      <aside className="w-full lg:w-72 bg-vcf-surface border-b lg:border-b-0 lg:border-r border-vcf-border p-5 flex flex-col justify-between shrink-0">
        <div className="space-y-6">
          <div className="flex items-center justify-between">
            <Link href="/" className="flex items-center gap-3">
              <img src="/assets/img/vcf-crest.png" alt="VCF" className="w-9 h-11 object-contain" />
              <div>
                <div className="font-display text-lg font-bold uppercase text-white leading-none">
                  VCF Admin
                </div>
                <div className="text-[10px] font-bold uppercase tracking-widest text-vcf-orange mt-1">
                  {user.username} · {user.role}
                </div>
              </div>
            </Link>
            <button
              type="button"
              onClick={fetchAdminData}
              className="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-gray-300"
              title="Refresh Data"
            >
              <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin text-vcf-orange' : ''}`} />
            </button>
          </div>

          <nav className="flex lg:flex-col gap-1.5 overflow-x-auto pb-2 lg:pb-0">
            {navItems.map((item) => {
              const Icon = item.icon;
              const active = activeTab === item.id;
              return (
                <button
                  key={item.id}
                  type="button"
                  onClick={() => setActiveTab(item.id)}
                  className={`px-3.5 py-2.5 rounded-xl text-xs font-semibold flex items-center justify-between gap-3 whitespace-nowrap transition-all ${
                    active
                      ? 'bg-vcf-orange text-black font-bold shadow-[0_0_15px_rgba(255,102,0,0.3)]'
                      : 'text-gray-300 hover:bg-white/5 hover:text-white'
                  }`}
                >
                  <span className="flex items-center gap-2.5">
                    <Icon className="w-4 h-4 shrink-0" />
                    <span>{item.label}</span>
                  </span>
                  {item.badge !== undefined && (
                    <span
                      className={`px-1.5 py-0.5 rounded text-[10px] font-bold ${
                        active ? 'bg-black/20 text-black' : 'bg-white/10 text-gray-300'
                      }`}
                    >
                      {item.badge}
                    </span>
                  )}
                </button>
              );
            })}
          </nav>
        </div>

        <div className="hidden lg:flex flex-col gap-2 pt-6 border-t border-white/10">
          <Link
            href="/"
            className="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-200 flex items-center justify-between"
          >
            <span>View Public Site</span>
            <ExternalLink className="w-3.5 h-3.5 text-vcf-orange" />
          </Link>
          <button
            type="button"
            onClick={handleLogout}
            className="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-xs font-semibold text-red-300 flex items-center gap-2"
          >
            <LogOut className="w-3.5 h-3.5" />
            <span>Sign Out</span>
          </button>
        </div>
      </aside>

      {/* Main Content Area */}
      <main className="flex-1 p-5 sm:p-8 lg:p-10 overflow-y-auto max-w-6xl">
        {/* 1. OVERVIEW TAB */}
        {activeTab === 'overview' && (
          <div className="space-y-8">
            <div className="flex flex-wrap items-center justify-between gap-4">
              <div>
                <h1 className="font-display text-3xl sm:text-4xl font-bold uppercase text-white">
                  Command <span className="text-vcf-orange">Center</span>
                </h1>
                <p className="text-xs text-gray-400 mt-1">
                  Real-time overview of VCF Academy Houston operations, players, fixtures, and community activity.
                </p>
              </div>
              <Link
                href="/"
                className="px-4 py-2.5 rounded-xl bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider flex items-center gap-1.5"
              >
                <span>Open Live Website</span>
                <ExternalLink className="w-3.5 h-3.5" />
              </Link>
            </div>

            {/* KPI Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              {[
                { label: 'Active Squad Players', val: d.roster?.length || 0, sub: 'With FUT Radar Stats', tab: 'roster' as TabKey },
                { label: 'Matches Logged', val: d.juegos?.length || 0, sub: `${d.torneos?.length || 0} Tournaments`, tab: 'matches' as TabKey },
                { label: 'Academy Registrations', val: d.inscripciones?.length || 0, sub: 'Join form leads', tab: 'inbox' as TabKey },
                { label: 'Published News', val: d.noticias?.length || 0, sub: 'Articles & Recaps', tab: 'news' as TabKey },
              ].map((kpi) => (
                <button
                  key={kpi.label}
                  type="button"
                  onClick={() => setActiveTab(kpi.tab)}
                  className="p-5 rounded-2xl bg-vcf-surface border border-vcf-border hover:border-vcf-orange/60 text-left transition-all"
                >
                  <div className="text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    {kpi.label}
                  </div>
                  <div className="font-display text-3xl sm:text-4xl font-bold text-vcf-orange mt-1">
                    {kpi.val}
                  </div>
                  <div className="text-[11px] text-gray-500 mt-1">{kpi.sub}</div>
                </button>
              ))}
            </div>

            {/* Top Scorers Bar Chart + Activity Log */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                <h2 className="font-display text-lg font-bold uppercase text-white">
                  Top Goal Contributers (Goals + Assists)
                </h2>
                <div className="space-y-3">
                  {(d.roster || []).slice(0, 6).map((p: { id: number; nombre: string; apellido: string; dorsal: number; goles: number; asistencias: number }) => {
                    const total = Number(p.goles || 0) + Number(p.asistencias || 0);
                    const pct = Math.min(100, Math.round((total / 16) * 100));
                    return (
                      <div key={p.id} className="space-y-1">
                        <div className="flex justify-between text-xs">
                          <span className="font-semibold text-white">
                            #{p.dorsal ?? '–'} {p.nombre} {p.apellido}
                          </span>
                          <span className="text-vcf-orange font-bold">
                            {p.goles}G · {p.asistencias}A
                          </span>
                        </div>
                        <div className="w-full h-2 rounded-full bg-black/50 overflow-hidden">
                          <div
                            className="h-full bg-gradient-to-r from-vcf-orange to-vcf-gold"
                            style={{ width: `${pct}%` }}
                          />
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                <h2 className="font-display text-lg font-bold uppercase text-white">
                  Recent Staff Activity Log
                </h2>
                <div className="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                  {(d.activityLog || []).slice(0, 12).map((log: { id: number; username: string; action: string; details: string; created_at: string }) => (
                    <div
                      key={log.id}
                      className="p-2.5 rounded-xl bg-vcf-card border border-vcf-border flex items-center justify-between text-xs"
                    >
                      <div>
                        <span className="font-bold text-vcf-orange">{log.action}</span>
                        {log.details && <span className="text-gray-300 ml-2">— {log.details}</span>}
                      </div>
                      <span className="text-[10px] text-gray-500 shrink-0 ml-2">
                        {log.username || 'system'}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* 2. ROSTER & FUT SKILLS TAB */}
        {activeTab === 'roster' && (
          <div className="space-y-6">
            <div className="flex items-center justify-between">
              <div>
                <h1 className="font-display text-3xl font-bold uppercase text-white">
                  Squad Roster &amp; <span className="text-vcf-orange">FUT Radar Skills</span>
                </h1>
                <p className="text-xs text-gray-400">
                  Manage players, jersey numbers, season statistics, and 6-axis radar chart skills (1–10).
                </p>
              </div>
              <button
                type="button"
                onClick={() =>
                  setEditingPlayer({
                    id: 0,
                    nombre: '',
                    apellido: '',
                    dorsal: '',
                    posicion: 'Mediocampista',
                    sub_posicion: '',
                    foto_url: '',
                    categoria_id: d.categorias?.[0]?.id || 3,
                    activo: 1,
                    partidos_jugados: 0,
                    goles: 0,
                    asistencias: 0,
                    motm: 0,
                    clean_sheets: 0,
                    pace: 7,
                    shooting: 7,
                    passing: 7,
                    dribbling: 7,
                    defense: 6,
                    physical: 7,
                  })
                }
                className="px-4 py-2.5 rounded-xl bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider flex items-center gap-1.5"
              >
                <Plus className="w-4 h-4" />
                Add Player
              </button>
            </div>

            {editingPlayer && (
              <form
                onSubmit={async (e) => {
                  e.preventDefault();
                  const ok = await runAction('roster.save', editingPlayer, 'Player saved!');
                  if (ok) setEditingPlayer(null);
                }}
                className="rounded-2xl bg-vcf-surface border border-vcf-orange p-6 space-y-5"
              >
                <h3 className="font-display text-xl font-bold uppercase text-vcf-orange">
                  {editingPlayer.id > 0 ? `Edit Player: ${editingPlayer.nombre}` : 'New Squad Player'}
                </h3>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">First Name *</label>
                    <input
                      type="text"
                      required
                      value={editingPlayer.nombre}
                      onChange={(e) => setEditingPlayer({ ...editingPlayer, nombre: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Last Name *</label>
                    <input
                      type="text"
                      required
                      value={editingPlayer.apellido}
                      onChange={(e) => setEditingPlayer({ ...editingPlayer, apellido: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Jersey # (Dorsal)</label>
                    <input
                      type="number"
                      value={editingPlayer.dorsal ?? ''}
                      onChange={(e) => setEditingPlayer({ ...editingPlayer, dorsal: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Primary Line</label>
                    <select
                      value={editingPlayer.posicion || 'Mediocampista'}
                      onChange={(e) => setEditingPlayer({ ...editingPlayer, posicion: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    >
                      <option value="Portero">Portero (GK)</option>
                      <option value="Defensa">Defensa (DEF)</option>
                      <option value="Mediocampista">Mediocampista (MID)</option>
                      <option value="Delantero">Delantero (FWD)</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Specific Role</label>
                    <input
                      type="text"
                      placeholder="e.g. Center Back (CB)"
                      value={editingPlayer.sub_posicion || ''}
                      onChange={(e) => setEditingPlayer({ ...editingPlayer, sub_posicion: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Photo URL / Upload</label>
                    <div className="flex gap-2">
                      <input
                        type="text"
                        value={editingPlayer.foto_url || ''}
                        onChange={(e) => setEditingPlayer({ ...editingPlayer, foto_url: e.target.value })}
                        placeholder="/uploads/player.jpg"
                        className="flex-1 px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                      />
                      <label className="px-3 py-2 rounded-lg bg-white/10 hover:bg-vcf-orange hover:text-black text-xs font-bold cursor-pointer flex items-center">
                        <Upload className="w-3.5 h-3.5" />
                        <input
                          type="file"
                          accept="image/*"
                          className="hidden"
                          onChange={async (e) => {
                            const f = e.target.files?.[0];
                            if (f) {
                              const url = await handleFileUpload(f);
                              if (url) setEditingPlayer({ ...editingPlayer, foto_url: url });
                            }
                          }}
                        />
                      </label>
                    </div>
                  </div>
                </div>

                {/* Season Stats */}
                <div className="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-2 border-t border-white/10">
                  {[
                    { key: 'partidos_jugados', label: 'Matches (PJ)' },
                    { key: 'goles', label: 'Goals' },
                    { key: 'asistencias', label: 'Assists' },
                    { key: 'motm', label: 'MOTM Awards' },
                    { key: 'clean_sheets', label: 'Clean Sheets' },
                  ].map((st) => (
                    <div key={st.key}>
                      <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">{st.label}</label>
                      <input
                        type="number"
                        value={editingPlayer[st.key] ?? 0}
                        onChange={(e) => setEditingPlayer({ ...editingPlayer, [st.key]: Number(e.target.value) })}
                        className="w-full px-3 py-1.5 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                      />
                    </div>
                  ))}
                </div>

                {/* Radar Skills (1-10) */}
                <div className="grid grid-cols-2 sm:grid-cols-6 gap-3 pt-2 border-t border-white/10">
                  {(['pace', 'shooting', 'passing', 'dribbling', 'defense', 'physical'] as const).map((sk) => (
                    <div key={sk}>
                      <label className="block text-[10px] font-bold uppercase text-vcf-orange mb-1">
                        {sk} ({editingPlayer[sk]}/10)
                      </label>
                      <input
                        type="range"
                        min={1}
                        max={10}
                        value={editingPlayer[sk] ?? 7}
                        onChange={(e) => setEditingPlayer({ ...editingPlayer, [sk]: Number(e.target.value) })}
                        className="w-full accent-vcf-orange"
                      />
                    </div>
                  ))}
                </div>

                <div className="flex gap-2">
                  <button
                    type="submit"
                    disabled={saving}
                    className="px-5 py-2.5 rounded-xl bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider"
                  >
                    Save Player
                  </button>
                  <button
                    type="button"
                    onClick={() => setEditingPlayer(null)}
                    className="px-4 py-2.5 rounded-xl bg-white/5 text-xs text-gray-300"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            )}

            <div className="rounded-2xl bg-vcf-surface border border-vcf-border overflow-hidden">
              <div className="divide-y divide-white/5">
                {(d.roster || []).map((p: { id: number; dorsal: number; nombre: string; apellido: string; posicion: string; categoria_nombre: string; goles: number; asistencias: number }) => (
                  <div
                    key={p.id}
                    className="px-5 py-3.5 flex items-center justify-between gap-4 hover:bg-white/[0.02]"
                  >
                    <div className="flex items-center gap-3">
                      <span className="w-9 h-9 rounded-lg bg-vcf-orange/15 border border-vcf-orange/30 font-display font-bold text-sm text-vcf-orange flex items-center justify-center">
                        #{p.dorsal ?? '–'}
                      </span>
                      <div>
                        <div className="font-bold text-sm text-white">
                          {p.nombre} {p.apellido}
                        </div>
                        <div className="text-xs text-gray-400">
                          {p.posicion} · {p.categoria_nombre} · {p.goles}G / {p.asistencias}A
                        </div>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => setEditingPlayer(p)}
                        className="p-2 rounded-lg bg-white/5 hover:bg-vcf-orange hover:text-black text-gray-300"
                      >
                        <Edit3 className="w-4 h-4" />
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          if (confirm(`Delete player ${p.nombre} ${p.apellido}?`)) {
                            runAction('roster.delete', { id: p.id }, 'Player deleted');
                          }
                        }}
                        className="p-2 rounded-lg bg-red-500/10 hover:bg-red-500 text-red-300 hover:text-black"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* 3. TOURNAMENTS & MATCHES TAB */}
        {activeTab === 'matches' && (
          <div className="space-y-8">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h1 className="font-display text-3xl font-bold uppercase text-white">
                  Tournaments &amp; <span className="text-vcf-orange">Matches</span>
                </h1>
                <p className="text-xs text-gray-400">
                  Manage competitions, match fixtures, live scores, and official goal scorers.
                </p>
              </div>
              <div className="flex gap-2">
                <button
                  type="button"
                  onClick={() => setEditingTorneo({ id: 0, nombre_torneo: '', temporada: '2026', archivado: 0 })}
                  className="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-xs font-bold uppercase"
                >
                  + New Tournament
                </button>
                <button
                  type="button"
                  onClick={() =>
                    setEditingMatch({
                      id: 0,
                      torneo_id: d.torneos?.[0]?.id || 1,
                      fecha: new Date().toISOString().slice(0, 10),
                      hora: '10:00',
                      rival: '',
                      cancha: 'Field #1',
                      sede_id: d.sedes?.[0]?.id || 1,
                      estado: 'proximo',
                      categoria: 'U10',
                      goles_vcf: '',
                      goles_rival: '',
                    })
                  }
                  className="px-4 py-2.5 rounded-xl bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider"
                >
                  + Add Match
                </button>
              </div>
            </div>

            {editingTorneo && (
              <form
                onSubmit={async (e) => {
                  e.preventDefault();
                  const ok = await runAction('torneo.save', editingTorneo, 'Tournament saved!');
                  if (ok) setEditingTorneo(null);
                }}
                className="rounded-2xl bg-vcf-surface border border-vcf-orange p-5 space-y-4"
              >
                <h3 className="font-display text-lg font-bold uppercase text-vcf-orange">Tournament Details</h3>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <input
                    type="text"
                    required
                    placeholder="Tournament Name"
                    value={editingTorneo.nombre_torneo}
                    onChange={(e) => setEditingTorneo({ ...editingTorneo, nombre_torneo: e.target.value })}
                    className="px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                  />
                  <input
                    type="text"
                    placeholder="Season (e.g. Spring 2026)"
                    value={editingTorneo.temporada || ''}
                    onChange={(e) => setEditingTorneo({ ...editingTorneo, temporada: e.target.value })}
                    className="px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                  />
                  <label className="flex items-center gap-2 text-xs text-gray-300">
                    <input
                      type="checkbox"
                      checked={Boolean(editingTorneo.archivado)}
                      onChange={(e) => setEditingTorneo({ ...editingTorneo, archivado: e.target.checked ? 1 : 0 })}
                    />
                    Archive (Move to Past Tournaments)
                  </label>
                </div>
                <div className="flex gap-2">
                  <button type="submit" className="px-4 py-2 rounded-lg bg-vcf-orange text-black font-bold text-xs uppercase">
                    Save Tournament
                  </button>
                  <button type="button" onClick={() => setEditingTorneo(null)} className="px-4 py-2 rounded-lg bg-white/5 text-xs">
                    Cancel
                  </button>
                </div>
              </form>
            )}

            {editingMatch && (
              <form
                onSubmit={async (e) => {
                  e.preventDefault();
                  const ok = await runAction('juego.save', editingMatch, 'Match saved!');
                  if (ok) setEditingMatch(null);
                }}
                className="rounded-2xl bg-vcf-surface border border-vcf-orange p-6 space-y-4"
              >
                <h3 className="font-display text-lg font-bold uppercase text-vcf-orange">
                  {editingMatch.id > 0 ? `Edit Match vs ${editingMatch.rival}` : 'Create Match Fixture'}
                </h3>
                <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">Tournament</label>
                    <select
                      value={editingMatch.torneo_id}
                      onChange={(e) => setEditingMatch({ ...editingMatch, torneo_id: Number(e.target.value) })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    >
                      {(d.torneos || []).map((t: { id: number; nombre_torneo: string }) => (
                        <option key={t.id} value={t.id}>{t.nombre_torneo}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">Opponent (Rival) *</label>
                    <input
                      type="text"
                      required
                      value={editingMatch.rival || ''}
                      onChange={(e) => setEditingMatch({ ...editingMatch, rival: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">Date *</label>
                    <input
                      type="date"
                      required
                      value={editingMatch.fecha || ''}
                      onChange={(e) => setEditingMatch({ ...editingMatch, fecha: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">Time</label>
                    <input
                      type="time"
                      value={editingMatch.hora || ''}
                      onChange={(e) => setEditingMatch({ ...editingMatch, hora: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">VCF Goals</label>
                    <input
                      type="number"
                      placeholder="Leave empty if upcoming"
                      value={editingMatch.goles_vcf ?? ''}
                      onChange={(e) => setEditingMatch({ ...editingMatch, goles_vcf: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">Rival Goals</label>
                    <input
                      type="number"
                      placeholder="Leave empty if upcoming"
                      value={editingMatch.goles_rival ?? ''}
                      onChange={(e) => setEditingMatch({ ...editingMatch, goles_rival: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">Pitch / Cancha</label>
                    <input
                      type="text"
                      value={editingMatch.cancha || ''}
                      onChange={(e) => setEditingMatch({ ...editingMatch, cancha: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">Status</label>
                    <select
                      value={editingMatch.estado || 'proximo'}
                      onChange={(e) => setEditingMatch({ ...editingMatch, estado: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    >
                      <option value="proximo">Upcoming (Próximo)</option>
                      <option value="live">Live Match</option>
                      <option value="finalizado">Finished (Finalizado)</option>
                    </select>
                  </div>
                </div>
                <div className="flex gap-2">
                  <button type="submit" className="px-5 py-2 rounded-xl bg-vcf-orange text-black font-bold text-xs uppercase">
                    Save Match
                  </button>
                  <button type="button" onClick={() => setEditingMatch(null)} className="px-4 py-2 rounded-xl bg-white/5 text-xs">
                    Cancel
                  </button>
                </div>
              </form>
            )}

            <div className="rounded-2xl bg-vcf-surface border border-vcf-border overflow-hidden">
              <div className="divide-y divide-white/5">
                {(d.juegos || []).map((j: { id: number; fecha: string; hora: string; rival: string; nombre_torneo: string; goles_vcf: number | null; goles_rival: number | null }) => (
                  <div
                    key={j.id}
                    className="px-5 py-4 flex items-center justify-between gap-4 hover:bg-white/[0.02]"
                  >
                    <div>
                      <div className="font-display text-base font-bold uppercase text-white">
                        VCF Houston vs <span className="text-vcf-orange">{j.rival}</span>
                        {j.goles_vcf !== null && j.goles_rival !== null && (
                          <span className="ml-2 px-2 py-0.5 rounded bg-white/10 text-xs">
                            {j.goles_vcf} – {j.goles_rival}
                          </span>
                        )}
                      </div>
                      <div className="text-xs text-gray-400">
                        {j.fecha} {j.hora || ''} · {j.nombre_torneo}
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() =>
                          setEditingMatch({
                            ...j,
                            goles_vcf: j.goles_vcf ?? '',
                            goles_rival: j.goles_rival ?? '',
                          })
                        }
                        className="p-2 rounded-lg bg-white/5 hover:bg-vcf-orange hover:text-black text-gray-300"
                      >
                        <Edit3 className="w-4 h-4" />
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          if (confirm('Delete this match?')) {
                            runAction('juego.delete', { id: j.id }, 'Match deleted');
                          }
                        }}
                        className="p-2 rounded-lg bg-red-500/10 hover:bg-red-500 text-red-300 hover:text-black"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* 4. MOTM & STAR OF THE MONTH TAB */}
        {activeTab === 'motm' && (
          <div className="space-y-8">
            <h1 className="font-display text-3xl font-bold uppercase text-white">
              Man of the Match &amp; <span className="text-vcf-orange">Star of the Month</span>
            </h1>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Launch MOTM Voting */}
              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                <h2 className="font-display text-xl font-bold uppercase text-vcf-orange">
                  Start New Man of the Match Poll
                </h2>
                <div>
                  <label className="block text-xs font-bold uppercase text-gray-400 mb-1">Select Match</label>
                  <select
                    value={motmForm.juego_id}
                    onChange={(e) => setMotmForm({ ...motmForm, juego_id: Number(e.target.value) })}
                    className="w-full px-3 py-2.5 rounded-xl bg-vcf-card border border-vcf-border text-sm text-white"
                  >
                    <option value={0}>-- Choose a match --</option>
                    {(d.juegos || []).map((j: { id: number; fecha: string; rival: string }) => (
                      <option key={j.id} value={j.id}>
                        {j.fecha} — vs {j.rival}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase text-gray-400 mb-1">
                    Pick 3 Nominees ({motmForm.roster_ids.length} selected)
                  </label>
                  <div className="max-h-48 overflow-y-auto grid grid-cols-2 gap-1.5 p-2 rounded-xl bg-vcf-card border border-vcf-border">
                    {(d.roster || []).map((p: { id: number; dorsal: number; nombre: string; apellido: string }) => {
                      const checked = motmForm.roster_ids.includes(p.id);
                      return (
                        <button
                          key={p.id}
                          type="button"
                          onClick={() => {
                            setMotmForm((prev) => ({
                              ...prev,
                              roster_ids: checked
                                ? prev.roster_ids.filter((id) => id !== p.id)
                                : [...prev.roster_ids, p.id].slice(0, 4),
                            }));
                          }}
                          className={`px-2.5 py-1.5 rounded-lg text-left text-xs font-medium transition-colors ${
                            checked ? 'bg-vcf-orange text-black font-bold' : 'bg-white/5 text-gray-300'
                          }`}
                        >
                          #{p.dorsal ?? '–'} {p.nombre} {p.apellido.split(' ')[0]}
                        </button>
                      );
                    })}
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => runAction('motm.create', motmForm, 'MOTM poll launched!')}
                  className="w-full py-3 rounded-xl bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider"
                >
                  Launch Live MOTM Poll
                </button>

                <div className="pt-4 border-t border-white/10 space-y-2">
                  <div className="text-xs font-bold uppercase text-gray-400">Existing MOTM Polls</div>
                  {(d.motmVotaciones || []).map((v: { id: number; rival: string; status: string }) => (
                    <div key={v.id} className="p-3 rounded-xl bg-vcf-card flex items-center justify-between text-xs">
                      <span>
                        Poll #{v.id} · vs <strong>{v.rival}</strong> ({v.status})
                      </span>
                      {v.status === 'open' && (
                        <button
                          type="button"
                          onClick={() => runAction('motm.close', { id: v.id }, 'MOTM Poll Closed & Winner Crowned!')}
                          className="px-3 py-1 rounded bg-emerald-500 text-black font-bold"
                        >
                          Close &amp; Crown Winner
                        </button>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              {/* Star of the Month Editor */}
              {d.star && (
                <form
                  onSubmit={(e) => {
                    e.preventDefault();
                    const fd = new FormData(e.currentTarget);
                    runAction('star.save', Object.fromEntries(fd.entries()), 'Star of the Month updated!');
                  }}
                  className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4"
                >
                  <h2 className="font-display text-xl font-bold uppercase text-vcf-gold">
                    VCF Star of the Month
                  </h2>
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Player Name</label>
                      <input
                        name="nombre"
                        defaultValue={d.star.nombre}
                        className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Month Label</label>
                      <input
                        name="mes"
                        defaultValue={d.star.mes}
                        className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Category</label>
                      <input
                        name="categoria"
                        defaultValue={d.star.categoria}
                        className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Jersey #</label>
                      <input
                        name="dorsal"
                        type="number"
                        defaultValue={d.star.dorsal ?? ''}
                        className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Achievement Description</label>
                    <textarea
                      name="descripcion_logro"
                      rows={4}
                      defaultValue={d.star.descripcion_logro}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <button
                    type="submit"
                    className="w-full py-3 rounded-xl bg-vcf-gold text-black font-display font-bold text-xs uppercase tracking-wider"
                  >
                    Update Star of the Month
                  </button>
                </form>
              )}
            </div>
          </div>
        )}

        {/* 5. NEWS & BLOG TAB */}
        {activeTab === 'news' && (
          <div className="space-y-6">
            <div className="flex items-center justify-between">
              <h1 className="font-display text-3xl font-bold uppercase text-white">
                Academy <span className="text-vcf-orange">News &amp; Blog</span>
              </h1>
              <button
                type="button"
                onClick={() =>
                  setEditingNews({
                    id: 0,
                    titulo: '',
                    categoria_id: d.noticiasCategorias?.[0]?.id || 1,
                    resumen: '',
                    contenido: '<p>Write your article content here...</p>',
                    imagen_destacada: '/assets/img/news-placeholder-editorial.jpg',
                    publicado: 1,
                    destacada: 1,
                  })
                }
                className="px-4 py-2.5 rounded-xl bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider"
              >
                + Write Article
              </button>
            </div>

            {editingNews && (
              <form
                onSubmit={async (e) => {
                  e.preventDefault();
                  const ok = await runAction('noticia.save', editingNews, 'Article published!');
                  if (ok) setEditingNews(null);
                }}
                className="rounded-2xl bg-vcf-surface border border-vcf-orange p-6 space-y-4"
              >
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div className="sm:col-span-2">
                    <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Headline *</label>
                    <input
                      type="text"
                      required
                      value={editingNews.titulo}
                      onChange={(e) => setEditingNews({ ...editingNews, titulo: e.target.value })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Category</label>
                    <select
                      value={editingNews.categoria_id || 1}
                      onChange={(e) => setEditingNews({ ...editingNews, categoria_id: Number(e.target.value) })}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                    >
                      {(d.noticiasCategorias || []).map((c: { id: number; nombre: string }) => (
                        <option key={c.id} value={c.id}>{c.nombre}</option>
                      ))}
                    </select>
                  </div>
                </div>
                <div>
                  <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Summary / Excerpt</label>
                  <input
                    type="text"
                    value={editingNews.resumen || ''}
                    onChange={(e) => setEditingNews({ ...editingNews, resumen: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white"
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-bold uppercase text-gray-400 mb-1">Article Body (HTML supported)</label>
                  <textarea
                    rows={6}
                    value={editingNews.contenido || ''}
                    onChange={(e) => setEditingNews({ ...editingNews, contenido: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-sm text-white font-mono"
                  />
                </div>
                <div className="flex gap-2">
                  <button type="submit" className="px-5 py-2.5 rounded-xl bg-vcf-orange text-black font-bold text-xs uppercase">
                    Publish Article
                  </button>
                  <button type="button" onClick={() => setEditingNews(null)} className="px-4 py-2.5 rounded-xl bg-white/5 text-xs">
                    Cancel
                  </button>
                </div>
              </form>
            )}

            <div className="rounded-2xl bg-vcf-surface border border-vcf-border divide-y divide-white/5">
              {(d.noticias || []).map((n: { id: number; titulo: string; categoria_nombre: string; views: number }) => (
                <div key={n.id} className="p-5 flex items-center justify-between gap-4">
                  <div>
                    <div className="font-display text-lg font-bold uppercase text-white">{n.titulo}</div>
                    <div className="text-xs text-gray-400">
                      {n.categoria_nombre} · {n.views} views
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <button
                      type="button"
                      onClick={() => setEditingNews(n)}
                      className="p-2 rounded-lg bg-white/5 hover:bg-vcf-orange hover:text-black"
                    >
                      <Edit3 className="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      onClick={() => runAction('noticia.delete', { id: n.id }, 'Article deleted')}
                      className="p-2 rounded-lg bg-red-500/10 hover:bg-red-500 text-red-300 hover:text-black"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* 6. GROUNDS & CATEGORIES TAB */}
        {activeTab === 'grounds' && (
          <div className="space-y-8">
            <h1 className="font-display text-3xl font-bold uppercase text-white">
              Training Grounds &amp; <span className="text-vcf-orange">Age Categories</span>
            </h1>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                <h2 className="font-display text-xl font-bold uppercase text-vcf-orange">Age Categories &amp; Schedules</h2>
                {(d.categorias || []).map((c: { id: number; nombre: string; horarios_entrenamiento: string }) => (
                  <form
                    key={c.id}
                    onSubmit={(e) => {
                      e.preventDefault();
                      const fd = new FormData(e.currentTarget);
                      runAction('categoria.save', {
                        id: c.id,
                        nombre: fd.get('nombre'),
                        horarios_entrenamiento: fd.get('horarios_entrenamiento'),
                      });
                    }}
                    className="flex items-center gap-2"
                  >
                    <input
                      name="nombre"
                      defaultValue={c.nombre}
                      className="w-20 px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs font-bold text-vcf-orange"
                    />
                    <input
                      name="horarios_entrenamiento"
                      defaultValue={c.horarios_entrenamiento}
                      className="flex-1 px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                    />
                    <button type="submit" className="px-3 py-2 rounded-lg bg-vcf-orange text-black font-bold text-xs">
                      Save
                    </button>
                  </form>
                ))}
              </div>

              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                <h2 className="font-display text-xl font-bold uppercase text-vcf-orange">Training Venues (Sedes)</h2>
                {(d.sedes || []).map((s: { id: number; nombre: string; direccion: string; mapa_general_url: string; nota_acceso: string }) => (
                  <form
                    key={s.id}
                    onSubmit={(e) => {
                      e.preventDefault();
                      const fd = new FormData(e.currentTarget);
                      runAction('sede.save', {
                        id: s.id,
                        nombre: fd.get('nombre'),
                        direccion: fd.get('direccion'),
                        mapa_general_url: fd.get('mapa_general_url'),
                        nota_acceso: fd.get('nota_acceso'),
                      });
                    }}
                    className="p-4 rounded-xl bg-vcf-card border border-vcf-border space-y-2.5"
                  >
                    <input
                      name="nombre"
                      defaultValue={s.nombre}
                      className="w-full px-3 py-1.5 rounded bg-vcf-surface border border-vcf-border text-xs font-bold text-white"
                    />
                    <input
                      name="direccion"
                      defaultValue={s.direccion}
                      className="w-full px-3 py-1.5 rounded bg-vcf-surface border border-vcf-border text-xs text-gray-300"
                    />
                    <input
                      name="mapa_general_url"
                      defaultValue={s.mapa_general_url || ''}
                      placeholder="Google Maps URL"
                      className="w-full px-3 py-1.5 rounded bg-vcf-surface border border-vcf-border text-xs text-gray-300"
                    />
                    <input
                      name="nota_acceso"
                      defaultValue={s.nota_acceso || ''}
                      placeholder="Access note"
                      className="w-full px-3 py-1.5 rounded bg-vcf-surface border border-vcf-border text-xs text-gray-300"
                    />
                    <button type="submit" className="px-4 py-1.5 rounded-lg bg-vcf-orange text-black font-bold text-xs">
                      Update Venue
                    </button>
                  </form>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* 7. HERO SLIDER & REELS TAB */}
        {activeTab === 'media' && (
          <div className="space-y-8">
            <h1 className="font-display text-3xl font-bold uppercase text-white">
              Hero Banners &amp; <span className="text-vcf-orange">Match Reels</span>
            </h1>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                <h2 className="font-display text-xl font-bold uppercase text-vcf-orange">Add Match Reel Video</h2>
                <form
                  onSubmit={(e) => {
                    e.preventDefault();
                    const fd = new FormData(e.currentTarget);
                    runAction('reel.save', {
                      video_url: fd.get('video_url'),
                      caption: fd.get('caption'),
                      orden: Number(fd.get('orden') || 1),
                    });
                    e.currentTarget.reset();
                  }}
                  className="space-y-3"
                >
                  <input
                    name="video_url"
                    required
                    placeholder="Video URL (.mp4)"
                    className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                  />
                  <input
                    name="caption"
                    placeholder="Caption (e.g. Golazo vs Rangers!)"
                    className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                  />
                  <button type="submit" className="px-4 py-2 rounded-xl bg-vcf-orange text-black font-bold text-xs uppercase">
                    + Add Reel
                  </button>
                </form>

                <div className="space-y-2 pt-3 border-t border-white/10">
                  {(d.matchReels || []).map((r: { id: number; caption: string; video_url: string; view_count: number; like_count: number }) => (
                    <div key={r.id} className="p-3 rounded-xl bg-vcf-card flex items-center justify-between text-xs">
                      <div>
                        <div className="font-bold text-white">{r.caption || r.video_url}</div>
                        <div className="text-gray-400">{r.view_count} views · {r.like_count} likes</div>
                      </div>
                      <button
                        type="button"
                        onClick={() => runAction('reel.delete', { id: r.id }, 'Reel removed')}
                        className="text-red-400 hover:text-red-300"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  ))}
                </div>
              </div>

              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                <h2 className="font-display text-xl font-bold uppercase text-vcf-orange">Hero Slider Banners</h2>
                <form
                  onSubmit={(e) => {
                    e.preventDefault();
                    const fd = new FormData(e.currentTarget);
                    runAction('hero.save', {
                      image_url: fd.get('image_url'),
                      title: fd.get('title'),
                      subtitle: fd.get('subtitle'),
                      button_text: fd.get('button_text') || 'Read More',
                      button_url: fd.get('button_url') || '/join',
                      orden: Number(fd.get('orden') || 1),
                      activo: 1,
                    }, 'Hero slide added!');
                    e.currentTarget.reset();
                  }}
                  className="space-y-3"
                >
                  <input
                    name="title"
                    required
                    placeholder="Banner Headline *"
                    className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                  />
                  <input
                    name="subtitle"
                    placeholder="Subtitle"
                    className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                  />
                  <div className="grid grid-cols-2 gap-2">
                    <input
                      name="image_url"
                      required
                      placeholder="Image URL (e.g. /assets/img/news-placeholder.jpg) *"
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                    />
                    <input
                      name="button_url"
                      placeholder="Button URL (e.g. /join)"
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                    />
                  </div>
                  <button type="submit" className="px-4 py-2 rounded-xl bg-vcf-orange text-black font-bold text-xs uppercase">
                    + Add Hero Slide
                  </button>
                </form>

                <div className="space-y-3 pt-3 border-t border-white/10">
                  {(d.heroSlides || []).map((s: { id: number; title: string; subtitle: string; image_url: string }) => (
                    <div key={s.id} className="p-3.5 rounded-xl bg-vcf-card border border-vcf-border flex items-center justify-between gap-3">
                      <div>
                        <div className="font-bold text-sm text-white">{s.title}</div>
                        <div className="text-xs text-gray-400">{s.subtitle}</div>
                      </div>
                      <button
                        type="button"
                        onClick={() => runAction('hero.delete', { id: s.id }, 'Slide deleted')}
                        className="text-red-400 hover:text-red-300"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* 8. INBOX (INSCRIPCIONES & CONTACT) TAB */}
        {activeTab === 'inbox' && (
          <div className="space-y-8">
            <h1 className="font-display text-3xl font-bold uppercase text-white">
              Academy Registrations &amp; <span className="text-vcf-orange">Contact Inbox</span>
            </h1>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                <h2 className="font-display text-xl font-bold uppercase text-vcf-orange">
                  Join the Academy Submissions ({d.inscripciones?.length || 0})
                </h2>
                {(d.inscripciones || []).length === 0 ? (
                  <p className="text-xs text-gray-400">No registrations yet.</p>
                ) : (
                  <div className="space-y-3">
                    {(d.inscripciones || []).map((ins: { id: number; child_name: string; child_category: string; parent_name: string; email: string; phone: string; message: string; created_at: string }) => (
                      <div key={ins.id} className="p-4 rounded-xl bg-vcf-card border border-vcf-border space-y-1.5 text-xs">
                        <div className="flex justify-between">
                          <span className="font-bold text-white text-sm">
                            {ins.child_name} ({ins.child_category})
                          </span>
                          <button
                            type="button"
                            onClick={() => runAction('inscripcion.delete', { id: ins.id }, 'Deleted')}
                            className="text-red-400"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                        <div className="text-gray-300">
                          Parent: <strong>{ins.parent_name}</strong> · {ins.email} {ins.phone ? `· ${ins.phone}` : ''}
                        </div>
                        {ins.message && <p className="text-gray-400 italic">&ldquo;{ins.message}&rdquo;</p>}
                      </div>
                    ))}
                  </div>
                )}
              </div>

              <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                <h2 className="font-display text-xl font-bold uppercase text-vcf-orange">
                  Contact Messages ({d.contactMessages?.length || 0})
                </h2>
                {(d.contactMessages || []).length === 0 ? (
                  <p className="text-xs text-gray-400">No contact messages yet.</p>
                ) : (
                  <div className="space-y-3">
                    {(d.contactMessages || []).map((msg: { id: number; subject: string; name: string; email: string; message: string }) => (
                      <div key={msg.id} className="p-4 rounded-xl bg-vcf-card border border-vcf-border space-y-1.5 text-xs">
                        <div className="flex justify-between">
                          <span className="font-bold text-white text-sm">{msg.subject}</span>
                          <button
                            type="button"
                            onClick={() => runAction('contact.delete', { id: msg.id }, 'Message deleted')}
                            className="text-red-400"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                        <div className="text-vcf-orange font-medium">
                          {msg.name} ({msg.email})
                        </div>
                        <p className="text-gray-300">{msg.message}</p>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {/* 9. SETTINGS, DONATIONS & USERS TAB */}
        {activeTab === 'settings' && (
          <div className="space-y-8">
            <h1 className="font-display text-3xl font-bold uppercase text-white">
              Support Donations, Settings &amp; <span className="text-vcf-orange">Staff Roles</span>
            </h1>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Site & Support Donations Form */}
              <form
                onSubmit={(e) => {
                  e.preventDefault();
                  const fd = new FormData(e.currentTarget);
                  runAction('settings.save', Object.fromEntries(fd.entries()), 'Site & Support settings updated!');
                }}
                className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4"
              >
                <h2 className="font-display text-xl font-bold uppercase text-vcf-orange">
                  Support Page &amp; Contact Settings
                </h2>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">Public Email</label>
                    <input
                      name="public_email"
                      defaultValue={d.settings?.public_email || ''}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">Phone / WhatsApp</label>
                    <input
                      name="phone"
                      defaultValue={d.settings?.phone || ''}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">Zelle Email</label>
                    <input
                      name="donation_zelle_email"
                      defaultValue={d.settings?.donation_zelle_email || ''}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">PayPal Link</label>
                    <input
                      name="donation_paypal_url"
                      defaultValue={d.settings?.donation_paypal_url || ''}
                      className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-400 mb-1">
                    Parent Contributions (One per line: Date|Name|$Amount)
                  </label>
                  <textarea
                    name="donation_parent_contributions"
                    rows={5}
                    defaultValue={d.settings?.donation_parent_contributions || ''}
                    className="w-full px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white font-mono"
                  />
                </div>
                <button
                  type="submit"
                  className="px-5 py-2.5 rounded-xl bg-vcf-orange text-black font-display font-bold text-xs uppercase tracking-wider"
                >
                  Save Support &amp; Contact Settings
                </button>
              </form>

              {/* Password & Staff Users */}
              <div className="space-y-6">
                <form
                  onSubmit={(e) => {
                    e.preventDefault();
                    runAction('password.change', { new_password: newPassword }, 'Password updated!');
                    setNewPassword('');
                  }}
                  className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-3"
                >
                  <h2 className="font-display text-lg font-bold uppercase text-white">Change Your Password</h2>
                  <div className="flex gap-2">
                    <input
                      type="password"
                      required
                      minLength={6}
                      value={newPassword}
                      onChange={(e) => setNewPassword(e.target.value)}
                      placeholder="New password (min 6 chars)"
                      className="flex-1 px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                    />
                    <button type="submit" className="px-4 py-2 rounded-lg bg-vcf-orange text-black font-bold text-xs uppercase">
                      Update
                    </button>
                  </div>
                </form>

                {user.role === 'super_admin' && (
                  <div className="rounded-2xl bg-vcf-surface border border-vcf-border p-6 space-y-4">
                    <h2 className="font-display text-lg font-bold uppercase text-vcf-orange flex items-center gap-2">
                      <Shield className="w-4 h-4" />
                      Staff Users &amp; RBAC Roles
                    </h2>
                    <form
                      onSubmit={async (e) => {
                        e.preventDefault();
                        const ok = await runAction('user.create', newUserForm, 'Staff account created!');
                        if (ok) setNewUserForm({ username: '', password: '', role: 'editor_coach', email: '' });
                      }}
                      className="grid grid-cols-1 sm:grid-cols-3 gap-2"
                    >
                      <input
                        type="text"
                        required
                        placeholder="Username"
                        value={newUserForm.username}
                        onChange={(e) => setNewUserForm({ ...newUserForm, username: e.target.value })}
                        className="px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                      />
                      <input
                        type="password"
                        required
                        placeholder="Password"
                        value={newUserForm.password}
                        onChange={(e) => setNewUserForm({ ...newUserForm, password: e.target.value })}
                        className="px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                      />
                      <select
                        value={newUserForm.role}
                        onChange={(e) => setNewUserForm({ ...newUserForm, role: e.target.value })}
                        className="px-3 py-2 rounded-lg bg-vcf-card border border-vcf-border text-xs text-white"
                      >
                        <option value="editor_coach">Editor / Coach</option>
                        <option value="staff_campo">Field Staff</option>
                        <option value="super_admin">Super Admin</option>
                      </select>
                      <button
                        type="submit"
                        className="sm:col-span-3 py-2 rounded-lg bg-white/10 hover:bg-vcf-orange hover:text-black font-bold text-xs uppercase"
                      >
                        + Create Staff Account
                      </button>
                    </form>

                    <div className="space-y-2 pt-2 border-t border-white/10">
                      {(d.users || []).map((u: { id: number; username: string; role: string }) => (
                        <div key={u.id} className="flex items-center justify-between text-xs p-2 rounded-lg bg-vcf-card">
                          <span>
                            <strong>{u.username}</strong> · <span className="text-vcf-orange">{u.role}</span>
                          </span>
                          {u.id !== user.id && (
                            <button
                              type="button"
                              onClick={() => runAction('user.delete', { id: u.id }, 'User removed')}
                              className="text-red-400"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </button>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
