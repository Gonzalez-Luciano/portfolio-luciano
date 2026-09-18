import { describe, expect, it } from 'vitest'
import type { Profile, Site } from '@/lib/api'
import { aboutStatement, hasAboutContent, hasContactContent, sectionNumber, visibleSections } from '@/lib/sections'

const profile: Profile = {
  name: 'Synthetic',
  location: null,
  work_modes: [],
  headline: 'Headline',
  short_summary: 'Summary.',
  introduction: 'Introduction.',
  availability: 'Availability.',
  statement: null,
  closing: null,
  cta: 'CTA',
  photo: null,
}

const site: Site = {
  projects_empty_message: 'Empty.',
  contact_intro: 'Intro.',
  technology_groups: [],
  professional_links: [],
  work_principles: [],
  education: [],
  languages: [],
  cv: null,
}

describe('visibleSections', () => {
  it('shows every section while regions load or fail, in the fixed order', () => {
    expect(
      visibleSections({
        experience: { status: 'loading', hasItems: false },
        stack: { status: 'error', hasItems: false },
        about: true,
        contact: true,
      }),
    ).toEqual(['experience', 'projects', 'stack', 'about', 'contact'])
  })

  it('hides loaded empty regions and structural sections without content; projects always stay', () => {
    expect(
      visibleSections({
        experience: { status: 'ready', hasItems: false },
        stack: { status: 'ready', hasItems: false },
        about: false,
        contact: false,
      }),
    ).toEqual(['projects'])
  })
})

describe('section helpers', () => {
  it('numbers sections by their fixed position', () => {
    expect(sectionNumber('experience')).toBe('01')
    expect(sectionNumber('contact')).toBe('05')
  })

  it('uses the first work principle as the about statement', () => {
    expect(aboutStatement(site)).toBeNull()
    expect(aboutStatement({ ...site, work_principles: [{ key: 'a', statement: 'First.' }, { key: 'b', statement: 'Second.' }] })).toBe('First.')
  })

  it('detects about and contact content', () => {
    expect(hasAboutContent(profile, site)).toBe(false)
    expect(hasAboutContent({ ...profile, work_modes: ['remote'] }, site)).toBe(true)
    expect(hasAboutContent(profile, { ...site, languages: [{ key: 'en', name: 'English', level: 'b2' }] })).toBe(true)
    expect(hasContactContent(site)).toBe(false)
    expect(hasContactContent({ ...site, cv: { url: '/cv.pdf', label: 'CV' } })).toBe(true)
  })
})
