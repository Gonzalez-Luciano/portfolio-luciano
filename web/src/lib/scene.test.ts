import { describe, expect, it } from 'vitest'
import type { PublicContent } from '@/lib/api'
import { buildScene } from '@/lib/scene'

const content: PublicContent = {
  profile: {
    name: 'Synthetic Engineer',
    headline: 'Synthetic headline',
    short_summary: 'Synthetic summary.',
    availability: 'Synthetic availability.',
    statement: { lead: 'Lead', emphasis: 'Emphasis', tail: 'Tail' },
    closing: { line_one: 'Line one', line_two: 'Line two' },
  },
  site: {
    professional_links: [
      { key: 'linkedin', label: 'LinkedIn', href: 'https://linkedin.test/synthetic' },
      { key: 'email', label: 'Email me', href: 'mailto:synthetic@example.test' },
    ],
    cv: { url: '/cv/luciano-gonzalez-en.pdf', label: 'Download CV' },
  },
  technologies: [
    { key: 'php', name: 'PHP', category: 'backend' },
    { key: 'mysql', name: 'MySQL', category: 'data' },
    { key: 'laravel', name: 'Laravel', category: 'backend' },
  ],
}

describe('buildScene', () => {
  it('maps published CMS content onto the scene', () => {
    const scene = buildScene(content)

    expect(scene.name).toBe('Synthetic Engineer')
    expect(scene.headline).toBe('Synthetic headline')
    expect(scene.statement).toEqual({ lead: 'Lead', emphasis: 'Emphasis', tail: 'Tail' })
    expect(scene.closing).toEqual({ lineOne: 'Line one', lineTwo: 'Line two' })
    expect(scene.eyebrow).toBe('PHP | Laravel')
    expect(scene.email?.href).toBe('mailto:synthetic@example.test')
    expect(scene.links).toHaveLength(2)
    expect(scene.cv?.label).toBe('Download CV')
  })

  it('falls back to approved profile fields when optional groups are empty', () => {
    const scene = buildScene({
      ...content,
      profile: { ...content.profile, statement: null, closing: null },
      site: { professional_links: [], cv: null },
      technologies: [],
    })

    expect(scene.statement).toBeNull()
    expect(scene.summary).toBe('Synthetic summary.')
    expect(scene.closing).toEqual({ lineOne: 'Synthetic availability.', lineTwo: null })
    expect(scene.eyebrow).toBeNull()
    expect(scene.email).toBeNull()
    expect(scene.cv).toBeNull()
  })
})
