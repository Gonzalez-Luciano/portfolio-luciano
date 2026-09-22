import { describe, expect, it } from 'vitest'
import { HREFLANG_LINKS, SEO_BY_LOCALE, renderSitemap, structuredDataGraph } from '../../seo.config'

describe('localized SEO contract', () => {
  it('keeps the approved canonical URLs and copy', () => {
    expect(SEO_BY_LOCALE.es).toMatchObject({
      lang: 'es',
      canonical: 'https://lucianogonzalez.dev/',
      title: 'Luciano González — Backend Engineer | PHP & Laravel',
      description: 'Desarrollo backend orientado a APIs, lógica de negocio, datos y mantenimiento de aplicaciones.',
      ogLocale: 'es_AR',
    })
    expect(SEO_BY_LOCALE.en).toMatchObject({
      lang: 'en',
      canonical: 'https://lucianogonzalez.dev/en',
      description: 'Backend development focused on APIs, business logic, data, and application maintenance.',
      ogLocale: 'en_US',
    })
    expect(HREFLANG_LINKS).toEqual([
      { hreflang: 'es', href: 'https://lucianogonzalez.dev/' },
      { hreflang: 'en', href: 'https://lucianogonzalez.dev/en' },
      { hreflang: 'x-default', href: 'https://lucianogonzalez.dev/' },
    ])
  })

  it('emits reciprocal XHTML sitemap alternates from the same source', () => {
    const xml = renderSitemap()
    expect(xml).toContain('xmlns:xhtml="http://www.w3.org/1999/xhtml"')
    expect(xml.match(/<url>/g)).toHaveLength(2)
    expect(xml.match(/<loc>https:\/\/lucianogonzalez\.dev\/<\/loc>/g)).toHaveLength(1)
    expect(xml.match(/<loc>https:\/\/lucianogonzalez\.dev\/en<\/loc>/g)).toHaveLength(1)
    expect(xml.match(/hreflang="es" href="https:\/\/lucianogonzalez\.dev\//g)).toHaveLength(2)
    expect(xml.match(/hreflang="en" href="https:\/\/lucianogonzalez\.dev\/en"/g)).toHaveLength(2)
    expect(xml.match(/hreflang="x-default" href="https:\/\/lucianogonzalez\.dev\//g)).toHaveLength(2)
    expect(xml).not.toMatch(/<lastmod>|<changefreq>|<priority>|\/es</)
  })

  it('limits structured data to Person and WebSite', () => {
    expect(structuredDataGraph()['@graph'].map((node) => node['@type'])).toEqual(['Person', 'WebSite'])
  })
})
