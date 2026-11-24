import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/lib/db';
import { checkRateLimit } from '@/lib/rate-limit';
import { z } from 'zod';

const JoinSchema = z.object({
  parent_name: z.string().trim().min(2, 'Parent / Guardian name is required').max(100),
  email: z.string().trim().email('Please enter a valid email address').max(255),
  phone: z.string().trim().max(50).optional().default(''),
  child_name: z.string().trim().min(2, 'Child name is required').max(100),
  child_category: z.string().trim().min(1, 'Please select an age category').max(30),
  message: z.string().trim().max(1500).optional().default(''),
  website: z.string().optional().default(''), // honeypot
});

export async function POST(req: NextRequest) {
  try {
    const ip =
      req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() ||
      req.headers.get('x-real-ip') ||
      '127.0.0.1';

    if (!checkRateLimit('join-form', ip, 5, 600)) {
      return NextResponse.json(
        { ok: false, error: 'Too many submissions. Please wait a few minutes and try again.' },
        { status: 429 }
      );
    }

    const body = await req.json();
    const parsed = JoinSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json(
        { ok: false, error: parsed.error.issues[0]?.message || 'Invalid registration data.' },
        { status: 400 }
      );
    }

    const data = parsed.data;
    if (data.website && data.website.trim() !== '') {
      // Honeypot triggered
      return NextResponse.json({
        ok: true,
        message: 'Thank you for your interest! We have received your registration and will contact you soon.',
      });
    }

    const db = getDb();
    db.prepare(
      'INSERT INTO inscripciones (parent_name, email, phone, child_name, child_category, message) VALUES (?, ?, ?, ?, ?, ?)'
    ).run(
      data.parent_name,
      data.email,
      data.phone || null,
      data.child_name,
      data.child_category,
      data.message || null
    );

    return NextResponse.json({
      ok: true,
      message: 'Thank you for your interest! We have received your registration and our coaching staff will contact you soon.',
    });
  } catch (err) {
    console.error('Join form error:', err);
    return NextResponse.json(
      { ok: false, error: 'Could not process your registration. Please try again later.' },
      { status: 500 }
    );
  }
}
