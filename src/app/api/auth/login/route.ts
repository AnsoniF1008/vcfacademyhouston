import { NextRequest, NextResponse } from 'next/server';
import { getDb, logAdminActivity } from '@/lib/db';
import { createSessionToken, SESSION_COOKIE, AdminRole } from '@/lib/auth';
import { checkRateLimit } from '@/lib/rate-limit';
import bcrypt from 'bcryptjs';

export async function POST(req: NextRequest) {
  try {
    const ip =
      req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() ||
      req.headers.get('x-real-ip') ||
      '127.0.0.1';

    if (!checkRateLimit('admin-login', ip, 10, 300)) {
      return NextResponse.json(
        { ok: false, error: 'Too many login attempts. Please wait 5 minutes.' },
        { status: 429 }
      );
    }

    const body = await req.json();
    const username = String(body.username || '').trim();
    const password = String(body.password || '');

    if (!username || !password) {
      return NextResponse.json(
        { ok: false, error: 'Username and password are required.' },
        { status: 400 }
      );
    }

    const db = getDb();
    const user = db
      .prepare('SELECT id, username, password_hash, role, email FROM admin_users WHERE username = ?')
      .get(username) as {
      id: number;
      username: string;
      password_hash: string;
      role: string;
      email: string | null;
    } | undefined;

    if (!user || !bcrypt.compareSync(password, user.password_hash)) {
      logAdminActivity(null, username, 'auth.login.failed', `Failed login from IP ${ip}`);
      return NextResponse.json(
        { ok: false, error: 'Invalid username or password.' },
        { status: 401 }
      );
    }

    const sessionUser = {
      id: user.id,
      username: user.username,
      role: (user.role as AdminRole) || 'super_admin',
      email: user.email,
    };

    const token = await createSessionToken(sessionUser);
    logAdminActivity(user.id, user.username, 'auth.login.success', 'Signed into Admin Panel');

    const res = NextResponse.json({ ok: true, user: sessionUser });
    res.cookies.set(SESSION_COOKIE, token, {
      httpOnly: true,
      sameSite: 'lax',
      path: '/',
      maxAge: 60 * 60 * 24 * 7,
    });

    return res;
  } catch (err) {
    console.error('Login error:', err);
    return NextResponse.json({ ok: false, error: 'Server error during sign in.' }, { status: 500 });
  }
}
