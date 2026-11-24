import { NextResponse } from 'next/server';
import { getSessionUser, SESSION_COOKIE } from '@/lib/auth';
import { logAdminActivity } from '@/lib/db';

export async function POST() {
  const user = await getSessionUser();
  if (user) {
    logAdminActivity(user.id, user.username, 'auth.logout', 'Signed out of Admin Panel');
  }
  const res = NextResponse.json({ ok: true });
  res.cookies.delete(SESSION_COOKIE);
  return res;
}
