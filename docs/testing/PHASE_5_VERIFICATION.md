# Phase 5 verification evidence

This ledger records the Phase 5 automated, integration, media-topology,
request-lifecycle, hydration, browser, and editorial-readiness evidence, per
the approved spec (`docs/superpowers/specs/2026-09-09-phase-5-public-site-design.md`)
sections 30, 31, and 35 and the approved implementation plan
(`docs/superpowers/plans/2026-09-09-phase-5-public-site.md`) Tasks 13–15.

Branch: `codex/phase-5-public-site`. Recorded from the isolated integration
stack unless noted. Dates are UTC.

---

## 1. Automated suites (per-task, recorded during subagent-driven execution)

| Check | Command | Result |
|---|---|---|
| Frontend unit/integration | `docker compose run --rm --no-deps web pnpm test:run` | grew task by task to **295 passed** after Task 14 (Task 10: 290) |
| Frontend typecheck | `docker compose run --rm --no-deps web pnpm typecheck` | clean at every task commit |
| Frontend lint | `docker compose run --rm --no-deps web pnpm lint` | clean at every task commit |
| Frontend format | `docker compose run --rm --no-deps web pnpm format:check` | clean (Task 14 fixed 30 files that had never been formatted; converged after 2 passes) |
| Frontend offline build | `INTERNAL_API_ORIGIN=http://unreachable.invalid … pnpm build` | PASS; `/[locale]` reported `ƒ (Dynamic)`; loader not executed at build |
| Backend suite | `docker compose --profile test run --rm api-test php artisan test` | grew to **506 passed (3094 assertions)** after Task 12's fix round |
| Backend style | `docker compose run --rm --no-deps api ./vendor/bin/pint --test <files>` | clean for every Phase 5 file |

The full Task 15 final automated verification matrix (re-run against the
completed branch, all tasks included) is recorded in section 6.

---

## 2. Task 13 — real Caddy / Next / Laravel runtime topology

### 2.1 Isolated integration stack

- Compose project: `portfolio-phase5-media` (dedicated; own networks/volumes).
- `GATEWAY_PORT=8015` → gateway published on `127.0.0.1:8015` only.
- `docker compose -p portfolio-phase5-media config` resolved only this stack;
  volumes created fresh: `portfolio-phase5-media_{mysql_data,web_node_modules,api_vendor,api_private_media,api_public_media}`.
  No default-project (`portfolio` / `phase-5-public-site`) volume was reused.

**Plan gap ruling A — `mysql` not started by the plan's Step 1 command.**
The plan's `docker compose -p portfolio-phase5-media up -d --build gateway`
does not start `mysql`: the real `compose.yaml` `api` service declares no
`depends_on: mysql` (only `gateway` depends on `web` + `api`), and `api`'s
`/up` healthcheck passes without a database, so the gateway came up "healthy"
while `php artisan migrate` failed with
`SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for mysql failed`.
Resolution: also `docker compose -p portfolio-phase5-media up -d mysql` before
migrating. This completes the plan's stack bring-up for the actual topology; it
is the same `mysql` service the dev/test compose already defines, in the
isolated project's own volume — not an architecture change.

**Plan gap ruling B — `public/storage` link not created.**
The API container entrypoint deliberately does not run `php artisan storage:link`
(documented: `docs/DEPLOYMENT.md`, `docs/testing/PHASE_3_VERIFICATION.md`). The
isolated stack therefore had no `public/storage` symlink, so `/storage/*` 404ed.
Resolution: run `docker compose -p portfolio-phase5-media exec -T api php artisan storage:link`
(standard Laravel public-disk bootstrap that the API contract and the Phase 3/4
`PublicMediaTest` already assume). After it: `public/storage -> /var/www/html/storage/app/public`.
This is not one of the spec §27 forbidden workarounds.

### 2.2 Guarded import end-to-end (bonus Task 12 acceptance evidence)

```
docker compose -p portfolio-phase5-media exec -T api php artisan migrate --force        # exit 0, 8 migrations
docker compose -p portfolio-phase5-media exec -T api php artisan portfolio:import-initial-content
# -> "The approved initial portfolio content was imported as draft, hidden, unpublished records."  exit 0
```

The guarded importer ran successfully against a real, freshly migrated database
using the read-only `./docs:/var/www/docs:ro` mount added in Task 12. It filled
the two pristine singletons and created the draft collections; the approved
photo attached to `profiles.default` (private path set, `photo_public_path`
null before publication).

### 2.3 QA fixture publication (Task 13 Step 2)

Through the normal Phase 4 Actions (`UpdateContent` → `PublishContent` →
`ShowContent`), Site technology-group labels were set to the synthetic values
`QA Backend` / `QA Data` / `QA Integration` / `QA Collaboration` (both locale
columns) and Site + Profile were published and made visible. These QA labels
are **not** approved production content.

Result: `SITE:published/visible PROFILE:published/visible`.

- `GET http://localhost:8015/api/v1/es/profile` → 200
  `photo.url = "/storage/profiles/69de1612-270b-4789-9906-97a93a6b05bf.jpg"`,
  `photo.alt = "Retrato profesional de Luciano González sobre fondo naranja"`.
- `GET http://localhost:8015/api/v1/es/site` → 200,
  `technology_groups` = the 4 QA labels in canonical order
  `backend, data, integration, collaboration`; `professional_links`,
  `expertise_areas`, `work_principles` = `[]` (imported rows remain draft);
  `cv` = `null` (imported CV rows remain draft).

### 2.4 Request-scoped cache / next-request reacquisition — **PASS**

Method: UTC marker → one `GET http://localhost:8015/es?request-scope-probe=1`
→ filter the `api` container's Apache access log (stdout) for exact
`GET /api/v1/es/{profile,site,experiences,work-cases,projects,technologies} HTTP/`
lines → repeat with `?request-scope-probe=2`. No application endpoint, BFF, or
instrumentation was added; the Apache access log is the only evidence source.

| | total | profile | site | experiences | work-cases | projects | technologies |
|---|---|---|---|---|---|---|---|
| after request #1 | **6** | 1 | 1 | 1 | 1 | 1 | 1 |
| cumulative after request #2 | **12** | 2 | 2 | 2 | 2 | 2 | 2 |

All six first-request lines carry the same timestamp and User-Agent `node`
(server-side SSR fetch). This proves, in the installed Next `16.2.12` /
React `19.2.4` runtime, that the localized layout, `page`, and
`generateMetadata` share one request-scoped `cache(loadPublicPortfolioUncached)`
acquisition (6, not 18) and that a second independent request performs six
fresh `no-store` reads. Spec §13 / §35 criterion 4: **verified**.

### 2.5 Media topology — **FAIL → mandatory STOP before Task 14**

Preferred strategy (spec §27 option 1: `next/image` + the Phase 4 root-relative
`/storage/...` reference, unchanged).

Evidence, all through `http://localhost:8015` (Caddy → {web:3000, api:80}):

1. API value: `photo.url = "/storage/profiles/69de1612-270b-4789-9906-97a93a6b05bf.jpg"` (root-relative).
2. Emitted `/es` HTML (`next/image`, `fill`): `src`/`srcSet` entries are all
   root-relative `"/_next/image?url=%2Fstorage%2Fprofiles%2F69de1612-…​.jpg&w=<n>&q=75"`.
   Page-source + emitted-URL scan for `http://api`, `INTERNAL_API_ORIGIN`,
   `portfolio-phase5-media-api`, `api:80`, `host.docker*`: **none** — no internal
   origin leaks into HTML.
3. `GET http://localhost:8015/storage/profiles/69de1612-….jpg`
   → **HTTP 200, `Content-Type: image/jpeg`, 175561 bytes** (after `storage:link`).
   Caddy → Laravel public disk works for a direct browser request.
4. `GET http://localhost:8015/_next/image?url=%2Fstorage%2Fprofiles%2F69de1612-….jpg&w=640&q=75`
   → **HTTP 400 Bad Request** (`Via: 1.1 Caddy`), body
   `"The requested resource isn't a valid image."` — same at `w=32`, `w=1200`.
5. `web` (Next optimizer) container log, correlated to the optimizer request:
   ```
   GET /storage/profiles/69de1612-270b-4789-9906-97a93a6b05bf.jpg 404 in 56ms (next.js: 16ms, application-code: 40ms)
   ⨯ The requested resource isn't a valid image for /storage/profiles/69de1612-270b-4789-9906-97a93a6b05bf.jpg received null
   ```
   The Next image optimizer, running inside the `web` container, resolves the
   root-relative `url=/storage/...` against **its own Next server**
   (`localhost:3000`), which has no `/storage/*` route → 404 → `null` → 400.
   The optimizer cannot reach the published image: `web` has no `/storage`
   route, and it has no route to the gateway (published only on the host
   loopback `127.0.0.1:8015`) or to `api:80` for this purpose.
6. The rest of `/es` renders correctly — `<h1 id="hero-name">Luciano González</h1>`,
   `#about #work #expertise #projects #approach #contact` anchors and ES
   headings all present. The only broken surface is the hero photo
   (`data-nimg` count = 1; its optimizer URL 400s).

Conclusion: the preferred root-relative `next/image` + `/storage/...` route
does **not** work through the real Caddy / Next / Laravel topology without a
targeted fix. Spec §27 option 2 (an approved public absolute origin + a narrow
`remotePatterns` entry) does not apply: the Phase 4 public API contract emits
`photo.url` only as a root-relative `/storage/...` string and defines no
approved public absolute media origin, and the optimizer inside `web` could
not reach such an origin in this topology regardless.

**Per Task 13 Step 5 and instructions §15, Phase 5 execution STOPPED before
Task 14** and the incompatibility was elevated. The human approved a fix (a
narrow, server-only Next.js `rewrites()` rule limited to `/storage/*`,
resolving toward the API over the existing internal Docker network — see
section 2.7) with explicit constraints: the public root-relative media
contract stays unchanged, the internal origin is server-side only (never
`NEXT_PUBLIC_*`, never in HTML or the client bundle), `api:80` stays
unpublished to the host, and `remotePatterns` is not broadened. This is
distinct from, and narrower than, the forbidden options (a BFF/Route Handler
with app logic, an exposed `api:80`, a local media copy, or a broad
`remotePatterns`).

### 2.7 Approved fix and re-verification — **PASS**

`web/next.config.ts` gained an `async rewrites()` entry:
`source: '/storage/:path*'` → `destination: '${INTERNAL_API_ORIGIN}/storage/:path*'`.
This is Next's built-in declarative reverse-proxy config, evaluated and
executed entirely inside the Next server process; it intercepts the
optimizer's own internal self-fetch (which arrives at the Next server exactly
like any other incoming request) before it 404s, and forwards it server-side
to `api:80` over the network `web` already uses for the six content fetches.
It does not add a Route Handler, an endpoint, or any aggregation logic; it
does not touch `images.remotePatterns` (the `src` passed to `next/image`
remains root-relative); it does not publish a new host port; the destination
origin (`INTERNAL_API_ORIGIN`) is read only inside `next.config.ts`, a
server-side build/runtime file, and is never exposed as `NEXT_PUBLIC_*`. Real
browser requests are unaffected — Caddy already routes `/storage/*` straight
to `api:80` without touching `web`.

Re-verification, fresh isolated-stack respin (`portfolio-phase5-media`,
`GATEWAY_PORT=8015`), same import + QA-publish sequence, new photo UUID
`3f5c94e5-a437-4502-94f4-53f9e99b1442`:

| Check | Result |
|---|---|
| `GET /storage/profiles/<uuid>.jpg` (gateway) | 200, `image/jpeg`, 175561 bytes (unaffected, as expected) |
| `GET /_next/image?url=%2Fstorage%2F…&w=640&q=75` (gateway) | **200**, `image/jpeg`, 44089 bytes, verified `file` magic: `JPEG image data, progressive, 640x853` |
| `GET /_next/image?…&w=1200&q=75` | 200, `image/jpeg`, 156937 bytes |
| `GET /_next/image?…&w=32&q=75` | 200, `image/jpeg`, 707 bytes |
| `/es` HTML internal-origin leak scan | none (`http://api`, `INTERNAL_API_ORIGIN`, container name, `api:80`, `host.docker*`) |
| Real browser (Chrome, via `claude-in-chrome`), `http://localhost:8015/es`, dark theme (system) | Hero photo renders correctly — approved portrait, orange background, no broken-image icon |
| Real browser, light theme (explicit toggle) | Hero photo renders correctly, text contrast good |

The mandatory media incompatibility gate is now **PASS** with the approved
fix applied. Execution resumed from Task 14.

**Browser note (not a Phase 5 defect):** the Next.js dev-mode error overlay
reported one hydration attribute mismatch on `<body>`:
`cz-shortcut-listen="true"` present only on the client side. This attribute is
injected by the ColorZilla browser extension installed in the automation
Chrome profile, not by any Phase 5 code — Next's own hydration-error message
explicitly names "a browser extension … which messes with the HTML before
React loaded" as a cause, and `cz-shortcut-listen` is ColorZilla's documented
signature attribute. The rendered `className` on `<body>` matched the source
(`"flex min-h-screen flex-col"`) exactly; only the extension-injected
attribute differed. Recorded here for truthfulness; Task 14's automated
hydration test does not run inside a real browser and is unaffected by
browser extensions. Task 15's real-browser console check should note the same
extension caveat if it recurs, or re-test with the extension disabled.

Isolated stack cleaned again after re-verification (down --volumes, ownership
confirmed, zero residual).

### 2.6 Isolated-stack cleanup

`docker compose -p portfolio-phase5-media ps -a` was checked: every
container/volume belonged to `portfolio-phase5-media`. Then
`docker compose -p portfolio-phase5-media down --volumes` and the `GATEWAY_PORT`
env var was cleared. No broad/default-project volume removal was run. The
running `portfolio` dev stack and the `phase-5-public-site` worktree project
were untouched throughout.

---

## 3. Media-topology incompatibility — RESOLVED (human decision recorded)

The approved Phase 5 media strategy (`next/image` + the Phase 4 root-relative
`/storage/...` reference) was not resolvable by the Next image optimizer from
inside the Compose topology as originally implemented (§2.5). The incompatibility
was elevated to the human, who approved **Option A**: give the Next optimizer a
narrow, server-only internal resolution limited to `/storage/*`, with these
exact constraints (verbatim from the approval): the Phase 4 public root-relative
media contract stays intact; the internal origin is exclusively server-side —
never `NEXT_PUBLIC_*`, never in HTML or the client bundle; `api:80` is not
published to the host; `remotePatterns` is not arbitrarily broadened.

Implemented and independently re-verified as **PASS** — see §2.7 for the fix,
the re-verification evidence, and the independent code review confirming every
constraint is honored (`web/next.config.ts`'s `rewrites()` entry; commit
`18746cd`, reviewed clean).

The three other options considered but not chosen (a Caddy/`web` proxy change,
an approved public absolute origin + `remotePatterns`, or a plain `<img>`
fallback) were not applied and remain available if a future contract change
makes them preferable — no combination of them was implemented.

---

## 4. Real-browser QA (Task 15 steps 2–4)

Performed with `claude-in-chrome` (a real, extension-controlled Chrome
instance) against the worktree's own isolated dev stack (`phase-5-public-site`
Compose project, `http://localhost:8005`), migrated + guarded-imported +
QA-fixture-published exactly as in Task 13 §2.1–2.3 (fresh photo UUID
`fd9241cc-1267-4296-bb7b-2a3998c350e5`; only Profile + Site published, all six
content collections legitimately empty).

**Capability limits of this automation session (not fabricated around; see
§4.4):** no genuine viewport resize (`resize_window` reports success but
`window.innerWidth` stays pinned to the OS display's 1920px regardless of the
requested size — confirmed after 3 attempts including 390×844 and 800×600); no
real browser zoom (the exposed tools explicitly do not support zoom keyboard
shortcuts, and the `zoom` action is a screenshot crop, not a reflow); no way to
disable JavaScript execution; no screen reader/assistive technology.

### 4.1 What was verified (real browser, both locales, both themes)

| Check | Result |
|---|---|
| Hero photo renders (the approved portrait, orange background) | ✅ ES/dark, ES/light, EN/dark, EN/light — all 4 combinations screenshotted, no broken-image icon |
| Single `<h1>` = Profile name | ✅ both locales |
| 5 primary nav destinations, stable order, numbered | ✅ `01 Work…05 Contact` / `01 Trabajo…05 Contacto` |
| Skip link keyboard-visible on first Tab | ✅ "Saltar al contenido" / "Skip to content" appears with a clear focus ring, both themes |
| Language switch (`/es` ↔ `/en`) | ✅ real full navigation, correct copy, no errors |
| Theme toggle (Light/Dark) | ✅ instant, correct contrast both locales |
| Empty-state matrix (real, not simulated) | ✅ `#work`/`#expertise`/`#approach` show the exact neutral copy "Contenido no disponible por el momento." with NO granular sub-anchors; `#projects` shows ONLY the exact `site.projects_empty_message` string (the §16 explicit exception — confirmed NOT neutral copy, NOT Retry); `#contact` shows the CMS `contact_intro` alone with no link/CV actions (intro-alone-renders-normally, per §16); footer shows only the Profile identity, no links |
| Console diagnostics across all of the above | ✅ only the documented `cz-shortcut-listen="true"` ColorZilla extension false-positive (identical pattern to Task 13 §2.7's browser note — confirmed reproduced identically on both `/es` and `/en`, confirmed to be the ONLY server/client `<body>` attribute diff both times); **no other error or warning of any kind** |

### 4.2 Anomaly investigated: native fragment scroll not observed in this session

Clicking a primary-nav anchor (`href="#contact"` — a bare server-rendered
`<a>` with zero JS interception, confirmed by reading
`web/src/components/layout/primary-navigation.tsx`) correctly updated
`location.hash` and `FragmentFocusManager` correctly moved
`document.activeElement` to `<section id="contact">`, but the viewport did not
visually scroll to it. Investigated before concluding anything:

- a real trusted mouse-wheel scroll event moved the page normally immediately
  afterward (rules out a CSS overflow trap, a stuck scroll-lock, or an open
  `<dialog>` — confirmed zero open dialogs and empty inline styles on
  `<body>`/`<html>`);
- `prefers-reduced-motion` was confirmed `false`;
- calling `element.scrollIntoView()` **directly** from the JS console also
  silently did nothing, despite the target being unique, `display:block`,
  `visibility:visible`, `position:static`, with no ancestor overflow trap.

The application code never calls `preventDefault`, `scrollTo`, or any custom
scroll logic on this path — `FragmentFocusManager` explicitly uses
`focus({preventScroll:true})` specifically so it never scrolls (spec §24),
and `PrimaryNavigation` renders a plain anchor with no `onClick`. Since a
trusted wheel gesture scrolls the identical page normally, this reads as this
specific remote/extension-controlled Chrome session suppressing
non-gesture-driven programmatic scrolls — consistent with the same session's
`resize_window` no-op — rather than a reproduction of an application defect.
**Not treated as a confirmed finding; folded into §4.4's human-execution list**
so a normal, non-automated browser can confirm native fragment positioning
directly.

### 4.3 Native mobile `<dialog>`, Indexed Detail cross-breakpoint, keyboard/no-JS/reduced-motion, screen reader

Not verifiable in this session — see §4.4. These are separately proven,
short of real-browser evidence, by:
- automated ARIA/keyboard/roving-tabindex/automatic-activation tests
  (Task 7 `indexed-work-cases.test.tsx`) and the full native-`<dialog>`
  open/close/focus-restore/scroll-lock/breakpoint-crossing observable-DOM
  test suite (Task 5 `mobile-navigation.test.tsx`);
- the Task 14 hydration test's real `hydrateRoot` exercise of the same dialog
  and tab interactions with zero console diagnostics;
- explicit jsdom-limitation disclaimers already present in both test files,
  naming exactly what only a real browser/AT can prove.

### 4.4 Evidence requiring human execution (not fabricated)

Per the Task 15 human-evidence rule, the following are recorded as requiring
a human with a real, non-automated browser/device — not inferred, not copied
forward as a PASS:

```
- Mobile/narrow viewport breakpoints (320/360/390/768px / <64rem), including
  the native mobile <dialog> (only rendered via CSS below 64rem) and the
  <noscript> mobile navigation fallback.
- Real 200% browser zoom reflow (no horizontal scroll/clipping/truncation).
- Native fragment (#hash) scroll positioning in a normal browser (§4.2).
- JavaScript-disabled mode (professional content + sequential Work dossiers
  readable; dead Menu/theme controls absent; <noscript> nav functional).
- A real screen reader/assistive-technology pass (name, version, browser,
  exact navigation/dialog/tabs/CV flow, and result — no generic "PASS").
```

---

## 5. Editorial readiness (Task 15 step 5) — IMPLEMENTATION COMPLETE / EDITORIAL ACCEPTANCE BLOCKED

Verified via the guarded import's own dataset (`InitialPortfolioContent::data()`,
Tasks 11–12) and the QA-fixture publication run (Task 13 §2.3, Task 15 §4):
Profile and Site publish and serve correctly through the normal Phase 4 flow
when their required fields are filled; every other required editorial input is
still an approved-but-incomplete or not-yet-approved gap. Per spec §3.1, these
four gates remain unresolved and are **not** filled by inference, and structural
unavailability / neutral-empty UI is **not** used as acceptance evidence:

```
Experience      -> BLOCKED: no approved organization/role/start exists; import leaves experiences = []
                   (an explicit human decision to accept an empty Experience release state has not been recorded either)
Work Cases      -> BLOCKED: problem/contribution/technical_approach/outcome are not approved for any
                   of the 4 cases (all null in the dataset); cannot be published as-is
Technology grp  -> BLOCKED: exact approved ES/EN labels for backend/data/integration/collaboration
                   do not exist (all null in the dataset; the "QA Backend" etc. labels used in Tasks 13/15
                   are synthetic QA fixtures only, explicitly not approved production content)
Projects        -> BY DESIGN, not a blocker: deliberately empty until Phase 7
```

**Verdict: IMPLEMENTATION COMPLETE / EDITORIAL ACCEPTANCE BLOCKED.** Phase 5's
technical implementation, automated verification, and the real-browser evidence
obtainable in this session are complete; the ROADMAP Phase 5 acceptance
criteria/deliverables are **not** marked complete (§9/ROADMAP.md), because three
of the four editorial gates above remain open. Only a human, through the normal
Filament review/publication flow, can close them.

---

## 6. Task 15 final automated verification matrix

Re-run against the complete branch (all 15 tasks), 2026-09-11:

| Check | Command | Result |
|---|---|---|
| Repository/environment contract | `node infra/validation/validate-repository.mjs` | **Found and fixed a real defect** (stale `Foundation.apiUnavailable` key reference — see §6.1) — PASS after fix |
| Compose config | `docker compose config` | exit 0 |
| Frontend format | `pnpm format:check` | exit 0 |
| Frontend lint | `pnpm lint` | exit 0 |
| Frontend typecheck | `pnpm typecheck` | exit 0 |
| Frontend tests | `pnpm test:run` | **295 passed**, 24 files |
| Frontend offline build | `INTERNAL_API_ORIGIN=http://unreachable.invalid pnpm build` | exit 0, `/[locale]` = `ƒ (Dynamic)` |
| Backend tests | `docker compose --profile test run --rm api-test php artisan test` | **506 passed (3094 assertions)** |
| Backend style | `./vendor/bin/pint --test` | PASS, 200 files |
| `git diff --check` (committed branch diff, `a09bba2...HEAD`) | — | clean, 0 issues |

### 6.1 Defect found and fixed during Step 6

`infra/validation/validate-repository.mjs:172` asserted a stale key,
`spanishMessages.Foundation.apiUnavailable` — a Phase 3 `Foundation` message
namespace + `ApiStatus` copy string. Task 2 legitimately deleted `ApiStatus`;
Task 4 legitimately renamed the entire catalog `Foundation` → `Portfolio`. The
repository validation script was never updated to match, so it crashed with a
`TypeError` on every run against the Phase 5 branch. Fixed (same intent — a
UTF-8 integrity guard on an accented Spanish string, just pointed at the
current key): now asserts
`spanishMessages.Portfolio.state.regionalFailure === 'No pudimos cargar esta sección.'`.
Re-run: PASS. This is a one-line, non-behavioral fix to a validation script,
not a change to any shipped Phase 5 code.

`git diff --check` against the dirty **working tree** (not shown above) flags 3
trailing-whitespace lines — confirmed to be exclusively in the pre-existing,
never-staged `api/public/js/filament/**` vendor-JS drift produced by
`composer install` re-publishing Filament's own build assets on every fresh
`vendor/` volume. Not part of any Phase 5 commit; left untouched throughout
execution.

---

## 7. Branch scope scan (Task 15 step 7)

`git diff a09bba23733241bc14fb71ae38f838f6e200a70a...HEAD` (excluding the
Filament vendor-JS drift) was searched for every forbidden pattern in the
plan's Global Constraints and Task 15 step 7: `unstable_cache`,
`revalidateTag`/`revalidatePath`, a `NEXT_PUBLIC_*` internal-API variable,
`images.remotePatterns`, `api:80` exposure, Playwright/Cypress/axe-core,
GSAP/ScrollTrigger/Framer Motion, analytics (`gtag`/Google Analytics),
`robots.txt`/`sitemap.xml`/Open Graph/Twitter card/JSON-LD, a `/app/api/*`
Route Handler, unchecked `as Profile`/`as SiteConfiguration`-style casts, and
a hardcoded `/cv/luciano-gonzalez-*.pdf` literal.

**Result: clean.** Every hit found is either (a) prose in this verification
document or in `next.config.ts`'s own comment explaining what was
*deliberately not done*, or (b) a NEGATIVE unit-test assertion proving the
absence of the pattern (e.g. `load-public-portfolio.test.ts`:
`expect(source).not.toMatch(/unstable_cache/)`; `contact-section` tests:
"contains no /cv/luciano-gonzalez- literal"; `validators.ts`'s `isCvPath`
comment gives an illustrative example of the route *shape*, not a literal used
in logic). No actual usage of any forbidden pattern exists in the diff.

---

## 8. Status

**Phase 5: technical implementation COMPLETE, independently reviewed, and
verified** — every task (1–14) passed its task-scoped spec-compliance + code
review (with fix rounds resolved), the Task 13 media-topology and
request-scoped-cache gates both PASS (the media gate via a human-approved,
independently-reviewed narrow exception), the Task 15 final automated matrix
is clean, and the branch scope scan found no leakage from later phases or
forbidden patterns.

**Phase 5 editorial acceptance: BLOCKED** (§5) — Work Cases, Technology-group
labels, and the Experience release decision remain open human decisions per
spec §3.1. `ROADMAP.md` is updated only to the extent that evidence was
actually earned (§9 of the plan; see the ROADMAP diff in this same commit).

**Per the approved plan and the human's standing instruction: no merge, no
push, no worktree deletion, and `superpowers:finishing-a-development-branch`
is NOT invoked.** The branch is left ready for human review and the human's
own manual test pass.
