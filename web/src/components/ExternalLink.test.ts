import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it } from 'vitest'
import { ExternalLink } from '@/components/ExternalLink'

describe('ExternalLink', () => {
  it('adds only the optional fixed Umami event attribute', () => {
    const markup = renderToStaticMarkup(
      createElement(ExternalLink, {
        href: 'https://github.com/Gonzalez-Luciano',
        label: 'GitHub',
        newTab: 'Opens in a new tab',
        analyticsEvent: 'github-click',
      }),
    )

    expect(markup).toContain('data-umami-event="github-click"')
    expect(markup).not.toMatch(/data-umami-event-[^=]+=/)
  })
})
