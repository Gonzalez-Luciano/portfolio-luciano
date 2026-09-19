# Fase 6 — QA en navegador

Spec: `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md` (sección 12). Stack aislado `portfolio-phase6` en http://127.0.0.1:8016 con el contenido de Fase 6 publicado. Ventana visible (una pestaña oculta pausa `requestAnimationFrame` y el video).

| # | Verificación | 1440 px | 375 px | Resultado |
|---|---|---|---|---|
| 1 | Foto, nombre y headline en el beat 1; avatar de 28 px junto al nombre en la barra al salir de la escena | | | |
| 2 | Scrub del video sin saltos en ambos sentidos; el canvas reemplaza al video (mezcla de cuadros suave) | | | |
| 3 | Tramo p 0,55–0,75 sin velo blanco (corrección de contraste visible) | | | |
| 4 | Paisaje se desvanece y aparece la constelación; líneas punteadas al final | | | |
| 5 | Textos siempre en la columna izquierda; en móvil el video encuadra el árbol | | | |
| 6 | Barra oscura hasta p 0,70 y clara con velo desde ahí; sólida con línea inferior en las secciones | | | |
| 7 | Empalme escena → secciones en tema claro (degradé) y oscuro (continuo) | | | |
| 8 | Sección activa con número y subrayado al recorrer las secciones | | | |
| 9 | Acordeones de casos: abrir varios, `aria-expanded` correcto | | | |
| 10 | Galería: miniaturas, "+N", visor con anterior/siguiente, flechas y Escape; el foco vuelve al botón | | | |
| 11 | "Ver más proyectos" revela el resto y mueve el foco al primero revelado | | | |
| 12 | Menú móvil: índice numerado, idioma, tema y CV; foco atrapado, Escape cierra, scroll bloqueado | | | |
| 13 | ES/EN conserva el ancla; cambio de tema persiste al recargar y no parpadea | | | |
| 14 | Solo teclado: saltar al contenido, navegación, acordeones, galería y contacto | | | |
| 15 | Movimiento reducido: sin banco de frames, sin desplazamiento del fondo ni stagger | | | |
| 16 | Zoom al 200 %: nada se corta ni se superpone | | | |
| 17 | Error regional: con `projects` fallando, solo Proyectos muestra error y Reintentar | | | |

Run the checks with the claude-in-chrome browser tools in a visible window (resize to 1440×900 and 375×812; emulate reduced motion through the OS or DevTools setting; for row 17 stop the API briefly with `docker compose -p portfolio-phase6 stop api` after the page loaded structural content, click Retry after `docker compose -p portfolio-phase6 start api`). Write `OK` or the observed problem in each result cell. Fix any problem in its owning component with a failing test first when the logic lives in `web/src/lib/`, re-run Step 5, and only then tick the QA item in `ROADMAP.md`.

Note for row 12: with Experience hidden (see "Blocked rows" below), the mobile index numbers its visible sections by their fixed position in `SECTION_ORDER` (`web/src/lib/sections.ts`), not by a recount — so it currently shows Proyectos 02, Stack 03, Sobre mí 04 and Contacto 05, gapped rather than renumbered from 01. That gap is expected, not a bug; the row still passes if the index, focus trap, Escape and scroll lock otherwise work.

## Blocked rows (2026-09-19, corrected 2026-09-19)

Live read-only `GET` checks against the dev stack at `http://127.0.0.1:8016/api/v1/es/*` (no content was published, seeded or modified) show:

- `/profile`: fully populated, including a real photo (`url` + `alt`), name, headline, statement, closing, cta.
- `/site`: `technology_groups` has 4 entries, `professional_links` has 3; `expertise_areas`, `work_principles`, `education` and `languages` are empty; `cv` is `null`.
- `/technologies`: 9 items.
- `/experiences`, `/work-cases`, `/projects`: all `[]` (nothing published).

Per `visibleSections` (`web/src/lib/sections.ts:25-55`): Projects always renders (empty state shows `site.projects_empty_message`); Stack renders because `technologies` is non-empty; About renders because `profile.location` and `profile.work_modes` are non-empty (`hasAboutContent`); Contact renders because `professional_links` is non-empty (`hasContactContent`); Experience is the only section hidden, because both `experiences` and `work-cases` are empty.

That means **almost everything in this checklist is executable right now**, against the current dev stack, with no publishing needed:

- **Rows 1–8** — the scene (photo, name, headline, video scrub, contrast correction, backdrop, left column, nav colour/veil) needs only `profile`/`site`, which are already populated; rows 6–8 need at least one non-scene section to scroll into, and Projects/Stack/About/Contact already render.
- **Rows 12–16** — mobile menu, ES/EN + theme persistence, keyboard-only navigation, reduced motion and 200% zoom all exercise nav/menu/scene/section chrome that exists today (see the row-12 note above for the expected gapped numbering).
- **Row 17 is NOT blocked.** `ProjectsSection` (`web/src/components/ProjectsSection.tsx:59-68`) renders `RegionStatus` (`role="alert"`, Retry button) whenever `projects.status !== 'ready'`, regardless of whether `projects` is currently empty. Stopping `api` and clicking Retry after restarting it exercises exactly the empty-vs-error distinction the row tests, with zero published projects required.

Genuinely blocked — need content that does not exist in the dev stack, and publishing is the user's decision, out of scope for this task:

- **Row 9** — needs a published Experience with at least one linked, published Work Case (`experience_key`), so an accordion exists to open.
- **Row 10** — needs a published Project with 5 or more gallery images, to exercise the "+N" tile and the anterior/siguiente viewer (`thumbnailSlots`, `web/src/lib/gallery.ts`).
- **Row 11** — needs more than 3 published Projects in at least one group (client or personal) to exercise "Ver más proyectos" (`PROJECT_PREVIEW_LIMIT`, `web/src/lib/grouping.ts`).
