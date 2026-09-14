/**
 * Interface strings only. Professional content (name, headline, statement,
 * closing, links, CV) comes from the Laravel public API; never add it here.
 */
import type { Locale } from '@/lib/api'

export type { Locale }

export type UiCopy = {
  navLabel: string
  menu: { label: string; title: string; close: string }
  next: string
  down: string
  up: string
  failure: string
  retry: string
  contact: string
}

/** `/en` and `/en/...` render English; `/`, `/es` and anything else render Spanish. */
export function resolveLocale(pathname: string): Locale {
  return /^\/en(\/|$)/.test(pathname) ? 'en' : 'es'
}

const COPY: Record<Locale, UiCopy> = {
  es: {
    navLabel: 'Navegación principal',
    menu: { label: 'Menú', title: 'Navegación', close: 'Cerrar' },
    next: 'Siguiente sección',
    down: 'Siguiente sección',
    up: 'Volver al inicio',
    failure: 'No pudimos cargar el contenido.',
    retry: 'Reintentar',
    contact: 'Contacto',
  },
  en: {
    navLabel: 'Primary navigation',
    menu: { label: 'Menu', title: 'Navigation', close: 'Close' },
    next: 'Next section',
    down: 'Next section',
    up: 'Back to top',
    failure: 'We could not load the content.',
    retry: 'Retry',
    contact: 'Contact',
  },
}

export function uiCopy(locale: Locale): UiCopy {
  return COPY[locale]
}
