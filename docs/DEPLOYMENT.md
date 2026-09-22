# DEPLOYMENT.md — Contrato de release y handoff del portfolio

## Propósito

Este documento describe el contrato de aplicación y de release que el repositorio entrega al flujo operativo `vps_ops_claude`. No es un checklist para que un agente del repositorio instale, configure u opere el VPS: **ningún agente del repositorio entra al VPS**.

> **Estado actual (2026-09-22): el portfolio NO está desplegado.** El runtime productivo, `compose.production.yaml` y los workflows de GitHub Actions existen y fueron verificados localmente. La release `v0.9.0` existe: tag anotado sobre `main` en `ac3ae1a`, GitHub Release publicada e imágenes en GHCR con sus digests registrados más abajo. Esas imágenes se descargaron por digest y pasaron el smoke completo (40/40) con la misma secuencia de deployment documentada en este archivo. El deployment al VPS **no fue ejecutado**: lo realiza después `vps_ops_claude` consumiendo exactamente esa release.
>
> **Excepción de roadmap autorizada (2026-09-22).** `v0.9.0` se produjo por decisión humana explícita de Luciano para publicar el portfolio antes de cerrar los gates normales, porque lo necesita en su CV. **Fase 9 (seguridad y endurecimiento) y Fase 10 (pruebas y control de calidad) permanecen OPEN**; sus tareas no se marcaron como hechas. `v0.9.0` es una release desplegable y funcional, no el cierre del roadmap. `v1.0.0` queda reservada para cuando Fases 9 y 10 estén completas y la imagen social final esté integrada. Ver `ROADMAP.md`, sección de la excepción.

La secuencia de responsabilidad es:

1. Fases 0–10 completan funcionalidad, pruebas y seguridad.
2. **Fase 11 (repositorio):** runtime productivo verificado localmente, CI verde, workflow de release, tag versionado sobre `main`, GitHub Release cuando corresponda, imágenes en GHCR identificables por versión y commit, digests registrados y handoff en este documento. **Ahí el repositorio hace STOP.**
3. **`vps_ops_claude` (fuera del repositorio):** despliega exactamente esa release en el VPS, de forma manual/asistida y desde un contexto operativo separado.
4. **Fase 12 (lanzamiento):** consume la release preexistente y la confirmación externa de deployment, smoke y backup inicial. No crea tags, releases ni imágenes.

La topología compartida de referencia permanece en `docs/SERVER_ARCHITECTURE.md`. No se copian aquí las instrucciones de `vps_ops_claude`.

## Límites de responsabilidad

### Responsabilidad del repositorio

- Definir servicios y dependencias de la aplicación.
- Proporcionar un entorno Docker completo de desarrollo y pruebas (`compose.yaml`).
- Crear Dockerfiles productivos y un `compose.production.yaml` portable (Fase 11).
- Levantar y verificar localmente el runtime productivo: migraciones desde cero, import inicial, bootstrap administrativo, healthchecks, persistencia y smoke.
- Documentar variables, secretos requeridos (sin valores), persistencia, migraciones, bootstrap y health checks.
- Proporcionar comandos reproducibles de build, pruebas y smoke checks.
- Mantener CI y el workflow de release; crear el tag, la GitHub Release cuando corresponda y las imágenes GHCR con digests registrados.
- Documentar el contrato de rollback de aplicación e identificar los datos que requieren backup.
- Mantener el gateway Caddy interno como único entrypoint del proyecto.
- No incluir secretos reales ni credenciales de Cloudflare.

### Responsabilidad de operaciones (`vps_ops_claude`)

- Host, SSH, UFW, Docker del host y reboot recovery.
- Caddy global del VPS (`/srv/ingress`): site de `lucianogonzalez.dev` hacia `127.0.0.1:8000` y TLS de origen.
- Cloudflare: DNS proxied, SSL/TLS `Full (strict)` y controles de acceso opcionales.
- Crear `/srv/apps/portfolio` (checkout exacto del tag de release), `/srv/ops/portfolio/compose.vps.yaml` y `/srv/backups/portfolio`.
- Crear y custodiar los secretos productivos reales.
- Pull de imágenes por tag inmutable o digest exacto; migraciones, import inicial y bootstrap administrativo en producción.
- Backups, restores, monitoreo y rollback real.
- Smoke en producción, verificación pública y cierre del deployment.

## Frontera absoluta con el VPS

La fase del repositorio **termina al producir la release**. Ni en Fase 11 ni en ninguna otra tarea del repositorio un agente:

- abre SSH, se conecta al VPS ni usa PuTTY u otro acceso remoto;
- hace `git pull` bajo `/srv/apps`;
- crea `/srv/apps/portfolio`, `/srv/ops/portfolio` ni overrides reales del VPS;
- edita `/srv/ingress`, modifica el Caddy global, UFW, Cloudflare o DNS;
- crea secretos productivos reales o certificados del host;
- ejecuta migraciones contra producción o levanta contenedores en OVH;
- modifica backups reales o ejecuta un rollback real;
- hace smoke contra producción;
- configura deployment automático con GitHub Actions o runners con acceso al VPS.

## Contrato público

```text
Internet
    -> Cloudflare (DNS proxied, SSL/TLS Full (strict))
    -> Caddy GLOBAL del VPS :443                 (operaciones)
    -> http://127.0.0.1:8000
    -> gateway Caddy INTERNO del portfolio       (repositorio)
         ├── Front Vite + React (SPA)
         └── Laravel -> MySQL
```

- Hostname: `lucianogonzalez.dev`.
- Entry point del proyecto: `127.0.0.1:8000`, contrato entre aplicación y operaciones.
- Solo el gateway publica ese puerto; en el runtime productivo el bind es configurable con default loopback.
- El Caddy global, Cloudflare y el host no forman parte del Compose ni de este repositorio.
- No existe `cloudflared` ni Cloudflare Tunnel en la arquitectura.
- Ninguna credencial o API token de Cloudflare ingresa en este repositorio.

## Servicios y rutas

Stack conceptual entregado:

```text
portfolio-gateway
portfolio-web
portfolio-api
portfolio-mysql
```

Rutas del mismo origen:

```text
/            -> front; portfolio en español
/es          -> front; portfolio en español
/en          -> front; portfolio en inglés
/api/*       -> Laravel
/admin/*     -> Laravel / Filament
```

El gateway del portfolio es Caddy. `portfolio-api` sirve Laravel mediante Apache interno con `public/` como `DocumentRoot`; Caddy no monta ni interpreta archivos de Laravel.

El handoff de Fase 3 registra las rutas Laravel estructuradas y también las rutas públicas observadas que Filament, Livewire y media requieren. Los matchers se derivan de `route:list --json` más smoke checks reales; no se adivina ni se hardcodea el hash variable de Livewire. Web, API y MySQL usan DNS/redes internas de Docker; sus puertos internos no forman parte del contrato público.

Caddy usa su comportamiento normal de `Host` y forwarded headers. En producción cada request atraviesa dos proxies (Caddy global del VPS y gateway interno) antes de llegar al front o a Laravel. Fase 11 debe definir y verificar localmente el tratamiento de `Host`, protocolo reenviado y trusted proxies para esa cadena, sin configurar Cloudflare ni el Caddy global; operaciones confirma el comportamiento real durante el deployment.

## Redes y exposición

- Gateway, web y API comparten una red frontal específica del portfolio.
- API y MySQL comparten una red de datos específica del portfolio.
- MySQL no pertenece a la red frontal y no publica `3306` al host.
- Ningún servicio usa `network_mode: host` salvo una futura decisión operacional documentada.
- Redes, volúmenes y credenciales no se comparten con otros proyectos.
- Desde Fase 6, `web` no hace requests internas a `api`: el navegador pide `/api/*`, `/storage/*` y `/cv/*` al mismo origen y Caddy los enruta directo a `api`.

## Persistencia relevante

Datos no regenerables que operaciones debe persistir y respaldar:

- base MySQL del portfolio;
- media administrada por Laravel/Filament;
- CV administrados cuando el CMS los gestione localmente;
- cualquier otro archivo de aplicación declarado persistente en una fase posterior.

Los contenedores son reemplazables; eliminar o recrear un contenedor no debe borrar esos datos. La ubicación física final y la política de backup se deciden externamente después de inspeccionar el servidor.

### Fase 4 — dos volúmenes de media persistentes

Desde Fase 4 la persistencia de media se divide en dos volúmenes Docker con nombre, declarados en `compose.yaml`:

- `api_private_media` -> `storage/app/private` dentro del contenedor API. Contiene los originales privados de foto de Profile, imagen de Project, ícono de Technology, y **todo** PDF de CV. Nunca se sirve directamente; solo se lee por código de aplicación (copia a `public` cuando corresponde, o streaming controlado para CV).
- `api_public_media` -> `storage/app/public` dentro del contenedor API, expuesto vía el enlace estándar `public/storage`. Contiene únicamente copias públicas verificadas de foto/imagen/ícono cuyo dueño está `published` y `is_visible=true` en ese momento.

Ambos son volúmenes Docker nombrados y reemplazar o recrear el contenedor `api` no los borra. La API nunca escribe un asset owned (foto/imagen/ícono/CV) fuera de estos dos volúmenes, y el disco `public` nunca contiene un PDF de CV.

### Permisos de storage

El proceso Apache/PHP dentro del contenedor API necesita permisos de escritura sobre `storage/` (incluyendo ambos subárboles `app/private` y `app/public`) y `bootstrap/cache`, igual que en Fase 3. El entrypoint de la API ya administra esos directorios/permisos; no crea el enlace `public/storage` por sí mismo (ver Fase 3, sección de bootstrap). Un volumen de producción para `api_private_media`/`api_public_media` debe conservar esos mismos permisos de escritura del proceso de aplicación tras cualquier restore.

### Requisitos del almacén de caché

El contenido público (`PublicContentCache`) requiere que el almacén de caché configurado implemente `Illuminate\Contracts\Cache\LockProvider`; el servicio lo verifica en tiempo de ejecución y falla explícitamente en vez de degradar silenciosamente el contrato de bloqueo. En este repositorio:

- el servicio `api` de desarrollo fija `CACHE_STORE=file` en `compose.yaml`;
- `api/.env.example` y el default de Laravel (`config/cache.php`) usan `database` (que ya tiene su migración de tablas `cache`/`cache_locks`);
- el servicio `api-test` descartable fija `CACHE_STORE=array`, válido únicamente para pruebas de un solo proceso;
- las pruebas de concurrencia/lock de caché fuerzan explícitamente `file` o `database`, nunca `array`.

Tanto Laravel `FileStore` como `DatabaseStore` implementan `LockProvider`; ninguno requiere Redis. Un handoff de producción puede conservar `file` (un solo contenedor API con filesystem de caché compartido) o usar `database` (ya migrada); no debe seleccionarse un almacén sin soporte de lock (por ejemplo, un backend que no implemente `LockProvider`). Esto no es una decisión de operaciones externas: es un requisito de la propia aplicación que cualquier configuración de despliegue debe respetar.

### Nota de compatibilidad de lock atómico

El lock de reconstrucción (`public-content-rebuild:v1:{locale}:{endpoint}`) y el lock de mutación administrativa comparten el mismo almacén de caché configurado. Si el almacén de producción cambia de `file`/`database` a otro backend, ese backend debe seguir implementando `LockProvider` con semántica de adquisición atómica real (no una emulación de "mejor esfuerzo"); de lo contrario la garantía documentada en `docs/api/PUBLIC_API_V1.md` (una petición pública nunca ve una reconstrucción parcial ni contenido obsoleto tras una reducción de visibilidad) deja de sostenerse.

### Ruta de CV

`GET /cv/luciano-gonzalez-es.pdf` y `GET /cv/luciano-gonzalez-en.pdf` son rutas Laravel estables, registradas fuera de `/api/v1` (ver `infra/caddy/ROUTE_OWNERSHIP.md`, familia `/cv/*`). Sirven contenido de `api_private_media` mediante streaming autenticado por estado en cada request; no dependen de la caché pública ni de `/storage/*`. Operaciones no necesita configuración adicional para esta ruta más allá de lo ya cubierto por la persistencia de `api_private_media` y el mismo entrypoint/gateway existentes.

### Semilla de contenido (opcional, nunca automática)

`PortfolioContentSeeder` es explícito y nunca se ejecuta como parte de bootstrap, migración o despliegue. Cuando un operador decide cargarlo deliberadamente (por ejemplo, para revisión editorial en un entorno no productivo):

```bash
php artisan db:seed --class=PortfolioContentSeeder
```

El seeder es idempotente (`updateOrCreate` sobre claves/tipos propios aprobados), nunca publica ni hace visible nada, nunca crea `Project`, `CvDocument`, usuarios o secretos, y nunca sube ni copia un archivo a ningún disco. Ejecutarlo dos veces no duplica filas ni borra contenido no relacionado.

### Importación inicial guardada de contenido (Fase 5, un solo uso)

`php artisan portfolio:import-initial-content` (sin flags, opciones ni modos) es un comando guardado de **un solo uso** que, a diferencia del seeder anterior, no es repetible: está diseñado para ejecutarse exactamente una vez contra una base de contenido editorial recién migrada, y rechaza cualquier ejecución posterior. Este documento describe su contrato; no lo ejecuta, y no afirma que se haya ejecutado contra datos de producción — solo se ha verificado en un stack Docker de integración aislado (Tarea 13, evidencia completa en `docs/testing/PHASE_5_VERIFICATION.md`) con datos sintéticos de QA.

**Precondición (preflight, completamente no mutante, corre antes de cualquier escritura):**

- Exactamente los dos singletons estructurales pristinos de Fase 4: `profiles.default` y `site_configurations.default`, cada uno con `status=draft`, `is_visible=false`, `published_at=null` y **todas** sus columnas editoriales/de asset reales en `null`.
- Las doce tablas de contenido/pivote restantes literalmente vacías: `experiences`, `experience_highlights`, `work_cases`, `projects`, `technologies`, `expertise_areas`, `work_principles`, `professional_links`, `cv_documents`, `experience_technology`, `technology_work_case`, `project_technology`.

Cualquier desviación — un singleton no pristino, una fila extra de singleton, o cualquier fila en las doce tablas — rechaza el comando con un mensaje seguro y **no produce ningún cambio en base de datos ni en filesystem**. Esto se cumple también en cada reejecución posterior a un primer éxito, incluso después de que un administrador haya publicado o editado contenido: el comando no vuelve a encontrar la línea base pristina y se rechaza igual, por diseño. No es un seed repetible; es una compuerta de una sola apertura.

**Resultado en éxito:** los dos singletons existentes se llenan por `id` (nunca se reemplazan), se crean las colecciones del dataset aprobado, y la foto aprobada más ambos CVs aprobados se adjuntan mediante el mismo `AssetLifecycleService::replace()` que usa Filament. Cada registro creado/actualizado queda `status=draft`, `is_visible=false`, `published_at=null` sin excepción — el comando re-estampa este estado sin importar lo que el dataset traiga. La foto y los CV importados quedan en el mismo estado de ownership privado de Fase 4 (`photo_public_path` permanece `null`; la ruta de descarga de CV permanece no disponible) hasta que un humano publique a través del flujo normal de Filament.

**Compensación de filesystem:** si un paso posterior falla, la transacción hace rollback y el comando borra únicamente las rutas de storage privado que él mismo creó durante ese intento concreto — nunca un archivo preexistente, nunca nada en el disco público. Es una compensación pequeña y específica de este comando, no un framework genérico de transacciones de media.

**Entrada de assets, de solo lectura:** las tres fuentes de asset aprobadas (`docs/content/approved-assets/{professional-photo.jpg,cv-es.pdf,cv-en.pdf}`) se leen desde el propio repositorio, expuesto al contenedor API mediante un mount de Compose de solo lectura, `./docs:/var/www/docs:ro`, presente tanto en `api` como en `api-test`. Estos archivos nunca se copian a `web/public` ni a un volumen público; son entrada versionada y read-only, no un dato persistente que operaciones deba respaldar por separado.

**Secuencia humana requerida después de un import exitoso** (ningún paso de esta secuencia se ejecuta automáticamente por Fase 5):

1. Ejecutar el import una sola vez.
2. Revisar el contenido en borrador y ambos assets en Filament.
3. Completar los campos que todavía requieren aprobación humana (ver la brecha editorial documentada abajo).
4. Publicar a través de las acciones normales de publicación de Fase 4.
5. Fase 4 invalida su propia caché.
6. El API pública empieza a servir el contenido.

**Brecha editorial conocida, no resuelta por este comando:** no existe todavía organización/rol/fecha de inicio aprobados para ninguna `Experience` (queda `[]` tras el import); cada `WorkCase` solo tiene título y un párrafo de contexto aprobados, así que `problem/contribution/technical_approach/outcome` quedan `null` y ese caso no puede publicarse todavía; las ocho etiquetas `technology_*_label_{es,en}` del Site tampoco están aprobadas, así que el Site tampoco puede publicarse todavía; `Project` permanece deliberadamente vacío hasta Fase 7. Estas son decisiones editoriales humanas pendientes, no defectos de implementación, y bloquean la aceptación editorial de Fase 5 (spec §3.1) de forma independiente de que la implementación esté técnicamente completa.

## Variables y secretos

El repositorio proporciona archivos de ejemplo con placeholders y documentación de ownership. Los valores reales permanecen fuera de Git.

Categorías mínimas esperadas para producción:

- configuración no secreta del servidor de archivos estáticos del front;
- URL/origen público de la aplicación cuando corresponda;
- Laravel `APP_KEY` y configuración de entorno;
- credenciales MySQL específicas del portfolio;
- configuración de sesión, logs y filesystem;
- contrato del bootstrap administrativo interactivo, sin contener la contraseña.

No pertenecen al repositorio ni al entorno de aplicación:

- credenciales o API tokens de Cloudflare;
- credenciales de otros proyectos;
- configuración global del host;
- claves SSH, runners o secretos de automatización operativa.

## Build y runtime

La documentación de handoff debe permitir a operaciones identificar:

- versiones canónicas de Node.js, pnpm, PHP, Laravel, Filament y MySQL;
- comandos de instalación con lockfiles;
- comandos de build de `web` y validación de `api`;
- procesos y puertos internos esperados;
- archivos o volúmenes requeridos por cada servicio;
- señales de readiness/health y dependencias de arranque.

Fase 3 no crea targets de producción especulativos ni un Compose final del servidor. Las definiciones de desarrollo deben evitar supuestos exclusivos de Windows y mantener límites claros sobre los que Fase 11 construye el runtime productivo. Ese runtime (Dockerfiles productivos y `compose.production.yaml`) lo crea el propio repositorio y se verifica localmente antes de la release; operaciones solo agrega el override del VPS.

Desde Fase 6, `web` es una SPA Vite: `pnpm build` genera archivos estáticos en `web/dist` sin depender de Laravel, y el contenido se pide en el navegador a la API pública. Por eso el build de imagen de producción de `web` nunca requiere que el API de producción esté disponible. Fase 11 define cómo se sirven esos archivos detrás del gateway (el gateway no sirve archivos de aplicación directamente).

La validación local de aceptación usa Caddy `2.11.4-alpine`, Node `24.18.0`,
pnpm `11.20.0`, Vite 8 con React 18, PHP `8.5.8`, Laravel `13.25.0`, Filament
`5.7.6` y MySQL `8.4`. Los puertos internos son Caddy `80`, Vite `5173`,
Apache/Laravel `80` y MySQL `3306`; el único publish local es Caddy
`127.0.0.1:8000`. Las señales de aplicación entregadas son Caddy
`/__gateway/health`, Vite `GET /`, Laravel `/up` y `mysqladmin ping`.

El bootstrap local instala desde lockfiles en los volúmenes vacíos, espera
health, ejecuta migraciones y crea explícitamente `public/storage` solo después
de comprobar que un enlace existente es no versionado. El entrypoint de API no
crea ese enlace. El bootstrap administrativo es separado e interactivo.
Fase 11 traslada este contrato al runtime productivo y lo verifica en local;
operaciones aplica el override del VPS y ejecuta el deployment. El repositorio
no ejecuta acciones de host.

## Migraciones y bootstrap

- Las migraciones de Laravel son la fuente reproducible del esquema.
- Desarrollo y pruebas deben construir una base limpia mediante migraciones.
- Producción usa migraciones no destructivas ejecutadas conscientemente por operaciones.
- Nunca se programa `migrate:fresh`, resets o seeds destructivos contra datos persistentes de producción.
- Los seeds normales contienen solo datos seguros y no crean credenciales.
- En desarrollo, el primer administrador de Filament se crea mediante `php artisan portfolio:bootstrap-admin`, un comando interactivo create-only con password oculto y confirmación.
- El comando rechaza duplicados/estados ambiguos, no actualiza usuarios y no imprime ni registra secretos.
- En producción, el operador ejecuta el mismo comando interactivo dentro del contenedor API del runtime productivo; Fase 11 verifica ese flujo en local. Un mecanismo no interactivo no forma parte del contrato vigente y requeriría una decisión explícita documentada antes de implementarse.

Las unidades persistentes relevantes son MySQL y, desde Fase 4, los dos
volúmenes de media descritos arriba: `api_private_media`
(`storage/app/private`, incluyendo todo CV) y `api_public_media`
(`storage/app/public`). `web_node_modules` y `api_vendor` son cachés
regenerables. `mysql-test` usa `tmpfs`, no entra en backups y no contiene
datos de desarrollo.

Las migraciones de Fase 4 son puramente aditivas: crean tablas y columnas
nuevas, no alteran ni eliminan estructura de Fases 1–3. `migrate` en
producción permanece seguro sin pasos de compatibilidad especiales
adicionales a los ya vigentes.

## Health checks y smoke checks

El contrato debe definir checks que no revelen secretos para:

- MySQL readiness;
- proceso Laravel;
- endpoint de salud de la API;
- servicio del front;
- gateway y rutas principales.

### Smoke local del runtime productivo (Fase 11, repositorio)

Contra el stack levantado localmente con `compose.production.yaml`, desde una base fresca:

- `/` responde con el front en español;
- `/es` y `/en` responden;
- `/api/v1` y los endpoints públicos localizados responden con el contrato esperado;
- `/admin` responde y requiere autenticación;
- `/storage/*` y `/cv/*` responden según el estado de publicación;
- migraciones, import inicial y bootstrap administrativo funcionan;
- los datos persisten tras restart y recreate;
- MySQL no es alcanzable desde el host;
- el gateway es el único puerto publicado por el proyecto.

### Smoke en producción (operaciones, fuera del repositorio)

Después del deployment, ejecutado por `vps_ops_claude` y nunca por un agente del repositorio, a través de `https://lucianogonzalez.dev`:

- `/` responde con el front en español;
- `/es` y `/en` responden;
- `/api/v1` responde con el contrato base esperado;
- `/admin` responde y requiere autenticación;
- MySQL no es alcanzable desde el host ni Internet;
- el gateway es el único puerto publicado por el proyecto, sobre loopback.

## Logs

- Laravel, el front y el gateway escriben a stdout/stderr o a destinos documentados compatibles con contenedores.
- Los logs no contienen secretos, credenciales, datos financieros reales ni información confidencial.
- Rotación, retención, recolección y acceso en producción pertenecen a operaciones externas.

## Backup y rollback: información entregada

El repositorio identifica qué datos necesitan backup y qué migraciones pueden afectar compatibilidad. Operaciones define rutas físicas, agenda, retención, copia externa y restore.

Antes del lanzamiento, el cierre de deployment de operaciones debe confirmar:

- backup inicial de MySQL y media;
- restore probado según el workflow operacional;
- release, commit y digests desplegados;
- ruta para volver a una versión anterior;
- tratamiento de migraciones incompatibles;
- smoke checks posteriores al rollback.

### Frontera de backup/restore de assets (Fase 4)

Siguiendo la misma frontera que `docs/SERVER_ARCHITECTURE.md` ya establece para MySQL, el repositorio identifica qué debe respaldarse pero no ejecuta backups:

- **Debe respaldarse:** el contenido de `api_private_media` (todo original privado de foto/imagen/ícono, y todo PDF de CV — sin esto el asset se pierde de forma irrecuperable) y `api_public_media` (copias públicas verificadas; regenerables desde el original privado por la propia aplicación, pero su backup evita una ventana de imagen faltante tras un restore).
- **Responsabilidad externa:** rutas físicas de backup, agenda, retención, copia externa/offsite y el restore mismo del contenido de esos volúmenes, exactamente igual que para el volumen de datos de MySQL. El repositorio no ejecuta ni programa esa copia.
- Un restore de `api_private_media` sin el `api_public_media` correspondiente es seguro: la aplicación no recrea automáticamente copias públicas al arrancar, así que un asset publicado antes del backup queda con imagen ausente hasta la próxima operación de publicación/edición sobre ese registro. Esto no es un error de aplicación a corregir; es el comportamiento esperado de un asset "propio" sin daemon de reconciliación (explícitamente fuera de alcance, spec sección 22).

### Advertencias de rollback (Fase 4)

- `key_locked` es de una sola dirección durante la vida de una fila: `PublishContent` la fija a `true` en la primera publicación y ningún código la vuelve a `false`. Revertir a una versión de aplicación anterior no revierte ese estado en la base de datos; una fila publicada antes del rollback permanece con la clave bloqueada.
- Las migraciones de Fase 4 son aditivas (tablas nuevas, sin `ALTER`/`DROP` sobre tablas de Fases 1–3). Un rollback de aplicación a una versión pre-Fase-4 con la base de datos ya migrada a Fase 4 deja tablas nuevas sin uso, pero no rompe el esquema previo; no se requiere una migración `down` destructiva para un rollback seguro de código.
- Ningún rollback de código elimina archivos de `api_private_media`/`api_public_media` por sí mismo; la limpieza de esos volúmenes sigue siendo responsabilidad explícita de operaciones si realmente se desea revertir contenido, no solo código.

## Runtime productivo (entregado en Fase 11)

El runtime productivo existe, es portable y fue verificado localmente. Los archivos que lo componen:

| Artefacto | Ruta |
|---|---|
| Compose productivo portable | `compose.production.yaml` |
| Entrada de entorno de ejemplo | `.env.production.example` |
| Imagen frontend | `infra/docker/web/Dockerfile` |
| Config estática del frontend | `infra/docker/web/Caddyfile` |
| Materialización de runtime-config | `infra/docker/web/entrypoint.sh` |
| Imagen backend | `infra/docker/api/Dockerfile.production` |
| Arranque backend | `infra/docker/api/production-entrypoint.sh` |
| PHP productivo | `infra/docker/api/php-production.ini` |
| Imagen gateway interno | `infra/docker/gateway/Dockerfile` |
| Rutas del gateway productivo | `infra/caddy/Caddyfile.production` |
| Smoke reproducible | `infra/validation/smoke-production.sh` |

### Topología del runtime

```text
127.0.0.1:8000 (único puerto publicado)
  -> gateway  (Caddy, red front)          imagen propia, Caddyfile.production baked
       ├── web  :8080  (red front)        Caddy static file server sobre el build de Vite
       └── api  :80    (redes front+data) Apache + PHP 8.5, Laravel + Filament
                └── mysql :3306 (solo red data, sin puerto publicado)
```

Decisiones cerradas al implementar la fase:

- **Frontend productivo:** build stage (Node 24.18.0 + pnpm 11.20.0, `tsc --noEmit && vite build`, más `verify:build`) seguido de un runtime estático Caddy. No hay proceso Node, HMR ni dependencias de desarrollo en la imagen publicada, y el código fuente no viaja en la capa final.
- **Puerto interno del frontend:** `8080`. El `5173` del entorno de desarrollo es del servidor Vite y no existe en producción.
- **Assets aprobados del import inicial:** se hornean en la imagen del API en `/var/www/docs/content/approved-assets`, que es exactamente donde el importador los resuelve vía `base_path('../docs/...')`. Producción ya no depende del bind mount `./docs` de desarrollo. Son entrada versionada de solo lectura, no dato persistente a respaldar.
- **Almacén de caché productivo:** `database`, que implementa `LockProvider` y ya tiene su migración. `file` sigue siendo válido; un backend sin `LockProvider` no.
- **`Host`, protocolo reenviado y trusted proxies:** Laravel confía únicamente en rangos privados (`TRUSTED_PROXIES`, default `10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,127.0.0.0/8`); un valor con comodín se descarta. El API no publica puerto y solo es alcanzable a través del gateway, así que un cliente público nunca puede presentar una de esas direcciones. Se confían `X-Forwarded-For` y `X-Forwarded-Proto`; **`X-Forwarded-Host` NO se confía**, porque Caddy ya reenvía el `Host` original y confiar en la variante reenviada permitiría envenenar las URLs generadas. Verificado en local: con `X-Forwarded-Proto: https` sobre el `Host` real, Filament emite `https://lucianogonzalez.dev/...`; sin esta configuración emitía `http://`, lo que habría roto el panel detrás de TLS. Cubierto por `api/tests/Feature/TrustedProxyTest.php`.

  > **Nota para operaciones — IP del cliente y rate limiting.** Symfony recorre `X-Forwarded-For` de derecha a izquierda y se detiene en el primer salto no confiable. Cloudflare es una red pública, así que si el Caddy global no la trata explícitamente, el `request->ip()` que ven los rate limiters (`public-api` 60/min y `cv-download` 30/min, en `api/app/Providers/AppServiceProvider.php`) será **la IP del edge de Cloudflare, no la del visitante**, y todos los visitantes de un mismo PoP compartirán cupo. Para obtener la IP real, el Caddy global debe declarar Cloudflare en sus propios `trusted_proxies` y reenviar `header_up X-Forwarded-For {http.request.client_ip}`. Esto pertenece a operaciones; el repositorio no lo configura. No es una vulnerabilidad: la dirección sigue sin ser falsificable, solo es menos granular.
- **Nombres GHCR:** `ghcr.io/gonzalez-luciano/portfolio-web`, `portfolio-api` y `portfolio-gateway`. MySQL usa la imagen oficial `mysql:8.4.11`, deliberadamente fijada a patch; no se construye una imagen propia de MySQL.
- **Rutas no cacheadas:** no se ejecuta `route:cache`. `routes/web.php` y `routes/api.php` registran acciones closure, que Laravel no puede serializar. Sí se cachean configuración y vistas, en el arranque del contenedor, cuando las variables de entorno ya están presentes.

### Lo que el arranque del contenedor hace y no hace

El entrypoint del API prepara directorios y permisos de `storage/` y `bootstrap/cache`, exige `APP_KEY`, rechaza arrancar con `APP_DEBUG` activo bajo `APP_ENV=production`, y cachea configuración y vistas.

**Nunca** ejecuta migraciones, seeders, el import inicial ni el bootstrap administrativo: son acciones explícitas del operador, documentadas más abajo.

### Requisitos que el runtime cumple

- Dockerfiles específicos de producción; no se reutilizan ciegamente las imágenes de desarrollo.
- `compose.production.yaml` portable y separado de `compose.yaml`: sin bind mounts de código, watchers/HMR ni dependencias de desarrollo.
- Servicios: gateway Caddy interno, front (archivos estáticos de la SPA Vite), Laravel y MySQL. Las imágenes propias (frontend, backend y gateway según la arquitectura real) y sus nombres GHCR se definen al implementar; MySQL usa una imagen oficial fijada deliberadamente.
- Solo el gateway publica un puerto, con bind configurable y default `127.0.0.1:8000`.
- Redes propias del portfolio; MySQL pertenece solo a la red de datos.
- Volúmenes persistentes: datos MySQL, `api_private_media` y `api_public_media`.
- Restart policies apropiadas y healthchecks reales por servicio.
- Configuración productiva sin secretos embebidos; sin rutas `/srv`, configuración de Cloudflare ni del Caddy global.
- Almacén de caché con `LockProvider` (ver "Requisitos del almacén de caché").
- Materialización futura y atómica de la configuración pública opcional
  `/runtime-config.json` a partir de valores de runtime. Tanto el `200` de una
  configuración existente como el `404` intencional cuando falte deben llevar
  `Cache-Control: no-store`. Valores ausentes o parciales dejan la analítica
  inequívocamente `OFF` y no afectan health.
- La materialización debe permitir cambiar la configuración de runtime con la
  misma imagen y digest del frontend, sin recompilar Vite, crear un tag ni
  publicar otra release. Los valores de Umami no se incorporan al build ni se
  convierten en secretos del frontend.

### Verificación local del runtime productivo (2026-09-22)

Ejecutada con un proyecto Compose aislado (`portfolio-production-test`) sobre
`127.0.0.1:28000`, sin tocar el stack de desarrollo, desde una base de datos
vacía. Evidencia resumida:

| Comprobación | Resultado |
|---|---|
| `docker compose -f compose.production.yaml config` | VALID |
| Build de las tres imágenes propias | PASS |
| Arranque con `--wait` (health de los 4 servicios) | PASS |
| `php artisan migrate --force` desde base fresca | PASS, 15 migraciones |
| `portfolio:import-initial-content` (primera ejecución) | PASS |
| `portfolio:import-initial-content` (segunda ejecución) | rechazada, sin cambios |
| Bootstrap administrativo interactivo en la imagen productiva | prompts operativos; completarlo requiere TTY real del operador |
| Smoke `infra/validation/smoke-production.sh` | 40/40 PASS |
| `runtime-config.json` OFF | `404` + `Cache-Control: no-store` |
| `runtime-config.json` ON (mismo image id, sin rebuild) | `200` + `no-store` + JSON válido |
| Configuración parcial/inválida/nil-UUID | siempre `404` (analítica OFF) |
| Persistencia tras `restart` | PASS |
| Persistencia tras `down` + `up` (recreate) | PASS, smoke 40/40 de nuevo |
| MySQL alcanzable desde el host | NO (sin port binding) |
| Puertos publicados por el proyecto | solo gateway, loopback |
| Secretos, `.env`, `.git`, `node_modules`, tests en las imágenes | ninguno |

El smoke cubre: health del gateway; `/` y `/en` con refresh directo, `lang` y
canonical correctos; redirecciones `308` de `/es`, `/es/` y `/en/`; `sitemap.xml`;
`robots.txt`; los cuatro favicons; `404` real con cuerpo estático y
`X-Robots-Tag`; assets y media inexistentes con `404`; ausencia de las rutas del
servidor de desarrollo; contrato `no-store` de `runtime-config.json`; `/api/v1` y
su envelope de error; locales soportados y no soportados; `/up`; y `/admin`
exigiendo autenticación y no indexable.

## Release, imágenes y digests

Orden obligatorio:

```text
main aprobada
  -> CI verde
  -> runtime productivo local PASS
  -> smoke local PASS
  -> tag versionado sobre main (por ejemplo v1.0.0)
  -> workflow de release
  -> GitHub Release si corresponde
  -> imágenes en GHCR
  -> versiones y digests registrados
  -> handoff a operaciones
  -> STOP
```

Reglas:

- Cada imagen es identificable al menos por versión de release y por commit SHA.
- `latest`, si existe, es solo comodidad: producción nunca depende exclusivamente de `latest`.
- El deployment usa tags inmutables o, preferentemente, digests exactos.
- Las imágenes no contienen `.env`, claves, tokens, passwords, secretos ni artefactos temporales innecesarios.
- El workflow de release usa permisos mínimos y nunca accede al VPS.

### Registro de releases

| Release | Commit | Imagen | Digest | Migraciones/schema incluidos |
|---|---|---|---|---|
| `v0.9.0` | `ac3ae1a5dba4a2dc37fcb589ff2c6301a287c6e0` | `ghcr.io/gonzalez-luciano/portfolio-web:v0.9.0` | `sha256:7a2396917fa0eb7bc4758bbf3c966a03b124c63ad9bbae071a952f517bb35210` | Schema hasta `2026_09_15_000005_add_location_to_profiles` |
| `v0.9.0` | `ac3ae1a5dba4a2dc37fcb589ff2c6301a287c6e0` | `ghcr.io/gonzalez-luciano/portfolio-api:v0.9.0` | `sha256:482579e1957487a991966ab1b9cf5bb7a911bcffb5006d70abd2e5bc40da4b83` | Schema hasta `2026_09_15_000005_add_location_to_profiles` |
| `v0.9.0` | `ac3ae1a5dba4a2dc37fcb589ff2c6301a287c6e0` | `ghcr.io/gonzalez-luciano/portfolio-gateway:v0.9.0` | `sha256:228fd25fc15ecf02e441d3cce5eb5aaf634fd20517a8dafa4b917574aac369f0` | — |

MySQL no se versiona con la release: usa la imagen oficial `mysql:8.4.11`.

## Handoff operativo de `v0.9.0`

Todo lo que operaciones necesita para desplegar esta release exacta. Ningún
valor real de secreto aparece aquí; solo nombres de variables.

### Identidad de la release

```text
Repositorio: https://github.com/Gonzalez-Luciano/portfolio-luciano
Tag:         v0.9.0
Commit:      ac3ae1a5dba4a2dc37fcb589ff2c6301a287c6e0
Compose:     compose.production.yaml (del checkout de ese tag)
Hostname público esperado: lucianogonzalez.dev
Entrypoint del proyecto:   127.0.0.1:8000
```

### Imágenes a desplegar

Desplegar por digest. El tag se incluye para lectura humana; `latest` existe
solo por comodidad y no debe usarse en producción. Las imágenes se publican
para `linux/amd64`.

> **`compose.production.yaml` conserva secciones `build:`** para poder verificar
> el runtime localmente. En el VPS eso significa que un `up` a secas podría
> construir en vez de descargar. El deployment debe ser explícito:
>
> ```bash
> docker compose -f compose.production.yaml pull
> docker compose -f compose.production.yaml up -d --no-build --wait
> ```
>
> `--no-build` falla de forma ruidosa si falta una imagen, en lugar de sustituir
> silenciosamente el digest publicado por una build local.

```text
ghcr.io/gonzalez-luciano/portfolio-web:v0.9.0      @ sha256:7a2396917fa0eb7bc4758bbf3c966a03b124c63ad9bbae071a952f517bb35210
ghcr.io/gonzalez-luciano/portfolio-api:v0.9.0      @ sha256:482579e1957487a991966ab1b9cf5bb7a911bcffb5006d70abd2e5bc40da4b83
ghcr.io/gonzalez-luciano/portfolio-gateway:v0.9.0  @ sha256:228fd25fc15ecf02e441d3cce5eb5aaf634fd20517a8dafa4b917574aac369f0
mysql:8.4.11                                       (imagen oficial fijada)
```

### Variables requeridas por el runtime

Nombres solamente. Los valores los crea y custodia operaciones.

**Obligatorias (el stack no arranca sin ellas):**

| Variable | Secreto | Notas |
|---|---|---|
| `APP_URL` | no | `https://lucianogonzalez.dev` |
| `APP_KEY` | **sí** | `php artisan key:generate --show`, una vez por entorno |
| `MYSQL_DATABASE` | no | |
| `MYSQL_USER` | no | |
| `MYSQL_PASSWORD` | **sí** | |
| `MYSQL_ROOT_PASSWORD` | **sí** | |

**Opcionales (tienen default en el Compose):**

| Variable | Default | Notas |
|---|---|---|
| `GATEWAY_HOST` | `127.0.0.1` | no cambiar a `0.0.0.0` en un host público |
| `GATEWAY_PORT` | `8000` | contrato con el Caddy global |
| `LOG_LEVEL` | `warning` | |
| `TRUSTED_PROXIES` | rangos privados | |
| `WEB_IMAGE` / `API_IMAGE` / `GATEWAY_IMAGE` | `:local` | fijar al digest de la release |
| `UMAMI_TRACKER_URL` | vacío | público, no secreto |
| `UMAMI_WEBSITE_ID` | vacío | público, no secreto |

`APP_ENV=production` y `APP_DEBUG=false` están fijados en el Compose y no son
configurables desde el entorno.

### Qué debe aportar el override del VPS

El override de operaciones (fuera de este repositorio) aporta únicamente:

- los valores reales de las variables anteriores, desde el mecanismo de
  secretos del host;
- las referencias de imagen fijadas por digest;
- cualquier ajuste de logging, límites o restart propio del host.

No debe redefinir rutas, redes, volúmenes ni el binding del gateway salvo
decisión documentada.

### Volúmenes persistentes

| Volumen | Contenido | Backup |
|---|---|---|
| `mysql_data` | base del portfolio | **obligatorio** |
| `api_private_media` | originales privados: foto, imágenes, íconos y **todos los PDF de CV** | **obligatorio**; sin esto el asset se pierde |
| `api_public_media` | copias públicas verificadas | recomendado; regenerable al republicar |

### Secuencia de primer deployment

Ejecutada por el operador, en este orden, después de levantar el stack:

```bash
# 1. Migraciones sobre base vacía.
docker compose -f compose.production.yaml exec api php artisan migrate --force

# 2. Import inicial guardado de contenido. Un solo uso: rechaza cualquier
#    ejecución posterior. Crea todo como borrador, oculto y no publicado.
docker compose -f compose.production.yaml exec api php artisan portfolio:import-initial-content

# 3. Primer administrador. Interactivo por diseño: requiere un TTY real.
#    Pide nombre, email y contraseña (mínimo 12 caracteres) con confirmación.
docker compose -f compose.production.yaml exec api php artisan portfolio:bootstrap-admin
```

Después de esto el contenido sigue **sin publicar**: un humano revisa y publica
desde Filament en `/admin`. Hasta entonces los endpoints públicos localizados
responden `404` con el envelope documentado, que es el comportamiento correcto
de un sitio sin contenido publicado.

### Health checks

| Servicio | Check |
|---|---|
| gateway | `GET /__gateway/health` -> `200 ok` |
| web | `GET :8080/__web/health` -> `200 ok` (interno) |
| api | `GET /up` -> `200` |
| mysql | `mysqladmin ping` (interno) |

### Smoke posterior al deployment

Operaciones ejecuta el mismo script del repositorio contra el origen público:

```bash
infra/validation/smoke-production.sh https://lucianogonzalez.dev
```

Es de solo lectura: no escribe contenido, no muta la base y no requiere
credenciales.

### Qué nunca se expone

- MySQL: sin puerto publicado, solo en la red `data`.
- `web` y `api`: sin puertos publicados, solo alcanzables por el gateway.
- El gateway publica exclusivamente `127.0.0.1:8000`.
- `/admin` exige autenticación y responde `X-Robots-Tag: noindex, nofollow`.

### Activación posterior de analítica

Cuando operaciones tenga el Website ID y la URL del tracker de su instancia
Umami independiente, basta con fijar `UMAMI_TRACKER_URL` y `UMAMI_WEBSITE_ID` y
reiniciar el contenedor `web`. **No se reconstruye la imagen, no cambia el
digest y no se crea otra release.** Si falta cualquiera de los dos valores, o
son inválidos, la analítica queda OFF y `/runtime-config.json` responde `404`;
en ambos estados el header es `Cache-Control: no-store`.

### Limitaciones conocidas de esta release

- **Imagen social: DEFERRED.** `web/public/social/luciano-gonzalez-social.jpg`
  (1200x630) todavía no existe. El build detecta su ausencia y **no** emite
  `og:image` ni `twitter:image`, así que no hay ninguna referencia rota; los
  enlaces compartidos muestran título y descripción sin imagen. Se integrará en
  una release posterior a `v0.9.0` y anterior a `v1.0.0`.
- **Fase 9 (seguridad y endurecimiento) OPEN.** Rate limiting, CORS por dominio
  y protección de rutas administrativas ya existen; cabeceras de seguridad,
  auditoría de dependencias, política de rotación de credenciales y el
  procedimiento probado de backup/restore siguen pendientes.
- **Fase 10 (QA integral) OPEN.** No se ejecutó la matriz completa de pruebas
  E2E, accesibilidad manual ni navegadores.
- **Revisión de indexación pendiente.** Search Console se trabaja en Fase 12.

## Handoff de release

Fase 11 termina con un handoff equivalente a:

```text
Release: vX.Y.Z
Commit: <sha>

CI: PASS
Runtime productivo local: PASS
Smoke local: PASS

GHCR:
- <imagen A> @ sha256:...
- <imagen B> @ sha256:...
- <imagen C> @ sha256:...

Handoff: listo
Working tree: clean

DEPLOYMENT AL VPS: NO EJECUTADO
```

Después: **STOP**.

## Deployment posterior (fuera del repositorio)

Contrato esperado; ningún agente del repositorio lo ejecuta. Lo realiza `vps_ops_claude`, manual/asistido, desde su propio contexto operativo:

```text
release ya creada
  -> operador entra al VPS
  -> preflight
  -> backup si corresponde
  -> checkout exacto del tag de release en /srv/apps/portfolio
  -> compose.production.yaml + override /srv/ops/portfolio/compose.vps.yaml
  -> pull de imágenes/digests exactos
  -> migraciones
  -> arranque
  -> health
  -> smoke
  -> verificación pública
  -> cierre del deployment
```

Fase 11 documenta qué valores espera el runtime productivo desde el entorno o el override (secretos, referencias de imagen y demás parámetros); el contenido real del override lo crea operaciones y nunca se versiona aquí.

No se despliega desde un working tree sin versionar ni desde una imagen construida manualmente sin referencia a una release o commit conocido.

## Activación operativa posterior de Umami y Search Console (Fase 12 — pendiente)

La Fase 12 comienza únicamente desde una release de Fase 11 ya existente. El
deployment del portfolio y la activación de Umami los realiza
`vps_ops_claude` desde el contexto operativo; no los ejecuta este repositorio
ni GitHub Actions.

El handoff para operaciones debe requerir, sin implementar aquí, una instancia
Umami independiente con imagen oficial deliberadamente fijada, PostgreSQL
`12.14` o superior, persistencia y backups separados del portfolio. Operaciones
es dueña de `DATABASE_URL`, `APP_SECRET`, la rotación de la credencial inicial
del administrador, hostname/TLS, logging, retención, actualizaciones y health.
Después de crear el sitio `lucianogonzalez.dev` en Umami, entrega el Website ID
y la URL del tracker para que el runtime de la misma release se reconfigure sin
reconstruir su imagen ni cambiar su digest.

La activación se acepta con ingestión real: pageviews de `/` y `/en` sin
variantes de query/hash ni duplicados, y los cuatro eventos aprobados sin
propiedades. Fase 8 no agregó ni contactó un servidor Umami, por lo que sus
pruebas no certifican ingestión real.

Después del deployment, el checklist de Search Console se limita a verificar
el dominio, enviar el sitemap, inspeccionar URLs y observar la indexación. Los
tokens, cambios DNS y el envío real permanecen fuera de Fase 8 y del repositorio.

## Contrato de rollback

- **Identificar la versión anterior:** tags y GitHub Releases del repositorio, más el registro de releases de este documento.
- **Imágenes por release:** cada release registra sus imágenes y digests exactos.
- **Volver atrás:** checkout del tag anterior y redeploy con los digests de esa release. Un rollback no reconstruye código.
- **Base de datos:** un rollback de aplicación **no** implica rollback de base de datos ni de los volúmenes de media. Revertir datos es una operación separada de restore.
- **Migraciones:** las migraciones destructivas requieren análisis independiente antes de cualquier rollback; las futuras migraciones deben considerar compatibilidad con la release anterior, y cada release registra qué schema incluye.
- **Ejecución:** el rollback real lo ejecuta `vps_ops_claude`; el repositorio solo entrega este contrato y las advertencias de aplicación (ver "Advertencias de rollback (Fase 4)").

## Supuestos que el repositorio no conoce

Hechos conocidos de la topología (ver `docs/SERVER_ARCHITECTURE.md`): VPS Linux OVHcloud multiproyecto, Cloudflare DNS proxied con SSL/TLS `Full (strict)`, Caddy global en `:80`/`:443`, UFW restringido a rangos de Cloudflare, layout `/srv/apps`, `/srv/ops`, `/srv/ingress`, `/srv/backups` y reserva de `127.0.0.1:8000`.

El repositorio no adivina, y pertenecen a operaciones:

- distribución o versión de Linux;
- CPU, RAM o capacidad efectiva;
- discos, filesystem o ubicación de Docker data root;
- rutas físicas finales de volúmenes;
- contenido de `/srv/ops` y `/srv/ingress`;
- mecanismo de certificado de origen del Caddy global;
- política real de monitoreo y backups;
- otros proyectos, puertos o restricciones presentes en el host.

## Definition of done del handoff (Fase 11)

- [x] El runtime productivo fue levantado y verificado localmente desde una base fresca.
- [x] Servicios, redes, puertos internos y entrypoint están documentados.
- [x] Variables y secretos requeridos tienen owner y ejemplo sin valores reales.
- [x] Persistencia, migraciones, import inicial y bootstrap están documentados.
- [x] Build, tests, health checks y smoke local son reproducibles.
- [x] MySQL y servicios internos no se exponen públicamente.
- [x] La frontera con el Caddy global, Cloudflare y el VPS está explícita.
- [x] Release, commit, imágenes y digests están registrados.
- [x] El contrato de rollback está documentado.
- [x] Los datos relevantes para backup están identificados.
- [x] La release puede entregarse a `vps_ops_claude` sin reconstruir código ni duplicar su checklist operacional.
- [x] El documento no afirma que el portfolio ya fue desplegado.

## Verificación de la release publicada (2026-09-22)

Las imágenes publicadas se descargaron desde GHCR **por digest** y se
ejecutaron con la misma secuencia que operaciones usará en el VPS
(`docker compose pull` seguido de `up -d --no-build --wait`), sobre un proyecto
Compose aislado y volúmenes vacíos.

| Comprobación | Resultado |
|---|---|
| `docker compose pull` de los tres digests | PASS |
| Arranque con `--no-build --wait` (health de los 4 servicios) | PASS |
| `php artisan migrate --force` desde base fresca | PASS |
| Smoke `infra/validation/smoke-production.sh` | 40/40 PASS |
| Solo el gateway publica puerto, sobre loopback | PASS |
| Trazabilidad de cada imagen al commit del tag | PASS |

Cada imagen lleva las etiquetas OCI `org.opencontainers.image.version=v0.9.0` y
`org.opencontainers.image.revision=ac3ae1a5dba4a2dc37fcb589ff2c6301a287c6e0`,
de modo que un operador puede confirmar desde el registry que el artefacto
corresponde exactamente al commit etiquetado, sin reconstruir nada.

Las imágenes están publicadas para `linux/amd64`.
