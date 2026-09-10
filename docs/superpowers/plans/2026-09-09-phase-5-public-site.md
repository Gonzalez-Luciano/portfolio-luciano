# Phase 5 Public Site Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the bilingual, accessible, responsive public portfolio from the six Phase 4 Laravel contracts, plus a guarded one-time initial-content import, without implementing Phase 6 motion or later-phase infrastructure.

**Architecture:** Laravel remains the authority for content, publication, media, and temporal caching. Next.js Server Components perform six parallel `no-store` reads through explicit runtime validators and a request-scoped memoized loader, while focused Client Components own only browser interaction. Expected endpoint failures remain typed values; the localized page owns structural, regional, and empty-state presentation.

**Tech Stack:** Next.js 16.2.12 App Router, React 19.2.4, strict TypeScript, next-intl 4.x, Tailwind CSS 4, Vitest 4, Testing Library 16, Laravel 13.25, PHP 8.5, PHPUnit 12, Filament 5, MySQL 8.4, Docker Compose, Caddy 2.11.4.

**Spec:** `docs/superpowers/specs/2026-09-09-phase-5-public-site-design.md`

## Global Constraints

- Treat the approved spec, including the approved §29.2 operational erratum, as authority. Do not reopen architecture during execution.
- Parent execution model must be GPT-5.6 Terra. When subagent-driven development is used, select every child model explicitly under `AGENTS.md`; never allow a child to inherit GPT-5.6 Sol silently.
- Start execution by invoking `superpowers:using-git-worktrees`; use one cohesive Phase 5 branch/worktree for every task, review, fix, and verification cycle.
- Laravel is the only temporal cache authority. Every Next public-content fetch uses `cache: 'no-store'`; do not add ISR, revalidation windows/tags, webhooks, persistent caches, or client resource caches.
- Start the six locale endpoint requests together. Do not introduce a BFF, Route Handler aggregator, seventh request, consolidated domain-copying view model, or per-section streaming.
- Parse every HTTP body as `unknown`, validate the exact Phase 4 envelope and consumed shape, tolerate unconsumed extra fields, and never cast JSON directly to a domain resource.
- Do not add Zod, a schema DSL, a state manager, dialog library, focus-trap library, Motion, GSAP, ScrollTrigger, Playwright, Cypress, axe-core, or another dependency.
- Server Components own content and composition by default. Client Components are restricted to theme interaction, locale/hash navigation, native-dialog coordination, Indexed Detail enhancement, Retry, fragment focus, and App Router-required error boundaries.
- Preserve the five primary destinations in this order: `#work`, `#expertise`, `#projects`, `#approach`, `#contact`. Work Cases precede Experience.
- Professional content, URLs, CV routes, image references, dates, metrics, employers, roles, projects, and Technology-group labels come only from approved sources/CMS. Never invent a fallback or infer a Project from GitHub.
- Keep Experience empty until organization/role/start receive human approval; keep Work Case problem/contribution/technical approach/outcome null until approved; keep Technology-group labels null until approved; keep Projects empty until Phase 7.
- Phase 5 metadata is only localized title and description from validated Profile. Canonical, alternates, Open Graph, Twitter, JSON-LD, sitemap, robots, analytics, and indexation stay in Phase 8.
- Do not implement Phase 6 motion, Phase 7 projects, Phase 10 automated E2E/accessibility/cross-browser infrastructure, Phase 11 CI/release, or deployment/server/Cloudflare work.
- Automated tests must not claim to prove native dialog modality, real scroll/fragment positioning, image optimization, 200% zoom, or screen-reader behavior; record those in real-browser QA.
- An implementation-complete result is not Phase 5 acceptance. Do not close the ROADMAP until the separate editorial-readiness gate is satisfied.

## Exact File Map

### Frontend data and shared contracts

| Path | Action | Responsibility |
|---|---|---|
| `web/src/lib/api/types.ts` | Modify | Six public resource types, envelopes, `EndpointName`, `EndpointFailure`, `EndpointResult<T>`, and coordinated loader result |
| `web/src/lib/api/validators.ts` | Create | Small primitive guards plus six exact runtime validators |
| `web/src/lib/api/validators.test.ts` | Create | Valid/missing/wrong/null/empty/malformed-item contract tests |
| `web/src/test/fixtures/public-api.ts` | Create | Synthetic, nonprofessional valid/malformed API fixtures used only by tests |
| `web/src/lib/api/client.ts` | Modify | Server-only public transport, envelope parsing, eight-second timeout, no-store, and safe normalization |
| `web/src/lib/api/client.test.ts` | Modify | Transport, timeout, no-store, envelope, HTTP, network, configuration, and unexpected-error tests |
| `web/src/lib/api/fetchers.ts` | Create | `fetchProfile`, `fetchSite`, `fetchExperiences`, `fetchWorkCases`, `fetchProjects`, `fetchTechnologies` |
| `web/src/lib/api/fetchers.test.ts` | Create | Per-endpoint path/validator/result tests |
| `web/src/lib/api/load-public-portfolio.ts` | Create | Six-way parallel loader and request-scoped React `cache()` export |
| `web/src/lib/api/load-public-portfolio.test.ts` | Create | Parallel-start, independent failure, memoization wiring, and fresh-request tests |

### Frontend routing, controls, shell, and boundaries

| Path | Action | Responsibility |
|---|---|---|
| `web/src/i18n/anchors.ts` | Create | Primary destinations, preserved/focus allowlist, `#top` exclusion, and locale href helpers |
| `web/src/i18n/anchors.test.ts` | Create | Exact order, allowlisting, unknown-hash removal, and locale URL tests |
| `web/messages/es.json` | Modify | Approved Spanish technical UI catalog |
| `web/messages/en.json` | Modify | Key-identical approved English technical UI catalog |
| `web/src/components/language-switcher.tsx` | Modify | Real cross-locale anchors, allowlisted hash preservation, and existing cookie persistence |
| `web/src/components/language-switcher.test.tsx` | Modify | ES/EN URL, hash, cookie, unknown hash, and callback tests |
| `web/src/components/theme-switcher.tsx` | Modify | Existing theme behavior plus hydration/no-JS visibility contract |
| `web/src/components/theme-switcher.test.tsx` | Modify | Existing behavior, dead-control marker, and hydration-safe markup tests |
| `web/src/theme/bootstrap.ts` | Modify | Existing pre-paint theme selection plus minimal JavaScript-presence marker |
| `web/src/theme/theme.test.ts` | Modify | Theme bootstrap and JavaScript-presence tests |
| `web/src/components/ui/retry-button.tsx` | Create | Localized `router.refresh()` control |
| `web/src/components/ui/retry-button.test.tsx` | Create | Labels, click, and pending/disabled observable behavior |
| `web/src/components/ui/fragment-focus-manager.tsx` | Create | Allowlisted focus enhancement without scroll ownership |
| `web/src/components/ui/fragment-focus-manager.test.tsx` | Create | Known/unknown/missing targets and hashchange tests |
| `web/src/components/ui/content-state.tsx` | Create | Semantic structural/regional/neutral state surfaces |
| `web/src/components/ui/content-state.test.tsx` | Create | Exact localized copy, roles, Retry presence, and no false announcements |
| `web/src/components/layout/primary-navigation.tsx` | Create | One shared five-destination server-rendered navigation definition |
| `web/src/components/layout/site-header.tsx` | Create | Sticky desktop/mobile header composition and skip-link target |
| `web/src/components/layout/mobile-navigation.tsx` | Create | Native `<dialog>` Client Component and local 64rem reconciliation |
| `web/src/components/layout/mobile-navigation.test.tsx` | Create | Open/close/Escape/link/locale/focus/scroll cleanup/breakpoint tests |
| `web/src/components/layout/site-footer.tsx` | Create | CMS-backed identity/links without biography duplication |
| `web/src/components/layout/layout.test.tsx` | Create | Shared destinations, desktop/mobile/no-JS shell, and dead-control tests |
| `web/src/app/[locale]/layout.tsx` | Modify | Document locale, bootstrap, provider, skip link, and root shell ownership |
| `web/src/app/[locale]/loading.tsx` | Create | One coordinated neutral loading shell |
| `web/src/app/[locale]/error.tsx` | Create | Localized unexpected subtree Client boundary |
| `web/src/app/[locale]/not-found.tsx` | Create | Localized technical 404 |
| `web/src/app/global-error.tsx` | Create | Self-contained root/localized-layout Client boundary |
| `web/src/app/[locale]/boundaries.test.tsx` | Create | Loading, error, global-error, and 404 markup/recovery tests |

### Frontend public sections and composition

| Path | Action | Responsibility |
|---|---|---|
| `web/src/components/sections/hero-section.tsx` | Create | CMS-only Hero with photo/no-photo layouts and `#work` CTA |
| `web/src/components/sections/about-section.tsx` | Create | Profile introduction at `#about` |
| `web/src/components/sections/profile-sections.test.tsx` | Create | Hero/About hierarchy, photo nullability, and no fallback tests |
| `web/src/components/sections/work-cases-section.tsx` | Create | SSR Work Case dossiers and field labels |
| `web/src/components/sections/indexed-work-cases.tsx` | Create | Client progressive desktop tabs around server-rendered dossiers |
| `web/src/components/sections/indexed-work-cases.test.tsx` | Create | Initial HTML, ARIA, keyboard, 0/1/many, and 64rem tests |
| `web/src/components/sections/experience-section.tsx` | Create | Experience timeline and localized open-ended date display |
| `web/src/components/sections/work-group.tsx` | Create | `#work` criticality/empty grouping with cases before experience |
| `web/src/components/sections/work-group.test.tsx` | Create | Content/empty/failure matrix and anchor hierarchy |
| `web/src/components/sections/expertise-group.tsx` | Create | Expertise Areas and Technology-group composition |
| `web/src/components/sections/expertise-group.test.tsx` | Create | Exact group order, nullable descriptions/icons, empty/failure matrix |
| `web/src/components/sections/projects-section.tsx` | Create | Project dossiers and CMS zero-project message |
| `web/src/components/sections/approach-section.tsx` | Create | Work principles and neutral empty state |
| `web/src/components/sections/contact-section.tsx` | Create | CMS intro, links, accessible new-tab suffix, and locale CV |
| `web/src/components/sections/site-sections.test.tsx` | Create | Projects/Approach/Contact content, nullable, empty, link, and CV tests |
| `web/src/app/[locale]/page.tsx` | Modify | Coordinated structural/regional/empty composition and basic metadata |
| `web/src/app/[locale]/page.test.tsx` | Modify | Full six-result route matrix, metadata, anchors, locale, and integration tests |
| `web/src/components/api-status.tsx` | Delete | Remove Phase 3 diagnostic placeholder from production UI |
| `web/src/components/api-status.test.tsx` | Delete | Remove obsolete diagnostic-only coverage |
| `web/src/app/globals.css` | Modify | Phase 2 tokens/layout, breakpoints, focus, no-JS, reduced-motion, and section styles |

### Backend import and evidence

| Path | Action | Responsibility |
|---|---|---|
| `api/app/Domain/Content/InitialPortfolioContent.php` | Create | Single deterministic structured representation of approved initial values and asset source paths |
| `api/tests/Unit/Domain/Content/InitialPortfolioContentTest.php` | Create | Dataset keys, references, gaps, and no-invention assertions |
| `api/database/seeders/PortfolioContentSeeder.php` | Modify | Consume the non-asset dataset slice while retaining repeatable draft behavior |
| `api/tests/Feature/Database/PortfolioContentSeederTest.php` | Modify | Preserve Phase 4 repeatability/no-assets/no-projects/no-experience behavior |
| `api/app/Domain/Content/InitialPortfolioImporter.php` | Create | Exact pristine-baseline preflight, asset preflight, transactional writes, and owned-file compensation |
| `api/app/Console/Commands/ImportInitialPortfolioContent.php` | Create | Public `portfolio:import-initial-content` one-time command |
| `api/tests/Feature/Console/ImportInitialPortfolioContentTest.php` | Create | Fresh baseline, every rejection case, assets, rollback, compensation, and second-run tests |
| `compose.yaml` | Modify | Read-only availability of tracked approved assets to the API in local/test application runtime |
| `docs/testing/PHASE_5_VERIFICATION.md` | Create | Automated, media-topology, browser, hydration, and editorial-readiness evidence ledger |
| `README.md` | Modify | Actual public-site development/test/import workflow |
| `web/README.md` | Modify | Replace scaffold text with web architecture and commands |
| `docs/ARCHITECTURE.md` | Modify | Server-only loader, request memoization, failure ownership, and Laravel-only cache |
| `docs/DEPLOYMENT.md` | Modify | Explicit guarded import handoff and approved asset-input requirement, without deployment operations |
| `ROADMAP.md` | Modify only at final gate | Record only verified technical items; keep Phase 5 open while editorial blockers remain |

---

### Task 1: Define and validate the six public contracts

**Objective:** Replace compile-time-only API assumptions with one strict public type set and explicit runtime validators for every Phase 4 resource.

**Files:**
- Modify: `web/src/lib/api/types.ts`
- Create: `web/src/lib/api/validators.ts`
- Create: `web/src/lib/api/validators.test.ts`
- Create: `web/src/test/fixtures/public-api.ts`

**Interfaces:**
- Consumes: Phase 4 shapes in `docs/api/PUBLIC_API_V1.md` and the approved spec §10–11.
- Produces: `Profile`, `SiteConfiguration`, `Experience`, `WorkCase`, `Project`, `Technology`, `EndpointName`, `EndpointFailureKind`, `EndpointFailure`, `EndpointResult<T>`, `PublicPortfolioResults`, `isProfile`, `isSiteConfiguration`, `isExperienceList`, `isWorkCaseList`, `isProjectList`, and `isTechnologyList`.

- [ ] **Step 1: Create synthetic test fixtures with no real professional claims**

```ts
export const validTechnology = {
  key: 'runtime-test',
  name: 'Runtime Test',
  category: 'backend',
  icon: null,
} as const;

export const validProfile = {
  name: 'Test Person',
  headline: 'Test Headline',
  short_summary: 'Test summary.',
  introduction: 'Test introduction.',
  availability: 'Test availability.',
  cta: 'Test CTA',
  photo: null,
} as const;
```

Add equivalent synthetic fixtures for Experience, Work Case, Project, and Site, including the four Technology groups in exact order. These values are test-only and must never be imported by production components or the PHP dataset.

- [ ] **Step 2: Write failing validator tests**

Use table-driven tests for every validator covering valid payload, missing required key, wrong primitive, valid nullable media/link fields, empty array success, malformed nested Technology, malformed collection item, invalid month, invalid URL shape, duplicate/wrong-order Technology groups, and tolerated unconsumed extra fields.

```ts
it.each([
  ['missing field', {...validProfile, headline: undefined}],
  ['wrong type', {...validProfile, cta: 42}],
])('rejects Profile with %s', (_case, payload) => {
  expect(isProfile(payload)).toBe(false);
});

it('accepts an empty collection', () => {
  expect(isProjectList([])).toBe(true);
});

it('rejects one malformed item inside a collection', () => {
  expect(isTechnologyList([validTechnology, {...validTechnology, name: 3}])).toBe(false);
});
```

- [ ] **Step 3: Run the validator suite and confirm the red state**

Run: `docker compose run --rm --no-deps web pnpm test:run src/lib/api/validators.test.ts`

Expected: FAIL because `validators.ts` and its exports do not exist.

- [ ] **Step 4: Implement the public types and focused guards**

Define the exact spec types once. Implement only small `isRecord`, `isString`, `isNullable`, `isArrayOf`, media, month, and URL helpers, followed by resource-specific guards. Check each consumed member and nested element; accept extra keys; do not encode publication/status/ordering decisions beyond the formal four-group HTTP contract.

- [ ] **Step 5: Run focused and static checks**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/lib/api/validators.test.ts
docker compose run --rm --no-deps web pnpm typecheck
```

Expected: all validator tests PASS and strict TypeScript reports zero errors.

- [ ] **Step 6: Commit the contract layer**

```powershell
git add web/src/lib/api/types.ts web/src/lib/api/validators.ts web/src/lib/api/validators.test.ts web/src/test/fixtures/public-api.ts
git commit -m "feat(web): validate public portfolio contracts"
```

**Completion criterion:** All six endpoint payloads transition from `unknown` to their single public TypeScript type only after explicit envelope-independent shape validation; every mandated malformed/nullable/empty case is covered.

### Task 2: Build server transport and the six endpoint fetchers

**Objective:** Acquire each localized endpoint through one safe server transport using `no-store`, an eight-second defensive timeout, and typed nonthrowing results for known operational failures.

**Files:**
- Modify: `web/src/lib/api/client.ts`
- Modify: `web/src/lib/api/client.test.ts`
- Create: `web/src/lib/api/fetchers.ts`
- Create: `web/src/lib/api/fetchers.test.ts`
- Modify: `web/src/app/[locale]/page.tsx`
- Modify: `web/src/app/[locale]/page.test.tsx`
- Delete: `web/src/components/api-status.tsx`
- Delete: `web/src/components/api-status.test.tsx`

**Interfaces:**
- Consumes: Task 1 validators/types; `INTERNAL_API_ORIGIN` from current Compose/runtime.
- Produces: `requestPublicResource<T>(endpoint, locale, validator, options?): Promise<EndpointResult<T>>` and six focused fetch functions with signature `(locale: Locale, options?: PublicRequestTestOptions) => Promise<EndpointResult<Resource>>`.

- [ ] **Step 1: Write failing transport tests**

Assert the exact URL `http://api/api/v1/es/profile`, `accept: application/json`, GET, `cache: 'no-store'`, a timeout signal, strict one-key `{data}` envelope, non-2xx→`http`, missing origin→`configuration`, malformed JSON/envelope/resource→`malformed`, fetch rejection and timeout abort→`network`, and no automatic second call. Also assert an unexpected validator exception is rethrown to App Router rather than normalized.

```ts
expect(fetchImpl).toHaveBeenCalledTimes(1);
expect(fetchImpl).toHaveBeenCalledWith(
  'http://api/api/v1/es/profile',
  expect.objectContaining({method: 'GET', cache: 'no-store'}),
);
```

- [ ] **Step 2: Write failing fetcher tests**

Use one table containing the six exact paths, validators, and expected result types. Assert a valid empty collection is `{ok:true,data:[]}`, while a malformed item is `{ok:false,failure:{kind:'malformed',endpoint}}`.

- [ ] **Step 3: Run the focused tests and confirm the red state**

Run: `docker compose run --rm --no-deps web pnpm test:run src/lib/api/client.test.ts src/lib/api/fetchers.test.ts`

Expected: FAIL because the server result transport/fetchers are absent.

- [ ] **Step 4: Implement minimal transport and focused fetchers**

Keep safe diagnostics limited to endpoint, locale, kind, and optional HTTP status. Use `AbortSignal.timeout(8_000)` when supported by the installed runtime and classify only its abort as `network`; do not catch programmer errors thrown outside HTTP/JSON/validation operations. Do not retain response bodies or internal URLs in failure values.

- [ ] **Step 5: Remove the browser diagnostic without breaking the interim page**

Remove `ApiStatus` and its test. Update the temporary foundation page/test to stop rendering/importing it; do not build Phase 5 composition in this task.

- [ ] **Step 6: Run focused, lint, and type checks**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/lib/api/client.test.ts src/lib/api/fetchers.test.ts src/app/[locale]/page.test.tsx
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
```

Expected: tests PASS, exactly one request per fetcher, and no lint/type errors.

- [ ] **Step 7: Commit the acquisition layer**

```powershell
git add web/src/lib/api web/src/app/[locale]/page.tsx web/src/app/[locale]/page.test.tsx
git rm web/src/components/api-status.tsx web/src/components/api-status.test.tsx
git commit -m "feat(web): fetch localized portfolio resources server-side"
```

**Completion criterion:** The six fetchers use the exact Phase 4 routes, no-store, one eight-second attempt, runtime validation, and preserve each known failure as a safe typed result.

### Task 3: Coordinate parallel loading with request-scoped memoization

**Objective:** Start all six independent requests together, preserve every individual result, and expose one React request-scoped cached loader for page and metadata.

**Files:**
- Create: `web/src/lib/api/load-public-portfolio.ts`
- Create: `web/src/lib/api/load-public-portfolio.test.ts`

**Interfaces:**
- Consumes: Task 2 `fetchProfile`, `fetchSite`, `fetchExperiences`, `fetchWorkCases`, `fetchProjects`, `fetchTechnologies`.
- Produces: `loadPublicPortfolioUncached(locale, dependencies?): Promise<PublicPortfolioResults>`, testable `createRequestScopedPortfolioLoader(cacheFn)`, and production `loadPublicPortfolio = cache(loadPublicPortfolioUncached)`.

- [ ] **Step 1: Write failing concurrency and independence tests**

Create six controlled promises, call the uncached loader, and assert all six fetch mocks have been called before resolving any promise. Resolve five and reject/return a typed failure for one; assert the completed object retains all healthy results and the exact failed endpoint.

```ts
const loading = loadPublicPortfolioUncached('es', fetchers);
expect(Object.values(fetchers).every((fn) => fn.mock.calls.length === 1)).toBe(true);
// Resolve the six deferred values only after this assertion.
await expect(loading).resolves.toMatchObject({projects: {ok: false}});
```

- [ ] **Step 2: Write request-scope wiring tests**

Pass a deterministic request-local memoizer to `createRequestScopedPortfolioLoader` so two consumers in one simulated render receive the same coordinated promise, then create a new memoizer for the next simulated request and prove all six fetchers run again. Also assert the production export is wired through React `cache()` and the source does not import/use `unstable_cache` or maintain a module result map.

- [ ] **Step 3: Run the loader test and confirm the red state**

Run: `docker compose run --rm --no-deps web pnpm test:run src/lib/api/load-public-portfolio.test.ts`

Expected: FAIL because the coordinated loader does not exist.

- [ ] **Step 4: Implement the loader**

Use one `Promise.all` over the six nonthrowing known-result fetchers and map tuple positions explicitly into `PublicPortfolioResults`. `createRequestScopedPortfolioLoader` accepts only a React-cache-compatible wrapper for testability; production passes React `cache`. Do not use Next Data Cache, `unstable_cache`, a global result `Map`, or incidental fetch deduplication.

- [ ] **Step 5: Run focused and static checks**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/lib/api/load-public-portfolio.test.ts
docker compose run --rm --no-deps web pnpm typecheck
```

Expected: PASS, including six simultaneous starts, independent failures, same-render sharing, and later-request reacquisition.

- [ ] **Step 6: Commit the coordinated loader**

```powershell
git add web/src/lib/api/load-public-portfolio.ts web/src/lib/api/load-public-portfolio.test.ts
git commit -m "feat(web): coordinate request-scoped portfolio loading"
```

**Completion criterion:** One locale acquisition returns six typed results without sequential waits; page and metadata can share it only for the current render/request, while a later request starts six fresh no-store reads.

### Task 4: Implement shared navigation, locale, focus, Retry, and theme contracts

**Objective:** Create the single anchor/navigation authority and the small reusable client controls without moving content or data fetching into the browser.

**Files:**
- Create: `web/src/i18n/anchors.ts`
- Create: `web/src/i18n/anchors.test.ts`
- Modify: `web/messages/es.json`
- Modify: `web/messages/en.json`
- Modify: `web/src/components/language-switcher.tsx`
- Modify: `web/src/components/language-switcher.test.tsx`
- Modify: `web/src/components/theme-switcher.tsx`
- Modify: `web/src/components/theme-switcher.test.tsx`
- Modify: `web/src/theme/bootstrap.ts`
- Modify: `web/src/theme/theme.test.ts`
- Create: `web/src/components/ui/retry-button.tsx`
- Create: `web/src/components/ui/retry-button.test.tsx`
- Create: `web/src/components/ui/fragment-focus-manager.tsx`
- Create: `web/src/components/ui/fragment-focus-manager.test.tsx`
- Create: `web/src/components/ui/content-state.tsx`
- Create: `web/src/components/ui/content-state.test.tsx`

**Interfaces:**
- Consumes: existing `Locale`, `localeCookieName`, theme storage/bootstrap, next-intl routing, and exact spec §8/16/18/19/24 copy.
- Produces: `PRIMARY_DESTINATIONS`, `PRESERVED_FRAGMENT_IDS`, `isPreservedFragment`, `localeHref`, `LanguageSwitcher`, `RetryButton`, `FragmentFocusManager`, `StructuralFailure`, `RegionalFailure`, and `NeutralEmptyState`.

- [ ] **Step 1: Write anchor and locale-switch red tests**

Assert exact destination order and IDs, full eleven-ID preserved/focus allowlist, deliberate exclusion of `top`, `/es#projects → /en#projects`, `/en#experience → /es#experience`, missing hash→locale root, and unknown hash→locale root. Extend `LanguageSwitcher` tests to prove real `href`, cookie persistence, optional `onNavigate`, and allowlisted hash use without manual scroll.

```ts
expect(PRIMARY_DESTINATIONS.map(({id}) => id)).toEqual([
  'work', 'expertise', 'projects', 'approach', 'contact',
]);
expect(localeHref('en', '#projects')).toBe('/en#projects');
expect(localeHref('en', '#unknown')).toBe('/en');
expect(isPreservedFragment('#top')).toBe(false);
```

- [ ] **Step 2: Write Retry, state-surface, fragment-focus, and theme red tests**

Mock `next/navigation` and assert Retry labels and `router.refresh()` only. Assert structural/regional failures use headings/text/buttons but no automatic `role="alert"`/live region; neutral empty has no Retry. For fragment focus, stub known/missing elements and verify `focus({preventScroll:true})` only for allowlisted existing targets on initial hydration/hashchange. Extend theme tests to assert the pre-paint script sets both `data-theme` and a minimal JavaScript-present marker while server markup remains deterministic.

- [ ] **Step 3: Run all focused tests and confirm the red state**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/i18n/anchors.test.ts src/components/language-switcher.test.tsx src/components/ui/retry-button.test.tsx src/components/ui/fragment-focus-manager.test.tsx src/components/ui/content-state.test.tsx src/components/theme-switcher.test.tsx src/theme/theme.test.ts
```

Expected: FAIL on absent anchor/control exports and new behavior.

- [ ] **Step 4: Add the exact bilingual UI catalog**

Replace the foundation-only catalog with a key-identical `Portfolio` namespace containing the exact spec tables: primary/section/menu labels, field/date labels, new-tab suffix, empty/failure/Retry strings, 404 strings, and existing language/theme accessible labels. Do not place Profile/Site/professional copy in JSON.

- [ ] **Step 5: Implement the navigation and client utilities**

Keep locale links as anchors. On activation, rewrite only an allowlisted current hash into the other-locale `href`, persist `portfolio_locale`, invoke the optional close callback, and let navigation proceed normally. Implement Retry with `router.refresh()` and a minimal transition pending flag only if React's installed primitive exposes it cleanly. Implement focus enhancement without `scrollTo`, offsets, history mutation, scroll-spy, or observer.

- [ ] **Step 6: Add the no-JavaScript/dead-control marker without a second app**

Extend the existing bootstrap to set one document JavaScript marker before paint. Mark interactive theme controls with the shared JS-only styling hook; retain current system/default CSS when JavaScript is disabled. Do not add a second theme authority or cookie.

- [ ] **Step 7: Run focused, catalog-alignment, lint, and type checks**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/i18n/anchors.test.ts src/components/language-switcher.test.tsx src/components/ui/retry-button.test.tsx src/components/ui/fragment-focus-manager.test.tsx src/components/ui/content-state.test.tsx src/components/theme-switcher.test.tsx src/theme/theme.test.ts src/app/[locale]/page.test.tsx
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
```

Expected: all tests PASS, message keys align, and no browser data-fetching is introduced.

- [ ] **Step 8: Commit the shared interaction contracts**

```powershell
git add web/messages web/src/i18n web/src/components/language-switcher.tsx web/src/components/language-switcher.test.tsx web/src/components/theme-switcher.tsx web/src/components/theme-switcher.test.tsx web/src/theme web/src/components/ui
git commit -m "feat(web): add shared portfolio navigation controls"
```

**Completion criterion:** The exact navigation/hash/copy contract has one source; locale/theme/Retry/focus behavior is accessible and narrowly client-owned; unknown hashes and `#top` receive no invented mapping.

### Task 5: Build the global shell, native mobile dialog, and App Router boundaries

**Objective:** Deliver the responsive document shell, one shared desktop/mobile/no-JS navigation, and safe loading/error/not-found coverage for the installed App Router.

**Files:**
- Create: `web/src/components/layout/primary-navigation.tsx`
- Create: `web/src/components/layout/site-header.tsx`
- Create: `web/src/components/layout/mobile-navigation.tsx`
- Create: `web/src/components/layout/mobile-navigation.test.tsx`
- Create: `web/src/components/layout/site-footer.tsx`
- Create: `web/src/components/layout/layout.test.tsx`
- Modify: `web/src/app/[locale]/layout.tsx`
- Create: `web/src/app/[locale]/loading.tsx`
- Create: `web/src/app/[locale]/error.tsx`
- Create: `web/src/app/[locale]/not-found.tsx`
- Create: `web/src/app/global-error.tsx`
- Create: `web/src/app/[locale]/boundaries.test.tsx`
- Modify: `web/src/app/globals.css`

**Interfaces:**
- Consumes: Task 4 anchor definitions, labels, `LanguageSwitcher`, `ThemeSwitcher`, state controls; optional CMS identity/link props.
- Produces: `SiteHeader`, `PrimaryNavigation`, `MobileNavigation`, `SiteFooter`, a skip target `#main-content`, and all required App Router route boundaries.

- [ ] **Step 1: Write shell/navigation red tests**

Render header variants for a structurally valid page and structural failure. Assert valid desktop/mobile/no-JS navigation contains the same five hrefs in order, structural failure omits professional destinations, identity uses CMS name and `#top`, mobile no-JS nav is named and includes the other-locale link, and JS-only Menu/theme controls carry the dead-control hiding contract.

- [ ] **Step 2: Write native-dialog red tests**

Polyfill only `HTMLDialogElement.showModal/close` state observable in jsdom. Assert real button and named dialog, initial focus, explicit close, cancel/Escape close, destination and locale close, trigger focus restoration, body scroll-state restoration, unmount cleanup, and `(min-width: 64rem)` transition. When crossing desktop while open, assert close/cleanup and focus to the visible identity link, never a hidden trigger. Do not claim native focus trapping.

- [ ] **Step 3: Write boundary red tests**

Assert `loading.tsx` has stable header/main/footer geometry and neutral skeletons without a person/role/project claim; localized `error.tsx` calls `reset`; `global-error.tsx` owns `<html>/<body>` and uses only already approved bilingual safe text; 404 uses exact ES/EN copy and a valid localized return link.

- [ ] **Step 4: Run focused tests and confirm the red state**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/components/layout/layout.test.tsx src/components/layout/mobile-navigation.test.tsx src/app/[locale]/boundaries.test.tsx
```

Expected: FAIL because the layout components and boundaries do not exist.

- [ ] **Step 5: Implement the shell and native dialog**

Render shared destination data through `PrimaryNavigation`; do not duplicate a second mobile/no-JS array. Coordinate `showModal()`, `close()`, native cancel, explicit close, callbacks, scroll restoration, unmount cleanup, and the component-local media query. Do not add a manual focus trap or `inert`.

- [ ] **Step 6: Implement layout and boundary responsibilities**

Keep the current locale validation, request locale, next-intl provider, early theme script, and `suppressHydrationWarning` limited to the intentionally mutated root. Add the skip link and `FragmentFocusManager`. Implement `[locale]/error.tsx` and `global-error.tsx` as required Client Components; use expected typed API states in normal page composition, not these boundaries.

- [ ] **Step 7: Establish Phase 2 base CSS contracts**

Bring the documented color/spacing/type/grid/sticky-header/focus tokens into `globals.css`; implement 20px/32px/48px/64px gutters at `<48rem`, `48–63.99rem`, `64–79.99rem`, and `80rem+`, cap at 90rem, set 44px controls and fragment scroll margins, and make JS-only/no-JS visibility truthful. Add reduced-motion removal for smooth scrolling/nonessential transitions.

- [ ] **Step 8: Run focused and static checks**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/components/layout src/app/[locale]/boundaries.test.tsx
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
```

Expected: PASS with one shared destination source and no dead controls in no-JS markup/CSS behavior.

- [ ] **Step 9: Commit the shell and safety boundaries**

```powershell
git add web/src/components/layout web/src/app/[locale]/layout.tsx web/src/app/[locale]/loading.tsx web/src/app/[locale]/error.tsx web/src/app/[locale]/not-found.tsx web/src/app/global-error.tsx web/src/app/[locale]/boundaries.test.tsx web/src/app/globals.css
git commit -m "feat(web): build accessible portfolio shell and boundaries"
```

**Completion criterion:** The installed App Router has correct subtree and root-layout fallbacks, the shell remains localized/theme-capable, and desktop/mobile/no-JS navigation uses one five-item definition with verified breakpoint cleanup.

### Task 6: Implement Hero and About with nullable CMS photography

**Objective:** Render the Profile-owned opening composition and introduction with intentional photo and text-only variants and no production fallback asset.

**Files:**
- Create: `web/src/components/sections/hero-section.tsx`
- Create: `web/src/components/sections/about-section.tsx`
- Create: `web/src/components/sections/profile-sections.test.tsx`
- Modify: `web/src/app/globals.css`

**Interfaces:**
- Consumes: Task 1 `Profile`; Task 5 layout tokens; Phase 4 `photo.url`/`photo.alt` unchanged.
- Produces: `HeroSection({profile}: {profile: Profile})` at `#top` and `AboutSection({introduction}: {introduction: string})` at `#about`.

- [ ] **Step 1: Write failing Profile-section tests**

Assert one `h1 = profile.name`, exact headline/summary/availability/CTA, CTA `href="#work"`, separate introduction, and CMS alt/url. Render `photo:null` and assert no `img`, initials, avatar, placeholder, prototype path, broken frame, or reserved media column.

- [ ] **Step 2: Run the tests and confirm the red state**

Run: `docker compose run --rm --no-deps web pnpm test:run src/components/sections/profile-sections.test.tsx`

Expected: FAIL because the Profile sections do not exist.

- [ ] **Step 3: Implement the two Server Components**

Use `next/image` with stable aspect-ratio geometry, responsive `sizes`, and the API reference verbatim as the preferred spec path. Keep media logic confined to meaningful photo presence; do not resolve internal origins, copy the approved image into `web/public`, or construct a fallback URL.

- [ ] **Step 4: Add responsive photo/text CSS**

Use portrait-first composition below 64rem and split composition at 64rem. The null-photo modifier must collapse the media column entirely and retain deliberate textual measure at every Phase 2 range.

- [ ] **Step 5: Run focused/static checks**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/components/sections/profile-sections.test.tsx
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
```

Expected: PASS for both layouts and no hardcoded production image.

- [ ] **Step 6: Commit the opening sections**

```powershell
git add web/src/components/sections/hero-section.tsx web/src/components/sections/about-section.tsx web/src/components/sections/profile-sections.test.tsx web/src/app/globals.css
git commit -m "feat(web): render CMS profile and nullable hero photo"
```

**Completion criterion:** Hero/About consume only valid Profile fields, `photo:null` is a first-class layout, and the only rendered photo URL/alt comes from Laravel.

### Task 7: Implement Work Cases progressive enhancement and Experience

**Objective:** Deliver the complete Work evidence sequence, with all Work Cases in SSR HTML, accessible desktop Indexed Detail, and Experience rendered second without inferred facts.

**Files:**
- Create: `web/src/components/sections/work-cases-section.tsx`
- Create: `web/src/components/sections/indexed-work-cases.tsx`
- Create: `web/src/components/sections/indexed-work-cases.test.tsx`
- Create: `web/src/components/sections/experience-section.tsx`
- Create: `web/src/components/sections/work-group.tsx`
- Create: `web/src/components/sections/work-group.test.tsx`
- Modify: `web/src/app/globals.css`

**Interfaces:**
- Consumes: Task 1 `EndpointResult<WorkCase[]>`, `EndpointResult<Experience[]>`; Task 4 labels/state surfaces.
- Produces: `WorkGroup({workCases, experiences, locale})`, conditional `#work-cases`/`#experience`, stable `#work`, and client `IndexedWorkCases` enhancement.

- [ ] **Step 1: Write SSR/progressive enhancement red tests**

Use `renderToStaticMarkup` to assert every case and every required field exists before hydration in Laravel order. Cover zero, one, and multiple cases. For multiple desktop cases assert tablist/tab/panel IDs, selected state, roving tab index, Up/Down/Home/End and activation behavior; below 64rem assert roles are removed and every dossier is visible.

- [ ] **Step 2: Write Work-group matrix red tests**

Cover both collections with content, first empty, second empty, both empty, each failure, simultaneous failures, and malformed-resource results supplied as failures. Assert cases appear before experience; successful empty granular sections have no heading/anchor; failed granular sections retain anchor and Retry; `#work` always exists on a structurally valid page.

- [ ] **Step 3: Run tests and confirm the red state**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/components/sections/indexed-work-cases.test.tsx src/components/sections/work-group.test.tsx
```

Expected: FAIL because Work components do not exist.

- [ ] **Step 4: Implement Server Component content and local formatting**

Render Context/Problem/Contribution/Technical approach/Outcome and Technology names exactly; do not omit required public fields. Format valid `YYYY-MM` with `Intl.DateTimeFormat` for the route locale; render `end:null` as exact `Actualidad`/`Present`; never infer organization or dates.

- [ ] **Step 5: Implement Indexed Detail as a narrow enhancement**

Pass server-rendered dossier children through the Client Component. Keep all panels visible and comprehensible in initial/no-JS DOM. On local `(min-width: 64rem)` match with multiple cases, apply tab semantics and hide only inactive visual panels; crossing below removes roles/hiding and reveals all. Do not fetch, animate, share viewport state, or add motion hooks.

- [ ] **Step 6: Add Work responsive/accessibility styles and rerun checks**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/components/sections/indexed-work-cases.test.tsx src/components/sections/work-group.test.tsx
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
```

Expected: PASS with complete SSR content, accessible desktop keyboard behavior, sequential narrow/no-JS content, and exact state distinctions.

- [ ] **Step 7: Commit Work**

```powershell
git add web/src/components/sections/work-cases-section.tsx web/src/components/sections/indexed-work-cases.tsx web/src/components/sections/indexed-work-cases.test.tsx web/src/components/sections/experience-section.tsx web/src/components/sections/work-group.tsx web/src/components/sections/work-group.test.tsx web/src/app/globals.css
git commit -m "feat(web): render progressive work evidence"
```

**Completion criterion:** Work Cases precede Experience, all case content exists in initial HTML, the local 64rem widget reconciles both directions, and every empty/failure anchor rule is proven.

### Task 8: Implement Expertise Areas and Technology grouping

**Objective:** Render Site-owned specialties and the Technologies endpoint under the exact CMS-provided group contract, preserving empty/failure distinctions and textual accessibility.

**Files:**
- Create: `web/src/components/sections/expertise-group.tsx`
- Create: `web/src/components/sections/expertise-group.test.tsx`
- Modify: `web/src/app/globals.css`

**Interfaces:**
- Consumes: Task 1 `SiteConfiguration['expertise_areas']`, `SiteConfiguration['technology_groups']`, and `EndpointResult<Technology[]>`; Task 4 state surfaces.
- Produces: `ExpertiseGroup({areas, groups, technologies})`, stable `#expertise`, conditional `#specialties`, and conditional/failure `#technologies`.

- [ ] **Step 1: Write failing matrix and grouping tests**

Cover areas+Technologies, empty areas, empty Technologies, both empty, Technology failure, nullable descriptions, nullable icons, and all four formal group keys. Assert API order is preserved, Technology names remain visible without icons, group labels come from Site, and no category label is invented.

```ts
expect(screen.getAllByRole('heading', {level: 4}).map((node) => node.textContent))
  .toEqual(['Group Backend', 'Group Data', 'Group Integration', 'Group Collaboration']);
```

- [ ] **Step 2: Run the test and confirm the red state**

Run: `docker compose run --rm --no-deps web pnpm test:run src/components/sections/expertise-group.test.tsx`

Expected: FAIL because `ExpertiseGroup` does not exist.

- [ ] **Step 3: Implement the Server Component**

Derive each displayed Technology group locally by matching the validated category to the validated Site group key; do not sort the endpoint collection or recreate labels. Omit a successful empty granular subsection and show exact neutral copy only when both group inputs are valid and empty. A failed Technologies endpoint retains `#technologies`, regional copy, and Retry.

- [ ] **Step 4: Add responsive and semantic styles**

Use the Phase 2 single-flow/mobile and multi-column/content-fit behavior, logical h2/h3 hierarchy, and decorative-icon treatment. Ensure long ES/EN labels wrap and remain visible.

- [ ] **Step 5: Verify and commit**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/components/sections/expertise-group.test.tsx
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
```

Expected: PASS.

```powershell
git add web/src/components/sections/expertise-group.tsx web/src/components/sections/expertise-group.test.tsx web/src/app/globals.css
git commit -m "feat(web): render CMS expertise and technologies"
```

**Completion criterion:** Expertise remains stable at `#expertise`, valid empty and failure states remain distinct, and all Technology group labels/order come from the validated Site contract.

### Task 9: Implement Projects, Approach, Contact, links, and CV

**Objective:** Complete the remaining Site/Project sections with the CMS zero-project message, nullable actions/media, approved link behavior, and no duplicated professional URL or CV route.

**Files:**
- Create: `web/src/components/sections/projects-section.tsx`
- Create: `web/src/components/sections/approach-section.tsx`
- Create: `web/src/components/sections/contact-section.tsx`
- Create: `web/src/components/sections/site-sections.test.tsx`
- Modify: `web/src/app/globals.css`

**Interfaces:**
- Consumes: Task 1 `EndpointResult<Project[]>`, Site `projects_empty_message`, `work_principles`, `contact_intro`, `professional_links`, and `cv`; Task 4 state surfaces and UI labels.
- Produces: stable `#projects`, `#approach`, `#contact`; conditional `#work-principles`; CMS-owned links/CV actions.

- [ ] **Step 1: Write Projects red tests**

Cover content, empty, and failure. Assert `projects:[]` renders only `site.projects_empty_message`, never neutral or Retry; failure renders regional copy/Retry; `featured` changes a CSS modifier only and never order; null image/demo/repository omit their surfaces; present image uses its CMS URL/alt.

- [ ] **Step 2: Write Approach and Contact red tests**

Cover principles content/empty; Contact intro-only, links-only, CV-only, all empty, and `cv:null`. Assert LinkedIn/GitHub keys use `target="_blank"`, `rel="me noopener noreferrer"`, and localized new-tab announcement; email/CV remain normal anchors. Assert `cv.url` is consumed verbatim and no `/cv/luciano-gonzalez-*.pdf` literal exists in component source.

- [ ] **Step 3: Run the tests and confirm the red state**

Run: `docker compose run --rm --no-deps web pnpm test:run src/components/sections/site-sections.test.tsx`

Expected: FAIL because the three sections do not exist.

- [ ] **Step 4: Implement the three Server Components**

Preserve Laravel order. Use Project image through `next/image` under the same Task 13 media gate as Profile. Keep all transformations local and omit absent surfaces cleanly. Treat Contact intro as valid presentable content even without actions; show neutral copy only when intro, links, and CV are all absent/empty.

- [ ] **Step 5: Add Phase 2 responsive styles and verify**

Projects stack below 48rem and use horizontal dossiers at 48rem+ where copy fits. Contact/actions must wrap without truncation and retain 44px targets.

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/components/sections/site-sections.test.tsx
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
```

Expected: PASS for every content/empty/failure/nullable case.

- [ ] **Step 6: Commit the Site-owned sections**

```powershell
git add web/src/components/sections/projects-section.tsx web/src/components/sections/approach-section.tsx web/src/components/sections/contact-section.tsx web/src/components/sections/site-sections.test.tsx web/src/app/globals.css
git commit -m "feat(web): render projects approach and contact"
```

**Completion criterion:** Projects use only the CMS empty message, Contact uses only CMS destinations/CV, and all three primary anchors survive legitimate empty or regional-failure states.

### Task 10: Compose the localized route and basic Profile metadata

**Objective:** Replace the foundation page with one coordinated SSR decision point that applies critical/regional/empty policy and shares loader acquisition with `generateMetadata()`.

**Files:**
- Modify: `web/src/app/[locale]/page.tsx`
- Modify: `web/src/app/[locale]/page.test.tsx`
- Modify: `web/src/components/layout/site-footer.tsx`
- Modify: `web/src/components/layout/site-header.tsx`
- Modify: `web/src/app/globals.css`

**Interfaces:**
- Consumes: Task 3 `loadPublicPortfolio`; Tasks 5–9 shell and section components.
- Produces: localized `default async function LocalePage`, `generateMetadata`, and the complete structurally-valid or structural-failure SSR response.

- [ ] **Step 1: Replace foundation tests with full route red tests**

Mock only `loadPublicPortfolio`. Cover six successes, Profile failure, Site failure, each collection failure, simultaneous collection failures, every collection empty, malformed-resource results, valid Profile+failed Site, and both locales. Assert structural failure renders controls/general error but no professional anchors/sections; regional failures preserve the other sections and the failed region anchor.

- [ ] **Step 2: Add navigation/heading/empty integration assertions**

For structurally valid pages assert exactly five primary links in stable order in every collection state, Work Cases before Experience, one h1, correct h2/h3 relationships, conditional granular anchors, Profile CTA to `#work`, Site Project empty message, and no hardcoded professional fallback.

- [ ] **Step 3: Add metadata and loader-sharing tests**

Assert exact ES/EN valid formula `${profile.name} — ${profile.headline}` plus `profile.short_summary`; Profile failure yields only `Portfolio no disponible`/`Portfolio unavailable`; Site-only failure retains valid Profile metadata. Call page and metadata within one mocked React render/request and assert one loader acquisition; reset request scope and assert a later render acquires again.

- [ ] **Step 4: Run the route tests and confirm the red state**

Run: `docker compose run --rm --no-deps web pnpm test:run src/app/[locale]/page.test.tsx`

Expected: FAIL because the foundation page does not implement the composition/metadata contract.

- [ ] **Step 5: Implement the coordinated page policy**

Validate locale, call the shared loader once, and branch first on Profile/Site. Structural failure uses the technical shell and omits all professional landmarks. A valid page passes endpoint results to each group; no section fetches. Header/footer receive only valid CMS subsets. Do not add Suspense per endpoint or a `PortfolioViewModel`.

- [ ] **Step 6: Implement `generateMetadata()` from the shared loader**

Use only Profile result and exact formulas. Omit description on failed/malformed Profile. Do not add canonical, alternates, Open Graph, Twitter, JSON-LD, robots, sitemap, or analytics.

- [ ] **Step 7: Verify route integration and build without API**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run src/app/[locale]/page.test.tsx src/lib/api/load-public-portfolio.test.ts
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
$env:INTERNAL_API_ORIGIN='http://unreachable.invalid'
docker compose run --rm --no-deps --env INTERNAL_API_ORIGIN web pnpm build
Remove-Item Env:INTERNAL_API_ORIGIN -ErrorAction SilentlyContinue
```

Expected: tests/lint/typecheck/build PASS; build does not require Laravel and produces no advanced metadata.

- [ ] **Step 8: Commit the public route**

```powershell
git add web/src/app/[locale]/page.tsx web/src/app/[locale]/page.test.tsx web/src/components/layout/site-header.tsx web/src/components/layout/site-footer.tsx web/src/app/globals.css
git commit -m "feat(web): compose the localized public portfolio"
```

**Completion criterion:** One SSR response applies the exact structural/regional/empty matrix, all sections consume the six typed results, metadata shares request-scoped acquisition, and offline production build succeeds.

### Task 11: Extract the deterministic initial-content dataset

**Objective:** Create one explicit code representation of approved Phase 1 content/assets and make the repeatable development seeder consume its non-asset slice without changing Phase 4 behavior.

**Files:**
- Create: `api/app/Domain/Content/InitialPortfolioContent.php`
- Create: `api/tests/Unit/Domain/Content/InitialPortfolioContentTest.php`
- Modify: `api/database/seeders/PortfolioContentSeeder.php`
- Modify: `api/tests/Feature/Database/PortfolioContentSeederTest.php`

**Interfaces:**
- Consumes: `docs/content/CONTENT.es.md`, `CONTENT.en.md`, `CONFIDENTIALITY_MATRIX.md`, `ASSET_INVENTORY.md`, and exact tracked asset paths.
- Produces: `InitialPortfolioContent::data(): array` as the sole code-level initial values; `PortfolioContentSeeder` retains its existing repeatable `updateOrCreate` contract.

- [ ] **Step 1: Write failing dataset tests**

Assert exact stable keys and approved bilingual values currently present in `PortfolioContentSeeder`, category references, unique keys/types, exact photo/CV source paths, draft-gap representation, zero Projects/Experiences, null Site Technology labels, and null Work Case problem/contribution/technical approach/outcome. Assert no Markdown parser, CV text extraction, employer/role/start date, or inferred Project value appears.

```php
$data = InitialPortfolioContent::data();

$this->assertSame([], $data['experiences']);
$this->assertSame([], $data['projects']);
$this->assertNull($data['site']['technology_backend_label_es']);
$this->assertNull($data['work_cases'][0]['problem_es']);
$this->assertSame('docs/content/approved-assets/professional-photo.jpg', $data['assets']['photo']);
```

- [ ] **Step 2: Run the dataset test and confirm the red state**

Run: `docker compose --profile test run --rm api-test php artisan test tests/Unit/Domain/Content/InitialPortfolioContentTest.php`

Expected: FAIL because `InitialPortfolioContent` does not exist.

- [ ] **Step 3: Implement the explicit dataset**

Move the current approved literals into structured arrays with precise PHP array-shape docblocks. Include asset source references and approved alt/CV labels, but never read or parse Markdown/PDF content at runtime. Keep null/incomplete editorial fields explicit.

- [ ] **Step 4: Refactor the development seeder to consume the dataset**

Retain `updateOrCreate` by singleton/type/key, repeatability, no asset writes, no CV/Experience/Project/user creation, and draft/hidden/unpublished state. Do not broaden the seed behavior to production import.

- [ ] **Step 5: Run focused backend tests and Pint**

Run:

```powershell
docker compose --profile test run --rm api-test php artisan test tests/Unit/Domain/Content/InitialPortfolioContentTest.php tests/Feature/Database/PortfolioContentSeederTest.php tests/Feature/DatabaseSeederTest.php
docker compose run --rm --no-deps api ./vendor/bin/pint --test app/Domain/Content/InitialPortfolioContent.php database/seeders/PortfolioContentSeeder.php tests/Unit/Domain/Content/InitialPortfolioContentTest.php tests/Feature/Database/PortfolioContentSeederTest.php
```

Expected: PASS with unchanged repeatable seeder counts/side effects.

- [ ] **Step 6: Commit the deterministic dataset**

```powershell
git add api/app/Domain/Content/InitialPortfolioContent.php api/tests/Unit/Domain/Content/InitialPortfolioContentTest.php api/database/seeders/PortfolioContentSeeder.php api/tests/Feature/Database/PortfolioContentSeederTest.php
git commit -m "refactor(api): centralize approved initial portfolio content"
```

**Completion criterion:** Approved initial values exist once in structured code, the development seeder remains repeatable and asset-free, and all known editorial gaps remain explicit null/empty values.

### Task 12: Implement the guarded one-time initial-content import

**Objective:** Add `portfolio:import-initial-content` with exact pristine-baseline preflight, asset validation, transactional draft writes, and command-specific filesystem compensation.

**Files:**
- Create: `api/app/Domain/Content/InitialPortfolioImporter.php`
- Create: `api/app/Console/Commands/ImportInitialPortfolioContent.php`
- Create: `api/tests/Feature/Console/ImportInitialPortfolioContentTest.php`
- Modify: `compose.yaml`

**Interfaces:**
- Consumes: Task 11 `InitialPortfolioContent::data()`, Phase 4 models, `AssetLifecycleService::replace(Model, UploadedFile)`, `PublicationStatus::Draft`, and the existing pristine structural Profile/Site rows.
- Produces: `InitialPortfolioImporter::__invoke(array $content): void` and operator command `php artisan portfolio:import-initial-content` returning success/failure without options; the command passes `InitialPortfolioContent::data()` while tests may pass a deliberately malformed copy.

- [ ] **Step 1: Write fresh/pristine baseline red tests**

Using `DatabaseMigrations`, assert a freshly migrated database contains exactly `profiles.default` and `site_configurations.default` and the other twelve tables are empty, then assert import succeeds by filling those exact IDs rather than creating singletons.

Define the twelve literally empty tables explicitly in the test data provider:

```php
[
    'experiences', 'experience_highlights', 'work_cases', 'projects',
    'technologies', 'expertise_areas', 'work_principles',
    'professional_links', 'cv_documents', 'experience_technology',
    'technology_work_case', 'project_technology',
]
```

- [ ] **Step 2: Write complete singleton rejection red tests**

For Profile and Site separately, mutate every real editorial category: name/copy, Technology label, private asset metadata/path, published+hidden, published+visible, and an unexpected extra singleton. For the defensive extra-row test, temporarily disable MySQL check enforcement only inside the test session, insert `singleton_key='unexpected'`, restore enforcement in `finally`, and leave the production schema unchanged. Snapshot all fourteen tables and both disks before invocation; assert command failure and byte-for-byte/count-identical DB plus filesystem afterward.

- [ ] **Step 3: Write twelve-table rejection and second-run red tests**

Use a data provider that inserts one valid row into each remaining table (creating only prerequisite parents inside the same test where a pivot/highlight requires them), invoke the command, and assert preflight rejection before any dataset or asset mutation. Run once successfully, snapshot, run again, and assert clear failure with the snapshot unchanged.

- [ ] **Step 4: Write dataset/asset/transaction/compensation red tests**

Cover missing photo, invalid photo bytes/MIME/size, invalid PDF header/EOF/MIME/size/language metadata, duplicate dataset keys/references, forced DB exception after at least one asset write, forced storage failure, preexisting file sentinel, no public copies/cache exposure, no users, and zero Experiences/Projects. On failures assert DB rollback and deletion of only exact private paths created by that attempt.

- [ ] **Step 5: Run the command suite and confirm the red state**

Run: `docker compose --profile test run --rm api-test php artisan test tests/Feature/Console/ImportInitialPortfolioContentTest.php`

Expected: FAIL because importer/command do not exist.

- [ ] **Step 6: Make approved tracked assets readable without copying them into the API tree**

Add a read-only `./docs:/var/www/docs:ro` mount to normal `api` and `api-test`, preserving the existing `/mnt/repo-docs:ro` contract used by `ApiContractDocumentationTest`. Resolve each dataset source from `base_path('../docs/content/approved-assets/...')`. Do not add assets to `web/public`, image build layers, or a public volume.

- [ ] **Step 7: Implement explicit pristine-baseline preflight**

Require exactly one Profile and Site row with `singleton_key='default'`. Compare their real Phase 4 editorial columns explicitly: every Profile professional/localized/photo column and every Site projects/contact/Technology-label column must be null; publication state must be draft/false/null. Ignore only structural/operational `id`, timestamps, and the expected singleton key. Require literal zero rows in the twelve-table list. Do not reduce this to checking status/visibility alone.

- [ ] **Step 8: Implement complete nonmutating asset/dataset preflight**

Validate stable-key/type uniqueness and relationship/category references. Verify source existence, real MIME, allowed size, image readability/dimensions, PDF signature/EOF, and exact `/Lang(es-AR)` or `/Lang(en-US)` metadata before DB/filesystem writes. Use Phase 4 size/MIME policy values; do not add a generic schema/content-sync framework.

- [ ] **Step 9: Implement transactional import and narrow compensation**

Within one DB transaction and `EditorialMutationContext::run()`, fill the existing singleton IDs and create the dataset collections as Draft/hidden/unpublished so Phase 4's mutation guard remains authoritative. Use `AssetLifecycleService::replace()` with test-mode `UploadedFile` wrappers for the approved photo and two CVs so Phase 4 private ownership is reused. Track only newly returned private paths from this invocation. On any later exception, allow DB rollback and delete only those tracked paths that did not exist before the attempt; never touch a preexisting path or public disk.

- [ ] **Step 10: Implement the command surface**

Use signature `portfolio:import-initial-content` with no flags, options, or alternate modes. Return `Command::FAILURE` and a safe clear message for preflight/import failure; return success only after full commit. Do not invoke Filament publication, migrations, `DatabaseSeeder`, startup logic, cache publication, or environment branches.

- [ ] **Step 11: Run focused/full import safety checks**

Run:

```powershell
docker compose --profile test run --rm api-test php artisan test tests/Feature/Console/ImportInitialPortfolioContentTest.php tests/Feature/Database/PortfolioContentSeederTest.php tests/Feature/Assets/AssetLifecycleTest.php tests/Feature/Assets/AssetTransitionActionTest.php
docker compose run --rm --no-deps api ./vendor/bin/pint --test app/Domain/Content/InitialPortfolioImporter.php app/Console/Commands/ImportInitialPortfolioContent.php tests/Feature/Console/ImportInitialPortfolioContentTest.php
```

Expected: PASS for fresh baseline, every rejection, second execution, transaction rollback, and filesystem ownership assertions.

- [ ] **Step 12: Commit the guarded import**

```powershell
git add compose.yaml api/app/Domain/Content/InitialPortfolioImporter.php api/app/Console/Commands/ImportInitialPortfolioContent.php api/tests/Feature/Console/ImportInitialPortfolioContentTest.php
git commit -m "feat(api): add guarded initial content import"
```

**Completion criterion:** A fresh migrated database imports once into the existing pristine singletons and empty graph; any prior editorial state rejects before mutation; all records remain draft/hidden/unpublished; storage compensation owns only files created by the failed attempt.

### Task 13: Verify the real Caddy/Next/Laravel media topology

**Objective:** Prove or disprove the preferred `next/image` + Phase 4 `/storage/...` route through the actual Compose topology before authorizing any media workaround.

**Files:**
- Create: `docs/testing/PHASE_5_VERIFICATION.md`
- Modify only if the preferred topology succeeds and a narrow public absolute origin is demonstrably required by the approved contract: `web/next.config.ts`
- Modify only to fix a defect within the already approved relative/public-origin strategies: `web/src/components/sections/hero-section.tsx`
- Modify only to fix a defect within the already approved relative/public-origin strategies: `web/src/components/sections/projects-section.tsx`

**Interfaces:**
- Consumes: Tasks 6/9 image components, Task 12 importer, current `compose.yaml`, `infra/caddy/Caddyfile`, Phase 4 `/storage/*` ownership.
- Produces: recorded API value, rendered image HTML, optimizer URL/status/content type, visible browser result, and internal-origin leak check.

- [ ] **Step 1: Start a named isolated integration stack and verify its exact target**

Run:

```powershell
$mediaProject = 'portfolio-phase5-media'
$env:GATEWAY_PORT = '8015'
docker compose -p $mediaProject config
docker compose -p $mediaProject up -d --build gateway
docker compose -p $mediaProject exec -T api php artisan migrate --force
docker compose -p $mediaProject exec -T api php artisan portfolio:import-initial-content
```

Expected: config resolves only the named Phase 5 stack; migration/import succeed; no existing default-project volume is reused.

- [ ] **Step 2: Publish only an isolated QA fixture through Phase 4 actions**

Use `UpdateContent` to set clearly synthetic Site Technology labels (`QA Backend`, `QA Data`, `QA Integration`, `QA Collaboration`) in both locale columns, then use `PublishContent` and `ShowContent` for Site and Profile. Do not edit the deterministic dataset or claim these QA labels are approved production content.

```powershell
docker compose -p $mediaProject exec -T api php artisan tinker --execute='$site = App\Models\SiteConfiguration::query()->sole(); $site = app(App\Domain\Content\Actions\UpdateContent::class)($site, ["technology_backend_label_es"=>"QA Backend","technology_backend_label_en"=>"QA Backend","technology_data_label_es"=>"QA Data","technology_data_label_en"=>"QA Data","technology_integration_label_es"=>"QA Integration","technology_integration_label_en"=>"QA Integration","technology_collaboration_label_es"=>"QA Collaboration","technology_collaboration_label_en"=>"QA Collaboration"]); $site = app(App\Domain\Content\Actions\PublishContent::class)($site); app(App\Domain\Content\Actions\ShowContent::class)($site); $profile = App\Models\Profile::query()->sole(); $profile = app(App\Domain\Content\Actions\PublishContent::class)($profile); app(App\Domain\Content\Actions\ShowContent::class)($profile);'
```

Expected: both resources become visible using the normal Phase 4 validation/public-copy path; Work Cases/Experience/Projects remain nonpublic.

- [ ] **Step 3: Capture HTTP and browser evidence**

Through `http://localhost:8015`, record `/api/v1/es/profile` photo value, `/es` emitted `img`/`srcset` markup, the corresponding `/_next/image?...` request/status/content type, and the visible crop. Search page source/network URLs for `http://api`, Docker hostnames, and `INTERNAL_API_ORIGIN`; expect none. Repeat representative light/dark and narrow/wide crops.

- [ ] **Step 4: Apply the mandatory incompatibility gate**

If root-relative `/storage/...` works through the optimizer, record PASS and retain it. If the Phase 4 contract already supplies an approved public absolute origin and only a narrow `remotePatterns` entry is necessary, add only that exact origin and rerun. If neither approved route works, **STOP execution here**: record request/response evidence and elevate the incompatibility. Do not create a Next proxy/Route Handler, change the API, expose `api:80`, copy media locally, or open broad remote patterns.

- [ ] **Step 5: Clean only the named isolated stack**

First run `docker compose -p $mediaProject ps -a` and confirm every container/volume belongs to `portfolio-phase5-media`; then run `docker compose -p $mediaProject down --volumes` and `Remove-Item Env:GATEWAY_PORT -ErrorAction SilentlyContinue`. Do not run a broad/default-project volume removal.

- [ ] **Step 6: Commit successful evidence and any approved narrow fix**

```powershell
git add docs/testing/PHASE_5_VERIFICATION.md web/next.config.ts web/src/components/sections/hero-section.tsx web/src/components/sections/projects-section.tsx
git commit -m "test: verify phase 5 public media topology"
```

Omit unchanged paths from `git add`. Do not commit a workaround after a STOP result.

**Completion criterion:** The preferred media flow has real Compose/Caddy/optimizer/browser evidence with no private origin, or execution has stopped with a concrete incompatibility before any unapproved architecture change.

### Task 14: Stabilize responsive, accessibility, hydration, and client-island integration

**Objective:** Verify the complete frontend as an integrated React/Next surface, close only evidence-backed defects, and prove automated hydration/client behavior without pretending jsdom is a real browser.

**Files:**
- Create: `web/src/app/[locale]/hydration.test.tsx`
- Modify as failures require: `web/src/app/globals.css`
- Modify as failures require: exact frontend files introduced in Tasks 4–10, always with a regression assertion in their existing focused test file

**Interfaces:**
- Consumes: all frontend components and route composition from Tasks 1–10; successful media strategy from Task 13.
- Produces: automated evidence for no hydration warning/error, no uncaught client exception, responsive-island reconciliation, and observable semantic/accessibility contracts.

- [ ] **Step 1: Write hydration integration tests before fixes**

Server-render and `hydrateRoot` representative ES and EN markup with both pre-applied themes, multiple Work Cases, mobile dialog, locale switch, and FragmentFocusManager. Spy on `console.error`, `window.error`, and `unhandledrejection`; exercise theme, tab selection, dialog open/close, and mocked media-query transitions; assert no hydration mismatch/warning/error or uncaught client exception.

```ts
expect(consoleError).not.toHaveBeenCalled();
expect(windowErrors).toEqual([]);
expect(unhandledRejections).toEqual([]);
```

Do not assert native dialog trapping, pixel scroll, real optimizer behavior, zoom, or screen-reader output here.

- [ ] **Step 2: Run the hydration/full frontend suite and capture the initial result**

Run:

```powershell
docker compose run --rm --no-deps web pnpm test:run
docker compose run --rm --no-deps web pnpm typecheck
docker compose run --rm --no-deps web pnpm lint
```

Expected before any required fix: the new hydration test exposes any integration defect; unrelated existing tests remain green.

- [ ] **Step 3: Fix each demonstrated integration defect test-first**

For every failure, tighten the nearest owning component/test rather than creating a global state layer. Preserve local 64rem listeners, server-first content, no-JS behavior, one navigation definition, and reduced-motion CSS. Do not introduce motion infrastructure or a viewport store.

- [ ] **Step 4: Run production build with API unavailable and static checks**

Run:

```powershell
$env:INTERNAL_API_ORIGIN='http://unreachable.invalid'
docker compose run --rm --no-deps --env INTERNAL_API_ORIGIN web pnpm build
Remove-Item Env:INTERNAL_API_ORIGIN -ErrorAction SilentlyContinue
docker compose run --rm --no-deps web pnpm format:check
docker compose run --rm --no-deps web pnpm test:run
docker compose run --rm --no-deps web pnpm typecheck
docker compose run --rm --no-deps web pnpm lint
```

Expected: all commands PASS; build performs no required live API acquisition; no hydration diagnostics are emitted by automated scenarios.

- [ ] **Step 5: Commit frontend stabilization**

```powershell
git add web/src/app/[locale]/hydration.test.tsx web/src/app/globals.css web/src/components web/src/i18n web/src/lib web/src/theme
git commit -m "test(web): stabilize phase 5 hydration and accessibility"
```

Only include files actually changed by evidence-backed fixes.

**Completion criterion:** All automated frontend checks pass, representative ES/EN/theme/client-island hydration is diagnostic-free, and no jsdom assertion overclaims browser-native behavior.

### Task 15: Complete real-browser QA, documentation, editorial gate, and final verification

**Objective:** Produce auditable Phase 5 evidence, align permanent documentation, distinguish implementation completion from editorial readiness, and stop before implementation-branch integration choices.

**Files:**
- Modify: `docs/testing/PHASE_5_VERIFICATION.md`
- Modify: `README.md`
- Modify: `web/README.md`
- Modify: `docs/ARCHITECTURE.md`
- Modify: `docs/DEPLOYMENT.md`
- Modify only when individual evidence is complete: `ROADMAP.md`
- Modify only for regression fixes proven during this task: exact owning implementation/test files from Tasks 1–14

**Interfaces:**
- Consumes: every Task 1–14 deliverable, Phase 2 responsive spec, Phase 4 API/publication flow, approved editorial sources, and the spec §3.1/31/35 gates.
- Produces: final command/browser evidence, documentation consistent with runtime, technical completion status, and a separate editorial-readiness verdict.

- [ ] **Step 1: Document the implemented architecture and operator workflow**

Update root/web README with six-request runtime, local commands, no-store/Laravel-cache ownership, and actual public-site workflow. Update Architecture with server transport, validators, result loader, request-scoped metadata/page sharing, criticality, and client islands. Update Deployment with the explicit one-time command, pristine-singleton/twelve-empty-table baseline, read-only approved asset inputs, draft-only result, second-run rejection, filesystem compensation, Filament review/publication sequence, and no automatic deployment execution.

- [ ] **Step 2: Run the real-browser responsive/theme/locale matrix**

Record browser/version and actual viewport for 320, 360, 390, 768/48rem, just below 64rem, 1024/64rem, 1280/80rem, and 1440/90rem+. Test ES and EN independently, light/dark, long copy, Hero photo/no-photo, loading, structural/regional/empty states, Contact/CV, and 200% browser zoom. Require no horizontal page scroll, clipping, overlap, truncation, or hidden actions and correct 20/32/48/64px gutters/content cap.

- [ ] **Step 3: Run keyboard, no-JS, reduced-motion, and screen-reader QA**

Verify skip link, focus visibility in both themes, heading/landmark order, five destination anchors, recognized locale hash, native fragment positioning plus focus enhancement, and unknown hash behavior. In a real JavaScript-disabled context verify all professional content and sequential Work dossiers remain readable, `<noscript>` mobile navigation/other-locale link works, and dead Menu/theme controls are absent. With reduced motion verify no functional dependency. Record screen reader name/version, browser, exact navigation/dialog/tabs/CV flow, and result; never write a generic “screen reader PASS”.

- [ ] **Step 4: Run real native-dialog, Indexed Detail, and console QA**

Verify `showModal()` modality, initial focus, native Escape, explicit close, destination/locale close, background scroll, trigger restoration, cleanup, and open-dialog crossing into desktop. Verify all dossiers below 64rem and accessible tabs above it, including crossing both directions. During ES/EN, theme bootstrap, tabs, dialog, locale/hash, and breakpoint flows, record that the browser console contains no hydration warning, hydration error, or uncaught client exception.

- [ ] **Step 5: Verify content/publication and editorial readiness separately**

Through normal Phase 4 Filament actions, verify a representative release-candidate Profile/Site, approved photo, professional links, both locale CVs, Work Cases, zero Projects, and any Experience decision. Record four unresolved gates without filling them from inference:

```text
Experience -> approved organization/role/start, or explicit human approval for empty release state
Work Cases -> approved bilingual problem/contribution/technical_approach/outcome
Technology groups -> exact approved ES/EN labels
Projects -> deliberately empty until Phase 7
```

If Profile/Site/photo/links/CVs/Work Cases/Technology labels or Experience decision are incomplete, record **IMPLEMENTATION COMPLETE / EDITORIAL ACCEPTANCE BLOCKED**. Do not close Phase 5 or use structural unavailability/neutral empty UI as acceptance evidence.

- [ ] **Step 6: Run the complete automated verification matrix**

Run:

```powershell
node infra/validation/validate-repository.mjs
docker compose config
docker compose run --rm --no-deps web pnpm format:check
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
docker compose run --rm --no-deps web pnpm test:run
$env:INTERNAL_API_ORIGIN='http://unreachable.invalid'
docker compose run --rm --no-deps --env INTERNAL_API_ORIGIN web pnpm build
Remove-Item Env:INTERNAL_API_ORIGIN -ErrorAction SilentlyContinue
docker compose --profile test run --rm api-test php artisan test
docker compose run --rm --no-deps api ./vendor/bin/pint --test
git diff --check
```

Expected: repository/Compose/frontend/backend/format/build checks PASS. Record exact dates, commands, exit results, test counts, and limitations in the ledger.

- [ ] **Step 7: Review the complete branch for scope and contract leaks**

Set `$phase5Base = git merge-base HEAD main`, then inspect `git diff $phase5Base...HEAD`, `git status --short`, generated page source, and repository search results. Reject direct JSON casts, professional content in frontend catalogs/components, `API_INTERNAL_URL`/Docker host exposure, frontend cache/revalidation, BFF/routes, client resource fetching, animation scaffolds, Playwright/axe, advanced metadata, invented Projects/Experience/Work Case/Technology labels, public CV copies, debug endpoints, and deployment/Cloudflare changes.

- [ ] **Step 8: Resolve discovered defects through the owning TDD cycle**

For any confirmed defect, add a failing regression to the focused test named in Tasks 1–14, implement the smallest correction in its owner, rerun focused and full affected checks, and make a focused commit. A media incompatibility must follow Task 13's STOP rule; do not “fix” it here by architecture expansion.

- [ ] **Step 9: Update ROADMAP only to the evidence actually earned**

Mark individual technical tasks only when their command/manual evidence passes. Mark Phase 5 acceptance/deliverables complete only if the §3.1 editorial-readiness gate also passes. Otherwise leave completion criteria open and record the named editorial blockers in `PHASE_5_VERIFICATION.md`.

- [ ] **Step 10: Commit documentation and verification evidence**

```powershell
git add docs/testing/PHASE_5_VERIFICATION.md README.md web/README.md docs/ARCHITECTURE.md docs/DEPLOYMENT.md ROADMAP.md
git commit -m "docs: record phase 5 verification and readiness"
```

Omit `ROADMAP.md` if no checkbox/status has legitimately changed.

- [ ] **Step 11: Request whole-branch review without integrating**

Invoke `superpowers:requesting-code-review` with a capable explicitly selected review model under `AGENTS.md`. Resolve every confirmed finding through Step 8, rerun Step 6, and update evidence. Do not invoke `finishing-a-development-branch`, merge, delete the worktree, or begin Phase 6 until the human reviews the implementation and editorial-readiness result.

**Completion criterion:** Automated and real-browser evidence is complete and truthful, permanent docs match runtime, hydration/client console evidence is clean, scope exclusions hold, and Phase 5 is reported as accepted only when both implementation and editorial gates pass.

## Producer/Consumer Dependency Order

```text
Task 1 types/validators
  -> Task 2 transport/fetchers
     -> Task 3 coordinated request loader

Task 4 shared anchors/copy/client utilities
  -> Task 5 shell/dialog/boundaries
  -> Tasks 7-9 section state labels and Retry

Task 5 shell + Task 6 Hero/About + Task 7 Work + Task 8 Expertise + Task 9 Site sections
  -> Task 10 localized page and metadata composition

Task 11 deterministic dataset
  -> Task 12 guarded import

Task 10 public page + Task 12 imported assets
  -> Task 13 real media topology gate
     -> Task 14 integrated frontend stabilization
        -> Task 15 QA/docs/editorial gate/final review
```

Tasks 4 and 11 may be implemented in either order after Task 1 because they share no mutable implementation surface. All other arrows are hard producer-before-consumer dependencies.

## Acceptance-Criteria Coverage

| Spec §35 criterion | Implementation/evidence owner |
|---|---|
| 1. Six runtime-validated contracts | Tasks 1–2 |
| 2. Six parallel no-store requests/coordinated SSR | Tasks 2–3, 10 |
| 3. Structural/regional/empty/malformed/unexpected distinction | Tasks 4–5, 7–10 |
| 4. Laravel-only temporal cache and next-request reacquisition | Tasks 2–3, 10, 15 |
| 5. Build without Laravel | Tasks 10, 14, 15 |
| 6. Navigation/locale/hash/theme/dialog/no-JS | Tasks 4–5, 14–15 |
| 7. Cases-first Work and progressive Indexed Detail | Task 7, Tasks 14–15 |
| 8. CMS-only Hero/sections/Contact/CV/media/zero Projects | Tasks 6–10, 13 |
| 9. Basic Profile metadata only | Task 10, Task 15 scope scan |
| 10. Responsive/accessibility/screen reader/reduced motion | Tasks 5–9, 14–15 |
| 11. Real Caddy/Next/Laravel media topology | Task 13 |
| 12. Deterministic guarded import and compensation | Tasks 11–12 |
| 13. No invented content | Tasks 1, 6–12, Task 15 editorial review |
| 14. All automated/static/manual checks | Tasks 14–15 |
| 15. No hydration/client runtime diagnostics | Tasks 14–15 |
| 16. Editorial readiness | Task 15, explicitly separate from implementation completion |
| 17. No later-phase/deployment leakage | Global Constraints and Task 15 branch scan |

## Known Risks and Mandatory Stop Points

1. **Media topology:** Task 13 may prove that relative `/storage/...` cannot be resolved by the Next optimizer from inside the current container topology. Stop before a proxy, API change, local copy, internal hostname, or broad `remotePatterns`; elevate evidence for a new human decision.
2. **Editorial readiness:** the Work Case field gap and Technology labels currently block publication; Experience requires approved structured data or an explicit empty-release decision. Technical completion must not be reported as Phase 5 acceptance.
3. **Native browser behavior:** `<dialog>`, fragment focus, no-JS, zoom, screen reader, and actual hydration console behavior require Task 15 real-browser evidence. A jsdom pass cannot waive this gate.
4. **Request-scoped caching:** React `cache()` must remain render/request scoped under installed Next 16.2.12. If execution shows cross-request persistence or page/metadata duplicate acquisition, stop and diagnose the installed runtime; do not replace it with `unstable_cache`.
5. **Import compensation:** if the existing asset service cannot expose enough ownership information to identify only files created by the current import, stop before deleting by namespace/glob or building a generic media transaction manager.
6. **Spec/Phase 4 contract mismatch:** any newly observed public field, nullable rule, ordering, publication validator, or route mismatch must be elevated and reconciled with `docs/api/PUBLIC_API_V1.md`; do not invent a frontend alternate contract.

## Plan Self-Review

- [x] Every approved spec section and all 17 final acceptance criteria map to explicit tasks/evidence above.
- [x] Every task lists exact files, interfaces, red tests, minimal implementation, commands, objective completion, and a suggested commit.
- [x] Type/function names are consistent from validators through fetchers, loader, page, and sections.
- [x] Producer-before-consumer dependencies are explicit; no task relies on an undefined prior interface.
- [x] The approved pristine-singleton/twelve-empty-table erratum is reflected in dataset/import tests and acceptance.
- [x] Work Case, Experience, Technology-label, and Project editorial gaps remain unresolved by code and are carried into the final gate.
- [x] Server/client, cache, metadata, failure, anchor, breakpoint, no-JS, and media boundaries remain exactly those approved.
- [x] No task adds Phase 6, 7, 8, 10, 11, deployment, server, or Cloudflare scope.
- [x] No task adds Playwright/axe or asks jsdom to prove browser-native behavior.
- [x] Plan contains no unresolved marker, cross-task shorthand, speculative file path, or hidden architectural decision.

## Execution Handoff (Do Not Execute Under GPT-5.6 Sol)

- Proposed branch: `codex/phase-5-public-site`
- Proposed worktree: `C:\Users\lucho\Desktop\portfolio-luciano\.worktrees\phase-5-public-site`
- Create/verify both only when execution begins via `superpowers:using-git-worktrees`.
- Recommended execution: `superpowers:subagent-driven-development` with GPT-5.6 Terra as parent and explicit child models under `AGENTS.md`.
- Do not execute Task 1, create the worktree, dispatch implementation agents, or invoke execution skills during this planning session.
