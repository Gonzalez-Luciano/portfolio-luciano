# ROADMAP.md — Portfolio Backend PHP & Laravel

> ## Excepción de orden autorizada — `v0.9.0` (2026-09-22)
>
> Luciano autorizó explícitamente **adelantar la Fase 11** (runtime productivo,
> CI y release) **sin cerrar antes las Fases 9 y 10**, porque necesita el
> portfolio publicado para usarlo en su CV.
>
> Esto es una **excepción de orden registrada**, no un cierre de fases:
>
> - **Fase 9 — seguridad y endurecimiento: OPEN**, diferida después del
>   lanzamiento expedito. Ninguna de sus tareas se marcó como hecha.
> - **Fase 10 — pruebas y control de calidad: OPEN**, diferida después del
>   lanzamiento expedito. Ninguna de sus tareas se marcó como hecha.
> - **Fase 8** permanece abierta por la imagen social final y la revisión real
>   de indexación.
> - **Fase 11** se ejecutó por esta autorización humana para producir una
>   release desplegable, su runtime y su handoff.
> - **`v0.9.0`** es la primera release pública desplegable: funcional y
>   verificada localmente, pero **no representa el cierre del roadmap**.
> - **`v1.0.0`** queda reservada para cuando se completen los gates normales:
>   Fase 9, Fase 10, la imagen social final y cualquier gap real que esas fases
>   descubran.
>
> Antes de etiquetar `v0.9.0` se aplicó un **gate mínimo de release** acotado
> (secretos, build, tests existentes, runtime productivo local, smoke y
> contratos HTTP). Ese gate no sustituye a las Fases 9 y 10.
>
> Camino previsto: `v0.9.0` -> portfolio online -> Fase 9 -> Fase 10 -> imagen
> social -> `v0.9.x` según corresponda -> `v1.0.0`.
>
> **Resultado.** `v0.9.0` existe: tag anotado sobre `main` en
> `ac3ae1a5dba4a2dc37fcb589ff2c6301a287c6e0`, con CI verde en los cuatro jobs
> antes de etiquetar, GitHub Release publicada e imágenes en GHCR
> (`portfolio-web`, `portfolio-api`, `portfolio-gateway`) cuyos digests están
> registrados en `docs/DEPLOYMENT.md`. Esas imágenes se descargaron por digest
> y pasaron el smoke completo con la secuencia de deployment documentada.
>
> **El portfolio todavía NO está desplegado.** El deployment al VPS lo ejecuta
> después `vps_ops_claude`, fuera de este repositorio.

## 1. Visión

Construir un portfolio bilingüe, administrable y visualmente cinematográfico que posicione a Luciano González para puestos de:

**Backend PHP/Laravel Jr.**

El portfolio debe demostrar experiencia real en:

- APIs REST.
- Lógica de negocio.
- Sistemas de gestión educativa.
- Integraciones con APIs bancarias y medios de pago.
- MySQL.
- Automatizaciones.
- Mantenimiento de sistemas productivos.
- Docker y prácticas de entrega profesional.

La estética será experimental, pero el mensaje central siempre será backend.

---

## 2. Principios de ejecución

- Construir por fases.
- No comenzar por las animaciones.
- Validar primero el mensaje y la arquitectura.
- No inventar contenido para completar secciones.
- Priorizar una primera versión sólida antes de agregar efectos.
- Medir rendimiento desde el primer prototipo visual.
- Mantener el sitio funcional sin animaciones avanzadas.
- No publicar proyectos nuevos hasta que tengan calidad suficiente.
- Cada fase debe tener criterios de aceptación.
- No avanzar con errores críticos acumulados.

---

# Fase 0 — Descubrimiento y definición

## Objetivo

Cerrar las decisiones necesarias antes de inicializar el workspace de aplicaciones y los frameworks. El repositorio Git y su baseline documental ya existen.

## Tareas

- [x] Definir nombre del proyecto y repositorio.
- [x] Definir si será un monorepositorio desde el inicio.
- [x] Elegir herramienta de paquetes para frontend.
- [x] Confirmar Laravel 13 y versión de PHP.
- [x] Confirmar MySQL y versión de imagen Docker.
- [x] Elegir librería de internacionalización para Next.js App Router.
- [x] Confirmar Filament 5.
- [x] Definir estructura inicial de rutas `/es` y `/en`.
- [x] Definir URL local del frontend, API y administración.
- [x] Definir política inicial de ramas.
- [x] Reservar `127.0.0.1:8000` para el portfolio en el registro de puertos del servidor.
- [x] Confirmar dominio principal definitivo.
- [x] Definir convención de subdominios para futuros proyectos.
- [x] Confirmar que `cloudflared` será un servicio global del host y no un contenedor por proyecto.
      — **Superada:** la arquitectura vigente usa un VPS OVHcloud con Cloudflare DNS proxied + Caddy global del host y no utiliza Cloudflare Tunnel ni `cloudflared` (ver `docs/SERVER_ARCHITECTURE.md`). Se conserva como registro histórico de la decisión de Fase 0.
- [x] Diferir distribución Linux, CPU, RAM, almacenamiento y layout físico al preflight de deployment.
- [x] Confirmar `/srv/apps` como raíz de aplicaciones y `/srv/backups` como raíz de backups.
- [x] Definir formato del CV público.
- [x] Preparar política de publicación y confidencialidad.

## Entregables

- Documento de arquitectura inicial.
- Diagrama de alto nivel.
- Convenciones del repositorio.
- Decisiones registradas.
- Backlog inicial priorizado.

## Criterios de aceptación

- Frontend, backend y administración tienen responsabilidades claras.
- La arquitectura de producción está definida para ejecutarse completamente en el servidor propio mediante Docker y Cloudflare Tunnel.
  — Criterio histórico de Fase 0. La topología vigente es VPS OVHcloud + Cloudflare DNS proxied + Caddy global del host, sin Tunnel (`docs/SERVER_ARCHITECTURE.md`).
- Las decisiones diferidas están identificadas.
- No queda ninguna duda que impida crear el repositorio.

---

# Fase 1 — Identidad, contenido y narrativa

## Objetivo

Definir qué verá y entenderá el reclutador antes de diseñar la interfaz final.

## Tareas

### Posicionamiento

- [x] Redactar titular principal en español.
- [x] Redactar titular principal en inglés.
- [x] Redactar resumen profesional corto.
- [x] Redactar presentación extendida.
- [x] Definir llamado a la acción principal.
- [x] Definir mensaje de disponibilidad laboral.

### Experiencia

- [x] Crear una versión pública y anonimizada de la experiencia en gestión educativa.
- [x] Crear una versión pública y anonimizada de las integraciones con APIs bancarias.
- [x] Describir trabajo con APIs REST.
- [x] Describir automatizaciones y tareas programadas.
- [ ] Describir optimización de consultas y soporte multiinstitución.
- [x] Revisar que no se expongan clientes, credenciales, rutas, datos ni reglas privadas.
- [x] Separar responsabilidades propias de logros del equipo.

> Pendiente: el contenido aprobado describe optimización de consultas, pero no incluye una descripción pública y aprobada de soporte multiinstitución; por eso el ítem combinado permanece abierto.

### Contacto

- [x] Confirmar LinkedIn.
- [x] Confirmar GitHub.
- [x] Confirmar correo público.
- [x] Aprobar versión del CV descargable.

> Los dos PDF fueron reemplazados y aprobados como activos para futura publicación el 2026-09-09. El PDF español declara `/Lang(es-AR)` y el inglés `/Lang(en-US)`. Su contenido textual no se considera automáticamente aprobado como fuente de nuevas afirmaciones del sitio: esa reconciliación permanece dentro de la revisión de contenido de Fase 5.

### Fotografía

- [x] Elegir o producir fotografía profesional.
- [x] Preparar recorte horizontal y vertical.
- [x] Preparar versión optimizada.
- [x] Definir texto alternativo.
- [x] Verificar integración en modo claro y oscuro.

> Los derivados optimizados y la validación visual en ambos temas se completaron en el prototipo de Fase 2. La integración de medios mediante el futuro flujo CMS sigue pendiente.

### Traducción

- [x] Crear glosario técnico español/inglés.
- [x] Traducir el contenido profesional.
- [x] Revisar el inglés de forma humana.
- [x] Mantener consistencia en términos como backend, API, payment integration y education management platform/system según el alcance aprobado.

## Entregables

- Documento de contenido aprobado.
- Textos en español e inglés.
- Material visual inicial.
- CV definitivo.
- Matriz de confidencialidad.

## Criterios de aceptación

- El mensaje “resuelve sistemas backend reales” se entiende en menos de diez segundos.
- Las integraciones bancarias y la gestión educativa tienen prioridad.
- El contenido es creíble sin exageraciones.
- Todo texto tiene versión equivalente en ambos idiomas.
- No hay información confidencial.

---

# Fase 2 — Diseño de experiencia

## Objetivo

Diseñar la experiencia completa antes de implementar animaciones complejas.

## Tareas

### Arquitectura de información

- [x] Definir orden definitivo de secciones.
- [x] Definir navegación de escritorio.
- [x] Definir navegación móvil.
- [x] Definir comportamiento de enlaces internos.
- [x] Definir ubicación de idioma y tema.
- [x] Definir estados con y sin proyectos publicados.

### Wireframes

- [x] Wireframe del hero.
- [x] Wireframe de experiencia.
- [x] Wireframe de casos de trabajo.
- [x] Wireframe de especializaciones.
- [x] Wireframe de proyectos.
- [x] Wireframe de tecnologías.
- [x] Wireframe de forma de trabajo.
- [x] Wireframe de contacto.
- [x] Wireframe de menú móvil.
- [x] Wireframe de estados de carga y error.

### Sistema visual

- [x] Elegir paleta clara.
- [x] Elegir paleta oscura.
- [x] Elegir tipografías.
- [x] Definir escala tipográfica.
- [x] Definir espaciado.
- [x] Definir radios, bordes y sombras.
- [x] Definir tokens de color y movimiento.
- [x] Definir tratamiento de fotografía.
- [x] Definir iconografía.
- [x] Definir apariencia de tarjetas de proyecto.
- [x] Definir estados hover, focus, active y disabled.

### Prototipo

- [x] Crear prototipo estático de alta fidelidad.
- [x] Revisar desktop.
- [x] Revisar tablet.
- [x] Revisar mobile.
- [x] Revisar modo claro.
- [x] Revisar modo oscuro.
- [x] Validar contraste.
- [x] Validar legibilidad sin animaciones.

## Entregables

- Mapa del sitio.
- Wireframes.
- Prototipo de alta fidelidad.
- Tokens de diseño.
- Especificación responsive.

## Criterios de aceptación

- El diseño se reconoce como profesional y técnico.
- No parece una web de agencia, videojuego o criptomonedas.
- La fotografía y el mensaje backend dominan el hero.
- La página funciona visualmente sin animación.
- Los dos temas tienen calidad equivalente.
- El diseño móvil no es una versión recortada sin criterio.

---

# Fase 3 — Arquitectura técnica y entorno Docker

## Objetivo

Crear una base reproducible para frontend, backend, base de datos y administración, con un entorno completo de desarrollo/pruebas y límites de servicio compatibles con el futuro handoff de deployment.

## Tareas

### Repositorio

- [x] Inicializar el workspace de aplicaciones del monorepositorio existente.
- [x] Crear estructura para `web`, `api`, `infra` y `docs`.
- [x] Configurar `.editorconfig`.
- [x] Configurar política de variables de entorno.
- [x] Crear archivos de ejemplo sin secretos.
- [x] Documentar comandos principales.

### Frontend

- [x] Inicializar Next.js con App Router.
- [x] Activar TypeScript estricto.
- [x] Configurar Tailwind CSS.
- [x] Configurar lint y formato.
- [x] Preparar rutas localizadas.
- [x] Preparar sistema de tema.
- [x] Preparar cliente de API tipado.
- [x] Definir estrategia de Server y Client Components.

### Backend

- [x] Inicializar Laravel.
- [x] Servir Laravel mediante Apache + PHP 8.5 interno con `public/` como `DocumentRoot`, rewrite/front controller y permisos runtime explícitos.
- [x] Configurar MySQL.
- [x] Configurar API versionada.
- [x] Configurar recursos JSON.
- [x] Implementar y probar el envelope mínimo exacto de éxito/error de `/api/v1`.
- [x] Configurar autenticación administrativa.
- [x] Agregar `users.is_admin BOOLEAN NOT NULL DEFAULT false` como único marcador administrativo de Fase 3 y usarlo en `canAccessPanel()`.
- [x] Instalar y configurar Filament.
- [x] Preparar almacenamiento de medios.
- [x] Configurar CORS.
- [x] Configurar rate limiting.
- [x] Configurar logs.

### Docker

- [x] Crear entorno de desarrollo.
- [x] Crear servicio frontend.
- [x] Crear servicio backend.
- [x] Crear servicio MySQL 8.4 persistente para desarrollo.
- [x] Crear servicio/perfil MySQL 8.4 descartable y bajo demanda para pruebas automatizadas.
- [x] Crear `api-test` one-shot en el perfil de pruebas, reutilizando la imagen backend y esperando a `mysql-test` saludable.
- [x] Crear Caddy como gateway/reverse proxy del portfolio y enlazar únicamente `127.0.0.1:8000`.
- [x] Inventariar rutas y tráfico público reales de Laravel/Filament/Livewire/media antes de fijar los matchers backend de Caddy.
- [x] Verificar compatibilidad del stack con el `cloudflared` global del servidor; no crear `cloudflared` dentro del proyecto.
      — Registro histórico: `cloudflared` ya no forma parte de la arquitectura vigente. La regla equivalente actual es que el gateway interno publica solo `127.0.0.1:8000` hacia el Caddy global del VPS.
- [x] Definir redes Docker internas del portfolio (`front` y `data` o equivalente).
- [x] Confirmar que MySQL no publica puertos al host ni a Internet.
- [x] Agregar health checks.
- [x] Agregar volúmenes.
- [x] Configurar red interna.
- [x] Definir estrategia de migraciones.
- [x] Definir seed inicial.
- [x] Crear un comando interactivo y explícito de bootstrap del administrador con entrada secreta oculta; los seeds normales no deben crear credenciales.
- [x] Documentar arranque, parada y reinicio.
- [x] Documentar PowerShell + Docker Desktop con backend WSL2 como flujo canónico de Windows.
- [x] Comprobar operación equivalente desde Ubuntu WSL2 mediante la integración de Docker Desktop, sin instalar un segundo Docker Engine.
- [x] Documentar el contrato de handoff sin crear Compose final de producción ni infraestructura específica del host.

## Entregables

- Repositorio inicial.
- Entorno local reproducible.
- Frontend base.
- API base.
- Panel administrativo accesible.
- Documentación de instalación.

## Criterios de aceptación

- El entorno completo se levanta mediante un procedimiento único documentado.
- Frontend y backend se comunican.
- La base de datos persiste.
- Las pruebas usan una base MySQL 8.4 descartable y no pueden reutilizar datos de desarrollo.
- No hay secretos en Git.
- Los builds básicos terminan correctamente.
- La administración requiere autenticación.
- Frontend, backend, gateway y MySQL pueden ejecutarse de forma reproducible mediante Docker; `cloudflared` permanece como servicio compartido del host.
- El repositorio describe límites, persistencia, variables, migraciones, bootstrap y health checks suficientes para el futuro agente externo de operaciones.

> Nota de reconciliación (2026-09-14): Fase 3 creó y verificó únicamente el entorno de desarrollo/pruebas; no creó runtime productivo, y eso fue correcto. La mención a `cloudflared` es histórica (ver `docs/SERVER_ARCHITECTURE.md`). El runtime productivo, CI, release e imágenes GHCR pertenecen a Fase 11.

---

# Fase 4 — CMS y modelo de datos

## Objetivo

Permitir administrar todo el contenido relevante desde Laravel/Filament.

## Tareas

### Modelado

- [x] Crear perfil.
- [x] Crear experiencias.
- [x] Crear casos de trabajo.
- [x] Crear proyectos.
- [x] Crear tecnologías.
- [x] Crear enlaces.
- [x] Crear configuración del sitio.
- [x] Crear medios.
- [x] Crear estados borrador y publicado.
- [x] Crear orden manual.
- [x] Crear visibilidad por elemento.
- [x] Crear campos bilingües.

### Administración

- [x] CRUD de perfil.
- [x] CRUD de experiencias.
- [x] CRUD de casos.
- [x] CRUD de proyectos.
- [x] CRUD de tecnologías.
- [x] Gestión de enlaces.
- [x] Gestión del CV.
- [x] Gestión de imágenes.
- [x] Filtros por estado.
- [x] Ordenamiento.
- [x] Validaciones.
- [x] Confirmaciones de eliminación.
- [x] Vista previa o mecanismo de revisión.

### API

- [x] Endpoint público de configuración.
- [x] Endpoint público de perfil.
- [x] Endpoint público de experiencia.
- [x] Endpoint público de casos.
- [x] Endpoint público de proyectos.
- [x] Endpoint público de tecnologías.
- [x] Respuestas por idioma.
- [x] Solo contenido publicado.
- [x] Recursos JSON consistentes.
- [x] Caché inicial.
- [x] Errores controlados.
- [x] Documentación del contrato.

## Entregables

- Migraciones.
- Modelos.
- Panel administrable.
- API pública.
- Seed de demostración.
- Documento del contrato de API.

## Criterios de aceptación

- Se puede modificar el contenido sin tocar React.
- Los borradores no aparecen públicamente.
- El contenido español e inglés se administra sin confusión.
- Los proyectos pueden quedar ocultos hasta estar listos.
- El sitio puede funcionar con cero proyectos.
- La API no expone datos administrativos.

> Fase 4 verificada y cerrada el 2026-09-09. Evidencia completa de
> verificación en `docs/testing/PHASE_4_VERIFICATION.md`. La revisión final
> independiente de todo el branch (fila 14 de esa evidencia) queda a cargo de
> un despacho separado del controller antes de fusionar o cerrar la rama.

---

# Fase 5 — Implementación del sitio público

## Objetivo

Construir una versión funcional, accesible y responsive antes de añadir la capa cinematográfica.

## Tareas

> **2026-09-11 — Estado de ejecución de Fase 5:** implementación técnica
> completa (Tareas 1–14 del plan aprobado), revisada tarea por tarea y con
> revisión final de rama, evidencia en
> `docs/testing/PHASE_5_VERIFICATION.md`. Los ítems marcados abajo tienen
> evidencia automatizada y/o de navegador real genuina. Los ítems de
> accesibilidad que requieren viewport móvil real, zoom real al 200% o lector
> de pantalla real permanecen sin marcar: el entorno de ejecución no pudo
> producir esa evidencia específica (ver `PHASE_5_VERIFICATION.md` §4.4) y no
> se marca sin evidencia real. La **aceptación editorial de Fase 5 permanece
> bloqueada** (ver más abajo y `PHASE_5_VERIFICATION.md` §5): Casos de trabajo,
> etiquetas de grupo de Tecnologías y la decisión de Experiencia siguen sin
> aprobación humana. Los criterios de aceptación de esta fase (al pie de esta
> sección) por lo tanto **no** se marcan como cumplidos.

### Base

- [x] Layout global.
- [x] Header.
- [x] Navegación.
- [x] Selector de idioma.
- [x] Selector de tema.
- [x] Footer.
- [x] Estados de carga.
- [x] Estados de error.
- [x] Página 404.

### Secciones

- [x] Hero.
- [x] Presentación.
- [x] Experiencia.
- [x] Casos de trabajo.
- [x] Especializaciones.
- [x] Proyectos.
- [x] Tecnologías.
- [x] Forma de trabajo.
- [x] Contacto.
- [x] Descarga de CV.

### Integración

- [x] Consumo de API.
- [x] Tipado de respuestas.
- [x] Manejo de errores.
- [x] Estrategia de caché.
- [x] Revalidación.
- [x] Fallback si la API no está disponible.
- [x] Optimización de imágenes.
- [x] Integración de fotografía.
- [x] Metadata inicial.

### Responsive y accesibilidad

- [x] Navegación por teclado.
- [x] Focus visible.
- [ ] Contraste. — tokens validados en el prototipo de Fase 2; no se corrió una auditoría de contraste dedicada sobre el sitio implementado de Fase 5.
- [x] Jerarquía de encabezados.
- [x] Textos alternativos.
- [ ] Lectura con zoom. — requiere zoom real de navegador al 200%; no disponible en esta sesión de automatización (`PHASE_5_VERIFICATION.md` §4.4).
- [ ] Menú móvil accesible. — cobertura automatizada completa (jsdom); el diálogo nativo `<dialog>` en viewport móvil real no se pudo verificar en esta sesión (sin redimensionado de viewport real disponible).
- [ ] Prueba con lector de pantalla. — requiere ejecución humana con un lector de pantalla real.
- [ ] Prueba sin animaciones. — `prefers-reduced-motion` verificado por CSS/tests automatizados; falta confirmación en navegador real.

### Contenido final y siembra de producción

- [ ] Revisar todo el contenido real aprobado (experiencia, casos de trabajo,
      proyectos, tecnologías, enlaces, CV) y confirmar qué está listo para
      publicarse tal cual quedó definido en Fase 1/2. — **bloqueado**: Casos de
      trabajo (problem/contribution/technical_approach/outcome) y las
      etiquetas de grupo de Tecnologías no tienen aprobación humana; la
      decisión de Experiencia (datos aprobados u omisión explícita) tampoco
      está registrada. Ver `docs/testing/PHASE_5_VERIFICATION.md` §5.
- [x] Crear un seeder de producción (distinto del `PortfolioContentSeeder` de
      desarrollo, que deliberadamente deja Experience/Project/CV vacíos) que
      cargue ese contenido real aprobado, para ejecutarse una única vez al
      desplegar en el servidor definitivo. — `App\Domain\Content\InitialPortfolioContent`
      + `InitialPortfolioImporter` + `php artisan portfolio:import-initial-content`
      (Tareas 11–12), verificado end-to-end contra una base de datos migrada
      real en tres entornos Docker aislados distintos.

## Entregables

- Portfolio funcional.
- Dos idiomas.
- Dos temas.
- Contenido conectado al CMS.
- Versión responsive.
- Versión accesible base.
- Seeder de producción listo para la primera carga en el servidor.

## Criterios de aceptación

- Todo el contenido principal es utilizable.
- Los enlaces de contacto funcionan.
- El CV se descarga correctamente.
- El sitio es navegable con teclado.
- La experiencia es clara sin animaciones.
- No hay errores de hidratación.
- El backend sigue siendo el centro del mensaje.

---

# Fase 6 — Sistema de movimiento

## Objetivo

Agregar movimiento cinematográfico de forma progresiva y medible.

## Orden obligatorio

1. Microinteracciones.
2. Entradas de secciones.
3. Transiciones de layout.
4. Secuencias de scroll.
5. Campo de nodos.
6. Ajustes de rendimiento.
7. Degradación móvil y movimiento reducido.

## Tareas

### Escena de scroll con video (nuevo front base en `web/`)

Spec: `docs/superpowers/specs/2026-09-14-phase-6-cinematic-scroll-design.md`.

- [x] App Vite + React 18 + TypeScript + Tailwind 3 con alias `@`.
- [x] Pista de 500vh, escena sticky, video, canvas y overlay.
- [x] Tres secciones secuenciales, stagger y navbar con cambio de color.
- [x] Hamburguesa y overlay de menú móvil.
- [x] `useVideoScrub`: lerp, banco de frames WebCodecs/mp4box, LRU, reintento por software, watchdog y fallback a `currentTime`.
- [x] Front Next.js de Fase 5 eliminado; la escena es el front en `web/` (`/`, `/es`, `/en`).
- [x] Contenido desde la API pública (Profile, Site, Technologies) con validación en runtime.
- [x] Campos opcionales de Profile `statement_*` y `closing_*` en Filament y en la API.
- [ ] QA en navegador real con ventana visible (scrub del canvas, móvil, movimiento reducido).
- [x] Rediseño visual con la nueva foto (la lógica de scroll se conserva).

### Portfolio completo (front)

Plan: `docs/superpowers/plans/2026-09-14-phase-6-front-visual.md`.

- [x] Paleta Terracota clara y oscura, fuentes autoalojadas y tema sin parpadeo.
- [x] Video todo intra con póster, mezcla de cuadros, corrección de la transición, velos y fondo paisaje → constelación.
- [x] Navegación transparente/sólida con sección activa, avatar, idioma, tema, CV y menú móvil.
- [x] Experiencia en línea de tiempo con casos desplegables.
- [x] Proyectos para clientes y personales en dossier con galería y visor.
- [x] Stack, Sobre mí y Contacto.
- [ ] QA en navegador real con ventana visible (`docs/testing/PHASE_6_BROWSER_QA.md`).

### CMS y API del portfolio

Spec: `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md`. Plan: `docs/superpowers/plans/2026-09-14-phase-6-cms-api.md`.

- [x] Proyectos para clientes y personales: tipo, cliente, rol, estado de entrega y resultado.
- [x] Galería ordenada de hasta 12 capturas por proyecto, editable en Filament.
- [x] Casos vinculados a su experiencia (`experience_key`).
- [x] Formación e idiomas como colecciones administrables.
- [x] Ubicación y modalidades de trabajo en Profile.
- [x] Borradores de contenido de Fase 6 cargados con acciones de dominio.
- [x] Revisión y publicación humana de los borradores en Filament.
- [x] Textos de problema, solución y resultado y capturas de Trucks and Drinks (los aporta Luciano).

### Alcance no ejecutado (planificación prematura)

Los bloques **Motion**, **GSAP y ScrollTrigger**, **Campo de nodos interactivos** y **Rendimiento** se escribieron antes del brainstorming del 2026-09-14, cuando la fase se llamaba "Sistema de movimiento" y se imaginaba como una capa de animaciones sobre el front de Fase 5. Ese enfoque quedó sin efecto: el 2026-09-14 la fase se redefinió como el rediseño completo del portfolio (spec `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md`) y se ejecutó con dos planes:

- `docs/superpowers/plans/2026-09-14-phase-6-cms-api.md` (CMS y API): 9 tareas, completas.
- `docs/superpowers/plans/2026-09-14-phase-6-front-visual.md` (front y sistema visual): 11 tareas, completas.

El movimiento que la fase necesitaba quedó resuelto dentro de ese trabajo: escena de scroll con video, curvas de opacidad y stagger, ciclo de cielo, transición amanecer, acordeones, galería con visor y menú móvil, todo con soporte de `prefers-reduced-motion`. No se incorporaron GSAP/ScrollTrigger ni el campo de nodos interactivos: no hizo falta ninguna librería extra y el campo de nodos habría competido con la escena de tinta ya aprobada.

Los ítems de esos cuatro bloques se dejan sin marcar a propósito, como registro de lo que se decidió no hacer. Si en el futuro se quiere retomar alguno (por ejemplo, mediciones formales de rendimiento), corresponde replantearlo con su propia spec y su propio plan, no darlo por pendiente de esta fase.

### Motion

- [ ] Animación inicial del hero.
- [ ] Estados de botones.
- [ ] Estados de enlaces.
- [ ] Selector de tema.
- [ ] Selector de idioma.
- [ ] Tarjetas de experiencia.
- [ ] Tarjetas de proyecto.
- [ ] Transiciones de secciones.
- [ ] Presencia y salida de menú móvil.

### GSAP y ScrollTrigger

- [ ] Definir storyboard de scroll.
- [ ] Elegir un máximo de dos secuencias complejas.
- [ ] Implementar entrada tipográfica.
- [ ] Implementar transición narrativa de experiencia.
- [ ] Evaluar una sección fijada.
- [ ] Probar scroll rápido.
- [ ] Probar cambio de tamaño.
- [ ] Limpiar timelines al desmontar.
- [ ] Eliminar efectos que no aporten.

### Campo de nodos interactivos

- [ ] Crear prototipo Canvas 2D.
- [ ] Medir rendimiento.
- [ ] Crear prototipo WebGL solo si es necesario.
- [ ] Comparar consumo y resultado.
- [ ] Reacción por proximidad al cursor.
- [ ] Conexiones entre nodos.
- [ ] Límites de densidad.
- [ ] Pausa fuera de viewport.
- [ ] Pausa con pestaña oculta.
- [ ] Reducción automática en móvil.
- [ ] Versión estática para movimiento reducido.
- [ ] Integración con tema claro y oscuro.
- [ ] Verificar que no tape la fotografía ni el texto.

### Rendimiento

- [ ] Importación dinámica.
- [ ] Medición de FPS.
- [ ] Medición de CPU.
- [ ] Medición de memoria.
- [ ] Medición de JavaScript transferido.
- [ ] Prueba en notebook media.
- [ ] Prueba en teléfono Android medio.
- [ ] Prueba con ahorro de batería.
- [ ] Desactivar efectos fuera del viewport.
- [ ] Revisar Core Web Vitals.

## Entregables

- Sistema de movimiento documentado.
- Campo de nodos interactivo.
- Versión reducida.
- Storyboard.
- Informe de rendimiento antes y después.

## Criterios de aceptación

- Las animaciones refuerzan la narrativa.
- El contenido nunca queda bloqueado.
- El hero es usable inmediatamente.
- No hay scroll-jacking.
- El campo de nodos responde con suavidad.
- La versión móvil mantiene fluidez.
- `prefers-reduced-motion` funciona.
- El sitio sigue siendo profesional.

---

# Fase 7 — Proyectos demostrables (alcance reconciliado)

## Objetivo

Publicar y verificar proyectos reales suficientes para respaldar el perfil backend, sin crear proyectos adicionales solo para cumplir el roadmap.

### Cierre práctico (2026-09-21)

La decisión humana de cierre reemplazó el supuesto original de crear proyectos nuevos desde cero. El portfolio ya contaba con dos proyectos reales, complementarios y suficientes para el objetivo profesional:

- **Trucks and Drinks**: proyecto para cliente, en uso, con foco en backend Symfony, reglas de negocio, stock, eventos y presupuestos.
- **ReservaHub**: proyecto personal publicado como demo, con Laravel, API, concurrencia, pagos simulados, pruebas, Docker y CI.

El cierre se realizó sobre el stack local asociado a `main`:

- [x] Auditar `PhaseSixDraftContentSeeder` contra el modelo, CMS, API y frontend finales de Fase 6.
- [x] Confirmar que no faltaba contenido aprobado recuperable y que las imágenes dependían de carga humana en Filament.
- [x] Ejecutar migraciones y los seeders canónicos (`PortfolioContentSeeder` y `PhaseSixDraftContentSeeder`) sin reset destructivo.
- [x] Cargar manualmente las imágenes reales desde Filament: cuatro para Trucks and Drinks y cinco para ReservaHub.
- [x] Publicar ambos proyectos con orden, tecnologías, textos ES/EN, visibilidad y alt text verificados.
- [x] Verificar CMS, API, `ProjectDossier`, `ProjectGallery`, enlaces, escritorio, móvil y ausencia de duplicados.
- [x] Revisar visualmente las nueve capturas y confirmar que no exponen secretos ni información privada evidente.
- [x] Recibir aprobación humana final de la presentación.

No existe `progress.md`; por lo tanto, `ROADMAP.md` conserva el registro documental de esta intervención.

### Alcance original deliberadamente no ejecutado

El checklist original que sigue se conserva sin marcar como registro histórico. No se creó otro proyecto, no se grabó un video, no se produjo un diagrama y no se montó otra demo. Tampoco se exigió que el proyecto para cliente tuviera repositorio público, README, licencia o instrucciones de instalación. Esos entregables no fueron necesarios para satisfacer el objetivo profesional y no deben interpretarse como deuda pendiente de esta fase.

## Criterios para elegir proyectos

Cada proyecto debe demostrar al menos una capacidad relevante:

- API REST con Laravel.
- Autenticación y autorización.
- Integración externa.
- Pagos o webhooks simulados de forma segura.
- Procesos en cola.
- Tareas programadas.
- MySQL y modelado.
- Pruebas.
- Docker.
- CI/CD.
- Documentación.
- Observabilidad.
- Manejo de errores.
- Seguridad.

## Material obligatorio por proyecto

- [ ] Repositorio público limpio.
- [ ] README completo.
- [ ] Capturas profesionales.
- [ ] Video breve o demo.
- [ ] Diagrama.
- [ ] Tecnologías.
- [ ] Problema.
- [ ] Solución.
- [ ] Instrucciones de instalación.
- [ ] Docker.
- [ ] Pruebas.
- [ ] Licencia.
- [ ] Demo estable.
- [ ] Datos de demostración seguros.
- [ ] Texto español e inglés para la tarjeta.

## Publicación

- [x] Cargar proyectos en CMS.
- [x] Revisar imágenes y galerías.
- [x] Verificar enlaces reales disponibles.
- [x] Verificar responsive.
- [x] Verificar traducción ES/EN.
- [x] Verificar accesibilidad y alt text.
- [x] Publicar después de la revisión humana.

## Criterios de aceptación

- Ningún proyecto parece un tutorial sin evolución.
- Cada proyecto explica decisiones técnicas.
- El material visual tiene calidad consistente.
- Las demos funcionan.
- Los repositorios no contienen secretos.
- Los proyectos apoyan la búsqueda de empleo Laravel.

**Estado:** Fase 7 cerrada el 2026-09-21 por decisión humana, con Trucks and Drinks y ReservaHub publicados y verificados. Los proyectos existentes satisfacen el objetivo; no se requiere crear otro proyecto para cerrar la fase.

---

# Fase 8 — SEO, metadata y analítica

## Objetivo

Hacer que el portfolio sea encontrable, compartible y medible.

## Tareas

- [x] Metadata en español.
- [x] Metadata en inglés.
- [x] Canonical.
- [x] `hreflang`.
- [x] Sitemap.
- [x] Robots.
- [x] Open Graph.
- [x] Twitter card.
- [ ] Imagen social.
- [x] Datos estructurados Person.
- [x] Datos estructurados WebSite.
- [x] Favicon.
- [x] Página 404.
- [x] Exclusión de administración.
- [x] Analítica respetuosa de privacidad.
- [x] Eventos de clic en CV, GitHub, LinkedIn y correo.
- [x] No registrar información sensible.
- [ ] Revisar indexación por idioma.

## Entregables

- Configuración SEO.
- Imagen social.
- Analítica.
- Checklist de indexación.

## Criterios de aceptación

- Los enlaces compartidos muestran imagen, título y descripción correctos.
- Cada idioma tiene metadata propia.
- Administración y previews no se indexan.
- Los eventos de contacto pueden medirse sin invadir privacidad.

**Estado al 2026-09-21:** las shells estáticas localizadas de Vite + React,
las redirecciones canónicas, la 404 estática, metadata/SEO, el sitemap,
`robots.txt`, el favicon y la analítica opcional en runtime están implementados
y verificados localmente. La fase permanece abierta: el diseño editorial de la
imagen social está aprobado, pero falta integrar su JPEG final humano de
`1200 × 630`; sin ese archivo no se emite metadata social que lo referencie.
La revisión real de indexación queda para el lanzamiento (Fase 12), no se
realizó una operación de Search Console en esta fase.

**Actualización 2026-09-22 (`v0.9.0`):** la dirección visual aprobada de la
imagen social es **A — Retrato editorial**, y su archivo final sigue
**PENDING**. Luciano decidió explícitamente que esto **no bloquea** el
lanzamiento expedito: `v0.9.0` se publica **sin imagen social**. El build
detecta la ausencia del archivo y no emite `og:image` ni `twitter:image`, así
que no hay metadata que apunte a un archivo inexistente, no se usa placeholder
y no se regeneró el diseño. Los enlaces compartidos muestran título y
descripción correctos, sin imagen. La integración entrará en una release
posterior a `v0.9.0` y anterior a `v1.0.0`. Esta fase permanece **OPEN**.

---

# Fase 9 — Seguridad y endurecimiento

> **Estado al 2026-09-22: OPEN — diferida después del lanzamiento expedito `v0.9.0`.**
> Ninguna tarea de esta fase se marcó como hecha. Lo que ya existe en el código
> desde fases anteriores (rate limiting en API y descargas de CV, CORS
> restringido por origen, panel sin registro público, autenticación
> administrativa obligatoria, MySQL sin exposición, secretos fuera de Git) no
> constituye el cierre de esta fase. Sigue pendiente, entre otros: cabeceras de
> seguridad, auditoría y actualización de dependencias, política y rotación de
> credenciales, revisión de logs y el procedimiento de backup/restore probado.
>
> El gate mínimo aplicado antes de `v0.9.0` verificó ausencia de secretos,
> `APP_DEBUG` desactivado en producción, credenciales suministradas por runtime
> y administración autenticada. Eso es un subconjunto acotado, no esta fase.

## Objetivo

Preparar el sistema para exposición pública.

## Tareas

- [ ] Revisar autenticación del panel.
- [ ] Desactivar registro público.
- [ ] Rate limiting.
- [ ] CORS por dominio.
- [ ] Validación de archivos.
- [ ] Límites de tamaño.
- [ ] Sanitización.
- [ ] Cabeceras de seguridad.
- [ ] Gestión de secretos.
- [ ] Rotación de credenciales iniciales.
- [ ] Procedimiento de backup de datos de aplicación (MySQL y ambos volúmenes de media) documentado.
- [ ] Restauración de prueba en entorno local descartable.
- [ ] Actualización de dependencias.
- [ ] Auditoría de paquetes.
- [ ] Revisión de logs.
- [ ] Ocultar información interna en errores.
- [ ] Política de contraseñas.
- [ ] Protección de rutas administrativas.
- [ ] Revisión del CV público.
- [ ] Revisión de información confidencial.

> Los backups y restores reales del VPS (`/srv/backups`) pertenecen a `vps_ops_claude`; esta fase entrega y prueba localmente el procedimiento de aplicación, sin tocar el servidor.

## Entregables

- Checklist de seguridad.
- Política de secretos.
- Procedimiento de backup.
- Procedimiento de recuperación.

## Criterios de aceptación

- No hay secretos en cliente ni repositorio.
- Los archivos están restringidos.
- Los errores no filtran información interna.
- El panel no permite registro.
- Existe un backup recuperable.

---

# Fase 10 — Pruebas y control de calidad

> **Estado al 2026-09-22: OPEN — diferida después del lanzamiento expedito `v0.9.0`.**
> Ninguna tarea de esta fase se marcó como hecha. El gate mínimo previo a
> `v0.9.0` ejecutó únicamente las validaciones que ya existían en el
> repositorio: contrato de repositorio, type check, 97 pruebas de frontend,
> build de producción, verificación del build emitido, 594 pruebas de backend
> contra MySQL real, build de las imágenes productivas y un smoke funcional de
> 40 comprobaciones contra el runtime productivo local.
>
> Sigue pendiente la matriz completa: pruebas E2E, accesibilidad automática y
> manual, navegadores principales, matriz de animaciones, rotación móvil, bajo
> rendimiento y los reportes formales de esta fase.

## Objetivo

Comprobar el funcionamiento completo antes del lanzamiento.

## Frontend

- [ ] Lint.
- [ ] Type check.
- [ ] Build.
- [ ] Pruebas unitarias críticas.
- [ ] Pruebas de integración.
- [ ] Pruebas E2E.
- [ ] Idioma.
- [ ] Tema.
- [ ] Navegación.
- [ ] Contacto.
- [ ] Descarga del CV.
- [ ] Proyectos con y sin contenido.
- [ ] API caída.
- [ ] 404.
- [ ] Accesibilidad automática.
- [ ] Accesibilidad manual.
- [ ] Responsive.
- [ ] Navegadores principales.

## Backend

- [ ] Pruebas unitarias.
- [ ] Pruebas de API.
- [ ] Autenticación.
- [ ] Autorización.
- [ ] Publicación.
- [ ] Borradores.
- [ ] Traducciones.
- [ ] Archivos.
- [ ] Rate limiting.
- [ ] CORS.
- [ ] Validaciones.
- [ ] Recursos JSON.
- [ ] Migraciones desde cero.
- [ ] Seed.
- [ ] Análisis estático.
- [ ] Formato.

## Animaciones

- [ ] Mouse.
- [ ] Touchpad.
- [ ] Touch.
- [ ] Teclado.
- [ ] Movimiento reducido.
- [ ] Pestaña en segundo plano.
- [ ] Scroll rápido.
- [ ] Resize.
- [ ] Rotación móvil.
- [ ] Bajo rendimiento.
- [ ] Modo claro.
- [ ] Modo oscuro.

## Entregables

- Matriz de pruebas.
- Reporte de errores.
- Reporte de accesibilidad.
- Reporte de rendimiento.
- Evidencia de builds correctos.

## Criterios de aceptación

- No hay errores críticos.
- No hay enlaces rotos.
- No hay contenido faltante en un idioma.
- No hay fallos graves de accesibilidad.
- No hay degradación severa por animaciones.
- Frontend y backend pasan localmente todas las validaciones que CI ejecutará en Fase 11.

---

# Fase 11 — Runtime de producción, CI y release readiness

## Objetivo

Convertir `main` aprobada en una **release real, completa y desplegable**: runtime Docker productivo verificado localmente, CI verde, tag versionado, imágenes publicadas en GHCR con digests registrados y handoff listo para operaciones.

Esta es la **última fase controlada por el repositorio**. Termina al producir la release y entregar el handoff. **Nunca entra al VPS ni despliega.** El deployment real lo ejecuta después `vps_ops_claude`, de forma manual/asistida y desde un contexto operativo separado (ver `docs/DEPLOYMENT.md` y `docs/SERVER_ARCHITECTURE.md`).

> Estado al 2026-09-14: Fases 3–5 construyeron y verificaron solo el entorno de desarrollo/pruebas. No existen todavía Dockerfiles productivos, `compose.production.yaml`, workflows de GitHub Actions, remoto GitHub configurado en este workspace, tags ni imágenes GHCR. **El portfolio no está desplegado.**

Secuencia conceptual de la fase:

```text
desarrollo terminado (Fases 0–10)
  -> runtime Docker productivo independiente
  -> Dockerfiles productivos
  -> compose.production.yaml portable
  -> stack PRODUCTIVO levantado localmente
  -> migraciones / import inicial / bootstrap / healthchecks / persistencia
  -> smoke local
  -> CI verde
  -> main aprobada
  -> tag versionado (por ejemplo v1.0.0)
  -> workflow de release
  -> GitHub Release si corresponde
  -> imágenes GHCR
  -> versiones y digests registrados
  -> handoff a operaciones
  -> STOP
```

Que las imágenes compilen **no** es evidencia suficiente: el runtime productivo debe levantarse y ser usable en local antes de crear la release.

## Prerrequisitos

- Fases 9 y 10 cerradas.
- `main` en estado aprobado por decisión humana explícita.
- Las brechas editoriales abiertas de Fase 5 resueltas, o su tratamiento en la release decidido explícitamente por un humano.

> **Excepción aplicada el 2026-09-22 para `v0.9.0`.** El primer prerrequisito
> **no se cumplió**: Fases 9 y 10 siguen OPEN. Luciano autorizó explícitamente
> ejecutar esta fase igualmente para publicar el portfolio y usarlo en su CV
> (ver la excepción al inicio de este documento). Los otros dos prerrequisitos
> sí se cumplieron: `main` fue aprobada por decisión humana, y el tratamiento
> de las brechas editoriales y de la imagen social en esta release se decidió
> explícitamente (contenido importado como borrador y publicado por un humano
> desde Filament; imagen social diferida sin emitir metadata rota).
>
> Este prerrequisito **no se elimina ni se reescribe**: sigue vigente para
> `v1.0.0`.

## Tareas

### Decisiones a cerrar al iniciar la fase

No se fijan antes de inspeccionar la implementación real; se registran cuando se decidan.

- [x] Nombres definitivos de las imágenes GHCR, derivados de la arquitectura real (frontend, backend y gateway según corresponda).
- [x] Visibilidad del repositorio GitHub y de los paquetes GHCR.
- [x] Cómo accede el runtime productivo a los assets aprobados que consume `portfolio:import-initial-content` sin depender de bind mounts de desarrollo.
- [x] Almacén de caché productivo compatible con `LockProvider` (`file` o `database`; ver `docs/DEPLOYMENT.md`).
- [x] Tratamiento de `Host`, protocolo reenviado y trusted proxies para la cadena Caddy global -> gateway interno -> frontend Vite + React y Laravel, verificable en local sin configurar Cloudflare ni el Caddy global.

### GitHub y auditoría previa

- [x] Auditar el historial Git completo (no solo el working tree) buscando `.env`, claves, tokens, passwords, dumps, logs sensibles y contenido confidencial, con conclusión explícita antes de publicar.
- [x] Crear o conectar el repositorio GitHub canónico `portfolio` y subir `main`.
- [x] Documentar la protección liviana de `main` (sin force push, sin borrado accidental, checks requeridos cuando corresponda).

### Runtime productivo

- [x] Diseñar Dockerfiles específicos de producción, sin reutilizar ciegamente las imágenes de desarrollo.
- [x] Definir las imágenes frontend, backend y gateway según la arquitectura real; MySQL usa una imagen oficial fijada deliberadamente.
- [x] Crear `compose.production.yaml` portable, separado de `compose.yaml`.
- [x] Separar configuración de desarrollo y producción (sin bind mounts de código, watchers/HMR ni dependencias de desarrollo en runtime).
- [x] Conservar el gateway Caddy interno como único entrypoint del stack.
- [x] Mantener MySQL exclusivamente interno, sin puertos publicados.
- [x] Publicar únicamente el gateway, con bind/puerto configurable y default `127.0.0.1:8000`.
- [x] Definir networks y volumes propios del portfolio (datos MySQL, `api_private_media`, `api_public_media`).
- [x] Definir restart policies apropiadas.
- [x] Definir healthchecks reales por servicio.
- [x] Definir la configuración productiva sin secretos embebidos; los secretos quedan fuera de Git y fuera de las imágenes.
- [x] Materializar opcionalmente `/runtime-config.json` en el runtime productivo desde valores públicos de entorno, con `Cache-Control: no-store` tanto para su `200` como para su `404` intencional; el mismo digest de imagen debe poder reconfigurarse sin rebuild, tag ni release nuevos.
- [x] Verificar que ninguna imagen contiene `.env`, claves, tokens, passwords, secretos ni artefactos temporales innecesarios.
- [x] Verificar que ni `compose.production.yaml` ni las imágenes contienen rutas `/srv`, configuración de Cloudflare, del Caddy global ni del host.

### Verificación productiva local

- [x] Levantar localmente el runtime productivo completo con `compose.production.yaml`, sin el Compose de desarrollo.
- [x] Verificar gateway interno, frontend Vite + React, Laravel y MySQL.
- [x] Verificar la API (`/api/v1` y los endpoints públicos localizados).
- [x] Verificar `/`, `/es`, `/en` y `/admin`.
- [x] Verificar media/storage (`/storage/*`) y descargas de CV (`/cv/*`).
- [x] Ejecutar migraciones desde una base fresca.
- [x] Ejecutar el import/bootstrap inicial de contenido según su contrato.
- [ ] Ejecutar el bootstrap administrativo según el contrato vigente.
      *Parcial:* `portfolio:bootstrap-admin` se ejecuta correctamente dentro de
      la imagen productiva (arranca, adquiere su lock, consulta la base y
      renderiza sus prompts), pero completarlo requiere un TTY real, que es
      exactamente su contrato interactivo. Lo ejecuta el operador durante el
      deployment. Su lógica ya está cubierta por `BootstrapAdminTest`.
- [x] Verificar healthchecks.
- [x] Verificar persistencia tras restart y recreate de contenedores.
- [x] Confirmar que solo el gateway publica un puerto y que MySQL no es alcanzable desde el host.
- [x] Ejecutar un smoke funcional reproducible y registrar la evidencia.

### CI

- [ ] Ejecutar lint.
- [ ] Ejecutar format check cuando corresponda.
- [x] Ejecutar typecheck.
- [x] Ejecutar tests frontend.
- [x] Ejecutar tests backend contra MySQL real.
- [x] Ejecutar build frontend.
- [x] Ejecutar build/validación backend cuando aplique.
- [x] Construir los Dockerfiles productivos.
- [x] Validar `compose.production.yaml`.
- [ ] Ejecutar las auditorías de dependencias acordadas.
- [ ] Detectar secretos accidentales.
- [x] Configurar caché de dependencias donde sea segura y útil.
- [x] Confirmar que CI no accede al VPS ni contiene secretos del host, claves SSH ni credenciales de Cloudflare.

> **Pendientes reales de CI al 2026-09-22.** No se agregó tooling nuevo solo
> para completar la lista:
>
> - *Lint* y *format check*: el frontend no tiene linter configurado y no se
>   introdujo uno para esta release. El backend tiene `laravel/pint` como
>   dependencia de desarrollo, pero no hay un contrato de formato acordado
>   todavía. Ambos corresponden a Fase 10.
> - *Auditorías de dependencias*: no hay una auditoría acordada todavía;
>   pertenece a Fase 9.
> - *Detección de secretos*: CI solo comprueba que no haya archivos `.env`
>   reales versionados. No hay escaneo de secretos por contenido ni sobre el
>   historial dentro del pipeline; la auditoría de historial de esta fase se
>   hizo manualmente y su conclusión está registrada. Un escáner permanente
>   pertenece a Fase 9.

### Release

- [x] Definir el checklist de release readiness.
- [x] Documentar versionado, release notes y tags anotados sobre `main`.
- [x] Crear el workflow de release disparado por tag versionado, con permisos mínimos para publicar Packages.
- [x] Etiquetar las imágenes al menos por versión de release y por commit SHA; `latest`, si existe, es solo comodidad.
- [x] Confirmar `main` aprobada, CI verde, runtime productivo local PASS, smoke local PASS y working tree limpio antes del tag.
- [x] Crear el tag versionado (por ejemplo `v1.0.0`) sobre el commit aprobado de `main`.
- [x] Publicar GitHub Release cuando corresponda.
- [x] Publicar las imágenes productivas en GHCR.
- [x] Registrar versión, commit y digest `sha256` de cada imagen.
- [x] Confirmar que artefactos, imágenes y logs de CI no exponen secretos ni contenido confidencial.

### Contrato de rollback

- [x] Documentar cómo identificar la release anterior.
- [x] Documentar qué imágenes y digests corresponden a cada release.
- [x] Documentar cómo volver a una imagen/digest anterior sin reconstruir código.
- [x] Documentar que un rollback de aplicación no implica automáticamente rollback de base de datos.
- [x] Documentar que las migraciones destructivas requieren análisis independiente y registrar el schema asociado a cada release.

### Handoff de deployment

- [x] Actualizar `docs/DEPLOYMENT.md` con release, commit, imágenes y digests.
- [x] Documentar servicios, gateway, puertos internos, entrypoint `127.0.0.1:8000` y rutas `/`, `/es`, `/en`, `/api/*`, `/admin/*`, `/storage/*` y `/cv/*`.
- [x] Documentar volúmenes persistentes, variables requeridas y secretos necesarios sin incluir valores.
- [x] Documentar migraciones, import inicial, bootstrap administrativo, health checks y smoke checks para el deployment posterior.
- [x] Documentar qué debe respaldarse y qué servicios y puertos nunca se exponen.
- [x] Documentar qué debe aportar el override `/srv/ops/portfolio/compose.vps.yaml`, sin crearlo.
- [x] Referenciar `vps_ops_claude` como propietario del deployment real.

## Salida esperada

La fase se considera terminada con un reporte equivalente a:

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

Después de esto: **STOP**.

## Prohibido en esta fase

El agente del repositorio nunca:

- abre SSH al VPS, se conecta al VPS ni usa PuTTY u otro acceso remoto;
- hace `git pull` bajo `/srv/apps`;
- crea `/srv/apps/portfolio` ni `/srv/ops/portfolio`;
- crea overrides reales del VPS;
- edita `/srv/ingress` ni modifica el Caddy global;
- modifica UFW, Cloudflare ni DNS;
- crea secretos productivos reales ni certificados del host;
- ejecuta migraciones contra producción ni levanta contenedores en OVH;
- modifica backups reales ni ejecuta un rollback real;
- hace smoke contra producción;
- configura deployment automático mediante GitHub Actions ni runners con acceso al VPS.

## Entregables

- Dockerfiles productivos.
- `compose.production.yaml` portable.
- Evidencia del runtime productivo local y del smoke local.
- Pipeline de CI.
- Workflow de release.
- Tag de release sobre `main` y GitHub Release cuando corresponda.
- Imágenes en GHCR identificables por versión y commit, con digests registrados.
- Contrato de rollback.
- Handoff de deployment actualizado en `docs/DEPLOYMENT.md`.

## Criterios de aceptación

- El runtime productivo arranca y es usable en local desde una base fresca, con persistencia verificada.
- Cada cambio relevante pasa las validaciones automáticas acordadas.
- La release proviene de un commit aprobado de `main` y se puede identificar por tag, commit y digests.
- Producción nunca depende exclusivamente de `latest`.
- Ninguna imagen ni archivo versionado contiene secretos.
- CI y release no acceden al VPS ni requieren secretos del host o de Cloudflare.
- El handoff permite a `vps_ops_claude` desplegar exactamente esa release sin reconstruir código.
- El working tree queda limpio.
- Ninguna tarea de esta fase afirma que el portfolio ya fue desplegado.

---

# Fase 12 — Lanzamiento

## Objetivo

Publicar el portfolio y comenzar a utilizarlo en postulaciones.

El lanzamiento parte de una release **que ya existe**: tag, GitHub Release cuando corresponda e imágenes GHCR con digests, todo producido en Fase 11. El deployment al VPS lo ejecuta `vps_ops_claude` fuera del repositorio.

Esta fase **no crea tags, releases ni imágenes**. Si el lanzamiento revela un defecto que requiere cambiar código, se corrige en el repositorio y se produce una nueva release mediante el flujo de Fase 11 antes de un nuevo deployment; nunca se etiqueta a posteriori lo que ya está desplegado.

Las verificaciones contra producción de esta fase las realizan Luciano u operaciones; el agente del repositorio no se conecta al VPS ni ejecuta smoke contra producción.

## Tareas

### Prerrequisitos externos de lanzamiento

- [ ] Confirmar qué release exacta (tag, commit y digests) desplegó `vps_ops_claude`.
- [ ] Confirmar que `https://lucianogonzalez.dev` responde a través de Cloudflare.
- [ ] Confirmar que `/es`, `/en`, `/api` y `/admin` responden según el contrato.
- [ ] Confirmar que los smoke checks de producción pasaron.
- [ ] Confirmar que existe un backup inicial gestionado y verificado por operaciones.

### Activación operativa de analítica e indexación

- [ ] Confirmar que operaciones desplegó Umami de forma independiente al runtime del portfolio, con imagen oficial fijada, PostgreSQL `12.14` o superior, persistencia y backups separados.
- [ ] Confirmar que operaciones administra `DATABASE_URL`, `APP_SECRET`, rotación de la credencial inicial de administrador, logging y retención; esos secretos no pertenecen al repositorio.
- [ ] Confirmar que operaciones creó el sitio `lucianogonzalez.dev` en Umami y entregó el Website ID y la URL del tracker al runtime de la release ya desplegada.
- [ ] Confirmar que operaciones reconfiguró solo el runtime necesario con la misma imagen y digest, preservando `Cache-Control: no-store` para la configuración presente o ausente.
- [ ] Verificar mediante ingestión real de Umami los pageviews de `/` y `/en`, sin variantes de query/hash ni duplicados, y los cuatro eventos sin propiedades.
- [ ] Completar en Search Console la verificación del dominio, envío del sitemap, inspección de URLs y observación de indexación. Tokens, cambios DNS y el envío real no pertenecen a Fase 8.

### Revisión y comunicación

- [ ] Revisión final de contenido.
- [ ] Revisión final en inglés.
- [ ] Revisión de fotografía.
- [ ] Revisión de CV.
- [ ] Revisión de enlaces.
- [ ] Revisión de confidencialidad.
- [ ] Revisión móvil.
- [ ] Lighthouse final.
- [ ] Core Web Vitals.
- [ ] Prueba desde otra red.
- [ ] Prueba sin sesión de administrador.
- [ ] Aprobar publicación después de recibir el cierre de deployment exitoso de operaciones.
- [ ] Actualizar LinkedIn.
- [ ] Actualizar GitHub.
- [ ] Agregar enlace al CV.
- [ ] Preparar mensaje de presentación para postulaciones.

## Entregables

- Portfolio público sirviendo la release registrada en Fase 11.
- Confirmación externa del deployment, smoke checks de producción y backup inicial.
- Enlaces profesionales actualizados.

## Criterios de aceptación

- El sitio puede enviarse a un reclutador sin explicación adicional.
- La propuesta profesional se entiende rápido.
- Los cuatro contactos funcionan.
- El contenido real tiene prioridad sobre los efectos.
- La experiencia es estable en móvil y escritorio.
- El deployment y el backup fueron ejecutados y confirmados por `vps_ops_claude`, no por el roadmap de desarrollo.
- La versión pública corresponde a una release preexistente de Fase 11; no se creó ningún tag, release ni imagen después del deployment.

---

# Fase 13 — Postlanzamiento

## Objetivo

Mejorar el portfolio mediante evidencia y no por impulso.

## Primera semana

- [ ] Revisar errores.
- [ ] Revisar los logs de aplicación disponibles mediante el handoff operativo, sin asumir administración del host.
- [ ] Revisar analítica.
- [ ] Revisar con datos reales los pageviews y los cuatro eventos de Umami, incluidos duplicados, propiedades y comportamiento de privacidad.
- [ ] Verificar indexación a partir de la observación posterior al lanzamiento en Search Console.
- [ ] Verificar descargas del CV.
- [ ] Corregir enlaces.
- [ ] Consultar a dos personas técnicas.
- [ ] Consultar a una persona no técnica.

## Primer mes

- [ ] Publicar el primer proyecto nuevo fuerte.
- [ ] Revisar qué secciones reciben interacción.
- [ ] Mejorar textos con feedback.
- [ ] Ajustar rendimiento.
- [ ] Revisar dependencias.
- [ ] Confirmar con operaciones el estado de backups y restores del portfolio.

## Mejoras futuras posibles

- [ ] Página individual de proyecto, solo si las tarjetas resultan insuficientes.
- [ ] Blog técnico.
- [ ] RSS.
- [ ] Página de notas técnicas.
- [ ] Open source de componentes.
- [ ] Automatización de proyectos desde GitHub.
- [ ] Observabilidad avanzada.
- [ ] Panel de métricas.
- [ ] Migración de medios a almacenamiento dedicado.
- [ ] Nuevos idiomas, solo si existe una necesidad real.

---

# Hitos

## Hito A — Base definida

Incluye fases 0 a 2.

Resultado:

- Mensaje profesional.
- Contenido.
- Arquitectura.
- Diseño aprobado.

## Hito B — Producto funcional

Incluye fases 3 a 5.

Resultado:

- Docker.
- Laravel.
- Filament.
- API.
- Next.js.
- Español e inglés.
- Tema claro y oscuro.
- Sitio sin animación avanzada.

## Hito C — Experiencia cinematográfica

Incluye fase 6.

Resultado:

- Motion.
- GSAP.
- ScrollTrigger.
- Campo de nodos.
- Movimiento reducido.
- Rendimiento validado.

## Hito D — Portfolio demostrable

Incluye fases 7 y 8.

Resultado:

- Proyectos reales publicados y verificados.
- Material visual.
- SEO.
- Analítica.

## Hito E — Release, deployment externo y lanzamiento

Incluye fases 9 a 12.

Resultado:

- Seguridad.
- Pruebas.
- Runtime productivo verificado localmente.
- CI verde.
- Release versionada con imágenes GHCR y digests registrados (fin de la responsabilidad del repositorio).
- Handoff entregado a `vps_ops_claude`.
- Deployment en el VPS confirmado externamente.
- Lanzamiento.

---

# Prioridades

## Prioridad 1

- Mensaje profesional.
- Contenido real.
- Arquitectura.
- Accesibilidad.
- Rendimiento.
- CMS.
- Backend Laravel.
- Docker.
- Bilingüe.
- Responsive.

## Prioridad 2

- Animaciones.
- Campo de nodos.
- Transiciones cinematográficas.
- SEO avanzado.
- Analítica.

## Prioridad 3

- Blog.
- Páginas individuales.
- Automatizaciones externas.
- Funciones adicionales.

No avanzar con prioridades 3 mientras existan problemas en prioridades 1.

---

# Riesgos principales

## Exceso de animación

Riesgo:

- El portfolio puede parecer frontend-first o perder legibilidad.

Mitigación:

- Aprobar diseño estático primero.
- Limitar secuencias complejas.
- Medir rendimiento.
- Eliminar cualquier efecto que compita con el contenido.

## Complejidad de dos aplicaciones

Riesgo:

- Next.js y Laravel aumentan despliegue y mantenimiento.

Mitigación:

- Separación clara.
- Docker.
- Contrato de API.
- CI.
- Documentación.
- Evitar duplicación.

## Administración demasiado grande

Riesgo:

- Invertir más tiempo en el CMS que en el portfolio.

Mitigación:

- Usar Filament.
- Implementar solo CRUD necesario.
- Un único administrador.
- No personalizar en exceso.

## Falta de proyectos listos

Riesgo:

- Lanzamiento sin evidencia pública suficiente.

Mitigación:

- Diseñar estado sin proyectos.
- Mostrar experiencia profesional anonimizada.
- Publicar proyectos solo cuando sean fuertes.
- Desarrollar proyectos en paralelo.

## Información confidencial

Riesgo:

- Exponer detalles del trabajo real.

Mitigación:

- Matriz de confidencialidad.
- Anonimización.
- Revisión manual antes de publicar.
- No inventar ni revelar nombres o datos internos.

## VPS como punto único de producción

Riesgo:

- Una falla del proveedor, del disco, de Linux, de Docker o del Caddy global puede dejar fuera de línea todos los proyectos del VPS.

Mitigación:

- Entregar health checks, restart policies y requisitos de persistencia claros desde el repositorio.
- Entregar releases inmutables (tags y digests) que permitan redeployar o volver atrás sin reconstruir código.
- Delegar reboot recovery, Caddy global, UFW, backups, restores, monitoreo, recuperación y seguridad del host a `vps_ops_claude`.
- Confirmar esas capacidades como prerrequisitos externos de lanzamiento.
- Evaluar snapshots y redundancia desde operaciones si el proyecto lo justifica.

## Colisión entre proyectos

Riesgo:

- Dos proyectos pueden intentar usar el mismo puerto, volumen, red o recurso.

Mitigación:

- Publicar con claridad el entrypoint reservado `127.0.0.1:8000`, las redes y los volúmenes propios del portfolio.
- Delegar el registro central de puertos y la coordinación multiproyecto a `vps_ops_claude`.
- Nombres/prefijos por proyecto.
- Redes independientes.
- Volúmenes independientes.
- Un directorio/repositorio por proyecto.

## Automatización insegura del deploy

Riesgo:

- Un runner, workflow o credencial SSH mal configurado puede dar acceso al VPS y a todos sus proyectos.

Mitigación:

- CI y release separados del deployment.
- GitHub Actions nunca despliega ni se conecta al VPS; no existen runners con acceso al servidor.
- CI sin secretos del host, claves SSH ni credenciales de Cloudflare.
- Entregar la release (tag, digests y handoff) a `vps_ops_claude`, que despliega de forma manual/asistida.
- Delegar SSH, firewall, automatización y controles de acceso del host a `vps_ops_claude`.

---

# Backlog inicial sugerido

1. Aprobar arquitectura.
2. Aprobar contenido.
3. Crear wireframes.
4. Crear prototipo visual estático.
5. Inicializar el workspace de aplicaciones del monorepositorio existente.
6. Crear Docker.
7. Crear Laravel y Filament.
8. Modelar contenido.
9. Crear API.
10. Crear Next.js.
11. Integrar idiomas.
12. Integrar temas.
13. Construir secciones.
14. Conectar CMS.
15. Agregar Motion.
16. Agregar GSAP.
17. Crear campo de nodos.
18. Medir y optimizar.
19. Publicar y verificar proyectos reales suficientes para demostrar el perfil backend.
20. Implementar SEO.
21. Implementar pruebas.
22. Crear el runtime productivo y verificarlo localmente.
23. Configurar CI y el workflow de release.
24. Crear la release: tag, imágenes GHCR, digests y handoff. STOP del repositorio.
25. Deployment manual/asistido por `vps_ops_claude`, fuera del repositorio.
26. Confirmar externamente deployment, smoke checks y backup inicial.
27. Lanzar.
