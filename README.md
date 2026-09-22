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

- `web/` — portfolio público Vite + React 18 (SPA). Desde Fase 6 reemplaza al front Next.js de Fase 5: escena de scroll con video y secciones de experiencia, proyectos, stack, sobre mí y contacto, todo leído de la API pública de Laravel. Los medios estáticos de la escena viven en `web/public/media/scroll/`; `web/public/media/images/` y `web/public/media/loops/` quedan reservadas para medios decorativos futuros (nunca contenido profesional, que se sube desde Filament).
- `api/` — Laravel API + administración.
- `infra/` — Docker e infraestructura específica del portfolio.
- `docs/` — documentación del producto, arquitectura y servidor.

La arquitectura usa Caddy como único gateway local, el front Vite interno (desde Fase 6; antes Next.js), Apache + Laravel interno, MySQL 8.4 persistente para desarrollo y un perfil de pruebas con MySQL 8.4 descartable. El contrato local es `http://localhost:8000`; los servicios internos no publican puertos al host.

## Entorno local

PowerShell/Windows Terminal con Docker Desktop y backend WSL2 es el flujo Windows canónico. Docker Desktop es el único engine: habilitar la integración de Ubuntu WSL2 y no instalar un segundo Docker Engine/dockerd dentro de Ubuntu. El checkout actual de Windows funciona; trasladarlo al filesystem WSL solo es una optimización futura que debe justificarse con una medición.

El servicio web de desarrollo ejecuta Vite con `VITE_USE_POLLING=true` (polling del watcher) y `VITE_HMR_CLIENT_PORT` igual al puerto del gateway. Es necesario para detectar de forma fiable ediciones del bind mount Windows/9p desde el contenedor y para que el HMR vuelva a través de Caddy; Caddy conserva su función de gateway y no interviene en ese watcher. Si se modifica esta configuración, comprobar una edición y su reversión en `/es` a través de `http://localhost:8000` sin reiniciar servicios ni hacer una recarga manual.

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

El comando guarda la clave directamente en el `.env` sin imprimir su valor. Tras completar los demás placeholders requeridos, el bootstrap limpio exige primero revisar el alcance. Ejecutar `down --volumes` solamente si cada contenedor y volumen listado pertenece a este proyecto:

```powershell
docker compose ps -a
docker compose config --volumes
docker volume ls --filter label=com.docker.compose.project=portfolio

# Tras confirmar el alcance anterior, elimina únicamente recursos de Compose de este proyecto.
docker compose --profile test down --volumes --remove-orphans
```

El bootstrap ordinario desde esos volúmenes vacíos es:

```powershell
docker compose build
docker compose run --rm --no-deps web pnpm install --frozen-lockfile
docker compose run --rm --no-deps api composer install --no-interaction --prefer-dist
docker compose up -d --wait
docker compose exec -T api php artisan migrate
docker compose ps
```

El volumen `web_node_modules` comienza realmente vacío: Compose usa `nocopy` y la imagen web no contiene `node_modules`. La instalación anterior es el único paso que lo puebla; el store de pnpm vive solo en `/pnpm/store` dentro del contenedor y el arranque normal no instala dependencias.

`up -d --wait` es intencional: `/up` confirma el boot de Laravel pero las migraciones necesitan que MySQL ya esté healthy. El entrypoint de la API crea directorios runtime y permisos, pero **nunca** crea `public/storage`. Antes de ejecutar `storage:link`, comprobar que el único path a retirar es el enlace no versionado de Laravel; `storage:unlink` elimina exclusivamente el enlace configurado por Laravel, no un directorio real.

```powershell
$storageLink = Join-Path (Resolve-Path -LiteralPath 'api\public') 'storage'
git ls-files --error-unmatch -- api/public/storage 2>$null
if ($LASTEXITCODE -eq 0) { throw 'Refusing to remove a tracked storage path.' }

if (Test-Path -LiteralPath $storageLink) {
  $item = Get-Item -Force -LiteralPath $storageLink
  if (($item.Attributes -band [IO.FileAttributes]::ReparsePoint) -eq 0) {
    throw 'Refusing to remove a non-link storage path.'
  }

  docker compose exec -T api sh -lc 'test -L public/storage'
  docker compose exec -T api php artisan storage:unlink
}

docker compose exec -T api php artisan storage:link
docker compose exec -T api sh -lc 'test -L public/storage && test -d storage/app/public'
```

Ese mismo enlace `public/storage` es lo que Fase 4 usa para exponer copias públicas de foto/imagen/ícono: la aplicación solo copia un asset a `storage/app/public` (el volumen `api_public_media`) después de verificar que su dueño está publicado y visible, y el Resource público solo emite una URL después de reverificar que esa copia existe en disco. Ningún PDF de CV pasa nunca por `storage/app/public` ni por este enlace; se sirve exclusivamente por streaming autenticado desde `/cv/*` (ver `docs/api/PUBLIC_API_V1.md`).

Compose no crea migraciones, seeds ni administradores por sí solo. La política de seeds de Fase 3 es no ejecutar `db:seed` ni `migrate --seed`: no existen seeds de credenciales y cualquier seed futuro debe ser seguro, revisado y solicitado explícitamente. Crear el primer administrador solo en una terminal local interactiva; la contraseña queda fuera de argumentos, variables, historial, Git y documentación:

```powershell
docker compose exec api php artisan portfolio:bootstrap-admin
```

Fase 4 agrega un único seed opcional, explícito y nunca automático: `PortfolioContentSeeder`. No forma parte del bootstrap ni de ningún script; se ejecuta solo cuando alguien lo pide deliberadamente, típicamente para revisión editorial local:

```powershell
docker compose exec api php artisan db:seed --class=PortfolioContentSeeder
```

Es seguro ejecutarlo más de una vez: usa `updateOrCreate` sobre claves/tipos propios aprobados, nunca publica ni hace visible contenido, nunca crea `Project`, `CvDocument`, usuarios o secretos, y nunca sube ni copia un archivo a ningún disco (ver `docs/DEPLOYMENT.md`).

El límite de autenticación/autorización de Filament sigue siendo exactamente el de Fase 3 (`users.is_admin` + `portfolio:bootstrap-admin`); Fase 4 agrega recursos y páginas administrables dentro de ese mismo panel, sin cambiar cómo se crea o autoriza un administrador.

Fase 5 agrega un comando guardado de importación única, nunca automático y nunca parte de este bootstrap: `php artisan portfolio:import-initial-content`, sin flags ni modos. Solo se ejecuta contra una base de contenido editorial pristina (los dos singletons estructurales de Fase 4 sin ningún campo editorial y las doce tablas de contenido/pivote vacías); cualquier desviación, incluida una segunda ejecución después de un primer éxito, se rechaza sin tocar base de datos ni filesystem. El detalle completo de precondiciones, resultado y la secuencia de revisión/publicación en Filament que debe seguir está en `docs/DEPLOYMENT.md`; este repositorio no lo ejecuta contra producción, solo lo verifica en un stack Docker de integración aislado con datos sintéticos de QA.

Para reiniciar sin borrar datos: `docker compose restart`. Para detener el entorno: `docker compose stop`. Los logs se consultan con `docker compose logs --tail=200 <servicio>`.

### Verificación local

```powershell
node infra/validation/validate-repository.mjs
docker compose --env-file .env config --quiet
docker compose run --rm --no-deps web pnpm typecheck
docker compose run --rm --no-deps web pnpm test:run
docker compose --profile test run --rm api-test
docker compose run --rm --no-deps web pnpm build
.\infra\validation\verify-hmr.ps1
```

El front se sirve a través del gateway en `http://localhost:8000/` y `http://localhost:8000/es` (ES) y `http://localhost:8000/en` (EN). Es una SPA: el build no depende de Laravel, Caddy ni MySQL, y el contenido profesional se pide en el navegador a `/api/v1/{locale}/profile`, `/site` y `/technologies` (mismo origen, sin CORS). Solo se muestra contenido publicado y visible.

`api-test` usa únicamente `mysql-test` descartable. Después de una sesión de test se puede retirar exclusivamente ese contenedor con `docker compose --profile test rm -sf mysql-test`.

`docker compose --profile test run --rm api-test` ya ejecuta `php artisan test` (el `command` del servicio); la forma explícita, útil para pasar opciones, es:

```powershell
docker compose --profile test run --rm api-test php artisan test
docker compose --profile test run --rm api-test php artisan test --compact
docker compose --profile test run --rm api-test php artisan test --filter=ApiContractDocumentationTest
```

Todas las pruebas de dominio, API, caché, assets y Filament de Fase 4 corren contra `mysql-test` real, nunca SQLite. Verificaciones adicionales específicas de Fase 4:

```powershell
docker compose run --rm --no-deps api ./vendor/bin/pint --test
node infra/validation/validate-repository.mjs
docker compose run --rm --no-deps api php artisan route:list --path=api/v1
docker compose run --rm --no-deps api php artisan route:list --path=cv
```

El detalle completo del contrato público (`docs/api/PUBLIC_API_V1.md`) y la matriz de verificación de Fase 4 (`docs/testing/PHASE_4_VERIFICATION.md`) documentan qué exactamente confirma cada comando.

Los checks directos y el inventario de rutas no imprimen secretos:

```powershell
Invoke-WebRequest http://localhost:8000/__gateway/health | Select-Object -ExpandProperty StatusCode
Invoke-WebRequest http://localhost:8000/es | Select-Object -ExpandProperty StatusCode
Invoke-WebRequest http://localhost:8000/up | Select-Object -ExpandProperty StatusCode
Invoke-RestMethod http://localhost:8000/api/v1
docker compose exec -T api php artisan route:list --json
```

Verificar los bindings reales de los contenedores que Compose inició. Este
procedimiento usa la configuración de runtime de Docker, no `docker compose
port`: algunas versiones de Compose informan erróneamente éxito para un puerto
interno no publicado. Debe listar exactamente un binding:
`gateway 80/tcp 127.0.0.1 8000`.

```powershell
$containerIds = @(docker compose ps -q)
if ($containerIds.Count -eq 0) { throw 'No running Compose containers to inspect.' }

$published = @(
  foreach ($containerId in $containerIds) {
    $container = docker inspect $containerId | ConvertFrom-Json
    $service = $container.Config.Labels.PSObject.Properties['com.docker.compose.service'].Value
    foreach ($portBinding in $container.HostConfig.PortBindings.PSObject.Properties) {
      foreach ($hostBinding in $portBinding.Value) {
        [PSCustomObject]@{
          Service = $service
          ContainerPort = $portBinding.Name
          HostIp = $hostBinding.HostIp
          HostPort = $hostBinding.HostPort
        }
      }
    }
  }
)

$published | Format-Table -AutoSize
if (
  $published.Count -ne 1 -or
  $published[0].Service -ne 'gateway' -or
  $published[0].ContainerPort -ne '80/tcp' -or
  $published[0].HostIp -ne '127.0.0.1' -or
  $published[0].HostPort -ne '8000'
) {
  throw 'Expected only gateway 80/tcp to publish 127.0.0.1:8000.'
}
```

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

En Bash, el equivalente completo de la copia inicial y la generación de `APP_KEY`
también se niega a sobrescribir un `.env` existente. `python3` genera la clave y
la escribe directamente sin imprimirla:

```bash
if [ -e .env ]; then
  printf '%s\n' 'Refusing to overwrite existing .env' >&2
else
  cp .env.example .env
  python3 - <<'PY'
from base64 import b64encode
from pathlib import Path
from secrets import token_bytes

path = Path('.env')
contents = path.read_text(encoding='utf-8')
replacement = 'APP_KEY=base64:' + b64encode(token_bytes(32)).decode('ascii')
if 'APP_KEY=' not in contents:
    raise SystemExit('APP_KEY placeholder is missing')
path.write_text(contents.replace('APP_KEY=base64:replace-with-generated-laravel-key', replacement, 1), encoding='utf-8')
PY
fi
```

La negativa no termina la shell interactiva de WSL. El equivalente Bash del bootstrap, enlace, health, inventario y puertos es:

```bash
docker compose ps -a
docker compose config --volumes
docker volume ls --filter label=com.docker.compose.project=portfolio
# Confirm the listed resources belong to this project before this destructive step.
docker compose --profile test down --volumes --remove-orphans
docker compose build
docker compose run --rm --no-deps web pnpm install --frozen-lockfile
docker compose run --rm --no-deps api composer install --no-interaction --prefer-dist
docker compose up -d --wait
docker compose exec -T api php artisan migrate

(
  storage_link='api/public/storage'
  if git ls-files --error-unmatch -- "$storage_link" >/dev/null 2>&1; then
    printf '%s\n' 'Refusing to remove a tracked storage path.' >&2
    exit 1
  fi
  if [ -e "$storage_link" ] || [ -L "$storage_link" ]; then
    docker compose exec -T api sh -lc 'test -L public/storage'
    docker compose exec -T api php artisan storage:unlink
  fi
  docker compose exec -T api php artisan storage:link
  docker compose exec -T api sh -lc 'test -L public/storage && test -d storage/app/public'
)

curl --fail http://localhost:8000/__gateway/health
curl --fail http://localhost:8000/es
curl --fail http://localhost:8000/up
curl --fail http://localhost:8000/api/v1
docker compose exec -T api php artisan route:list --json
container_ids="$(docker compose ps -q)"
if [ -z "$container_ids" ]; then
  printf '%s\n' 'No running Compose containers to inspect.' >&2
  exit 1
fi
published_ports="$(
  for container_id in $container_ids; do
    docker inspect --format '{{ $service := index .Config.Labels "com.docker.compose.service" }}{{ range $port, $bindings := .HostConfig.PortBindings }}{{ range $binding := $bindings }}{{ printf "%s %s %s %s\n" $service $port $binding.HostIp $binding.HostPort }}{{ end }}{{ end }}' "$container_id"
  done | sed '/^$/d'
)"
printf '%s\n' "$published_ports"
expected_port='gateway 80/tcp 127.0.0.1 8000'
if [ "$published_ports" != "$expected_port" ]; then
  printf '%s\n' 'Expected only gateway 80/tcp to publish 127.0.0.1:8000.' >&2
  exit 1
fi
```

La misma regla de secretos aplica: no pasar una contraseña al comando, entorno ni historial. La política de seeds no cambia entre PowerShell y Bash: no ejecutar `db:seed` ni `migrate --seed` durante Fase 3.

## Flujo Git

`main` es la única rama estable de larga duración. El trabajo usa ramas cortas con prefijos como `feat/`, `fix/`, `docs/`, `refactor/`, `test/`, `chore/` e `infra/`, y llega a `main` mediante pull request después de las validaciones relevantes. No existe una rama permanente `develop`, no se reescribe historial compartido y ningún trabajo se integra en `main` sin aprobación explícita.

Los tags anotados se crean desde commits aprobados de `main` solo para hitos o releases significativos. Desde Fase 11, un tag versionado de release dispara el workflow que publica las imágenes productivas en GHCR.

`main` se protege de forma liviana: no se fuerza el push, no se borra la rama y no se reescribe su historial. Los cambios llegan por pull request con CI verde. El tag de release apunta siempre a un commit ya validado de `main`.

## Runtime productivo local

El stack productivo es independiente del de desarrollo y puede levantarse en cualquier máquina con Docker. Usa siempre un nombre de proyecto y un puerto distintos para no interferir con el entorno de desarrollo:

```bash
# El archivo de entorno vive FUERA del repositorio y nunca se versiona.
cp .env.production.example ../portfolio-production.env
# Editar ../portfolio-production.env con valores locales descartables.

docker compose --env-file ../portfolio-production.env \
  -f compose.production.yaml -p portfolio-production-test up -d --build --wait

# Base fresca: migraciones, import inicial guardado y primer administrador.
docker compose --env-file ../portfolio-production.env \
  -f compose.production.yaml -p portfolio-production-test \
  exec api php artisan migrate --force

docker compose --env-file ../portfolio-production.env \
  -f compose.production.yaml -p portfolio-production-test \
  exec api php artisan portfolio:import-initial-content

docker compose --env-file ../portfolio-production.env \
  -f compose.production.yaml -p portfolio-production-test \
  exec api php artisan portfolio:bootstrap-admin

# Smoke funcional reproducible contra el puerto publicado.
infra/validation/smoke-production.sh http://127.0.0.1:8000
```

Solo el gateway publica un puerto, sobre loopback. MySQL no es alcanzable desde el host. Los volúmenes `mysql_data`, `api_private_media` y `api_public_media` sobreviven a `restart` y a `down` + `up`; `down --volumes` sí los destruye.

La analítica opcional se activa fijando `UMAMI_TRACKER_URL` y `UMAMI_WEBSITE_ID` y reiniciando el contenedor `web`: la imagen y su digest no cambian. Si falta cualquiera de los dos, o son inválidos, la analítica queda OFF y `/runtime-config.json` responde `404`; en ambos casos con `Cache-Control: no-store`.

## Contexto de producción

El portfolio se ejecutará en un VPS Linux de OVHcloud junto con otros proyectos Dockerizados. **Todavía no está desplegado.**

```text
Internet
   ↓
Cloudflare (DNS proxied, SSL/TLS Full (strict))
   ↓
Caddy GLOBAL del VPS :443
   ↓
127.0.0.1:8000
   ↓
Caddy/gateway INTERNO del portfolio
   ├── Front Vite + React
   └── Laravel / Filament
          ↓
        MySQL
```

No se usa Cloudflare Tunnel. Otros proyectos se publican con subdominios y puertos loopback diferentes detrás del mismo Caddy global.

El repositorio termina su responsabilidad al producir una release completa y desplegable (Fase 11): runtime productivo verificado localmente, CI, tag, imágenes GHCR con digests y handoff. Ahí hace STOP y nunca entra al VPS. El flujo operativo separado `vps_ops_claude` ejecuta después el deployment real, junto con el Caddy global, Cloudflare, UFW, backups y seguridad del host.

`docs/DEPLOYMENT.md` describe el contrato de release y el handoff que consume ese flujo operativo.

La arquitectura multiproyecto se documenta en:

`docs/SERVER_ARCHITECTURE.md`

El proyecto todavía debe seguir las fases activas de `ROADMAP.md`.
