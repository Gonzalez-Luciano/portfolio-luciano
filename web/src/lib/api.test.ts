import { afterEach, describe, expect, it, vi } from 'vitest'
import { ApiError, isProfile, isSite, isTechnologyList, loadPublicContent } from '@/lib/api'

const profile = {
  name: 'Synthetic Engineer',
  headline: 'Synthetic headline',
  short_summary: 'Synthetic summary.',
  introduction: 'Synthetic introduction.',
  availability: 'Synthetic availability.',
  statement: { lead: 'Lead', emphasis: 'Emphasis', tail: 'Tail' },
  closing: null,
  cta: 'Synthetic CTA',
  photo: null,
}

const site = {
  projects_empty_message: 'Empty.',
  contact_intro: 'Intro.',
  technology_groups: [],
  professional_links: [
    { key: 'github', label: 'GitHub', href: 'https://github.test/synthetic' },
    { key: 'email', label: 'Email me', href: 'mailto:synthetic@example.test' },
  ],
  expertise_areas: [],
  work_principles: [],
  cv: null,
}

const technologies = [
  { key: 'php', name: 'PHP', category: 'backend', icon: null },
  { key: 'mysql', name: 'MySQL', category: 'data', icon: null },
]

function json(data: unknown, status = 200): Response {
  return new Response(JSON.stringify(data), { status, headers: { 'content-type': 'application/json' } })
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('validators', () => {
  it('accepts the documented profile shape and tolerates extra fields', () => {
    expect(isProfile(profile)).toBe(true)
    expect(isProfile({ ...profile, statement: null, closing: { line_one: 'One', line_two: 'Two' } })).toBe(true)
  })

  it('rejects missing, wrong, or partial profile groups', () => {
    expect(isProfile({ ...profile, headline: null })).toBe(false)
    expect(isProfile({ ...profile, statement: { lead: 'Lead' } })).toBe(false)
    const { closing: _closing, ...withoutClosing } = profile
    expect(isProfile(withoutClosing)).toBe(false)
  })

  it('validates site links and cv', () => {
    expect(isSite(site)).toBe(true)
    expect(isSite({ ...site, cv: { url: '/cv/luciano-gonzalez-es.pdf', label: 'Descargar CV' } })).toBe(true)
    expect(isSite({ ...site, professional_links: [{ key: 'twitter', label: 'X', href: 'https://x.test' }] })).toBe(false)
    expect(isSite({ ...site, cv: { url: '/cv.pdf' } })).toBe(false)
  })

  it('validates technology categories', () => {
    expect(isTechnologyList(technologies)).toBe(true)
    expect(isTechnologyList([{ key: 'x', name: 'X', category: 'frontend' }])).toBe(false)
  })
})

describe('loadPublicContent', () => {
  it('requests the three locale endpoints and returns validated data', async () => {
    const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
      const path = String(input)
      if (path.endsWith('/profile')) return json({ data: profile })
      if (path.endsWith('/site')) return json({ data: site })
      return json({ data: technologies })
    })
    vi.stubGlobal('fetch', fetchMock)

    const content = await loadPublicContent('en', new AbortController().signal)

    expect(fetchMock.mock.calls.map(([path]) => String(path)).sort()).toEqual([
      '/api/v1/en/profile',
      '/api/v1/en/site',
      '/api/v1/en/technologies',
    ])
    expect(content.profile.name).toBe('Synthetic Engineer')
    expect(content.technologies).toHaveLength(2)
  })

  it('degrades a technologies failure to an empty list', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async (input: RequestInfo | URL) => {
        const path = String(input)
        if (path.endsWith('/profile')) return json({ data: profile })
        if (path.endsWith('/site')) return json({ data: site })
        return json({ error: { code: 'content_temporarily_unavailable' } }, 503)
      }),
    )

    const content = await loadPublicContent('es', new AbortController().signal)
    expect(content.technologies).toEqual([])
  })

  it('fails when a structural resource is missing or malformed', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async (input: RequestInfo | URL) => {
        const path = String(input)
        if (path.endsWith('/profile')) return json({ error: { code: 'not_found' } }, 404)
        if (path.endsWith('/site')) return json({ data: site })
        return json({ data: technologies })
      }),
    )
    await expect(loadPublicContent('es', new AbortController().signal)).rejects.toBeInstanceOf(ApiError)

    vi.stubGlobal(
      'fetch',
      vi.fn(async (input: RequestInfo | URL) => {
        const path = String(input)
        if (path.endsWith('/site')) return json({ data: { ...site, professional_links: null } })
        if (path.endsWith('/profile')) return json({ data: profile })
        return json({ data: technologies })
      }),
    )
    await expect(loadPublicContent('es', new AbortController().signal)).rejects.toBeInstanceOf(ApiError)
  })
})
