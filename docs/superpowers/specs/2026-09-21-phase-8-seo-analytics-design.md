# Phase 8 — SEO, localized static shells, and privacy-first analytics

- **Date:** 2026-09-21
- **Status:** ready for human review; not approved for implementation
- **Selected approach:** static localized shells
- **Timebox:** approximately 6–8 implementation hours, assuming approved visual assets are supplied ready for technical integration
- **Authority:** the human decisions approved during the Phase 8 audit and brainstorming on 2026-09-21

## 1. Purpose and success criteria

Phase 8 makes the existing Vite + React portfolio technically discoverable and shareable without changing its application architecture. Spanish and English crawlers receive localized metadata before JavaScript runs, public URLs have unambiguous canonical behavior, unknown frontend routes return real 404 responses, and the portfolio can optionally send minimal analytics to an independently operated self-hosted Umami instance.

The phase succeeds when:

- `/` serves a Spanish HTML shell with complete Spanish metadata;
- `/en` serves an English HTML shell with complete English metadata;
- `/es`, `/es/`, and `/en/` normalize through permanent redirects;
- canonical, `hreflang`, Open Graph, Twitter/X, JSON-LD, sitemap, robots, and status codes agree;
- unknown frontend routes cannot fall back to a `200` SPA shell;
- Umami is loaded only from valid optional runtime configuration;
- analytics failure never affects the portfolio;
- only the four approved fixed-name events exist, with no custom properties;
- active documentation describes Vite + React and the agreed release/operations boundary;
- the repository does not deploy Umami, access the VPS, or perform Search Console operations.

The complete professional content may continue to arrive from Laravel after the React application starts. This phase does not attempt to place all CMS content in the initial HTML.

## 2. Current architecture and constraints

The public site is an existing React 18 SPA built with Vite. Laravel remains the source of truth for managed content and is consumed at runtime. The portfolio gateway routes Laravel families before sending frontend traffic to Vite.

Phase 8 must preserve those boundaries:

- no dependency on Laravel during the frontend build;
- no duplication or snapshot of CMS production content in React;
- no SSR, full prerender, framework migration, or backend SEO renderer;
- no new frontend router solely for SEO routes;
- no production Docker wiring, VPS configuration, Cloudflare change, or global Caddy change;
- no new lint or end-to-end testing framework solely for this phase;
- no infrastructure service added to the portfolio runtime.

The selected design is intentionally small enough to fit one implementation plan and the approved timebox.

## 3. Selected architecture: localized static shells

Vite will build two localized entry documents that load the same React application and theme bootstrap:

```text
dist/index.html       -> Spanish shell for /
dist/en/index.html    -> English shell for /en
```

The documents differ in `html.lang`, localized description, canonical URL, Open Graph locale, and localized metadata. They share the application bundle, visual system, stable structured data, social image, and favicon assets.

A single typed static SEO configuration is the source of truth for:

- production origin;
- supported canonical paths;
- locale and Open Graph locale mappings;
- exact approved title and descriptions;
- canonical and `hreflang` relationships;
- social-image metadata;
- stable `Person` and `WebSite` structured data.

The build derives both HTML shells from this configuration. It must not fetch Laravel or any external service. Future changes to published copy require explicit human review of the SEO configuration; there is no automatic CMS synchronization.

The current client-side effect that replaces `document.title` after content fetch must be removed. A defensive update to `document.documentElement.lang` may remain if it still protects runtime locale correctness, but it must agree with the initial shell rather than compensate for a wrong shell.

This approach was selected over client-only metadata, which would make the initial document incorrect until JavaScript runs, and over SSR or full prerendering, which would add architecture and build-time content dependencies without current post-launch evidence that they are needed.

## 4. Public URL and gateway contract

### 4.1 Canonical documents and redirects

| Request path | HTTP behavior | Document / destination |
| --- | --- | --- |
| `/` | `200` | Spanish shell |
| `/en` | `200` | English shell |
| `/es` | `308` | `/` |
| `/es/` | `308` | `/` |
| `/en/` | `308` | `/en` |

The physical `dist/en/index.html` layout is not permission to delegate canonicalization to a static file server. The gateway must own these exact path decisions before any generic directory or file-server behavior:

```text
exact request /en
    -> internal rewrite/serve /en/index.html
    -> HTTP 200
    -> browser-visible URL remains /en

exact request /en/
    -> HTTP 308
    -> Location: /en
```

The implementation must prevent a file server from detecting `en/` as a directory and redirecting `/en` to `/en/`. It must not rely on automatic static-server canonicalization for either result. The concrete Caddy matcher and rewrite syntax belongs to the implementation plan; this specification fixes the observable behavior.

`/es` is a compatibility alias only. It has no shell, canonical, `hreflang`, sitemap entry, or client-side analytics pageview.

The language switch links Spanish directly to `/` and English directly to `/en`. When the current section exists in both languages, the switch preserves its fragment, for example `/#projects` to `/en#projects` and back.

Fragments are not sent in HTTP requests. Browser verification must therefore prove that:

```text
/es#projects -> HTTP redirect -> /#projects
```

and that the browser lands on the intended section. The implementation may add only the smallest architecture-compatible correction if the selected redirect mechanism fails this test; it must not introduce a router or intermediate tracking page.

### 4.2 Explicit frontend whitelist

The gateway continues to match existing Laravel, Filament, Livewire, storage, health, and CV route families before frontend handling. Backend responses retain their own status codes; a Laravel 404 must never become a frontend page.

The frontend side recognizes only the approved documents and required public assets, including:

- `/` and `/en`;
- Vite-generated static assets;
- approved public media required by the page;
- social-image and favicon files once supplied;
- `/sitemap.xml` and `/robots.txt`;
- `/runtime-config.json` when it has been materialized;
- development-only Vite/HMR traffic in the development environment.

Any unknown frontend document path returns the static 404 described below. In particular, an absent `/runtime-config.json` returns a real `404` and never falls through to either localized shell.

### 4.3 Frontend 404

Unknown frontend routes return a real HTTP `404` with a minimal, bilingual, self-contained document:

- `<html lang="es">`;
- Spanish primary copy;
- English copy marked with `lang="en"`;
- links to `/` and `/en`;
- `noindex` metadata;
- optional reinforcing `X-Robots-Tag: noindex, nofollow` from the gateway;
- no canonical;
- no React bundle;
- no Laravel API calls;
- no Umami tracker;
- no animation or other effects.

## 5. Localized metadata contract

### 5.1 Spanish shell at `/`

- `<html lang="es">`
- title: `Luciano González — Backend Engineer | PHP & Laravel`
- description: `Desarrollo backend orientado a APIs, lógica de negocio, datos y mantenimiento de aplicaciones.`
- canonical: `https://lucianogonzalez.dev/`
- robots: `index, follow, max-image-preview:large`

### 5.2 English shell at `/en`

- `<html lang="en">`
- title: `Luciano González — Backend Engineer | PHP & Laravel`
- description: `Backend development focused on APIs, business logic, data, and application maintenance.`
- canonical: `https://lucianogonzalez.dev/en`
- robots: `index, follow, max-image-preview:large`

Neither shell adds meta keywords, unapproved claims, invented metrics, client names, or dynamically fetched copy to the initial metadata.

### 5.3 Language alternates

Both shells declare all three absolute alternates:

| `hreflang` | URL |
| --- | --- |
| `es` | `https://lucianogonzalez.dev/` |
| `en` | `https://lucianogonzalez.dev/en` |
| `x-default` | `https://lucianogonzalez.dev/` |

## 6. Open Graph and Twitter/X

### 6.1 Open Graph

Both shells include:

- `og:type=website`;
- `og:site_name=Luciano González`;
- localized `og:title` and `og:description` using the exact approved metadata copy;
- `og:url` equal to that shell's canonical URL;
- an absolute `og:image` URL to the approved social image;
- `og:image:width=1200`;
- `og:image:height=630`;
- `og:image:alt=Luciano González — Backend Engineer | PHP & Laravel`.

Locale mapping is exact:

| Shell | `og:locale` | `og:locale:alternate` |
| --- | --- | --- |
| `/` | `es_AR` | `en_US` |
| `/en` | `en_US` | `es_AR` |

### 6.2 Twitter/X

Both shells include:

- `twitter:card=summary_large_image`;
- localized `twitter:title` and `twitter:description` using the same approved copy;
- the same approved image through `twitter:image`;
- `twitter:image:alt=Luciano González — Backend Engineer | PHP & Laravel`.

No `twitter:site` or `twitter:creator` is emitted without a separately approved account.

## 7. Human-provided visual assets

### 7.1 Social image

The single social image serves both languages and both Open Graph and Twitter/X. Its approved contract is:

- public JPEG, exactly `1200 × 630`;
- light Terracotta background;
- approved professional photograph on the right;
- approved name and title on the left;
- a very subtle sprout or landscape line;
- `lucianogonzalez.dev` as secondary information;
- no additional claim, metric, or invented content;
- visible copy limited to:

```text
Luciano González
Backend Engineer | PHP & Laravel
```

The implementation will consume the final human-approved file. It must not generate or substitute a face, invent a replacement image, publish a placeholder, or point metadata at a nonexistent file. The final same-origin public filename is an integration detail chosen only when the approved asset is supplied, and all metadata must reference that actual file.

### 7.2 Favicon family

The favicon uses the approved minimal Terracotta sprout direction:

- simple and recognizable at 16–32 px;
- no text and no portrait;
- visually consistent with the Phase 6 sprout;
- SVG as the modern favicon source;
- a small PNG or ICO fallback where required;
- a dedicated PNG `apple-touch-icon` suitable for that use;
- shared by both locales and both themes unless the final asset itself requires a theme variant.

These files are also human inputs. The implementation must not invent a new mark or brand system.

The missing approved social image or favicon files do not block this specification or its later implementation plan. They do block declaring Phase 8 implemented and closed.

## 8. Structured data

Each localized shell contains one static JSON-LD `@graph` with only `Person` and `WebSite`.

The `Person` node may contain:

- a stable same-origin `@id`;
- `name: Luciano González`;
- the primary site URL;
- `jobTitle: Backend Engineer`;
- approved public GitHub and LinkedIn URLs through `sameAs`;
- `image` only if a suitable final, stable public portrait URL exists without creating a new dependency.

The `WebSite` node contains:

- a stable same-origin `@id`;
- root URL `https://lucianogonzalez.dev/`;
- `name: Luciano González`;
- Spanish and English language declarations;
- a relation to the `Person` node.

No other schema is introduced. In particular, the graph excludes `Organization`, `SearchAction`, address, employer, clients, education, ratings, reviews, metrics, email, articles, project schema, and breadcrumbs.

## 9. Sitemap, robots, and non-indexable surfaces

### 9.1 Static sitemap

`/sitemap.xml` is a real static XML document containing only:

- `https://lucianogonzalez.dev/`;
- `https://lucianogonzalez.dev/en`.

Because the sitemap uses `xhtml:link` alternates, its root contract is `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">`. Each `<url>` contains its own `<loc>` and the same complete, reciprocal alternate set:

| `hreflang` | `href` |
| --- | --- |
| `es` | `https://lucianogonzalez.dev/` |
| `en` | `https://lucianogonzalez.dev/en` |
| `x-default` | `https://lucianogonzalez.dev/` |

Each URL therefore declares itself as well as the other locale, and both entries carry identical alternate relationships. The same typed SEO configuration generates the HTML `hreflang` links and sitemap relationships so they cannot drift independently.

The sitemap excludes aliases, normalized trailing-slash forms, admin, API, technical endpoints, and unknown paths. It does not invent `lastmod`, `changefreq`, or `priority`.

### 9.2 `robots.txt`

`/robots.txt` is a real public text file, not a SPA fallback. It allows the public site to be crawled and references the absolute sitemap URL:

```text
User-agent: *
Allow: /
Sitemap: https://lucianogonzalez.dev/sitemap.xml
```

This file is not a security boundary. Non-indexable responses expose their indexing instruction through HTTP or document metadata so crawlers can observe it; authentication and authorization remain the actual protection for administration.

### 9.3 `X-Robots-Tag`

The active gateway/application contract adds `X-Robots-Tag: noindex, nofollow` only through explicit route matchers for surfaces that must not be indexed:

- `/admin`, `/admin/*`, and specifically identified Filament administrative document/request routes, excluding Filament static-asset families;
- `/api` and `/api/*`;
- `/runtime-config.json` when present;
- identified health or technical response endpoints such as `/up` and `/__gateway/health`;
- the frontend 404 when this fits cleanly in the gateway.

The implementation must not attach this header to an umbrella Laravel matcher or another broad family that also includes public content. In particular, `/storage/*`, `/cv/*`, and other public media/assets remain outside the noindex matcher unless a separate human decision changes that policy. The implementation plan must enumerate and test the exact matchers rather than treating “technical endpoints” as a catch-all.

Phase 8 does not change Laravel authentication or expand into Phase 9 security hardening.

### 9.4 Search Console boundary

Phase 8 prepares canonical URLs, alternates, metadata, sitemap, robots, HTTP statuses, and a post-deployment checklist. Domain verification, Search Console enrollment, sitemap submission, URL inspection, and real indexing checks occur after deployment. The repository receives no verification token, DNS change, or real Search Console operation in this phase.

## 10. Analytics provider and service boundary

The approved provider is self-hosted Umami. It will later run on the OVH VPS as a service independent of the portfolio, likely at `analytics.lucianogonzalez.dev` or an operations-approved equivalent.

The Umami service is not part of the portfolio runtime:

- no Umami container in the portfolio compose project;
- no shared Laravel tables or application database;
- no Laravel dependency or analytics endpoint;
- no shared persistent volume, secrets, or backups;
- no dashboard built in the portfolio;
- the public frontend only consumes the external tracker.

The operations handoff requires a separately managed official Umami image, deliberately pinned; PostgreSQL 12.14 or newer in its own database service; separate persistence and backups; secrets such as `DATABASE_URL` and `APP_SECRET`; initial administrator credential rotation; hostname; TLS; Cloudflare; global Caddy; upgrades; logging/retention policy; and operational health checks. Those are requirements for the later operational context, not work performed by Phase 8.

## 11. Runtime analytics configuration

### 11.1 Public schema

The frontend requests same-origin `/runtime-config.json`. Its complete public schema is:

```json
{
  "umami": {
    "trackerUrl": "https://analytics.example.invalid/script.js",
    "websiteId": "00000000-0000-0000-0000-000000000000"
  }
}
```

This shape example is deliberately non-activating: `.invalid` is a reserved non-production domain and the nil UUID is rejected by validation. The configuration file does not exist by default, and no documentation value may become an application default.

Both values are public, not secrets. The response is handled as `unknown` and accepted only when:

- the root is an object;
- `umami` is an object;
- `trackerUrl` is an absolute `http:` or `https:` URL;
- the tracker URL contains no embedded username or password;
- `websiteId` is a syntactically valid, non-nil UUID;
- types are already correct, with no coercion.

Unknown properties are ignored. A partially valid Umami object is invalid as a whole. Production documentation requires HTTPS even though HTTP remains valid for controlled local verification.

The frontend must not use `VITE_*`, Laravel, or any other build-time analytics value.

### 11.2 Fetch and degradation

Runtime configuration loading is:

- non-blocking relative to application rendering;
- same-origin;
- equivalent to `cache: "no-store"`;
- equivalent to `credentials: "omit"`;
- free from persistent retries and visible user errors.

The HTTP response contract independently requires `Cache-Control: no-store` for `/runtime-config.json`. This header applies both when the config exists and when the route returns its intentional real `404`. The browser, portfolio gateway, host-level proxy, or another intermediate cache must not preserve an old config or negatively cache the initial absence across later activation.

Phase 8 defines and verifies this route contract in the current application/gateway context. Phase 11 must preserve it when implementing production materialization, and Phase 12 must preserve it through the deployed proxy path. This requirement does not authorize Phase 8 to configure Cloudflare or production infrastructure.

Any of the following leaves analytics `OFF` for that page load:

- missing file or real `404`;
- network failure;
- non-success HTTP response;
- malformed JSON;
- invalid schema;
- invalid tracker URL;
- invalid Website ID;
- subsequent tracker script load failure.

Analytics failure never affects React, content fetching, navigation, health checks, downloads, or outbound links.

## 12. Tracker lifecycle and pageviews

With valid runtime configuration, the frontend inserts exactly one non-blocking Umami tracker script. It uses:

- `trackerUrl` as its source;
- `websiteId` as `data-website-id`;
- a domain restriction to `lucianogonzalez.dev`;
- Do Not Track support;
- query-string exclusion;
- hash exclusion;
- Umami's normal initial pageview and History API support.

The initialization is idempotent. Repeated bootstrap calls, React development behavior, remounts, or navigation cannot add a second tracker or start a second initialization. If script loading fails, the failed reference is removed when safe, the page-load state becomes terminally `OFF`, and repeated calls do not retry or insert another script.

The tracker is absent from the static 404, Laravel admin, API, and technical endpoints. It is not hardcoded in either HTML shell.

The required eventual pageview behavior is:

| Navigation | Analytics path |
| --- | --- |
| `/` | `/` |
| `/en` | `/en` |
| URL with query string | path only; no query variant |
| URL with hash | path only; no hash variant |
| in-page `#projects` navigation | no artificial additional pageview |
| locale switch `/` ↔ `/en` | one pageview for the resulting document, no duplicate |
| `/es` or `/es/` | no `/es` pageview; HTTP redirect occurs before JavaScript and the destination records `/` |

Residual traffic to `/es` may be evaluated later from gateway/operations logs, not with a client-side interstitial or special event.

Phase 8 verifies the client-observable prerequisites for this table: the single inserted script, `data-website-id`, domain restriction, Do Not Track, search exclusion, hash exclusion, idempotence, and the absence of reinitialization on a hash change. It does not claim that a real Umami server received or deduplicated any pageview. Phase 12 verifies the table through live ingestion after the independent Umami service exists.

## 13. Approved custom events

Exactly four fixed-name events are permitted:

- `cv-download`;
- `github-click`;
- `linkedin-click`;
- `email-click`.

Where appropriate, existing links receive Umami's declarative event attribute, for example `data-umami-event="github-click"`. Every link for the same destination type reuses the same name regardless of section, locale, or visual variant.

No event carries properties. The implementation does not add locale, section, position, variant, identifiers, or any custom payload. It also does not add scroll, section-view, animation, pointer, or other custom events.

Instrumentation must not prevent, delay, or replace the primary link action. When analytics is `OFF`, the attributes are inert and all links remain fully functional.

## 14. Privacy contract

The portfolio integration uses:

- no analytics cookies;
- no `identify` call;
- no application-defined user or session identifier;
- no session properties;
- no event properties;
- no query strings or hashes in analytics URLs;
- no replay;
- no scroll, section, animation, or mouse tracking;
- no PII added by the application.

Normal HTTP communication necessarily exposes technical connection data such as IP address and User-Agent to the receiving server. Umami applies its cookieless model, while later operations must define logging and retention compatible with this privacy contract. No analytics data is stored in Laravel.

## 15. Release and activation lifecycle

### 15.1 Phase 8

Phase 8 owns:

- localized static shells and metadata;
- redirects, whitelist, and frontend 404 contract;
- sitemap, robots, structured data, and indexability headers;
- optional runtime-config consumer and Umami loader;
- the four event hooks and privacy constraints;
- scoped tests and current-documentation reconciliation;
- the operational requirements contract.

It does not deploy Umami or define VPS-specific values.

### 15.2 Phase 11: final repository-controlled phase

Phase 11 will create and locally verify the production Dockerfiles, `compose.production.yaml`, internal gateway, migrations/import/bootstrap flow, health checks, persistence, local smoke checks, CI, GitHub tag and release, GHCR images, digests, and deployment handoff. If the actual production architecture justifies a custom gateway image, that image is published alongside frontend and backend; MySQL remains a pinned official image.

Phase 11 also materializes the already-defined `/runtime-config.json` interface. The recommended model is normal public runtime values passed to a container entrypoint that atomically creates the JSON file. The exact production wiring belongs to Phase 11, not Phase 8. Both a present config response and an absent-config `404` must carry `Cache-Control: no-store` through the production internal gateway.

The result must permit:

```text
same immutable frontend image + same digest + different runtime configuration
```

without rebuilding Vite, creating another tag, or publishing another release. Missing or partial Umami values leave the file absent or analytics unambiguously `OFF`; they do not fail portfolio health.

Phase 11 ends after the release and handoff. GitHub Actions never receives SSH access and never deploys to the VPS.

### 15.3 Phase 12: external operational activation

The separate `vps_ops_claude` context later:

1. deploys the already-created portfolio release, initially with analytics `OFF` if necessary;
2. deploys Umami as an independent stack with its own PostgreSQL and persistence;
3. configures its hostname, Cloudflare, global Caddy, secrets, backups, logging, and retention;
4. creates the `lucianogonzalez.dev` website in Umami;
5. obtains the Website ID;
6. supplies `trackerUrl` and `websiteId` to the portfolio runtime;
7. recreates or reconfigures only the required runtime using the same released image and digest;
8. preserves `Cache-Control: no-store` for both present and absent runtime config across the deployed proxy chain;
9. verifies through real Umami ingestion that `/` and `/en` arrive without query/hash variants or duplicate pageviews, and that the four approved property-free events arrive.

The repository does not open SSH, touch `/srv`, modify Cloudflare or global Caddy, create real secrets, run production migrations, or perform production smoke checks.

### 15.4 Phase 13

Phase 13 reviews post-launch analytics behavior using real data: receipt of expected pageviews and events, absence of duplicates and event properties, privacy behavior, and any evidence that JavaScript indexing of the main content is insufficient. Only evidence from that review can justify reconsidering full content prerendering.

## 16. Error handling

| Failure | Required behavior |
| --- | --- |
| CMS/API content fetch fails | existing frontend failure behavior; SEO shell remains valid |
| `/runtime-config.json` missing | real `404`; analytics `OFF`; portfolio healthy |
| runtime JSON or schema invalid | analytics `OFF`; no visible error |
| only one Umami value present | analytics `OFF` |
| tracker script fails | remove failed reference when safe; terminal `OFF` for that page load; no retry loop |
| unknown frontend path | real static bilingual `404`; no React/API/Umami |
| backend route is unknown | preserve Laravel/backend 404 |
| redirect loses a hash in browser verification | smallest compatible correction before acceptance; no router or interstitial |
| approved visual asset missing | do not emit a broken reference or placeholder; Phase 8 cannot be closed |

## 17. Verification strategy

### 17.1 Unit tests

Use the existing Vitest setup to cover:

- exact ES/EN SEO values and locale mappings;
- structured-data shape and exclusion of unapproved schema;
- runtime-config validation from `unknown`;
- invalid URLs, credentials, UUIDs, nil UUID, partial config, and unknown-property behavior;
- `OFF` behavior for missing, HTTP, network, JSON, schema, and script errors;
- one script and one initialization under repeated bootstrap calls;
- exact tracker attributes for `data-website-id`, domain restriction, Do Not Track, search exclusion, and hash exclusion;
- terminal no-retry behavior after script failure;
- exact event names and absence of event properties;
- removal of post-fetch `document.title` mutation.

### 17.2 Build inspection

The existing production build must prove:

- Spanish and English HTML artifacts exist;
- complete localized metadata precedes JavaScript execution;
- canonical, alternates, social metadata, and JSON-LD are correct;
- sitemap and robots are real static files;
- the sitemap declares the XHTML namespace and the same reciprocal `es`, `en`, and `x-default` set for both URLs;
- approved visual assets exist before their metadata references are accepted;
- `/es` has no generated shell;
- the bundle contains no real or sample Umami Website ID and no build-time analytics configuration;
- no manifest or service worker is introduced;
- no API or external-service call is required during build.

Assertions should inspect semantic elements and exact critical values rather than snapshotting entire HTML documents.

### 17.3 HTTP and gateway verification

Through the portfolio gateway, verify:

- `200` localized responses for `/` and `/en`;
- exact `308` destinations for `/es`, `/es/`, and `/en/`;
- `/en` stays visible as `/en` while the gateway internally serves `/en/index.html`, with no reverse canonicalization to `/en/`;
- coherent `HEAD` behavior where applicable;
- real `404` for unknown frontend documents and absent runtime config;
- no SPA fallback for `/runtime-config.json`;
- `Cache-Control: no-store` on both present and absent/`404` runtime-config responses;
- correct content types for sitemap and robots;
- `noindex` behavior for the frontend 404 and explicitly matched non-indexable families;
- absence of an accidental `X-Robots-Tag` on `/storage/*`, `/cv/*`, and other public assets;
- existing Laravel routing precedence and preservation of backend 404 responses.

### 17.4 Browser verification

Use the browser/manual mechanism already available to the repository workflow. Do not add Playwright, Cypress, or another E2E framework solely for Phase 8.

Verify:

- direct load and refresh of `/` and `/en`;
- normal navigation and locale switching;
- `/es#projects` ending functionally at `/#projects`;
- locale switching with a preserved valid hash;
- anchors under `/en`;
- bilingual static 404 with no React, API, or Umami requests;
- analytics `OFF` for missing and invalid runtime configuration;
- one tracker for valid simulated runtime configuration;
- correct client-side tracker attributes for Website ID, domain restriction, Do Not Track, search exclusion, and hash exclusion;
- no duplicate script or second initialization from remount, navigation, development behavior, repeated bootstrap, or a hash change;
- functional CV, GitHub, LinkedIn, and email links with analytics both on and off.

These Phase 8 checks observe DOM, network loading, and portfolio navigation only. They do not certify the pathname stored by Umami, server-side query/hash exclusion, or absence of duplicate pageviews in Umami because the service is intentionally not deployed. Phase 12 verifies live ingestion for `/`, `/en`, query/hash exclusion, duplicate absence, and all four events. Phase 13 reviews post-launch data over time.

Phase 8 must not add a fake Umami ingestion server or other infrastructure to imitate that later verification.

### 17.5 Available checks and YAGNI

The repository currently provides `typecheck`, `test:run`, and `build` scripts for the frontend, plus repository and Docker/gateway checks. It does not provide a frontend lint script or an E2E framework.

Phase 8 therefore:

- runs the checks that actually exist;
- records the absence of lint rather than installing lint tooling;
- performs focused browser verification without adding E2E infrastructure;
- leaves comprehensive QA to Phase 10;
- reports any check that could not be executed instead of assuming success.

Before declaring implementation complete, the future implementation session must also review the diff, validate the gateway configuration, search for accidental Umami values, distinguish active Next.js drift from preserved history, and confirm the working-tree state.

## 18. Documentation reconciliation

Documentation changes are factual, active, and scoped to this phase.

### 18.1 `ROADMAP.md`

- record the implemented Phase 8 contracts only when implementation has actually passed;
- correct active Phase 11 references from Laravel/Next.js to Vite + React, Laravel, internal gateway, and MySQL;
- preserve Phase 11 as the final repository-controlled phase;
- assign production runtime-config materialization to Phase 11;
- assign real Umami deployment and activation to Phase 12 operations;
- preserve Phase 13 for real analytics review;
- retain clearly historical descriptions of superseded phases;
- never imply GitHub Actions deploys or SSHs to the VPS.

### 18.2 `docs/ARCHITECTURE.md`

- remove or correct the active paragraph that mixes the former Next.js frontend into the current Vite description;
- document localized shells, normalized URLs, route whitelist, and static 404;
- document the optional runtime-config interface and analytics degradation;
- describe Umami as an external independent service;
- preserve explicitly historical material.

### 18.3 `docs/DEPLOYMENT.md`

- correct active health, port, and frontend contracts inherited from Next.js;
- document the future runtime-config handoff and same-image activation model;
- separate repository release from operational deployment and Umami activation;
- avoid inventing Phase 11 production Dockerfiles or compose wiring;
- include no real production values, secrets, `/srv` override, Cloudflare change, or global Caddy configuration.

### 18.4 `docs/content/ASSET_INVENTORY.md`

- replace the obsolete claim that no project screenshots exist;
- record four Trucks and Drinks images and five ReservaHub images from Phase 7;
- add the social image and favicon family only after their final files are human-approved and integrated.

No general documentation cleanup is authorized. `docs/SERVER_ARCHITECTURE.md` must not present Umami as already deployed, and future Phase 11/12 work must not be marked complete.

## 19. Timebox and implementation order

Expected implementation effort is approximately 6–8 hours:

1. typed SEO configuration and two shells: 1–1.5 hours;
2. gateway whitelist, redirects, and static 404: 1–1.5 hours;
3. metadata, JSON-LD, sitemap, robots, and approved-asset integration: 1–1.5 hours;
4. runtime-config validation, loader, and event attributes: 1–1.5 hours;
5. unit, build, HTTP, and browser verification: 1–1.5 hours;
6. scoped documentation reconciliation and final review: 0.5–1 hour.

This estimate assumes the final social image and favicon family arrive ready for technical integration. Their temporary absence does not enlarge the architecture; it keeps the final closure gate open. If implementation reveals that the design cannot fit approximately within one working day, work stops for human review before adding scope.

## 20. Explicitly deferred work

The following are outside Phase 8:

- SSR, full prerender, or a backend SEO renderer;
- Next.js or any framework migration;
- CMS snapshots or API access during build;
- a new client router;
- manifest, service worker, offline mode, installability, or other PWA behavior;
- production Dockerfiles and `compose.production.yaml`;
- definitive production materialization of runtime config;
- Umami or PostgreSQL deployment;
- VPS, SSH, `/srv`, Cloudflare, DNS, global Caddy, UFW, backups, or real secrets;
- real Search Console verification or submission;
- GitHub Actions deployment or any CI runner with VPS access;
- new analytics events, properties, identification, replay, or a custom dashboard;
- a new lint stack or browser E2E framework;
- comprehensive Phase 10 QA;
- Phase 9 security hardening beyond the narrow indexing headers approved here;
- broad visual redesign or unrelated documentation cleanup.

Full-content prerendering may be reconsidered only after post-launch evidence shows that JavaScript indexing of the main portfolio content is insufficient.

## 21. Acceptance gates

Phase 8 may be declared implemented and closed only when all of the following are true:

1. The URL, shell, canonical, alternate, redirect, and real-404 contracts pass, including internal serving of `/en/index.html` without redirecting visible `/en` to `/en/`.
2. Localized metadata, social metadata, JSON-LD, sitemap namespaces and reciprocal alternates, robots, and indexing headers match this specification.
3. The human-approved social image and favicon files are integrated and referenced without placeholders.
4. Runtime config is optional, validated from `unknown`, absent from the build-time configuration surface, and served with `Cache-Control: no-store` for both success and absence/`404`.
5. Analytics remains harmlessly `OFF` for every specified failure.
6. A valid simulated config produces one correctly attributed tracker, no duplicate client initialization, and only the four approved property-free event hooks; real ingestion remains a Phase 12 check.
7. Hash, locale-switch, direct-refresh, and unknown-route behavior is verified in a browser.
8. Available automated checks, gateway checks, and build inspection pass; unavailable tooling is reported without adding it.
9. Active documentation drift is corrected without rewriting history or marking future work complete.
10. No production infrastructure, operational access, or later-phase implementation has entered the repository.

After this written specification is approved, the only permitted next design-process step is `superpowers:writing-plans`. Implementation remains blocked until that plan is written, reviewed, and its execution method is selected.
