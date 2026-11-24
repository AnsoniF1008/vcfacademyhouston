import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/lib/db';

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    const reelId = Number(body.reel_id);
    const action = String(body.action || 'view');
    const fingerprint = String(body.fingerprint || 'anon');

    if (!reelId || reelId <= 0) {
      return NextResponse.json({ ok: false }, { status: 400 });
    }

    const db = getDb();
    if (action === 'view') {
      db.prepare('UPDATE match_reels SET view_count = view_count + 1 WHERE id = ?').run(reelId);
    } else if (action === 'like') {
      const exists = db
        .prepare('SELECT 1 FROM match_reel_like_visitors WHERE reel_id = ? AND fingerprint_hash = ?')
        .get(reelId, fingerprint);
      if (!exists) {
        db.prepare('INSERT INTO match_reel_like_visitors (reel_id, fingerprint_hash) VALUES (?, ?)').run(
          reelId,
          fingerprint
        );
        db.prepare('UPDATE match_reels SET like_count = like_count + 1 WHERE id = ?').run(reelId);
      }
    }

    const updated = db
      .prepare('SELECT view_count, like_count FROM match_reels WHERE id = ?')
      .get(reelId) as { view_count: number; like_count: number } | undefined;

    return NextResponse.json({
      ok: true,
      view_count: updated?.view_count ?? 0,
      like_count: updated?.like_count ?? 0,
    });
  } catch {
    return NextResponse.json({ ok: false }, { status: 500 });
  }
}
