import type { Profile, Site } from '@/lib/api'

export type SectionId = 'experience' | 'projects' | 'stack' | 'about' | 'contact'

export const SECTION_ORDER: readonly SectionId[] = ['experience', 'projects', 'stack', 'about', 'contact']

export function sectionNumber(id: SectionId): string {
  return String(SECTION_ORDER.indexOf(id) + 1).padStart(2, '0')
}

export type RegionStatus = 'loading' | 'ready' | 'error'

export type SectionAvailability = {
  experience: { status: RegionStatus; hasItems: boolean }
  stack: { status: RegionStatus; hasItems: boolean }
  about: boolean
  contact: boolean
}

/**
 * A regional section stays while it loads or fails (it shows its own state and
 * Retry) and disappears only when it loaded empty. Projects always stay: an
 * empty result shows `site.projects_empty_message`.
 */
export function visibleSections(input: SectionAvailability): SectionId[] {
  const region = ({ status, hasItems }: { status: RegionStatus; hasItems: boolean }) => status !== 'ready' || hasItems

  const visible: Record<SectionId, boolean> = {
    experience: region(input.experience),
    projects: true,
    stack: region(input.stack),
    about: input.about,
    contact: input.contact,
  }

  return SECTION_ORDER.filter((id) => visible[id])
}

export function aboutStatement(site: Site): string | null {
  return site.work_principles[0]?.statement ?? null
}

export function hasAboutContent(profile: Profile, site: Site): boolean {
  return (
    aboutStatement(site) !== null ||
    site.education.length > 0 ||
    site.languages.length > 0 ||
    profile.location !== null ||
    profile.work_modes.length > 0
  )
}

export function hasContactContent(site: Site): boolean {
  return site.professional_links.length > 0 || site.cv !== null
}

/** A section fed by several regions (experience needs experiences and work cases). */
export function combineStatus(...statuses: RegionStatus[]): RegionStatus {
  if (statuses.includes('error')) return 'error'
  if (statuses.includes('loading')) return 'loading'
  return 'ready'
}
