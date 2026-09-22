import assert from 'node:assert/strict'
import { existsSync, readFileSync, readdirSync, statSync } from 'node:fs'
import { join } from 'node:path'

const dist = join(process.cwd(), 'dist')
const read = (file) => readFileSync(join(dist, file), 'utf8')
const es = read('index.html')
const en = read('en/index.html')
const sitemap = read('sitemap.xml')
const robots = read('robots.txt')
const socialImage = join(process.cwd(), 'public', 'social', 'luciano-gonzalez-social.jpg')

const title = 'Luciano González — Backend Engineer | PHP & Laravel'
const metadata = {
  es: {
    canonical: 'https://lucianogonzalez.dev/',
    description: 'Desarrollo backend orientado a APIs, lógica de negocio, datos y mantenimiento de aplicaciones.',
    locale: 'es_AR',
  },
  en: {
    canonical: 'https://lucianogonzalez.dev/en',
    description: 'Backend development focused on APIs, business logic, data, and application maintenance.',
    locale: 'en_US',
  },
}

const expectedAlternates = [
  '<link rel="alternate" hreflang="es" href="https://lucianogonzalez.dev/">',
  '<link rel="alternate" hreflang="en" href="https://lucianogonzalez.dev/en">',
  '<link rel="alternate" hreflang="x-default" href="https://lucianogonzalez.dev/">',
]
const expectedFavicons = [
  '<link rel="icon" href="/favicon.ico" sizes="any">',
  '<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">',
  '<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">',
  '<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">',
]

const assertShell = (html, locale) => {
  const expected = metadata[locale]
  assert.match(html, new RegExp(`<html lang="${locale}">`))
  assert(html.includes(`<title>${title}</title>`), `${locale} title is missing`)
  assert(html.includes(`<meta name="description" content="${expected.description}">`), `${locale} description is missing`)
  assert(html.includes(`<link rel="canonical" href="${expected.canonical}">`), `${locale} canonical is missing`)
  assert(html.includes(`<meta property="og:locale" content="${expected.locale}">`), `${locale} OG locale is missing`)
  assert.equal((html.match(/rel="alternate" hreflang=/g) ?? []).length, 3, `${locale} alternate count`)

  for (const alternate of expectedAlternates) assert(html.includes(alternate), `${locale} alternate is missing: ${alternate}`)
  for (const favicon of expectedFavicons) assert(html.includes(favicon), `${locale} favicon is missing: ${favicon}`)

  const jsonLd = html.match(/<script type="application\/ld\+json">(.*?)<\/script>/)
  assert(jsonLd, `${locale} JSON-LD is missing`)
  assert.deepEqual(JSON.parse(jsonLd[1])['@graph'].map((node) => node['@type']), ['Person', 'WebSite'])
}

assertShell(es, 'es')
assertShell(en, 'en')

for (const file of ['favicon.ico', 'favicon-16x16.png', 'favicon-32x32.png', 'apple-touch-icon.png']) {
  const path = join(dist, file)
  assert(existsSync(path), `missing ${file}`)
  assert(statSync(path).size > 0, `empty ${file}`)
}

assert(sitemap.includes('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'), 'sitemap namespace is missing')
assert(sitemap.includes('xmlns:xhtml="http://www.w3.org/1999/xhtml"'), 'XHTML sitemap namespace is missing')
assert.equal((sitemap.match(/<url>/g) ?? []).length, 2, 'sitemap URL count')
for (const alternate of [
  'hreflang="es" href="https://lucianogonzalez.dev/"',
  'hreflang="en" href="https://lucianogonzalez.dev/en"',
  'hreflang="x-default" href="https://lucianogonzalez.dev/"',
]) {
  assert.equal((sitemap.match(new RegExp(alternate, 'g')) ?? []).length, 2, `sitemap alternate count: ${alternate}`)
}

assert.equal(robots, 'User-agent: *\nAllow: /\nSitemap: https://lucianogonzalez.dev/sitemap.xml\n')
assert(!existsSync(join(dist, 'es', 'index.html')), 'legacy /es document must not be emitted')
for (const file of ['site.webmanifest', 'android-chrome-192x192.png', 'android-chrome-512x512.png', 'service-worker.js']) {
  assert(!existsSync(join(dist, file)), `forbidden output exists: ${file}`)
}

const outputFiles = (directory) => readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
  const path = join(directory, entry.name)
  return entry.isDirectory() ? outputFiles(path) : [path]
})
const output = outputFiles(dist).filter((file) => statSync(file).isFile()).map((file) => readFileSync(file, 'utf8')).join('\n')
assert(!output.includes('VITE_'), 'build must not contain a VITE_ analytics value')
assert(!/[0]{8}-[0]{4}-[0]{4}-[0]{4}-[0]{12}/.test(output), 'build must not contain a nil UUID')

if (existsSync(socialImage)) {
  const image = 'https://lucianogonzalez.dev/social/luciano-gonzalez-social.jpg'
  for (const html of [es, en]) {
    assert(html.includes(`<meta property="og:image" content="${image}">`), 'OG image is missing')
    assert(html.includes('<meta property="og:image:width" content="1200">'), 'OG image width is missing')
    assert(html.includes('<meta property="og:image:height" content="630">'), 'OG image height is missing')
    assert(html.includes('<meta property="og:image:alt" content="Luciano González — Backend Engineer | PHP & Laravel">'), 'OG image alt is missing')
    assert(html.includes(`<meta name="twitter:image" content="${image}">`), 'Twitter image is missing')
    assert(html.includes('<meta name="twitter:image:alt" content="Luciano González — Backend Engineer | PHP & Laravel">'), 'Twitter image alt is missing')
  }
} else {
  assert(!es.includes('og:image'), 'ES shell must not reference a missing OG image')
  assert(!en.includes('og:image'), 'EN shell must not reference a missing OG image')
  assert(!es.includes('twitter:image'), 'ES shell must not reference a missing Twitter image')
  assert(!en.includes('twitter:image'), 'EN shell must not reference a missing Twitter image')
  console.log('SOCIAL IMAGE: PENDING')
  if (process.argv.includes('--require-social')) {
    assert.fail('SOCIAL IMAGE: PENDING — --require-social requires web/public/social/luciano-gonzalez-social.jpg')
  }
}
