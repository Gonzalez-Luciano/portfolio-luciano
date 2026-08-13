# Environment contract

The tracked [`.env.example`](../.env.example) defines the root Compose
interpolation contract. Copy it to an untracked root `.env` before running a
future local stack:

```powershell
Copy-Item .env.example .env
```

```bash
cp .env.example .env
```

Replace every `replace-with-` value before use and never commit `.env`.

## Ownership and exposure

The root `.env` is Compose interpolation input; it is not blanket container
injection. Future `compose.yaml` entries must deliberately pass each service
only the variables it owns.

| Variable | Owner | Recipient service | Secret | Browser exposure |
| --- | --- | --- | --- | --- |
| `COMPOSE_PROJECT_NAME` | Root / Compose | Compose project | No | Never |
| `GATEWAY_HOST` | Root / Compose | Gateway port binding | No | Never |
| `GATEWAY_PORT` | Root / Compose | Gateway port binding | No | Never |
| `APP_URL` | Root / Compose | API runtime and web server runtime when required | No | Never unless a later browser requirement justifies an explicit `NEXT_PUBLIC_` variable |
| `APP_KEY` | Root / Compose | API runtime | Yes | Never |
| `MYSQL_DATABASE` | Root / Compose | API runtime and development MySQL initialization | No | Never |
| `MYSQL_USER` | Root / Compose | API runtime and development MySQL initialization | No | Never |
| `MYSQL_PASSWORD` | Root / Compose | API runtime and development MySQL initialization | Yes | Never |
| `MYSQL_ROOT_PASSWORD` | Root / Compose | Development MySQL initialization/readiness only | Yes | Never |
| `MYSQL_TEST_DATABASE` | Root / Compose | Test API runtime and disposable test MySQL initialization | No | Never |
| `MYSQL_TEST_USER` | Root / Compose | Test API runtime and disposable test MySQL initialization | No | Never |
| `MYSQL_TEST_PASSWORD` | Root / Compose | Test API runtime and disposable test MySQL initialization | Yes | Never |
| `MYSQL_TEST_ROOT_PASSWORD` | Root / Compose | Disposable test MySQL initialization/readiness only | Yes | Never |
| `INTERNAL_API_ORIGIN=http://api` | Compose service configuration | Web server runtime only | No | Never; it is server-only |

The web service never receives `MYSQL_*` variables. `INTERNAL_API_ORIGIN=http://api`
is injected only into the web server runtime; it is not browser-exposed. Browser
configuration, if a later requirement needs it, must use explicit `NEXT_PUBLIC_`
names and must not contain secrets.

Cloudflare variables do not belong to this repository: there is no tracked
example, container injection, or placeholder for that shared host concern.
