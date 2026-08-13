# Phase 3 verification record

## Scope and platform

Acceptance verification ran on 2026-08-13 from the Phase 3 worktree, starting
from `aa3d531` plus the Task 12 formatting/documentation changes. It used
Windows PowerShell, Docker Desktop `29.4.3` (Compose `v5.1.3`) with the WSL2
backend, then Ubuntu WSL2 through Docker Desktop integration. The observed
application versions were Caddy `2.11.4-alpine`, Node `24.18.0`, pnpm
`11.20.0`, Next `16.2.12`, React `19.2.4`, PHP `8.5.8`, Laravel `13.25.0`,
Filament `5.7.6`, Composer `2.10.2`, and MySQL `8.4`.

This is evidence for the development/test foundation only. It is not a
production deployment record and does not authorize host, Cloudflare, backup,
or server operations. Those remain with `home_server_ops_claude`.

## Destructive scope and clean bootstrap

Before cleanup, `docker compose ps -a`, `docker compose config --volumes`, and
`docker volume ls --filter label=com.docker.compose.project=portfolio` found
five `portfolio` containers and four volumes:

- `portfolio-api-1`, `portfolio-gateway-1`, `portfolio-mysql-1`,
  `portfolio-mysql-test-1`, and `portfolio-web-1`;
- `portfolio_api_public_media`, `portfolio_api_vendor`,
  `portfolio_mysql_data`, and `portfolio_web_node_modules`.

Every removal target carried `com.docker.compose.project=portfolio`; no
ambiguous or foreign resource was targeted. The verified clean sequence was:

```powershell
docker compose --profile test down --volumes --remove-orphans
docker compose build
docker compose run --rm --no-deps web pnpm install --frozen-lockfile
docker compose run --rm --no-deps api composer install --no-interaction --prefer-dist
docker compose up -d --wait
docker compose exec -T api php artisan migrate
docker compose exec -T api php artisan storage:unlink # only after confirming the existing path is an untracked link
docker compose exec -T api php artisan storage:link
```

The initial plain `up -d` followed by an immediate migration encountered MySQL
startup timing; no data was lost. Repeating the clean procedure with the
documented `up -d --wait` made MySQL healthy before migration. Migrations
completed. The checkout retained an untracked `public/storage` symbolic link
across the Compose volume cleanup; the API entrypoint did not create it. The
link must therefore be checked as an untracked symbolic link, removed only by
Laravel's `storage:unlink` when present, and recreated with `storage:link`. No
seed or administrator was created by Compose startup.

The documented PowerShell and Bash setup commands now refuse to overwrite an
existing `.env`; they are only for a verified fresh checkout. An existing local
configuration, including its `APP_KEY`, must be preserved and reviewed rather
than regenerated. The Bash guard uses an `if` branch rather than top-level
`exit`, so refusal does not terminate an interactive WSL shell.

## Final Sol follow-up verification

On 2026-08-13, the focused review fix first inspected the project-labelled
Compose resources. Only `portfolio-web-1` and the regenerable,
project-labelled `portfolio_web_node_modules` volume were removed; MySQL and
media volumes were not targeted. The following fresh cold-bootstrap evidence
was recorded:

- the newly created `web_node_modules` volume passed an empty-directory check
  before installation;
- `docker compose run --rm --no-deps web pnpm install --frozen-lockfile`
  populated it from the committed lockfile (462 packages; pnpm 11.20.0) using
  the container-only `/pnpm/store`, without recreating `web/.pnpm-store`;
- `/app/node_modules/next` existed after that explicit install and `web`
  returned healthy after `docker compose up -d --wait web`;
- Git confirmed `api/public/storage` was untracked, Laravel confirmed it was a
  symbolic link, and `storage:unlink` then `storage:link` deleted and recreated
  the standard link successfully.

This follow-up did not create an administrator or perform authenticated
Filament/Livewire browser steps. Those remain explicitly unobserved below.

## Automated and structural results

All commands below exited `0` after the formatter correction in
`web/src/components/theme-switcher.test.tsx`:

```text
node infra/validation/validate-repository.mjs                 PASS
docker compose --env-file .env config --quiet                 PASS
pnpm format:check                                             PASS
pnpm lint                                                     PASS
pnpm typecheck                                                PASS
pnpm test:run                                                 8 files, 32 tests PASS
pnpm build with gateway/api/mysql stopped                     PASS
docker compose --profile test run --rm api-test               26 tests, 136 assertions PASS
php artisan route:list --json                                 PASS
node docs/design/phase-2/tests/validate-artifacts.mjs         PASS
node docs/design/phase-2/tests/validate-contrast.mjs          PASS
python docs/design/phase-2/tests/validate-assets.py           PASS
git diff --check                                              PASS
```

`api-test` was rerun after removing only the inspected,
project-labelled `mysql-test` container. It recreated its tmpfs MySQL service
and again passed all 26 tests; no named test-database volume exists.

## Final Sol repair verification (2026-08-13)

The following was rerun after the final-review repairs. This is fresh command
or HTTP evidence, not a replacement for any unperformed browser observation:

```text
node infra/validation/validate-repository.mjs                 PASS
docker compose --env-file .env config --quiet                 PASS
docker compose build                                          PASS
web: format:check, lint, typecheck, test:run                  8 files, 35 tests PASS
api-test                                                       27 tests, 139 assertions PASS
caddy validate --config /etc/caddy/Caddyfile                  PASS
php artisan route:list --json                                 PASS
GET /__gateway/health, /health, /up                           200
GET /api/v1                                                    data.status=ok, data.version=v1
docker inspect published-port check                            gateway 80/tcp 127.0.0.1 8000
node docs/design/phase-2/tests/validate-artifacts.mjs         PASS
node docs/design/phase-2/tests/validate-contrast.mjs          PASS
python docs/design/phase-2/tests/validate-assets.py           PASS
git diff --check                                              PASS
```

For the dependency-cache proof, only the inspected, project-labelled
`portfolio_web_node_modules` cache volume was removed and recreated. It was
observed empty before an explicit
`pnpm install --frozen-lockfile --reporter=append-only`; the install reported
its content-addressable store at `/pnpm/store/v11`, and no
`web/.pnpm-store` copy-up remained afterward. The web service then started
healthy without an install command.

For the storage-link proof, `api/public/storage` was first confirmed untracked
and to be a symbolic link. Laravel `storage:unlink` removed only that link,
then `storage:link` recreated it and the expected public media directory was
reachable. No administrator, password, browser authentication, or
credential-gated browser smoke was attempted or recorded.

## Runtime, publication, and persistence observations

- `docker compose ps --format json` reported healthy gateway, web, API, MySQL,
  and test MySQL services.
- Only `gateway` published a host mapping: `127.0.0.1:8000 -> 80`. Compose
  reported unpublished internal ports for web, API, MySQL, and mysql-test.
- `/api/v1` returned `{"data":{"status":"ok","version":"v1"}}`; `/es`
  and `/en` responded successfully. With only `api` stopped, `/api/v1` was
  `502` while `/en` remained `200`; starting API restored the endpoint.
- A non-secret `phase-3-verification-media` marker placed in
  `api_public_media` was returned through `/storage/` before and after an API
  `--force-recreate`. The marker was removed afterward; it was test evidence,
  not content.
- A non-admin factory user with the non-secret email
  `phase3-task12-persistence@example.test` was created through Laravel. Its
  observed record was `id=1`, `is_admin=0`. After a label-verified
  `--force-recreate` of only `mysql` and `api` (without volume removal), the
  same record was queried successfully with the same ID and admin flag. The
  marker user was then deleted and a follow-up count was `0`; no administrator
  credential or persistent test account was created.
- The administrator-row persistence proof was intentionally not performed:
  creating it requires a password entered in a local interactive terminal and
  no credential was supplied or recorded here.

Route ownership is independently evidenced in
`infra/caddy/laravel-routes.json` and
`infra/caddy/GATEWAY_INTEGRATION_EVIDENCE.md`. Caddy owns the one published
entrypoint and sends API/admin/Livewire/assets/media traffic to Laravel before
the Next.js fallback.

## Manual local-browser record

This is manual browser evidence, not Playwright/Selenium coverage. In the local
Codex browser at `http://localhost:8000`, the following was observed:

- `/` redirected to `/es`; the Spanish shell exposed `lang="es"`.
- `/en` rendered the English shell with `lang="en"`.
- Selecting Dark on `/es` set the root theme to `dark`; navigating to `/en`
  retained that root theme.
- The rendered API-status control returned `ok/v1` before the restart test.
- `/admin` redirected to `/admin/login`, rendered the Filament sign-in form,
  and had no captured warning/error console entry.
- `/es`, `/en`, and `/admin/login` had no captured warning/error console entry
  after their rendered state was inspected.

Not observed, and therefore not claimed as passed: authenticated Filament and
Livewire interaction, a real admin-row persistence check, system-theme flash,
invalid-storage fallback, HMR, and a browser-level recovery render after the
API restart. The browser surface blocked direct JSON navigation to `/api/v1`
and later left the API-status region empty after the restart, even though the
separate HTTP checks above passed; this is retained as a manual follow-up rather
than relabelled as automated coverage.

## Ubuntu WSL2 parity

From the same worktree at
`/mnt/c/Users/lucho/Desktop/portfolio-luciano/.worktrees/phase-3-foundation`,
Ubuntu WSL2 successfully ran `docker version`, `docker compose version`,
`docker compose config --quiet`, `docker compose ps`, `php artisan about
--only=environment`, `pnpm --version`, and `curl --fail` for `/es`, `/en`, and
`/api/v1`. It reported the same Docker server `29.4.3`, Compose `v5.1.3`, and
pnpm `11.20.0`. This used Docker Desktop integration; no second `dockerd` was
installed or required.

## Remaining non-blocking follow-up

Complete the explicitly unobserved manual browser checks with a disposable,
locally created development administrator. Keep its password outside command
arguments, environment, history, repository, screenshots, and reports. The
production handoff still requires the independent operational preflight,
deployment, backup/restore, trusted-proxy, and public-domain verification
defined in `docs/DEPLOYMENT.md`.
