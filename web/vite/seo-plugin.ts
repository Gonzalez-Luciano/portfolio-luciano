import { existsSync } from 'node:fs'
import { resolve } from 'node:path'
import type { Plugin, ResolvedConfig } from 'vite'
import {
  HREFLANG_LINKS,
  SEO_BY_LOCALE,
  SOCIAL_IMAGE_ALT,
  SOCIAL_IMAGE_PATH,
  SITE_ORIGIN,
  structuredDataGraph,
  type SeoLocale,
  renderSitemap,
} from '../seo.config.ts'

const socialImageTags = (hasSocialImage: boolean) => {
  if (!hasSocialImage) return []

  const image = `${SITE_ORIGIN}${SOCIAL_IMAGE_PATH}`
  return [
    { tag: 'meta', attrs: { property: 'og:image', content: image }, injectTo: 'head-prepend' as const },
    { tag: 'meta', attrs: { property: 'og:image:width', content: '1200' }, injectTo: 'head-prepend' as const },
    { tag: 'meta', attrs: { property: 'og:image:height', content: '630' }, injectTo: 'head-prepend' as const },
    { tag: 'meta', attrs: { property: 'og:image:alt', content: SOCIAL_IMAGE_ALT }, injectTo: 'head-prepend' as const },
    { tag: 'meta', attrs: { name: 'twitter:image', content: image }, injectTo: 'head-prepend' as const },
    { tag: 'meta', attrs: { name: 'twitter:image:alt', content: SOCIAL_IMAGE_ALT }, injectTo: 'head-prepend' as const },
  ]
}

const localeFor = (filename: string, originalUrl?: string): SeoLocale => {
  const normalizedFilename = filename.replace(/\\/g, '/')
  return originalUrl === '/en' || originalUrl?.startsWith('/en/') || normalizedFilename.endsWith('/en/index.html') ? 'en' : 'es'
}

export function localizedSeoPlugin(): Plugin {
  let config: ResolvedConfig

  const hasSocialImage = () => existsSync(resolve(config.root, `public${SOCIAL_IMAGE_PATH}`))

  return {
    name: 'localized-seo',
    configResolved(resolvedConfig) {
      config = resolvedConfig
    },
    transformIndexHtml(html, context) {
      const locale = localeFor(context.filename, context.originalUrl)
      const seo = SEO_BY_LOCALE[locale]
      const tags = [
        { tag: 'title', children: seo.title, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { name: 'description', content: seo.description }, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { name: 'robots', content: 'index, follow, max-image-preview:large' }, injectTo: 'head-prepend' as const },
        { tag: 'link', attrs: { rel: 'canonical', href: seo.canonical }, injectTo: 'head-prepend' as const },
        ...HREFLANG_LINKS.map(({ hreflang, href }) => ({
          tag: 'link',
          attrs: { rel: 'alternate', hreflang, href },
          injectTo: 'head-prepend' as const,
        })),
        { tag: 'meta', attrs: { property: 'og:type', content: 'website' }, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { property: 'og:site_name', content: 'Luciano González' }, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { property: 'og:title', content: seo.title }, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { property: 'og:description', content: seo.description }, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { property: 'og:url', content: seo.canonical }, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { property: 'og:locale', content: seo.ogLocale }, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { property: 'og:locale:alternate', content: seo.ogAlternate }, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { name: 'twitter:card', content: 'summary_large_image' }, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { name: 'twitter:title', content: seo.title }, injectTo: 'head-prepend' as const },
        { tag: 'meta', attrs: { name: 'twitter:description', content: seo.description }, injectTo: 'head-prepend' as const },
        ...socialImageTags(hasSocialImage()),
        { tag: 'link', attrs: { rel: 'icon', href: '/favicon.ico', sizes: 'any' }, injectTo: 'head-prepend' as const },
        { tag: 'link', attrs: { rel: 'icon', type: 'image/png', sizes: '32x32', href: '/favicon-32x32.png' }, injectTo: 'head-prepend' as const },
        { tag: 'link', attrs: { rel: 'icon', type: 'image/png', sizes: '16x16', href: '/favicon-16x16.png' }, injectTo: 'head-prepend' as const },
        { tag: 'link', attrs: { rel: 'apple-touch-icon', sizes: '180x180', href: '/apple-touch-icon.png' }, injectTo: 'head-prepend' as const },
        { tag: 'script', attrs: { type: 'application/ld+json' }, children: JSON.stringify(structuredDataGraph()), injectTo: 'head-prepend' as const },
      ]

      return {
        html: html.replace(/<html lang="[^"]*">/, `<html lang="${locale}">`),
        tags,
      }
    },
    configureServer(server) {
      server.middlewares.use('/sitemap.xml', (_request, response) => {
        response.statusCode = 200
        response.setHeader('Content-Type', 'application/xml; charset=utf-8')
        response.end(renderSitemap())
      })
    },
    generateBundle() {
      this.emitFile({
        type: 'asset',
        fileName: 'sitemap.xml',
        source: renderSitemap(),
      })
    },
  }
}
