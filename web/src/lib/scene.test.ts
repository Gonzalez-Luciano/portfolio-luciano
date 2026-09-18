import { describe, expect, it } from 'vitest'
import { buildScene, type SceneInput } from '@/lib/scene'

const input: SceneInput = {
  profile: {
    name: 'Synthetic Engineer',
    location: null,
    work_modes: [],
    headline: 'Synthetic headline',
    short_summary: 'Synthetic summary.',
    introduction: 'Synthetic introduction.',
    availability: 'Synthetic availability.',
    statement: { lead: 'Lead', emphasis: 'Emphasis', tail: 'Tail' },
    closing: { line_one: 'Line one', line_two: 'Line two' },
    cta: 'Synthetic CTA',
    photo: { url: '/storage/profiles/photo.png', alt: 'Synthetic portrait' },
  },
  site: {
    projects_empty_message: 'Empty.',
    contact_intro: 'Intro.',
    technology_groups: [],
    professional_links: [
      { key: 'linkedin', label: 'LinkedIn', href: 'https://linkedin.test/synthetic' },
      { key: 'email', label: 'Email me', href: 'mailto:synthetic@example.test' },
    ],
    work_principles: [],
    education: [],
    languages: [],
    cv: null,
  },
  technologies: [
    { key: 'php', name: 'PHP', category: 'backend' },
    { key: 'mysql', name: 'MySQL', category: 'data' },
    { key: 'laravel', name: 'Laravel', category: 'backend' },
  ],
}

describe('buildScene', () => {
  it('maps published CMS content onto the three beats', () => {
    const scene = buildScene(input)

    expect(scene.name).toBe('Synthetic Engineer')
    expect(scene.headline).toBe('Synthetic headline')
    expect(scene.photo).toEqual({ url: '/storage/profiles/photo.png', alt: 'Synthetic portrait' })
    expect(scene.statement).toEqual({ lead: 'Lead', emphasis: 'Emphasis', tail: 'Tail' })
    expect(scene.closing).toEqual({ lineOne: 'Line one', lineTwo: 'Line two' })
    expect(scene.eyebrow).toBe('PHP | Laravel')
    expect(scene.email?.href).toBe('mailto:synthetic@example.test')
  })

  it('falls back to approved profile fields when optional groups are empty', () => {
    const scene = buildScene({
      ...input,
      profile: { ...input.profile, statement: null, closing: null, photo: null },
      site: { ...input.site, professional_links: [] },
      technologies: [],
    })

    expect(scene.statement).toBeNull()
    expect(scene.summary).toBe('Synthetic summary.')
    expect(scene.closing).toEqual({ lineOne: 'Synthetic availability.', lineTwo: null })
    expect(scene.eyebrow).toBeNull()
    expect(scene.email).toBeNull()
    expect(scene.photo).toBeNull()
  })
})
