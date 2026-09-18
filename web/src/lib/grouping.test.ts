import { describe, expect, it } from 'vitest'
import type { Experience, Project, Technology, WorkCase } from '@/lib/api'
import { groupExperience, groupProjects, groupTechnologies, previewItems } from '@/lib/grouping'

const experience = (key: string, organization: string | null): Experience => ({
  key,
  organization,
  role: `Role ${key}`,
  start: '2025-01',
  end: null,
  summary: 'Summary.',
  highlights: [],
  technologies: [],
})

const workCase = (key: string, experienceKey: string | null): WorkCase => ({
  key,
  experience_key: experienceKey,
  title: `Case ${key}`,
  context: 'Context.',
  problem: 'Problem.',
  contribution: 'Contribution.',
  technical_approach: 'Approach.',
  outcome: 'Outcome.',
  technologies: [],
})

const project = (key: string, kind: Project['kind']): Project => ({
  key,
  kind,
  client_name: kind === 'client' ? 'Client' : null,
  title: key,
  role: 'Backend',
  status: 'in_use',
  summary: 'Summary.',
  problem: 'Problem.',
  solution: 'Solution.',
  result: 'Result.',
  featured: false,
  images: [],
  demo_url: null,
  repository_url: null,
  technologies: [],
})

describe('groupExperience', () => {
  it('groups roles by organization in order of first appearance and attaches their cases', () => {
    const timeline = groupExperience(
      [experience('current', 'Org A'), experience('freelance', 'Org B'), experience('intern', 'Org A')],
      [workCase('c1', 'current'), workCase('c2', 'intern'), workCase('c3', 'current')],
    )

    expect(timeline.organizations.map((group) => group.organization)).toEqual(['Org A', 'Org B'])
    expect(timeline.organizations[0]?.roles.map((role) => role.experience.key)).toEqual(['current', 'intern'])
    expect(timeline.organizations[0]?.roles[0]?.cases.map((item) => item.key)).toEqual(['c1', 'c3'])
    expect(timeline.organizations[0]?.roles[1]?.cases.map((item) => item.key)).toEqual(['c2'])
    expect(timeline.unlinkedCases).toEqual([])
  })

  it('keeps each role without organization as its own group', () => {
    const timeline = groupExperience([experience('a', null), experience('b', null)], [])

    expect(timeline.organizations.map((group) => group.key)).toEqual(['a', 'b'])
    expect(timeline.organizations.every((group) => group.organization === null)).toBe(true)
  })

  it('moves unlinked cases and cases of non-public experiences to unlinkedCases', () => {
    const timeline = groupExperience([experience('current', 'Org A')], [workCase('linked', 'current'), workCase('none', null), workCase('hidden', 'hidden-role')])

    expect(timeline.unlinkedCases.map((item) => item.key)).toEqual(['none', 'hidden'])
  })

  it('returns an empty timeline for empty input', () => {
    expect(groupExperience([], [])).toEqual({ organizations: [], unlinkedCases: [] })
  })
})

describe('groupProjects', () => {
  it('splits client and personal projects preserving API order', () => {
    const groups = groupProjects([project('p1', 'personal'), project('c1', 'client'), project('p2', 'personal')])

    expect(groups.client.map((item) => item.key)).toEqual(['c1'])
    expect(groups.personal.map((item) => item.key)).toEqual(['p1', 'p2'])
  })

  it('returns empty groups', () => {
    expect(groupProjects([])).toEqual({ client: [], personal: [] })
  })
})

describe('previewItems', () => {
  it('shows the first three until expanded', () => {
    const items = [1, 2, 3, 4, 5]

    expect(previewItems(items, false)).toEqual({ visible: [1, 2, 3], hiddenCount: 2 })
    expect(previewItems(items, true)).toEqual({ visible: items, hiddenCount: 0 })
    expect(previewItems([1, 2], false)).toEqual({ visible: [1, 2], hiddenCount: 0 })
  })
})

describe('groupTechnologies', () => {
  it('follows the site group order and skips empty columns', () => {
    const technologies: Technology[] = [
      { key: 'mysql', name: 'MySQL', category: 'data' },
      { key: 'php', name: 'PHP', category: 'backend' },
      { key: 'laravel', name: 'Laravel', category: 'backend' },
    ]

    expect(
      groupTechnologies(technologies, [
        { key: 'backend', label: 'Backend' },
        { key: 'data', label: 'Datos' },
        { key: 'integration', label: 'Integraciones' },
      ]),
    ).toEqual([
      { key: 'backend', label: 'Backend', names: ['PHP', 'Laravel'] },
      { key: 'data', label: 'Datos', names: ['MySQL'] },
    ])
  })
})
