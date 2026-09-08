# Gateway route ownership

This record captures the Laravel route inventory generated on 2026-08-13 from the running `api` service, plus local-browser observations of the unauthenticated Filament login and authenticated dashboard through `http://localhost:8000`. The exact command output, statuses, and browser observation record are committed in [GATEWAY_INTEGRATION_EVIDENCE.md](GATEWAY_INTEGRATION_EVIDENCE.md). It is intentionally evidence-based: Caddy does not contain a copied Livewire build hash.

## Ownership

| Request family | Owner | Evidence | Caddy matcher | Representative result |
| --- | --- | --- | --- | --- |
| `/__gateway/health` | Caddy | Compose gateway health check | Exact `handle` | `200 ok` from Caddy |
| `/`, `/es`, `/en`, `/_next/*`, `/health`, Next development HMR/WebSocket requests | Next.js | Architecture/Compose contract; localized pages are served by `web:3000` | Final unqualified `handle` | Tested `/es` and `/en` after final routing |
| `/api`, `/api/*` | Laravel | `api/v1` in `laravel-routes.json` | `@laravel` path family | Tested `/api/v1` and backend-owned unknown API response |
| `/admin`, `/admin/*` | Laravel/Filament | `admin`, `admin/login`, and `admin/logout` in inventory; browser navigation reached login and an authenticated dashboard through the gateway | `@laravel` path family | Login and dashboard observed through gateway |
| `/up` | Laravel | Installed framework health route in inventory | `@laravel` path family | API service health check |
| `/storage/*` | Laravel | Installed `storage/{path}` GET/PUT routes in inventory | `@laravel` path family | Disposable marker served through Caddy before and after API recreate; removed afterward |
| `/filament/*` | Laravel/Filament | Installed export/import download routes in inventory | `@laravel` path family | Authenticated dashboard observed; export/import downloads remain untested |
| `/css/filament/*` | Laravel/Filament | Browser observed `/css/filament/filament/app.css?v=5.7.6.0` | `@laravel` path family | `200 text/css` for that exact request |
| `/fonts/filament/*` | Laravel/Filament | Browser observed `/fonts/filament/filament/inter/inter-latin-wght-normal-NRMW37G5.woff2` | `@laravel` path family | `200 font/woff2` for that exact request |
| `/js/filament/*` | Laravel/Filament | Browser observed `/js/filament/actions/actions.js?v=5.7.6.0` | `@laravel` path family | `200 text/javascript` for that exact request |
| `/livewire-[^/]+(?:/.*)?` | Laravel/Livewire | Inventory routes use an installed content-addressed prefix; the login update was observed structurally as `POST /livewire-<build-hash>/update` in Apache | Structural `path_regexp`, not an inventory hash | `200` for the observed update; dashboard user-menu interaction was console-clean |
| `/cv/*` | Laravel | `cv/luciano-gonzalez-es.pdf` and `cv/luciano-gonzalez-en.pdf` in `laravel-routes.json`, backed by `CvDownloadController` (`api/app/Http/Controllers/CvDownloadController.php`) and `Tests\Feature\CvDownloadTest` | `@laravel` path family | `Tests\Feature\CvDownloadTest` covers both fixed routes streaming a published+visible private original and every ineligible state (missing row, hidden, draft, missing file) returning a plain web 404, plus the `cv-download` limiter and the absence of any working `/storage/*` alternative |

The committed evidence records the unauthenticated panel and its asset URLs. It also records that, after Caddy reload, reloading the same panel page produced the same asset set and an empty browser warning/error console result. A disposable local administrator then authenticated through Caddy; its login update reached the structural Livewire route above with `200`, and a non-destructive dashboard user-menu interaction remained console-clean. No credential, account identifier, email, or password is retained in the evidence.

## Boundary rules

- Backend handlers precede the final Next.js fallback, so Laravel 404s and upstream failures cannot become frontend pages.
- Caddy preserves the incoming `Host` and uses its standard reverse-proxy forwarded headers. There is no Cloudflare trusted-proxy or tunnel configuration in this repository.
- Caddy mounts no application source or Laravel media path; Apache/Laravel owns framework routes and the storage link.
