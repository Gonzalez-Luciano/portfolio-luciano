'use client';

/**
 * Self-contained root / localized-layout failure boundary (design spec §17).
 *
 * `app/[locale]/layout.tsx` is the root layout, and an `error.tsx` does not
 * catch an exception thrown by the layout in its own segment. This boundary
 * therefore owns its own `<html>` and `<body>` because the locale provider and
 * theme shell may not exist.
 *
 * It uses ONLY already-safe HARDCODED bilingual text — no next-intl, no
 * `useTranslations`, no provider dependency. It exposes NO technical detail, has
 * NO professional content, performs NO data acquisition, and adds NO
 * observability. It provides the version-appropriate `reset()` control.
 */

type GlobalErrorProps = {
  error: Error & {digest?: string};
  reset: () => void;
};

export default function GlobalError({reset}: GlobalErrorProps) {
  return (
    <html lang="es">
      <body
        style={{
          margin: 0,
          minHeight: '100vh',
          fontFamily:
            "system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
          lineHeight: 1.6,
          background: '#eee9de',
          color: '#1b1e1c',
        }}
      >
        <main
          style={{
            maxWidth: '36rem',
            margin: '0 auto',
            padding: '4rem 1.5rem',
          }}
        >
          <p
            lang="es"
            style={{fontSize: '1.25rem', fontWeight: 600, margin: '0 0 0.5rem'}}
          >
            No pudimos cargar el portfolio.
          </p>
          <p
            lang="en"
            style={{fontSize: '1.25rem', fontWeight: 600, margin: '0 0 1.5rem'}}
          >
            We couldn&apos;t load the portfolio.
          </p>
          <button
            type="button"
            onClick={() => reset()}
            style={{
              minHeight: 44,
              minWidth: 44,
              padding: '0.5rem 1.25rem',
              fontSize: '1rem',
              border: '2px solid #1b1e1c',
              borderRadius: 4,
              background: 'transparent',
              color: 'inherit',
              cursor: 'pointer',
            }}
          >
            Reintentar · Retry
          </button>
        </main>
      </body>
    </html>
  );
}
