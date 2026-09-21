# Phase 8 — SEO and analytics implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver localized pre-JavaScript SEO for `/` and `/en`, strict gateway URL/status behavior, and optional privacy-first Umami tracking without changing the Vite + React/Laravel architecture.

**Architecture:** Vite builds two HTML entries from one typed SEO model and emits a static sitemap. The development gateway owns canonical redirects, explicit frontend routing, 404 status, indexing headers, and the no-cache runtime-config contract. A small dependency-injected analytics module validates `/runtime-config.json`, inserts one Umami script, and leaves analytics off on every failure.

**Tech Stack:** React 18, Vite 8, TypeScript 5.9 strict, Vitest 4 in Node, Caddy 2.11.4, Docker Compose, PowerShell/curl, existing host `ffmpeg`/`ffprobe`.

**Spec:** `docs/superpowers/specs/2026-09-21-phase-8-seo-analytics-design.md` at approved commit `7d8a8989cd76c1ccbb0e24503da8ce6fbc530e8a`.

## Global Constraints

- Execute later on branch `codex/phase-8-seo-analytics` in `.worktrees/phase-8-seo-analytics`, created at execution time from the approved planning commit with `superpowers:using-git-worktrees`.
- Canonical documents are `/` (ES) and `/en` (EN). Redirect `/es` and `/es/` to `/`, and `/en/` to `/en`, with HTTP `308`.
- Exact `/en` internally serves `/en/index.html` with `200`; a static file server must never reverse-canonicalize it to `/en/`.
- Use the exact approved title/descriptions and one typed SEO source for HTML metadata and sitemap alternates.
- Do not fetch Laravel during build and do not add SSR, prerendering, a router, a backend SEO renderer, a manifest, a service worker, or a PWA.
- Do not add dependencies, Playwright, Cypress, a lint stack, another test framework, or a fake Umami ingestion server.
- Umami configuration is runtime-only. Never introduce `VITE_*` analytics variables or a checked-in `runtime-config.json`.
- Only `cv-download`, `github-click`, `linkedin-click`, and `email-click` are allowed; never add event properties.
- Phase 8 tests client-observable tracker behavior only. Live Umami ingestion belongs to Phase 12 and ongoing review to Phase 13.
- Do not access a VPS, SSH, `/srv`, Cloudflare, DNS, global Caddy, production secrets, or real Search Console.
- Use Conventional Commits without attribution lines. Keep the implementation within 6–8 hours.

## Review Focus

1. `/en` must stay `/en` while serving `en/index.html`; HTTP tests must reject a `Location: /en/` response.
2. Both `200` and `404` responses for `/runtime-config.json` must carry `Cache-Control: no-store`, preventing stale or negatively cached activation state.
3. Repeated bootstrap calls, React development behavior, navigation, and hash changes must not insert or initialize a second tracker.
4. `X-Robots-Tag` matchers must cover only approved non-indexable responses and must not reach `/storage/*`, `/cv/*`, or frontend assets.
5. Both sitemap entries must contain the same reciprocal `es`, `en`, and `x-default` links under the XHTML namespace generated from the HTML metadata source.

---

## File Map

| Responsibility | Existing files | New files |
| --- | --- | --- |
| Localized shells and SEO model | `web/index.html`, `web/vite.config.ts`, `web/package.json` | `web/en/index.html`, `web/seo.config.ts`, `web/vite/seo-plugin.ts`, `web/src/lib/seo.test.ts`, `web/scripts/verify-build.mjs` |
| Public discovery/visual assets | `web/src/media.test.ts` | `web/public/robots.txt`, `web/public/social/luciano-gonzalez-social.jpg`, `web/public/favicon.svg`, `web/public/favicon-32x32.png`, `web/public/apple-touch-icon.png` |
| Gateway, 404, runtime-config transport | `infra/caddy/Caddyfile`, `infra/validation/validate-repository.mjs`, `web/vite.config.ts` | `web/public/404.html`, `web/vite/runtime-config-plugin.ts` |
| Runtime analytics | `web/src/main.tsx`, `web/src/App.tsx` | `web/src/lib/analytics.ts`, `web/src/lib/analytics.test.ts`, `web/src/lib/analytics-events.ts`, `web/src/lib/analytics-events.test.ts` |
| Event hooks | `web/src/components/ExternalLink.tsx`, `web/src/components/SiteNav.tsx`, `web/src/components/ContactSection.tsx`, `web/src/components/ScrollScene.tsx`, `web/src/components/ProjectDossier.test.ts`, `web/src/components/ScrollScene.test.ts` | `web/src/components/ContactSection.test.ts`, `web/src/components/ExternalLink.test.ts` |
| Approved documentation reconciliation | `ROADMAP.md`, `docs/ARCHITECTURE.md`, `docs/DEPLOYMENT.md`, `docs/content/ASSET_INVENTORY.md` | none |

`web/public/runtime-config.json` is intentionally absent from source control. Vite copies `web/public/*` unchanged, making `web/public/` the correct source location for the approved favicon and social image used by both shells.

## Human Asset Input Gate

These inputs must exist before Task 1 can pass. They are not design tasks.

### Social image

- **SOURCE:** human-approved editorial portrait JPEG matching the spec.
- **OUTPUT:** `web/public/social/luciano-gonzalez-social.jpg`.
- **SIZE/FORMAT:** exactly 1200 × 630, JPEG.
- **PURPOSE:** the single Open Graph and Twitter/X image for ES and EN.

Copy it into the execution worktree at:

```text
C:\Users\lucho\Desktop\portfolio-luciano\.worktrees\phase-8-seo-analytics\web\public\social\luciano-gonzalez-social.jpg
```

The executor creates `web/public/social/` with `New-Item -ItemType Directory -Force web/public/social` before the human copy if the directory is absent.

### Favicon

**FAVICON SOURCE FILE:** the human's existing approved Terracotta sprout SVG, copied without redesign and renamed to `favicon.svg`.

**DESTINATION PATH:** `web/public/`.

**FINAL FILENAME:** `favicon.svg`.

```text
COPIÁ TU FAVICON EN: C:\Users\lucho\Desktop\portfolio-luciano\.worktrees\phase-8-seo-analytics\web\public\favicon.svg
```

Technical derivatives:

| SOURCE | OUTPUT | SIZE/FORMAT | PURPOSE |
| --- | --- | --- | --- |
| `web/public/favicon.svg` | `web/public/favicon.svg` | approved vector SVG | primary modern favicon |
| `web/public/favicon.svg` | `web/public/favicon-32x32.png` | 32 × 32 RGBA PNG | small raster fallback |
| `web/public/favicon.svg` | `web/public/apple-touch-icon.png` | 180 × 180 RGBA PNG | Apple touch icon |

No `.ico` is required: the SVG plus explicit 32 px PNG covers the approved fallback contract without another derivative.

---

### Task 1: Localized shells, SEO model, discovery files, and approved assets

**Objective:** Build `/` and `/en` HTML from one typed SEO source and ship the approved social/favicon assets, structured data, sitemap, and robots contract.

**Estimated effort:** 1.5–2 hours.

**Files:**
- Create: `web/en/index.html`
- Create: `web/seo.config.ts`
- Create: `web/vite/seo-plugin.ts`
- Create: `web/src/lib/seo.test.ts`
- Create: `web/scripts/verify-build.mjs`
- Create: `web/public/robots.txt`
- Add human input: `web/public/social/luciano-gonzalez-social.jpg`, `web/public/favicon.svg`
- Generate: `web/public/favicon-32x32.png`, `web/public/apple-touch-icon.png`
- Modify: `web/index.html`, `web/vite.config.ts`, `web/package.json`, `web/src/media.test.ts`

**Interfaces:**
- Consumes: approved metadata copy and human assets from the input gate.
- Produces: `type SeoLocale = 'es' | 'en'`; `SEO_BY_LOCALE`; `HREFLANG_LINKS`; `structuredDataGraph()`; `renderSitemap()`; Vite plugin `localizedSeoPlugin()`; build entries `dist/index.html` and `dist/en/index.html`; `/sitemap.xml`; `/robots.txt`; public favicon/social URLs.

- [ ] **Step 1: Verify and derive the human assets**

Run from the worktree root:

```powershell
ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0:s=x web/public/social/luciano-gonzalez-social.jpg
rg -n '<script|xlink:href="https?://|href="https?://' web/public/favicon.svg
ffmpeg -y -i web/public/favicon.svg -vf "scale=32:32:flags=lanczos,format=rgba" -frames:v 1 web/public/favicon-32x32.png
ffmpeg -y -i web/public/favicon.svg -vf "scale=180:180:flags=lanczos,format=rgba" -frames:v 1 web/public/apple-touch-icon.png
ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0:s=x web/public/favicon-32x32.png
ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0:s=x web/public/apple-touch-icon.png
```

Expected: social image reports `1200x630`; SVG scan has no matches; PNGs report `32x32` and `180x180`. Stop for corrected human input if the JPEG size or SVG safety scan fails; do not redesign or substitute either asset.

- [ ] **Step 2: Write the failing SEO contract tests**

Create `web/src/lib/seo.test.ts` with exact assertions:

```ts
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
    expect(xml.match(/hreflang="es"/g)).toHaveLength(2)
    expect(xml.match(/hreflang="en"/g)).toHaveLength(2)
    expect(xml.match(/hreflang="x-default"/g)).toHaveLength(2)
    expect(xml).not.toMatch(/<lastmod>|<changefreq>|<priority>|\/es</)
  })

  it('limits structured data to Person and WebSite', () => {
    expect(structuredDataGraph()['@graph'].map((node) => node['@type'])).toEqual(['Person', 'WebSite'])
  })
})
```

Extend `web/src/media.test.ts` to assert the four final public files exist. Run:

```powershell
docker compose exec -T web pnpm vitest run src/lib/seo.test.ts src/media.test.ts
```

Expected: FAIL because `web/seo.config.ts` does not exist.

- [ ] **Step 3: Implement the typed SEO source and Vite plugin**

In `web/seo.config.ts`, define these exact public constants and pure functions:

```ts
export type SeoLocale = 'es' | 'en'
export type SeoEntry = Readonly<{
  lang: SeoLocale
  canonical: string
  title: string
  description: string
  ogLocale: 'es_AR' | 'en_US'
  ogAlternate: 'es_AR' | 'en_US'
}>
export type HreflangLink = Readonly<{ hreflang: 'es' | 'en' | 'x-default'; href: string }>
export type StructuredDataGraph = Readonly<{
  '@context': 'https://schema.org'
  '@graph': readonly [Record<string, unknown>, Record<string, unknown>]
}>
export const SITE_ORIGIN = 'https://lucianogonzalez.dev'
export const SOCIAL_IMAGE_PATH = '/social/luciano-gonzalez-social.jpg'
export const SOCIAL_IMAGE_ALT = 'Luciano González — Backend Engineer | PHP & Laravel'
export const SEO_BY_LOCALE: Readonly<Record<SeoLocale, SeoEntry>>
export const HREFLANG_LINKS: readonly HreflangLink[]
export function structuredDataGraph(): StructuredDataGraph
export function renderSitemap(): string
```

Use stable JSON-LD IDs `https://lucianogonzalez.dev/#person` and `https://lucianogonzalez.dev/#website`. `Person` contains name, root URL, `jobTitle: 'Backend Engineer'`, and only `https://github.com/Gonzalez-Luciano` and `https://www.linkedin.com/in/luciano-gonzález-590350294` in `sameAs`. Omit `image` because no independent stable public portrait URL is required. `WebSite` contains root URL, name, `inLanguage: ['es', 'en']`, and an `author` reference to the person.

Create `web/vite/seo-plugin.ts` exporting `localizedSeoPlugin(): Plugin`. It must:

- select ES for `/index.html` and EN for `/en/index.html`;
- set the existing `<html lang>` correctly;
- inject title, description, `index, follow, max-image-preview:large`, canonical, and the three alternate links before application JavaScript;
- inject exact Open Graph fields `og:type`, `og:site_name`, `og:title`, `og:description`, `og:url`, absolute `og:image`, width `1200`, height `630`, image alt, locale, and alternate locale;
- inject exact Twitter/X fields `summary_large_image`, title, description, the same absolute image, and image alt;
- inject `/favicon.svg`, `/favicon-32x32.png`, `/apple-touch-icon.png`, and one JSON-LD graph before application JavaScript;
- serve generated `/sitemap.xml` in Vite development with `application/xml; charset=utf-8`;
- emit `sitemap.xml` during `vite build` from `renderSitemap()`.

Do not add meta keywords, `twitter:site`, `twitter:creator`, manifest, or service worker tags.

- [ ] **Step 4: Configure Vite multi-page output and source shells**

Update `web/vite.config.ts` to register `localizedSeoPlugin()` and define exact Rollup inputs:

```ts
input: {
  index: fileURLToPath(new URL('./index.html', import.meta.url)),
  en: fileURLToPath(new URL('./en/index.html', import.meta.url)),
}
```

Remove the placeholder `<title>Portfolio</title>` from `web/index.html`; preserve its theme bootstrap, noscript scene, root, and `/src/main.tsx`. Create `web/en/index.html` with the same body/bootstrap and `<html lang="en">`. Metadata remains plugin-generated rather than duplicated in either source shell.

Create `web/public/robots.txt` with exactly:

```text
User-agent: *
Allow: /
Sitemap: https://lucianogonzalez.dev/sitemap.xml
```

- [ ] **Step 5: Add repeatable build inspection**

Create `web/scripts/verify-build.mjs` using only `node:assert`, `node:fs`, and `node:path`. It must read `dist/index.html`, `dist/en/index.html`, `dist/sitemap.xml`, and `dist/robots.txt`; assert exact localized titles/descriptions/canonical/OG locales; assert three HTML alternates per shell; assert social and favicon references exist; assert JSON-LD contains only `Person` and `WebSite`; assert reciprocal sitemap alternates and both namespaces; assert no `/es` document, manifest, service worker, `VITE_` analytics value, or nil UUID exists.

Add this existing-tool script to `web/package.json`:

```json
"verify:build": "node scripts/verify-build.mjs"
```

- [ ] **Step 6: Run Task 1 checks and commit**

```powershell
docker compose exec -T web pnpm typecheck
docker compose exec -T web pnpm test:run
docker compose exec -T web pnpm build
docker compose exec -T web pnpm verify:build
```

Expected: typecheck and all Vitest tests pass; Vite emits both shells and sitemap; build inspection exits 0.

```powershell
git add web/index.html web/en/index.html web/seo.config.ts web/vite/seo-plugin.ts web/vite.config.ts web/package.json web/scripts/verify-build.mjs web/src/lib/seo.test.ts web/src/media.test.ts web/public/robots.txt web/public/social/luciano-gonzalez-social.jpg web/public/favicon.svg web/public/favicon-32x32.png web/public/apple-touch-icon.png
git commit -m "feat(web): add localized SEO shells and metadata"
```

---

### Task 2: Gateway routing, redirects, static 404, and indexability headers

**Objective:** Make the gateway own canonical routing and status codes without disturbing Laravel routing or public media.

**Estimated effort:** 1.25–1.5 hours.

**Files:**
- Create: `web/public/404.html`
- Create: `web/vite/runtime-config-plugin.ts`
- Modify: `web/vite.config.ts`
- Modify: `infra/caddy/Caddyfile`
- Modify: `infra/validation/validate-repository.mjs`

**Interfaces:**
- Consumes: `en/index.html`, SEO/static asset paths from Task 1, existing Laravel/Livewire route ownership.
- Produces: exact `200`/`308`/`404` behavior; internal `/en/index.html` rewrite; explicit frontend whitelist; static bilingual 404; scoped `X-Robots-Tag`; `Cache-Control: no-store` for present and absent runtime config.

- [ ] **Step 1: Add failing repository assertions**

Extend `infra/validation/validate-repository.mjs` to require:

```js
// Observable route contract, expressed against named matchers/handlers in Caddyfile.
assert.match(caddy, /\/es.*308/)
assert.match(caddy, /\/es\/.*308/)
assert.match(caddy, /\/en\/.*308/)
assert.match(caddy, /rewrite[^\n]*\/en\/index\.html/)
assert.match(caddy, /Cache-Control[^\n]*no-store/)
assert.doesNotMatch(caddy, /@(?:backendNoIndex|nonIndexable)[^\n]*\/storage\/\*/)
assert.doesNotMatch(caddy, /@(?:backendNoIndex|nonIndexable)[^\n]*\/cv\/\*/)
```

Also assert that `web/public/404.html` and `web/vite/runtime-config-plugin.ts` exist. Run:

```powershell
node infra/validation/validate-repository.mjs
```

Expected: FAIL on the first new Phase 8 assertion.

- [ ] **Step 2: Create the static bilingual 404**

Create `web/public/404.html` as a self-contained document with `<html lang="es">`, `<meta name="robots" content="noindex, nofollow">`, Spanish `Página no encontrada`, English content inside an element carrying `lang="en"`, and plain links to `/` and `/en`. It must contain no `/src/main.tsx`, API URL, tracker, animation, or canonical link.

- [ ] **Step 3: Enforce runtime-config behavior in Vite development**

Create `web/vite/runtime-config-plugin.ts` exporting `runtimeConfigContractPlugin(): Plugin`. Its `configureServer` middleware must inspect the URL pathname exactly once:

```ts
if (pathname !== '/runtime-config.json') return next()
res.setHeader('Cache-Control', 'no-store')
res.setHeader('X-Robots-Tag', 'noindex, nofollow')
if (!existsSync(resolve(server.config.publicDir, 'runtime-config.json'))) {
  res.statusCode = 404
  res.end()
  return
}
next()
```

Register it in `web/vite.config.ts` after the SEO plugin. Do not create `web/public/runtime-config.json`.

- [ ] **Step 4: Replace the broad frontend fallback with explicit Caddy handlers**

Modify `infra/caddy/Caddyfile` in this order:

1. `/__gateway/health`: keep `200`, add `X-Robots-Tag: noindex, nofollow`.
2. `@backendNoIndex`: exact families `/api`, `/api/*`, `/admin`, `/admin/*`, `/filament`, `/filament/*`, `/up`; add `X-Robots-Tag: noindex, nofollow`, then proxy to `api:80`.
3. `@backendPublic`: `/storage/*`, `/css/filament/*`, `/fonts/filament/*`, `/js/filament/*`, `/cv/*`; proxy to `api:80` without `X-Robots-Tag`.
4. Preserve the structural Livewire matcher and proxy without attaching the broad noindex header.
5. Exact redirects: `/es` → `/`, `/es/` → `/`, `/en/` → `/en`, all `308`.
6. Exact `/`: proxy to `web:5173` without rewrite.
7. Exact `/en`: rewrite internally to `/en/index.html`, proxy to `web:5173`, and emit no external redirect.
8. Exact `/runtime-config.json`: add `Cache-Control: no-store` and `X-Robots-Tag: noindex, nofollow`, then proxy so the Vite middleware owns present versus missing status.
9. Frontend public whitelist: `/robots.txt`, `/sitemap.xml`, `/favicon.svg`, `/favicon-32x32.png`, `/apple-touch-icon.png`, `/social/luciano-gonzalez-social.jpg`, `/assets/*`, `/media/*`; add Vite-only development paths `/@vite/*`, `/@react-refresh`, `/src/*`, `/node_modules/.vite/*`.
10. Final unknown frontend handler: internally rewrite to `/404.html`, proxy that file from Vite, copy its body with HTTP `404`, and add `X-Robots-Tag: noindex, nofollow`.

Do not use Caddy `file_server`, mount application files into the gateway, or write global-Caddy syntax.

- [ ] **Step 5: Validate gateway configuration and HTTP behavior**

```powershell
node infra/validation/validate-repository.mjs
docker compose exec -T gateway caddy validate --config /etc/caddy/Caddyfile
docker compose restart gateway
docker compose up -d --wait
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/en
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/en/
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/es
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/es/
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/runtime-config.json
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/not-a-public-route
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/api/not-a-route
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/admin
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/up
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/__gateway/health
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/cv/luciano-gonzalez-es.pdf
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/favicon.svg
```

Expected: `/` and `/en` are `200`; `/en` has no `Location`; redirects are exact `308`; runtime config is `404` plus `Cache-Control: no-store`; unknown frontend and backend paths are distinct `404`s; API/admin/technical responses carry noindex; CV has no `X-Robots-Tag`.

- [ ] **Step 6: Commit**

```powershell
git add web/public/404.html web/vite/runtime-config-plugin.ts web/vite.config.ts infra/caddy/Caddyfile infra/validation/validate-repository.mjs
git commit -m "feat(gateway): enforce public route and indexability contracts"
```

---

### Task 3: Optional Umami bootstrap and the four event hooks

**Objective:** Load Umami only from validated runtime config, exactly once, and instrument only the approved link types.

**Estimated effort:** 1.5–2 hours.

**Files:**
- Create: `web/src/lib/analytics.ts`, `web/src/lib/analytics.test.ts`
- Create: `web/src/lib/analytics-events.ts`, `web/src/lib/analytics-events.test.ts`
- Create: `web/src/components/ContactSection.test.ts`, `web/src/components/ExternalLink.test.ts`
- Modify: `web/src/main.tsx`, `web/src/App.tsx`
- Modify: `web/src/components/ExternalLink.tsx`, `web/src/components/SiteNav.tsx`, `web/src/components/ContactSection.tsx`, `web/src/components/ScrollScene.tsx`
- Modify: `web/src/components/ProjectDossier.test.ts`, `web/src/components/ScrollScene.test.ts`

**Interfaces:**
- Produces: `type RuntimeConfig = Readonly<{ umami: Readonly<{ trackerUrl: string; websiteId: string }> }>`; `type AnalyticsStatus = 'enabled' | 'off'`; `type AnalyticsDependencies = Readonly<{ loadConfig: () => Promise<RuntimeConfig | null>; mountTracker: (config: RuntimeConfig['umami']) => Promise<void> }>`; `parseRuntimeConfig(value: unknown): RuntimeConfig | null`; `loadRuntimeConfig(fetcher?: typeof fetch): Promise<RuntimeConfig | null>`; `trackerAttributes(config: RuntimeConfig['umami']): Readonly<Record<string, string>>`; `createAnalyticsBootstrap(dependencies: AnalyticsDependencies): () => Promise<AnalyticsStatus>`; singleton `bootstrapAnalytics(): Promise<AnalyticsStatus>`; `ANALYTICS_EVENTS`; `type AnalyticsEventName`.
- Consumes: Task 2's optional same-origin `/runtime-config.json` route.

- [ ] **Step 1: Write failing runtime-config and idempotence tests**

Create `web/src/lib/analytics.test.ts` covering:

```ts
expect(parseRuntimeConfig({
  umami: {
    trackerUrl: 'https://analytics.example.test/script.js',
    websiteId: '11111111-1111-4111-8111-111111111111',
    ignored: true,
  },
})).toEqual({
  umami: {
    trackerUrl: 'https://analytics.example.test/script.js',
    websiteId: '11111111-1111-4111-8111-111111111111',
  },
})

expect(parseRuntimeConfig({ umami: { trackerUrl: 'javascript:alert(1)', websiteId: '11111111-1111-4111-8111-111111111111' } })).toBeNull()
expect(parseRuntimeConfig({ umami: { trackerUrl: 'https://user:pass@analytics.example.test/script.js', websiteId: '11111111-1111-4111-8111-111111111111' } })).toBeNull()
expect(parseRuntimeConfig({ umami: { trackerUrl: 'https://analytics.example.test/script.js', websiteId: '00000000-0000-0000-0000-000000000000' } })).toBeNull()
```

Test `loadRuntimeConfig` calls its fetcher with `/runtime-config.json`, `{ cache: 'no-store', credentials: 'omit' }` and returns `null` for rejection, non-OK response, invalid JSON, or partial config. Test `createAnalyticsBootstrap` by injecting spies for `loadConfig` and `mountTracker`: two calls return the same in-flight promise and mount once; a mount rejection returns `off` and never retries on a later call.

Run:

```powershell
docker compose exec -T web pnpm vitest run src/lib/analytics.test.ts
```

Expected: FAIL because `@/lib/analytics` does not exist.

- [ ] **Step 2: Implement runtime validation and tracker lifecycle**

Implement `web/src/lib/analytics.ts` with a structural `unknown` validator, `URL` parsing, an HTTP/HTTPS protocol check, empty username/password checks, and a UUID regex plus explicit nil-UUID rejection. `trackerAttributes()` returns exactly:

```ts
{
  'data-website-id': config.websiteId,
  'data-domains': 'lucianogonzalez.dev',
  'data-do-not-track': 'true',
  'data-exclude-search': 'true',
  'data-exclude-hash': 'true',
}
```

The browser mount adapter must create one async/deferred `<script data-portfolio-analytics="umami">`, set those attributes, append it to `<head>`, resolve on `load`, remove it and reject on `error`, and refuse to append when the marker already exists. The exported singleton uses `createAnalyticsBootstrap` so `idle → loading → enabled|off` is terminal for the page load.

In `web/src/main.tsx`, render React normally and invoke `void bootstrapAnalytics()` without awaiting it. Do not call analytics from a React effect.

- [ ] **Step 3: Define and test the closed event-name set**

Create `web/src/lib/analytics-events.ts`:

```ts
export const ANALYTICS_EVENTS = {
  cv: 'cv-download',
  github: 'github-click',
  linkedin: 'linkedin-click',
  email: 'email-click',
} as const

export type AnalyticsEventName = (typeof ANALYTICS_EVENTS)[keyof typeof ANALYTICS_EVENTS]
```

Create `analytics-events.test.ts` asserting exact values, exact count four, and absence of any event-property helper or payload type. Run both analytics test files; expected result is PASS.

- [ ] **Step 4: Instrument only the approved links**

Add optional `analyticsEvent?: AnalyticsEventName` to `ExternalLink` and render only `data-umami-event={analyticsEvent}`. Apply events at these exact call sites:

- every CV anchor in `SiteNav` and `ContactSection`: `cv-download`;
- `ContactSection` professional GitHub: `github-click`;
- `ContactSection` professional LinkedIn: `linkedin-click`;
- `ContactSection` email and both real email anchors in `ScrollScene`: `email-click`.

Do not instrument project demo/repository links in `ProjectDossier`, even if a repository happens to use GitHub. Do not add `data-umami-event-*` property attributes.

Write `ExternalLink.test.ts` and `ContactSection.test.ts` using `renderToStaticMarkup`; extend `ScrollScene.test.ts` to assert its email anchors; extend `ProjectDossier.test.ts` to assert project links have no Umami event attribute.

- [ ] **Step 5: Remove client title drift and run tests**

Delete only the `useEffect` in `web/src/App.tsx` that replaces `document.title` after content fetch. Keep the defensive `document.documentElement.lang = locale` effect.

```powershell
docker compose exec -T web pnpm typecheck
docker compose exec -T web pnpm test:run
docker compose exec -T web pnpm build
docker compose exec -T web pnpm verify:build
rg -n "document\.title" web/src
```

Expected: all automated checks pass; the final `rg` has no matches; the build contains no Website ID or tracker URL; only the four approved event strings occur in application source. The defensive `document.documentElement.lang` effect remains.

- [ ] **Step 6: Commit**

```powershell
git add web/src/main.tsx web/src/App.tsx web/src/lib/analytics.ts web/src/lib/analytics.test.ts web/src/lib/analytics-events.ts web/src/lib/analytics-events.test.ts web/src/components/ExternalLink.tsx web/src/components/ExternalLink.test.ts web/src/components/SiteNav.tsx web/src/components/ContactSection.tsx web/src/components/ContactSection.test.ts web/src/components/ScrollScene.tsx web/src/components/ScrollScene.test.ts web/src/components/ProjectDossier.test.ts
git commit -m "feat(web): add optional Umami runtime analytics"
```

---

### Task 4: Integrated HTTP and visible-browser verification gate

**Objective:** Verify observable behavior with existing tools before documentation claims completion.

**Estimated effort:** 1–1.5 hours.

**Files:** none committed. Two temporary public probe files are created with `apply_patch` and removed before this task ends.

**Interfaces:**
- Consumes: Tasks 1–3 and the existing local Compose stack.
- Produces: a pass/fail gate for Task 5; it does not claim real Umami ingestion.

- [ ] **Step 1: Run the automated and HTTP matrix**

```powershell
node infra/validation/validate-repository.mjs
docker compose exec -T web pnpm typecheck
docker compose exec -T web pnpm test:run
docker compose exec -T web pnpm build
docker compose exec -T web pnpm verify:build
docker compose exec -T gateway caddy validate --config /etc/caddy/Caddyfile
docker compose restart gateway
docker compose up -d --wait
```

Repeat Task 2's curl matrix and additionally request `/sitemap.xml`, `/robots.txt`, `/storage/not-found`, and both CV URLs. Expected: correct content types; reciprocal sitemap; scoped noindex; public storage/CV responses never inherit `X-Robots-Tag`; runtime-config `404` remains `no-store`.

- [ ] **Step 2: Verify canonical navigation and fragments in a visible browser**

Using the existing browser/manual mechanism, verify:

- direct load and refresh of `/` and `/en`;
- address bar stays `/en`, never `/en/`;
- `/en/` redirects to `/en`;
- `/es#projects` finishes at `/#projects` and lands on Projects;
- language switch preserves `#projects` between `/#projects` and `/en#projects`;
- anchors under `/en` land correctly;
- unknown path shows the bilingual no-script 404 and Network shows no API or analytics request.

If fragment preservation fails, return to Task 2 and implement only the smallest gateway/browser-compatible correction allowed by the spec.

- [ ] **Step 3: Verify analytics OFF in the browser**

With no `web/public/runtime-config.json`, reload `/` and `/en`. Expected: one `404` request for runtime config carrying `Cache-Control: no-store`; no Umami script; content, locale switch, CV, email, GitHub, and LinkedIn still work.

Temporarily add `web/public/runtime-config.json` containing the nil UUID and reload. Expected: config response `200` with `Cache-Control: no-store`, validator leaves analytics off, and no tracker script is inserted. Remove the file before continuing.

- [ ] **Step 4: Verify analytics ON client behavior without simulating ingestion**

Temporarily add `web/public/assets/analytics-loader-probe.js` (the `/assets/*` family is already in the approved development whitelist):

```js
window.__portfolioAnalyticsLoaderProbe = true
```

Temporarily add `web/public/runtime-config.json`:

```json
{
  "umami": {
    "trackerUrl": "http://127.0.0.1:8000/assets/analytics-loader-probe.js",
    "websiteId": "11111111-1111-4111-8111-111111111111"
  }
}
```

Reload `/` and inspect the single script marked `data-portfolio-analytics="umami"`. Confirm its source, Website ID, domain restriction, Do Not Track, exclude-search, and exclude-hash attributes. In the browser console run `await import('/src/lib/analytics.ts').then(({ bootstrapAnalytics }) => bootstrapAnalytics())`, then change the hash to `#projects`; the script count stays one. Confirm the four declarative event names on the intended links and no property attributes, then activate each link type and confirm its normal download/navigation/mail action is not delayed or cancelled. Locale switching loads one tracker in the new document.

This static no-op file tests script loading only; it is not an Umami server and makes no ingestion claim. Remove both temporary files with `apply_patch`, reload, and confirm analytics returns to `OFF` with a non-cacheable `404`.

Phase 8 stops at these client-observable checks. Phase 12 verifies live ingestion of `/` and `/en`, absence of query/hash variants and server-observed duplicates, and receipt of the four property-free events after the independent Umami instance exists.

- [ ] **Step 5: Confirm the task leaves no artifacts**

```powershell
git status --short
rg -n "analytics-loader-probe|11111111-1111-4111-8111-111111111111" web
```

Expected: no temporary files or probe values remain. Do not commit this verification-only task. Any fix belongs in the task that owns the failed behavior and must rerun that task's tests.

---

### Task 5: Approved documentation reconciliation and final verification

**Objective:** Align only active documentation with the implemented Phase 8 contract, then run the complete repository-local gate.

**Estimated effort:** 0.75–1 hour.

**Files:**
- Modify: `ROADMAP.md`
- Modify: `docs/ARCHITECTURE.md`
- Modify: `docs/DEPLOYMENT.md`
- Modify: `docs/content/ASSET_INVENTORY.md`

**Interfaces:**
- Consumes: verified implementation and human assets from Tasks 1–4.
- Produces: current architecture/release documentation with Phase 11/12/13 still explicitly future work.

- [ ] **Step 1: Reconcile `ROADMAP.md` only in active Phase 8/11/12/13 text**

Record completed Phase 8 behavior only after Task 4 passes. In active Phase 11 wording, replace `Laravel/Next.js` and `gateway, Next.js, Laravel y MySQL` with the actual Vite + React frontend, Laravel, internal gateway, and MySQL. Add the already-approved future responsibility split: Phase 11 materializes runtime config and releases immutable images; Phase 12 operations deploys/activates portfolio and independent Umami; Phase 13 reviews real analytics. Preserve clearly historical Next.js sections and do not mark future phases complete.

- [ ] **Step 2: Reconcile `docs/ARCHITECTURE.md`**

Replace the active obsolete Next.js paragraph under current Vite rendering with the two-shell/SPA contract. Document `/`, `/en`, redirects, explicit frontend whitelist, real static 404, one typed SEO source, generated sitemap, optional runtime config, analytics-off degradation, and Umami as an external independent service. Correct the active runtime inventory from Next `3000`/health to Vite `5173`/`GET /`. Preserve the explicitly historical Phase 5 section.

- [ ] **Step 3: Reconcile `docs/DEPLOYMENT.md`**

Correct active acceptance wording that still lists Next `16.2.12`, port `3000`, or `/health` as current. Document the future Phase 11 `/runtime-config.json` materialization with `Cache-Control: no-store` for `200` and `404`, same-image/digest reconfiguration, and the Phase 12 operations handoff for independent Umami. That handoff records an independently pinned official Umami image, PostgreSQL 12.14 or newer, separate persistence/backups, `DATABASE_URL`, `APP_SECRET`, administrator credential rotation, logging/retention ownership, website creation, Website ID delivery, and live ingestion checks—without implementing any of them. Add the post-deployment Search Console checklist limited to domain verification, sitemap submission, URL inspection, and indexing observation; record that tokens, DNS changes, and live submission remain outside Phase 8. Do not add production Dockerfiles, compose snippets, real values, SSH commands, `/srv` changes, Cloudflare configuration, or global Caddy configuration.

- [ ] **Step 4: Correct `docs/content/ASSET_INVENTORY.md`**

Replace the obsolete `AST-PROJECT-MEDIA` statement with the Phase 7 fact: Trucks and Drinks has 4 images and ReservaHub has 5. Add rows for the integrated 1200 × 630 social JPEG and favicon SVG/PNG derivatives, identifying them as human-approved Phase 8 assets and recording their exact public paths. Do not rewrite older asset history.

- [ ] **Step 5: Run documentation and scope scans**

```powershell
rg -n "Laravel/Next\.js|gateway, Next\.js|Next `3000`|Next `/health`" ROADMAP.md docs/ARCHITECTURE.md docs/DEPLOYMENT.md
rg -n "Umami|runtime-config\.json|Cache-Control: no-store|Phase 11|Phase 12|Phase 13" ROADMAP.md docs/ARCHITECTURE.md docs/DEPLOYMENT.md
rg -n "4 images|5 images|1200|favicon" docs/content/ASSET_INVENTORY.md
```

Expected: the first scan returns only explicitly historical wording or no matches; the second shows the approved lifecycle without completed Phase 11/12/13 claims; the inventory shows all nine project images plus the integrated SEO assets.

- [ ] **Step 6: Run the final repository-local gate**

```powershell
node infra/validation/validate-repository.mjs
docker compose exec -T web pnpm typecheck
docker compose exec -T web pnpm test:run
docker compose exec -T web pnpm build
docker compose exec -T web pnpm verify:build
docker compose exec -T gateway caddy validate --config /etc/caddy/Caddyfile
git diff --check
git status --short
```

Expected: validator passes; typecheck passes; all Vitest tests pass; Vite build and semantic inspection pass; Caddy validates; no whitespace errors; only the four documentation files are uncommitted at this point. Report that no lint script or E2E framework exists; do not install either.

- [ ] **Step 7: Commit documentation and stop**

```powershell
git add ROADMAP.md docs/ARCHITECTURE.md docs/DEPLOYMENT.md docs/content/ASSET_INVENTORY.md
git commit -m "docs: reconcile phase 8 SEO and analytics contracts"
git status --short --branch
```

Expected: clean feature worktree. Report automated checks, HTTP/browser observations, the absence of real Umami ingestion testing, and that production/VPS deployment was not executed. Stop before any Phase 11, Phase 12, or Phase 13 work.
