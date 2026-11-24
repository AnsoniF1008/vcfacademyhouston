import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'VCF Academy Houston | Official Valencia CF Youth Academy — TX',
  description:
    'Official Valencia CF youth soccer academy in Katy & Houston, TX. Identity, Effort, and Intelligence — training, tournaments, live match center, and player development.',
  icons: {
    icon: '/assets/img/favicon.svg',
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className="dark">
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap"
          rel="stylesheet"
        />
      </head>
      <body className="font-sans bg-vcf-dark text-white antialiased selection:bg-vcf-orange selection:text-black min-h-screen flex flex-col">
        {children}
      </body>
    </html>
  );
}
