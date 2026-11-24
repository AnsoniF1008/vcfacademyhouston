import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/lib/db';
import { checkRateLimit } from '@/lib/rate-limit';
import { z } from 'zod';

const VoteSchema = z.object({
  votacion_id: z.number().int().positive(),
  nominee_id: z.number().int().positive(),
  fingerprint: z.string().max(128).optional(),
});

export async function POST(req: NextRequest) {
  try {
    const ip =
      req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() ||
      req.headers.get('x-real-ip') ||
      '127.0.0.1';

    if (!checkRateLimit('motm-vote', ip, 10, 300)) {
      return NextResponse.json(
        { ok: false, error: 'Too many requests. Please try again in a few minutes.' },
        { status: 429 }
      );
    }

    const body = await req.json();
    const parsed = VoteSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json({ ok: false, error: 'Invalid vote parameters.' }, { status: 400 });
    }

    const { votacion_id, nominee_id, fingerprint } = parsed.data;
    const db = getDb();

    const votacion = db
      .prepare('SELECT id, status, ends_at FROM motm_votaciones WHERE id = ?')
      .get(votacion_id) as { id: number; status: string; ends_at: string } | undefined;

    if (!votacion || votacion.status !== 'open' || new Date(votacion.ends_at).getTime() <= Date.now()) {
      return NextResponse.json(
        { ok: false, error: 'Voting for this match is already closed.' },
        { status: 400 }
      );
    }

    const nominee = db
      .prepare('SELECT id FROM motm_nominees WHERE id = ? AND votacion_id = ?')
      .get(nominee_id, votacion_id) as { id: number } | undefined;

    if (!nominee) {
      return NextResponse.json({ ok: false, error: 'Nominee not found for this poll.' }, { status: 404 });
    }

    const voterKey = fingerprint ? `${ip}:${fingerprint.slice(0, 32)}` : ip;

    const existing = db
      .prepare('SELECT id FROM motm_votes WHERE votacion_id = ? AND ip_address = ?')
      .get(votacion_id, voterKey) as { id: number } | undefined;

    if (existing) {
      return NextResponse.json(
        { ok: false, error: 'You have already voted in this Man of the Match poll!' },
        { status: 409 }
      );
    }

    db.prepare(
      'INSERT INTO motm_votes (votacion_id, nominee_id, ip_address, fingerprint_hash) VALUES (?, ?, ?, ?)'
    ).run(votacion_id, nominee_id, voterKey, fingerprint ?? null);

    const nominees = db.prepare(`
      SELECT n.id, n.nombre, COUNT(mv.id) as votes
      FROM motm_nominees n
      LEFT JOIN motm_votes mv ON mv.nominee_id = n.id
      WHERE n.votacion_id = ?
      GROUP BY n.id
      ORDER BY n.orden ASC
    `).all(votacion_id) as { id: number; nombre: string; votes: number }[];

    const totalVotes = nominees.reduce((sum, n) => sum + Number(n.votes), 0);

    return NextResponse.json({
      ok: true,
      message: 'Vote recorded! ¡Amunt Valencia!',
      total_votes: totalVotes,
      nominees: nominees.map((n) => ({
        id: n.id,
        nombre: n.nombre,
        votes: Number(n.votes),
        pct: totalVotes > 0 ? Math.round((Number(n.votes) / totalVotes) * 100) : 0,
      })),
    });
  } catch (err) {
    console.error('MOTM vote error:', err);
    return NextResponse.json({ ok: false, error: 'Could not register vote.' }, { status: 500 });
  }
}
