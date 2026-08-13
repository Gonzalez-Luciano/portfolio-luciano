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

## Persistencia relevante

Datos no regenerables que operaciones debe persistir y respaldar:

- base MySQL del portfolio;
- media administrada por Laravel/Filament;
- CV administrados cuando el CMS los gestione localmente;
- cualquier otro archivo de aplicación declarado persistente en una fase posterior.

Los contenedores son reemplazables; eliminar o recrear un contenedor no debe borrar esos datos. La ubicación física final y la política de backup se deciden externamente después de inspeccionar el servidor.

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

La validación local de aceptación usó Caddy `2.11.4-alpine`, Node `24.18.0`,
pnpm `11.20.0`, Next `16.2.12`, React `19.2.4`, PHP `8.5.8`, Laravel
`13.25.0`, Filament `5.7.6` y MySQL `8.4`. Los puertos internos son Caddy
`80`, Next `3000`, Apache/Laravel `80` y MySQL `3306`; el único publish local
es Caddy `127.0.0.1:8000`. Las señales de aplicación entregadas son Caddy
`/__gateway/health`, Next `/health`, Laravel `/up` y `mysqladmin ping`.

El bootstrap local instala desde lockfiles, espera health, ejecuta migraciones
y crea `public/storage`. El bootstrap administrativo es separado e interactivo.
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

Las unidades persistentes relevantes son MySQL y `storage/app/public` de
Laravel (media y, cuando exista, CV administrado). En desarrollo se
materializan como `mysql_data` y `api_public_media`; `web_node_modules` y
`api_vendor` son cachés regenerables. `mysql-test` usa `tmpfs`, no entra en
backups y no contiene datos de desarrollo.

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
