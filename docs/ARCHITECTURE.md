# ARCHITECTURE.md — Portfolio

## Estado

Baseline de arquitectura del proyecto Portfolio.

Este documento describe la arquitectura interna del portfolio.

La arquitectura compartida del VPS se encuentra en:

`docs/SERVER_ARCHITECTURE.md`

Las reglas de despliegue específicas del portfolio se encuentran en:

`docs/DEPLOYMENT.md`

---

## Contexto

El portfolio no es el único proyecto del servidor.

El VPS Linux de OVHcloud de producción aloja múltiples aplicaciones Docker independientes.

A nivel del host:

```text
Cloudflare (DNS proxied, SSL/TLS Full (strict))
  -> Caddy GLOBAL del VPS :80/:443
       ├── lucianogonzalez.dev  -> 127.0.0.1:8000     -> Portfolio
       └── <slug>.<dominio>     -> 127.0.0.1:<puerto> -> Otro proyecto
```

Por lo tanto, el repositorio del portfolio no debe intentar administrar los demás proyectos ni ser dueño del Caddy global, UFW, Cloudflare o DNS. No existe Cloudflare Tunnel ni `cloudflared` en la arquitectura vigente.

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
- Dockerfiles productivos y `compose.production.yaml` portable (Fase 11), sin configuración específica del VPS.
- Artefactos de CI/release y contrato de handoff que consume `vps_ops_claude`.

No debe incluir la configuración global de otros proyectos del servidor.

### `docs/`

Contexto permanente para humanos y agentes.

---

## Frontera con el servidor

El portfolio debe exponer exactamente un punto de entrada HTTP al host:

```text
127.0.0.1:8000
```

El puerto podrá parametrizarse, pero `127.0.0.1:8000` es la reserva vigente y un contrato con operaciones.

El Caddy global del VPS (propiedad de operaciones) publica:

```text
https://lucianogonzalez.dev
    -> http://127.0.0.1:8000
```

El stack del portfolio no contiene ni configura el Caddy global, Cloudflare ni `cloudflared`. Conserva su propio gateway Caddy interno: son capas distintas.

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
 Vite + React    Apache + Laravel
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
- El Caddy global del VPS no necesita conocer la red Docker interna.
- Los contenedores web/api/mysql no se exponen directamente.

Caddy debe soportar el tráfico WebSocket/HMR de Vite durante desarrollo. Los matchers backend definitivos se derivan después de instalar Laravel, Filament y Livewire: `route:list --json` aporta las rutas registradas y el tráfico real de admin, autenticación, Livewire, assets y media completa el inventario. No se adivinan prefijos ni se fija un hash generado de Livewire. Una ruta backend conserva su ownership incluso cuando Laravel responde 404.

Caddy conserva el `Host` entrante y usa su comportamiento normal de forwarded headers. En producción la cadena es Cloudflare -> Caddy global -> gateway interno -> servicio; Fase 11 define y verifica localmente trusted proxies y protocolo reenviado para esa cadena, sin configuración de Cloudflare ni del Caddy global en este repositorio.

URLs canónicas de desarrollo:

```text
http://localhost:8000/        -> portfolio en español
http://localhost:8000/es      -> portfolio en español
http://localhost:8000/en      -> portfolio en inglés
http://localhost:8000/api/v1  -> API pública versionada
http://localhost:8000/admin   -> Filament
```

Los puertos internos de `web`, `api`, `mysql` y futuros servicios no son URLs canónicas y no se publican al host.

---

## Frontend

Dirección (decisión humana del 2026-09-14, Fase 6; reemplaza al front Next.js de Fases 3–5):

- React 18.
- Vite (SPA), alias `@` -> `src`.
- TypeScript estricto.
- Tailwind CSS 3.
- lucide-react, mp4box 0.5.x y fuentes autoalojadas con Fontsource (Fraunces Variable, Instrument Sans, IBM Plex Mono).
- Node.js 24 LTS.
- `pnpm` 11.20.0 fijado en `web/package.json`.

`pnpm`, `package.json` y `pnpm-lock.yaml` pertenecen exclusivamente a `web/`; no existe workspace pnpm en la raíz.

### Renderizado y datos

El front es una SPA sin render de servidor. `resolveLocale` toma el idioma del path (`/en` -> inglés; `/` y `/es` -> español). `loadStructuralContent` pide `profile` y `site`; si alguno falla, la página muestra solo el error general con Reintentar. `App.tsx` llama a `useRegion` una vez por cada región (`technologies`, `experiences`, `work-cases`, `projects`): son cuatro llamadas independientes al mismo hook, no un único hook que reparte varias colecciones, y sus cuatro fetches corren en paralelo; cada colección es regional y su fallo muestra un error con Reintentar solo en su sección. Todo cuerpo se valida como `unknown` en `web/src/lib/api.ts`. Las decisiones de presentación son funciones puras con tests en `web/src/lib/` (`groupExperience`, `groupProjects`, `groupTechnologies`, `visibleSections`, `formatPeriod`, `buildScene`, `thumbnailSlots`, `pickActiveId`, curvas de la escena); los componentes de `web/src/components/` solo las renderizan (por ejemplo `ExternalLink`, el enlace con flecha e hint `sr-only` para lo que abre en una pestaña nueva). `web/src/content.ts` contiene solo textos de interfaz, nunca contenido profesional. El tema (`light`/`dark`) se fija antes del primer pintado con `data-theme` desde `localStorage` (`portfolio-theme`) o `prefers-color-scheme`; los tokens viven en `docs/design/phase-6/DESIGN_TOKENS.md`. Consecuencia conocida: sin render de servidor, el HTML inicial no contiene el contenido; SEO/metadata se resuelven en Fase 8.

La base de Fase 3 mantenía `/es` y `/en` prerenderizables porque la UI base todavía no hacía ningún fetch de contenido. Desde Fase 5, la ruta pública localizada hace fetch obligatorio a los seis endpoints públicos en cada request (ver sección "Fase 5 — Sitio público" más abajo) y por lo tanto renderiza dinámicamente (`dynamic = 'force-dynamic'` en `[locale]/layout.tsx`), no estáticamente. `next build` sigue completando sin Laravel, Caddy ni MySQL en ejecución — incluyendo con `INTERNAL_API_ORIGIN` inalcanzable — porque el build no ejecuta ese fetch; la única demostración real de contenido ocurre en runtime, con Laravel arriba. El tema no se resuelve con `cookies()` del servidor: un bootstrap mínimo, estable y previo al paint aplica `data-theme` desde una preferencia explícita `light`/`dark` en `localStorage` o, si no existe, desde `prefers-color-scheme`. Cualquier supresión de warning de hidratación queda limitada al elemento raíz cuya mutación previa es intencional.

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

Fase 4 agrega seis endpoints públicos localizados (`GET /api/v1/{locale}/profile`, `experiences`, `work-cases`, `projects`, `technologies`, `site`) más las dos rutas estables de descarga de CV (`/cv/luciano-gonzalez-es.pdf`, `/cv/luciano-gonzalez-en.pdf`, fuera de `/api/v1`). El contrato exacto de campos, códigos de error y comportamiento de caché vive en `docs/api/PUBLIC_API_V1.md`; ese documento es la fuente de verdad byte a byte y está verificado automáticamente contra el código real por `api/tests/Feature/Documentation/ApiContractDocumentationTest.php`.

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

- Tipo: para cliente o personal (`kind`), con nombre del cliente solo en proyectos para clientes.
- Título, rol y estado de entrega (`delivery_status`: en producción, en uso, demo pública, en desarrollo).
- Resumen, problema, solución y resultado.
- Galería ordenada de hasta 12 capturas con alt ES/EN (`project_images`).
- Tecnologías.
- Demo URL.
- Repository URL.
- Destacado.
- Publicación.
- Orden.

La demo URL puede apuntar a subdominios alojados en el mismo servidor.

### Education y Language

- Formación: institución, programa y detalle opcional (ES/EN), años opcionales.
- Idiomas: nombre (ES/EN) y nivel (`native`, `a1`…`c2`).
- Ambas son colecciones ordenadas y publicables que se exponen dentro de `site`.

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

## Fase 4 — CMS relacional (implementado)

Esta sección documenta lo que el código realmente implementa, no una intención futura. El detalle exacto de campos/JSON público está en `docs/api/PUBLIC_API_V1.md`; esta sección cubre el modelo de dominio, el ciclo de vida de publicación y de assets, y la caché que ese contrato consume.

### Modelos explícitos

No existe una capa CMS/media genérica. Cada tabla tiene su propio modelo Eloquent explícito:

```text
Profile              (singleton)
SiteConfiguration    (singleton)
Experience
ExperienceHighlight  (pertenece a una Experience; sin publicación propia)
WorkCase
Project
Technology
ExpertiseArea
WorkPrinciple
ProfessionalLink
CvDocument
```

`Experience`, `WorkCase`, `Project` y `Technology` tienen una relación many-to-many con `Technology` a través de una tabla pivote con columna `position` propia; el pivote se reordena de forma explícita, nunca implícita.

### Máquina de estados de publicación

Todo modelo con publicación propia usa el mismo par de columnas: `status` (`draft`/`published`) y `is_visible` (bool), más `published_at`. Las transiciones administrativas son acciones de dominio explícitas, no ediciones directas de esas columnas:

```text
draft --Publish--> published (is_visible=false, published_at=now())
published (hidden) --Show--> published (is_visible=true) [+ copia pública si aplica]
published (visible) --Hide--> published (is_visible=false) [retira copia pública]
published --Return to draft--> draft (is_visible=false, published_at=null)
cualquier estado --Delete--> fila eliminada [+ limpieza de asset privado]
```

`Publish` exige que el contenido pase `PublicationValidator::assertPublishable()` (ver reglas bilingües abajo) antes de escribir la transacción, y solo puede aplicarse a contenido `draft`. `Show`/`Hide`/`Return to draft` para modelos con asset propio (`Profile`, `Project`, `Technology`, `CvDocument` para casos análogos) se ejecutan mediante `AssetLifecycleService`, no la acción de publicación simple, porque cambian también la copia pública del asset. Ninguna transición sensible puede ejecutarse fuera de una acción de dominio: `EditorialMutationGuard` (un observer Eloquent) rechaza cualquier escritura directa de `status`, `is_visible`, `published_at`, `key`, `key_locked` o de las columnas de asset propio que no ocurra dentro de `EditorialMutationContext::run()`.

### Bloqueo de clave pública (`key_locked`)

Cada modelo con clave pública (`key`) tiene una columna `key_locked`. `PublishContent` la fija a `true` en la primera publicación y nunca la vuelve a poner en `false` en ningún punto del código; es una transición de una sola dirección durante la vida de la fila. Con la clave bloqueada, `ChangePublicKey` exige confirmación explícita del editor para poder cambiar la clave igualmente; sin esa confirmación, el cambio se rechaza. Antes de la primera publicación (`key_locked=false`) la clave puede cambiarse libremente. No existe regeneración automática de clave a partir del título.

### Validación bilingüe

`PublicationValidator` decide, por tipo de contenido, qué pares `_es`/`_en` son obligatorios para publicar y cuáles son opcionales-mas-parejos:

- **Obligatorios (ambos idiomas requeridos para publicar):** `Profile` (`headline`, `short_summary`, `introduction`, `availability`, `cta`), `SiteConfiguration` (`projects_empty_message`, `contact_intro`, las cuatro etiquetas de categoría de tecnología), `Experience` (`role`, `summary`), `WorkCase` (`title`, `context`, `problem`, `contribution`, `technical_approach`, `outcome`), `Project` (`title`, `summary`, `problem`, `solution`), `ExpertiseArea` (`title`), `WorkPrinciple` (`statement`), `ProfessionalLink` (`label`), cada `ExperienceHighlight.content`.
- **Opcionales pero pareados (ambos presentes o ambos ausentes):** `Experience.organization_label`, `ExpertiseArea.description`. Un asset con imagen (`Profile.photo`, `Project.image`) exige su texto alternativo bilingüe únicamente cuando el asset existe.
- `Technology.name` no está localizado (nombres de marca/tecnología no se traducen).

La validación de clave pública es independiente (`assertKey()`): slug ASCII en minúsculas, único entre filas del mismo modelo.

### Mapa de dependencias de caché y protocolo de lock

`PublicContentDependencies::for()` traduce cada modelo mutado a la lista de endpoints públicos que debe invalidar en ambos locales:

| Mutación | Endpoints invalidados |
|---|---|
| `Profile` | `profile` |
| `SiteConfiguration`, `ProfessionalLink`, `ExpertiseArea`, `WorkPrinciple`, `CvDocument` | `site` |
| `Experience`, `ExperienceHighlight` | `experiences` |
| `WorkCase` | `work-cases` |
| `Project` | `projects` |
| `Technology` | `technologies`, `experiences`, `work-cases`, `projects` |

Las claves de caché son `public-content:v1:{locale}:{endpoint}`; las de lock de reconstrucción son `public-content-rebuild:v1:{locale}:{endpoint}`. Un miss público adquiere ese lock, revalida dentro del lock, reconstruye desde los scopes públicos y guarda con `Cache::forever()`; un hit normal nunca toma lock. Una mutación que reduce visibilidad adquiere los locks de todos los endpoints afectados, en orden lexicográfico, antes de retirar el asset público y antes del primer `forget`; los libera en orden inverso al terminar. Esto cierra la carrera donde una reconstrucción vieja podría escribir contenido obsoleto después de que la mutación ya invalidó. El almacén de caché configurado debe implementar `Illuminate\Contracts\Cache\LockProvider`; el servicio lo verifica en tiempo de ejecución y falla explícitamente si no.

### Ciclo de vida de assets propios

No existe un modelo `Media` genérico. `Profile.photo`, `Project.image`, `Technology.icon` y `CvDocument` (su único PDF) son columnas propias de cada modelo (`*_private_path`, `*_public_path` cuando aplica, `*_mime`, `*_size`). El PDF de CV nunca tiene copia pública; solo se sirve por streaming autenticado por estado a través de `/cv/*`.

Publicar o reemplazar una imagen pública sigue este orden: guardar el nuevo original privado -> validar contenido/tamaño/estado/alt bilingüe/entidad completa -> calcular (sin crear) la nueva ruta pública -> commitear la mutación de base de datos referenciando ambas rutas -> crear la copia pública -> verificar su existencia -> invalidar caché -> recién entonces borrar el asset anterior. Si la copia pública falla, una mutación compensatoria restaura la referencia/estado anterior, el asset anterior permanece, las rutas nuevas se limpian donde sea seguro, ambos locales se invalidan y Filament recibe un error controlado.

Retirar visibilidad, devolver a borrador o eliminar sigue el orden inverso de exposición primero: adquirir los locks de mutación -> borrar y verificar la copia pública -> olvidar caché ES/EN -> ejecutar y commitear la transición/eliminación en base de datos -> olvidar caché ES/EN otra vez -> limpieza segura del archivo privado (solo en eliminación) -> liberar locks. La primera invalidación es una excepción intencional al patrón normal "invalidar después del commit": prioriza cerrar el acceso público lo antes posible. La limpieza de filesystem usa reintentos acotados; un fallo final nunca se vuelve éxito silencioso, sino un error administrativo con un ID de operación registrado en logs (sin contenido binario, credenciales ni rutas privadas completas).

Límites de validación por asset:

| Asset | Contenido aceptado | Máximo |
|---|---|---:|
| Foto de Profile | JPEG, PNG, WebP | 5 MiB |
| Imagen de Project | JPEG, PNG, WebP | 8 MiB |
| Ícono de Technology | PNG, WebP | 1 MiB |
| PDF de CvDocument | PDF real y extensión `.pdf` | 5 MiB |

### Frontera de reparación de singleton ("ensure")

`Profile` y `SiteConfiguration` son singletons estructurales (`singleton_key = 'default'`). La garantía normal es la migración. Como defensa adicional ante un borrado manual excepcional de esa fila, las páginas Filament `EditProfile`/`EditSiteConfiguration` ejecutan un `insertOrIgnore` idempotente sobre la fila `default` en su `mount()`. Esa reparación:

- nunca se ejecuta desde un `GET` público — vive exclusivamente en páginas Filament autenticadas;
- nunca cambia una fila existente, solo crea la fila `default` si falta;
- nunca publica ni hace visible nada (inserta siempre en `draft`, `is_visible=false`, `published_at=null`).

Los endpoints públicos (`ProfileController`, `SiteController`) permanecen estrictamente de solo lectura: nunca crean esa fila. Una ausencia estructural anómala en producción responde `not_found`, no una reparación automática desde una request pública.

### Explícitamente fuera de alcance en Fase 4

Por diseño aprobado (spec sección 22), Fase 4 no implementa: el frontend público de Fase 5 ni integración completa de Next.js; preview público o tokens de preview; revisiones/historial de versiones/flujo de aprobación ni estados editoriales adicionales a `draft`/`published`; soft deletes; páginas públicas de detalle o ruteo por clave; alias/historial de clave; video de Project; SEO/Open Graph/analítica/metadata de Fase 8; animación o comportamiento de Fase 6; S3, URLs firmadas, Redis introducido solo para esta caché, colas/workers o un daemon de reconciliación; una arquitectura genérica de media/CMS/traducción/taxonomía/page-builder; registro público, múltiples roles, teams, tenants o RBAC empresarial; el hardening final de Fase 9.

---

## Fase 6 — CMS del portfolio (implementado)

Spec: `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md` (secciones 4 y 5). Todo cambio es aditivo sobre el modelo de Fase 4 y conserva sus reglas: estados `draft`/`published`, `is_visible`, `key_locked`, validación bilingüe, guard de mutaciones y caché por endpoint.

- **Project** suma `kind`, `client_name`, `role_*`, `delivery_status` y `result_*`. Un check de MySQL (`projects_client_name_kind_check`) impide guardar un cliente en un proyecto personal; publicar exige rol, resultado, estado de entrega y, si es para cliente, el nombre del cliente.
- **Galería (`project_images`)**: reemplaza la imagen única (migrada a la posición 0). Filas escritas solo por `ProjectGalleryService` (acción `SyncProjectImages`) o `AssetLifecycleService`; el guard rechaza cualquier otro cambio. `sync()` valida la lista propuesta (máximo 12, IDs del mismo proyecto, alt ≤ 500), guarda originales privados, aplica las reglas de publicación sobre la galería propuesta, confirma filas en una transacción y solo entonces copia públicos si el proyecto está publicado y visible. Un fallo de copia restaura las filas anteriores y descarta las subidas. Mostrar un proyecto copia cada captura; ocultar, volver a borrador o borrar retira las copias (y al borrar, los originales).
- **WorkCase** tiene `experience_id` opcional (`ON DELETE SET NULL`); la API expone `experience_key` solo si la experiencia es pública. Cambiar una Experience invalida `experiences` y `work-cases`.
- **EducationEntry** y **Language** son recursos Filament propios en "Profile and site", con revisión, reorden, cambio de clave y borrado como las demás colecciones; invalidan `site`.
- **Profile** suma `location` (sin traducir) y `work_modes` (JSON de `on_site`, `hybrid`, `remote`; la API los emite en ese orden).
- **Contenido inicial de Fase 6**: `php artisan db:seed --class=PhaseSixDraftContentSeeder` crea borradores (experiencias de Coned, vínculo de casos, ReservaHub, Trucks and Drinks sin textos ni capturas, formación, idiomas, tecnologías del CV, ubicación y modalidades) mediante acciones de dominio. Es idempotente: nunca cambia el estado de publicación de nada y todo contenido nuevo se crea en borrador, sin pisar lo existente (`location` y `work_modes` del Profile solo se completan si están vacíos, cada uno por separado). El Profile no tiene borrador por campo, así que si ya está publicado, la ubicación o las modalidades que el seeder complete quedan visibles de inmediato.

## Fase 5 — Sitio público (implementado; reemplazado en Fase 6)

> Registro histórico. El front Next.js descrito en esta sección fue eliminado el 2026-09-14 y reemplazado por la SPA Vite de Fase 6 (ver "Frontend" y "Escena de scroll con video"). El contrato de la API pública, la política de caché de Laravel y el importador inicial siguen vigentes.

Esta sección documenta lo que el código de `web/` realmente implementa como consumidor del contrato público de Fase 4, no una intención futura. El detalle exacto por comando/componente vive en `web/README.md`; esta sección cubre el transporte, los validadores, el loader coordinado, la compartición con alcance de request, la política de criticidad y los islands de cliente.

### Transporte server-only

`requestPublicResource<T>(endpoint, locale, validator, options?)` es la única función que hace `fetch` hacia el API interno. Nunca se importa desde un Client Component. Cada llamada es `GET`, `cache: 'no-store'`, `accept: application/json`, con un techo defensivo de ocho segundos vía `AbortSignal.timeout` y exactamente un intento (sin reintento ni backoff). Clasifica cualquier fallo operacional en cuatro categorías seguras y diagnósticas — `configuration` (falta `INTERNAL_API_ORIGIN`, ni se intenta el fetch), `network` (rechazo de fetch o timeout), `http` (respuesta no 2xx), `malformed` (JSON inválido, envelope inválido o rechazo del validador) — y nunca filtra el origen interno, el cuerpo de la respuesta ni el mensaje de error del API hacia el resultado. Un error de programador (por ejemplo un validador que lanza una excepción real) se propaga al error boundary de la ruta en vez de convertirse en un fallo operacional.

### Contratos y validadores

Los seis recursos públicos (`Profile`, `SiteConfiguration`, `Experience`, `WorkCase`, `Project`, `Technology`) y los tipos de soporte (`EndpointName`, `EndpointFailureKind`, `EndpointFailure`, `EndpointResult<T>`, `PublicPortfolioResults`) están tipados explícitamente. Un `unknown` recibido por red se convierte a uno de estos tipos únicamente después de pasar por un validador de runtime dedicado (`isProfile`, `isSiteConfiguration`, `isExperienceList`, `isWorkCaseList`, `isProjectList`, `isTechnologyList`); no existe un cast sin verificar entre el API y la UI.

### Loader coordinado y compartición con alcance de request

`loadPublicPortfolioUncached(locale)` arranca las seis lecturas (una por endpoint, mediante seis fetchers delgados) en paralelo con un único `Promise.all`, sin ningún `await` secuencial. `loadPublicPortfolio = cache(loadPublicPortfolioUncached)` usa el `cache()` de React con alcance de request — no `unstable_cache`, no Next Data Cache, no un mapa global de módulo. Laravel sigue siendo la única autoridad de caché temporal del contenido público (`PublicContentCache`, Fase 4); Next no agrega ISR, ventana de revalidación ni caché propia sobre estos seis endpoints.

El layout localizado, la página y `generateMetadata()` llaman al mismo export `loadPublicPortfolio(locale)`. Esto produce, dentro de una sola request de página, exactamente **seis** adquisiciones de contenido en Laravel en total — una por endpoint, no dieciocho — porque los tres puntos de llamada comparten una única adquisición con alcance de request. Esta afirmación está verificada con evidencia real de access log de Apache en un stack Docker de integración aislado (Tarea 13, evidencia completa en `docs/testing/PHASE_5_VERIFICATION.md`; no solo con un test unitario de wiring); una segunda request, independiente, siempre vuelve a hacer las seis lecturas `no-store` desde cero, sin persistencia ni caché entre requests en el lado de Next.

### Criticidad

La página aplica la política de criticidad en un único punto, no en el loader ni en las secciones:

- `Profile` **o** `Site` en fallo (cualquier tipo de fallo, incluido `malformed`) -> la página renderiza únicamente el shell de fallo estructural (error general localizado + Retry); no hay secciones profesionales, no hay las cinco anclas de navegación primaria y no hay contenido de respaldo hardcodeado. El layout renderiza en paralelo la variante de fallo estructural del header.
- En cualquier otro caso, cada sección renderiza y aplica su **propia** política de vacío/fallo regional de forma independiente: una colección fallida (`Experience`, `Work Cases`, `Projects`, `Technologies`) muestra fallo regional + Retry mientras el resto de la página sigue normal; una colección exitosa pero vacía típicamente se omite (su heading/ancla no existen), salvo `Projects`, cuyo `[]` explícito muestra únicamente `site.projects_empty_message` — nunca copy neutro genérico, nunca Retry. Es la única excepción y viene dictada por el propio contenido administrado, no por una regla de UI inventada.

### Islands de cliente

No existe un framework de hidratación selectiva propio: cada isla es un Client Component ordinario, delgado, con estado local. `ThemeSwitcher` e `IndexedWorkCases` usan `useSyncExternalStore` sobre `matchMedia`/preferencia de tema sin ningún store global de breakpoint compartido entre componentes — cada uno suscribe su propio listener a la misma media query cuando corresponde. `MobileNavigation` usa, en cambio, un `useEffect` con `matchMedia(...).addEventListener('change', ...)` component-local (no `useSyncExternalStore`) para cerrar el diálogo y mover el foco cuando el viewport cruza a escritorio, y el `<dialog>` nativo del navegador (`showModal`/`close`), sin librería de focus-trap. `FragmentFocusManager` mueve el foco a un ancla permitida tras la primera hidratación. Los controles solo-interactivos (disparador de Menú, botones de tema) quedan ocultos sin JavaScript mediante un marcador `data-js="ready"` que fija el bootstrap previo al paint; existe un `<noscript>` de navegación de respaldo para navegadores sin JavaScript.

### Media

`next/image` sigue consumiendo las referencias `/storage/...` root-relative del contrato público de Fase 4, sin cambios. `web/next.config.ts` agrega una única regla `rewrites()` server-only y estrecha (`/storage/:path*` -> `${INTERNAL_API_ORIGIN}/storage/:path*`) para que el propio self-fetch interno del optimizador de imágenes de Next resuelva la media a través del API; no cambia el contrato público, `images.remotePatterns` ni los puertos publicados al host, y nada interno llega al navegador ni al HTML. El tráfico real del navegador nunca pasa por esta regla porque Caddy ya enruta `/storage/*` directo a `api:80` sin tocar `web`.

### Metadata

`generateMetadata()` produce, con `Profile` válido, `title: "${name} — ${headline}"` y `description: short_summary`; con `Profile` fallido o malformado, solo un título de respaldo localizado. No hay canonical/alternates/Open Graph/Twitter/JSON-LD/sitemap/robots/analítica; eso es Fase 8.

### Importación inicial guardada de contenido

`App\Domain\Content\InitialPortfolioContent::data()` es la única representación determinística, a nivel de código, del contenido bilingüe aprobado de Fase 1 (Profile, Site, enlaces profesionales, tecnologías, áreas de expertise, work cases, principios de trabajo) más las tres rutas de asset aprobadas. Nunca parsea Markdown/PDF en runtime. `Experience` y `Project` son deliberadamente `[]`; las ocho etiquetas `technology_*_label_{es,en}` del Site y los campos `problem/contribution/technical_approach/outcome` de cada Work Case son deliberadamente `null` — son brechas editoriales humanas conocidas y aprobadas, no defectos de implementación (ver `docs/DEPLOYMENT.md`).

`App\Domain\Content\InitialPortfolioImporter`, expuesto como `php artisan portfolio:import-initial-content`, es un comando guardado de un solo uso e idempotentemente rechazante: su precondición completa, su resultado exacto (siempre draft/oculto/sin publicar) y la secuencia humana de revisión/publicación que debe seguir están documentados en `docs/DEPLOYMENT.md`, que es la fuente de verdad de ese contrato de handoff.

### Explícitamente fuera de alcance en Fase 5

Por diseño aprobado (spec `2026-09-09-phase-5-public-site-design.md`), Fase 5 no implementa: animación (Fase 6), Projects nuevos (Fase 7), SEO/Open Graph/analítica avanzada (Fase 8), E2E con navegador real (Fase 10), CI/release (Fase 11), ni ningún cambio de despliegue/Cloudflare/operación de servidor. La implementación de Fase 5 está completa y verificada de forma independiente; eso es distinto de la aceptación editorial (spec §3.1), que permanece bloqueada mientras el Work Case, las etiquetas de Technology y la decisión sobre Experience no tengan aprobación humana — ver `docs/DEPLOYMENT.md`.

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

### Escena de scroll con video (Fase 6, implementado)

Specs: `docs/superpowers/specs/2026-09-14-phase-6-cinematic-scroll-design.md` (lógica de scroll) y `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md` (sección 7, visual). No usa Motion ni GSAP/Lenis.

- Pista de 500vh con escena sticky: `<video>` local (`/media/scroll/ink-tree-network-v2.mp4`, H.264 todo intra, póster `ink-tree-network-v2-poster.webp`), `<canvas>` de 848×480 (tamaño nativo del cuadro), fondo SVG (`SceneBackdrop`) y tres beats en la columna izquierda. Mientras el contenido estructural carga o falla, un `<h1 className="sr-only">` de respaldo (texto de carga o de error) sustituye al `<h1>` real del bloque de copy completo.
- `useVideoScrub` sigue el scroll con lerp (`LERP_TAU = 8`, `SNAP = 0.002`); con WebCodecs arma un banco de frames WebP (mp4box + `VideoDecoder`, `LEAD = 24`, reintento por software, watchdog de 60 s) y dibuja los dos cuadros vecinos mezclados según la posición fraccional (`blendFrames`), con una LRU de `ImageBitmap` (`LRU_MAX = 24`). Sin WebCodecs, con movimiento reducido o ante un fallo, cae a `video.currentTime`.
- `web/src/lib/scene-timeline.ts` define la corrección de contraste de la transición (triángulo 0,55–0,75), el velo de la columna de texto (dimensionado al ancho real de esa columna, no a toda la escena) y el de la barra, el paso paisaje → constelación y `navLightText(p) = p >= NAV_LIGHT_THRESHOLD` (`0.70`), que decide a la vez el color del texto de la barra y la aparición de su velo, con valores medidos cuadro por cuadro (`docs/design/phase-6/scroll-video/PROMPTS.md`).
- Beat 1: foto de Profile, nombre y headline; beat 2: frase en tres partes o `short_summary`; beat 3: tecnologías backend, cierre o `availability` y un enlace de correo real (`sr-only`, se revela al recibir foco) además de un CTA visual duplicado no enfocable. Los beats son decorativos para lectores de pantalla; el mismo copy está en un bloque `sr-only`.
- La barra es transparente sobre la escena y sólida en las secciones, con sección activa, avatar, ES/EN, tema, CV y menú a pantalla completa en móvil.
- No hay scroll-jacking: el scroll nativo nunca se intercepta.

---

## Docker

Compose del portfolio esperado:

```text
gateway
web (Vite dev server, puerto interno 5173)
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

El código de desarrollo usa bind mounts. Volúmenes Linux administrados por Docker aíslan `web/node_modules` y `api/vendor`; un bootstrap frío explícito los puebla desde lockfiles y el arranque normal no reinstala dependencias. MySQL de desarrollo y `storage/app/public` son persistentes. La raíz `.env` alimenta interpolación de Compose, pero cada servicio recibe explícitamente solo sus variables propias; web nunca recibe credenciales MySQL ni orígenes internos.

PowerShell con Docker Desktop/WSL2 es el flujo Windows canónico. Ubuntu WSL2 opera el mismo engine mediante integración de Docker Desktop, sin instalar un segundo Docker Engine.

En el bind mount Windows/9p, el desarrollo web activa el polling de Vite
(`VITE_USE_POLLING=true`) para detectar ediciones y `VITE_HMR_CLIENT_PORT` para
que el HMR vuelva por el gateway. Esta elección es solo del watcher de
desarrollo: no cambia Caddy, sus rutas ni el build de producción.

### Runtime verificado de Fase 3

La aceptación local verificó Caddy `2.11.4-alpine`, Node `24.18.0`, pnpm
`11.20.0`, Next `16.2.12`, React `19.2.4`, PHP `8.5.8`, Laravel `13.25.0`,
Filament `5.7.6` y MySQL `8.4`. Los puertos internos son Caddy `80`, Next
`3000`, Apache/Laravel `80` y MySQL `3306`; solo Caddy se publica como
`127.0.0.1:8000`.

Las señales de health son `GET /__gateway/health` para Caddy, `GET /health`
para Next (desde Fase 6: `GET /` en Vite `5173`), `GET /up` para Laravel y
`mysqladmin ping` para MySQL. El bootstrap
frío instala dependencias desde los lockfiles en volúmenes inicialmente vacíos,
espera servicios saludables, ejecuta migraciones y crea explícitamente el link
estándar `public/storage` tras comprobar que solo se retiraría un enlace no
versionado. El entrypoint de API no crea ese link. No crea seeds ni un
administrador. El comando interactivo `portfolio:bootstrap-admin` crea una
sola cuenta administradora y nunca acepta ni muestra contraseñas por argumentos,
variables o logs.

Desde Fase 5, `api` y `api-test` también montan `./docs:/var/www/docs:ro`
(solo lectura) para que el comando guardado de importación inicial
(`portfolio:import-initial-content`) pueda leer las tres fuentes de asset
aprobadas (`docs/content/approved-assets/{professional-photo.jpg,cv-es.pdf,
cv-en.pdf}`) sin copiarlas nunca a `web/public` ni a un volumen público. No
agrega puertos al host ni cambia lo que ya se publica.

Los volúmenes persistentes del desarrollo son `mysql_data`, `api_public_media`
y (desde Fase 4) `api_private_media`; `web_node_modules` y `api_vendor` son
cachés reproducibles de dependencias. `api_private_media` respalda
`storage/app/private` (originales de foto/imagen/ícono y todo PDF de CV, que
nunca se sirve desde `public`); `api_public_media` respalda
`storage/app/public`, servido a través del enlace `public/storage`.
`mysql-test` usa `tmpfs`, no tiene volumen nombrado y no puede reutilizar la
base de desarrollo. La evidencia de rutas, Caddy y smoke manual está en
`infra/caddy/GATEWAY_INTEGRATION_EVIDENCE.md` y
`docs/testing/PHASE_3_VERIFICATION.md`; el registro equivalente de Fase 4 está
en `docs/testing/PHASE_4_VERIFICATION.md`.

### Runtime productivo (Fase 11, pendiente)

El entorno anterior es exclusivamente de desarrollo/pruebas. El runtime productivo es un artefacto separado que el repositorio crea en Fase 11: Dockerfiles productivos y `compose.production.yaml` portable, con el gateway Caddy interno como único puerto publicado (bind configurable, default `127.0.0.1:8000`), MySQL solo en red privada, volúmenes persistentes, restart policies y healthchecks reales. Se verifica levantándolo localmente antes de cualquier release. El contrato detallado vive en `docs/DEPLOYMENT.md`; el override específico del VPS pertenece a operaciones y nunca a este repositorio.

---

## CI y release

CI valida:

### Web

- Install.
- Lint.
- Format.
- Type check.
- Tests.
- Build.

### API

- Install.
- Format/lint/static checks configurados.
- Tests contra MySQL real.

### Docker

- Build de Dockerfiles productivos.
- Validación de `compose.production.yaml`.

### Seguridad

- Auditorías de dependencias acordadas.
- Detección de secretos accidentales.

El workflow de release, disparado por un tag versionado sobre `main`, publica las imágenes productivas en GHCR identificables por versión y commit SHA, y sus digests quedan registrados en `docs/DEPLOYMENT.md`.

CI y release nunca acceden al VPS, no contienen secretos del host ni de Cloudflare y no despliegan.

---

## Producción

El portfolio se ejecutará en el VPS Linux de OVHcloud administrado por Luciano, junto con otros proyectos Docker independientes. **Todavía no está desplegado.**

No se despliega a una plataforma de hosting de aplicaciones.

Cloudflare proporciona DNS proxied y edge (SSL/TLS `Full (strict)`); el Caddy global del VPS recibe el tráfico en `:443` y lo enruta a `127.0.0.1:8000`. Los procesos y datos de la aplicación permanecen en el VPS.

### Límite de responsabilidad del repositorio

El repositorio es responsable de todo lo necesario hasta una release completa y desplegable:

- servicios y límites de red del proyecto;
- gateway interno y rutas públicas esperadas;
- entrypoint `127.0.0.1:8000`;
- variables y secretos requeridos sin sus valores;
- persistencia, migraciones, bootstrap y health checks de aplicación;
- Dockerfiles productivos y `compose.production.yaml`, verificados localmente;
- procedimientos reproducibles de build, pruebas y smoke checks;
- CI, workflow de release, tag, GitHub Release cuando corresponda, imágenes GHCR y digests registrados;
- handoff y contrato de rollback de aplicación.

Ahí termina: el repositorio nunca entra al VPS.

El flujo operativo `vps_ops_claude` es responsable de operar el VPS real desde un contexto separado: host, SSH, UFW, Caddy global (`/srv/ingress`), Cloudflare y DNS, `/srv/apps`, `/srv/ops` (incluido el override del portfolio), `/srv/backups`, secretos reales, deployment, migraciones de producción, backups, restores, reboot recovery y registro multiproyecto. Este repositorio referencia ese límite, pero no duplica su checklist operacional.

La Fase 3 creó el entorno Compose completo de desarrollo/pruebas y límites compatibles con producción. No creó runtime productivo, targets de imagen especulativos, configuración de Cloudflare, unidades `systemd`, firewall, cron de backup ni scripts específicos del host; el runtime productivo corresponde a Fase 11.

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
- Caddy global, UFW, Cloudflare y DNS administrados por operaciones, fuera de este repo.
- Imágenes de release sin secretos.

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
- Contrato del sitio público (transporte, validadores, loader, criticidad) o del comando de importación inicial guardada.

Actualizar `SERVER_ARCHITECTURE.md` si cambia:

- Estructura multiproyecto del host.
- Estrategia de ingress pública (Cloudflare proxy / Caddy global).
- Convención de puertos.
- Política de backups compartida.
- Estrategia general de deploy.

Actualizar `DEPLOYMENT.md` cuando cambie el runtime productivo, el proceso de release o el contrato de handoff que consume `vps_ops_claude`.
