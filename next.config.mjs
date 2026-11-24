import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/** @type {import('next').NextConfig} */
const nextConfig = {
  outputFileTracingRoot: __dirname,
  serverExternalPackages: ['node:sqlite'],
  images: {
    remotePatterns: [
      { protocol: 'https', hostname: '**' },
      { protocol: 'http', hostname: '**' }
    ]
  },
  async redirects() {
    return [
      { source: '/index.php', destination: '/', permanent: true },
      { source: '/news.php', destination: '/news', permanent: true },
      { source: '/calendar.php', destination: '/calendar', permanent: true },
      { source: '/join.php', destination: '/join', permanent: true },
      { source: '/contact.php', destination: '/contact', permanent: true },
      { source: '/recaudaciones.php', destination: '/support', permanent: true },
      { source: '/privacy.php', destination: '/privacy', permanent: true },
      { source: '/terms.php', destination: '/terms', permanent: true },
    ];
  }
};

export default nextConfig;
