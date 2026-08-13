# Portfolio — Luciano González

Portfolio profesional orientado a:

**Backend Developer | PHP & Laravel**

Identidad del repositorio:

- Workspace local: `portfolio-luciano`.
- Repositorio GitHub canónico: `portfolio`.
- Producción: `lucianogonzalez.dev`.

## Antes de trabajar en el proyecto

Leer:

1. `AGENTS.md`
2. `docs/PROJECT.md`
3. `docs/ARCHITECTURE.md`
4. `docs/SERVER_ARCHITECTURE.md`
5. `docs/DEPLOYMENT.md`
6. `ROADMAP.md`

## Estructura del repositorio

- `web/` — portfolio público React/Next.js.
- `api/` — Laravel API + administración.
- `infra/` — Docker e infraestructura específica del portfolio.
- `docs/` — documentación del producto, arquitectura y servidor.

La arquitectura de Fase 3 usa Caddy como único gateway local, Next.js interno, Apache + Laravel interno, MySQL 8.4 persistente para desarrollo y un perfil de pruebas con MySQL 8.4 descartable. El contrato local es `http://localhost:8000`; los servicios internos no publican puertos al host.

## Entorno local

PowerShell/Windows Terminal con Docker Desktop y backend WSL2 es el flujo Windows canónico. Docker Desktop es el único engine: habilitar la integración de Ubuntu WSL2 y no instalar un segundo Docker Engine/dockerd dentro de Ubuntu. El checkout actual de Windows funciona; trasladarlo al filesystem WSL solo es una optimización futura que debe justificarse con una medición.

Prerequisitos: Docker Desktop iniciado, backend WSL2 e integración Ubuntu habilitados, Node no es necesario en el host y una copia local de `.env` basada en el ejemplo. No imprimir ni compartir los valores de `.env`.

### Bootstrap limpio (PowerShell)

Primero crear el archivo local y generar una clave Laravel sin exponerla en
pantalla. Ejecutar este bloque solamente cuando `.env` no exista: se niega a
sobrescribir una configuración o secreto local ya presente. Si `.env` existe,
verificar y conservar su contenido; no volver a generar `APP_KEY`.

```powershell
if (Test-Path -LiteralPath .env) { throw 'Refusing to overwrite existing .env' }
Copy-Item -LiteralPath .env.example -Destination .env -ErrorAction Stop
$envPath = (Resolve-Path .env)
$appKey = 'base64:' + [Convert]::ToBase64String([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
[IO.File]::WriteAllText($envPath, [regex]::Replace((Get-Content -Raw $envPath), '(?m)^APP_KEY=.*$', "APP_KEY=$appKey"))
Remove-Variable appKey
```

El comando guarda la clave directamente en el `.env` sin imprimir su valor. Tras completar los demás placeholders requeridos, un bootstrap ordinario es:

```powershell
docker compose build
docker compose run --rm --no-deps web pnpm install --frozen-lockfile
docker compose run --rm --no-deps api composer install --no-interaction --prefer-dist
docker compose up -d --wait
docker compose exec -T api php artisan migrate
docker compose exec -T api php artisan storage:link
docker compose ps
```

`up -d --wait` es intencional: `/up` confirma el boot de Laravel pero las migraciones necesitan que MySQL ya esté healthy. Compose no crea migraciones, seeds ni administradores por sí solo. El seed normal no crea cuentas. Crear el primer administrador solo en una terminal local interactiva; la contraseña queda fuera de argumentos, variables, historial, Git y documentación:

```powershell
docker compose exec api php artisan portfolio:bootstrap-admin
```

Para reiniciar sin borrar datos: `docker compose restart`. Para detener el entorno: `docker compose stop`. Los logs se consultan con `docker compose logs --tail=200 <servicio>`.

### Verificación local

```powershell
node infra/validation/validate-repository.mjs
docker compose --env-file .env config --quiet
docker compose run --rm --no-deps web pnpm format:check
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
docker compose run --rm --no-deps web pnpm test:run
docker compose --profile test run --rm api-test
docker compose run --rm --no-deps web pnpm build
```

El build de Next debe poder ejecutarse con `gateway`, `api` y `mysql` detenidos. `api-test` usa únicamente `mysql-test` descartable. Después de una sesión de test se puede retirar exclusivamente ese contenedor con `docker compose --profile test rm -sf mysql-test`.

`docs/testing/PHASE_3_BROWSER_SMOKE.md` contiene las comprobaciones manuales, y `docs/testing/PHASE_3_VERIFICATION.md` conserva la evidencia del bootstrap de aceptación.

### Equivalencia Ubuntu WSL2

Desde Ubuntu integrado, sobre el mismo checkout, se usan los mismos comandos sin cambios de Compose:

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

En Bash, el equivalente seguro de la copia inicial también se niega a sobrescribir
un `.env` existente:

```bash
if [ -e .env ]; then
  printf '%s\n' 'Refusing to overwrite existing .env' >&2
else
  cp .env.example .env
fi
```

La negativa no termina la shell interactiva de WSL. La misma regla de secretos
aplica: no pasar una contraseña al comando, entorno ni historial.

## Flujo Git

`main` es la única rama estable de larga duración. El trabajo usa ramas cortas con prefijos como `feat/`, `fix/`, `docs/`, `refactor/`, `test/`, `chore/` e `infra/`, y llega a `main` mediante pull request después de las validaciones relevantes. No existe una rama permanente `develop`, no se reescribe historial compartido y ningún trabajo se integra en `main` sin aprobación explícita.

Los tags anotados se crean desde commits aprobados de `main` solo para hitos o releases significativos.

## Contexto de producción

El portfolio se ejecutará en una computadora Linux propia junto con otros proyectos Dockerizados.

```text
Internet
   ↓
Cloudflare
   ↓
Cloudflare Tunnel compartido del servidor
   ↓
127.0.0.1:8000
   ↓
Portfolio Docker
   ├── Next.js
   ├── Laravel / Filament
   └── MySQL
```

Otros proyectos se publicarán usando subdominios y puertos locales diferentes.

El roadmap de este repositorio termina su responsabilidad operativa al entregar una versión aprobada, validada y acompañada por su contrato de deployment. El workflow externo `home_server_ops_claude` inspecciona la computadora Linux real y ejecuta el Compose final de producción, Cloudflare Tunnel, backups, seguridad del host y deployment. El portfolio no duplica ese checklist ni presupone características físicas del servidor.

`docs/DEPLOYMENT.md` describe el handoff de aplicación que consume ese workflow externo.

La arquitectura multiproyecto se documenta en:

`docs/SERVER_ARCHITECTURE.md`

El proyecto todavía debe seguir las fases activas de `ROADMAP.md`.
