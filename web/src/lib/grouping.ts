import type { Experience, Project, Technology, TechnologyCategory, TechnologyGroup, WorkCase } from '@/lib/api'

export type RoleEntry = { experience: Experience; cases: WorkCase[] }
export type OrganizationGroup = { key: string; organization: string | null; roles: RoleEntry[] }
export type ExperienceTimeline = { organizations: OrganizationGroup[]; unlinkedCases: WorkCase[] }

/**
 * Organizations in order of first appearance, each with its roles in API
 * order and each role with its linked cases. A case whose experience is not
 * public (or that has none) goes to `unlinkedCases`.
 */
export function groupExperience(experiences: Experience[], workCases: WorkCase[]): ExperienceTimeline {
  const publicKeys = new Set(experiences.map((experience) => experience.key))
  const casesByRole = new Map<string, WorkCase[]>()
  const unlinkedCases: WorkCase[] = []

  for (const workCase of workCases) {
    if (workCase.experience_key !== null && publicKeys.has(workCase.experience_key)) {
      casesByRole.set(workCase.experience_key, [...(casesByRole.get(workCase.experience_key) ?? []), workCase])
    } else {
      unlinkedCases.push(workCase)
    }
  }

  const organizations: OrganizationGroup[] = []
  const byOrganization = new Map<string, OrganizationGroup>()

  for (const experience of experiences) {
    const role: RoleEntry = { experience, cases: casesByRole.get(experience.key) ?? [] }
    const existing = experience.organization === null ? undefined : byOrganization.get(experience.organization)

    if (existing) {
      existing.roles.push(role)
      continue
    }

    const group: OrganizationGroup = { key: experience.key, organization: experience.organization, roles: [role] }
    organizations.push(group)
    if (experience.organization !== null) byOrganization.set(experience.organization, group)
  }

  return { organizations, unlinkedCases }
}

export type ProjectGroups = { client: Project[]; personal: Project[] }

export function groupProjects(projects: Project[]): ProjectGroups {
  return {
    client: projects.filter((project) => project.kind === 'client'),
    personal: projects.filter((project) => project.kind === 'personal'),
  }
}

/** Projects shown per group before "Show more projects". */
export const PROJECT_PREVIEW_LIMIT = 3

export function previewItems<T>(items: T[], expanded: boolean, limit = PROJECT_PREVIEW_LIMIT): { visible: T[]; hiddenCount: number } {
  if (expanded || items.length <= limit) return { visible: items, hiddenCount: 0 }
  return { visible: items.slice(0, limit), hiddenCount: items.length - limit }
}

export type TechnologyColumn = { key: TechnologyCategory; label: string; names: string[] }

/** Stack columns in the order and with the labels of `site.technology_groups`; empty columns are skipped. */
export function groupTechnologies(technologies: Technology[], groups: TechnologyGroup[]): TechnologyColumn[] {
  return groups
    .map((group) => ({
      key: group.key,
      label: group.label,
      names: technologies.filter((technology) => technology.category === group.key).map((technology) => technology.name),
    }))
    .filter((column) => column.names.length > 0)
}
