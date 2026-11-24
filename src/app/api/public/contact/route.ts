import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/lib/db';
import { checkRateLimit } from '@/lib/rate-limit';
import { z } from 'zod';

const ContactSchema = z.object({
  name: z.string().trim().min(2, 'Name is required').max(100),
  email: z.string().trim().email('Please enter a valid email address').max(255),
  subject: z.string().trim().min(2, 'Subject is required').max(255),
  message: z.string().trim().min(5, 'Please write a message').max(3000),
  website: z.string().optional().default(''), // honeypot
});

export async function POST(req: NextRequest) {
  try {
    const ip =
      req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() ||
      req.headers.get('x-real-ip') ||
      '127.0.0.1';

    if (!checkRateLimit('contact-form', ip, 5, 600)) {
      return NextResponse.json(
        { ok: false, error: 'Too many messages. Please wait a few minutes and try again.' },
        { status: 429 }
      );
    }

    const body = await req.json();
    const parsed = ContactSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json(
        { ok: false, error: parsed.error.issues[0]?.message || 'All fields are required.' },
        { status: 400 }
      );
    }

    const data = parsed.data;
    if (data.website && data.website.trim() !== '') {
      return NextResponse.json({
        ok: true,
        message: 'Thank you for your message. We will get back to you soon.',
      });
    }

    const db = getDb();
    db.prepare(
      'INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)'
    ).run(data.name, data.email, data.subject, data.message);

    return NextResponse.json({
      ok: true,
      message: 'Thank you for your message! Our team will get back to you shortly.',
    });
  } catch (err) {
    console.error('Contact form error:', err);
    return NextResponse.json(
      { ok: false, error: 'Sorry, we could not send your message. Please try again later.' },
      { status: 500 }
    );
  }
}
