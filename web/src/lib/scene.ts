import type { CvLink, ProfessionalLink, Statement, StructuralContent, Technology } from '@/lib/api'

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
  links: ProfessionalLink[]
  email: ProfessionalLink | null
  cv: CvLink | null
}

export type SceneInput = StructuralContent & { technologies: Technology[] }

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
    links: site.professional_links,
    email: site.professional_links.find((link) => link.key === 'email') ?? null,
    cv: site.cv,
  }
}
