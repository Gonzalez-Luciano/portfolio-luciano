import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it } from 'vitest'
import { ContactSection } from '@/components/ContactSection'
import { uiCopy } from '@/content'
import type { Site } from '@/lib/api'

const site: Site = {
  projects_empty_message: '',
  contact_intro: 'Let us work together.',
  technology_groups: [],
  professional_links: [
    { key: 'email', label: 'Email', href: 'mailto:luciano@example.test' },
    { key: 'linkedin', label: 'LinkedIn', href: 'https://www.linkedin.com/in/luciano' },
    { key: 'github', label: 'GitHub', href: 'https://github.com/Gonzalez-Luciano' },
  ],
  work_principles: [],
  education: [],
  languages: [],
  cv: { url: '/cv/luciano-gonzalez-en.pdf', label: 'Download CV' },
}

describe('ContactSection', () => {
  it('instruments only its professional and CV contact links', () => {
    const markup = renderToStaticMarkup(createElement(ContactSection, { ui: uiCopy('en'), site }))

    expect(markup).toContain('data-umami-event="email-click"')
    expect(markup).toContain('data-umami-event="linkedin-click"')
    expect(markup).toContain('data-umami-event="github-click"')
    expect(markup).toContain('data-umami-event="cv-download"')
    expect(markup).not.toMatch(/data-umami-event-[^=]+=/)
  })
})
