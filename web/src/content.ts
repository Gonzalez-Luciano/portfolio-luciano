/**
 * Interface strings only. Professional content (name, headline, statement,
 * experience, projects, links, CV) comes from the Laravel public API; never add it here.
 */
import type { DeliveryStatus, LanguageLevel, Locale, ProjectKind, WorkMode } from '@/lib/api'
import type { SectionId } from '@/lib/sections'

export type { Locale }

export type UiCopy = {
  skipToContent: string
  navLabel: string
  backToTop: string
  menu: { open: string; close: string; title: string }
  theme: { toDark: string; toLight: string }
  language: { switchTo: string }
  cvLabel: string
  sections: Record<SectionId, { nav: string; title: string }>
  loading: string
  failure: string
  regionFailure: string
  retry: string
  experience: {
    otherCases: string
    caseLabels: { context: string; problem: string; contribution: string; approach: string; outcome: string; technologies: string }
  }
  projects: {
    groups: Record<ProjectKind, string>
    kind: Record<ProjectKind, string>
    status: Record<DeliveryStatus, string>
    labels: { problem: string; solution: string; result: string; stack: string; demo: string; repository: string }
    showMore: string
    gallery: {
      viewFull: string
      previous: string
      next: string
      close: string
      more: (count: number) => string
      image: (position: number, total: number) => string
    }
  }
  about: { education: string; languages: string; location: string; modes: Record<WorkMode, string>; levels: Record<LanguageLevel, string> }
  contact: { newTab: string }
}

/** `/en` and `/en/...` render English; `/`, `/es` and anything else render Spanish. */
export function resolveLocale(pathname: string): Locale {
  return /^\/en(\/|$)/.test(pathname) ? 'en' : 'es'
}

export function alternateLocale(locale: Locale): Locale {
  return locale === 'es' ? 'en' : 'es'
}

export function localePath(locale: Locale, hash = ''): string {
  return `${locale === 'en' ? '/en' : '/'}${hash}`
}

const LEVELS: Omit<Record<LanguageLevel, string>, 'native'> = { a1: 'A1', a2: 'A2', b1: 'B1', b2: 'B2', c1: 'C1', c2: 'C2' }

const COPY: Record<Locale, UiCopy> = {
  es: {
    skipToContent: 'Saltar al contenido',
    navLabel: 'Navegación principal',
    backToTop: 'Volver al inicio',
    menu: { open: 'Menú', close: 'Cerrar menú', title: 'Índice' },
    theme: { toDark: 'Cambiar a tema oscuro', toLight: 'Cambiar a tema claro' },
    language: { switchTo: 'Ver en inglés' },
    cvLabel: 'CV',
    sections: {
      experience: { nav: 'Experiencia', title: 'Experiencia laboral' },
      projects: { nav: 'Proyectos', title: 'Proyectos' },
      stack: { nav: 'Stack', title: 'Stack' },
      about: { nav: 'Sobre mí', title: 'Sobre mí' },
      contact: { nav: 'Contacto', title: 'Contacto' },
    },
    loading: 'Cargando…',
    failure: 'No pudimos cargar el contenido.',
    regionFailure: 'No pudimos cargar esta sección.',
    retry: 'Reintentar',
    experience: {
      otherCases: 'Otros casos',
      caseLabels: {
        context: 'Contexto',
        problem: 'Problema',
        contribution: 'Aporte',
        approach: 'Enfoque técnico',
        outcome: 'Resultado',
        technologies: 'Tecnologías',
      },
    },
    projects: {
      groups: { client: 'Para clientes', personal: 'Personales' },
      kind: { client: 'Para cliente', personal: 'Personal' },
      status: { in_production: 'En producción', in_use: 'En uso', public_demo: 'Demo pública', in_development: 'En desarrollo' },
      labels: { problem: 'Problema', solution: 'Solución', result: 'Resultado', stack: 'Stack', demo: 'Ver demo', repository: 'Ver código' },
      showMore: 'Ver más proyectos',
      gallery: {
        viewFull: 'Ver en tamaño completo',
        previous: 'Captura anterior',
        next: 'Captura siguiente',
        close: 'Cerrar visor',
        more: (count) => `+${count}`,
        image: (position, total) => `Captura ${position} de ${total}`,
      },
    },
    about: {
      education: 'Formación',
      languages: 'Idiomas',
      location: 'Ubicación · Modalidades',
      modes: { on_site: 'Presencial', hybrid: 'Híbrido', remote: 'Remoto' },
      levels: { native: 'Nativo', ...LEVELS },
    },
    contact: { newTab: '(se abre en una pestaña nueva)' },
  },
  en: {
    skipToContent: 'Skip to content',
    navLabel: 'Primary navigation',
    backToTop: 'Back to top',
    menu: { open: 'Menu', close: 'Close menu', title: 'Index' },
    theme: { toDark: 'Switch to dark theme', toLight: 'Switch to light theme' },
    language: { switchTo: 'View in Spanish' },
    cvLabel: 'CV',
    sections: {
      experience: { nav: 'Experience', title: 'Work experience' },
      projects: { nav: 'Projects', title: 'Projects' },
      stack: { nav: 'Stack', title: 'Stack' },
      about: { nav: 'About', title: 'About me' },
      contact: { nav: 'Contact', title: 'Contact' },
    },
    loading: 'Loading…',
    failure: 'We could not load the content.',
    regionFailure: 'We could not load this section.',
    retry: 'Retry',
    experience: {
      otherCases: 'Other cases',
      caseLabels: {
        context: 'Context',
        problem: 'Problem',
        contribution: 'Contribution',
        approach: 'Technical approach',
        outcome: 'Outcome',
        technologies: 'Technologies',
      },
    },
    projects: {
      groups: { client: 'Client projects', personal: 'Personal projects' },
      kind: { client: 'Client', personal: 'Personal' },
      status: { in_production: 'In production', in_use: 'In use', public_demo: 'Public demo', in_development: 'In development' },
      labels: { problem: 'Problem', solution: 'Solution', result: 'Result', stack: 'Stack', demo: 'View demo', repository: 'View code' },
      showMore: 'Show more projects',
      gallery: {
        viewFull: 'View full size',
        previous: 'Previous screenshot',
        next: 'Next screenshot',
        close: 'Close viewer',
        more: (count) => `+${count}`,
        image: (position, total) => `Screenshot ${position} of ${total}`,
      },
    },
    about: {
      education: 'Education',
      languages: 'Languages',
      location: 'Location · Work modes',
      modes: { on_site: 'On-site', hybrid: 'Hybrid', remote: 'Remote' },
      levels: { native: 'Native', ...LEVELS },
    },
    contact: { newTab: '(opens in a new tab)' },
  },
}

export function uiCopy(locale: Locale): UiCopy {
  return COPY[locale]
}
