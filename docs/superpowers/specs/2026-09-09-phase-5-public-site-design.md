# Phase 5 — Public site design

**Status:** Written specification proposed for human approval

**Date:** 2026-09-09

**Planning branch:** `main`

**Implementation branch/worktree:** Not created. They belong to the later
execution gate.

## 1. Purpose and authority

Phase 5 turns the approved static experience and the implemented Laravel public
content contract into the production Next.js portfolio. The result must be a
functional, responsive, accessible, bilingual, two-theme, CMS-driven public site
before any cinematic motion layer is added.

This specification refines, and does not silently replace:

- `AGENTS.md`;
- `ROADMAP.md`, especially the Phase 4 -> Phase 5 boundary;
- `docs/PROJECT.md` and `docs/ARCHITECTURE.md`;
- `docs/SERVER_ARCHITECTURE.md` and `docs/DEPLOYMENT.md` for runtime boundaries;
- approved Phase 1 content and confidentiality decisions;
- the approved Phase 2 experience design, sitemap, tokens, wireframes, and
  responsive contract;
- the implemented Phase 3 Next.js, locale, theme, API-client, Docker, and Caddy
  foundations;
- the implemented Phase 4 CMS, publication, cache, media, CV, and localized API
  contract;
- all Phase 5 architectural decisions approved during brainstorming.

Laravel remains the source of truth for managed public content, publication,
visibility, asset availability, ordering, and temporal freshness. Next.js owns
presentation, runtime shape validation, server composition, locale navigation,
themes, responsive behavior, accessibility, and basic metadata.

Where historic planning text differs from implemented Phase 4 behavior, the
implemented public routes, Resources, publication validators, tests, and
`docs/api/PUBLIC_API_V1.md` are authoritative. Draft database columns being
nullable does not make a field nullable in a valid published resource when the
Phase 4 publication validator requires it.

## 2. Phase 4 boundary and readiness

Phase 4 is closed and integrated into `main`. Its final whole-branch review is
represented by commit `f3d60d02810b3914873ffe768307952ef087e09c`; the branch was
fast-forwarded to `main`, and that commit is an ancestor of the Phase 5 planning
baseline. The remaining Phase 4 notes are non-blocking cleanup or documentation
drift, not missing public-site prerequisites.

Phase 5 consumes, rather than rebuilds:

- Filament-managed localized content;
- draft/published and visibility rules;
- deterministic public ordering;
- localized public REST Resources;
- public cache and invalidation in Laravel;
- media publication rules and public asset URLs;
- locale-specific CV selection and download routes;
- safe API error envelopes.

Phase 5 must not add a second CMS, publication rule set, cache invalidation
system, media model, or alternative public API shape.

## 3. Goals and acceptance contract

After Phase 5 implementation:

1. `/es` and `/en` render a coherent single-page portfolio from the six Phase 4
   endpoints.
2. Content is acquired in Server Components, validated from `unknown`, and
   composed without duplicating Laravel business rules.
3. Profile and Site failures produce a safe structural failure; collection
   failures remain regional; successful empty collections remain successful.
4. The five primary navigation destinations are stable on every structurally
   valid page.
5. The site works at the Phase 2 responsive breakpoints, at 320 CSS pixels, and
   at 200% browser zoom.
6. The main professional content remains available without JavaScript; client
   interactions degrade honestly.
7. Both themes and both locales provide equivalent usable quality.
8. Profile and project media are nullable without creating broken layouts or
   frontend fallbacks.
9. Locale-appropriate CV availability comes only from `site.cv`.
10. Basic localized title and description are derived from validated Profile
    content.
11. An explicit one-time production import provisions only approved content and
    assets as draft, hidden, unpublished records.
12. Automated checks and documented real-browser QA both pass.
13. No Phase 6 motion, Phase 8 advanced metadata, Phase 10 E2E infrastructure,
    or deployment work is introduced.

### 3.1 Implementation completion and Phase 5 acceptance are different gates

**Implementation complete** means the specified code, import command,
automated tests, integration checks, and QA procedure have been implemented and
verified without inventing missing editorial data. Runtime support for an empty
Experience collection, unpublished singletons, and incomplete drafts can be
complete even while human content decisions remain pending.

**Phase 5 accepted / editorially ready** means a representative release-candidate
dataset has also passed the ROADMAP Phase 5 content review and the public site
actually presents its required real content. It does not require deployment to
the final home server, but it does require evidence through the normal Phase 4
publication flow that:

- Profile and Site are complete, published, visible, and consumable in ES/EN;
- the approved Profile photograph is present in the published Profile;
- the approved Work Cases have every Phase 4-required bilingual field approved
  and are publishable/public, rather than being replaced by a neutral Work state;
- Experience has either received approved structured CMS data or Luciano has
  made an explicit documented editorial decision that an empty Experience
  subsection is acceptable for the Phase 5 release candidate;
- Technology group labels have exact human-approved ES/EN copy so Site can be
  published;
- approved professional links and both locale CVs are published and verified;
- Projects remains the explicitly approved empty collection until Phase 7.

Until those conditions are met, implementation may be reported complete, but
Phase 5 must remain **editorial acceptance blocked**. Its ROADMAP acceptance
criteria and completion checkboxes must not be closed merely because the
structural-unavailability or neutral-empty states work correctly.

## 4. Scope

Phase 5 includes:

- localized global layout and shell;
- header, shared navigation contract, language control, theme control, mobile
  navigation, and footer;
- loading, expected structural/regional failures, unexpected error boundary,
  and localized 404 behavior;
- Hero, presentation, Work, Expertise, Projects, Approach, and Contact;
- six-endpoint Laravel integration with explicit runtime validation;
- server-only parallel acquisition and coordinated rendering;
- Laravel-only temporal cache ownership;
- empty, nullable media, missing CV, and API failure states;
- responsive and baseline accessibility requirements from Phase 2;
- basic localized title and description;
- a separate guarded initial production-content import;
- frontend and backend automated tests plus documented manual QA.

## 5. Non-goals and rejected architecture

Phase 5 does not include:

- Motion, GSAP, ScrollTrigger, scroll narratives, a node field, animation
  registries, or animation-specific performance tuning;
- new portfolio projects or invented project material;
- canonical URLs, `hreflang`, language alternates, Open Graph, Twitter cards,
  JSON-LD, sitemap, robots, social images, analytics, or indexation strategy;
- Playwright, Cypress, axe-core, automated cross-browser infrastructure, or a
  new E2E CI pipeline;
- deployment, Cloudflare, physical-host changes, production Compose ownership,
  or server operations;
- a Next Route Handler/BFF, `/api/portfolio`, or a seventh HTTP request;
- a consolidated `PortfolioViewModel` that copies the Laravel domain;
- Zod or another schema dependency;
- a generic home-grown schema framework;
- a global client state library;
- client-side resource fetching, polling, automatic retry, exponential backoff,
  circuit breaker, ISR, or frontend content synchronization;
- Markdown parsing, CV extraction, NLP, or docs-to-CMS synchronization;
- a generic media transaction manager or content sync engine.

Three architectural approaches were considered:

1. **Direct server-side composition — chosen.** A server-only loader coordinates
   focused endpoint fetchers; the page applies presentation policy; Client
   Components are limited to interaction.
2. **Consolidated frontend view model — rejected.** It would duplicate the
   public domain and obscure producer/consumer ownership without a real
   transformation need.
3. **Next BFF/aggregator — rejected.** Next already runs server-side; a Route
   Handler would add another request, another partial-failure contract, and no
   useful isolation for this project.

## 6. Current-state reuse and replacement

Phase 5 preserves:

- Next.js App Router and TypeScript strict mode;
- Tailwind CSS and Phase 2 design tokens;
- `next-intl` locale routing and message catalogs;
- the current `/` locale resolution order: explicit locale cookie, then
  `Accept-Language`, then Spanish;
- `/es` and `/en` as route authority;
- the pre-paint theme bootstrap and existing theme storage contract;
- the generic API transport/error foundation where compatible;
- Vitest and Testing Library;
- the current Caddy ownership of public paths.

Phase 5 replaces the technical foundation page with the public composition. The
existing `ApiStatus` is a Phase 3 diagnostic placeholder and is not part of the
final public site. Phase 2 prototype HTML, JavaScript, and image derivatives are
reference artifacts, not runtime code or fallback content.

The Next application is not reinitialized or rewritten from scratch. Existing
patterns are extended and focused.

## 7. App Router and component ownership

The localized route owns structural composition:

```text
app/[locale]/layout.tsx
app/[locale]/page.tsx
app/[locale]/loading.tsx
app/[locale]/error.tsx
app/[locale]/not-found.tsx
app/global-error.tsx
```

Exact placement may adapt to the installed Next.js version, but responsibilities
must not move between layers.

Server Components are the default for:

- page composition;
- shell and structural layout;
- Hero and presentation;
- Work Cases base content and Experience;
- Specialties and Technologies;
- Projects and Work Principles;
- Contact and footer;
- empty and expected failure wrappers that do not themselves need browser state.

Client Components are limited to:

- the existing theme interaction/provider where required;
- language switching with allowlisted fragment preservation;
- the mobile native-dialog coordinator;
- Indexed Detail desktop enhancement;
- `RetryButton`;
- the narrowly scoped `FragmentFocusManager`;
- `error.tsx`, because App Router requires error boundaries to be Client
  Components.
- `global-error.tsx`, because the root localized layout also needs a parent/root
  fallback and Next requires global error UI to be a Client Component.

A Server Component section does not become a Client Component merely because it
contains one of these islands.

Suggested ownership boundaries for planning are:

```text
src/lib/api/                 transport, public types, validators, fetchers, loader
src/i18n/                    locale routing, messages, fragment/anchor contract
src/components/layout/      header, navigation, dialog shell, footer
src/components/sections/    server-rendered portfolio sections and groups
src/components/ui/          focused controls and state surfaces
src/app/[locale]/            route composition and App Router boundaries
```

The implementation plan may refine filenames, but it must preserve these
responsibility boundaries.

## 8. Locale routing, navigation, and anchors

Route locale is the source of truth for rendered language. The preference cookie
helps choose future routes but never overrides an explicit `/es` or `/en` URL.

The main navigation contains exactly five localized labels backed by stable,
non-translated IDs:

```text
#work
#expertise
#projects
#approach
#contact
```

Their order is Work, Expertise, Projects, Approach, Contact on desktop, mobile,
and no-JavaScript fallback.

The cross-locale-preserved and focus-managed anchor allowlist is:

```text
#work
#expertise
#projects
#approach
#contact
#about
#work-cases
#experience
#specialties
#technologies
#work-principles
```

`#top` is a valid local anchor used by the header identity, but it is
deliberately outside that allowlist. It needs no cross-locale preservation:
switching locale from `#top` navigates to the other locale without a fragment,
which already lands at the top. Local `#top` navigation uses native browser
positioning and is not managed by `FragmentFocusManager`.

Grouped structure and narrative order are:

```text
#work
  #work-cases
  #experience

#expertise
  #specialties
  #technologies

#projects

#approach
  #work-principles

#contact

additional: #about
```

Work Cases always precede Experience. A granular anchor exists when its content
or its regional error is rendered. It does not exist for a successful empty
subsection that is omitted.

The language switch changes the locale route and preserves the current hash only
when it is in the shared allowlist. It preserves no exact scroll coordinate. If
the hash is absent or unknown, the other locale opens at its top. The switch
uses normal navigation so Server Components reacquire that locale from Laravel,
and it persists the explicit preference through the existing cookie mechanism.

No section-name translation map, scroll-spy, IntersectionObserver, automatic
hash update, custom history, or scroll-coordinate management is introduced.

## 9. Theme ownership

The existing theme contract remains authoritative:

- stored explicit preference when available;
- otherwise system preference/base CSS behavior;
- early document theme application to avoid a visible flash;
- no CMS field, route segment, or server cookie as a competing theme authority;
- no `next-themes` dependency.

The control remains an accessible real button in the header. With JavaScript
disabled, professional content remains available and the CSS/system base theme
remains usable; only interactive theme changes are lost.

## 10. Public API contract consumed by Next

All routes are localized Phase 4 routes:

```text
GET /api/v1/{locale}/profile
GET /api/v1/{locale}/site
GET /api/v1/{locale}/experiences
GET /api/v1/{locale}/work-cases
GET /api/v1/{locale}/projects
GET /api/v1/{locale}/technologies
```

Success uses `{ "data": ... }`. Collections use arrays and return HTTP 200 with
`data: []` when no public records exist. Missing/nonpublic Profile or Site uses
the controlled Phase 4 `not_found` 404. Every non-2xx response is an endpoint
failure for Phase 5; frontend UI does not display the API message or details.

The exact valid public resource shapes are:

```ts
type MediaWithAlt = { url: string; alt: string };
type MediaIcon = { url: string };

type Technology = {
  key: string;
  name: string;
  category: "backend" | "data" | "integration" | "collaboration";
  icon: MediaIcon | null;
};

type Profile = {
  name: string;
  headline: string;
  short_summary: string;
  introduction: string;
  availability: string;
  cta: string;
  photo: MediaWithAlt | null;
};

type Experience = {
  key: string;
  organization: string | null;
  role: string;
  start: string;       // YYYY-MM
  end: string | null;  // YYYY-MM; null means the experience is current
  summary: string;
  highlights: string[];
  technologies: Technology[];
};

type WorkCase = {
  key: string;
  title: string;
  context: string;
  problem: string;
  contribution: string;
  technical_approach: string;
  outcome: string;
  technologies: Technology[];
};

type Project = {
  key: string;
  title: string;
  summary: string;
  problem: string;
  solution: string;
  featured: boolean;
  image: MediaWithAlt | null;
  demo_url: string | null;
  repository_url: string | null;
  technologies: Technology[];
};

type SiteConfiguration = {
  projects_empty_message: string;
  contact_intro: string;
  technology_groups: Array<{
    key: "backend" | "data" | "integration" | "collaboration";
    label: string;
  }>;
  professional_links: Array<{
    key: "linkedin" | "github" | "email";
    label: string;
    href: string;
  }>;
  expertise_areas: Array<{
    key: string;
    title: string;
    description: string | null;
  }>;
  work_principles: Array<{
    key: string;
    statement: string;
  }>;
  cv: { url: string; label: string } | null;
};
```

These types describe valid public resources after Phase 4 publication
validation. For example, work-case and project narrative columns may be nullable
while draft, but all listed narrative strings are required before the record can
be publicly served. Profile `headline` and `short_summary` are required strings,
so metadata needs no nullable fallback. Nullable fields above reflect the actual
public contract.

Media paths may be root-relative public references such as `/storage/...`.
`site.cv.url` is the locale-specific stable relative route. HTTP professional
links are HTTPS in the Phase 4 contract; email is exposed as `mailto:`.

## 11. Runtime types and validation

Every HTTP body begins as `unknown`. Conversion is:

```text
unknown
-> success-envelope validation
-> endpoint-specific resource validation
-> typed resource
```

There are no unchecked `as Profile`, `as SiteConfiguration`, or equivalent JSON
casts.

Validators must check:

- required object membership;
- arrays and every array element;
- strings, numbers, and booleans as applicable;
- actual nullable fields;
- relevant nested media, links, CV, technology, and date structures;
- technology and link closed values;
- `YYYY-MM` structure and month range for experience dates;
- structurally valid HTTPS, `mailto:`, root-relative storage, and CV references
  according to the field's contract.

`site.technology_groups` must contain the four unique canonical keys in the
Phase 4 order `backend`, `data`, `integration`, `collaboration`, each with a
string label. Exact membership and order are formally part of
`docs/api/PUBLIC_API_V1.md`: `SiteResource` emits `TechnologyCategory::cases()`
directly and its contract guarantees exactly four entries in declaration order.
Rejecting another order therefore validates the HTTP contract; it does not
duplicate publication logic, sort the response, or reclassify Technologies in
Next.

Additional unconsumed backend fields are tolerated. Missing or malformed
consumed fields fail the endpoint. Validators check the documented HTTP shape;
they do not decide whether Laravel should publish a record, calculate positions,
sort content, or infer a different order.

Small composable guards for records, arrays, nullable values, and primitives are
allowed only where they reduce duplication. They must not become a generic
schema DSL.

A malformed Profile/Site payload produces structural failure. A malformed
collection payload, including one bad item inside an otherwise valid array,
produces that collection's regional failure. `[]` remains success.

## 12. Transport, timeout, and endpoint results

Shared server-only transport owns:

- the internal API base URL;
- localized URL construction;
- GET execution;
- success-envelope parsing;
- timeout/abort handling;
- normalized known operational errors.

Every request uses the installed Next.js equivalent of:

```ts
fetch(url, {
  cache: "no-store",
  signal: AbortSignal.timeout(8_000),
});
```

An `AbortController` equivalent is acceptable where required. Eight seconds is
a defensive ceiling, not an expected response time. Normal internal Laravel
requests should resolve much sooner. Because calls are parallel, the ceiling is
not multiplied by six.

Timeout aborts are normalized specifically as `network` failures. Known network,
HTTP, configuration, and malformed-response conditions become a small result:

```ts
type EndpointFailureKind =
  | "configuration"
  | "network"
  | "http"
  | "malformed";

type EndpointResult<T> =
  | { ok: true; data: T }
  | { ok: false; failure: EndpointFailure };
```

`EndpointFailure` may preserve the logical endpoint, kind, and HTTP status for
server diagnostics. It must not preserve arbitrary response bodies or expose
internal origins to public components. No retry, backoff, endpoint-specific
timeout, or circuit breaker is added.

Transport normalizes expected failures, not arbitrary programming exceptions.
Unexpected defects must be allowed to reach the App Router error boundary rather
than being mislabeled as recoverable content absence.

## 13. Coordinated loader and request-scoped memoization

Focused server-only fetchers are:

```text
fetchProfile
fetchSite
fetchExperiences
fetchWorkCases
fetchProjects
fetchTechnologies
```

They share only transport, envelope, common guards, and error normalization.
They do not contain presentation policy.

`loadPublicPortfolioUncached(locale)` starts all six independent operations
together and preserves the result of each. A single known failure must not reject
or discard healthy endpoint results. The exact Promise primitive may be
`Promise.all` over non-rejecting known-result fetchers or an equivalent explicit
settlement mapping; sequential requests are prohibited.

The exported loader is explicitly memoized for the current render/request:

```ts
const loadPublicPortfolio = cache(loadPublicPortfolioUncached);
```

The installed React/Next equivalent is acceptable only if it has the same
request-scoped lifetime.

Contract:

```text
same locale + same render/request
-> one coordinated acquisition shared by generateMetadata and page

next request
-> six new no-store requests to Laravel
```

`unstable_cache`, persistent Data Cache, a module-global result map, or reliance
on incidental `fetch` deduplication is prohibited.

## 14. Cache and rendering mode

Laravel is the only temporal cache authority:

```text
Filament change/publication
-> Laravel cache invalidation
-> next navigation/request reaches Next
-> six no-store reads
-> Laravel returns its current cached representation
```

Next adds no stale window, ISR, revalidation interval, cache tag, webhook,
secret, `revalidatePath`, or `revalidateTag` endpoint. Client islands add no
resource cache.

Localized portfolio routes render dynamically at request time. Static locale
enumeration may remain only if it does not execute the public loader during
build. A production build must succeed while Laravel is unavailable.

## 15. Failure policy and logging

The page, not the loader, applies criticality.

### 15.1 Structural failure

If Profile or Site fails:

- render the document and global technical shell;
- preserve locale and theme behavior;
- preserve language and theme controls;
- show the localized general error and `Retry`;
- do not render professional sections from partial collection data;
- do not render hardcoded professional or contact fallback content;
- omit the five professional anchor links because their landmarks do not exist.

A valid Profile may still provide metadata when Site fails.

### 15.2 Regional failure

Experiences, Work Cases, Projects, and Technologies fail independently. The rest
of a structurally valid page continues rendering. The affected region shows
localized safe text and `Retry` and keeps its granular anchor because the
semantic region exists.

Errors rendered in initial SSR HTML use an identifiable semantic region,
heading/text, and accessible button. They do not receive `role="alert"` by
default. `role="status"` or `aria-live="polite"` is used only for a real dynamic
update that benefits from announcement. `role="alert"` is reserved for a
genuinely urgent condition; Phase 5 defines no automatic use for ordinary
endpoint failures.

### 15.3 Diagnostics

Visible text never includes status codes, endpoints, exception messages, stack
traces, SQL, internal paths, or private hosts. Sanitized server logging may
record logical endpoint, locale, failure kind, and status. It must not record
secrets, arbitrary payloads, or private origin values.

## 16. Empty-state contract

Exact UI copy is:

| State | Spanish | English |
|---|---|---|
| Neutral empty group | `Contenido no disponible por el momento.` | `Content is not available at the moment.` |
| Regional failure | `No pudimos cargar esta sección.` | `We couldn't load this section.` |
| Structural failure | `No pudimos cargar el contenido del portfolio.` | `We couldn't load the portfolio content.` |
| Retry | `Reintentar` | `Retry` |
| Pending retry, if used | `Reintentando…` | `Retrying…` |

Rules:

- success with items renders normally;
- a successful empty subsection is omitted, including its heading and granular
  anchor;
- when every successful subsection in a main group is empty, the main anchor
  remains and shows the neutral copy;
- a failed subsection shows failure, never neutral empty copy;
- empty states are ordinary content and have no live-region role;
- navigation does not change because collections are empty or regionally
  failed.

Per group:

- Work combines Work Cases then Experiences;
- Expertise combines `site.expertise_areas` then Technologies;
- Projects is the explicit exception: `projects: []` uses only
  `site.projects_empty_message`;
- Approach uses `site.work_principles`;
- Contact is valid if its presentable intro, links, or CV provides content. An
  intro alone renders normally. Neutral copy appears only when all presentable
  contact content is legitimately empty/absent.

## 17. Loading, unexpected errors, and not found

`loading.tsx` provides one coordinated route shell with stable header/main/footer
geometry and neutral placeholders. It contains no invented name, role,
experience, technology, project, or contact claim. There is no Suspense boundary
per endpoint and no six-stage streaming composition.

Expected API failures are values handled by page composition, not exceptions
sent to `error.tsx`.

`app/[locale]/error.tsx` is the App Router safety net for unexpected exceptions
in the localized page/subtree below the localized layout. It is the documented
technical exception to Server Components by default, uses safe localized copy,
exposes no technical detail, and provides framework-compatible recovery. It
must not become the normal endpoint failure model.

An error boundary does not catch an exception thrown by the layout in its own
route segment because the boundary is nested inside that layout. In the current
application, `app/[locale]/layout.tsx` is the root layout. Therefore Phase 5 also
requires the minimum `app/global-error.tsx` prescribed by Next.js for unexpected
root/localized-layout failure. It is a Client Component, defines its own
`<html>` and `<body>`, uses a self-contained safe technical fallback because the
locale provider/theme shell may not exist, and provides the version-appropriate
reset/retry control. It contains no professional content, data acquisition, or
observability system. This follows the installed App Router boundary model
documented by Next.js in `https://nextjs.org/docs/app/getting-started/error-handling`.

The Phase 5 404 uses the visual shell and technical localized copy, links back
to a valid localized page, and contains no professional fallback. A valid `es`
or `en` segment selects its copy; an unsupported/missing locale uses a safe
technical default. SEO/indexation treatment for 404 remains Phase 8.

## 18. Retry behavior

Expected structural and regional failure surfaces include a small reusable
Client Component `RetryButton`.

It knows only its localized labels and calls:

```ts
router.refresh();
```

The refresh preserves current locale, pathname, query string, and fragment and
causes the server route to reacquire all six no-store endpoints. It does not
perform resource-specific client fetching. A small pending/disabled state is
allowed where the installed router primitives support it cleanly; no retry state
machine is introduced.

Retry is explicit and manual. There is no timer, polling, infinite retry, or
silent client backoff. Without JavaScript, the user can refresh the browser.

Unexpected `error.tsx` recovery may use the App Router boundary's required
`reset()` semantics; this does not change the `router.refresh()` contract for
expected content failures.

## 19. Shell and navigation components

The localized layout provides correct document language, UI catalog, theme
bootstrap, skip link, and shell. Header identity on a structurally valid page is
derived from Profile and links to `#top`.

At `64rem` and above, the sticky desktop header renders the five shared numbered
destinations, language control, and theme control. Phase 5 does not implement
active-section scroll tracking.

Below `64rem`, the header keeps identity, theme, and a real Menu button. Language
selection moves inside the native `<dialog>`.

The mobile Client Component coordinates only:

- `dialog.showModal()`;
- open/close state;
- an accessible dialog name;
- initial focus inside the menu;
- native Escape cancellation;
- explicit Close;
- closure on destination or locale selection;
- restoration of focus to the trigger;
- document scroll lock/restoration if required by verified supported-browser
  behavior;
- cleanup on unmount.

The dialog owns a local `(min-width: 64rem)` media-query listener. If an open
dialog crosses into desktop mode, it closes, removes its scroll lock, and does
not leave focus inside closed/hidden content. Because the mobile trigger becomes
hidden, focus moves to a sensible visible header target such as the identity
link rather than being restored to a hidden trigger. Returning below `64rem`
starts with the dialog closed. This state is local to the dialog; there is no
global breakpoint store.

It does not implement a manual focus trap or manual `inert` unless a verified
supported-browser defect requires a separately approved scope change. No Radix,
Headless UI, or dialog dependency is added.

The no-JavaScript mobile fallback is a semantic named `<nav>` within
`<noscript>`, containing normal anchors for the same five destination definitions
and a real link to the other locale. It is not a second dialog or navigation
configuration, and it has no separate labels or URLs.

With JavaScript disabled, controls whose behavior requires hydration must not be
visibly exposed or remain in the accessibility tree as dead controls. In
particular, the mobile Menu trigger and interactive theme toggle are hidden or
otherwise not exposed, while native desktop anchors, the other-locale link, and
the mobile `<noscript>` navigation remain functional. The implementation plan
chooses the smallest mechanism compatible with the existing theme bootstrap; the
specification requires behavior, not a second no-JavaScript application.

The footer consumes only available CMS identity/link data plus technical UI copy.
It contains no duplicated biography or hardcoded professional URL.

The following localized UI catalog entries are fixed for Phase 5 rather than
being CMS professional content. Primary navigation, section, and Menu/Close
labels come from Phase 2. Date/field/new-tab labels are Phase 5 technical copy
submitted for approval in this specification, as recorded in section 33:

| Purpose | Spanish | English |
|---|---|---|
| Primary navigation | `Navegación principal` | `Primary navigation` |
| Work destination | `Trabajo` | `Work` |
| Expertise destination | `Especialización` | `Expertise` |
| Projects destination | `Proyectos` | `Projects` |
| Approach destination | `Forma de trabajo` | `Approach` |
| Contact destination | `Contacto` | `Contact` |
| Mobile menu title | `Navegación` | `Navigation` |
| Open mobile menu | `Menú` | `Menu` |
| Close mobile menu | `Cerrar` | `Close` |
| About heading | `Presentación profesional` | `Professional introduction` |
| Work Cases heading | `Casos de trabajo` | `Work cases` |
| Experience heading | `Experiencia` | `Experience` |
| Specialties heading | `Áreas de especialización` | `Areas of specialization` |
| Technologies heading | `Tecnologías` | `Technologies` |
| Projects heading | `Proyectos` | `Projects` |
| Approach heading | `Forma de trabajo` | `Working approach` |
| Contact heading | `Contacto y CV` | `Contact and CV` |
| Current experience end | `Actualidad` | `Present` |
| Context field | `Contexto` | `Context` |
| Problem field | `Problema` | `Problem` |
| Contribution field | `Contribución` | `Contribution` |
| Technical approach field | `Enfoque técnico` | `Technical approach` |
| Outcome field | `Resultado` | `Outcome` |
| Solution field | `Solución` | `Solution` |
| Opens-new-tab suffix | `Abre en una nueva pestaña` | `Opens in a new tab` |

The basic 404 copy is also fixed:

| Purpose | Spanish | English |
|---|---|---|
| Title | `Página no encontrada` | `Page not found` |
| Explanation | `La página solicitada no existe.` | `The requested page does not exist.` |
| Return action | `Volver al portfolio` | `Back to portfolio` |

Existing approved theme and language-control accessible labels remain owned by
their current catalogs; Phase 5 must not create competing copies.

## 20. Section composition

The logical server composition is:

```text
LocalizedPage
├── HeroSection                  #top
├── AboutSection                 #about
├── WorkGroup                    #work
│   ├── WorkCasesSection         #work-cases
│   └── ExperienceSection        #experience
├── ExpertiseGroup               #expertise
│   ├── SpecialtiesSection       #specialties
│   └── TechnologiesSection      #technologies
├── ProjectsSection              #projects
├── ApproachSection              #approach
│   └── WorkPrinciples           #work-principles
└── ContactSection               #contact
```

Components receive public resources or genuine subsets; they never fetch.
Transformations such as grouping Technologies by CMS-provided group order,
formatting dates, or choosing a media/no-media variant remain local to the
consumer and do not justify a global view model.

Laravel ordering is preserved. Next does not sort records again. Headings and
field labels such as Context/Problem are localized UI copy. Professional values
come from the API.

Every primary group has a stable accessible name even when an optional
subsection is omitted. Work and Expertise may use a visually hidden group `h2`
from the primary navigation label with their rendered granular subsections as
`h3`; this preserves hierarchy without adding a duplicate visual heading.
Projects, Approach, Contact, and About use their sensible visible headings.
`aria-labelledby` must never reference a heading removed by an empty-state
decision.

## 21. Hero, Profile, and photography

Hero consumes `profile.name`, `headline`, `short_summary`, `availability`, `cta`,
and `photo`. Its exact content hierarchy is:

```text
h1                 = profile.name
prominent headline = profile.headline
summary             = profile.short_summary
availability        = profile.availability
CTA label           = profile.cta
```

No additional professional eyebrow or positioning claim is added. The CTA is a
real anchor:

```text
label = profile.cta
href  = #work
```

The CTA does not point to `#contact` or `#experience`. It targets the always
present start of the approved Work evidence sequence.

`#about` renders `profile.introduction` separately and does not duplicate the
short summary.

Photo behavior:

```text
valid Profile + photo
-> intentional text/photo composition

valid Profile + photo null
-> intentional text-only composition with no reserved gap

invalid/failed Profile
-> structural failure
```

No prototype photo, avatar, initials, generic placeholder, local fallback, or
broken-image substitute is allowed. Runtime nullability remains distinct from
the launch/editorial requirement to publish the approved photo.

## 22. Work, Expertise, Projects, and Approach

Work Cases render all validated fields, omitting no required public content.
Their field labels use the UI catalog in section 19. Technology arrays may be
empty. All cases exist in SSR HTML in Laravel order.

Indexed Detail is progressive enhancement:

- without JavaScript, all dossiers are sequential and readable;
- below `64rem`, all dossiers remain sequential;
- at `64rem` and above after hydration, multiple cases become accessible
  vertical tabs;
- one case remains a simple dossier;
- zero cases follows the group-empty policy.

The desktop widget uses correct `tablist`, `tab`, `tabpanel`, `aria-selected`,
`aria-controls`, and `aria-labelledby` relationships; roving tab focus; Up/Down
and Home/End; and Enter/Space where activation is not automatic. Tab enters and
leaves the widget without visiting every tab. No content is fetched client-side
or absent from initial HTML.

Indexed Detail owns its local `(min-width: 64rem)` reconciliation. Crossing
below `64rem` removes desktop-only tab semantics/state from the rendered
interaction, unhides every dossier, and restores sequential reading. Crossing
back to desktop reapplies the accessible tab mode using its local selected item
or the first item when no valid selection exists. It shares no global breakpoint
state with the mobile dialog or any other component.

Experience renders organization when non-null, role, localized `YYYY-MM` dates,
summary, highlights, and Technologies. `end: null` receives localized
Present/Actualidad UI copy. No date or organization is inferred.

Expertise Areas come from Site; nullable descriptions are omitted cleanly.
Technologies are grouped using `site.technology_groups` order and labels. The
frontend does not invent categories or labels. Missing decorative icons do not
remove textual names.

Projects preserve Laravel order. `featured` selects presentation emphasis only
and never reorders. Nullable image/demo/repository fields omit their respective
surface. No GitHub repository is inferred to be a project.

Approach renders `site.work_principles` in API order and follows the neutral group
rule when empty.

## 23. Contact and CV

Contact consumes only `site.contact_intro`, `professional_links`, and `cv`.
HTTP(S) links are not assigned new-tab behavior merely because they are
external. The Phase 2 contact prototype specifically approved LinkedIn and
GitHub opening in a new tab; those two known link keys retain that behavior,
receive the localized accessible suffix above, and use safe `rel` values. Email
and CV remain normal anchors. Any future external link defaults to normal
same-context navigation until its interaction is explicitly approved.

CV behavior is:

```text
site.cv object -> real anchor using cv.url and cv.label
site.cv null   -> omit the CV action without error or placeholder
```

There is no hardcoded locale CV path, client PDF fetch, other-locale fallback,
or copy of the approved PDF under `web/`. Laravel/Caddy own the stable download
route and response.

## 24. Fragment focus enhancement

`FragmentFocusManager` is a minimal Client Component using only the shared
allowlist in section 8. If the allowlisted target exists, it may improve focus
after initial hydration with a hash, `hashchange`, internal navigation, or locale
switch preserving the hash.

Targets may use `tabIndex="-1"` so programmatic focus does not add a Tab stop.
The browser owns fragment positioning. The manager does not calculate offsets,
call coordinate-based `scrollTo`, implement scrolling, observe sections, update
history, or alter hashes on scroll.

`focus({ preventScroll: true })` must be verified with the installed App Router
and real browsers. If it causes double positioning or jumps, correct native
fragment navigation takes priority over forced focus enhancement. Without
JavaScript, native anchor scrolling continues and only enhanced focus is lost.

## 25. Responsive contract

Phase 2 breakpoints remain exact:

| Range | Required composition |
|---|---|
| `<48rem` | 4 columns, 20px gutters, single reading flow, stacked Projects |
| `48rem–63.99rem` | 8 columns, 32px gutters, horizontal Projects where content fits, mobile header |
| `64rem–79.99rem` | 12 columns, 48px gutters, desktop header, split Hero, Indexed Detail |
| `80rem–89.99rem` | 12 columns, 64px gutters |
| `>=90rem` | centered primary content capped at 90rem |

Acceptance applies independently to ES and EN:

- no page-level horizontal scroll, clipping, overlap, or hidden actions at 320px;
- the same guarantees at 200% browser zoom;
- normal body measure around 60–70ch;
- content wraps/recomposes rather than truncates;
- relevant controls provide at least 44 x 44 CSS pixels;
- Hero, loading, errors, no-photo, no-CV, and empty states reflow correctly;
- direct fragment targets account for the sticky header using CSS scroll margin.

## 26. Accessibility and reduced motion

Phase 5 requires:

- one `h1` and logical heading hierarchy;
- semantic document, header, navigation, main, section, and footer landmarks;
- labelled sections and dialog;
- a keyboard-visible skip link;
- visible focus in both themes;
- WCAG AA contrast for text and meaningful visual boundaries;
- information and state not communicated by color alone;
- accessible names for every action;
- CMS alt text for meaningful Profile/Project images;
- decorative icon treatment when adjacent text already names the Technology;
- full keyboard operation for dialog and tabs;
- readable DOM/source order matching narrative order;
- no dependency on hover or animation;
- browser zoom and screen-reader QA.

With `prefers-reduced-motion`, smooth scrolling and nonessential transitions are
reduced or removed. Functionality, focus, fragment behavior, selected Work state,
loading, and failures remain understandable. Phase 5 adds no motion library or
motion infrastructure.

The main professional content must remain available without JavaScript. The
documented degradations are:

- Indexed Detail -> sequential dossiers;
- language switch -> functional other-locale link, possibly without hash
  preservation;
- Retry -> manual browser refresh;
- theme -> usable CSS/system base theme without interactive control;
- mobile menu -> simple `<noscript>` navigation;
- fragment focus -> native fragment positioning without focus enhancement.

## 27. Images and public-media topology

Profile photo and Project image use `next/image` when verified compatible with
the real public topology. Their layouts use stable aspect-ratio containers,
`fill` or stable dimensions, responsive `sizes`, and CSS crops. The Hero image
may be prioritized; noncritical images remain lazy by default.

Phase 4 references are preserved:

- a root-relative `/storage/...` remains root-relative when it works;
- `API_INTERNAL_URL`, Docker DNS, and private Laravel hostnames never reach HTML;
- an absolute URL is allowed only when it is the approved public origin;
- `remotePatterns`, if required, permits only that narrow public origin.

Before fixing the production implementation, Phase 5 must verify:

```text
Compose + Caddy + Next image optimizer + Laravel /storage
```

Evidence must record the API value, emitted HTML, optimizer request/response,
content type, visible result, and absence of internal origins. Preference order:

1. `next/image` plus the Phase 4 public reference;
2. an approved public absolute origin if topology requires it;
3. stop and report a concrete incompatibility before changing contracts or
   creating a proxy.

Phase 5 must not preemptively add a media Route Handler, proxy, local copy,
internal hostname exposure, or broad remote pattern.

Technology icons remain supplementary to visible names. Nullable icon/image
fields render no empty broken-media frame.

## 28. Basic metadata boundary

`generateMetadata()` consumes the same validated, request-scoped loader result.

For valid Profile:

```text
title       = `${profile.name} — ${profile.headline}`
description = profile.short_summary
```

Profile headline and short summary are mandatory strings in the valid Phase 4
public contract; there is no nullable separator or description fallback.

For failed/malformed Profile:

```text
ES title = Portfolio no disponible
EN title = Portfolio unavailable
description omitted
```

Site failure does not suppress valid Profile metadata. Metadata failure does not
create an additional visual error beyond the established page composition.

Canonical, alternates, social metadata, structured data, sitemap, robots,
analytics, and indexation remain Phase 8.

## 29. Exact production import contract

`PortfolioContentSeeder` remains the repeatable development/editorial seeder. It
is not renamed or repurposed.

Phase 5 adds an explicit one-time command with the public contract:

```text
php artisan portfolio:import-initial-content
```

The implementation may delegate mechanical inserts to a purpose-named class,
but the operator-facing command and its safety behavior remain explicit.

### 29.1 Deterministic dataset

Approved Markdown is reviewed by humans and represented as an explicit,
structured `InitialPortfolioContent` dataset in code. The command consumes that
dataset; it never parses documents at runtime.

Editorial authorities are:

- `docs/content/CONTENT.es.md`;
- `docs/content/CONTENT.en.md`;
- `docs/content/CONFIDENTIALITY_MATRIX.md`;
- `docs/content/ASSET_INVENTORY.md`;
- later explicit documented approvals.

The dataset is a deterministic import representation, not a second editorial
authority. There is no Markdown scraping, heading inference, CV claim extraction,
NLP, translation, or docs synchronization.

There is one code-level representation of the approved initial values. The
repeatable development seeder may consume the non-asset slice of that same
dataset after refactoring, while preserving its existing behavior. Production
and development seeders must not retain separate copies of the bilingual
professional text that can drift independently.

Current approved asset inputs are exactly:

```text
docs/content/approved-assets/professional-photo.jpg
docs/content/approved-assets/cv-es.pdf
docs/content/approved-assets/cv-en.pdf
```

### 29.2 Exact empty-editorial-baseline set

The Phase 4 migration deliberately creates one structural singleton row in
`profiles` and one in `site_configurations`. The import therefore requires the
following exact empty **editorial** baseline; it does not require those two
tables to be literally rowless:

```text
profiles
site_configurations
experiences
experience_highlights
work_cases
projects
technologies
expertise_areas
work_principles
professional_links
cv_documents
experience_technology
technology_work_case
project_technology
```

This is the complete managed content graph represented by the initial dataset,
including Experience and Projects collections whose approved initial value is
empty and their possible relationship rows. `users`, authentication/session
tables, framework tables, migrations, cache, jobs, and unrelated operational
records are explicitly outside the baseline check.

`profiles` and `site_configurations` must each contain exactly the structural
`singleton_key = "default"` row created by Phase 4, and no second row. Each row
counts as editorially pristine only when every real Phase 4 professional,
localized, and asset/media column is null or otherwise at its migration default,
with `status = draft`, `is_visible = false`, and `published_at = null`. In
particular, no name, localized copy, Technology-group label, private/public asset
path, MIME, size, alt text, or other editorial value may already be present.
The check is explicit against the actual Profile and SiteConfiguration schema;
it is not a generic three-column publication-state check. The importer fills
these existing structural rows and never creates replacement singleton rows.

The other twelve listed tables must be literally empty. A non-pristine
structural singleton, an additional singleton row, or any row in any of those
twelve tables causes a clear failure before mutation. Rejection performs zero
database and zero filesystem mutation. There is no `--force-overwrite`,
`--sync`, `--reset`, update mode, environment-dependent branch, or partial
import.

### 29.3 Preflight and mutation

Before any mutation, preflight verifies:

- the exact baseline above;
- dataset shape and required stable-key uniqueness;
- category and relationship references;
- source asset existence;
- allowed MIME and size through Phase 4 rules;
- both PDF files and locale/language metadata;
- the approved photograph format and validity;
- every condition that existing services can validate without writing.

Only after preflight succeeds do DB writes begin in a transaction.

Every created publishable entity uses, without exception:

```text
status = draft
is_visible = false
published_at = null
```

Imported draft photo/CV files remain in the Phase 4 private ownership state.
Profile `photo_public_path` remains null and the CV download route remains
unavailable until the corresponding normal publication action succeeds.

The command creates no users/admins and publishes nothing. It never invokes a
Filament publication action internally. It is not called by migrations,
`DatabaseSeeder`, startup, or deployment automation.

### 29.4 Filesystem compensation

SQL rollback does not roll back filesystem/storage. Prefer validating and
preparing every asset before mutation when existing Phase 4 services permit it.

If an import attempt creates storage files, the command tracks the exact files
newly created by that attempt. On failure it rolls back DB state and deletes only
those newly created files. It never deletes a preexisting file during
compensation. This is a small command-specific compensation, not a generic media
transaction abstraction.

The second execution fails during baseline preflight and performs no DB or
filesystem mutation, including after an administrator has published or edited
content.

### 29.5 Known editorial gaps and specification self-review finding

The current approved Experience prose does not supply the organization label,
role, and required start month/year needed for an Experience record. Therefore:

```text
experiences = []
```

The command must not infer those values from CVs, LinkedIn, commits, known work
history, or neighboring documents.

Projects is deliberately:

```text
projects = []
```

until Phase 7.

The exact bilingual Technology group labels are not currently approved. The
dataset leaves those SiteConfiguration fields in the draft state permitted by
Phase 4. It does not invent, automatically translate, or provision temporary
copy. Phase 4 correctly prevents Site publication until an administrator enters
and approves required labels in Filament.

The following Work Case issue is a **new editorial gap discovered during the
specification self-review**. It was not one of the two gaps explicitly approved
during brainstorming and must not be represented as a prior human content
decision.

Concrete evidence:

- `docs/content/CONTENT.es.md` and `CONTENT.en.md` contain a heading plus one
  approved descriptive paragraph for each of the four Work Cases; they do not
  contain separately approved Problem, Contribution, Technical Approach, and
  Outcome copy;
- `api/database/seeders/PortfolioContentSeeder.php` documents that mismatch and
  currently writes the approved paragraph to `context_es/en` while leaving
  `problem`, `contribution`, `technical_approach`, and `outcome` null;
- `App\Domain\Publishing\PublicationValidator::workCaseIssues()` requires
  bilingual `title`, `context`, `problem`, `contribution`,
  `technical_approach`, and `outcome` before publication;
- `App\Http\Resources\Api\V1\WorkCaseResource` exposes those six fields to the
  public API.

The gap is therefore fully supported by the current Phase 1 sources and Phase 4
implementation. The initial dataset imports the approved title/context values
and leaves the remaining draft fields null. It does not split or paraphrase the
paragraph to manufacture the missing structure. Filament correctly prevents
each Work Case from being published until those exact fields receive
human-approved bilingual content. Until then, this is an editorial acceptance
blocker under section 3.1, not an implementation defect and not permission to
close Phase 5 with neutral Work content.

The initial editorial procedure is:

```text
run guarded import
-> content/assets exist as draft + hidden
-> review in Filament
-> complete only explicitly approved missing fields
-> review photo and both CVs
-> publish through normal Phase 4 actions
-> Phase 4 validates and invalidates cache
-> public API begins serving content
```

It is acceptable runtime behavior for the public site to show structural
unavailability during the interval before Profile and Site are published. No
Next fallback masks that interval. That interim state does not satisfy Phase 5
editorial acceptance or authorize closing its ROADMAP criteria; section 3.1
remains controlling.

## 30. Automated testing contract

### 30.1 Frontend

Vitest and Testing Library cover, at minimum:

- each runtime validator: valid payload, missing required field, wrong type,
  valid nullable, valid empty array, malformed collection item;
- success-envelope and malformed JSON/body behavior;
- all six endpoint fetchers and safe error normalization;
- eight-second timeout abort classified as `network` and no automatic retry;
- `cache: "no-store"`;
- six requests starting in parallel;
- one failed/slow collection not changing healthy endpoint semantics;
- request-scoped metadata/page deduplication and fresh work on a later request;
- all success, Profile failure, Site failure, every individual collection
  failure, simultaneous collection failures, empty collections, and malformed
  resources;
- structural, regional, and exact empty-state composition;
- stable five-item navigation across content, empty, and regional failures;
- conditional granular anchors;
- ES/EN rendering and allowlisted fragment preservation;
- no special preservation for unknown hashes;
- loading shell and localized 404;
- Hero with photo and `photo: null`, with no local fallback;
- Work Case SSR completeness, 0/1/multiple behavior, tabs, keyboard, and ARIA
  relationships;
- mobile-dialog observable DOM behavior: accessible trigger/name, open, explicit
  close, Escape event handling where jsdom can represent it, navigation close,
  locale close, and focus restoration;
- local `64rem` transitions: an open dialog closes and releases its state on
  desktop entry, while Indexed Detail removes tab mode and reveals every dossier
  on narrow entry;
- desktop/mobile using the same navigation definition;
- `<noscript>` fallback in server HTML and absence of exposed dead Menu/theme
  controls in the no-JavaScript contract;
- `RetryButton` visibility, labels, click-to-refresh, and absence from success or
  empty states;
- theme behavior already supported by the test environment;
- Profile/Project nullable media and Site nullable CV;
- Contact with intro only;
- CV URL/label consumption without hardcoded routes;
- metadata for ES, EN, Profile failure, and Site-only failure;
- heading and landmark relationships observable in rendered markup;
- localized subtree error UI and the minimal root-layout `global-error` fallback;
- no hydration mismatch/error warning or uncaught client exception in the
  automated ES/EN, theme, Indexed Detail, and responsive-island scenarios that
  the test environment can exercise.

Tests must not claim to prove native dialog modality/focus trapping, real scroll,
fragment positioning, image optimization, browser zoom, or screen-reader output
through jsdom.

### 30.2 Backend

PHPUnit covers:

- successful import on a freshly migrated database containing only the two
  pristine Phase 4 structural singletons;
- explicit dataset consistency;
- all created publishable entities draft, hidden, and unpublished;
- correct approved photo and bilingual CV asset ingestion;
- no users and no projects/experiences invented;
- Profile and Site pristine-default acceptance;
- rejection without DB/filesystem mutation when either structural singleton has
  any professional/localized value, asset metadata/path, non-default publication
  state, or when either singleton table has an additional row;
- each of the other twelve listed tables independently rejecting a nonempty
  initial state without mutation;
- second execution failing before mutation;
- invalid/missing asset and invalid dataset failing during preflight;
- DB failure rolling back records;
- storage failure cleaning only files created by that attempt;
- preexisting files never deleted;
- no automatic publication or cache/public exposure;
- unchanged repeatable behavior of `PortfolioContentSeeder`.

## 31. Manual QA and integration evidence

`docs/testing/PHASE_5_VERIFICATION.md` is the Phase 5 evidence ledger. It records
commit, environment, command, result, and evidence for:

- desktop, tablet, mobile, and the exact 48rem/64rem boundaries;
- 320px and 200% zoom;
- light/dark and ES/EN independently;
- keyboard, skip link, visible focus, headings, and landmarks;
- mobile dialog with native modality, Escape, close, destination selection,
  background scroll, focus restoration, and clean transition across `64rem`;
- Indexed Detail transition across `64rem`, including all dossiers becoming
  visible below the breakpoint;
- real JavaScript-disabled fallback with no exposed dead Menu/theme controls;
- locale change with recognized hash;
- native fragment positioning plus enhanced focus behavior;
- Indexed Detail and no-JavaScript sequential dossiers;
- reduced motion/no dependency on animation;
- Hero with and without photo where practical;
- zero Projects, regional failure, structural failure, and Retry;
- Contact and locale CV download;
- Caddy/Next optimizer/Laravel storage integration from section 27;
- responsive reflow with long ES/EN copy;
- browser console free of hydration warnings, hydration errors, and uncaught
  client exceptions while exercising ES/EN, theme bootstrap, Indexed Detail,
  mobile dialog, and breakpoint changes;
- localized subtree unexpected-error fallback and the minimal root-layout
  global fallback, without professional content;
- a real screen-reader run recording browser, reader, exact flow, and result.

Controlled failure states may use clearly synthetic test fixtures or a
nonproduction QA harness. They must not add public debug endpoints or production
fallback content.

Phase 5 is not accepted merely because unit tests pass. Required checks are:

```text
frontend tests
frontend typecheck
frontend lint
frontend production build with API unavailable at build time
backend tests
Pint
repository validation
documented real-browser QA
no hydration warnings/errors or uncaught client errors in the recorded flows
```

## 32. Phase 6 extension points

Phase 6 receives stable semantic components, anchors, SSR content, media geometry,
and reduced-motion behavior. It may enhance those surfaces without changing API
types, fetch ownership, cache policy, locale routing, failure policy, or content
grouping.

Phase 5 adds no `data-motion`, timeline IDs, animation registry, GSAP ref,
ScrollTrigger hook, empty animation wrapper, field-of-nodes scaffold, or
animation dependency. Phase 6 adapts the semantic DOM only where its approved
storyboard requires it.

## 33. Traceability of concrete specification assertions

Concrete details added while writing or reviewing this specification have the
following provenance. This table distinguishes repository evidence from new
Phase 5 choices being submitted for human approval.

| Assertion | Evidence or decision status |
|---|---|
| LinkedIn and GitHub open in a new tab with safe `rel` | The approved Phase 2 localized prototype files contain `target="_blank" rel="me noopener noreferrer"` for those two links, and `docs/design/phase-2/VALIDATION_REPORT.md` records Contact/external-link validation. The localized new-tab announcement is a Phase 5 accessibility requirement approved during brainstorming, not claimed as pre-existing Phase 2 copy. |
| Fixed approved photo and CV input paths | `docs/content/ASSET_INVENTORY.md` entries AST-PHOTO-PROFILE, AST-CV-ES, and AST-CV-EN and `docs/content/SOURCE_INVENTORY.md` name the three tracked files exactly. |
| Fourteen production-baseline tables | The names exactly match the Phase 4 content and pivot tables created by `api/database/migrations/2026_09_02_000000` through `000003`. The same migration and `PhaseFourSchemaTest` require the pristine `default` Profile and SiteConfiguration structural rows, so the approved operational erratum treats those two rows as editorially empty only under the exact conditions in section 29.2; the other twelve tables remain literally empty. Users/authentication are not inferred into the baseline. |
| Exact Technology group membership/order | `docs/api/PUBLIC_API_V1.md` formally guarantees exactly four entries in canonical order; `SiteResource` iterates `TechnologyCategory::cases()` directly; `PublicApiContractTest` asserts the exact JSON order. |
| Primary navigation, section, and Menu/Close labels | The Phase 2 `SITEMAP.md`, localized prototype HTML, wireframes, and artifact validator contain these exact labels. |
| Work Case field labels, `Actualidad`/`Present`, 404 copy, and new-tab suffix | These are technical UI-copy choices introduced and clearly enumerated by this Phase 5 specification. They are not represented as prior Phase 1 professional content or hidden Phase 2 decisions; human approval of this revised specification approves them. |
| Root-layout global error fallback | The installed dependency is Next.js `16.2.12`; the official App Router error-handling contract states that same-segment `error.tsx` does not cover its layout and that `app/global-error.tsx` handles root-layout failures. |
| New Work Case editorial gap | Evidence and status are recorded explicitly in section 29.5. It is a self-review discovery, not a brainstorming decision. |

No concrete assertion in this table authorizes extraction of professional facts
from CVs, prototypes, tests, or implementation history.

## 34. Documentation outcomes

Implementation must update documentation that becomes inaccurate because of
Phase 5, including:

- root/developer setup documentation for the real public web workflow;
- architecture documentation for the server-only loader and Laravel-only cache
  ownership;
- deployment handoff documentation for the explicit production import command,
  without executing deployment;
- `ROADMAP.md` only when Phase 5 work and evidence actually satisfy each item;
- `docs/testing/PHASE_5_VERIFICATION.md` with real evidence.

Historic Phase 4 verification drift remains a separately reported documentation
cleanup; this spec does not silently rewrite Phase 4 history.

## 35. Final acceptance criteria

Phase 5 is accepted only when both the implementation gate and the editorial
readiness gate in section 3.1 are satisfied and all of the following are true:

1. The six real Phase 4 contracts are validated at runtime from `unknown`.
2. All six no-store requests begin in parallel and render one coordinated SSR
   response.
3. Profile/Site failures, collection failures, successful empties, malformed
   payloads, and unexpected exceptions follow distinct approved behavior.
4. Laravel is the sole temporal cache authority and each new request reacquires
   content.
5. The site builds without Laravel being available during build.
6. Main navigation, locale switching, fragment allowlist, theme, mobile dialog,
   and no-JavaScript fallback meet this specification.
7. Work Cases precede Experience and progressive Indexed Detail leaves all
   content in initial HTML.
8. Hero, sections, Contact, CV, nullable images, and zero Projects use only CMS
   content and approved UI copy.
9. Basic metadata uses the exact Profile formulas and advanced metadata remains
   deferred.
10. Responsive, keyboard, focus, contrast, heading, zoom, reduced-motion, and
    screen-reader requirements have recorded evidence.
11. The real Caddy/Next/Laravel media topology is verified without exposing an
    internal origin or adding an unapproved proxy.
12. The initial import uses a deterministic reviewed dataset, accepts only the
    two pristine Phase 4 structural singletons plus twelve literally empty
    editorial tables, rejects every deviation without mutation, writes only
    draft/hidden/unpublished content, and compensates only its own newly created
    files.
13. No content, metrics, experience dates, employer, role, Technology label,
    Project, URL, asset, or professional claim is invented.
14. Frontend/backend checks, production build, formatting, repository validation,
    and manual Phase 5 QA pass.
15. Recorded ES/EN, theme, Indexed Detail, dialog, and responsive flows have no
    hydration warning/error or uncaught client exception.
16. Profile, Site, approved Work Cases, photo, professional links, and both CVs
    meet the editorial readiness requirements; unresolved Experience inclusion
    is recorded as an editorial acceptance blocker unless explicitly decided.
17. No Phase 6, Phase 8, Phase 10, Phase 11, or deployment scope has leaked into
    the implementation.
