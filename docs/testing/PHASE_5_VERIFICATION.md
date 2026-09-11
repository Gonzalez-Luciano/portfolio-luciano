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
| Frontend unit/integration | `docker compose run --rm --no-deps web pnpm test:run` | 290 passed (after Task 10); grew to the Task 14 figure recorded below |
| Frontend typecheck | `docker compose run --rm --no-deps web pnpm typecheck` | clean at every task commit |
| Frontend lint | `docker compose run --rm --no-deps web pnpm lint` | clean at every task commit |
| Frontend offline build | `INTERNAL_API_ORIGIN=http://unreachable.invalid … pnpm build` | PASS; `/[locale]` reported `ƒ (Dynamic)`; loader not executed at build |
| Backend suite | `docker compose --profile test run --rm api-test php artisan test` | 506 passed (after Task 12) |
| Backend style | `docker compose run --rm --no-deps api ./vendor/bin/pint --test <files>` | clean for every Phase 5 file |

The full Task 15 automated verification matrix is recorded in section 6 below
(pending — Phase 5 execution STOPPED at Task 13, see section 3).

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

## 3. Elevated incompatibility — media topology (awaiting human decision)

The approved Phase 5 media strategy (`next/image` + the Phase 4 root-relative
`/storage/...` reference) is not resolvable by the Next image optimizer from
inside the current Compose topology, because the optimizer runs in the `web`
container and resolves a root-relative image `url` against its own origin
(`web:3000`), which does not serve `/storage/*` and cannot route to Caddy or
`api` for image bytes. Direct browser access to `/storage/...` through the
gateway works (HTTP 200 `image/jpeg`).

Options that would require a new human decision (all currently forbidden by
spec §27 / instructions §15 without approval):

- give the Next optimizer a narrow, non-leaking internal origin for
  `/storage` resolution (e.g. a build/runtime config pointing image fetches at
  `api`), without that origin reaching HTML;
- a Caddy/topology change so the `web` origin itself serves `/storage/*`
  (e.g. `web` proxying `/storage` to `api`), i.e. making the root-relative
  reference genuinely same-origin from `web`'s perspective;
- an approved public absolute media origin added to the Phase 4 contract plus a
  single `remotePatterns` entry for exactly that origin;
- rendering the hero/project photos with a plain `<img>` (no Next optimizer),
  since `/storage/...` already resolves correctly for the browser.

No option has been applied. Task 14 and the remainder of Task 15 are blocked on
this decision.

---

## 4. Real-browser QA (Task 15 step 2–4) — NOT RUN

Blocked: Phase 5 execution stopped at Task 13. Also requires real-browser /
JS-disabled / screen-reader capability; to be executed by a human per the
Task 15 human-evidence rule.

## 5. Editorial readiness (Task 15 step 5) — NOT EVALUATED

Blocked by the Task 13 STOP. The known §29.5 / §3.1 editorial blockers remain
outstanding regardless:

```
Experience     -> no approved organization/role/start; import leaves experiences = []
Work Cases     -> problem/contribution/technical_approach/outcome not approved (all null)
Technology grp -> exact ES/EN labels not approved (all null; QA-only labels used for topology testing)
Projects       -> deliberately empty until Phase 7
```

## 6. Task 15 automated verification matrix — NOT RUN (blocked)

---

## 7. Status

**Phase 5 execution: STOPPED at Task 13 (media-topology incompatibility).**
Request-scoped cache lifecycle: verified PASS. Media optimizer path: verified
FAIL through the real topology. Awaiting a human decision on the media
strategy (section 3) before Task 14 can proceed.
