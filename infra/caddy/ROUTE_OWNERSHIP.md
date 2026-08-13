# Gateway route ownership

This record captures the Laravel route inventory generated on 2026-08-13 from the running `api` service and the unauthenticated Filament login page observed through `http://localhost:8000`. The exact command output, statuses, and browser observation record are committed in [GATEWAY_INTEGRATION_EVIDENCE.md](GATEWAY_INTEGRATION_EVIDENCE.md). It is intentionally evidence-based: Caddy does not contain a copied Livewire build hash.

## Ownership

| Request family | Owner | Evidence | Caddy matcher | Representative result |
| --- | --- | --- | --- | --- |
| `/__gateway/health` | Caddy | Compose gateway health check | Exact `handle` | `200 ok` from Caddy |
| `/`, `/es`, `/en`, `/_next/*`, `/health`, Next development HMR/WebSocket requests | Next.js | Architecture/Compose contract; localized pages are served by `web:3000` | Final unqualified `handle` | Tested `/es` and `/en` after final routing |
| `/api`, `/api/*` | Laravel | `api/v1` in `laravel-routes.json` | `@laravel` path family | Tested `/api/v1` and backend-owned unknown API response |
| `/admin`, `/admin/*` | Laravel/Filament | `admin`, `admin/login`, and `admin/logout` in inventory; browser navigation reached `/admin/login` | `@laravel` path family | `200` login page through gateway |
| `/up` | Laravel | Installed framework health route in inventory | `@laravel` path family | API service health check |
| `/storage/*` | Laravel | Installed `storage/{path}` GET/PUT routes in inventory | `@laravel` path family | Manual media marker check is pending browser smoke |
| `/filament/*` | Laravel/Filament | Installed export/import download routes in inventory | `@laravel` path family | Requires authenticated browser smoke |
| `/css/filament/*` | Laravel/Filament | Browser observed `/css/filament/filament/app.css?v=5.7.6.0` | `@laravel` path family | `200 text/css` for that exact request |
| `/fonts/filament/*` | Laravel/Filament | Browser observed `/fonts/filament/filament/inter/inter-latin-wght-normal-NRMW37G5.woff2` | `@laravel` path family | `200 font/woff2` for that exact request |
| `/js/filament/*` | Laravel/Filament | Browser observed `/js/filament/actions/actions.js?v=5.7.6.0` | `@laravel` path family | `200 text/javascript` for that exact request |
| `/livewire-[^/]+(?:/.*)?` | Laravel/Livewire | Inventory routes use the installed content-addressed `livewire-…` prefix; browser observed `/livewire-9621a96e/livewire.js?id=26bbdf42` | Structural `path_regexp`, not an inventory hash | `200 application/javascript; charset=utf-8` for that exact request |

The committed evidence records the unauthenticated panel and its asset URLs. It also records that, after Caddy reload, reloading the same panel page produced the same asset set and an empty browser warning/error console result. Authentication and a real Livewire interaction require a manually created development administrator and are deliberately recorded as unobserved in the smoke checklist rather than inferred here.

## Boundary rules

- Backend handlers precede the final Next.js fallback, so Laravel 404s and upstream failures cannot become frontend pages.
- Caddy preserves the incoming `Host` and uses its standard reverse-proxy forwarded headers. There is no Cloudflare trusted-proxy or tunnel configuration in this repository.
- Caddy mounts no application source or Laravel media path; Apache/Laravel owns framework routes and the storage link.
