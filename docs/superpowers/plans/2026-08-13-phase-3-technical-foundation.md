# Phase 3 Technical Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a reproducible Next.js 16.2.x, Laravel 13/Filament 5, MySQL 8.4, Caddy, and Docker Compose development/test foundation with one same-origin entrypoint and no dependency on physical-server infrastructure.

**Architecture:** Keep `web/` and `api/` independent application workspaces joined only through an explicit HTTP contract. Caddy is the sole host-published reverse proxy; Apache owns Laravel HTTP behavior; persistent development MySQL and media are isolated from an on-demand disposable MySQL test profile. Build and application tests are automated, while real Caddy/HMR/Filament/Livewire/theme behavior is verified through a version-controlled clean-state browser smoke pass.

**Tech Stack:** Node.js 24 LTS, latest patched stable Next.js 16.2.x, React 19.2.x, TypeScript strict, Tailwind CSS 4.x, next-intl 4.x, pnpm 11.20.0, PHP 8.5, Laravel 13, Filament 5, Livewire 4, Apache, MySQL 8.4, Caddy 2, Docker Desktop/Compose V2, Vitest, and Laravel PHPUnit tests.

## Global Constraints

- Execute every step in the existing branch `feat/phase-3-foundation` and worktree `C:\Users\lucho\Desktop\portfolio-luciano\.worktrees\phase-3-foundation`; never create another Phase 3 branch or worktree.
- The parent/controller must be GPT-5.6 Terra before Task 1 starts. Every child agent receives an explicit model; never inherit GPT-5.6 Sol silently.
- Use the latest patched stable Next.js 16.2.x available when Task 3 runs. Never select preview, canary, or Next.js 16.3.
- Pin `packageManager` to `pnpm@11.20.0`; keep `package.json` and `pnpm-lock.yaml` inside `web/` only.
- Use PHP 8.5, Laravel 13, Filament 5, Livewire 4, `mysql:8.4`, and a deliberately versioned stable Caddy 2 image.
- Only `gateway` publishes a host port: `127.0.0.1:8000:80`. Web `3000`, API `80`, and both MySQL `3306` ports remain internal.
- `cloudflared`, Tunnel credentials, final production Compose/images, systemd, firewall, backups, deployment scripts, and physical-host configuration are outside this plan.
- The external `home_server_ops_claude` workflow owns real Linux-host discovery, final production configuration, Cloudflare integration, deployment, backup/restore, and host operations.
- Automated database tests use only disposable `mysql-test` with Laravel `APP_ENV=testing`; never development `mysql` and never SQLite as a MySQL integration substitute.
- Development MySQL and `storage/app/public` persist. Test MySQL is disposable and has no host port or named data volume.
- Root `.env` is untracked Compose interpolation input. Compose explicitly passes only owned variables to each service; web never receives database credentials and `http://api` is server-only.
- Do not install dependencies on every normal container start. Populate Docker-managed `web/node_modules` and `api/vendor` volumes through explicit cold-bootstrap commands.
- `/es` and `/en` stay statically prerenderable; `next build` must pass with Caddy, Laravel, and MySQL stopped.
- Theme preference uses only `light`/`dark` in `localStorage`; absent/invalid values follow `prefers-color-scheme`; no `cookies()` and no `next-themes`.
- The only Phase 3 administrator marker is `users.is_admin BOOLEAN NOT NULL DEFAULT false`; do not add roles, permissions, or RBAC.
- The administrator command remains interactive and create-only. Never place its password in arguments, environment examples, normal seeds, output, or logs.
- Do not guess Filament/Livewire asset routes. Final Caddy ownership comes from `route:list --json` plus observed browser traffic; match the Livewire hash structurally.
- Do not add Playwright, Selenium, browser containers, duplicate gateway/API services, or a second Compose topology.
- Do not implement Phase 4 content entities, Phase 5 sections, Phase 6 motion, Phase 8 SEO/analytics, Phase 9 full hardening, or Phase 11 CI/release work.
- Each task ends with implementation self-checks, a specification-compliance review, a code-quality review, fixes for all approved findings, fresh task tests, and the listed commit. Do not advance with an unresolved review finding.
- Every coding task uses `superpowers:test-driven-development`; any unexpected failure uses `superpowers:systematic-debugging`; completion claims use `superpowers:verification-before-completion`.

## Execution Model Routing

| Work | Required model |
|---|---|
| Parent/controller for all execution | GPT-5.6 Terra |
| Mechanical lockfile/scaffold generation when fully specified | GPT-5.6 Luna if available; otherwise GPT-5.6 Terra |
| Application, Docker, integration, and debugging tasks | GPT-5.6 Terra |
| Per-task specification and quality reviewers | GPT-5.6 Terra by default |
| Genuine architecture contradiction only | GPT-5.6 Sol |
| Final whole-branch review in Task 12 | GPT-5.6 Sol |

If the runtime cannot confirm the requested child model, stop before spawning that child and report the routing limitation.

## File Map

### Repository and environment contract

- Create `.editorconfig`: repository-wide encoding, newline, whitespace, and indentation baseline.
- Create `.env.example`: placeholder-only Compose interpolation contract.
- Create `.dockerignore`: root API-image build-context exclusions.
- Modify `.gitignore`: ignore all real environment files, application dependencies, Laravel runtime output, and generated public-storage link while retaining examples.
- Create `docs/ENVIRONMENT.md`: variable ownership, secret classification, and PowerShell/Bash setup.
- Create `infra/validation/validate-repository.mjs`: structure, ignore, environment, port-publication, and forbidden-scope assertions.

### Frontend application

- Create `web/package.json` and `web/pnpm-lock.yaml`: independent pnpm application and exact resolution.
- Create `web/Dockerfile`, `web/.dockerignore`, and framework/tooling config.
- Create `web/src/i18n/{routing,request}.ts`, `web/src/proxy.ts`, `web/src/app/[locale]/{layout,page}.tsx`, and `web/messages/{es,en}.json`: localized static foundation.
- Create `web/src/theme/{bootstrap,theme}.ts` and `web/src/components/theme-switcher.tsx`: pre-paint theme and interactive control.
- Create `web/src/lib/api/{types,client}.ts` and `web/src/components/api-status.tsx`: exact typed envelope and optional runtime connectivity demonstration.
- Create focused tests under `web/src/**/*.test.ts(x)` and `web/src/app/health/route.ts`.

### Backend application

- Create the Laravel 13 skeleton under `api/`, retaining its normal framework structure and `api/composer.lock`.
- Create `infra/docker/api/{Dockerfile,apache-vhost.conf,entrypoint.sh}` with the root `.dockerignore`: PHP 8.5/Apache runtime with explicit front controller and writable directories.
- Create API contract classes/routes/tests under `api/app/Http`, `api/bootstrap/app.php`, `api/routes/api.php`, and `api/tests/Feature/Api`.
- Create/update the user migration/model, Filament provider, `BootstrapAdmin` command, and tests.
- Create media/storage tests and keep Laravel's standard `public/storage` link behavior.

### Compose and gateway

- Create `compose.yaml`: six-service development/test topology, three networks, named dependency/data/media volumes, explicit variable injection, and health dependencies.
- Create `infra/caddy/Caddyfile`, `infra/caddy/laravel-routes.json`, and `infra/caddy/ROUTE_OWNERSHIP.md`: gateway config and evidence-derived ownership.
- Create `docs/testing/PHASE_3_BROWSER_SMOKE.md`: manual clean-state browser checklist with prerequisite/action/expected-result records.

### Documentation and acceptance

- Modify `README.md`, `docs/ARCHITECTURE.md`, `docs/DEPLOYMENT.md`, and `ROADMAP.md` only as verified implementation evidence becomes true.
- Create `docs/testing/PHASE_3_VERIFICATION.md`: executed commands and results, explicitly distinguishing automated tests from manual smoke checks.

---

### Task 1: Repository, environment, and validation contract

**Files:**
- Create: `.editorconfig`
- Create: `.env.example`
- Create: `.dockerignore`
- Modify: `.gitignore`
- Create: `docs/ENVIRONMENT.md`
- Create: `infra/validation/validate-repository.mjs`

**Interfaces:**
- Consumes: approved specification sections 4 and 9; existing repository documentation.
- Produces: root variable names `COMPOSE_PROJECT_NAME`, `GATEWAY_HOST`, `GATEWAY_PORT`, `APP_URL`, `APP_KEY`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`, `MYSQL_TEST_DATABASE`, `MYSQL_TEST_USER`, `MYSQL_TEST_PASSWORD`, `MYSQL_TEST_ROOT_PASSWORD`; validator entrypoint `node infra/validation/validate-repository.mjs`.

- [ ] **Step 1: Write the failing repository contract validator**

Create `infra/validation/validate-repository.mjs` using only Node standard-library modules. It must assert required files, reject tracked non-example `.env` files, reject `CLOUDFLARE`/`TUNNEL` variables, require placeholder markers such as `replace-with-`, and later validate `compose.yaml` when it exists. Start with:

```js
import assert from 'node:assert/strict';
import {existsSync, readFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
import {resolve} from 'node:path';

const root = resolve(import.meta.dirname, '../..');
const read = (path) => readFileSync(resolve(root, path), 'utf8');

for (const path of ['.editorconfig', '.env.example', '.dockerignore', 'docs/ENVIRONMENT.md']) {
  assert.ok(existsSync(resolve(root, path)), `Missing ${path}`);
}

const tracked = execFileSync('git', ['ls-files'], {cwd: root, encoding: 'utf8'}).split(/\r?\n/);
assert.deepEqual(tracked.filter((path) => /(^|\/)\.env(\.|$)/.test(path) && !path.endsWith('.example')), []);

const example = read('.env.example');
assert.doesNotMatch(example, /CLOUDFLARE|TUNNEL_TOKEN/i);
assert.match(example, /MYSQL_PASSWORD=replace-with-/);
console.log('Repository and environment contract pass.');
```

- [ ] **Step 2: Run it and confirm the intended failure**

Run: `node infra/validation/validate-repository.mjs`

Expected: FAIL with `Missing .editorconfig`.

- [ ] **Step 3: Add editor, ignore, and placeholder-only environment files**

Create `.editorconfig` with UTF-8, final newline, trimmed trailing whitespace, LF for shell/Markdown/YAML/JSON/TS/PHP, four spaces for PHP, two spaces for JS/TS/JSON/YAML, and CRLF only for `*.ps1` if a PowerShell file is later justified.

Create `.env.example` with these names and non-secret placeholders:

```dotenv
COMPOSE_PROJECT_NAME=portfolio
GATEWAY_HOST=127.0.0.1
GATEWAY_PORT=8000
APP_URL=http://localhost:8000
APP_KEY=base64:replace-with-generated-laravel-key
MYSQL_DATABASE=portfolio
MYSQL_USER=portfolio
MYSQL_PASSWORD=replace-with-development-password
MYSQL_ROOT_PASSWORD=replace-with-development-root-password
MYSQL_TEST_DATABASE=portfolio_test
MYSQL_TEST_USER=portfolio_test
MYSQL_TEST_PASSWORD=replace-with-test-password
MYSQL_TEST_ROOT_PASSWORD=replace-with-test-root-password
```

Extend `.gitignore` with anchored rules for root and application `.env*` files plus negations for `*.example`, `web/node_modules`, `web/.next`, `web/coverage`, `api/vendor`, Laravel cache/log/session output, and `api/public/storage`. Preserve `.worktrees/` and every existing Phase 2 asset rule.

Create the root `.dockerignore` for the API image's root build context. Exclude `.git`, `.worktrees`, all real `.env*` while re-including examples only when a future build genuinely needs them, `docs`, `web`, `api`, editor files, and generated dependencies/caches. Do not exclude `infra/docker/api`.

- [ ] **Step 4: Document explicit ownership**

Create `docs/ENVIRONMENT.md` with a table listing each variable, its root/Compose owner, recipient service, secret status, and browser exposure. State explicitly that root `.env` is interpolation input rather than automatic container injection; web receives no `MYSQL_*`; `INTERNAL_API_ORIGIN=http://api` is injected only into web server runtime; Cloudflare variables do not belong to the repository.

Include PowerShell `Copy-Item .env.example .env` and Bash `cp .env.example .env`, with a warning to replace every `replace-with-` value and never commit `.env`.

- [ ] **Step 5: Run the validator and ignore checks**

Run:

```powershell
node infra/validation/validate-repository.mjs
git check-ignore .env web/.env.local api/.env api/public/storage web/node_modules
git check-ignore .env.example
```

Expected: validator PASS; the first five paths are ignored; `.env.example` produces no match/exit code 1 because it remains trackable.

- [ ] **Step 6: Review checkpoint and commit**

Review against spec sections 4, 9, 13, and 14. Confirm no real secret, Cloudflare placeholder, application scaffold, or extra worktree was created. After fixes, run `git diff --check` and commit:

```powershell
git add .editorconfig .env.example .dockerignore .gitignore docs/ENVIRONMENT.md infra/validation/validate-repository.mjs
git commit -m "chore: establish workspace environment contract"
```

### Task 2: PHP 8.5 Apache image and Laravel 13 skeleton

**Files:**
- Create: `infra/docker/api/Dockerfile`
- Create: `infra/docker/api/apache-vhost.conf`
- Create: `infra/docker/api/entrypoint.sh`
- Create: `api/**` through Laravel's official skeleton
- Modify: `api/.env.example`
- Modify: `api/phpunit.xml`
- Test: `api/tests/Feature/HealthTest.php`

**Interfaces:**
- Consumes: root `APP_KEY` and later Compose-injected Laravel/database variables.
- Produces: reusable image target `portfolio-api-dev`; internal Apache port `80`; Laravel `/up`; writable `/var/www/html/storage` and `/var/www/html/bootstrap/cache`.

- [ ] **Step 1: Create the explicit API container boundary**

Use `php:8.5.8-apache-bookworm` as the base. Install Composer from `composer:2`, `curl`, `unzip`, MySQL client, and libraries/extensions required by Laravel/Filament: `pdo_mysql`, `mbstring`, `intl`, `bcmath`, `zip`, and `opcache`. Enable `rewrite`, copy the vhost, set `WORKDIR /var/www/html`, expose `80`, and use the entrypoint below before `apache2-foreground`:

```dockerfile
FROM php:8.5.8-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl default-mysql-client libicu-dev libonig-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring opcache pdo_mysql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY infra/docker/api/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY infra/docker/api/entrypoint.sh /usr/local/bin/portfolio-api-entrypoint

RUN chmod +x /usr/local/bin/portfolio-api-entrypoint

WORKDIR /var/www/html
EXPOSE 80
ENTRYPOINT ["portfolio-api-entrypoint"]
CMD ["apache2-foreground"]
```

```sh
#!/bin/sh
set -eu
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
exec "$@"
```

The vhost must contain:

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public>
        AllowOverride All
        Options FollowSymLinks
        Require all granted
        FallbackResource /index.php
    </Directory>
    ErrorLog /proc/self/fd/2
    CustomLog /proc/self/fd/1 combined
</VirtualHost>
```

- [ ] **Step 2: Build the bootstrap image and verify PHP/Apache capabilities**

Run:

```powershell
docker build --tag portfolio-api-dev --file infra/docker/api/Dockerfile .
docker run --rm portfolio-api-dev php -r "exit(PHP_VERSION_ID >= 80500 ? 0 : 1);"
docker run --rm portfolio-api-dev php -m
docker run --rm portfolio-api-dev apache2ctl -M
```

Expected: build succeeds; PHP check exits 0; module output contains `pdo_mysql`, `mbstring`, `intl`, `bcmath`, `zip`; Apache output contains `rewrite_module`.

- [ ] **Step 3: Generate Laravel 13 without a second Git repository**

From the Phase 3 worktree root:

```powershell
$repoPath = (Get-Location).Path
New-Item -ItemType Directory -Force api | Out-Null
docker run --rm --mount "type=bind,source=$repoPath\api,target=/var/www/html" portfolio-api-dev composer create-project laravel/laravel:^13.0 . --no-interaction
if (Test-Path api\.git) { throw 'Laravel scaffold created a nested Git repository' }
```

Bash equivalent for execution from Ubuntu WSL2:

```bash
mkdir -p api
docker run --rm --mount "type=bind,source=$PWD/api,target=/var/www/html" portfolio-api-dev composer create-project 'laravel/laravel:^13.0' . --no-interaction
test ! -e api/.git
```

Expected: `api/artisan`, `api/composer.json`, and `api/composer.lock` exist; no `api/.git` exists; `composer show laravel/framework` resolves 13.x. Do not create `api/.dockerignore`; the API image uses the verified root build context and root `.dockerignore`.

- [ ] **Step 4: Remove SQLite as the default test database and write the health test**

In `api/phpunit.xml`, set `APP_ENV=testing` but do not define SQLite or `DB_CONNECTION=sqlite`; database values will be injected explicitly by `api-test` in Task 4. Set the application example database host to `mysql` with placeholder-only values.

Create `api/tests/Feature/HealthTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

final class HealthTest extends TestCase
{
    public function test_up_reports_application_boot_without_database_query(): void
    {
        $this->get('/up')->assertOk();
    }
}
```

- [ ] **Step 5: Verify Laravel boot, lockfile, front controller, and permissions**

Run the container with `APP_KEY` set to a temporary generated value and the source mounted, then execute:

```powershell
$repoPath = (Get-Location).Path
$appKey = docker run --rm --mount "type=bind,source=$repoPath\api,target=/var/www/html" portfolio-api-dev php artisan key:generate --show
docker run --rm --mount "type=bind,source=$repoPath\api,target=/var/www/html" --env "APP_KEY=$appKey" portfolio-api-dev php artisan test --filter=HealthTest
docker run --rm --mount "type=bind,source=$repoPath\api,target=/var/www/html" --env "APP_KEY=$appKey" portfolio-api-dev sh -lc "test -w storage && test -w bootstrap/cache && apache2ctl -S"
```

Expected: health test PASS without a database; both directories are writable; Apache reports `/var/www/html/public` vhost behavior; `composer.lock` is present.

- [ ] **Step 6: Review checkpoint and commit**

Review PHP extensions against resolved Laravel/Filament requirements, confirm Apache—not Caddy—owns `public/index.php`, confirm no runtime dependency installation and no nested Git metadata. After fixes, rerun Step 5 and commit:

```powershell
git add api infra/docker/api
git commit -m "feat: initialize Laravel Apache foundation"
```

### Task 3: Next.js 16.2.x workspace and deterministic frontend tooling

**Files:**
- Create: `web/**` through `create-next-app`
- Modify: `web/package.json`
- Modify: `web/tsconfig.json`
- Create: `web/vitest.config.ts`
- Create: `web/vitest.setup.ts`
- Create: `web/Dockerfile`
- Create: `web/.dockerignore`
- Create: `web/src/app/health/route.ts`
- Test: `web/src/app/health/route.test.ts`

**Interfaces:**
- Consumes: pnpm 11.20.0 and Node.js 24 LTS.
- Produces: scripts `dev`, `build`, `lint`, `format`, `format:check`, `typecheck`, `test`, `test:run`; internal web port `3000`; `GET /health` returning `{status:'ok'}`.

- [ ] **Step 1: Resolve and record the latest stable 16.2.x generator**

Run in PowerShell:

```powershell
corepack enable
corepack prepare pnpm@11.20.0 --activate
$versions = pnpm view next@16.2 version --json | ConvertFrom-Json
$nextVersion = @($versions)[-1]
if ($nextVersion -notmatch '^16\.2\.\d+$') { throw "Unexpected Next version: $nextVersion" }
$nextVersion
```

Expected: a stable `16.2.x` version with no `-canary`, `-rc`, or `16.3` segment.

- [ ] **Step 2: Generate the independent web application**

Run:

```powershell
pnpm dlx "create-next-app@$nextVersion" web --typescript --tailwind --eslint --app --src-dir --import-alias "@/*" --use-pnpm --yes
```

Confirm there is no root `package.json`, no root pnpm workspace, and no nested `.git`. Set `web/package.json` to include `"packageManager": "pnpm@11.20.0"` and pin Next to the exact resolved 16.2.x selected by the lockfile.

- [ ] **Step 3: Add formatting and test tooling**

From `web/`, run:

```powershell
pnpm add next-intl@^4.0.0
pnpm add --save-dev prettier prettier-plugin-tailwindcss vitest jsdom @testing-library/react @testing-library/jest-dom
```

Add scripts:

```json
{
  "format": "prettier --write .",
  "format:check": "prettier --check .",
  "typecheck": "tsc --noEmit",
  "test": "vitest",
  "test:run": "vitest run"
}
```

Configure Vitest for `jsdom`, `@/*` alias resolution, and `vitest.setup.ts` importing `@testing-library/jest-dom/vitest`.

- [ ] **Step 4: Write the failing frontend health test**

Create `web/src/app/health/route.test.ts`:

```ts
import {describe, expect, it} from 'vitest';
import {GET} from './route';

describe('GET /health', () => {
  it('returns the frontend-owned health response', async () => {
    const response = GET();
    await expect(response.json()).resolves.toEqual({status: 'ok'});
    expect(response.status).toBe(200);
  });
});
```

Run: `pnpm --dir web test:run -- src/app/health/route.test.ts`

Expected: FAIL because `route.ts` does not exist.

- [ ] **Step 5: Implement health route and Docker image**

Create the route:

```ts
export function GET(): Response {
  return Response.json({status: 'ok'});
}
```

Create `web/Dockerfile` from `node:24.18.0-bookworm-slim`, enable/prepare pnpm 11.20.0, set `/app`, expose 3000, and default to `pnpm dev --hostname 0.0.0.0`. Do not copy dependencies into a runtime volume or install on startup. Make `.dockerignore` exclude `.env*`, `.next`, `node_modules`, coverage, Git metadata, and unrelated docs.

```dockerfile
FROM node:24.18.0-bookworm-slim

RUN corepack enable && corepack prepare pnpm@11.20.0 --activate

WORKDIR /app
EXPOSE 3000
CMD ["pnpm", "dev", "--hostname", "0.0.0.0"]
```

- [ ] **Step 6: Verify toolchain and clean build baseline**

Run:

```powershell
pnpm --dir web test:run
pnpm --dir web lint
pnpm --dir web typecheck
pnpm --dir web format:check
pnpm --dir web build
pnpm --dir web list next react next-intl --depth 0
docker build --tag portfolio-web-dev --file web/Dockerfile web
```

Expected: all checks and image build PASS; resolved Next is stable 16.2.x, React 19.2.x, next-intl 4.x; `web/pnpm-lock.yaml` records exact versions.

- [ ] **Step 7: Review checkpoint and commit**

Review strict TypeScript, package-manager locality, lack of preview packages, absence of a root workspace, and absence of Phase 5 UI. After fixes, repeat Step 6 and commit:

```powershell
git add web
git commit -m "feat: initialize Next.js frontend workspace"
```

### Task 4: Compose topology, health dependencies, and disposable test database

**Files:**
- Create: `compose.yaml`
- Create: `infra/caddy/Caddyfile`
- Modify: `infra/validation/validate-repository.mjs`
- Test: rendered Compose configuration and runtime topology

**Interfaces:**
- Consumes: `web/Dockerfile`, `infra/docker/api/Dockerfile`, root environment names, web `/health`, Laravel `/up`.
- Produces: services `gateway`, `web`, `api`, `mysql`, `mysql-test`, `api-test`; networks `front`, `data`, `test`; volumes `web_node_modules`, `api_vendor`, `mysql_data`, `api_public_media`; gateway health path `/__gateway/health`.

- [ ] **Step 1: Extend the repository validator with failing Compose assertions**

Add assertions that `compose.yaml` exists, contains exactly the six approved service names, has `127.0.0.1:${GATEWAY_PORT:-8000}:80` only on `gateway`, gives `mysql-test` and `api-test` the `test` profile, gives `api-test` a `service_healthy` dependency on `mysql-test`, contains no `cloudflared`, `network_mode: host`, `3306:` publication, or sleep command, and declares the four named volumes/three networks.

Run: `node infra/validation/validate-repository.mjs`

Expected: FAIL with `Missing compose.yaml`.

- [ ] **Step 2: Create the six-service Compose project with explicit injection**

Create `compose.yaml` with these exact boundaries:

- `gateway`: versioned Caddy 2 image, `front` only, read-only Caddyfile mount, `127.0.0.1:${GATEWAY_PORT:-8000}:80`, healthcheck against `/__gateway/health`.
- `web`: build `web/Dockerfile`, bind `./web:/app`, volume `web_node_modules:/app/node_modules`, `front` only, `INTERNAL_API_ORIGIN=http://api` as its only API-origin variable, healthcheck `http://localhost:3000/health`.
- `api`: build the root context with `infra/docker/api/Dockerfile`, bind `./api:/var/www/html`, volume `api_vendor:/var/www/html/vendor`, volume `api_public_media:/var/www/html/storage/app/public`, networks `front` and `data`, explicit Laravel and development `DB_*` values only, healthcheck `http://localhost/up`.
- `mysql`: `mysql:8.4`, `data` only, explicit development `MYSQL_*`, `mysql_data:/var/lib/mysql`, `mysqladmin ping` healthcheck, no published port.
- `mysql-test`: `mysql:8.4`, profiles `[test]`, `test` only, explicit test `MYSQL_*`, `tmpfs: /var/lib/mysql`, `mysqladmin ping` healthcheck, no published port.
- `api-test`: same build/image as `api`, profiles `[test]`, bind source and `api_vendor`, `test` only, `APP_ENV=testing`, `DB_HOST=mysql-test`, explicit test `DB_*`, command `php artisan test`, restart `no`, and `depends_on.mysql-test.condition: service_healthy`.

Do not use a root `env_file` on every service. Interpolate individual root values into only the service `environment` entries that own them. Development services must not depend on profile-only services.

Use this concrete Compose structure:

```yaml
services:
  gateway:
    image: caddy:2.11.4-alpine
    ports:
      - "${GATEWAY_HOST:-127.0.0.1}:${GATEWAY_PORT:-8000}:80"
    volumes:
      - ./infra/caddy/Caddyfile:/etc/caddy/Caddyfile:ro
    networks: [front]
    depends_on:
      web: {condition: service_healthy}
      api: {condition: service_healthy}
    healthcheck:
      test: [CMD, wget, -q, --spider, http://localhost/__gateway/health]
      interval: 5s
      timeout: 3s
      retries: 20

  web:
    build: {context: ./web, dockerfile: Dockerfile}
    volumes:
      - ./web:/app
      - web_node_modules:/app/node_modules
    environment:
      INTERNAL_API_ORIGIN: http://api
    networks: [front]
    healthcheck:
      test: [CMD, wget, -q, --spider, http://localhost:3000/health]
      interval: 5s
      timeout: 3s
      retries: 20

  api:
    image: portfolio-api-dev
    build: {context: ., dockerfile: infra/docker/api/Dockerfile}
    volumes:
      - ./api:/var/www/html
      - api_vendor:/var/www/html/vendor
      - api_public_media:/var/www/html/storage/app/public
    environment:
      APP_ENV: local
      APP_DEBUG: "true"
      APP_URL: ${APP_URL}
      APP_KEY: ${APP_KEY}
      LOG_CHANNEL: stderr
      DB_CONNECTION: mysql
      DB_HOST: mysql
      DB_PORT: "3306"
      DB_DATABASE: ${MYSQL_DATABASE}
      DB_USERNAME: ${MYSQL_USER}
      DB_PASSWORD: ${MYSQL_PASSWORD}
      SESSION_DRIVER: file
      CACHE_STORE: file
      QUEUE_CONNECTION: sync
    networks: [front, data]
    healthcheck:
      test: [CMD, curl, --fail, --silent, http://localhost/up]
      interval: 5s
      timeout: 3s
      retries: 20

  mysql:
    image: mysql:8.4
    environment:
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    networks: [data]
    healthcheck:
      test: [CMD-SHELL, 'mysqladmin ping -h localhost -u"$${MYSQL_USER}" -p"$${MYSQL_PASSWORD}" --silent']
      interval: 5s
      timeout: 5s
      retries: 30

  mysql-test:
    image: mysql:8.4
    profiles: [test]
    environment:
      MYSQL_DATABASE: ${MYSQL_TEST_DATABASE}
      MYSQL_USER: ${MYSQL_TEST_USER}
      MYSQL_PASSWORD: ${MYSQL_TEST_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${MYSQL_TEST_ROOT_PASSWORD}
    tmpfs:
      - /var/lib/mysql
    networks: [test]
    healthcheck:
      test: [CMD-SHELL, 'mysqladmin ping -h localhost -u"$${MYSQL_USER}" -p"$${MYSQL_PASSWORD}" --silent']
      interval: 3s
      timeout: 5s
      retries: 40

  api-test:
    image: portfolio-api-dev
    build: {context: ., dockerfile: infra/docker/api/Dockerfile}
    profiles: [test]
    volumes:
      - ./api:/var/www/html
      - api_vendor:/var/www/html/vendor
    environment:
      APP_ENV: testing
      APP_DEBUG: "false"
      APP_URL: http://localhost:8000
      APP_KEY: ${APP_KEY}
      LOG_CHANNEL: stderr
      DB_CONNECTION: mysql
      DB_HOST: mysql-test
      DB_PORT: "3306"
      DB_DATABASE: ${MYSQL_TEST_DATABASE}
      DB_USERNAME: ${MYSQL_TEST_USER}
      DB_PASSWORD: ${MYSQL_TEST_PASSWORD}
      SESSION_DRIVER: array
      CACHE_STORE: array
      QUEUE_CONNECTION: sync
    command: php artisan test
    restart: "no"
    depends_on:
      mysql-test: {condition: service_healthy}
    networks: [test]

networks:
  front: {}
  data: {}
  test: {}

volumes:
  web_node_modules: {}
  api_vendor: {}
  mysql_data: {}
  api_public_media: {}
```

- [ ] **Step 3: Add the minimal pre-inventory Caddy configuration**

Create `infra/caddy/Caddyfile` with global auto-HTTPS disabled for local HTTP, a Caddy-owned `/__gateway/health` response, explicit conceptual matchers for `/api`, `/api/*`, `/admin`, `/admin/*`, `/up`, and `/storage/*` to `api:80`, and a final frontend handler to `web:3000`.

Do not add guessed Filament asset prefixes or a fixed Livewire hash. Mark the config as provisional until Task 11's installed-route/traffic inventory, but do not use placeholder tokens.

Initial file content:

```caddyfile
{
    auto_https off
}

:80 {
    handle /__gateway/health {
        respond "ok" 200
    }

    @laravel path /api /api/* /admin /admin/* /up /storage/*
    handle @laravel {
        reverse_proxy api:80
    }

    handle {
        reverse_proxy web:3000
    }
}
```

- [ ] **Step 4: Validate interpolation without exposing real secrets**

Run only with the placeholder example:

```powershell
docker compose --env-file .env.example config --quiet
docker compose --env-file .env.example config --services
docker compose --env-file .env.example --profile test config --services
node infra/validation/validate-repository.mjs
```

Expected: default services are `gateway`, `web`, `api`, `mysql`; profile output additionally includes `mysql-test`, `api-test`; validation passes. Do not paste fully rendered environment values into reports.

- [ ] **Step 5: Prove deterministic cold dependency bootstrap**

Create local `.env` from the example, replace all secrets, and generate `APP_KEY` with the API image. Then run:

```powershell
docker compose build web api
docker compose run --rm --no-deps api composer install --no-interaction --prefer-dist
docker compose run --rm --no-deps web pnpm install --frozen-lockfile
$appKey = (docker compose run --rm --no-deps api php artisan key:generate --show | Select-Object -Last 1).Trim()
(Get-Content .env) -replace '^APP_KEY=.*$', "APP_KEY=$appKey" | Set-Content .env
Remove-Variable appKey
docker compose run --rm --no-deps web test -d node_modules/next
docker compose run --rm --no-deps api test -f vendor/autoload.php
```

Expected: fresh named dependency volumes populate once; the last two commands exit 0. Inspect `docker compose config` to confirm normal `web`/`api` startup commands contain neither install command.

- [ ] **Step 6: Start development services and inspect isolation**

Run:

```powershell
docker compose up -d
docker compose ps
docker compose exec -T api curl --fail http://localhost/up
docker compose exec -T web wget -qO- http://localhost:3000/health
docker compose exec -T api getent hosts mysql
docker network inspect portfolio_front
docker compose port gateway 80
docker compose port web 3000
docker compose port api 80
docker compose port mysql 3306
```

Expected: all four development services become healthy; API can resolve `mysql`; inspection shows gateway/web/api on `portfolio_front` but no MySQL container; only gateway returns `127.0.0.1:8000`; the other three `port` commands produce no mapping.

- [ ] **Step 7: Prove test health dependency and disposability**

Run:

```powershell
docker compose --profile test run --rm api-test php artisan about --only=environment
docker compose --profile test ps -a
docker compose --profile test down --remove-orphans
docker volume ls --format '{{.Name}}' | Select-String 'mysql.*test'
```

Expected: `api-test` starts only after healthy `mysql-test`, reports `testing`, exits 0, and no named test database volume exists. Confirm `mysql-test` has no host port.

- [ ] **Step 8: Review checkpoint and commit**

Review service/network matrices, explicit variable ownership, no automatic migrations/seeds/admin creation, no sleep, no host networking, and no production/Cloudflare config. After fixes, repeat Steps 4, 6, and 7; then commit without `.env`:

```powershell
git add compose.yaml infra/caddy/Caddyfile infra/validation/validate-repository.mjs
git commit -m "feat: add isolated Docker development and test topology"
```

### Task 5: Localized static App Router foundation

**Files:**
- Create: `web/src/i18n/routing.ts`
- Create: `web/src/i18n/request.ts`
- Create: `web/src/i18n/resolve-locale.ts`
- Create: `web/src/i18n/navigation.ts`
- Create: `web/src/proxy.ts`
- Modify: `web/next.config.ts`
- Delete: generated `web/src/app/layout.tsx`
- Delete: generated `web/src/app/page.tsx`
- Create: `web/src/app/[locale]/layout.tsx`
- Create: `web/src/app/[locale]/page.tsx`
- Create: `web/src/components/language-switcher.tsx`
- Create: `web/messages/es.json`
- Create: `web/messages/en.json`
- Test: `web/src/i18n/resolve-locale.test.ts`
- Test: `web/src/app/[locale]/page.test.tsx`

**Interfaces:**
- Produces: `locales = ['es','en'] as const`, type `Locale`, `resolveLocale(cookieValue, acceptLanguage): Locale`, explicit cookie name `portfolio_locale`, static params `[{locale:'es'},{locale:'en'}]`.

- [ ] **Step 1: Write failing locale-resolution tests**

Create `resolve-locale.test.ts` covering: valid explicit `es`/`en` wins; invalid cookie is ignored; supported `Accept-Language` with q-values wins; unsupported/missing headers fall back to `es`; passive resolution returns only a locale and never a cookie mutation.

```ts
expect(resolveLocale('en', 'es-AR,es;q=0.9')).toBe('en');
expect(resolveLocale('invalid', 'en-US,en;q=0.9')).toBe('en');
expect(resolveLocale(undefined, 'fr-FR,fr;q=0.9')).toBe('es');
```

Run: `pnpm --dir web test:run -- src/i18n/resolve-locale.test.ts`

Expected: FAIL because `resolveLocale` does not exist.

- [ ] **Step 2: Implement centralized locale resolution and next-intl configuration**

Define the exact locale tuple/default/cookie name in `routing.ts`; implement a small q-value-aware resolver in `resolve-locale.ts`; use `getRequestConfig` plus `hasLocale` in `request.ts`; export localized navigation helpers from `navigation.ts`.

Implement `proxy.ts` so `/` reads the valid explicit cookie, then `Accept-Language`, then Spanish, and redirects to `/${locale}`. For already localized routes, delegate to next-intl middleware with locale-cookie persistence disabled. Its matcher must exclude `/api`, `/admin`, `/up`, `/health`, `/_next`, and dot-containing asset paths. Only the client language switcher writes `portfolio_locale` with `Path=/; Max-Age=31536000; SameSite=Lax` after an explicit selection.

Wrap `next.config.ts` with `createNextIntlPlugin('./src/i18n/request.ts')`. Remove the generated root page/layout so `[locale]/layout.tsx` is the HTML root layout while the standalone `/health` route handler remains middleware-independent.

- [ ] **Step 3: Write failing static-page tests**

Test `generateStaticParams()` returns exactly `es` and `en`; invalid route params call `notFound`; both message files expose identical technical keys; the page is a Server Component and renders the locale plus minimal foundation copy without CMS content.

Run: `pnpm --dir web test:run -- src/app/[locale]/page.test.tsx`

Expected: FAIL until localized layout/page exist.

- [ ] **Step 4: Implement the minimal localized shell**

Use `setRequestLocale(locale)` in the localized layout/page, export `generateStaticParams`, load only technical messages, and add `NextIntlClientProvider` at the smallest useful boundary. Build a minimal accessible page containing heading, current locale, language switcher, theme control slot, and API-status slot—no Phase 5 sections or duplicated approved CMS prose.

- [ ] **Step 5: Prove routing tests and static build independence**

Stop the stack first, then run:

```powershell
docker compose down
docker compose run --rm --no-deps web pnpm test:run
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
Remove-Item Env:INTERNAL_API_ORIGIN -ErrorAction SilentlyContinue
docker compose run --rm --no-deps web pnpm build
```

Expected: all checks PASS; build output identifies `/es` and `/en` as prerendered/static; it makes no request to Caddy or Laravel.

- [ ] **Step 6: Review checkpoint and commit**

Review cookie precedence, explicit-only persistence, static rendering, Server Component default, bilingual technical-key parity, and Phase 5 scope exclusion. After fixes, rerun Step 5 and commit:

```powershell
git add web/src web/messages web/next.config.* web/package.json web/pnpm-lock.yaml
git commit -m "feat: add static localized frontend foundation"
```

### Task 6: Pre-paint theme foundation

**Files:**
- Create: `web/src/theme/theme.ts`
- Create: `web/src/theme/bootstrap.ts`
- Create: `web/src/components/theme-switcher.tsx`
- Modify: `web/src/app/[locale]/layout.tsx`
- Modify: `web/src/app/globals.css`
- Test: `web/src/theme/theme.test.ts`
- Test: `web/src/components/theme-switcher.test.tsx`

**Interfaces:**
- Produces: key `portfolio_theme`, type `Theme = 'light' | 'dark'`, `readStoredTheme(storage): Theme | null`, `getEffectiveTheme(stored, prefersDark): Theme`, `applyTheme(theme, root): void`, stable inline bootstrap source.

- [ ] **Step 1: Write failing theme-resolution tests**

Cover no stored preference with light/dark system values, explicit light/dark overrides, invalid value fallback, root `data-theme` mutation, persistence across a re-created control, and initialization from the already-applied DOM attribute.

Run: `pnpm --dir web test:run -- src/theme/theme.test.ts src/components/theme-switcher.test.tsx`

Expected: FAIL because theme modules/control do not exist.

- [ ] **Step 2: Implement dependency-free theme primitives**

In `theme.ts`, accept a `Pick<Storage, 'getItem'|'setItem'|'removeItem'>` and a root supporting `dataset`. Return only validated `light`/`dark`, and remove invalid stored values before using the system preference. Do not import React or application logic.

In `bootstrap.ts`, export one stable string that catches storage-denial errors, reads only `portfolio_theme`, checks `matchMedia('(prefers-color-scheme: dark)')`, and sets `document.documentElement.dataset.theme` before paint.

- [ ] **Step 3: Integrate the isolated bootstrap and control**

Place the inline bootstrap in the root HTML before interactive content. Add `suppressHydrationWarning` only to `<html>` because that attribute can intentionally differ. The Client Component reads `document.documentElement.dataset.theme` for its initial state, writes only explicit values to local storage, updates the root, and exposes accessible pressed/current-state text.

CSS must use Phase 2 semantic tokens for both `[data-theme='light']` and `[data-theme='dark']`, with a `prefers-color-scheme` fallback before script execution. Do not add animation libraries.

- [ ] **Step 4: Verify unit behavior and static build**

Run with the Docker stack stopped:

```powershell
docker compose run --rm --no-deps web pnpm test:run -- src/theme/theme.test.ts src/components/theme-switcher.test.tsx
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
docker compose run --rm --no-deps web pnpm build
rg -n "cookies\(|next-themes|suppressHydrationWarning" web/src
```

Expected: tests/build PASS; no `cookies()` or `next-themes`; exactly one intentional `suppressHydrationWarning` on root HTML.

- [ ] **Step 5: Review checkpoint and commit**

Review bootstrap determinism/CSP stability, no-flash ordering, invalid-storage safety, root-only suppression, accessibility, and no dynamic rendering. After fixes, rerun Step 4 and commit:

```powershell
git add web/src/theme web/src/components/theme-switcher.tsx web/src/app
git commit -m "feat: add static pre-paint theme foundation"
```

### Task 7: Exact typed API envelope and runtime-only connectivity

**Files:**
- Create: `web/src/lib/api/types.ts`
- Create: `web/src/lib/api/client.ts`
- Create: `web/src/components/api-status.tsx`
- Modify: `web/src/app/[locale]/page.tsx`
- Test: `web/src/lib/api/client.test.ts`
- Test: `web/src/components/api-status.test.tsx`

**Interfaces:**
- Produces: `ApiSuccess<T> = {data:T}`, `ApiError = {error:{code:string;message:string;details:Record<string,string[]>}}`, `ApiResult<T>`, `ApiClientError`, `requestApi<T>(path, options)`, server origin `INTERNAL_API_ORIGIN`, browser-relative `/api` behavior.

- [ ] **Step 1: Write failing envelope/client tests**

Test exact version success, exact handled error, HTTP error propagation, network failure, non-JSON response, neither/both top-level members, wrong field types, and browser/server origin selection. Assert server requests use `http://api` only when explicitly called at runtime; browser requests stay relative.

```ts
await expect(requestApi<VersionData>('/v1', {fetchImpl})).resolves.toEqual({
  data: {status: 'ok', version: 'v1'}
});
```

Run: `pnpm --dir web test:run -- src/lib/api/client.test.ts`

Expected: FAIL because client/types do not exist.

- [ ] **Step 2: Implement strict runtime validation**

Implement small type guards without adding a schema package. `details` must be an object whose values are arrays of strings; success and error are mutually exclusive; HTTP status remains on `ApiClientError` and is not expected in the body. Reject malformed response shapes rather than treating them as empty success.

Origin rules (the client appends `/api` to the server origin and uses `/api` directly in the browser):

```ts
const base = typeof window === 'undefined'
  ? `${requireServerOrigin(process.env.INTERNAL_API_ORIGIN)}/api`
  : '/api';
```

Never reference `NEXT_PUBLIC_INTERNAL_API_ORIGIN` and never put database variables in this module.

- [ ] **Step 3: Add an optional browser-only connectivity control**

Implement `api-status.tsx` as a small Client Component that performs no request during render/build. A user-triggered “Check API” action calls same-origin `/api/v1`, displays `ok/v1` on success, and shows a safe error state otherwise. This is technical Phase 3 UI, not portfolio content.

- [ ] **Step 4: Verify API tests and backend-independent build**

Run:

```powershell
docker compose down
docker compose run --rm --no-deps web pnpm test:run -- src/lib/api/client.test.ts src/components/api-status.test.tsx
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
$env:INTERNAL_API_ORIGIN='http://unreachable.invalid'
docker compose run --rm --no-deps --env INTERNAL_API_ORIGIN web pnpm build
Remove-Item Env:INTERNAL_API_ORIGIN
rg -n "http://api|MYSQL_" web/.next/static
```

Expected: tests/build PASS without any live request; the final grep reports no matches (exit 1) because `.next/static` contains neither `http://api` nor any `MYSQL_` value.

- [ ] **Step 5: Review checkpoint and commit**

Review exact envelope discrimination, safe errors, server/browser separation, no build-time fetch, and no extra dependency. After fixes, repeat Step 4 and commit:

```powershell
git add web/src/lib/api web/src/components/api-status.tsx web/src/app/[locale]/page.tsx
git commit -m "feat: add typed runtime API boundary"
```

### Task 8: Laravel API v1 contract, errors, rate limiting, CORS, and logs

**Files:**
- Create/Modify: `api/routes/api.php`
- Modify: `api/bootstrap/app.php`
- Modify: `api/app/Providers/AppServiceProvider.php`
- Create: `api/app/Http/Resources/ApiVersionResource.php`
- Create: `api/app/Http/Responses/ApiErrorResponse.php`
- Modify: `api/config/cors.php`
- Modify: `api/config/logging.php`
- Test: `api/tests/Feature/Api/VersionTest.php`
- Test: `api/tests/Feature/Api/ErrorEnvelopeTest.php`
- Test: `api/tests/Feature/Api/RateLimitTest.php`
- Test: `api/tests/Feature/DatabaseConnectionTest.php`

**Interfaces:**
- Produces: `GET /api/v1`; success `{"data":{"status":"ok","version":"v1"}}`; error `{"error":{"code":string,"message":string,"details":object}}`; named limiter `public-api`.

- [ ] **Step 1: Write failing MySQL-backed API tests**

Use `RefreshDatabase`. Assert exact JSON and top-level keys for `GET /api/v1`; assert `/api/v1/missing` returns JSON error with `code=not_found`, safe message, empty details, and 404; exhaust the named limiter and assert 429 `rate_limited`; assert the active database host is `mysql-test` and a simple MySQL query succeeds.

Run:

```powershell
docker compose --profile test run --rm api-test php artisan test --testsuite=Feature --filter='VersionTest|ErrorEnvelopeTest|RateLimitTest|DatabaseConnectionTest'
```

Expected: FAIL because API routes/contracts do not exist; `api-test` waits for healthy `mysql-test` without a sleep.

- [ ] **Step 2: Implement exact Resource and error response primitives**

`ApiVersionResource::toArray()` returns only `['status' => 'ok', 'version' => 'v1']`; disable unintended outer wrapping only if necessary to preserve exactly one `data` member. `ApiErrorResponse` exposes one constructor/factory taking machine code, safe message, details array, and HTTP status, and returns exactly the approved `error` object.

Wire `routes/api.php` under `/api/v1` and configure API-route exception rendering in `bootstrap/app.php` so API 404 and throttling use `ApiErrorResponse`. Do not alter Filament HTML exception behavior.

- [ ] **Step 3: Configure the named limiter, restrictive CORS, and stderr logs**

Define and attach the limiter with this behavior:

```php
RateLimiter::for('public-api', static fn (Request $request) =>
    Limit::perMinute(60)->by($request->ip())
);
```

Configure CORS paths only for `api/*`, methods/headers required by the API, credentials off, and `allowed_origins` containing `env('APP_URL', 'http://localhost:8000')`; never use `*` with credentials.

Set Laravel's default container log channel to stderr through the existing stack/channel configuration. Keep `/up` built-in and database-independent.

- [ ] **Step 4: Run exact API and operational tests**

Run:

```powershell
docker compose --profile test run --rm api-test php artisan test --testsuite=Feature
docker compose exec -T api php artisan route:list --path=api --json
docker compose exec -T api curl --fail http://localhost/up
docker compose logs --no-color api | Select-String -NotMatch 'MYSQL_PASSWORD|APP_KEY'
```

Expected: tests PASS on `mysql-test`; inventory contains `/api/v1`; `/up` succeeds; logs expose no configured secret values.

- [ ] **Step 5: Review checkpoint and commit**

Review exact body shapes, mutually exclusive envelope members, status-code ownership, API-only error normalization, limiter determinism, same-origin CORS, `/up` independence, and no Phase 4 resources. After fixes, rerun Steps 1 and 4 and commit:

```powershell
git add api/app api/bootstrap/app.php api/config api/routes/api.php api/tests/Feature
git commit -m "feat: establish versioned Laravel API contract"
```

### Task 9: Filament authentication, `is_admin`, and interactive bootstrap

**Files:**
- Modify: `api/composer.json`
- Modify: `api/composer.lock`
- Create: `api/app/Providers/Filament/AdminPanelProvider.php` through the official installer
- Modify: `api/database/migrations/0001_01_01_000000_create_users_table.php`
- Modify: `api/app/Models/User.php`
- Create: `api/app/Console/Commands/BootstrapAdmin.php`
- Test: `api/tests/Feature/Admin/PanelAccessTest.php`
- Test: `api/tests/Feature/Admin/PanelLivewireTest.php`
- Test: `api/tests/Feature/Console/BootstrapAdminTest.php`

**Interfaces:**
- Produces: `/admin`, `User::$casts['is_admin']='boolean'`, `User::canAccessPanel(Panel): bool`, Artisan command `portfolio:bootstrap-admin`.

- [ ] **Step 1: Install exact compatible Filament 5/Livewire 4 resolution**

Run through the API service so Composer uses PHP 8.5:

```powershell
docker compose run --rm --no-deps api composer require filament/filament:^5.0 --with-all-dependencies --no-interaction
docker compose run --rm --no-deps api php artisan filament:install --panels --no-interaction
docker compose run --rm --no-deps api composer show filament/filament livewire/livewire
```

Expected: stable Filament 5.x and Livewire 4.x are locked; one panel provider exists; no CMS Resource is generated.

- [ ] **Step 2: Write failing authorization tests**

Using `RefreshDatabase`, assert unauthenticated `/admin` redirects to Filament login; authenticated `is_admin=false` receives 403/denial; authenticated `is_admin=true` reaches the panel. Assert user factory defaults to false.

Run: `docker compose --profile test run --rm api-test php artisan test --filter=PanelAccessTest`

Expected: FAIL because `is_admin`/panel authorization is absent.

- [ ] **Step 3: Implement the minimal marker and panel authorization**

Add `is_admin` to the initial users migration as `$table->boolean('is_admin')->default(false);`. Add boolean casting and implement Filament's `FilamentUser` contract:

```php
public function canAccessPanel(Panel $panel): bool
{
    return $this->is_admin === true;
}
```

Do not add role tables, permission packages, enums, policies, or editorial concepts. Configure one `/admin` panel with authentication and no public registration.

- [ ] **Step 4: Write a failing real Filament/Livewire login-component test**

Create `PanelLivewireTest.php` using the installed Filament login component, not a project-only diagnostic component:

```php
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

Filament::setCurrentPanel(Filament::getPanel('admin'));
$admin = User::factory()->create([
    'is_admin' => true,
    'password' => Hash::make('phase-3-test-password'),
]);

Livewire::test(Login::class)
    ->set('data.email', $admin->email)
    ->set('data.password', 'phase-3-test-password')
    ->call('authenticate')
    ->assertHasNoErrors();
```

Also test that the same Livewire authentication attempt for `is_admin=false` does not establish an authorized panel session. Run:

```powershell
docker compose --profile test run --rm api-test php artisan test --filter=PanelLivewireTest
```

Expected: the first run fails until panel/current-panel/auth configuration is correct; the final test proves real Livewire 4 execution against disposable MySQL without browser automation.

- [ ] **Step 5: Write failing interactive-command tests**

Use Laravel console/prompt test facilities to simulate name, email, hidden password, and confirmation. Cover: successful user with `is_admin=true`; validation failure; mismatched confirmation; duplicate email; existing administrator/ambiguous state refusal; hash verification; plaintext sentinel absent from command output and captured logs; no existing user updated.

Run: `docker compose --profile test run --rm api-test php artisan test --filter=BootstrapAdminTest`

Expected: FAIL because command does not exist.

- [ ] **Step 6: Implement the interactive create-only command**

Use Laravel Prompts (`text`, `password`, `confirm` only as needed), validator-backed name/email/password rules, `Hash::make`, and one transaction. Do not accept a password CLI option or environment fallback. Refuse creation when the email exists or an administrator already exists; report a safe non-secret message and non-zero status. Create exactly one user with `is_admin=true`.

- [ ] **Step 7: Verify admin behavior and secret safety**

Run:

```powershell
docker compose --profile test run --rm api-test php artisan test --filter='PanelAccessTest|PanelLivewireTest|BootstrapAdminTest'
docker compose exec -T api php artisan list --raw | Select-String '^portfolio:bootstrap-admin'
docker compose exec -T api php artisan route:list --path=admin --json
rg -n "spatie.*permission|role_id|permissions" api/composer.* api/app api/database
```

Expected: tests PASS; command and admin routes exist; final grep finds no role/RBAC implementation.

- [ ] **Step 8: Review checkpoint and commit**

Review authentication boundary, default-false migration, real Livewire login coverage, create-only ambiguity handling, prompt secrecy, log safety, and absence of Phase 4 authorization. After fixes, rerun Step 7 and commit:

```powershell
git add api
git commit -m "feat: add minimal Filament administrator boundary"
```

### Task 10: Standard Laravel public-media persistence

**Files:**
- Modify: `api/config/filesystems.php` only if the generated default needs explicit environment-safe alignment
- Test: `api/tests/Feature/Media/PublicMediaTest.php`
- Modify: `compose.yaml` only if runtime mount ownership needs a verified correction
- Modify: `infra/validation/validate-repository.mjs`

**Interfaces:**
- Consumes: Laravel `public` disk at `storage/app/public`, `api_public_media` volume, standard `public/storage` link.
- Produces: HTTP ownership `/storage/*` served by Apache/Laravel public directory; persistence proof marker used only during verification.

- [ ] **Step 1: Write the failing public-media test**

Use `Storage::fake('public')` for application behavior and create a representative file. Assert the public disk root/URL contract and that a request through the standard storage link returns the file when the link exists. Do not create a media model.

Run: `docker compose --profile test run --rm api-test php artisan test --filter=PublicMediaTest`

Expected: FAIL until link/test fixture setup establishes the standard boundary.

- [ ] **Step 2: Preserve Laravel defaults and establish the link explicitly**

Keep `FILESYSTEM_DISK=local` for general runtime unless Laravel's generated defaults require otherwise; use the named `public` disk explicitly for public CMS media. Run:

```powershell
docker compose exec -T api php artisan storage:link
docker compose exec -T api sh -lc "test -L public/storage && test -d storage/app/public"
```

Do not add a Caddy filesystem mount, custom media controller, object storage, or media database entity.

- [ ] **Step 3: Verify automated behavior and volume persistence**

Run tests, then create a non-secret marker in the public-media volume, recreate only the API container, and fetch it through Apache/Caddy's known `/storage/*` ownership:

```powershell
docker compose --profile test run --rm api-test php artisan test --filter=PublicMediaTest
docker compose exec -T api sh -lc "printf 'phase-3-media' > storage/app/public/phase-3-media.txt"
docker compose up -d --force-recreate api
Invoke-WebRequest http://localhost:8000/storage/phase-3-media.txt | Select-Object -ExpandProperty Content
```

Expected: test PASS; response equals `phase-3-media` after recreation. Remove only the marker afterward; do not remove the volume.

- [ ] **Step 4: Extend repository validation and review checkpoint**

Assert Compose mounts `api_public_media` only into the development API service and Caddy mounts no Laravel path. Review standard storage-link semantics, permissions, persistence, and absence of Phase 4 media design. After fixes, rerun Steps 2–3 and commit:

```powershell
git add api/config/filesystems.php api/tests/Feature/Media compose.yaml infra/validation/validate-repository.mjs
git commit -m "feat: establish persistent Laravel public media"
```

### Task 11: Evidence-derived Caddy ownership and gateway integration

**Files:**
- Create: `infra/caddy/laravel-routes.json`
- Create: `infra/caddy/ROUTE_OWNERSHIP.md`
- Modify: `infra/caddy/Caddyfile`
- Create: `docs/testing/PHASE_3_BROWSER_SMOKE.md`
- Modify: `infra/validation/validate-repository.mjs`

**Interfaces:**
- Consumes: installed Laravel/Filament/Livewire routes, observed admin/login/assets/media requests, Next.js HMR traffic.
- Produces: final explicit backend matchers and structural Livewire regexp; manual smoke checklist fields `Prerequisite`, `Action`, `Expected`, `Observed`.

- [ ] **Step 1: Capture the structured Laravel route inventory**

Start the fully migrated stack, run storage link, and capture UTF-8 JSON:

```powershell
docker compose up -d
docker compose exec -T api php artisan migrate
docker compose exec -T api php artisan storage:link
docker compose exec -T api php artisan route:list --json | Set-Content infra/caddy/laravel-routes.json -Encoding utf8NoBOM
Get-Content infra/caddy/laravel-routes.json -Raw | ConvertFrom-Json | Select-Object uri,methods,name
```

Expected: valid JSON containing API, admin/auth, Filament, Livewire, `/up`, and other installed framework routes. Do not edit generated hash values into Caddy.

- [ ] **Step 2: Create the route-ownership record from evidence**

In `ROUTE_OWNERSHIP.md`, classify:

- Caddy-owned `/__gateway/health`;
- Next-owned `/`, `/es`, `/en`, `/_next/*`, `/health`, and observed HMR/WebSocket traffic;
- Laravel-owned `/api`, `/api/*`, `/admin`, `/admin/*`, `/up`, `/storage/*`;
- every additional Filament/Livewire public asset/request family present in route JSON or observed browser network traffic.

For each additional family, record the evidence source (`route:list` URI/name or observed request), matcher form, and a representative gateway request/status. This is an evidence table, not a guessed list.

- [ ] **Step 3: Observe real Filament/Livewire/media traffic through Caddy**

Run the interactive command directly in a real terminal to create the development administrator; do not redirect its password. In browser DevTools Network, visit unauthenticated `/admin`, authenticate, perform one real Livewire interaction (for example profile/user menu interaction exposed by installed Filament), reload assets, and request the media marker. Record every backend request that the provisional Caddyfile misroutes or that is not already covered.

Expected: a concrete observed set exists. If the installed panel offers no safe state-changing Livewire control, use a non-destructive panel interaction and record it precisely; do not invent a CMS Resource.

- [ ] **Step 4: Implement final evidence-derived matchers**

Retain the explicit known backend paths. Add only observed Filament/Livewire asset families. The Livewire matcher must be structural:

```caddyfile
@livewire path_regexp livewire ^/livewire-[^/]+(?:/.*)?$
handle @livewire {
    reverse_proxy api:80
}
```

Do not include the generated hash from `laravel-routes.json`. Preserve incoming Host and use Caddy's default reverse-proxy forwarded headers; add no Cloudflare trusted-proxy override. Keep backend handlers before the final Next.js handler so Laravel 404s never fall through.

- [ ] **Step 5: Create the version-controlled browser smoke checklist**

Create `docs/testing/PHASE_3_BROWSER_SMOKE.md` with one table/section per approved check. Every entry must have prerequisite, exact browser/action, expected result, and observed-evidence field. Include clean checkout/volumes, dependency bootstrap, start/migrate/link/admin, `/` redirect, `/es`, `/en`, exact `/api/v1`, admin unauthenticated/authenticated, Livewire interaction, assets, media, HMR edit/revert, system/explicit/invalid theme states, flash, hydration/console errors, stop/start, MySQL persistence, media persistence, and clean restart.

State prominently that the checklist is manual and is not automated coverage.

- [ ] **Step 6: Run gateway integration checks**

Run:

```powershell
docker compose exec -T gateway caddy validate --config /etc/caddy/Caddyfile
docker compose exec -T gateway caddy reload --config /etc/caddy/Caddyfile
Invoke-WebRequest http://localhost:8000/es -MaximumRedirection 0
Invoke-WebRequest http://localhost:8000/en -MaximumRedirection 0
Invoke-RestMethod http://localhost:8000/api/v1
Invoke-WebRequest http://localhost:8000/admin -MaximumRedirection 0 -SkipHttpErrorCheck
Invoke-WebRequest http://localhost:8000/api/v1/not-found -SkipHttpErrorCheck
docker compose logs --no-color gateway
```

Expected: Caddy validates; localized pages reach Next; API returns exact data envelope; admin reaches Filament auth; unknown API path returns Laravel JSON error, never Next HTML. Complete the browser-only HMR/auth/Livewire/assets/theme checks and record them as manual observations.

Then stop only API and verify the failure boundary:

```powershell
docker compose stop api
$failure = Invoke-WebRequest http://localhost:8000/api/v1 -SkipHttpErrorCheck
if ($failure.StatusCode -ne 502) { throw "Expected gateway failure, got $($failure.StatusCode)" }
if ($failure.Content -match '<title>.*Next') { throw 'API failure fell through to Next.js' }
docker compose start api
```

Expected: Caddy reports an upstream failure and never serves a false frontend page; API returns healthy after restart.

- [ ] **Step 7: Extend validation and review checkpoint**

Add validator assertions that Caddy has the structural Livewire regex, contains no literal hash copied from route inventory, mounts no API/web source, and ends with frontend fallback after backend handlers. Review observed evidence, backend 404 ownership, HMR, default headers, and absence of guessed/Cloudflare config. After fixes, repeat Steps 1, 3, and 6 and commit:

```powershell
git add infra/caddy docs/testing/PHASE_3_BROWSER_SMOKE.md infra/validation/validate-repository.mjs
git commit -m "feat: finalize evidence-based gateway routing"
```

### Task 12: Clean bootstrap, platform parity, documentation, and final acceptance

**Files:**
- Modify: `README.md`
- Modify: `docs/ENVIRONMENT.md`
- Modify: `docs/ARCHITECTURE.md`
- Modify: `docs/DEPLOYMENT.md`
- Create: `docs/testing/PHASE_3_VERIFICATION.md`
- Modify: `ROADMAP.md`
- Modify: `infra/validation/validate-repository.mjs`

**Interfaces:**
- Consumes: all previous tasks and the approved specification acceptance contract.
- Produces: canonical PowerShell workflow, equivalent Ubuntu WSL2 workflow, complete evidence report, accurately completed Phase 3 Roadmap items, deployment handoff.

- [ ] **Step 1: Document canonical direct commands before final clean-state run**

Update README with prerequisites and exact PowerShell commands for copy env, generate app key without exposing it, build, cold dependency bootstrap, start, migrate, storage link, interactive admin, seed policy, tests, lint/type/build, logs, health, route inventory, stop/restart, and test-profile cleanup. Add Bash variants wherever syntax differs.

State Docker Desktop is the single engine, WSL2 backend is required, Ubuntu integration must be enabled, and a second Ubuntu Docker Engine must not be installed. Keep the current Windows checkout; mention WSL filesystem relocation only as a measured-performance optimization.

- [ ] **Step 2: Perform a destructive-scope review before clean bootstrap**

The clean-bootstrap acceptance intentionally removes only Compose resources belonging to `COMPOSE_PROJECT_NAME=portfolio` in this Phase 3 worktree. First inspect:

```powershell
docker compose ps -a
docker compose config --volumes
docker volume ls --filter label=com.docker.compose.project=portfolio
```

Verify every target carries the Compose project label and is not used by another project. Do not remove anything if identity is ambiguous.

- [ ] **Step 3: Execute the documented PowerShell clean bootstrap**

After the scope check, stop/remove only this project's containers and named volumes, then follow README exactly from a fresh-volume state:

```powershell
docker compose --profile test down --volumes --remove-orphans
docker compose build
docker compose run --rm --no-deps web pnpm install --frozen-lockfile
docker compose run --rm --no-deps api composer install --no-interaction --prefer-dist
docker compose up -d
docker compose exec -T api php artisan migrate
docker compose exec -T api php artisan storage:link
docker compose ps
```

Run the interactive admin command separately in the terminal if the browser-auth smoke record requires a fresh administrator. Expected: fresh volumes bootstrap deterministically; all development services become healthy; no migration/seed/admin ran during `up`.

- [ ] **Step 4: Run all automated and structural verification**

With the appropriate service state, execute:

```powershell
node infra/validation/validate-repository.mjs
docker compose --env-file .env config --quiet
docker compose build
docker compose run --rm --no-deps web pnpm format:check
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
docker compose run --rm --no-deps web pnpm test:run
docker compose stop gateway api mysql
docker compose run --rm --no-deps web pnpm build
docker compose up -d mysql api gateway
docker compose --profile test run --rm api-test
docker compose exec -T api php artisan route:list --json | Out-Null
git diff --check
node docs/design/phase-2/tests/validate-artifacts.mjs
node docs/design/phase-2/tests/validate-contrast.mjs
python docs/design/phase-2/tests/validate-assets.py
```

Expected: every command exits 0; Next build succeeds while backend/gateway/database are stopped; API tests run against `mysql-test`; Phase 2 baselines remain green.

- [ ] **Step 5: Verify persistence, publication, and disposability**

Use the interactively created development administrator row as the existing non-domain database persistence proof, and create a removable media marker. Record the administrator ID/email and media content, recreate containers without deleting named volumes, and confirm both persist. Do not add a persistence-test table or portfolio domain model. Remove/recreate `mysql-test` from scratch and confirm migrations/tests pass without development data.

Inspect:

```powershell
docker compose ps --format json
docker compose port gateway 80
docker compose port web 3000
docker compose port api 80
docker compose port mysql 3306
docker compose --profile test port mysql-test 3306
docker volume ls --filter label=com.docker.compose.project=portfolio
```

Expected: only gateway maps `127.0.0.1:8000`; persistent volumes are project-labeled; no test DB named volume exists; no MySQL port is published.

- [ ] **Step 6: Execute the complete browser smoke checklist**

Follow every prerequisite/action/expected result in `docs/testing/PHASE_3_BROWSER_SMOKE.md` through `http://localhost:8000`. Record actual evidence without relabeling it automated. Specifically capture HMR, real Filament auth/Livewire/assets, public media, theme flash/hydration/console state, locale redirect, and post-restart persistence.

Expected: every required item passes or a concrete failure is fixed and the relevant earlier automated check is rerun.

- [ ] **Step 7: Verify equivalent Ubuntu WSL2 operation**

From Docker-Desktop-integrated Ubuntu WSL2 in the same worktree, run:

```bash
docker version
docker compose version
docker compose config --quiet
docker compose ps
docker compose exec -T api php artisan about --only=environment
docker compose run --rm --no-deps web pnpm --version
curl --fail http://localhost:8000/es
curl --fail http://localhost:8000/en
curl --fail http://localhost:8000/api/v1
```

Expected: commands operate the same Docker Desktop stack; pnpm reports 11.20.0; routes respond. Record that no second `dockerd` installation was required. If bind-mount performance is only subjectively slower, record it without moving the checkout; re-evaluate only with measurable evidence.

- [ ] **Step 8: Synchronize authoritative documentation and evidence**

Create `PHASE_3_VERIFICATION.md` with date, commit, platform versions, each command/result, route-ownership evidence, automated test summary, separately labeled manual browser results, persistence/disposability observations, and remaining non-blocking considerations.

Update architecture/deployment docs with verified exact service/image versions, internal ports, volumes, health signals, environment ownership, build/migration/bootstrap commands, route ownership, backup-relevant MySQL/media boundaries, rollback-relevant application facts, and external trusted-proxy verification. Do not add host instructions.

Mark only Phase 3 Roadmap tasks whose implementation and verification evidence now exists. Leave any genuinely unverified item unchecked and explain it in the report.

- [ ] **Step 9: Run final specification-coverage and forbidden-scope checks**

Run:

```powershell
node infra/validation/validate-repository.mjs
rg -n "cloudflared.*(service|image)|TUNNEL_TOKEN|network_mode:\s*host|0\.0\.0\.0:8000|3306:3306|next-themes|spatie.*permission|playwright|selenium|systemd|firewall|cron" compose.yaml web api infra README.md docs/ENVIRONMENT.md docs/testing
git status --short
git diff --check
```

Expected: validator PASS; forbidden-scope grep has no implementation hits (documentation may mention prohibitions/boundaries); status contains only intended Task 12 files.

- [ ] **Step 10: Final review checkpoint, fixes, and commit**

Request a GPT-5.6 Terra specification-compliance review covering all 14 acceptance criteria, then a GPT-5.6 Sol whole-branch architecture/scope review. Fix all approved findings, rerun the smallest affected task checks and then Steps 4, 5, 7, and 9 in full.

Commit the verified handoff:

```powershell
git add README.md ROADMAP.md docs/ENVIRONMENT.md docs/ARCHITECTURE.md docs/DEPLOYMENT.md docs/testing infra/validation/validate-repository.mjs
git commit -m "docs: complete phase 3 verification and handoff"
```

Do not merge, push, delete the branch/worktree, or invoke `finishing-a-development-branch` until the complete implementation has passed its requested final review and the user reaches the normal finishing workflow.

## Acceptance-Criterion Coverage Matrix

| Specification acceptance criterion | Implementing/verifying tasks |
|---|---|
| 1. Structure and secret-free examples | Tasks 1, 4, 12 |
| 2. Exact locks and deterministic dependency volumes | Tasks 2, 3, 4, 12 |
| 3. Frontend checks, static locales, API-independent build | Tasks 3, 5, 6, 7, 12 |
| 4. Explicit Apache Laravel boot and disposable-MySQL tests | Tasks 2, 4, 8, 12 |
| 5. API/admin/Livewire/bootstrap/log/CORS/rate/media | Tasks 8, 9, 10, 11, 12 |
| 6. Compose build/health/no-sleep/test isolation | Tasks 4, 12 |
| 7. Sole Caddy loopback publication/no MySQL ports | Tasks 4, 11, 12 |
| 8. Installed/observed route ownership | Task 11, reverified Task 12 |
| 9. Persistent development data/media and disposable test DB | Tasks 4, 10, 12 |
| 10. Clean-state manual browser smoke | Tasks 11, 12 |
| 11. PowerShell and Ubuntu WSL2 parity | Task 12 |
| 12. Documentation responsibility agreement | Tasks 1, 11, 12 |
| 13. No later-phase/server-operation scope | Global constraints and every review; final Task 12 scan |
| 14. Roadmap updated only from evidence | Task 12 |

## Key Execution Ordering

1. Establish repository/environment safety before generators create files.
2. Build the PHP/Apache and Node application boundaries and lock exact dependencies.
3. Establish Compose networks, health conditions, dependency volumes, and test isolation before database-backed feature tests.
4. Implement static locale, theme, and typed frontend contracts while continuously proving build-time API independence.
5. Implement the exact Laravel envelope, minimal Filament authorization, interactive bootstrap, and standard media boundary against disposable MySQL tests.
6. Only after installed routes and working UI exist, inventory real Laravel/Filament/Livewire/media traffic and finalize Caddy matchers.
7. Finish with a destructive-scope-reviewed clean bootstrap, full automated verification, explicitly manual browser smoke, Ubuntu WSL2 parity, documentation synchronization, and final Terra/Sol reviews.
