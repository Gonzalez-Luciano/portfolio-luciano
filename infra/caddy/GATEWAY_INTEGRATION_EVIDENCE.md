# Gateway integration evidence

Captured locally on 2026-08-13 against the running Compose stack after the final Caddy reload. This is an auditable record of executed integration checks, not a substitute for the manual browser smoke checklist.

## Installed route evidence

```powershell
docker compose exec -T api php artisan migrate --force
docker compose exec -T api php artisan route:list --json > infra/caddy/laravel-routes.json
```

The committed inventory contains `api/v1`, `admin`, `admin/login`, `admin/logout`, `filament/exports/{export}/download`, `filament/imports/{import}/failed-rows/download`, installed `livewire-9621a96e/...` routes, `storage/{path}`, and `up`.

## Caddy and HTTP evidence

```text
node infra/validation/validate-repository.mjs
Repository and environment contract pass.

docker compose exec -T gateway caddy validate --config /etc/caddy/Caddyfile
Valid configuration

docker compose exec -T gateway caddy reload --config /etc/caddy/Caddyfile
load complete

GET /__gateway/health                         => 200 text/plain; charset=utf-8
GET /es                                       => 200 text/html; charset=utf-8
GET /en                                       => 200 text/html; charset=utf-8
GET /api/v1                                   => 200 application/json
GET /admin (following redirect)               => 200 text/html; charset=utf-8
GET /api/v1/not-found                         => 404 application/json
GET /css/filament/filament/app.css?v=5.7.6.0 => 200 text/css
GET /fonts/filament/filament/inter/inter-latin-wght-normal-NRMW37G5.woff2
                                                => 200 font/woff2
GET /js/filament/actions/actions.js?v=5.7.6.0 => 200 text/javascript
GET /livewire-9621a96e/livewire.js?id=26bbdf42
                                                => 200 application/javascript; charset=utf-8
```

`GET /api/v1` returned `{"data":{"status":"ok","version":"v1"}}`. `GET /api/v1/not-found` returned Laravel's `404 application/json` envelope, rather than Next.js HTML.

## API-down ownership boundary

```powershell
docker compose stop api
curl.exe -sS -i http://localhost:8000/api/v1
```

Result:

```text
HTTP/1.1 502 Bad Gateway
Server: Caddy
Content-Length: 0
```

The empty 502 response contained no Next.js content. The Caddy log recorded an upstream lookup failure with `status:502`. After `docker compose start api`, `GET /api/v1` returned `200 application/json` again.

## Browser observation evidence

Using the local in-app browser, navigation to `http://localhost:8000/admin` reached `http://localhost:8000/admin/login`; the title was `Login - Laravel` and the visible sign-in form was present. Reloading after the Caddy reload observed these exact request URLs:

- `http://localhost:8000/css/filament/filament/app.css?v=5.7.6.0`
- `http://localhost:8000/fonts/filament/filament/inter/inter-latin-wght-normal-NRMW37G5.woff2`
- `http://localhost:8000/js/filament/actions/actions.js?v=5.7.6.0`
- `http://localhost:8000/livewire-9621a96e/livewire.js?id=26bbdf42`

The captured browser warning/error console result for that unauthenticated login reload was an empty list. This evidence does not cover authenticated Filament, a stateful Livewire action, HMR, media persistence, theme flash, or clean bootstrap; those remain `Not observed` in the manual checklist.
