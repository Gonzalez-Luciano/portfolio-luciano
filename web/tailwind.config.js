/** Theme tokens are RGB channel triplets defined in src/index.css so Tailwind alpha modifiers work. */
const token = (name) => `rgb(var(--color-${name}) / <alpha-value>)`

/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        canvas: token('canvas'),
        surface: token('surface'),
        ink: token('text'),
        muted: token('text-muted'),
        accent: token('accent'),
        line: token('border'),
        node: token('node'),
        // The scroll scene tells day and night itself, so its colors ignore the theme.
        scene: {
          ink: '#2D251B',
          accent: '#9A4E2A',
          muted: '#665244',
          light: '#F3E9DD',
          glow: '#E08E5E',
          node: '#E38B50',
          veil: '#1A1411',
        },
      },
      fontFamily: {
        display: ['"Fraunces Variable"', 'Georgia', 'serif'],
        sans: ['"Instrument Sans"', 'system-ui', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
      },
      maxWidth: {
        content: '90rem',
      },
    },
  },
  plugins: [],
}
