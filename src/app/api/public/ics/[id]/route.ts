import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/lib/db';

export async function GET(
  _req: NextRequest,
  context: { params: Promise<{ id: string }> }
) {
  const { id } = await context.params;
  const matchId = Number(id);
  if (!matchId || matchId <= 0) {
    return new NextResponse('Invalid match ID', { status: 400 });
  }

  const db = getDb();
  const match = db.prepare(`
    SELECT j.id, j.fecha, j.hora, j.rival, j.cancha, j.ubicacion_mapa_url,
           t.nombre_torneo, s.nombre AS sede_nombre
    FROM juegos j
    JOIN torneos_info t ON t.id = j.torneo_id
    LEFT JOIN sedes s ON s.id = j.sede_id
    WHERE j.id = ?
  `).get(matchId) as {
    id: number;
    fecha: string;
    hora: string | null;
    rival: string | null;
    cancha: string | null;
    ubicacion_mapa_url: string | null;
    nombre_torneo: string;
    sede_nombre: string | null;
  } | undefined;

  if (!match) {
    return new NextResponse('Match not found', { status: 404 });
  }

  const title = match.rival ? `VCF Academy Houston vs ${match.rival}` : match.nombre_torneo;
  const location = [match.sede_nombre, match.cancha].filter(Boolean).join(' - ');
  const details = match.ubicacion_mapa_url ? `GPS Map: ${match.ubicacion_mapa_url}` : match.nombre_torneo;

  const timePart = match.hora ? `${match.hora}:00`.slice(0, 8) : '12:00:00';
  const startDate = new Date(`${match.fecha}T${timePart}`);
  const endDate = new Date(startDate.getTime() + 90 * 60 * 1000);

  const fmtIcs = (d: Date) => d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');

  const icsLines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//VCF Academy Houston//Match Calendar//EN',
    'BEGIN:VEVENT',
    `UID:valencia-match-${match.id}@vcfacademyhouston.com`,
    `DTSTAMP:${fmtIcs(new Date())}`,
    `DTSTART:${fmtIcs(startDate)}`,
    `DTEND:${fmtIcs(endDate)}`,
    `SUMMARY:${title.replace(/[,;\r\n]/g, ' ')}`,
    `LOCATION:${location.replace(/[,;\r\n]/g, ' ')}`,
    `DESCRIPTION:${details.replace(/[\r\n]/g, ' ')}`,
    'END:VEVENT',
    'END:VCALENDAR',
  ].join('\r\n');

  return new NextResponse(icsLines, {
    status: 200,
    headers: {
      'Content-Type': 'text/calendar; charset=utf-8',
      'Content-Disposition': `attachment; filename="vcf-match-${match.id}.ics"`,
    },
  });
}
