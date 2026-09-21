# Phase 8 — SEO and analytics implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver localized pre-JavaScript SEO for `/` and `/en`, strict gateway URL/status behavior, and optional privacy-first Umami tracking without changing the Vite + React/Laravel architecture.

**Architecture:** Vite builds two HTML entries from one typed SEO model and emits a static sitemap. The development gateway owns canonical redirects, explicit frontend routing, 404 status, indexing headers, and the no-cache runtime-config contract. A small dependency-injected analytics module validates `/runtime-config.json`, inserts one Umami script, and leaves analytics off on every failure.

**Tech Stack:** React 18, Vite 8, TypeScript 5.9 strict, Vitest 4 in Node, Caddy 2.11.4, Docker Compose, PowerShell/curl, and the existing host `ffprobe` only for inspecting the pending social JPEG when it arrives.

**Spec:** `docs/superpowers/specs/2026-09-21-phase-8-seo-analytics-design.md` at amended commit `729db509accc7e22f232bce2f3923275e792b937`.

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
6. GET and HEAD must agree on status and relevant headers for both shells, all redirects, runtime config, unknown frontend paths, sitemap, and robots.
7. The approved favicon files are copied unchanged; the pending social JPEG cannot block independent work, but strict final verification cannot pass without it.

---

## File Map

| Responsibility | Existing files | New files |
| --- | --- | --- |
| Localized shells and SEO model | `web/index.html`, `web/vite.config.ts`, `web/package.json` | `web/en/index.html`, `web/seo.config.ts`, `web/vite/seo-plugin.ts`, `web/src/lib/seo.test.ts`, `web/scripts/verify-build.mjs` |
| Public discovery/visual assets | `web/src/media.test.ts` | `web/public/robots.txt`, `web/public/social/luciano-gonzalez-social.jpg`, `web/public/favicon.ico`, `web/public/favicon-16x16.png`, `web/public/favicon-32x32.png`, `web/public/apple-touch-icon.png` |
| Gateway, 404, runtime-config transport | `infra/caddy/Caddyfile`, `infra/validation/validate-repository.mjs`, `web/vite.config.ts` | `web/public/404.html`, `web/vite/runtime-config-plugin.ts` |
| Runtime analytics | `web/src/main.tsx`, `web/src/App.tsx` | `web/src/lib/analytics.ts`, `web/src/lib/analytics.test.ts`, `web/src/lib/analytics-events.ts`, `web/src/lib/analytics-events.test.ts` |
| Event hooks | `web/src/components/ExternalLink.tsx`, `web/src/components/SiteNav.tsx`, `web/src/components/ContactSection.tsx`, `web/src/components/ScrollScene.tsx`, `web/src/components/ProjectDossier.test.ts`, `web/src/components/ScrollScene.test.ts` | `web/src/components/ContactSection.test.ts`, `web/src/components/ExternalLink.test.ts` |
| Approved documentation reconciliation | `ROADMAP.md`, `docs/ARCHITECTURE.md`, `docs/DEPLOYMENT.md`, `docs/content/ASSET_INVENTORY.md` | none |

`web/public/runtime-config.json` is intentionally absent from source control. Vite copies `web/public/*` unchanged, making `web/public/` the correct source location for the approved favicon and social image used by both shells.

## Human Asset Availability and Gates

Design approval and final-file availability are separate. Missing asset files never block unrelated implementation. A missing file blocks only its integration and physical-file checks, while any missing required final asset blocks final Phase 8 closure.

### Social image

- **DESIGN:** APPROVED — A: Retrato editorial.
- **FINAL FILE:** PENDING; no final JPEG exists at the target path at planning time.
- **SOURCE:** the human-approved final editorial portrait JPEG when supplied.
- **OUTPUT:** `web/public/social/luciano-gonzalez-social.jpg`.
- **SIZE/FORMAT:** exactly 1200 × 630, JPEG.
- **PURPOSE:** the single Open Graph and Twitter/X image for ES and EN.

Copy it into the execution worktree at:

```text
C:\Users\lucho\Desktop\portfolio-luciano\.worktrees\phase-8-seo-analytics\web\public\social\luciano-gonzalez-social.jpg
```

The executor creates `web/public/social/` with `New-Item -ItemType Directory -Force web/public/social` before the human copy if the directory is absent. Until the JPEG arrives, independent shell, SEO-source, sitemap, robots, gateway, runtime-config, analytics, event, test, and documentation work proceeds. The SEO plugin must omit `og:image`, its dimensions/alt, and `twitter:image`/alt when the file is physically absent so no build points at a nonexistent file; the final closure gate requires the file and all those tags.

### Favicon

**DESIGN:** APPROVED — Terracotta sprout.

**FINAL FILES:** AVAILABLE. Copy the existing Favicon.io outputs unchanged; do not generate, convert, derive, or vectorize them.

**SOURCE DIRECTORY:** `C:\Users\lucho\Downloads\favicon_io`.

| SOURCE | REPOSITORY DESTINATION | EXECUTION-WORKTREE DESTINATION | SIZE/FORMAT | PURPOSE |
| --- | --- | --- | --- | --- |
| `C:\Users\lucho\Downloads\favicon_io\favicon.ico` | `web/public/favicon.ico` | `C:\Users\lucho\Desktop\portfolio-luciano\.worktrees\phase-8-seo-analytics\web\public\favicon.ico` | final ICO package file | favicon fallback |
| `C:\Users\lucho\Downloads\favicon_io\favicon-16x16.png` | `web/public/favicon-16x16.png` | `C:\Users\lucho\Desktop\portfolio-luciano\.worktrees\phase-8-seo-analytics\web\public\favicon-16x16.png` | 16 × 16 PNG | small favicon |
| `C:\Users\lucho\Downloads\favicon_io\favicon-32x32.png` | `web/public/favicon-32x32.png` | `C:\Users\lucho\Desktop\portfolio-luciano\.worktrees\phase-8-seo-analytics\web\public\favicon-32x32.png` | 32 × 32 PNG | standard favicon |
| `C:\Users\lucho\Downloads\favicon_io\apple-touch-icon.png` | `web/public/apple-touch-icon.png` | `C:\Users\lucho\Desktop\portfolio-luciano\.worktrees\phase-8-seo-analytics\web\public\apple-touch-icon.png` | 180 × 180 PNG | Apple touch icon |

Intentionally omit `android-chrome-192x192.png`, `android-chrome-512x512.png`, and `site.webmanifest`. Do not add `<link rel="manifest">`.

---

### Task 1: Localized shells, SEO model, discovery files, and approved assets

**Objective:** Build `/` and `/en` HTML from one typed SEO source and ship structured data, discovery files, the available favicon package, and social metadata only when its final JPEG exists.

**Estimated effort:** 1.5–2 hours.

**Files:**
- Create: `web/en/index.html`
- Create: `web/seo.config.ts`
- Create: `web/vite/seo-plugin.ts`
- Create: `web/src/lib/seo.test.ts`
- Create: `web/scripts/verify-build.mjs`
- Create: `web/public/robots.txt`
- Copy final human inputs unchanged: `web/public/favicon.ico`, `web/public/favicon-16x16.png`, `web/public/favicon-32x32.png`, `web/public/apple-touch-icon.png`
- Add when supplied: `web/public/social/luciano-gonzalez-social.jpg`
- Modify: `web/index.html`, `web/vite.config.ts`, `web/package.json`, `web/src/media.test.ts`

**Interfaces:**
- Consumes: approved metadata copy, the available final favicon package, and the final social JPEG only when supplied.
- Produces: `type SeoLocale = 'es' | 'en'`; `SEO_BY_LOCALE`; `HREFLANG_LINKS`; `structuredDataGraph()`; `renderSitemap()`; Vite plugin `localizedSeoPlugin()`; build entries `dist/index.html` and `dist/en/index.html`; `/sitemap.xml`; `/robots.txt`; public favicon/social URLs.

- [ ] **Step 1: Copy and verify the available final favicon files**

Run from the worktree root:

```powershell
Copy-Item -LiteralPath 'C:\Users\lucho\Downloads\favicon_io\favicon.ico' -Destination 'web/public/favicon.ico'
Copy-Item -LiteralPath 'C:\Users\lucho\Downloads\favicon_io\favicon-16x16.png' -Destination 'web/public/favicon-16x16.png'
Copy-Item -LiteralPath 'C:\Users\lucho\Downloads\favicon_io\favicon-32x32.png' -Destination 'web/public/favicon-32x32.png'
Copy-Item -LiteralPath 'C:\Users\lucho\Downloads\favicon_io\apple-touch-icon.png' -Destination 'web/public/apple-touch-icon.png'
Get-Item web/public/favicon.ico, web/public/favicon-16x16.png, web/public/favicon-32x32.png, web/public/apple-touch-icon.png | Select-Object FullName, Length
```

Expected: all four copied files exist and are non-empty; the known package values are ICO header bytes `0,0,1,0`, PNG dimensions `16x16`, `32x32`, and `180x180`. Do not copy the Android Chrome files or `site.webmanifest`; do not generate derivatives.

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
    expect(xml.match(/<url>/g)).toHaveLength(2)
    expect(xml.match(/<loc>https:\/\/lucianogonzalez\.dev\/<\/loc>/g)).toHaveLength(1)
    expect(xml.match(/<loc>https:\/\/lucianogonzalez\.dev\/en<\/loc>/g)).toHaveLength(1)
    expect(xml.match(/hreflang="es" href="https:\/\/lucianogonzalez\.dev\/"/g)).toHaveLength(2)
    expect(xml.match(/hreflang="en" href="https:\/\/lucianogonzalez\.dev\/en"/g)).toHaveLength(2)
    expect(xml.match(/hreflang="x-default" href="https:\/\/lucianogonzalez\.dev\/"/g)).toHaveLength(2)
    expect(xml).not.toMatch(/<lastmod>|<changefreq>|<priority>|\/es</)
  })

  it('limits structured data to Person and WebSite', () => {
    expect(structuredDataGraph()['@graph'].map((node) => node['@type'])).toEqual(['Person', 'WebSite'])
  })
})
```

Extend `web/src/media.test.ts` using only Node `fs`: assert the ICO is non-empty and its first four bytes match an ICO header, and read each PNG's IHDR width/height directly to assert `16 × 16`, `32 × 32`, and `180 × 180`. The test covers exactly the four integrated favicon files and asserts that `site.webmanifest` and both Android Chrome files are absent. Run:

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
- always inject exact Open Graph fields `og:type`, `og:site_name`, `og:title`, `og:description`, `og:url`, locale, and alternate locale; add absolute `og:image`, width `1200`, height `630`, and image alt only under the physical-file gate below;
- always inject exact Twitter/X fields `summary_large_image`, title, and description; add the same absolute image and image alt only under the physical-file gate below;
- inject these exact favicon links and one JSON-LD graph before application JavaScript:

```html
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
```

- serve generated `/sitemap.xml` in Vite development with `application/xml; charset=utf-8`;
- emit `sitemap.xml` during `vite build` from `renderSitemap()`.

Read the physical social JPEG path during the Vite configuration/build. Inject the `og:image` group and `twitter:image`/alt only when `web/public/social/luciano-gonzalez-social.jpg` exists. This conditional is an implementation-time safety gate only: it prevents broken metadata during independent work, while the final Phase 8 gate requires the JPEG and the complete image metadata. Do not add meta keywords, `twitter:site`, `twitter:creator`, manifest, or service worker tags.

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

Create `web/scripts/verify-build.mjs` using only `node:assert`, `node:fs`, and `node:path`. It must read `dist/index.html`, `dist/en/index.html`, `dist/sitemap.xml`, and `dist/robots.txt`; assert exact localized titles/descriptions/canonical/OG locales; assert three HTML alternates per shell; assert all four favicon files and exact link metadata; assert JSON-LD contains only `Person` and `WebSite`; assert reciprocal sitemap alternates and both namespaces; assert no `/es` document, manifest, Android Chrome asset, service worker, `VITE_` analytics value, or nil UUID exists. If the social JPEG is absent, assert that neither shell contains `og:image` or `twitter:image` and emit a clear `SOCIAL IMAGE: PENDING` note without failing independent checks. If it exists, assert its references and full image metadata in both shells. A `--require-social` argument must make absence fail, for use only at the final Phase 8 closure gate.

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
git add web/index.html web/en/index.html web/seo.config.ts web/vite/seo-plugin.ts web/vite.config.ts web/package.json web/scripts/verify-build.mjs web/src/lib/seo.test.ts web/src/media.test.ts web/public/robots.txt web/public/favicon.ico web/public/favicon-16x16.png web/public/favicon-32x32.png web/public/apple-touch-icon.png
if (Test-Path -LiteralPath 'web/public/social/luciano-gonzalez-social.jpg') { git add web/public/social/luciano-gonzalez-social.jpg }
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
assert.match(caddy, /@livewireTechnical/)
assert.match(caddy, /@livewireAssets/)
assert.match(caddy, /handle_response/)
assert.match(caddy, /copy_response 404/)
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
4. Replace the single broad Livewire matcher with two structural matchers that retain the generated `livewire-{hash}` prefix:
   - `@livewireTechnical` uses `^/livewire-[^/]+/(?:update|upload-file|preview-file(?:/.*)?)$` for the current action/technical routes `/update`, `/upload-file`, and `/preview-file/{filename}`; add `X-Robots-Tag: noindex, nofollow` and proxy to `api:80`.
   - `@livewireAssets` uses `^/livewire-[^/]+/(?:css/[^/]+(?:\.global)?\.css|js/[^/]+\.js|livewire\.js|livewire\.csp\.min\.js\.map|livewire\.min\.js\.map)$` for the current CSS, JS, and source-map asset routes; proxy to `api:80` without `X-Robots-Tag`.
   These two categories must be checked against `infra/caddy/laravel-routes.json`; do not copy the current generated hash into the Caddyfile.
5. Exact redirects: `/es` → `/`, `/es/` → `/`, `/en/` → `/en`, all `308`.
6. Exact `/`: proxy to `web:5173` without rewrite.
7. Exact `/en`: rewrite internally to `/en/index.html`, proxy to `web:5173`, and emit no external redirect.
8. Exact `/runtime-config.json`: add `Cache-Control: no-store` and `X-Robots-Tag: noindex, nofollow`, then proxy so the Vite middleware owns present versus missing status.
9. Frontend public whitelist: `/robots.txt`, `/sitemap.xml`, `/favicon.ico`, `/favicon-16x16.png`, `/favicon-32x32.png`, `/apple-touch-icon.png`, `/social/luciano-gonzalez-social.jpg`, `/assets/*`, `/media/*`; add Vite-only development paths `/@vite/*`, `/@react-refresh`, `/src/*`, `/node_modules/.vite/*`. None of these paths receives the noindex header.
10. The final unnamed `handle` is the unknown-frontend matcher because every approved backend, redirect, shell, runtime-config, and public-asset family has already terminated in an earlier `handle`. Implement the response explicitly with this Caddy 2.11.4-valid mechanism (validated during planning with `caddy adapt --validate`):

```caddyfile
handle {
	header X-Robots-Tag "noindex, nofollow"
	rewrite * /404.html
	reverse_proxy web:5173 {
		handle_response {
			copy_response 404
		}
	}
}
```

The internal rewrite obtains the self-contained file from Vite; `copy_response 404` copies the upstream body and headers, preserving `Content-Type: text/html; charset=utf-8`, while overriding the status to a real `404`. Do not use `handle_errors`: the upstream serves `/404.html` with `200`, which does not enter error handling.

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
curl.exe -sS -D - -o NUL http://127.0.0.1:8000/favicon.ico
curl.exe -sS -I http://127.0.0.1:8000/
curl.exe -sS -I http://127.0.0.1:8000/en
curl.exe -sS -I http://127.0.0.1:8000/es
curl.exe -sS -I http://127.0.0.1:8000/es/
curl.exe -sS -I http://127.0.0.1:8000/en/
curl.exe -sS -I http://127.0.0.1:8000/runtime-config.json
curl.exe -sS -I http://127.0.0.1:8000/not-a-public-route
curl.exe -sS -I http://127.0.0.1:8000/sitemap.xml
curl.exe -sS -I http://127.0.0.1:8000/robots.txt
```

Expected for both GET and HEAD where listed: `/` and `/en` are `200`; `/en` has no `Location` header and specifically must fail the check if it returns `Location: /en/`; `/es` and `/es/` are `308` with `Location: /`; `/en/` is `308` with `Location: /en`; absent runtime config is `404` with `Cache-Control: no-store` and `X-Robots-Tag`; unknown frontend is `404` with `Content-Type: text/html; charset=utf-8` and `X-Robots-Tag`; sitemap is `200` XML; robots is `200` plain text; API/admin/Livewire technical responses carry noindex; Livewire assets, CV, storage, favicon, social image when present, sitemap, robots, and frontend assets do not inherit `X-Robots-Tag`.

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

**Files:** `web/public/social/luciano-gonzalez-social.jpg` is committed here only if it was still pending during Task 1 and has now been supplied. Two temporary public probe files are created with `apply_patch` and removed before this task ends.

**Interfaces:**
- Consumes: Tasks 1–3 and the existing local Compose stack.
- Produces: completed client/HTTP evidence and the final asset gate for Task 5; it does not claim real Umami ingestion.

- [ ] **Step 1: Resolve only the social-file integration gate**

Check the exact target:

```powershell
Test-Path -LiteralPath 'web/public/social/luciano-gonzalez-social.jpg'
```

If the result is `False`, record `SOCIAL IMAGE FINAL FILE: PENDING`, continue all independent HTTP/browser checks in this task and the independent documentation corrections in Task 5, but do not run the `--require-social` gate, add image metadata manually, publish a placeholder, or declare Phase 8 closed. If the human has supplied the approved JPEG at that exact target, run:

```powershell
ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0:s=x web/public/social/luciano-gonzalez-social.jpg
docker compose exec -T web pnpm build
docker compose exec -T web pnpm verify:build -- --require-social
$socialChange = git status --porcelain -- web/public/social/luciano-gonzalez-social.jpg
if ($socialChange) {
  git add web/public/social/luciano-gonzalez-social.jpg
  git commit -m "feat(web): add approved social preview"
}
```

Expected: `1200x630`, both shells contain the complete shared Open Graph/Twitter image metadata, the strict build verifier passes, and no creative change is made. The conditional commit runs only when Task 1 did not already commit that exact file.

- [ ] **Step 2: Run the automated and GET/HEAD HTTP matrix**

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

Repeat Task 2's GET and HEAD curl matrices and additionally request GET and HEAD for `/storage/not-found` and both CV URLs. The required HEAD set is `/`, `/en`, `/es`, `/es/`, `/en/`, `/runtime-config.json`, `/not-a-public-route`, `/sitemap.xml`, and `/robots.txt`. Expected: HEAD exposes the same status and relevant headers as GET; redirect `Location` values are exact; `/en` is `200` without `Location: /en/`; sitemap and robots have their correct content types; unknown frontend has HTML content type plus noindex; public storage/CV responses never inherit `X-Robots-Tag`; absent runtime config remains `404` with `Cache-Control: no-store` and noindex.

- [ ] **Step 3: Verify canonical navigation and fragments in a visible browser**

Using the existing browser/manual mechanism, verify:

- direct load and refresh of `/` and `/en`;
- address bar stays `/en`, never `/en/`;
- `/en/` redirects to `/en`;
- `/es#projects` finishes at `/#projects` and lands on Projects;
- language switch preserves `#projects` between `/#projects` and `/en#projects`;
- anchors under `/en` land correctly;
- unknown path shows the bilingual no-script 404 and Network shows no API or analytics request.

If fragment preservation fails, return to Task 2 and implement only the smallest gateway/browser-compatible correction allowed by the spec.

- [ ] **Step 4: Verify analytics OFF in the browser**

With no `web/public/runtime-config.json`, reload `/` and `/en`. Expected: one `404` request for runtime config carrying `Cache-Control: no-store`; no Umami script; content, locale switch, CV, email, GitHub, and LinkedIn still work.

Temporarily add `web/public/runtime-config.json` containing the nil UUID and reload. Issue both GET and HEAD to the route. Expected: both return `200`, `Content-Type: application/json`, `Cache-Control: no-store`, and `X-Robots-Tag: noindex, nofollow`; the validator leaves analytics off and no tracker script is inserted. Remove the file before continuing and confirm GET and HEAD return non-cacheable `404` again.

- [ ] **Step 5: Verify analytics ON client behavior without simulating ingestion**

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

Issue GET and HEAD to `/runtime-config.json`; both must return `200`, `Content-Type: application/json`, `Cache-Control: no-store`, and `X-Robots-Tag: noindex, nofollow`. Reload `/` and inspect the single script marked `data-portfolio-analytics="umami"`. Confirm its source, Website ID, domain restriction, Do Not Track, exclude-search, and exclude-hash attributes. In the browser console run `await import('/src/lib/analytics.ts').then(({ bootstrapAnalytics }) => bootstrapAnalytics())`, then change the hash to `#projects`; the script count stays one. Confirm exactly `cv-download`, `github-click`, `linkedin-click`, and `email-click` on the intended portfolio links, no property attributes, and no `github-click` on project repository/demo links; then activate each approved link type and confirm its normal download/navigation/mail action is not delayed or cancelled. Locale switching loads one tracker in the new document.

This static no-op file tests script loading only; it is not an Umami server and makes no ingestion claim. Remove both temporary files with `apply_patch`, reload, and confirm analytics returns to `OFF` with a non-cacheable `404`.

Phase 8 stops at these client-observable checks. Phase 12 verifies live ingestion of `/` and `/en`, absence of query/hash variants and server-observed duplicates, and receipt of the four property-free events after the independent Umami instance exists.

- [ ] **Step 6: Confirm the task leaves no probe artifacts**

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

Record completed Phase 8 behavior only after Task 4 passes including the strict social-file gate. If that JPEG remains pending, apply the independent factual and future-boundary corrections below but leave Phase 8 explicitly open at its asset gate. In active Phase 11 wording, replace `Laravel/Next.js` and `gateway, Next.js, Laravel y MySQL` with the actual Vite + React frontend, Laravel, internal gateway, and MySQL. Add the already-approved future responsibility split: Phase 11 materializes runtime config and releases immutable images; Phase 12 operations deploys/activates portfolio and independent Umami; Phase 13 reviews real analytics. Preserve clearly historical Next.js sections and do not mark future phases complete.

- [ ] **Step 2: Reconcile `docs/ARCHITECTURE.md`**

Replace the active obsolete Next.js paragraph under current Vite rendering with the two-shell/SPA contract. Document `/`, `/en`, redirects, explicit frontend whitelist, real static 404, one typed SEO source, generated sitemap, optional runtime config, analytics-off degradation, and Umami as an external independent service. Correct the active runtime inventory from Next `3000`/health to Vite `5173`/`GET /`. Preserve the explicitly historical Phase 5 section.

- [ ] **Step 3: Reconcile `docs/DEPLOYMENT.md`**

Correct active acceptance wording that still lists Next `16.2.12`, port `3000`, or `/health` as current. Document the future Phase 11 `/runtime-config.json` materialization with `Cache-Control: no-store` for `200` and `404`, same-image/digest reconfiguration, and the Phase 12 operations handoff for independent Umami. That handoff records an independently pinned official Umami image, PostgreSQL 12.14 or newer, separate persistence/backups, `DATABASE_URL`, `APP_SECRET`, administrator credential rotation, logging/retention ownership, website creation, Website ID delivery, and live ingestion checks—without implementing any of them. Add the post-deployment Search Console checklist limited to domain verification, sitemap submission, URL inspection, and indexing observation; record that tokens, DNS changes, and live submission remain outside Phase 8. Do not add production Dockerfiles, compose snippets, real values, SSH commands, `/srv` changes, Cloudflare configuration, or global Caddy configuration.

- [ ] **Step 4: Correct `docs/content/ASSET_INVENTORY.md`**

Replace the obsolete `AST-PROJECT-MEDIA` statement with the Phase 7 fact: Trucks and Drinks has 4 images and ReservaHub has 5. Add exact rows for `web/public/favicon.ico`, `web/public/favicon-16x16.png`, `web/public/favicon-32x32.png`, and `web/public/apple-touch-icon.png`, identifying them as final human-approved Phase 8 files copied without derivation. Add `web/public/social/luciano-gonzalez-social.jpg` only after the actual 1200 × 630 JPEG has been supplied and integrated; while pending, document the closed editorial design separately without claiming that the file exists. Do not add the Android Chrome files or `site.webmanifest`, and do not rewrite older asset history.

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
if (Test-Path -LiteralPath 'web/public/social/luciano-gonzalez-social.jpg') {
  docker compose exec -T web pnpm verify:build -- --require-social
} else {
  Write-Output 'SOCIAL IMAGE FINAL FILE: PENDING — Phase 8 remains open'
}
docker compose exec -T gateway caddy validate --config /etc/caddy/Caddyfile
git diff --check
git status --short
```

Expected: validator passes; typecheck passes; all Vitest tests pass; Vite build and base semantic inspection pass; Caddy validates; no whitespace errors; only the four documentation files are uncommitted at this point. When the physical social JPEG exists, the strict inspection also passes. When it remains pending, the command reports the single asset gate without failing or discarding independent work, and Phase 8 remains explicitly open. Report that no lint script or E2E framework exists; do not install either.

- [ ] **Step 7: Commit documentation and stop**

```powershell
git add ROADMAP.md docs/ARCHITECTURE.md docs/DEPLOYMENT.md docs/content/ASSET_INVENTORY.md
git commit -m "docs: reconcile phase 8 SEO and analytics contracts"
git status --short --branch
```

Expected: clean feature worktree. Report automated checks, HTTP/browser observations, the absence of real Umami ingestion testing, and that production/VPS deployment was not executed. If the social JPEG is pending, report it as the sole final closure blocker; otherwise report the strict social gate as passed. Stop before any Phase 11, Phase 12, or Phase 13 work.
