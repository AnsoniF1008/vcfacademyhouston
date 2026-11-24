import { NextRequest, NextResponse } from 'next/server';
import { getSessionUser } from '@/lib/auth';
import { logAdminActivity } from '@/lib/db';
import path from 'node:path';
import fs from 'node:fs';

const ALLOWED_EXTENSIONS = new Set(['.jpg', '.jpeg', '.png', '.webp', '.svg', '.mp4', '.webm']);

export async function POST(req: NextRequest) {
  const user = await getSessionUser();
  if (!user) {
    return NextResponse.json({ ok: false, error: 'Unauthorized' }, { status: 401 });
  }

  try {
    const formData = await req.formData();
    const file = formData.get('file') as File | null;
    if (!file) {
      return NextResponse.json({ ok: false, error: 'No file provided.' }, { status: 400 });
    }

    const ext = path.extname(file.name).toLowerCase();
    if (!ALLOWED_EXTENSIONS.has(ext)) {
      return NextResponse.json(
        { ok: false, error: 'File type not supported. Allowed: JPG, PNG, WEBP, SVG, MP4, WEBM.' },
        { status: 400 }
      );
    }

    if (file.size > 25 * 1024 * 1024) {
      return NextResponse.json(
        { ok: false, error: 'File exceeds maximum size of 25 MB.' },
        { status: 400 }
      );
    }

    const uploadsDir = path.join(process.cwd(), 'public', 'uploads');
    if (!fs.existsSync(uploadsDir)) {
      fs.mkdirSync(uploadsDir, { recursive: true });
    }

    const safeBase = path
      .basename(file.name, ext)
      .toLowerCase()
      .replace(/[^a-z0-9_-]/g, '-')
      .slice(0, 40);
    const filename = `${Date.now()}-${safeBase || 'media'}${ext}`;
    const targetPath = path.join(uploadsDir, filename);

    const bytes = Buffer.from(await file.arrayBuffer());
    fs.writeFileSync(targetPath, bytes);

    const publicUrl = `/uploads/${filename}`;
    logAdminActivity(user.id, user.username, 'media.upload', `Uploaded ${publicUrl}`);

    return NextResponse.json({ ok: true, url: publicUrl });
  } catch (err) {
    console.error('Upload error:', err);
    return NextResponse.json({ ok: false, error: 'Failed to save uploaded file.' }, { status: 500 });
  }
}
