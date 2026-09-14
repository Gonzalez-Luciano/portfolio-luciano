# Environment contract

The tracked [`.env.example`](../.env.example) defines the root Compose
interpolation contract. Copy it to an untracked root `.env` before running the
local stack. These commands are for a verified fresh checkout only: they refuse
to overwrite an existing `.env`, which may contain local configuration or
secrets.

```powershell
if (Test-Path -LiteralPath .env) { throw 'Refusing to overwrite existing .env' }
Copy-Item -LiteralPath .env.example -Destination .env -ErrorAction Stop
```

```bash
if [ -e .env ]; then
  printf '%s\n' 'Refusing to overwrite existing .env' >&2
else
  cp .env.example .env
fi
```

The Bash guard leaves an interactive WSL shell running after a refusal.

Replace every `replace-with-` value before use and never commit `.env`.

## Local platform contract

Windows development uses PowerShell/Windows Terminal with Docker Desktop's WSL2
backend. Ubuntu WSL2 must be enabled in Docker Desktop integration and uses that
same engine; do not install or start a second Ubuntu Docker Engine. The checked
out Windows path is supported. Moving it under the WSL filesystem is optional
only after measuring a material bind-mount performance problem.

For reliable development watching on the Windows/9p bind mount, the web
Compose service runs the Vite dev server with `VITE_USE_POLLING=true`, which
`web/vite.config.ts` turns into polling, and `VITE_HMR_CLIENT_PORT` set to the
gateway port so HMR reconnects through Caddy. Keep both settings together. They are
development-only and do not alter Caddy routing or the production build.

The equivalent Ubuntu command surface is Bash plus the same `docker compose`
commands. It does not require a distinct `.env`, project name, image, network,
or volume. See the tested command set in the repository root `README.md`.

## Ownership and exposure

The root `.env` is Compose interpolation input; it is not blanket container
injection. `compose.yaml` deliberately passes each service
only the variables it owns.

| Variable | Owner | Recipient service | Secret | Browser exposure |
| --- | --- | --- | --- | --- |
| `COMPOSE_PROJECT_NAME` | Root / Compose | Compose project | No | Never |
| `GATEWAY_HOST` | Root / Compose | Gateway port binding | No | Never |
| `GATEWAY_PORT` | Root / Compose | Gateway port binding | No | Never |
| `APP_URL` | Root / Compose | API runtime | No | Never unless a later browser requirement justifies an explicit `VITE_` variable |
| `APP_KEY` | Root / Compose | API runtime | Yes | Never |
| `MYSQL_DATABASE` | Root / Compose | API runtime and development MySQL initialization | No | Never |
| `MYSQL_USER` | Root / Compose | API runtime and development MySQL initialization | No | Never |
| `MYSQL_PASSWORD` | Root / Compose | API runtime and development MySQL initialization | Yes | Never |
| `MYSQL_ROOT_PASSWORD` | Root / Compose | Development MySQL initialization/readiness only | Yes | Never |
| `MYSQL_TEST_DATABASE` | Root / Compose | Test API runtime and disposable test MySQL initialization | No | Never |
| `MYSQL_TEST_USER` | Root / Compose | Test API runtime and disposable test MySQL initialization | No | Never |
| `MYSQL_TEST_PASSWORD` | Root / Compose | Test API runtime and disposable test MySQL initialization | Yes | Never |
| `MYSQL_TEST_ROOT_PASSWORD` | Root / Compose | Disposable test MySQL initialization/readiness only | Yes | Never |
| `VITE_HMR_CLIENT_PORT` (from `GATEWAY_PORT`) | Compose service configuration | Web development server only | No | Only as the HMR WebSocket port |
| `VITE_USE_POLLING=true` | Compose service configuration | Web development server only | No | Never |

The web service never receives `MYSQL_*` variables or an internal API origin:
the browser calls the API root-relatively through the gateway. Any value that
Vite exposes to the browser (`VITE_` names read through `import.meta.env`) must
not contain secrets.

Cloudflare variables do not belong to this repository: there is no tracked
example, container injection, or placeholder for that shared host concern.
