import type { Photo, ProfessionalLink, Statement, StructuralContent, Technology } from '@/lib/api'

export type SceneInput = StructuralContent & { technologies: Technology[] }

/** Presentation model for the scroll scene, derived only from published CMS content. */
export type SceneContent = {
  name: string
  headline: string
  /** Three-tier statement; `null` means render `summary` as a single tier. */
  statement: Statement | null
  summary: string
  /** Closing title; without the CMS closing group it falls back to availability on one line. */
  closing: { lineOne: string; lineTwo: string | null }
  eyebrow: string | null
  photo: Photo | null
  email: ProfessionalLink | null
}

export function buildScene({ profile, site, technologies }: SceneInput): SceneContent {
  const backend = technologies.filter((technology) => technology.category === 'backend').map((technology) => technology.name)

  return {
    name: profile.name,
    headline: profile.headline,
    statement: profile.statement,
    summary: profile.short_summary,
    closing: profile.closing
      ? { lineOne: profile.closing.line_one, lineTwo: profile.closing.line_two }
      : { lineOne: profile.availability, lineTwo: null },
    eyebrow: backend.length > 0 ? backend.join(' | ') : null,
    photo: profile.photo,
    email: site.professional_links.find((link) => link.key === 'email') ?? null,
  }
}
