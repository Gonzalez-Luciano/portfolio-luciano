export type SeoLocale = 'es' | 'en'

export type SeoEntry = Readonly<{
  lang: SeoLocale
  canonical: string
  title: string
  description: string
  ogLocale: 'es_AR' | 'en_US'
  ogAlternate: 'es_AR' | 'en_US'
}>

export type HreflangLink = Readonly<{
  hreflang: 'es' | 'en' | 'x-default'
  href: string
}>

export type StructuredDataGraph = Readonly<{
  '@context': 'https://schema.org'
  '@graph': readonly [Record<string, unknown>, Record<string, unknown>]
}>

export const SITE_ORIGIN = 'https://lucianogonzalez.dev'
export const SOCIAL_IMAGE_PATH = '/social/luciano-gonzalez-social.jpg'
export const SOCIAL_IMAGE_ALT = 'Luciano González — Backend Engineer | PHP & Laravel'

export const SEO_BY_LOCALE: Readonly<Record<SeoLocale, SeoEntry>> = {
  es: {
    lang: 'es',
    canonical: `${SITE_ORIGIN}/`,
    title: 'Luciano González — Backend Engineer | PHP & Laravel',
    description: 'Desarrollo backend orientado a APIs, lógica de negocio, datos y mantenimiento de aplicaciones.',
    ogLocale: 'es_AR',
    ogAlternate: 'en_US',
  },
  en: {
    lang: 'en',
    canonical: `${SITE_ORIGIN}/en`,
    title: 'Luciano González — Backend Engineer | PHP & Laravel',
    description: 'Backend development focused on APIs, business logic, data, and application maintenance.',
    ogLocale: 'en_US',
    ogAlternate: 'es_AR',
  },
}

export const HREFLANG_LINKS: readonly HreflangLink[] = [
  { hreflang: 'es', href: SEO_BY_LOCALE.es.canonical },
  { hreflang: 'en', href: SEO_BY_LOCALE.en.canonical },
  { hreflang: 'x-default', href: SEO_BY_LOCALE.es.canonical },
]

export function structuredDataGraph(): StructuredDataGraph {
  return {
    '@context': 'https://schema.org',
    '@graph': [
      {
        '@id': `${SITE_ORIGIN}/#person`,
        '@type': 'Person',
        name: 'Luciano González',
        url: `${SITE_ORIGIN}/`,
        jobTitle: 'Backend Engineer',
        sameAs: [
          'https://github.com/Gonzalez-Luciano',
          'https://www.linkedin.com/in/luciano-gonzález-590350294',
        ],
      },
      {
        '@id': `${SITE_ORIGIN}/#website`,
        '@type': 'WebSite',
        url: `${SITE_ORIGIN}/`,
        name: 'Luciano González',
        inLanguage: ['es', 'en'],
        author: { '@id': `${SITE_ORIGIN}/#person` },
      },
    ],
  }
}

export function renderSitemap(): string {
  const entries = (Object.keys(SEO_BY_LOCALE) as SeoLocale[])
    .map((locale) => {
      const links = HREFLANG_LINKS.map(
        ({ hreflang, href }) => `    <xhtml:link rel="alternate" hreflang="${hreflang}" href="${href}" />`,
      ).join('\n')

      return `  <url>\n    <loc>${SEO_BY_LOCALE[locale].canonical}</loc>\n${links}\n  </url>`
    })
    .join('\n')

  return `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">\n${entries}\n</urlset>\n`
}
