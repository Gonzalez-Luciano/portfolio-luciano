# ARCHITECTURE.md — Portfolio

## Estado

Baseline de arquitectura del proyecto Portfolio.

Este documento describe la arquitectura interna del portfolio.

La arquitectura compartida de la computadora/servidor se encuentra en:

`docs/SERVER_ARCHITECTURE.md`

Las reglas de despliegue específicas del portfolio se encuentran en:

`docs/DEPLOYMENT.md`

---

## Contexto

El portfolio no es el único proyecto del servidor.

La computadora Linux de producción aloja múltiples aplicaciones Docker independientes.

A nivel del host:

```text
Cloudflare Tunnel
  ├── lucianogonzalez.dev -> 127.0.0.1:8000 -> Portfolio
  ├── subdominio A        -> 127.0.0.1:8080 -> Proyecto A
  └── subdominio B        -> 127.0.0.1:8081 -> Proyecto B
```

Por lo tanto, el repositorio del portfolio no debe intentar administrar los demás proyectos ni ser dueño de `cloudflared`.

---

## Estructura del repositorio del portfolio

Identidad del repositorio:

- Workspace local: `portfolio-luciano`.
- Repositorio GitHub canónico: `portfolio`.
- El nombre del directorio local no necesita coincidir con el slug remoto.

```text
portfolio/
├── AGENTS.md
├── ROADMAP.md
├── README.md
├── docs/
│   ├── PROJECT.md
│   ├── ARCHITECTURE.md
│   ├── SERVER_ARCHITECTURE.md
│   └── DEPLOYMENT.md
├── web/
├── api/
└── infra/
```

### `web/`

Aplicación pública.

Responsabilidades:

- Renderizado.
- UI responsive.
- Español/inglés.
- Tema claro/oscuro.
- SEO.
- Animaciones.
- Consumo de API.
- Estados de error/carga.

### `api/`

Laravel.

Responsabilidades:

- Autenticación administrativa.
- Filament.
- Persistencia MySQL.
- Validación.
- Borrador/publicado.
- Medios.
- API pública.
- Autorización.
- Integridad de datos.

### `infra/`

Infraestructura propia del portfolio.

Responsabilidades:

- Dockerfiles.
- Compose del proyecto.
- Gateway/reverse proxy del proyecto.
- Configuración de servicios internos.
- Configuración de desarrollo/pruebas y validaciones de infraestructura propias del portfolio.
- Contrato de handoff para que operaciones externas adapte el runtime al servidor real.

No debe incluir la configuración global de otros proyectos del servidor.

### `docs/`

Contexto permanente para humanos y agentes.

---

## Frontera con el servidor

El portfolio debe exponer exactamente un punto de entrada HTTP al host:

```text
127.0.0.1:8000
```

El puerto podrá parametrizarse, pero `8000` es la reserva inicial.

`cloudflared` del host publica:

```text
https://lucianogonzalez.dev
    -> http://localhost:8000
```

El stack del portfolio no necesita un contenedor `cloudflared`.

---

## Topología interna del portfolio

```text
127.0.0.1:8000
       |
       v
portfolio-gateway (Caddy)
    |             |
    v             v
portfolio-web   portfolio-api
 Next.js         Apache + Laravel
                    |
                    v
              portfolio-mysql
```

Servicios adicionales se agregan solo con una necesidad real.

---

## Gateway

El gateway es Caddy 2 y es el único contenedor del proyecto que publica un puerto al host. Caddy opera como reverse proxy HTTP y no monta ni interpreta el filesystem de Laravel.

Ownership conceptual de rutas:

```text
/          -> web
/api/*     -> api
/admin/*   -> api / Filament
Livewire, assets backend y media pública verificados -> api
```

Beneficios:

- Un solo hostname.
- Un solo puerto del host.
- Menos CORS.
- Cookies más simples.
- `cloudflared` no necesita conocer la red Docker interna.
- Los contenedores web/api/mysql no se exponen directamente.

Caddy debe soportar el tráfico WebSocket/HMR de Next.js durante desarrollo. Los matchers backend definitivos se derivan después de instalar Laravel, Filament y Livewire: `route:list --json` aporta las rutas registradas y el tráfico real de admin, autenticación, Livewire, assets y media completa el inventario. No se adivinan prefijos ni se fija un hash generado de Livewire. Una ruta backend conserva su ownership incluso cuando Laravel responde 404.

Caddy conserva el `Host` entrante y usa su comportamiento normal de forwarded headers. La integración futura con el `cloudflared` externo debe validar trusted proxies y protocolo reenviado en el servidor real, sin configuración Cloudflare especulativa en Fase 3.

URLs canónicas de desarrollo:

```text
http://localhost:8000/        -> redirección localizada
http://localhost:8000/es      -> portfolio en español
http://localhost:8000/en      -> portfolio en inglés
http://localhost:8000/api/v1  -> API pública versionada
http://localhost:8000/admin   -> Filament
```

Los puertos internos de `web`, `api`, `mysql` y futuros servicios no son URLs canónicas y no se publican al host.

---

## Frontend

Dirección:

- React.
- Next.js App Router.
- TypeScript estricto.
- Tailwind CSS 4.x.
- Node.js 24 LTS.
- última release estable parcheada de Next.js 16.2.x disponible al implementar; no preview/canary.
- React 19.2.x.
- `pnpm` 11.20.0 fijado en `web/package.json`.
- `next-intl` 4.x.

`pnpm`, `package.json` y `pnpm-lock.yaml` pertenecen exclusivamente a `web/`; no existe workspace pnpm en la raíz.

### Renderizado

Preferir Server Components y renderizado apto para SEO cuando sea posible.

Usar Client Components únicamente donde la interacción lo requiera:

- Tema.
- Menú.
- Selector de idioma si corresponde.
- Animaciones.
- Campo de nodos.

Evitar hidratar contenido estático innecesariamente.

La base de Fase 3 mantiene `/es` y `/en` prerenderizables. `next build` debe completar sin Laravel, Caddy ni MySQL en ejecución: la UI base no hace requests API obligatorios durante el build. Una demostración de conectividad ocurre en runtime por `/api/v1` o mediante smoke checks. El tema no se resuelve con `cookies()` del servidor: un bootstrap mínimo, estable y previo al paint aplica `data-theme` desde una preferencia explícita `light`/`dark` en `localStorage` o, si no existe, desde `prefers-color-scheme`. Cualquier supresión de warning de hidratación queda limitada al elemento raíz cuya mutación previa es intencional.

---

## Backend

Dirección:

- PHP 8.5.
- Laravel 13.
- MySQL 8.4 LTS mediante `mysql:8.4`.
- Filament 5.
- Livewire 4+ como requisito de Filament 5.

Usar releases estables compatibles dentro de estas líneas mayores. No degradar PHP, Laravel o Filament automáticamente: una incompatibilidad concreta requiere detenerse y reportarla antes de cambiar el stack acordado.

Laravel es la fuente de verdad del contenido administrable.

El frontend no duplica el CMS.

El servicio API usa Apache interno en puerto 80 con `DocumentRoot` explícito en `public/`, rewrite/front controller verificado y permisos de escritura para `storage/` y `bootstrap/cache`. Laravel conserva `/up` como señal ligera de boot, independiente de MySQL.

Filament expone un panel autenticado sin registro público. El único marcador de autorización de Fase 3 es `users.is_admin BOOLEAN NOT NULL DEFAULT false`; `canAccessPanel()` exige `is_admin = true`. No se introducen roles, permisos ni RBAC. El primer administrador se crea con ese marcador mediante `php artisan portfolio:bootstrap-admin`: flujo interactivo create-only, contraseña oculta y confirmada, sin actualización silenciosa, salida ni logs de secretos. Los tests simulan prompts con las herramientas de consola de Laravel.

---

## Base de datos

`portfolio-mysql` pertenece exclusivamente al portfolio.

Reglas:

- Red Docker privada.
- Sin puerto público.
- Volumen persistente independiente.
- Usuario/contraseña propios.
- Backup independiente.
- No compartir instancia lógica con demos por comodidad.
- No ejecutar resets automáticos destructivos.

Las pruebas automatizadas nunca usan esa base persistente. El perfil Compose `test` crea `mysql-test` sobre MySQL 8.4 con storage descartable y un `api-test` one-shot que reutiliza la imagen backend, espera health real y ejecuta Laravel con `APP_ENV=testing`. No se publica `3306` ni se usa SQLite como sustituto de integración MySQL.

---

## API

Principios:

- Namespace versionado.
- Solo lectura para contenido público.
- Solo contenido publicado.
- JSON consistente.
- Serialización explícita.
- Rate limiting.
- Errores controlados.
- Sin campos sensibles.
- Caché cuando tenga sentido.

Contrato mínimo de Fase 3:

```json
{"data":{"status":"ok","version":"v1"}}
```

Los errores manejados usan exclusivamente:

```json
{"error":{"code":"rate_limited","message":"Too many requests.","details":{}}}
```

`code` es `snake_case` estable, `message` es seguro, `details` siempre es un objeto y el status HTTP no se duplica en el body. Una respuesta nunca contiene simultáneamente `data` y `error`. El cliente tipado valida estas formas y rechaza envelopes ambiguos o inválidos.

El contenido en borrador nunca debe filtrarse a endpoints públicos.

---

## Modelo de contenido

### Profile

- Nombre.
- Título profesional.
- Resumen corto.
- Resumen extendido.
- Ubicación general.
- Fotografía.
- Disponibilidad.
- Estado de publicación.

### Experience

- Empresa o etiqueta anonimizada.
- Rol.
- Fechas.
- Resumen.
- Responsabilidades.
- Tecnologías.
- Orden.
- Visibilidad.

### Work Case

- Título.
- Contexto.
- Problema.
- Participación.
- Solución.
- Tecnologías.
- Aprendizajes/resultados descriptivos.
- Confidencialidad.
- Traducciones.
- Visibilidad.

### Project

- Nombre.
- Descripción.
- Descripción técnica.
- Imagen.
- Video opcional.
- Tecnologías.
- Demo URL.
- Repository URL.
- Destacado.
- Publicación.
- Orden.

La demo URL puede apuntar a subdominios alojados en el mismo servidor.

### Technology

- Nombre.
- Categoría.
- Icono.
- Nivel descriptivo opcional.
- Orden.
- Visibilidad.

### Site Settings

- Enlaces.
- Email.
- CV.
- SEO.
- Open Graph.
- Secciones.
- Disponibilidad.

---

## Internacionalización

Idiomas:

- Español.
- Inglés.

Reglas:

- No duplicar manualmente toda la aplicación.
- Contenido administrado traducible.
- Metadata localizada.
- `next-intl` como arquitectura de routing e internacionalización.
- `/es` y `/en` son las entradas canónicas; `/` siempre redirige.
- Resolver primero una preferencia explícita válida, luego `Accept-Language` y finalmente español.
- Persistir solo una selección explícita del usuario; ignorar valores almacenados inválidos.
- No duplicar la detección de locale entre capas.
- No traducir nombres de marcas/tecnologías innecesariamente.

---

## Tema

- Dark.
- Light.
- Preferencia del sistema.

Reglas:

- Persistencia.
- Sin flash inicial notable.
- Tokens de diseño.
- Campo de nodos adaptado al tema.
- Contraste accesible.

---

## Animaciones

### Motion

Para:

- UI.
- Enter/exit.
- Hover/focus.
- Layout.
- Menú/tema/idioma.

### GSAP / ScrollTrigger

Solo para:

- Secuencias complejas.
- Storytelling de scroll.
- Timelines coordinados.
- Secciones fijadas justificadas.

No usar GSAP para efectos simples.

### Campo de nodos

Orden de evaluación:

1. Canvas 2D.
2. WebGL si es necesario.
3. React Three Fiber solo si justifica el costo.

Fallbacks:

- Móvil.
- Reduced motion.
- Background tab.
- Offscreen.

---

## Docker

Compose del portfolio esperado:

```text
gateway
web
api
mysql
mysql-test (perfil test, bajo demanda)
api-test (perfil test, one-shot)
```

Posibles servicios posteriores:

```text
scheduler
queue
redis
```

solo si aparecen requisitos reales.

### Redes

Conceptualmente:

```text
portfolio_front:
  gateway
  web
  api

portfolio_data:
  api
  mysql

portfolio_test:
  api-test
  mysql-test
```

MySQL no necesita pertenecer a la red frontal.

### Puertos

Solo:

```text
gateway -> 127.0.0.1:8000
```

Los demás servicios se comunican mediante DNS interno de Docker.

El código de desarrollo usa bind mounts. Volúmenes Linux administrados por Docker aíslan `web/node_modules` y `api/vendor`; un bootstrap frío explícito los puebla desde lockfiles y el arranque normal no reinstala dependencias. MySQL de desarrollo y `storage/app/public` son persistentes. La raíz `.env` alimenta interpolación de Compose, pero cada servicio recibe explícitamente solo sus variables propias; web nunca recibe credenciales MySQL y `http://api` es server-only.

PowerShell con Docker Desktop/WSL2 es el flujo Windows canónico. Ubuntu WSL2 opera el mismo engine mediante integración de Docker Desktop, sin instalar un segundo Docker Engine.

### Runtime verificado de Fase 3

La aceptación local verificó Caddy `2.11.4-alpine`, Node `24.18.0`, pnpm
`11.20.0`, Next `16.2.12`, React `19.2.4`, PHP `8.5.8`, Laravel `13.25.0`,
Filament `5.7.6` y MySQL `8.4`. Los puertos internos son Caddy `80`, Next
`3000`, Apache/Laravel `80` y MySQL `3306`; solo Caddy se publica como
`127.0.0.1:8000`.

Las señales de health son `GET /__gateway/health` para Caddy, `GET /health`
para Next, `GET /up` para Laravel y `mysqladmin ping` para MySQL. El bootstrap
frío instala dependencias desde los lockfiles, espera servicios saludables,
ejecuta migraciones y crea el link estándar `public/storage`. No crea seeds ni
un administrador. El comando interactivo `portfolio:bootstrap-admin` crea una
sola cuenta administradora y nunca acepta ni muestra contraseñas por argumentos,
variables o logs.

Los volúmenes persistentes del desarrollo son `mysql_data` y
`api_public_media`; `web_node_modules` y `api_vendor` son cachés reproducibles
de dependencias. `mysql-test` usa `tmpfs`, no tiene volumen nombrado y no puede
reutilizar la base de desarrollo. La evidencia de rutas, Caddy y smoke manual
está en `infra/caddy/GATEWAY_INTEGRATION_EVIDENCE.md` y
`docs/testing/PHASE_3_VERIFICATION.md`.

---

## CI

CI valida:

### Web

- Install.
- Lint.
- Type check.
- Tests.
- Build.

### API

- Install.
- Format/lint/static checks configurados.
- Tests.

### Docker

- Build validation.
- Config validation.

CI no debe asumir que es responsable del acceso público del servidor.

---

## Producción

El portfolio se ejecuta en la computadora Linux de Luciano.

No se despliega a una plataforma de hosting de aplicaciones.

Cloudflare proporciona DNS/edge/tunnel, pero los procesos y datos de la aplicación permanecen en el servidor propio.

### Límite de responsabilidad del repositorio

El repositorio es responsable de entregar una aplicación verificable y un contrato de runtime consumible:

- servicios y límites de red del proyecto;
- gateway y rutas públicas esperadas;
- entrypoint futuro `127.0.0.1:8000`;
- variables y secretos requeridos sin sus valores;
- persistencia, migraciones, bootstrap y health checks de aplicación;
- procedimientos reproducibles de build, pruebas y smoke checks;
- información de aplicación relevante para backup y rollback.

El workflow externo `home_server_ops_claude` es responsable de inspeccionar y operar el servidor real: preflight de Linux/hardware/storage, instalación y configuración del host, clonación, variables reales de producción, Compose final de producción, `cloudflared`, firewall, backups, restores, reinicios, registro multiproyecto y deployment. Este repositorio referencia ese límite, pero no duplica su checklist operacional.

La Fase 3 crea el entorno Compose completo de desarrollo/pruebas y límites compatibles con producción. No crea el Compose final del servidor, targets de imagen especulativos, configuración de Cloudflare, unidades `systemd`, firewall, cron de backup ni scripts específicos del host.

---

## Seguridad

- Secretos solo del lado servidor.
- Panel administrativo autenticado.
- Sin registro público.
- CORS mínimo; idealmente mismo origen.
- Uploads validados.
- API pública limitada.
- Errores de producción sin traces internos.
- Información profesional sensible anonimizada.
- `.env` fuera de Git.
- Puerto MySQL no publicado.
- Gateway enlazado a loopback del host.
- `cloudflared` administrado fuera de este repo.

---

## Accesibilidad

Baseline:

- WCAG AA.
- Navegación por teclado.
- Focus visible.
- HTML semántico.
- Reduced motion.
- Controles accesibles.
- Canvas decorativo.

---

## Rendimiento

Prioridades:

1. Hero legible rápido.
2. Poca hidratación innecesaria.
3. Imágenes/fuentes optimizadas.
4. Lazy loading de animación pesada.
5. Pausa de animaciones fuera de viewport.
6. Simplificación móvil.
7. Core Web Vitals medidos.

---

## Política de decisiones

Actualizar este documento si cambia:

- Frontera frontend/backend.
- Base de datos.
- CMS.
- Autenticación.
- API.
- Renderizado.
- Gateway.
- Puerto asignado al portfolio.
- Servicios Docker internos.
- Tecnología principal de animación.

Actualizar `SERVER_ARCHITECTURE.md` si cambia:

- Estructura multiproyecto del host.
- Tunnel global.
- Convención de puertos.
- Política de backups compartida.
- Estrategia general de deploy.

Actualizar `DEPLOYMENT.md` cuando cambie el contrato de handoff que consume el workflow externo de operaciones.
