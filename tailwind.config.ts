import type { Config } from 'tailwindcss';

const config: Config = {
  content: [
    './src/pages/**/*.{js,ts,jsx,tsx,mdx}',
    './src/components/**/*.{js,ts,jsx,tsx,mdx}',
    './src/app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        vcf: {
          orange: '#FF6600',
          'orange-light': '#FF8533',
          'orange-dark': '#CC5200',
          gold: '#D4AF37',
          dark: '#0A0A0C',
          surface: '#121216',
          card: '#18181E',
          border: '#262630',
          muted: '#9CA3AF',
        }
      },
      fontFamily: {
        display: ['var(--font-oswald)', 'sans-serif'],
        sans: ['var(--font-montserrat)', 'sans-serif'],
      },
      backgroundImage: {
        'vcf-gradient': 'linear-gradient(135deg, #FF6600 0%, #E85D04 50%, #CC4400 100%)',
        'dark-radial': 'radial-gradient(circle at top right, rgba(255, 102, 0, 0.16), transparent 60%)',
      }
    },
  },
  plugins: [],
};

export default config;
