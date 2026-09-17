# Fase 6 — Tokens de diseño

Fuente: `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md` (sección 8). Implementación: `web/src/index.css` (variables CSS) y `web/tailwind.config.js` (nombres de Tailwind).

## Paleta Terracota

| Token CSS | Tailwind | Claro | Oscuro |
|---|---|---|---|
| `--color-canvas` | `canvas` | `#F5EFE6` | `#1A1411` |
| `--color-surface` | `surface` | `#EADFD1` | `#281F1A` |
| `--color-text` | `ink` | `#2D251B` | `#F3E9DD` |
| `--color-text-muted` | `muted` | `#665244` | `#C7B3A1` |
| `--color-accent` | `accent` | `#9A4E2A` | `#E08E5E` |
| `--color-border` | `line` | `#86705E` | `#8C7462` |
| `--color-node` | `node` | `#9A4E2A` | `#E38B50` |

Contraste medido (claro / oscuro): texto sobre fondo 13,2:1 / 15,2:1; secundario sobre superficie 5,6:1 / 8,0:1; acento sobre superficie 4,6:1 / 6,3:1; borde sobre superficie 3,6:1 / 3,7:1.

El tema se fija con `data-theme` en `<html>` antes del primer pintado (script de `web/index.html`), a partir de la preferencia guardada en `localStorage` (`portfolio-theme`) o de `prefers-color-scheme`.

## Escena (no depende del tema)

| Uso | Tailwind | Color |
|---|---|---|
| Texto beats 1–2 | `scene-ink` | `#2D251B` |
| Acento beats 1–2 | `scene-accent` | `#9A4E2A` |
| Cierre de la frase | `scene-muted` | `#665244` |
| Texto beat 3 | `scene-light` | `#F3E9DD` |
| Acento beat 3 | `scene-glow` | `#E08E5E` |
| Estrellas y líneas | `scene-node` | `#E38B50` |
| Velos | `scene-veil` | `#1A1411` |

## Tipografía

| Rol | Familia | Pesos | Paquete |
|---|---|---|---|
| Display (`font-display`) | Fraunces Variable | 300, cursiva para énfasis | `@fontsource-variable/fraunces` |
| Texto (`font-sans`) | Instrument Sans | 400, 600 | `@fontsource/instrument-sans` (latin) |
| Etiquetas (`font-mono`, clase `.label`) | IBM Plex Mono | 400, 500, mayúsculas con tracking | `@fontsource/ibm-plex-mono` (latin) |

`@fontsource-variable/fraunces` 5.3.0 solo publica los archivos `wght.css` / `wght-italic.css`, que cubren todos los subsets vía `unicode-range`; el navegador solo descarga el subset latino que usa esta página.

## Base

Espaciado base 4 px, anillo de foco de 3 px en acento con 2 px de separación, objetivos táctiles de 44 × 44 px y ancho máximo de contenido de 90rem (`max-w-content`).
