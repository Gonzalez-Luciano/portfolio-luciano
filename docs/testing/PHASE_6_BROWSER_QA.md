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

## Blocked rows (2026-09-19)

The dev stack currently has 0 work cases, 0 project images, 0 work principles, 0 CV documents and no published projects/experiences/education/languages. Publishing content is the user's decision and was explicitly out of scope for this task (no seeding, no publishing). The following rows cannot be exercised until that content exists and is published:

- **Row 1** — needs a published Profile with a photo, name and headline.
- **Row 2, 3, 4** — need the scroll scene to render past the loading/error state, which requires published `profile` and `site` (structural content); the video/canvas scrub itself does not depend on regional content, but the scene text does.
- **Row 5** — same as above; needs published `profile`/`site`.
- **Row 6, 7, 8** — need at least one published section (Experience, Projects, Stack, About or Contact) to scroll into and observe the nav/seam/active-section behaviour past the scene.
- **Row 9** — needs at least one published Work Case linked to a published Experience.
- **Row 10, 11** — need at least one published Project with a gallery of project images (row 10 needs 5+ images on at least one project to exercise the "+N" tile and the viewer; row 11 needs more than 3 projects in at least one group — client or personal — to exercise "show more").
- **Row 12, 13, 14, 16** — exercise the nav, menu, language and keyboard behaviour that exist regardless of content, but a realistic pass needs at least one published section so the page is not just the scene; these are not hard-blocked but are best done together with the others once content exists.
- **Row 15** — same as 2/3/4/5: reduced motion needs the scene to actually render frames, which needs `profile`/`site` published.
- **Row 17** — needs published Projects so the regional failure/retry is visible in the UI (currently `projects` returns an empty/unpublished result regardless of API health).

In short: nothing in this checklist can be meaningfully exercised until `profile`, `site` are published, and ideally at least one published Experience with a Work Case, one published Project per group with 5+ gallery images and more than 3 items in a group, and Stack/About/Contact content. That publication step was intentionally not performed by this task.
