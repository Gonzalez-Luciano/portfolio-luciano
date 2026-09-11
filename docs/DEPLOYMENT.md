# DEPLOYMENT.md — Contrato de handoff del portfolio

## Propósito

Este documento describe el contrato de aplicación que el repositorio entrega al workflow externo de operaciones del servidor. No es un checklist para que el agente de desarrollo del portfolio instale, configure u opere la computadora Linux.

La secuencia de responsabilidad es:

1. El roadmap del portfolio completa funcionalidad, pruebas, seguridad, builds, CI y release readiness.
2. Una versión aprobada se publica en GitHub junto con este contrato actualizado.
3. El workflow externo `home_server_ops_claude` inspecciona el servidor real, clona la versión aprobada y diseña/ejecuta el deployment final.
4. El lanzamiento del portfolio depende de la confirmación externa de deployment, smoke checks y backup inicial.

No se copian aquí las instrucciones completas de `home_server_ops_claude`. La topología compartida de referencia permanece en `docs/SERVER_ARCHITECTURE.md`.

## Límites de responsabilidad

### Responsabilidad del repositorio

- Definir servicios y dependencias de la aplicación.
- Proporcionar un entorno Docker completo de desarrollo y pruebas.
- Mantener límites de servicio compatibles con el futuro entorno Linux.
- Documentar variables, secretos requeridos, persistencia, migraciones, bootstrap y health checks.
- Proporcionar comandos reproducibles de build, pruebas y smoke checks.
- Identificar datos que requieren backup y consideraciones de aplicación para rollback.
- Mantener el gateway como único entrypoint del proyecto.
- No incluir secretos reales ni credenciales de Cloudflare.

### Responsabilidad de operaciones externas

- Descubrir distribución Linux, CPU, RAM, discos, filesystem y ubicación real de datos persistentes.
- Preparar `/srv/apps`, `/srv/backups` y el registro multiproyecto del servidor.
- Instalar y configurar Docker, Compose y el arranque del host.
- Clonar la versión aprobada y crear las variables reales de producción.
- Crear o ajustar imágenes/targets y Compose final de producción según el servidor real.
- Ejecutar migraciones y bootstrap administrativo en producción.
- Operar `cloudflared`, DNS, Tunnel, firewall y controles de acceso del host.
- Configurar y probar backups, restores, reinicios, monitoreo y rollback operativo.
- Confirmar el deployment y entregar evidencia de smoke checks al proceso de lanzamiento.

## Contrato público futuro

```text
lucianogonzalez.dev
    -> Cloudflare Tunnel compartido del host
    -> http://127.0.0.1:8000
    -> gateway del portfolio
```

- Hostname: `lucianogonzalez.dev`.
- Entry point del proyecto: `127.0.0.1:8000`.
- Solo el gateway publica ese puerto.
- No se requieren puertos HTTP/HTTPS del router para el flujo normal mediante Tunnel.
- `cloudflared` es infraestructura compartida del host y nunca integra el Compose del portfolio.
- El token y las credenciales del Tunnel nunca ingresan en este repositorio.

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
/            -> Next.js; redirección localizada
/es          -> Next.js; portfolio en español
/en          -> Next.js; portfolio en inglés
/api/*       -> Laravel
/admin/*     -> Laravel / Filament
```

El gateway del portfolio es Caddy. `portfolio-api` sirve Laravel mediante Apache interno con `public/` como `DocumentRoot`; Caddy no monta ni interpreta archivos de Laravel.

El handoff de Fase 3 registra las rutas Laravel estructuradas y también las rutas públicas observadas que Filament, Livewire y media requieren. Los matchers se derivan de `route:list --json` más smoke checks reales; no se adivina ni se hardcodea el hash variable de Livewire. Web, API y MySQL usan DNS/redes internas de Docker; sus puertos internos no forman parte del contrato público.

Caddy usa su comportamiento normal de `Host` y forwarded headers. Operaciones debe verificar trusted proxies y protocolo reenviado al conectar el `cloudflared` compartido en la topología real.

## Redes y exposición

- Gateway, web y API comparten una red frontal específica del portfolio.
- API y MySQL comparten una red de datos específica del portfolio.
- MySQL no pertenece a la red frontal y no publica `3306` al host.
- Ningún servicio usa `network_mode: host` salvo una futura decisión operacional documentada.
- Redes, volúmenes y credenciales no se comparten con otros proyectos.
- Desde Fase 5, `web` resuelve la media `/storage/...` del optimizador de imágenes de Next mediante una regla `rewrites()` server-only hacia `INTERNAL_API_ORIGIN` (`api:80`), sobre la misma red interna ya usada para las seis lecturas de contenido público. No agrega un puerto nuevo, no cambia `images.remotePatterns` y el tráfico real del navegador nunca la usa: Caddy sigue enrutando `/storage/*` directo a `api`.

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

- configuración del runtime Next.js que no sea secreta;
- URL/origen público de la aplicación cuando corresponda;
- Laravel `APP_KEY` y configuración de entorno;
- credenciales MySQL específicas del portfolio;
- configuración de sesión, logs y filesystem;
- contrato del bootstrap administrativo interactivo, sin contener la contraseña.

No pertenecen al repositorio ni al entorno de aplicación:

- token o credenciales de Cloudflare Tunnel;
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

Fase 3 no crea targets de producción especulativos ni un Compose final del servidor. Las definiciones de desarrollo deben evitar supuestos exclusivos de Windows y mantener límites claros que operaciones pueda adaptar después del preflight real.

Desde Fase 5, la ruta pública localizada renderiza dinámicamente en cada request (hace fetch obligatorio a los seis endpoints públicos por request) en vez de prerenderizarse en build. El build de `web` sigue sin depender de Laravel: completa igual con `gateway`/`api`/`mysql` detenidos o con `INTERNAL_API_ORIGIN` apuntando a un host inalcanzable, porque no ejecuta ningún fetch de contenido durante el build. Esto es relevante para operaciones porque significa que el build de imagen de producción de `web` nunca requiere que el API de producción esté disponible.

La validación local de aceptación usó Caddy `2.11.4-alpine`, Node `24.18.0`,
pnpm `11.20.0`, Next `16.2.12`, React `19.2.4`, PHP `8.5.8`, Laravel
`13.25.0`, Filament `5.7.6` y MySQL `8.4`. Los puertos internos son Caddy
`80`, Next `3000`, Apache/Laravel `80` y MySQL `3306`; el único publish local
es Caddy `127.0.0.1:8000`. Las señales de aplicación entregadas son Caddy
`/__gateway/health`, Next `/health`, Laravel `/up` y `mysqladmin ping`.

El bootstrap local instala desde lockfiles en los volúmenes vacíos, espera
health, ejecuta migraciones y crea explícitamente `public/storage` solo después
de comprobar que un enlace existente es no versionado. El entrypoint de API no
crea ese enlace. El bootstrap administrativo es separado e interactivo.
Operaciones adapta este contrato al servidor real después del preflight; el
repositorio no ejecuta ni prescribe acciones de host.

## Migraciones y bootstrap

- Las migraciones de Laravel son la fuente reproducible del esquema.
- Desarrollo y pruebas deben construir una base limpia mediante migraciones.
- Producción usa migraciones no destructivas ejecutadas conscientemente por operaciones.
- Nunca se programa `migrate:fresh`, resets o seeds destructivos contra datos persistentes de producción.
- Los seeds normales contienen solo datos seguros y no crean credenciales.
- En desarrollo, el primer administrador de Filament se crea mediante `php artisan portfolio:bootstrap-admin`, un comando interactivo create-only con password oculto y confirmación.
- El comando rechaza duplicados/estados ambiguos, no actualiza usuarios y no imprime ni registra secretos.
- Si producción necesita un mecanismo no interactivo, operaciones lo decide después del preflight real; Fase 3 no especula cómo inyectar ese secreto.

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
- proceso Next.js;
- gateway y rutas principales.

Smoke checks mínimos después de un deployment externo:

- `/` redirige según el contrato de locale;
- `/es` y `/en` responden;
- `/api/v1` responde con el contrato base esperado;
- `/admin` responde y requiere autenticación;
- MySQL no es alcanzable desde el host ni Internet;
- el gateway es el único puerto publicado por el proyecto.

## Logs

- Laravel, Next.js y gateway escriben a stdout/stderr o a destinos documentados compatibles con contenedores.
- Los logs no contienen secretos, credenciales, datos financieros reales ni información confidencial.
- Rotación, retención, recolección y acceso en producción pertenecen a operaciones externas.

## Backup y rollback: información entregada

El repositorio identifica qué datos necesitan backup y qué migraciones pueden afectar compatibilidad. Operaciones define rutas físicas, agenda, retención, copia externa y restore.

Antes del lanzamiento, el handoff externo debe confirmar:

- backup inicial de MySQL y media;
- restore probado según el workflow operacional;
- versión/commit desplegado;
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

## Supuestos que deben descubrirse

El repositorio no adivina:

- distribución o versión de Linux;
- CPU, RAM o capacidad efectiva;
- discos, filesystem o layout físico;
- ubicación de Docker data root;
- rutas físicas finales de volúmenes;
- política real de firewall, monitoreo o backups;
- estado del Tunnel compartido;
- otros proyectos, puertos o restricciones presentes en el host.

El workflow externo obtiene esos datos antes de producir configuración final.

## Definition of done del handoff

- [ ] Servicios, redes, puertos internos y entrypoint están documentados.
- [ ] Variables y secretos requeridos tienen owner y ejemplo sin valores reales.
- [ ] Persistencia, migraciones y bootstrap están documentados.
- [ ] Build, tests, health checks y smoke checks son reproducibles.
- [ ] MySQL y servicios internos no se exponen públicamente.
- [ ] La frontera de `cloudflared` está explícita.
- [ ] Los datos relevantes para backup y rollback están identificados.
- [ ] Los supuestos de servidor pendientes de descubrimiento están enumerados.
- [ ] La versión aprobada puede entregarse a `home_server_ops_claude` sin duplicar su checklist operacional.
- [ ] El documento no afirma que el servidor ya fue desplegado.
