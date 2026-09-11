# Portfolio — Frontend (`web/`)

Aplicación pública del portfolio: Next.js App Router (TypeScript estricto), consumidora de solo lectura del API pública versionada de Laravel (`docs/api/PUBLIC_API_V1.md`). El frontend no duplica el CMS: nunca decide qué está publicado, nunca cachea contenido más allá de la vida de una request y nunca inventa contenido profesional que la API no entregó.

Este documento cubre exclusivamente `web/`. La arquitectura completa, la frontera con el backend y el contrato de despliegue están en `docs/ARCHITECTURE.md` y `docs/DEPLOYMENT.md`; el contrato exacto de campos/JSON está en `docs/api/PUBLIC_API_V1.md`.

## Comandos locales

Todos los comandos se ejecutan contra el servicio `web` del Compose raíz, nunca con Node instalado en el host:

```powershell
docker compose run --rm --no-deps web pnpm install --frozen-lockfile
docker compose run --rm --no-deps web pnpm format
docker compose run --rm --no-deps web pnpm format:check
docker compose run --rm --no-deps web pnpm lint
docker compose run --rm --no-deps web pnpm typecheck
docker compose run --rm --no-deps web pnpm test:run
docker compose run --rm --no-deps web pnpm build
```

`pnpm build` completa sin que `gateway`, `api` ni `mysql` estén corriendo, incluso con `INTERNAL_API_ORIGIN` apuntando a un host inalcanzable: la ruta pública localizada renderiza dinámicamente en cada request (`dynamic = 'force-dynamic'` en el layout de `[locale]`) y el build no ejecuta ningún fetch de contenido. La conectividad real solo se demuestra en runtime, con Laravel arriba, a través de `http://localhost:8000/es` y `/en`.

## Runtime de seis requests

Cada carga de `/[locale]` acciona exactamente seis lecturas independientes al API pública de Fase 4, una por endpoint (`profile`, `site`, `experiences`, `work-cases`, `projects`, `technologies`), definidas en `src/lib/api/types.ts` (`EndpointName`, `PublicPortfolioResults`) y `src/lib/api/fetchers.ts`.

El transporte, `requestPublicResource<T>` en `src/lib/api/client.ts`, es server-only (nunca se importa desde un Client Component) y para cada request:

- usa `GET` con `cache: 'no-store'` y `accept: application/json`;
- aplica un techo defensivo de ocho segundos vía `AbortSignal.timeout`;
- hace exactamente un intento, sin reintento ni backoff;
- clasifica cualquier fallo operacional en `configuration` (falta `INTERNAL_API_ORIGIN`, ni siquiera se intenta el fetch), `network` (rechazo de fetch o timeout), `http` (respuesta no 2xx) o `malformed` (JSON inválido, envelope inválido o el validador rechaza el `data`);
- nunca filtra el origen interno, el cuerpo de la respuesta ni el mensaje de error del API en un valor de fallo — el resultado siempre es un `EndpointResult<T>` seguro y diagnóstico, nunca una excepción para un fallo operacional conocido. Un error de programador (por ejemplo un validador que lanza) sí se propaga al error boundary de Next.

Cada `unknown` recibido se convierte a un tipo público solo después de una validación explícita de forma (`src/lib/api/validators.ts`: `isProfile`, `isSiteConfiguration`, `isExperienceList`, `isWorkCaseList`, `isProjectList`, `isTechnologyList`); no existe un cast sin verificar en ningún punto del camino API -> UI.

`src/lib/api/load-public-portfolio.ts` expone `loadPublicPortfolioUncached(locale)`, que arranca las seis lecturas en paralelo con un único `Promise.all` (nunca `await` secuencial), y `loadPublicPortfolio = cache(loadPublicPortfolioUncached)`, el `cache()` de React con alcance de request. No es `unstable_cache`, no es Next Data Cache, no es un mapa global de módulo: Laravel sigue siendo la única autoridad de caché temporal del contenido (Fase 4, `PublicContentCache`); Next no agrega ISR, ventana de revalidación ni caché propia sobre estos seis endpoints.

### Compartición dentro de una request, verificada en runtime real

El layout localizado (`src/app/[locale]/layout.tsx`), la página (`src/app/[locale]/page.tsx`) y `generateMetadata()` llaman al mismo export `loadPublicPortfolio(locale)`. Gracias al alcance de request de `cache()`, una sola request de página produce exactamente **seis** adquisiciones de contenido en Laravel en total — una por endpoint, no dieciocho — porque los tres puntos de llamada comparten una única adquisición con alcance de request. Esto está verificado con evidencia real de access log de Apache en un stack Docker de integración aislado (Tarea 13, evidencia completa en `docs/testing/PHASE_5_VERIFICATION.md`), no solo con un test unitario de wiring. Una segunda request, independiente, siempre vuelve a hacer las seis lecturas `no-store` desde cero: no hay persistencia ni caché entre requests en el lado de Next.

## Criticidad y estados de contenido

`page.tsx` aplica la política de criticidad en un único punto:

- si `Profile` **o** `Site` resuelven en fallo (cualquier `EndpointFailureKind`, incluido `malformed`), la página renderiza únicamente el shell de fallo estructural (error general localizado + control de Retry); no hay secciones profesionales ni las cinco anclas de navegación primaria, y no existe contenido de respaldo hardcodeado;
- en cualquier otro caso, cada sección renderiza y aplica su **propia** política de vacío/fallo regional: una colección fallida (`Experience`, `Work Cases`, `Projects`, `Technologies`) muestra un texto de fallo regional más un control de Retry mientras el resto de la página sigue normal; una colección exitosa pero vacía normalmente se omite (su heading/ancla no existen), salvo `Projects`, cuyo `[]` explícito muestra únicamente `site.projects_empty_message` (nunca copy neutro genérico, nunca Retry) — la única excepción, y viene dictada por el propio CMS.

`layout.tsx` consume el mismo loader compartido para decidir la variante `valid`/`structural-failure` del header y para la identidad (Profile) y los enlaces profesionales (Site) del header/footer.

## Estructura de componentes

- `src/components/sections/`: `hero-section.tsx`/`about-section.tsx` (Profile; foto anulable, nunca un avatar o placeholder de respaldo — layout solo texto cuando `photo: null`), `work-group.tsx` (Work Cases antes que Experience; `indexed-work-cases.tsx` implementa tabs de escritorio con mejora progresiva para 2+ casos, ARIA completo y activación automática por teclado, y contenido totalmente secuencial en el resto de los casos o sin JS), `expertise-group.tsx` (áreas de expertise del Site + Technologies agrupadas por `technology_groups`; las etiquetas/orden de grupo vienen únicamente del contrato validado del Site, nunca inventadas), `projects-section.tsx`, `approach-section.tsx`, `contact-section.tsx` (LinkedIn/GitHub abren en pestaña nueva con `rel="me noopener noreferrer"` y un anuncio accesible de pestaña nueva; email y CV son anchors normales del mismo contexto; `site.cv.url` se consume literal, sin ruta de CV hardcodeada).
- `src/components/layout/`: `site-header.tsx` (navegación de escritorio + controles de idioma/tema en ≥64rem, identidad + disparador de Menú <64rem, resuelto por CSS, sin bifurcación JS de breakpoint), `mobile-navigation.tsx` (`<dialog>` nativo, sin librería de focus-trap), `site-footer.tsx`, y una única fuente compartida de destinos (`#work #expertise #projects #approach #contact`, siempre en ese orden) en `primary-navigation.tsx`. Existe un fallback `<noscript>` de navegación para navegadores sin JavaScript; los controles solo-interactivos (disparador de Menú, botones de tema) quedan ocultos sin JS mediante un marcador `data-js="ready"` que fija el bootstrap previo al paint.
- `src/components/ui/`: `content-state.tsx` (shells de fallo estructural/regional/vacío neutro), `retry-button.tsx`, `fragment-focus-manager.tsx` (foco a anclas permitidas tras hidratar).

## Media

`next/image` sigue usando las referencias `/storage/...` root-relative de Fase 4 sin cambios en el contrato público. `next.config.ts` agrega una única regla `rewrites()` server-only y estrecha (`/storage/:path*` -> `${INTERNAL_API_ORIGIN}/storage/:path*`) para que el propio self-fetch interno del optimizador de imágenes de Next pueda resolver la media a través del API; el contrato público, `images.remotePatterns` y los puertos publicados al host no cambian, y nada interno llega al navegador ni al HTML. El tráfico real del navegador nunca pasa por esta regla: Caddy ya enruta `/storage/*` directo a `api:80` sin tocar `web`.

## Metadata

`generateMetadata()` produce, con Profile válido, `title: "${name} — ${headline}"` y `description: short_summary`; con Profile fallido o malformado, solo un título (`"Portfolio no disponible"`/`"Portfolio unavailable"`). No hay canonical/alternates/Open Graph/Twitter/JSON-LD/sitemap/robots/analítica todavía — eso es Fase 8.

## Límites de la ruta App Router

`loading.tsx` es un esqueleto neutro sin contenido inventado. `[locale]/error.tsx` y el `global-error.tsx` raíz son redes de seguridad solo para excepciones inesperadas — un fallo esperado del API es un valor tipado manejado en la composición normal, nunca se enruta aquí. `[locale]/not-found.tsx` es el 404 localizado.

## Explícitamente fuera de alcance en Fase 5

Animación (Fase 6), nuevos Projects (Fase 7), SEO/Open Graph/analítica avanzada (Fase 8), E2E con navegador real (Fase 10) y CI/release (Fase 11) no forman parte de este código. El sitio no ha sido desplegado a producción; el comando de importación de contenido inicial (`docs/DEPLOYMENT.md`) solo se ha verificado en un stack Docker de integración aislado con datos sintéticos de QA.
