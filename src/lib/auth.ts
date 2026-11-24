import { SignJWT, jwtVerify } from 'jose';
import { cookies } from 'next/headers';

const JWT_SECRET = new TextEncoder().encode(
  process.env.JWT_SECRET || 'vcf-academy-houston-secret-key-2026-valenciacf-amunt'
);

export const SESSION_COOKIE = 'vcf_admin_session';

export type AdminRole = 'super_admin' | 'editor_coach' | 'staff_campo';

export interface SessionUser {
  id: number;
  username: string;
  role: AdminRole;
  email?: string | null;
}

const ROLE_PERMISSIONS: Record<AdminRole, string[]> = {
  super_admin: ['*'],
  editor_coach: [
    'juegos',
    'juegos_live_score',
    'torneos',
    'motm',
    'roster_edit',
    'jugador_mes',
    'sedes',
    'categorias',
    'hero_slider',
    'match_reels',
    'noticias',
    'inscripciones_view',
    'contact_messages_view',
    'support_donations',
    'change_own_password',
  ],
  staff_campo: [
    'jugador_mes',
    'juegos_live_score',
    'match_reels',
    'change_own_password',
  ],
};

export function canAccess(role: AdminRole, permission: string): boolean {
  if (role === 'super_admin') return true;
  const list = ROLE_PERMISSIONS[role] || [];
  return list.includes('*') || list.includes(permission);
}

export async function createSessionToken(user: SessionUser): Promise<string> {
  return new SignJWT({
    id: user.id,
    username: user.username,
    role: user.role,
    email: user.email ?? null,
  })
    .setProtectedHeader({ alg: 'HS256' })
    .setIssuedAt()
    .setExpirationTime('7d')
    .sign(JWT_SECRET);
}

export async function verifySessionToken(token: string): Promise<SessionUser | null> {
  try {
    const { payload } = await jwtVerify(token, JWT_SECRET);
    if (!payload || typeof payload.id !== 'number' || typeof payload.username !== 'string') {
      return null;
    }
    return {
      id: payload.id,
      username: payload.username,
      role: (payload.role as AdminRole) || 'staff_campo',
      email: (payload.email as string) || null,
    };
  } catch {
    return null;
  }
}

export async function getSessionUser(): Promise<SessionUser | null> {
  try {
    const cookieStore = await cookies();
    const token = cookieStore.get(SESSION_COOKIE)?.value;
    if (!token) return null;
    return await verifySessionToken(token);
  } catch {
    return null;
  }
}
