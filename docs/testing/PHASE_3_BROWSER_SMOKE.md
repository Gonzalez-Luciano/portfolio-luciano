# Phase 3 manual browser smoke checklist

> This is a **manual, non-automated** browser checklist. It is not Playwright/Selenium coverage and must be completed against a clean local stack at `http://localhost:8000`. Record observed facts and failures; do not mark an item passed from an expected result alone.

| Check | Prerequisite | Action | Expected | Observed |
| --- | --- | --- | --- | --- |
| Clean bootstrap | Clean checkout; project volumes removed only after confirming they are disposable | Run dependency bootstrap and `docker compose up -d`; run `php artisan migrate` and `storage:link` in `api` | Services become healthy; no migration or administrator is created by Compose startup | Not observed in this task; environment began with existing development volumes |
| Development administrator | Healthy API and an interactive local terminal | Run `docker compose exec api php artisan make:filament-user`; retain credentials only locally | Administrator exists; no credential is committed or copied to documentation | Not observed: interactive credential entry was unavailable in this run |
| Root locale redirect | Healthy gateway | Navigate to `/` in a new browser context | Redirect resolves to a valid localized route according to locale policy | Not observed in this task |
| Spanish page | Healthy gateway | Navigate to `/es` | Next.js Spanish shell renders without gateway error | Automated HTTP check recorded separately; browser visual check not observed |
| English page | Healthy gateway | Navigate to `/en` | Next.js English shell renders without gateway error | Automated HTTP check recorded separately; browser visual check not observed |
| Exact public API | Healthy gateway | Navigate to `/api/v1` | Laravel JSON envelope identifies `ok` and `v1` | Automated HTTP check recorded separately; browser visual check not observed |
| Admin unauthenticated | Healthy gateway | Navigate to `/admin` | Redirect reaches Filament login page | Observed; see `infra/caddy/GATEWAY_INTEGRATION_EVIDENCE.md` for the login URL, title, and visible form |
| Admin authenticated | Development administrator created locally | Sign in at `/admin` | Dashboard renders and session remains same-origin | Not observed: no interactive development administrator was created |
| Livewire interaction | Authenticated Filament dashboard | Perform one non-destructive interaction (for example open/close user menu) and inspect Network | A request matching `/livewire-[^/]+/...` stays on Laravel; UI updates without console error | Not observed: authentication prerequisite unavailable |
| Filament assets | Unauthenticated `/admin/login` page | Reload with Network/assets visible | Filament CSS, JS, fonts and Livewire script return from Laravel through Caddy | Observed; exact CSS/font/JS/Livewire request URLs and `200` statuses are in `infra/caddy/GATEWAY_INTEGRATION_EVIDENCE.md` |
| Public media | Storage link exists and a non-secret marker is placed in `api_public_media` | Request `/storage/<marker>` | Marker is served by Laravel/Apache through Caddy and survives an API recreate | Not observed: this task did not create a marker |
| HMR | Web dev server running | Make a harmless local text edit in `web`, observe browser update, then revert it | Browser updates through gateway without full manual reload or WebSocket error | Not observed: manual browser step |
| System theme | Fresh browser context with OS light then dark preference | Open `/es` in each context | Initial theme follows system preference without visible flash | Not observed: manual browser step |
| Explicit and invalid theme | Existing `/es` page | Toggle theme; then set invalid `portfolio_theme` value in browser storage and reload | Explicit choice persists; invalid value is cleared and falls back to system preference | Not observed: manual browser step |
| Hydration and console | `/es`, `/en`, and `/admin/login` loaded | Inspect DevTools console after each render and interaction | No hydration error or uncaught console error | Not observed: manual browser step |
| Gateway/API restart | Gateway/API healthy | Stop and start `api`, then reload `/api/v1` and `/es` | While API is stopped `/api/v1` is 502, never a Next page; API recovers after start | Automated HTTP boundary check recorded separately; browser reload not observed |
| MySQL persistence | Existing known development database record | Restart `mysql`, then `api` and gateway if required | Record remains after restart | Not observed: no task-owned database record was created |
| Media persistence | Existing non-secret media marker | Recreate only `api`, then request marker | Marker remains available through `/storage/*` | Not observed: no marker was created |
| Clean restart | All manual checks complete | Restart the Compose development services without deleting volumes | Gateway, web, API, and MySQL recover; locale/API/admin remain reachable | Not observed: manual browser step |
