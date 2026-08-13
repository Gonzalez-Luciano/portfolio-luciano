# Phase 3 Technical Architecture and Docker Environment

- **Date:** 2026-08-13
- **Status:** Complete design approved by Luciano on 2026-08-13; written specification awaiting approval
- **Branch:** `feat/phase-3-foundation`
- **Worktree:** `.worktrees/phase-3-foundation`
- **Scope:** Reproducible Next.js, Laravel/Filament, MySQL, Caddy, Docker Compose, environment, testing, and developer-workflow foundations. No Phase 4 content domain, Phase 5 portfolio implementation, advanced motion, final production Compose, or physical server operations.

## 1. Purpose and success condition

Phase 3 creates the application workspace and a reproducible development/test foundation inside the existing Git monorepository. It must establish clean application and HTTP boundaries that work locally on Windows and Ubuntu WSL2 and can later be adapted by the external server-operations workflow after inspecting the real Linux host.

Phase 3 succeeds when a developer can start from a clean checkout, explicitly populate dependency volumes, start the complete development stack, migrate Laravel, link public storage, optionally create the first administrator through an interactive command, and reach the same-origin contract at `http://localhost:8000`. Automated backend tests must use a separate disposable MySQL 8.4 service and never development data. The documented browser smoke pass must prove the real Caddy routing, Filament/Livewire behavior, media serving, HMR, locale behavior, theme behavior, and persistence boundaries.

The outcome is a **deployment-ready repository, not a prematurely deployed repository**.

## 2. Authoritative inputs and continuity

This specification is subordinate to and consistent with:

- `AGENTS.md`
- `README.md`
- `ROADMAP.md`
- `docs/PROJECT.md`
- `docs/ARCHITECTURE.md`
- `docs/SERVER_ARCHITECTURE.md`
- `docs/DEPLOYMENT.md`
- the approved Phase 0 foundation specification
- the approved Phase 1 content plans and content artifacts
- the approved Phase 2 experience specification, plan, design system, responsive specification, sitemap, wireframes, prototype, and validation report

The complete Phase 3 lifecycle uses the existing branch `feat/phase-3-foundation` and the single isolated worktree `.worktrees/phase-3-foundation`. Brainstorming, specification, documentation, planning, implementation, tests, reviews, fixes, and final integration must not be split into other Phase 3 branches or worktrees.

The Phase 0–2 handoff is sufficient. The remaining earlier-phase items do not block Phase 3:

- Public multi-institution wording still lacks approved copy and must not be invented.
- The Spanish CV still needs its incorrect language metadata corrected and reapproval before publication.
- Phase 2 manual checks for exact 200% zoom, forced colors, and native-dialog behavior remain later UI integration considerations.

Phase 3 must preserve the approved Operational Editorial visual direction but implements only enough interface to validate the technical foundation.

## 3. Fixed stack and reproducibility policy

### Frontend

- Node.js 24 LTS.
- The latest patched **stable Next.js 16.2.x** available at implementation time. Preview, canary, and Next.js 16.3 prereleases are excluded unless a later concrete requirement is separately approved.
- React 19.2.x.
- TypeScript strict mode.
- Tailwind CSS 4.x.
- `next-intl` 4.x.
- pnpm `11.20.0`, recorded through the `packageManager` field.
- `pnpm` remains scoped to `web/`, which owns its `package.json` and `pnpm-lock.yaml`. There is no root pnpm workspace.

The exact resolved JavaScript dependency graph is committed in `web/pnpm-lock.yaml`.

### Backend

- PHP 8.5.
- Laravel 13.
- Filament 5.
- Livewire 4 on the Filament-compatible stable line.
- Composer dependency resolution committed in `api/composer.lock`.
- Apache inside the API container; PHP-FPM is not introduced at the gateway.

### Infrastructure

- MySQL 8.4 via the deliberate `mysql:8.4` image policy for development and automated tests.
- Caddy 2 as the project gateway, using a deliberately versioned stable image rather than `latest`.
- Docker Desktop with the WSL2 backend as the canonical Windows Docker engine.
- Docker Compose V2 syntax and behavior.

Implementation records the exact compatible stable application and container versions selected within these approved lines. A concrete incompatibility is reported rather than silently downgrading an approved major version.

## 4. Repository/application workspace

The repository already exists and must not be initialized again.

```text
portfolio/
├── web/                    # independent Next.js/pnpm application
├── api/                    # independent Laravel/Composer application
├── infra/
│   ├── caddy/              # Caddy configuration and HTTP ownership record
│   ├── docker/             # application container and Apache/vhost configuration
│   └── validation/         # only justified infrastructure validation helpers
├── docs/
│   ├── superpowers/        # approved specifications and plans
│   └── testing/            # version-controlled manual smoke procedure
├── compose.yaml            # one development/test Compose project
├── .env.example            # placeholder-only Compose contract
├── .editorconfig
└── README.md
```

`web/` and `api/` own their application configuration, tests, ignore files, and dependency lockfiles. `infra/` owns project-specific gateway and container configuration but never shared-host Cloudflare or physical-server operations. `.editorconfig` establishes consistent UTF-8, line-ending, indentation, and trailing-whitespace rules without overriding framework-specific formatters.

Git and Docker ignore files exclude real environment files, dependency directories, generated storage links, framework runtime/cache output, secrets, Git metadata, and irrelevant build-context files. Tracked environment examples contain variable names and explicit placeholders, never operational credentials.

## 5. Frontend foundation

### 5.1 App Router and localized routes

The application uses the Next.js App Router under `web/src/app/[locale]/`. The only supported locales are `es` and `en`.

Locale handling is centralized and used consistently by routing middleware/proxy, navigation, and tests:

1. a valid explicit locale cookie;
2. a supported `Accept-Language` preference;
3. Spanish as the fallback.

The Next.js 16 convention is implemented through `web/src/proxy.ts`. `/` always redirects to an explicit localized route. Only an explicit language-control action persists the locale cookie; passive detection does not. Invalid stored values are ignored safely.

Phase 3 must keep `/es` and `/en` statically renderable/prerenderable and verify that the generated foundation preserves that capability. This is the preferred baseline, not a permanent ban on justified dynamic rendering in later phases.

Technical interface strings belong in `next-intl` message files. Approved CMS content is not copied into React source or translation messages.

### 5.2 Rendering and component boundaries

Server Components are the default. Client Components are limited to real interactive boundaries such as the language and theme controls. The minimal Phase 3 page demonstrates locale, theme, API boundary, and gateway operation without implementing the Phase 5 public portfolio sections or Phase 6 animation system.

Data fetching stays separate from presentation. No global state library or theme package is introduced.

### 5.3 Theme foundation

Theme handling preserves static rendering and never calls Next.js `cookies()`:

- Only explicit `light` or `dark` selections are stored in `localStorage`.
- With no valid explicit preference, CSS/system behavior follows `prefers-color-scheme`.
- A tiny deterministic dependency-free bootstrap runs before first paint and sets a stable `data-theme` attribute on the root element.
- Invalid local-storage values fall back safely to the system preference.
- The interactive theme control initializes from the theme already applied to the DOM; it does not repaint to a second initial value.
- If the deliberate pre-hydration root mutation creates a root-attribute mismatch, `suppressHydrationWarning` may be used only on that root element. It must not conceal unrelated hydration defects.
- The bootstrap is isolated and source-stable so a future CSP can permit it through an appropriate hash/nonce strategy without making the route dynamic solely for theme handling.

Automated tests cover resolution and persistence logic where deterministic DOM testing is appropriate. The real absence of a wrong-theme flash, hydration warnings, and browser-console errors is verified in the manual browser smoke pass.

### 5.4 Typed API boundary

The API client distinguishes two origins:

- Server-side Next.js requests use a server-only internal origin such as `http://api` supplied explicitly to the web service.
- Browser requests use same-origin relative `/api/...` URLs through Caddy.

The internal origin must never use a `NEXT_PUBLIC_` name or enter browser bundles. The frontend never receives MySQL credentials. The client validates/normalizes the Phase 3 response contract and exposes typed success and failure results. HTTP failures, malformed responses, and unavailable upstreams must not be confused with successful empty data.

### 5.5 Frontend quality commands

`web/package.json` owns independent commands for formatting checks, linting, type checking, automated tests, development, and production build. Frozen lockfile installation must work in Windows/PowerShell, Ubuntu WSL2, containers, and future CI through the pinned pnpm version.

## 6. Backend foundation

### 6.1 Laravel HTTP boundary

`api/` is an independent Laravel 13 application. The backend image uses PHP 8.5 with internal Apache HTTP service on port 80.

The container configuration must explicitly:

- set Apache `DocumentRoot` to Laravel's `public/` directory;
- enable the required rewrite module and override/vhost behavior;
- route application requests through `public/index.php`;
- make `storage/` and `bootstrap/cache` writable by the runtime user;
- send Apache access/error output to container-visible stdout/stderr.

The API container does not publish its port to the host. Caddy communicates with it over project-internal HTTP. PHP-FPM is not added at the Caddy layer unless implementation reveals and documents a concrete failure with the approved Apache boundary.

### 6.2 Versioned API

`GET /api/v1` is the minimal public versioned entrypoint. It proves routing and the serialization contract without creating Phase 4 content models. Responses use a documented JSON Resource/envelope convention, and API errors retain a consistent JSON contract instead of falling through to Next.js or Filament HTML.

Phase 3 establishes:

- versioned route grouping;
- explicit Resource/serialization conventions;
- a named API rate limiter;
- tests for success, error, and rate-limit behavior;
- no private fields or stack traces in public responses.

### 6.3 Filament and administrative authentication

Filament 5 provides one panel at `/admin`, with Livewire 4 as required by the resolved Filament stack.

- There is no public registration.
- Session authentication is Laravel-owned.
- The minimal user/admin persistence required for authentication is in scope.
- Panel access is enforced through explicit `canAccessPanel()` authorization.
- An unauthenticated visitor is sent through the normal Filament authentication flow.
- An authenticated but unauthorized user is denied.
- Roles, permissions, CMS resources, content entities, and broader editorial workflows remain Phase 4.

### 6.4 Interactive initial-administrator command

The first administrator is created only by an explicit create-only Artisan command:

```text
php artisan portfolio:bootstrap-admin
```

For the canonical human development/bootstrap flow, the command:

- interactively prompts for name and email;
- prompts for password through hidden/secret input;
- confirms the password;
- validates all values;
- hashes the password through Laravel's hashing facilities;
- creates only when the state is unambiguous and no duplicate account exists;
- refuses duplicate or ambiguous conditions;
- never silently updates an existing user;
- never echoes or logs the password.

Normal seeders never create credentials. Phase 3 does not design non-interactive production secret injection. The external server-operations workflow will choose that mechanism later from the real deployment environment.

Automated command tests use Laravel's console/prompt testing facilities rather than real terminal input. They simulate answers and verify successful creation, validation, duplicate refusal, applicable ambiguous-state refusal, password hashing, and absence of secret values from command output and logs.

### 6.5 Media storage

Phase 3 uses Laravel's standard public-storage behavior:

- application-owned public media lives at `storage/app/public`;
- that boundary is backed by a dedicated persistent API-owned Docker volume in development;
- `public/storage` is created with Laravel's standard storage-link command;
- Laravel/Apache serves the public storage path;
- Caddy routes verified backend-owned media paths over HTTP without mounting or understanding Laravel files.

Runtime framework cache/session/log directories are writable but are not incorrectly treated as durable CMS media. No ad-hoc media server, object storage, or Phase 4 media model is introduced.

### 6.6 CORS, logging, and health

Browser access is same-origin through Caddy. Laravel CORS policy remains restrictive and permits only documented origins where a real direct cross-origin development/testing need exists; it is never broadly opened by default.

Laravel logs use a container-compatible stderr channel/stack. Logs must not expose passwords, credentials, internal secrets, or confidential content.

Laravel's built-in `/up` route is the lightweight API container/application boot signal. It deliberately does not query MySQL. Temporary database unavailability therefore does not by itself mark the Laravel boot signal unhealthy. Database connectivity is checked separately by integration tests, while each MySQL service owns its readiness healthcheck.

## 7. Gateway and HTTP route ownership

Caddy is the definitive project gateway. It is a conventional HTTP reverse proxy and does not mount, inspect, or understand the Laravel source tree.

```text
127.0.0.1:8000
        |
      Caddy
      /   \
     /     \
Next.js   Apache + Laravel
  :3000         :80
                   \
                  MySQL :3306
```

Caddy sends localized/public frontend requests to Next.js and backend-owned requests to Laravel. It supports Next.js development HMR/WebSocket upgrades through its normal reverse-proxy behavior.

The final backend matchers are not guessed in this specification. After Laravel, Filament, Livewire, and public storage are installed and operational, implementation must:

1. capture Laravel's structured registered routes with `php artisan route:list --json`;
2. inspect actual admin login, authenticated panel, Livewire interaction, Filament asset, and public-media HTTP requests;
3. classify every observed public path as Next.js-owned, Laravel-owned, or Caddy-owned;
4. record the ownership in version-controlled infrastructure documentation/comments;
5. configure explicit Caddy matchers for all verified backend-owned route and asset families;
6. match Livewire's variable hash-based route structurally rather than hardcoding an installation-specific hash;
7. re-run the gateway browser smoke checklist.

Known conceptual ownership includes `/api/...`, `/admin...`, Livewire endpoints, and Laravel public media/assets, but conceptual examples do not substitute for installed-route and observed-traffic verification. Backend-owned paths remain backend-owned even when Laravel returns 404 or an error; they never fall through to a misleading Next.js page.

Caddy preserves the incoming Host and uses its standard forwarded-header behavior. Phase 3 adds no speculative Cloudflare-specific trusted-proxy override. The deployment handoff requires external operations to verify forwarded protocol and trusted-proxy behavior when the real host-level Cloudflare Tunnel is connected.

## 8. Docker Compose topology

One root `compose.yaml` defines the complete development/test project.

| Service | Role | Internal port | Networks | Host publication | Lifecycle |
|---|---|---:|---|---|---|
| `gateway` | Caddy reverse proxy | 80 | `front` | `127.0.0.1:8000:80` | development |
| `web` | Next.js development server | 3000 | `front` | none | development |
| `api` | Apache + Laravel | 80 | `front`, `data` | none | development |
| `mysql` | persistent MySQL 8.4 | 3306 | `data` | none | development |
| `mysql-test` | disposable MySQL 8.4 | 3306 | `test` | none | on-demand `test` profile |
| `api-test` | one-shot Laravel test runner reusing the API image | none | `test` | none | on-demand `test` profile |

The networks and volumes are Compose-project-specific. No service uses host networking. Caddy cannot reach either MySQL service. Next.js cannot reach either MySQL service. Development MySQL and test MySQL share neither network, storage, database, nor credentials.

`api-test` waits on the `mysql-test` healthy condition before executing tests. Arbitrary readiness sleeps are prohibited. Its exit code is the Laravel test result and it does not remain running after the test command.

`mysql-test` uses disposable container/tmpfs storage, is safe to recreate from zero, and is started only for test operations. It has no persistent named data volume. Development `mysql` uses a persistent named volume and must never be used by automated tests.

### 8.1 Development mounts and dependency volumes

Development source is bind-mounted to support editing and HMR from the current Windows-accessible checkout. Linux-managed named volumes own:

- `web/node_modules`;
- `api/vendor`;
- development MySQL data;
- Laravel public media at `storage/app/public`.

Fresh dependency volumes are populated by explicit one-shot install commands using the committed lockfiles. The design does not rely on implicit Docker volume copy-up behavior and does not reinstall dependencies on every normal application start.

Container images and mounts must not assume Windows paths internally. If measured bind-mount performance later proves inadequate, moving the checkout into the WSL2 Linux filesystem may be evaluated as an optimization; Phase 3 does not move it preemptively.

### 8.2 Health and startup dependencies

Each health signal has one owner:

- Caddy exposes a lightweight gateway-owned response.
- Next.js exposes/responds to a frontend-owned internal application probe.
- Laravel uses built-in `/up`, independent of MySQL.
- `mysql` and `mysql-test` use MySQL-owned readiness checks.

Compose health/dependency conditions are used only where readiness is genuinely required. In particular, `api-test` must wait for `mysql-test`. Database connectivity is a separate integration assertion, not hidden inside `/up`.

## 9. Environment-variable and secret policy

The root untracked `.env` is the canonical input for Compose interpolation and local cross-service configuration. Docker Compose does **not** automatically inject every root value into containers. `compose.yaml` explicitly passes each service only the variables it owns through deliberate `environment`, `env_file`, or another documented explicit Compose mechanism.

Ownership rules:

- Root/Compose owns project identity, published gateway binding, and development/test database interpolation values.
- The API service receives only Laravel runtime and its selected database values.
- MySQL services receive only their own initialization/readiness values.
- The web service receives its server-only internal API origin and non-secret application settings, but never MySQL credentials.
- Browser-exposed variables use explicit `NEXT_PUBLIC_` naming only when the browser genuinely requires them.
- `http://api` is server-only and is never browser-exposed.
- Caddy receives only gateway configuration values it actually consumes.
- Cloudflare Tunnel credentials have no repository variable, tracked example, container injection, or placeholder.

Tracked root and application example files contain names and placeholders only. Real `.env` files, overrides, credentials, and keys remain ignored. Configuration is not duplicated without an ownership or direct-tooling reason. Documentation includes a variable-ownership table and explains which values are secret, server-only, or intentionally browser-visible.

Environment examples and rendered Compose configuration are reviewed without publishing interpolated secret values in reports or logs.

## 10. Bootstrap, migration, seed, and daily workflow

The canonical fresh-checkout sequence is explicit:

1. copy the root tracked environment example to untracked `.env` and replace required placeholders;
2. build the development images;
3. populate `web/node_modules` with `pnpm install --frozen-lockfile` through a one-shot `web` service command;
4. populate `api/vendor` with `composer install --no-interaction` through a one-shot `api` service command;
5. start development services;
6. run `php artisan migrate` explicitly;
7. run Laravel's standard storage-link command explicitly;
8. optionally run the interactive `portfolio:bootstrap-admin` command.

Normal container start performs none of steps 3, 4, 6, 7, or 8 implicitly. Documentation identifies the explicit dependency refresh command when a lockfile changes.

Phase 3 seeders contain no administrator, credentials, or speculative CMS content. Any framework-level seed introduced must be deterministic, non-secret, safe to rerun, and justified by a Phase 3 requirement. Development migration/reset commands are clearly distinguished; destructive resets are never automatic and are never part of the production handoff.

The external operations workflow owns the eventual production environment, non-interactive secret mechanism if needed, conscious non-destructive migration execution, administrator bootstrap mechanism, and operational audit trail.

Primary direct commands are documented for:

- environment setup;
- cold dependency bootstrap and refresh;
- build;
- start, stop, restart, and clean test-service removal;
- migrate and safe seed;
- storage link;
- interactive administrator creation;
- frontend/backend tests and quality checks;
- logs;
- service and route health inspection;
- Laravel route inventory;
- Compose configuration and published-port inspection.

PowerShell syntax is primary. Equivalent Bash variants are documented wherever syntax differs. Wrapper scripts are introduced only if direct commands prove genuinely unreliable or repetitively error-prone across both shells.

## 11. Testing and verification strategy

### 11.1 Automated repository checks

- Expected application/infrastructure/documentation structure exists.
- Tracked environment examples contain placeholders and no real secrets.
- Real environment files and generated dependencies/runtime files are ignored.
- Compose renders and validates without unsupported or ambiguous configuration.
- Only Caddy publishes `127.0.0.1:8000`; web, API, and both MySQL services publish no host ports.

### 11.2 Automated frontend checks

- Frozen dependency installation succeeds.
- Formatting, lint, strict type checking, and production build pass.
- `/es` and `/en` remain statically renderable/prerenderable in the Phase 3 foundation.
- Locale resolution covers valid explicit preference, `Accept-Language`, Spanish fallback, invalid values, and persistence only after explicit selection.
- Theme logic covers light/dark system preference, explicit overrides, persistence, invalid stored values, and intentional root hydration handling where testable.
- Typed API behavior covers successful data, HTTP failure, malformed response, and unavailable upstream behavior.
- Client/server environment separation is verified so the internal API origin cannot enter the browser bundle.

### 11.3 Automated backend checks

`api-test` runs under Laravel's `testing` environment against only `mysql-test`. Migrations construct the schema from an empty MySQL 8.4 database. Laravel's normal database testing tools, including `RefreshDatabase` where appropriate, keep tests independent of existing state.

Coverage includes:

- Laravel boot and `/up` behavior without coupling it to a database query;
- MySQL connectivity as a separate integration assertion;
- `/api/v1`, response envelope, JSON errors, and named rate limiter;
- unauthenticated admin flow;
- authorized Filament access;
- authenticated unauthorized-user denial;
- Filament/Livewire behavior suitable for application-level automated testing;
- standard storage link/public-media behavior;
- interactive bootstrap command success, validation, duplicate/ambiguous refusal, hashing, and secret-output/log safety;
- proof that test database configuration identifies `mysql-test`, not development `mysql`.

No SQLite substitution is accepted for integration/database behavior that depends on MySQL.

### 11.4 Version-controlled clean-state browser smoke pass

Phase 3 does not add Playwright, Selenium, browser-runner containers, duplicate API HTTP services, duplicate gateways, or a second Compose topology. Instead, a version-controlled manual checklist under `docs/testing/` records, for every check:

- prerequisite state;
- action;
- expected result;
- a place/method for recording observed evidence during implementation verification.

The smoke pass starts after a genuinely clean development bootstrap: fresh checkout, no dependency volumes, no MySQL volume, no storage link, explicit dependency installation, Compose startup, migration, storage link, and optional interactive administrator creation.

Through `http://localhost:8000`, it verifies:

- `/` locale redirect;
- `/es` and `/en`;
- `/api/v1`;
- `/admin` unauthenticated behavior;
- authenticated Filament access;
- one real Livewire interaction through Caddy;
- required Filament and Livewire assets;
- Laravel public media through the gateway;
- Next.js development HMR through Caddy;
- system light and dark theme behavior;
- explicit light and dark overrides and reload persistence;
- invalid stored-theme fallback;
- no visible wrong-theme flash;
- no unexpected hydration warning;
- no relevant browser-console error;
- behavior after a normal stop/start;
- development MySQL persistence after restart;
- Laravel media persistence after restart;
- clean shutdown/restart behavior.

These observations are reported as manual smoke verification, never as automated coverage. The architecture retains deterministic test data, explicit route ownership, and separable authentication so a later CI/testing phase can add isolated browser E2E without redesigning the application.

### 11.5 Platform verification

The primary Windows verification uses PowerShell/Windows Terminal, Docker Desktop, Linux containers, and the Docker Desktop WSL2 backend while leaving the repository in its current Windows-accessible location.

Equivalent commands are executed from Ubuntu WSL2 after enabling Docker Desktop WSL integration. `docker` and `docker compose` must address the same Docker Desktop engine. Documentation explicitly prohibits installing a second Docker Engine inside that Ubuntu distribution for this workflow.

## 12. Error and observability boundaries

- Caddy upstream failures surface as gateway failures; they do not masquerade as valid Next.js pages.
- Backend-owned 404/error responses remain Laravel responses.
- API responses preserve the JSON error convention.
- Filament retains its Laravel-owned HTML and authentication responses.
- The typed frontend client distinguishes transport, HTTP, and response-shape failures.
- Caddy, Next.js, Apache, and Laravel write container-readable logs without secrets.
- MySQL readiness, Laravel boot, frontend response, gateway response, and database integration remain independently diagnosable.

## 13. Development and future production boundary

Phase 3 creates development/test Dockerfiles and Compose behavior with clean service boundaries. It does not create speculative production image targets, a final production Compose file, server-specific overrides, systemd units, Cloudflare configuration, firewall rules, backup jobs, deployment scripts, or physical disk assumptions.

The future application/runtime contract remains:

```text
lucianogonzalez.dev
    -> shared host-level cloudflared
    -> http://127.0.0.1:8000
    -> portfolio Caddy gateway
```

The portfolio is one independent Docker project among multiple host projects. Only its gateway owns the reserved loopback entrypoint. Next.js, Laravel, and MySQL remain internal. MySQL is never public. Database and media persistence remain project-owned. Cloudflare credentials never enter this repository.

The repository handoff tells the external `home_server_ops_claude` workflow the service boundaries, internal ports, route ownership, environment contract, persistence requirements, build commands, migrations, bootstrap behavior, health signals, backup-relevant data, rollback-relevant information, and prohibited exposures. External operations discovers the real host, creates/adapts final production configuration, verifies trusted proxies/forwarded protocol with Cloudflare Tunnel, and performs deployment and host operations.

## 14. Explicit phase boundaries

Phase 3 does not implement:

- Phase 4 CMS resources, content schema, translations, drafts/publication, or domain seed data;
- Phase 5 complete public portfolio sections or CMS integration;
- Phase 6 Motion/GSAP/node-field behavior;
- Phase 8 SEO, analytics, or full metadata system;
- Phase 9 complete security hardening;
- Phase 11 GitHub Actions/release-readiness implementation;
- final production containers/Compose or any physical server deployment;
- project-level `cloudflared`, Tunnel tokens, router forwarding, firewall, backups, or server automation.

YAGNI applies. Redis, queues, schedulers, mail services, object storage, browser-test infrastructure, and other services require a later concrete need.

## 15. Phase 3 acceptance contract

Phase 3 implementation is acceptable only when:

1. the documented monorepo structure and environment examples exist without secrets;
2. exact dependency resolution is committed and fresh dependency volumes bootstrap deterministically;
3. frontend lint, type checks, tests, and build pass, with `/es` and `/en` still statically renderable;
4. Laravel boots under explicit Apache configuration and backend automated tests pass against disposable MySQL 8.4;
5. `/api/v1`, `/admin`, authorization, Livewire foundations, interactive administrator creation, logging, CORS, rate limiting, and standard public media behavior meet this specification;
6. Compose validates, builds, reaches healthy states without sleeps, and keeps test/development data isolated;
7. only Caddy publishes `127.0.0.1:8000`, while no MySQL host port exists;
8. installed/observed backend route ownership is recorded and Caddy routes it without filesystem coupling;
9. persistent development MySQL and media survive normal restart while the test database remains disposable;
10. the clean-state browser smoke checklist passes through Caddy and is reported accurately as manual verification;
11. PowerShell and Ubuntu WSL2 operate the same Docker Desktop-backed stack;
12. documentation agrees on application responsibility versus external server operations;
13. no later-phase application or server-operation scope has been pulled into Phase 3;
14. `ROADMAP.md` Phase 3 boxes remain incomplete until actual implementation and verification justify changing them.

## 16. Specification self-review checklist

Before written approval, review this document and synchronized authority files for:

- contradictions with the six approved design sections;
- unresolved placeholders or guessed framework routes;
- undefined internal or published ports;
- duplicate public entrypoints;
- accidental environment-wide injection or frontend database credentials;
- committed secrets or Cloudflare placeholders;
- development/test MySQL crossover;
- accidental MySQL publication;
- accidental project-level `cloudflared`;
- theme-induced request-time rendering;
- automatic migrations, seeds, or administrator creation;
- hidden dependency-volume bootstrap assumptions;
- arbitrary readiness sleeps;
- speculative production/server configuration;
- browser smoke checks mislabeled as automation;
- scope creep or unnecessary services/dependencies;
- any instruction that could create another Phase 3 branch or worktree.
