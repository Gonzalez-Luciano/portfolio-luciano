import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  ApiError,
  isExperienceList,
  isProfile,
  isProjectList,
  isSite,
  isTechnologyList,
  isWorkCaseList,
  loadRegion,
  loadStructuralContent,
} from '@/lib/api'

const technology = { key: 'php', name: 'PHP', category: 'backend', icon: null }

const profile = {
  name: 'Synthetic Engineer',
  location: 'Synthetic City',
  work_modes: ['on_site', 'remote'],
  headline: 'Synthetic headline',
  short_summary: 'Synthetic summary.',
  introduction: 'Synthetic introduction.',
  availability: 'Synthetic availability.',
  statement: { lead: 'Lead', emphasis: 'Emphasis', tail: 'Tail' },
  closing: null,
  cta: 'Synthetic CTA',
  photo: { url: '/storage/profiles/photo.png', alt: 'Synthetic portrait' },
}

const site = {
  projects_empty_message: 'Empty.',
  contact_intro: 'Intro.',
  technology_groups: [
    { key: 'backend', label: 'Backend' },
    { key: 'data', label: 'Data' },
    { key: 'integration', label: 'Integrations' },
    { key: 'collaboration', label: 'Collaboration' },
  ],
  professional_links: [{ key: 'email', label: 'Email me', href: 'mailto:synthetic@example.test' }],
  expertise_areas: [],
  work_principles: [{ key: 'quality', statement: 'Synthetic principle.' }],
  education: [{ key: 'school', institution: 'Synthetic School', program: 'Diploma', detail: null, start_year: null, end_year: 2021 }],
  languages: [{ key: 'english', name: 'English', level: 'b2' }],
  cv: null,
}

const experience = {
  key: 'role',
  organization: 'Synthetic Org',
  role: 'Engineer',
  start: '2025-09',
  end: null,
  summary: 'Summary.',
  highlights: ['Highlight.'],
  technologies: [technology],
}

const workCase = {
  key: 'case',
  experience_key: 'role',
  title: 'Case',
  context: 'Context.',
  problem: 'Problem.',
  contribution: 'Contribution.',
  technical_approach: 'Approach.',
  outcome: 'Outcome.',
  technologies: [],
}

const project = {
  key: 'project',
  kind: 'client',
  client_name: 'Synthetic Client',
  title: 'Project',
  role: 'Backend',
  status: 'in_use',
  summary: 'Summary.',
  problem: 'Problem.',
  solution: 'Solution.',
  result: 'Result.',
  featured: false,
  images: [{ url: '/storage/projects/a.webp', alt: 'Screenshot' }],
  demo_url: null,
  repository_url: 'https://example.test/repo',
  technologies: [technology],
}

function json(data: unknown, status = 200): Response {
  return new Response(JSON.stringify(data), { status, headers: { 'content-type': 'application/json' } })
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('validators', () => {
  it('accept the documented shapes and tolerate extra fields', () => {
    expect(isProfile(profile)).toBe(true)
    expect(isProfile({ ...profile, photo: null, location: null, work_modes: [] })).toBe(true)
    expect(isSite(site)).toBe(true)
    expect(isTechnologyList([technology])).toBe(true)
    expect(isExperienceList([experience, { ...experience, organization: null, end: '2025-09' }])).toBe(true)
    expect(isWorkCaseList([workCase, { ...workCase, experience_key: null }])).toBe(true)
    expect(isProjectList([project, { ...project, kind: 'personal', client_name: null, images: [] }])).toBe(true)
  })

  it('reject missing, mistyped, or out-of-contract values', () => {
    expect(isProfile({ ...profile, work_modes: ['office'] })).toBe(false)
    expect(isProfile({ ...profile, photo: { url: '/x.png' } })).toBe(false)
    expect(isSite({ ...site, languages: [{ key: 'x', name: 'X', level: 'fluent' }] })).toBe(false)
    expect(isSite({ ...site, education: [{ ...site.education[0], end_year: '2021' }] })).toBe(false)
    expect(isTechnologyList([{ ...technology, category: 'frontend' }])).toBe(false)
    expect(isExperienceList([{ ...experience, start: '2025-9' }])).toBe(false)
    expect(isWorkCaseList([{ ...workCase, experience_key: 3 }])).toBe(false)
    expect(isProjectList([{ ...project, status: 'archived' }])).toBe(false)
    expect(isProjectList([{ ...project, images: [{ url: '/a.webp' }] }])).toBe(false)
  })
})

describe('loadStructuralContent', () => {
  it('requests profile and site for the locale', async () => {
    const fetchMock = vi.fn(async (input: RequestInfo | URL) =>
      String(input).endsWith('/profile') ? json({ data: profile }) : json({ data: site }),
    )
    vi.stubGlobal('fetch', fetchMock)

    const content = await loadStructuralContent('en', new AbortController().signal)

    expect(fetchMock.mock.calls.map(([path]) => String(path)).sort()).toEqual(['/api/v1/en/profile', '/api/v1/en/site'])
    expect(content.profile.name).toBe('Synthetic Engineer')
    expect(content.site.languages).toHaveLength(1)
  })

  it('rejects when either structural resource fails or is malformed', async () => {
    vi.stubGlobal('fetch', vi.fn(async (input: RequestInfo | URL) =>
      String(input).endsWith('/profile') ? json({ error: { code: 'not_found' } }, 404) : json({ data: site }),
    ))
    await expect(loadStructuralContent('es', new AbortController().signal)).rejects.toBeInstanceOf(ApiError)

    vi.stubGlobal('fetch', vi.fn(async (input: RequestInfo | URL) =>
      String(input).endsWith('/profile') ? json({ data: profile }) : json({ data: { ...site, education: null } }),
    ))
    await expect(loadStructuralContent('es', new AbortController().signal)).rejects.toBeInstanceOf(ApiError)
  })
})

describe('loadRegion', () => {
  it('requests one collection and returns validated data', async () => {
    const fetchMock = vi.fn(async (_input: RequestInfo | URL) => json({ data: [project] }))
    vi.stubGlobal('fetch', fetchMock)

    const projects = await loadRegion('es', 'projects', new AbortController().signal)

    expect(String(fetchMock.mock.calls[0]?.[0])).toBe('/api/v1/es/projects')
    expect(projects[0]?.client_name).toBe('Synthetic Client')
  })

  it('rejects a failed or malformed collection', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => json({ error: { code: 'content_temporarily_unavailable' } }, 503)))
    await expect(loadRegion('es', 'work-cases', new AbortController().signal)).rejects.toBeInstanceOf(ApiError)

    vi.stubGlobal('fetch', vi.fn(async () => json({ data: [{ key: 'bad' }] })))
    await expect(loadRegion('es', 'experiences', new AbortController().signal)).rejects.toBeInstanceOf(ApiError)
  })
})
