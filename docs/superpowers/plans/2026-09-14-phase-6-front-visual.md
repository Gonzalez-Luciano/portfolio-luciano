# Fase 6 — Front y sistema visual: implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the Vite scroll scene in `web/` into the complete bilingual portfolio — redesigned scene, navigation, experience timeline, project dossiers with galleries, stack, about and contact — in the approved Terracota light and dark themes.

**Architecture:** A single-page React 18 app. Pure, unit-tested modules in `web/src/lib/` own every decision (API validation, grouping, formatting, section availability, scene timeline, frame blending, theme resolution); React components in `web/src/components/` only render their results. `useVideoScrub` keeps its lerp/frame-bank/LRU/watchdog logic and gains native canvas sizing and two-frame blending. Profile and Site load as structural content; the four collections load as independent regions with their own retry.

**Tech Stack:** Vite 8, React 18, TypeScript 5.9 (strict), Tailwind CSS 3, lucide-react 1.46, mp4box 0.5, Vitest 4 (node environment), Fontsource (Fraunces Variable, Instrument Sans, IBM Plex Mono), ffmpeg 7.1 in a one-off Docker container.

**Spec:** `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md` (sections 3, 6, 7, 8, 11, 12, 13). API contract: `docs/api/PUBLIC_API_V1.md` as extended by `docs/superpowers/plans/2026-09-14-phase-6-cms-api.md`, **which must be fully implemented before this plan starts** (the validators here match that contract).

## Global Constraints

- Work only in `.worktrees/phase-6-cinematic-scroll` on branch `feat/phase-6-cinematic-scroll`, with the isolated stack `-p portfolio-phase6` (`GATEWAY_PORT=8016`, http://127.0.0.1:8016). Never touch the main `portfolio` stack, the VPS or production.
- Never name the site used as a design reference, nor its author, in any file, comment, doc, test, or commit.
- Professional copy never lives in front code: `web/src/content.ts` holds interface strings only; everything else comes from the API.
- Keep the scroll logic: 500vh track, sticky scene, beat opacity curves and stagger (`section-opacity.ts`), `LERP_TAU = 8`, `SNAP = 0.002`, `LRU_MAX = 24`, `LEAD = 24`, `WATCHDOG = 60000`, software retry, `currentTime` fallback. No scroll-jacking.
- Theme tokens (light / dark): canvas `#F5EFE6` / `#1A1411`; surface `#EADFD1` / `#281F1A`; text `#2D251B` / `#F3E9DD`; muted `#665244` / `#C7B3A1`; accent `#9A4E2A` / `#E08E5E`; border `#86705E` / `#8C7462`; node `#9A4E2A` / `#E38B50`.
- Scene colors are theme-independent: beats 1–2 text `#2D251B`, accent `#9A4E2A`, tail `#665244`; beat 3 text `#F3E9DD`, accent `#E08E5E`; veil `#1A1411`; backdrop landscape `#665244` at 24 %, sun `#9A4E2A` at 22 %, stars `#E38B50` core 75 % / halo 32 %, dotted links `#E38B50` 20 %.
- Scene timing: correction filter triangular over p 0.55–0.75, peak 0.65 `contrast(1.35) saturate(1.25) brightness(.94)`; column veil 0→45 % at 0.66–0.70, 45 % to 0.78, 45→0 % at 0.78–0.84; nav veil absent before 0.70, 45 % from 0.70 (150 ms fade) to 0.78, 45→0 % at 0.78–0.84; `NAV_LIGHT_THRESHOLD = 0.70`; landscape fades 0.50–0.75; stars appear 0.50–0.75; dotted links 0.75–0.90.
- Typography: Fraunces 300 (italic for emphasis) for display; Instrument Sans 400/600 for text; IBM Plex Mono 400/500 uppercase with tracking for labels. Self-hosted, no external font request.
- Accessibility: H1 in the scene, H2 per section, H3 organization/project, H4 role/case; focus ring 3 px accent with 2 px offset; 44×44 px minimum targets; everything keyboard-operable and usable at 200 % zoom; the navigation avatar is decorative (`alt=""`); hidden beats are `inert` and all scene copy exists in an sr-only block.
- Commits use Conventional Commits with no attribution lines.

## Commands (run from the worktree root)

| Purpose | Command |
|---|---|
| Unit tests (one file) | `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run <path relative to web/>` |
| Full front check | `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'` |
| Add dependencies | `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm add <packages>` |
| Dev stack | `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 up -d` then open http://127.0.0.1:8016 |
| Repository validator | `node infra/validation/validate-repository.mjs` |

The Bash tool in this repository refuses `$VAR` expansions and loops inside `sh -c`; keep container commands literal. Prefix Docker commands that pass container paths with `MSYS_NO_PATHCONV=1`.

## File map

| Responsibility | Files (under `web/`) |
|---|---|
| Theme and tokens | `index.html`, `tailwind.config.js`, `src/index.css`, `src/main.tsx`, `src/lib/theme.ts`, `src/hooks/useTheme.ts` |
| API contract | `src/lib/api.ts` |
| Content shaping | `src/lib/grouping.ts`, `src/lib/format.ts`, `src/lib/sections.ts`, `src/lib/scene.ts`, `src/content.ts` |
| Scene math | `src/lib/section-opacity.ts`, `src/lib/scene-timeline.ts`, `src/lib/scrub-math.ts`, `src/lib/constellation.ts`, `src/useVideoScrub.ts` |
| Scene UI | `src/components/ScrollScene.tsx`, `src/components/SceneBackdrop.tsx`, `src/components/Stagger.tsx` |
| Navigation | `src/components/SiteNav.tsx`, `src/components/ThemeToggle.tsx`, `src/components/LanguageSwitch.tsx`, `src/hooks/useActiveSection.ts` |
| Sections | `src/components/DawnTransition.tsx`, `src/components/Section.tsx`, `src/components/RegionStatus.tsx`, `src/components/Accordion.tsx`, `src/components/TechnologyChips.tsx`, `src/components/ExperienceSection.tsx`, `src/components/StackSection.tsx`, `src/components/ProjectsSection.tsx`, `src/components/ProjectDossier.tsx`, `src/components/ProjectGallery.tsx`, `src/components/AboutSection.tsx`, `src/components/ContactSection.tsx` |
| Composition | `src/App.tsx`, `src/hooks/useRegion.ts`, `src/hooks/usePrefersReducedMotion.ts` |
| Media | `public/media/scroll/ink-tree-network-v1.mp4`, `public/media/scroll/ink-tree-network-v1-poster.webp` |
| Docs | `docs/design/phase-6/DESIGN_TOKENS.md`, `docs/ARCHITECTURE.md`, `ROADMAP.md`, `README.md`, `docs/content/ASSET_INVENTORY.md`, `docs/testing/PHASE_6_BROWSER_QA.md` |

Unit tests live next to each `src/lib/*.ts` module as `*.test.ts` (Vitest runs in the node environment, so components are verified by typecheck, build and the browser QA of Task 11).

---

### Task 1: Theme tokens, self-hosted fonts and pre-paint theme

**Files:**
- Modify: `web/package.json`, `web/pnpm-lock.yaml` (through `pnpm add`)
- Modify: `web/index.html`, `web/tailwind.config.js`, `web/src/index.css`, `web/src/main.tsx`
- Create: `web/src/lib/theme.ts`, `web/src/lib/theme.test.ts`, `web/src/hooks/useTheme.ts`
- Create: `docs/design/phase-6/DESIGN_TOKENS.md`

**Interfaces:**
- Produces: `type Theme = 'light' | 'dark'`; `THEME_STORAGE_KEY = 'portfolio-theme'`; `resolveTheme(stored: string | null, prefersDark: boolean): Theme`; `nextTheme(theme: Theme): Theme`; `useTheme(): { theme: Theme; toggle: () => void }`.
- Tailwind colors: `canvas`, `surface`, `ink`, `muted`, `accent`, `line`, `node` (theme tokens with alpha support) and `scene-ink`, `scene-accent`, `scene-muted`, `scene-light`, `scene-glow`, `scene-node`, `scene-veil` (fixed). Font families: `font-display`, `font-sans`, `font-mono`. Max width `max-w-content` (90rem).

- [ ] **Step 1: Write the failing test**

`web/src/lib/theme.test.ts`:

```ts
import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import { THEME_STORAGE_KEY, nextTheme, resolveTheme } from '@/lib/theme'

describe('resolveTheme', () => {
  it('uses a valid stored choice over the system preference', () => {
    expect(resolveTheme('dark', false)).toBe('dark')
    expect(resolveTheme('light', true)).toBe('light')
  })

  it('falls back to the system preference for a missing or invalid choice', () => {
    expect(resolveTheme(null, true)).toBe('dark')
    expect(resolveTheme(null, false)).toBe('light')
    expect(resolveTheme('sepia', true)).toBe('dark')
  })

  it('toggles between the two themes', () => {
    expect(nextTheme('light')).toBe('dark')
    expect(nextTheme('dark')).toBe('light')
  })
})

describe('index.html pre-paint script', () => {
  const html = readFileSync(new URL('../../index.html', import.meta.url), 'utf8')

  it('sets data-theme with the same storage key before the app loads', () => {
    expect(html).toContain(`'${THEME_STORAGE_KEY}'`)
    expect(html).toContain("setAttribute('data-theme'")
    expect(html.indexOf('data-theme')).toBeLessThan(html.indexOf('/src/main.tsx'))
  })

  it('requests no external font stylesheet', () => {
    expect(html).not.toMatch(/<link[^>]+stylesheet/)
  })
})
```

- [ ] **Step 2: Run it to verify it fails**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/lib/theme.test.ts`
Expected: FAIL — `Failed to resolve import "@/lib/theme"`.

- [ ] **Step 3: Implement the theme helpers and the pre-paint script**

`web/src/lib/theme.ts`:

```ts
export type Theme = 'light' | 'dark'

/** Also hard-coded in the pre-paint script of index.html; theme.test.ts keeps both in sync. */
export const THEME_STORAGE_KEY = 'portfolio-theme'

/** An explicit stored choice wins; otherwise the operating system preference decides. */
export function resolveTheme(stored: string | null, prefersDark: boolean): Theme {
  if (stored === 'light' || stored === 'dark') return stored
  return prefersDark ? 'dark' : 'light'
}

export function nextTheme(theme: Theme): Theme {
  return theme === 'dark' ? 'light' : 'dark'
}
```

Replace `web/index.html` with:

```html
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light dark" />
    <title>Portfolio</title>
    <script>
      // Applies the saved or system theme before the first paint to avoid a flash.
      ;(function () {
        var prefersDark = false
        try {
          prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        } catch (error) {}
        var stored = null
        try {
          stored = window.localStorage.getItem('portfolio-theme')
        } catch (error) {}
        var theme = stored === 'light' || stored === 'dark' ? stored : prefersDark ? 'dark' : 'light'
        document.documentElement.setAttribute('data-theme', theme)
      })()
    </script>
  </head>
  <body>
    <div id="root"></div>
    <script type="module" src="/src/main.tsx"></script>
  </body>
</html>
```

`web/src/hooks/useTheme.ts`:

```ts
import { useCallback, useState } from 'react'
import { THEME_STORAGE_KEY, nextTheme, resolveTheme, type Theme } from '@/lib/theme'

function documentTheme(): Theme {
  return resolveTheme(document.documentElement.getAttribute('data-theme'), false)
}

/** Reads the theme set by the pre-paint script and persists explicit changes. */
export function useTheme(): { theme: Theme; toggle: () => void } {
  const [theme, setTheme] = useState<Theme>(documentTheme)

  const toggle = useCallback(() => {
    const next = nextTheme(documentTheme())
    document.documentElement.setAttribute('data-theme', next)
    try {
      window.localStorage.setItem(THEME_STORAGE_KEY, next)
    } catch {
      // Storage can be unavailable (private mode); the choice then lasts for this page view.
    }
    setTheme(next)
  }, [])

  return { theme, toggle }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/lib/theme.test.ts`
Expected: PASS (5 tests).

- [ ] **Step 5: Install the fonts**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm add @fontsource-variable/fraunces@5.3.0 @fontsource/instrument-sans@5.3.0 @fontsource/ibm-plex-mono@5.3.0`
Expected: the three packages appear under `dependencies` in `web/package.json` and `web/pnpm-lock.yaml` changes. If pnpm refuses a package because of its release-age policy, stop and report it; do not add exclusions on your own.

- [ ] **Step 6: Tokens, Tailwind and font imports**

Replace `web/tailwind.config.js` with:

```js
/** Theme tokens are RGB channel triplets defined in src/index.css so Tailwind alpha modifiers work. */
const token = (name) => `rgb(var(--color-${name}) / <alpha-value>)`

/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        canvas: token('canvas'),
        surface: token('surface'),
        ink: token('text'),
        muted: token('text-muted'),
        accent: token('accent'),
        line: token('border'),
        node: token('node'),
        // The scroll scene tells day and night itself, so its colors ignore the theme.
        scene: {
          ink: '#2D251B',
          accent: '#9A4E2A',
          muted: '#665244',
          light: '#F3E9DD',
          glow: '#E08E5E',
          node: '#E38B50',
          veil: '#1A1411',
        },
      },
      fontFamily: {
        display: ['"Fraunces Variable"', 'Georgia', 'serif'],
        sans: ['"Instrument Sans"', 'system-ui', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
      },
      maxWidth: {
        content: '90rem',
      },
    },
  },
  plugins: [],
}
```

Replace `web/src/index.css` with:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

:root {
  --color-canvas: 245 239 230;
  --color-surface: 234 223 209;
  --color-text: 45 37 27;
  --color-text-muted: 102 82 68;
  --color-accent: 154 78 42;
  --color-border: 134 112 94;
  --color-node: 154 78 42;
  color-scheme: light;
}

[data-theme='dark'] {
  --color-canvas: 26 20 17;
  --color-surface: 40 31 26;
  --color-text: 243 233 221;
  --color-text-muted: 199 179 161;
  --color-accent: 224 142 94;
  --color-border: 140 116 98;
  --color-node: 227 139 80;
  color-scheme: dark;
}

html {
  scroll-behavior: smooth;
}

body {
  @apply bg-canvas font-sans text-ink antialiased;
  overflow-x: hidden;
}

:focus-visible {
  outline: 3px solid rgb(var(--color-accent));
  outline-offset: 2px;
}

.label {
  @apply font-mono text-xs font-medium uppercase tracking-[0.18em];
}

@media (prefers-reduced-motion: reduce) {
  html {
    scroll-behavior: auto;
  }

  *,
  *::before,
  *::after {
    transition-duration: 0.01ms !important;
    transition-delay: 0ms !important;
  }
}
```

Replace `web/src/main.tsx` with:

```tsx
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import '@fontsource-variable/fraunces/wght.css'
import '@fontsource-variable/fraunces/wght-italic.css'
import '@fontsource/instrument-sans/latin-400.css'
import '@fontsource/instrument-sans/latin-600.css'
import '@fontsource/ibm-plex-mono/latin-400.css'
import '@fontsource/ibm-plex-mono/latin-500.css'
import App from '@/App'
import '@/index.css'

const root = document.getElementById('root')

if (root) {
  createRoot(root).render(
    <StrictMode>
      <App />
    </StrictMode>,
  )
}
```

- [ ] **Step 7: Record the tokens**

Create `docs/design/phase-6/DESIGN_TOKENS.md`:

```markdown
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

## Base

Espaciado base 4 px, anillo de foco de 3 px en acento con 2 px de separación, objetivos táctiles de 44 × 44 px y ancho máximo de contenido de 90rem (`max-w-content`).
```

- [ ] **Step 8: Run the full front check and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'`
Expected: typecheck clean, all tests pass, build succeeds and `dist/assets` contains `.woff2` files.

```bash
git add web/package.json web/pnpm-lock.yaml web/index.html web/tailwind.config.js web/src/index.css web/src/main.tsx web/src/lib/theme.ts web/src/lib/theme.test.ts web/src/hooks/useTheme.ts docs/design/phase-6/DESIGN_TOKENS.md
git commit -m "feat(web): add terracotta theme tokens, self-hosted fonts and pre-paint theme"
```

---

### Task 2: All-intra scroll video and poster

The current file is not published anywhere yet, so it is re-encoded in place under its `-v1` name; any later regeneration ships as `-v2`.

**Files:**
- Modify: `web/public/media/scroll/ink-tree-network-v1.mp4`
- Create: `web/public/media/scroll/ink-tree-network-v1-poster.webp`
- Create test: `web/src/media.test.ts`

**Interfaces:**
- Produces: `/media/scroll/ink-tree-network-v1.mp4` (H.264, every frame a key frame, 848×480, 81 frames, no audio, `+faststart`) and `/media/scroll/ink-tree-network-v1-poster.webp` (frame 0). `SCROLL_VIDEO_SRC` and `SCROLL_POSTER_SRC` constants are added in Task 5.

- [ ] **Step 1: Write the failing test**

`web/src/media.test.ts`:

```ts
import { existsSync, statSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

const scroll = (file: string) => new URL(`../public/media/scroll/${file}`, import.meta.url)

describe('scroll scene media', () => {
  it('ships the video and its poster with a small footprint', () => {
    expect(existsSync(scroll('ink-tree-network-v1.mp4'))).toBe(true)
    expect(existsSync(scroll('ink-tree-network-v1-poster.webp'))).toBe(true)
    expect(statSync(scroll('ink-tree-network-v1.mp4')).size).toBeLessThan(3 * 1024 * 1024)
    expect(statSync(scroll('ink-tree-network-v1-poster.webp')).size).toBeLessThan(200 * 1024)
  })
})
```

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/media.test.ts`
Expected: FAIL — the poster does not exist.

- [ ] **Step 2: Re-encode the video as all-intra**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "C:/Users/lucho/Desktop/portfolio-luciano/.worktrees/phase-6-cinematic-scroll/web/public/media/scroll:/media" jrottenberg/ffmpeg:7.1-alpine -y -i /media/ink-tree-network-v1.mp4 -an -c:v libx264 -preset slow -crf 20 -g 1 -keyint_min 1 -sc_threshold 0 -pix_fmt yuv420p -movflags +faststart /media/ink-tree-network-v1-intra.mp4
MSYS_NO_PATHCONV=1 docker run --rm -v "C:/Users/lucho/Desktop/portfolio-luciano/.worktrees/phase-6-cinematic-scroll/web/public/media/scroll:/media" --entrypoint ffprobe jrottenberg/ffmpeg:7.1-alpine -v error -select_streams v:0 -skip_frame nokey -count_frames -show_entries stream=codec_name,width,height,nb_read_frames -of csv=p=0 /media/ink-tree-network-v1-intra.mp4
```

Expected: the probe prints `h264,848,480,81` (81 key frames). Then replace the original:

```bash
mv web/public/media/scroll/ink-tree-network-v1-intra.mp4 web/public/media/scroll/ink-tree-network-v1.mp4
```

- [ ] **Step 3: Extract the poster**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "C:/Users/lucho/Desktop/portfolio-luciano/.worktrees/phase-6-cinematic-scroll/web/public/media/scroll:/media" jrottenberg/ffmpeg:7.1-alpine -y -i /media/ink-tree-network-v1.mp4 -vf "select=eq(n\,0)" -frames:v 1 -c:v libwebp -quality 82 /media/ink-tree-network-v1-poster.webp
```

Open `web/public/media/scroll/ink-tree-network-v1-poster.webp` with the Read tool and confirm it shows the cream paper with the small sprouting root.

- [ ] **Step 4: Run the test, record the change and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/media.test.ts`
Expected: PASS.

In `docs/design/phase-6/scroll-video/PROMPTS.md`, replace the `Output` row of the first table with:

```markdown
| Output | 848×480, 81 frames, 5.06 s, no audio. Served re-encoded as H.264 all-intra (`-g 1`, CRF 20, `+faststart`) so the `currentTime` fallback seeks to any frame; poster `ink-tree-network-v1-poster.webp` is frame 0 |
```

```bash
git add web/public/media/scroll web/src/media.test.ts docs/design/phase-6/scroll-video/PROMPTS.md
git commit -m "feat(web): re-encode the scroll video as all-intra and add its poster"
```

---

### Task 3: API client for the six public resources

**Files:**
- Modify: `web/src/lib/api.ts` (full rewrite), `web/src/lib/api.test.ts` (full rewrite)
- Modify: `web/src/lib/scene.ts`, `web/src/lib/scene.test.ts`, `web/src/App.tsx` (loader call only)

**Interfaces:**
- Produces (all exported from `@/lib/api`):
  - Types `Locale`, `Statement`, `Closing`, `Photo`, `WorkMode`, `Profile`, `ProfessionalLinkKey`, `ProfessionalLink`, `CvLink`, `TechnologyCategory`, `TechnologyGroup`, `WorkPrinciple`, `EducationItem`, `LanguageLevel`, `LanguageItem`, `Site`, `Technology`, `Experience`, `WorkCase`, `ProjectKind`, `DeliveryStatus`, `ProjectImage`, `Project`, `StructuralContent = { profile: Profile; site: Site }`, `RegionData = { experiences: Experience[]; 'work-cases': WorkCase[]; projects: Project[]; technologies: Technology[] }`, `Region = keyof RegionData`.
  - `REGIONS: readonly Region[]`, `ApiError`, validators `isProfile`, `isSite`, `isTechnologyList`, `isExperienceList`, `isWorkCaseList`, `isProjectList`.
  - `loadStructuralContent(locale: Locale, signal: AbortSignal): Promise<StructuralContent>` — rejects with `ApiError` if either request fails or is malformed.
  - `loadRegion<R extends Region>(locale: Locale, region: R, signal: AbortSignal): Promise<RegionData[R]>` — rejects with `ApiError` on failure.
  - `@/lib/scene`: `type SceneInput = StructuralContent & { technologies: Technology[] }`; `buildScene(input: SceneInput): SceneContent` (same output as before).

- [ ] **Step 1: Write the failing tests**

Replace `web/src/lib/api.test.ts` with:

```ts
import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  ApiError,
  isExperienceList,
  isProfile,
  isProjectList,
  isSite,
  isTechnologyList,
  isWorkCaseList,
  loadRegion,
  loadStructuralContent,
} from '@/lib/api'

const technology = { key: 'php', name: 'PHP', category: 'backend', icon: null }

const profile = {
  name: 'Synthetic Engineer',
  location: 'Synthetic City',
  work_modes: ['on_site', 'remote'],
  headline: 'Synthetic headline',
  short_summary: 'Synthetic summary.',
  introduction: 'Synthetic introduction.',
  availability: 'Synthetic availability.',
  statement: { lead: 'Lead', emphasis: 'Emphasis', tail: 'Tail' },
  closing: null,
  cta: 'Synthetic CTA',
  photo: { url: '/storage/profiles/photo.png', alt: 'Synthetic portrait' },
}

const site = {
  projects_empty_message: 'Empty.',
  contact_intro: 'Intro.',
  technology_groups: [
    { key: 'backend', label: 'Backend' },
    { key: 'data', label: 'Data' },
    { key: 'integration', label: 'Integrations' },
    { key: 'collaboration', label: 'Collaboration' },
  ],
  professional_links: [{ key: 'email', label: 'Email me', href: 'mailto:synthetic@example.test' }],
  expertise_areas: [],
  work_principles: [{ key: 'quality', statement: 'Synthetic principle.' }],
  education: [{ key: 'school', institution: 'Synthetic School', program: 'Diploma', detail: null, start_year: null, end_year: 2021 }],
  languages: [{ key: 'english', name: 'English', level: 'b2' }],
  cv: null,
}

const experience = {
  key: 'role',
  organization: 'Synthetic Org',
  role: 'Engineer',
  start: '2025-09',
  end: null,
  summary: 'Summary.',
  highlights: ['Highlight.'],
  technologies: [technology],
}

const workCase = {
  key: 'case',
  experience_key: 'role',
  title: 'Case',
  context: 'Context.',
  problem: 'Problem.',
  contribution: 'Contribution.',
  technical_approach: 'Approach.',
  outcome: 'Outcome.',
  technologies: [],
}

const project = {
  key: 'project',
  kind: 'client',
  client_name: 'Synthetic Client',
  title: 'Project',
  role: 'Backend',
  status: 'in_use',
  summary: 'Summary.',
  problem: 'Problem.',
  solution: 'Solution.',
  result: 'Result.',
  featured: false,
  images: [{ url: '/storage/projects/a.webp', alt: 'Screenshot' }],
  demo_url: null,
  repository_url: 'https://example.test/repo',
  technologies: [technology],
}

function json(data: unknown, status = 200): Response {
  return new Response(JSON.stringify(data), { status, headers: { 'content-type': 'application/json' } })
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('validators', () => {
  it('accept the documented shapes and tolerate extra fields', () => {
    expect(isProfile(profile)).toBe(true)
    expect(isProfile({ ...profile, photo: null, location: null, work_modes: [] })).toBe(true)
    expect(isSite(site)).toBe(true)
    expect(isTechnologyList([technology])).toBe(true)
    expect(isExperienceList([experience, { ...experience, organization: null, end: '2025-09' }])).toBe(true)
    expect(isWorkCaseList([workCase, { ...workCase, experience_key: null }])).toBe(true)
    expect(isProjectList([project, { ...project, kind: 'personal', client_name: null, images: [] }])).toBe(true)
  })

  it('reject missing, mistyped, or out-of-contract values', () => {
    expect(isProfile({ ...profile, work_modes: ['office'] })).toBe(false)
    expect(isProfile({ ...profile, photo: { url: '/x.png' } })).toBe(false)
    expect(isSite({ ...site, languages: [{ key: 'x', name: 'X', level: 'fluent' }] })).toBe(false)
    expect(isSite({ ...site, education: [{ ...site.education[0], end_year: '2021' }] })).toBe(false)
    expect(isTechnologyList([{ ...technology, category: 'frontend' }])).toBe(false)
    expect(isExperienceList([{ ...experience, start: '2025-9' }])).toBe(false)
    expect(isWorkCaseList([{ ...workCase, experience_key: 3 }])).toBe(false)
    expect(isProjectList([{ ...project, status: 'archived' }])).toBe(false)
    expect(isProjectList([{ ...project, images: [{ url: '/a.webp' }] }])).toBe(false)
  })
})

describe('loadStructuralContent', () => {
  it('requests profile and site for the locale', async () => {
    const fetchMock = vi.fn(async (input: RequestInfo | URL) =>
      String(input).endsWith('/profile') ? json({ data: profile }) : json({ data: site }),
    )
    vi.stubGlobal('fetch', fetchMock)

    const content = await loadStructuralContent('en', new AbortController().signal)

    expect(fetchMock.mock.calls.map(([path]) => String(path)).sort()).toEqual(['/api/v1/en/profile', '/api/v1/en/site'])
    expect(content.profile.name).toBe('Synthetic Engineer')
    expect(content.site.languages).toHaveLength(1)
  })

  it('rejects when either structural resource fails or is malformed', async () => {
    vi.stubGlobal('fetch', vi.fn(async (input: RequestInfo | URL) =>
      String(input).endsWith('/profile') ? json({ error: { code: 'not_found' } }, 404) : json({ data: site }),
    ))
    await expect(loadStructuralContent('es', new AbortController().signal)).rejects.toBeInstanceOf(ApiError)

    vi.stubGlobal('fetch', vi.fn(async (input: RequestInfo | URL) =>
      String(input).endsWith('/profile') ? json({ data: profile }) : json({ data: { ...site, education: null } }),
    ))
    await expect(loadStructuralContent('es', new AbortController().signal)).rejects.toBeInstanceOf(ApiError)
  })
})

describe('loadRegion', () => {
  it('requests one collection and returns validated data', async () => {
    const fetchMock = vi.fn(async () => json({ data: [project] }))
    vi.stubGlobal('fetch', fetchMock)

    const projects = await loadRegion('es', 'projects', new AbortController().signal)

    expect(String(fetchMock.mock.calls[0]?.[0])).toBe('/api/v1/es/projects')
    expect(projects[0]?.client_name).toBe('Synthetic Client')
  })

  it('rejects a failed or malformed collection', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => json({ error: { code: 'content_temporarily_unavailable' } }, 503)))
    await expect(loadRegion('es', 'work-cases', new AbortController().signal)).rejects.toBeInstanceOf(ApiError)

    vi.stubGlobal('fetch', vi.fn(async () => json({ data: [{ key: 'bad' }] })))
    await expect(loadRegion('es', 'experiences', new AbortController().signal)).rejects.toBeInstanceOf(ApiError)
  })
})
```

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/lib/api.test.ts`
Expected: FAIL — `isExperienceList` / `loadRegion` are not exported.

- [ ] **Step 2: Rewrite the client**

Replace `web/src/lib/api.ts` with:

```ts
/**
 * Browser client for the Laravel public API v1 (docs/api/PUBLIC_API_V1.md).
 * Every body is parsed as `unknown` and validated before it reaches the UI.
 * Requests are root-relative: the portfolio gateway serves the front and
 * `/api/*` from the same origin, so no CORS or API origin configuration exists.
 */

export type Locale = 'es' | 'en'

export type Statement = { lead: string; emphasis: string; tail: string }
export type Closing = { line_one: string; line_two: string }
export type Photo = { url: string; alt: string }
export type WorkMode = 'on_site' | 'hybrid' | 'remote'

export type Profile = {
  name: string
  location: string | null
  work_modes: WorkMode[]
  headline: string
  short_summary: string
  introduction: string
  availability: string
  statement: Statement | null
  closing: Closing | null
  cta: string
  photo: Photo | null
}

export type ProfessionalLinkKey = 'linkedin' | 'github' | 'email'
export type ProfessionalLink = { key: ProfessionalLinkKey; label: string; href: string }
export type CvLink = { url: string; label: string }
export type TechnologyCategory = 'backend' | 'data' | 'integration' | 'collaboration'
export type TechnologyGroup = { key: TechnologyCategory; label: string }
export type WorkPrinciple = { key: string; statement: string }
export type EducationItem = {
  key: string
  institution: string
  program: string
  detail: string | null
  start_year: number | null
  end_year: number | null
}
export type LanguageLevel = 'native' | 'a1' | 'a2' | 'b1' | 'b2' | 'c1' | 'c2'
export type LanguageItem = { key: string; name: string; level: LanguageLevel }

export type Site = {
  projects_empty_message: string
  contact_intro: string
  technology_groups: TechnologyGroup[]
  professional_links: ProfessionalLink[]
  work_principles: WorkPrinciple[]
  education: EducationItem[]
  languages: LanguageItem[]
  cv: CvLink | null
}

export type Technology = { key: string; name: string; category: TechnologyCategory }

export type Experience = {
  key: string
  organization: string | null
  role: string
  /** `YYYY-MM` */
  start: string
  /** `YYYY-MM`, or `null` for a current role */
  end: string | null
  summary: string
  highlights: string[]
  technologies: Technology[]
}

export type WorkCase = {
  key: string
  experience_key: string | null
  title: string
  context: string
  problem: string
  contribution: string
  technical_approach: string
  outcome: string
  technologies: Technology[]
}

export type ProjectKind = 'client' | 'personal'
export type DeliveryStatus = 'in_production' | 'in_use' | 'public_demo' | 'in_development'
export type ProjectImage = { url: string; alt: string }

export type Project = {
  key: string
  kind: ProjectKind
  client_name: string | null
  title: string
  role: string
  status: DeliveryStatus
  summary: string
  problem: string
  solution: string
  result: string
  featured: boolean
  images: ProjectImage[]
  demo_url: string | null
  repository_url: string | null
  technologies: Technology[]
}

export type StructuralContent = { profile: Profile; site: Site }

export type RegionData = {
  experiences: Experience[]
  'work-cases': WorkCase[]
  projects: Project[]
  technologies: Technology[]
}

export type Region = keyof RegionData

export const REGIONS: readonly Region[] = ['experiences', 'work-cases', 'projects', 'technologies']

export class ApiError extends Error {
  override name = 'ApiError'
}

type Validator<T> = (value: unknown) => value is T

const LINK_KEYS: readonly string[] = ['linkedin', 'github', 'email']
const TECHNOLOGY_CATEGORIES: readonly string[] = ['backend', 'data', 'integration', 'collaboration']
const WORK_MODES: readonly string[] = ['on_site', 'hybrid', 'remote']
const LANGUAGE_LEVELS: readonly string[] = ['native', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2']
const PROJECT_KINDS: readonly string[] = ['client', 'personal']
const DELIVERY_STATUSES: readonly string[] = ['in_production', 'in_use', 'public_demo', 'in_development']
const MONTH = /^\d{4}-(0[1-9]|1[0-2])$/

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function hasStrings(value: unknown, keys: readonly string[]): value is Record<string, string> {
  return isRecord(value) && keys.every((key) => typeof value[key] === 'string')
}

function isNullableString(value: unknown): value is string | null {
  return value === null || typeof value === 'string'
}

function isNullableYear(value: unknown): value is number | null {
  return value === null || (typeof value === 'number' && Number.isInteger(value))
}

function listOf<T>(validate: Validator<T>): Validator<T[]> {
  return (value: unknown): value is T[] => Array.isArray(value) && value.every(validate)
}

function isOneOf(value: unknown, allowed: readonly string[]): boolean {
  return typeof value === 'string' && allowed.includes(value)
}

const isPhoto: Validator<Photo> = (value): value is Photo => hasStrings(value, ['url', 'alt'])

export function isProfile(value: unknown): value is Profile {
  if (!hasStrings(value, ['name', 'headline', 'short_summary', 'introduction', 'availability', 'cta'])) return false
  const record = value as Record<string, unknown>
  return (
    isNullableString(record.location) &&
    Array.isArray(record.work_modes) &&
    record.work_modes.every((mode) => isOneOf(mode, WORK_MODES)) &&
    (record.statement === null || hasStrings(record.statement, ['lead', 'emphasis', 'tail'])) &&
    (record.closing === null || hasStrings(record.closing, ['line_one', 'line_two'])) &&
    (record.photo === null || isPhoto(record.photo))
  )
}

const isProfessionalLink: Validator<ProfessionalLink> = (value): value is ProfessionalLink =>
  hasStrings(value, ['key', 'label', 'href']) && isOneOf(value.key, LINK_KEYS)

const isTechnologyGroup: Validator<TechnologyGroup> = (value): value is TechnologyGroup =>
  hasStrings(value, ['key', 'label']) && isOneOf(value.key, TECHNOLOGY_CATEGORIES)

const isWorkPrinciple: Validator<WorkPrinciple> = (value): value is WorkPrinciple =>
  hasStrings(value, ['key', 'statement'])

const isEducationItem: Validator<EducationItem> = (value): value is EducationItem =>
  hasStrings(value, ['key', 'institution', 'program']) &&
  isNullableString((value as Record<string, unknown>).detail) &&
  isNullableYear((value as Record<string, unknown>).start_year) &&
  isNullableYear((value as Record<string, unknown>).end_year)

const isLanguageItem: Validator<LanguageItem> = (value): value is LanguageItem =>
  hasStrings(value, ['key', 'name', 'level']) && isOneOf(value.level, LANGUAGE_LEVELS)

export function isSite(value: unknown): value is Site {
  if (!hasStrings(value, ['projects_empty_message', 'contact_intro'])) return false
  const record = value as Record<string, unknown>
  return (
    listOf(isTechnologyGroup)(record.technology_groups) &&
    listOf(isProfessionalLink)(record.professional_links) &&
    listOf(isWorkPrinciple)(record.work_principles) &&
    listOf(isEducationItem)(record.education) &&
    listOf(isLanguageItem)(record.languages) &&
    (record.cv === null || hasStrings(record.cv, ['url', 'label']))
  )
}

const isTechnology: Validator<Technology> = (value): value is Technology =>
  hasStrings(value, ['key', 'name', 'category']) && isOneOf(value.category, TECHNOLOGY_CATEGORIES)

export const isTechnologyList: Validator<Technology[]> = listOf(isTechnology)

const isExperience: Validator<Experience> = (value): value is Experience => {
  if (!hasStrings(value, ['key', 'role', 'start', 'summary'])) return false
  const record = value as Record<string, unknown>
  return (
    MONTH.test(value.start) &&
    isNullableString(record.organization) &&
    (record.end === null || (typeof record.end === 'string' && MONTH.test(record.end))) &&
    Array.isArray(record.highlights) &&
    record.highlights.every((highlight) => typeof highlight === 'string') &&
    isTechnologyList(record.technologies)
  )
}

export const isExperienceList: Validator<Experience[]> = listOf(isExperience)

const isWorkCase: Validator<WorkCase> = (value): value is WorkCase =>
  hasStrings(value, ['key', 'title', 'context', 'problem', 'contribution', 'technical_approach', 'outcome']) &&
  isNullableString((value as Record<string, unknown>).experience_key) &&
  isTechnologyList((value as Record<string, unknown>).technologies)

export const isWorkCaseList: Validator<WorkCase[]> = listOf(isWorkCase)

const isProject: Validator<Project> = (value): value is Project => {
  if (!hasStrings(value, ['key', 'kind', 'title', 'role', 'status', 'summary', 'problem', 'solution', 'result'])) return false
  const record = value as Record<string, unknown>
  return (
    isOneOf(record.kind, PROJECT_KINDS) &&
    isOneOf(record.status, DELIVERY_STATUSES) &&
    isNullableString(record.client_name) &&
    typeof record.featured === 'boolean' &&
    listOf(isPhoto)(record.images) &&
    isNullableString(record.demo_url) &&
    isNullableString(record.repository_url) &&
    isTechnologyList(record.technologies)
  )
}

export const isProjectList: Validator<Project[]> = listOf(isProject)

async function request<T>(path: string, validate: Validator<T>, signal: AbortSignal): Promise<T> {
  const response = await fetch(path, { headers: { accept: 'application/json' }, signal })
  if (!response.ok) throw new ApiError(`HTTP ${response.status} for ${path}`)

  const body: unknown = await response.json().catch(() => null)
  const data = isRecord(body) ? body.data : undefined
  if (!validate(data)) throw new ApiError(`Malformed response for ${path}`)
  return data
}

/** Profile and Site are structural: without them the page shows only a general error with Retry. */
export async function loadStructuralContent(locale: Locale, signal: AbortSignal): Promise<StructuralContent> {
  const [profile, site] = await Promise.all([
    request(`/api/v1/${locale}/profile`, isProfile, signal),
    request(`/api/v1/${locale}/site`, isSite, signal),
  ])
  return { profile, site }
}

const REGION_VALIDATORS: { [R in Region]: Validator<RegionData[R]> } = {
  experiences: isExperienceList,
  'work-cases': isWorkCaseList,
  projects: isProjectList,
  technologies: isTechnologyList,
}

/** A regional collection: its failure affects only its own section, which offers its own Retry. */
export function loadRegion<R extends Region>(locale: Locale, region: R, signal: AbortSignal): Promise<RegionData[R]> {
  return request(`/api/v1/${locale}/${region}`, REGION_VALIDATORS[region], signal)
}
```

- [ ] **Step 3: Adapt the scene builder and the current App**

In `web/src/lib/scene.ts`, replace the first import line with:

```ts
import type { CvLink, ProfessionalLink, Statement, StructuralContent, Technology } from '@/lib/api'
```

insert before `export function buildScene`:

```ts
export type SceneInput = StructuralContent & { technologies: Technology[] }
```

and change the signature to `export function buildScene({ profile, site, technologies }: SceneInput): SceneContent {`.

In `web/src/lib/scene.test.ts`, replace the import of `PublicContent` with `import type { SceneInput } from '@/lib/scene'` merged into the existing `buildScene` import (`import { buildScene, type SceneInput } from '@/lib/scene'`), delete the `import type { PublicContent } from '@/lib/api'` line, and replace the `content` constant header and its `profile`/`site` objects with:

```ts
const content: SceneInput = {
  profile: {
    name: 'Synthetic Engineer',
    location: null,
    work_modes: [],
    headline: 'Synthetic headline',
    short_summary: 'Synthetic summary.',
    introduction: 'Synthetic introduction.',
    availability: 'Synthetic availability.',
    statement: { lead: 'Lead', emphasis: 'Emphasis', tail: 'Tail' },
    closing: { line_one: 'Line one', line_two: 'Line two' },
    cta: 'Synthetic CTA',
    photo: null,
  },
  site: {
    projects_empty_message: 'Empty.',
    contact_intro: 'Intro.',
    technology_groups: [],
    professional_links: [
      { key: 'linkedin', label: 'LinkedIn', href: 'https://linkedin.test/synthetic' },
      { key: 'email', label: 'Email me', href: 'mailto:synthetic@example.test' },
    ],
    work_principles: [],
    education: [],
    languages: [],
    cv: { url: '/cv/luciano-gonzalez-en.pdf', label: 'Download CV' },
  },
```

(keep the existing `technologies` array and the closing `}`). In the second test replace `site: { professional_links: [], cv: null },` with `site: { ...content.site, professional_links: [], cv: null },`.

In `web/src/App.tsx`, replace `import { loadPublicContent } from '@/lib/api'` with `import { loadRegion, loadStructuralContent } from '@/lib/api'`, and replace the loading effect (the `useEffect` that calls `loadPublicContent`) with:

```tsx
  useEffect(() => {
    const controller = new AbortController()
    const { signal } = controller
    setState({ status: 'loading' })
    Promise.all([
      loadStructuralContent(locale, signal),
      loadRegion(locale, 'technologies', signal).catch((error: unknown) => {
        if (signal.aborted) throw error
        return []
      }),
    ])
      .then(([structural, technologies]) => setState({ status: 'ready', scene: buildScene({ ...structural, technologies }) }))
      .catch(() => {
        if (!signal.aborted) setState({ status: 'error' })
      })
    return () => controller.abort()
  }, [locale, attempt])
```

(App is fully recomposed in Tasks 6–10; this keeps the build green.)

- [ ] **Step 4: Run the full front check and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'`
Expected: all green.

```bash
git add web/src/lib/api.ts web/src/lib/api.test.ts web/src/lib/scene.ts web/src/lib/scene.test.ts web/src/App.tsx
git commit -m "feat(web): validate the six public API resources with structural and regional loaders"
```

---

### Task 4: Content shaping — grouping, gallery, formatting and section availability

Pure functions only; no component uses them until Task 6.

**Files:**
- Create: `web/src/lib/grouping.ts`, `web/src/lib/grouping.test.ts`
- Create: `web/src/lib/gallery.ts`, `web/src/lib/gallery.test.ts`
- Create: `web/src/lib/format.ts`, `web/src/lib/format.test.ts`
- Create: `web/src/lib/sections.ts`, `web/src/lib/sections.test.ts`

**Interfaces:**
- Consumes: types from `@/lib/api` (Task 3).
- Produces:
  - `@/lib/grouping`: `type RoleEntry = { experience: Experience; cases: WorkCase[] }`; `type OrganizationGroup = { key: string; organization: string | null; roles: RoleEntry[] }`; `type ExperienceTimeline = { organizations: OrganizationGroup[]; unlinkedCases: WorkCase[] }`; `groupExperience(experiences: Experience[], workCases: WorkCase[]): ExperienceTimeline`; `type ProjectGroups = { client: Project[]; personal: Project[] }`; `groupProjects(projects: Project[]): ProjectGroups`; `PROJECT_PREVIEW_LIMIT = 3`; `previewItems<T>(items: T[], expanded: boolean, limit?: number): { visible: T[]; hiddenCount: number }`; `type TechnologyColumn = { key: TechnologyCategory; label: string; names: string[] }`; `groupTechnologies(technologies: Technology[], groups: TechnologyGroup[]): TechnologyColumn[]`.
  - `@/lib/gallery`: `GALLERY_THUMB_LIMIT = 4`; `thumbnailSlots(count: number, limit?: number): { visible: number; overflow: number }`; `wrapIndex(index: number, count: number): number`.
  - `@/lib/format`: `formatMonth(value: string, locale: Locale): string`; `formatPeriod(start: string, end: string | null, locale: Locale): string`; `formatYears(start: number | null, end: number | null): string | null`; `emailAddress(href: string): string`.
  - `@/lib/sections`: `type SectionId = 'experience' | 'projects' | 'stack' | 'about' | 'contact'`; `SECTION_ORDER: readonly SectionId[]`; `sectionNumber(id: SectionId): string` (`'01'`…`'05'`); `type RegionStatus = 'loading' | 'ready' | 'error'`; `type SectionAvailability = { experience: { status: RegionStatus; hasItems: boolean }; stack: { status: RegionStatus; hasItems: boolean }; about: boolean; contact: boolean }`; `visibleSections(input: SectionAvailability): SectionId[]`; `aboutStatement(site: Site): string | null`; `hasAboutContent(profile: Profile, site: Site): boolean`; `hasContactContent(site: Site): boolean`.

- [ ] **Step 1: Write the failing tests**

`web/src/lib/grouping.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import type { Experience, Project, Technology, WorkCase } from '@/lib/api'
import { groupExperience, groupProjects, groupTechnologies, previewItems } from '@/lib/grouping'

const experience = (key: string, organization: string | null): Experience => ({
  key,
  organization,
  role: `Role ${key}`,
  start: '2025-01',
  end: null,
  summary: 'Summary.',
  highlights: [],
  technologies: [],
})

const workCase = (key: string, experienceKey: string | null): WorkCase => ({
  key,
  experience_key: experienceKey,
  title: `Case ${key}`,
  context: 'Context.',
  problem: 'Problem.',
  contribution: 'Contribution.',
  technical_approach: 'Approach.',
  outcome: 'Outcome.',
  technologies: [],
})

const project = (key: string, kind: Project['kind']): Project => ({
  key,
  kind,
  client_name: kind === 'client' ? 'Client' : null,
  title: key,
  role: 'Backend',
  status: 'in_use',
  summary: 'Summary.',
  problem: 'Problem.',
  solution: 'Solution.',
  result: 'Result.',
  featured: false,
  images: [],
  demo_url: null,
  repository_url: null,
  technologies: [],
})

describe('groupExperience', () => {
  it('groups roles by organization in order of first appearance and attaches their cases', () => {
    const timeline = groupExperience(
      [experience('current', 'Org A'), experience('freelance', 'Org B'), experience('intern', 'Org A')],
      [workCase('c1', 'current'), workCase('c2', 'intern'), workCase('c3', 'current')],
    )

    expect(timeline.organizations.map((group) => group.organization)).toEqual(['Org A', 'Org B'])
    expect(timeline.organizations[0]?.roles.map((role) => role.experience.key)).toEqual(['current', 'intern'])
    expect(timeline.organizations[0]?.roles[0]?.cases.map((item) => item.key)).toEqual(['c1', 'c3'])
    expect(timeline.organizations[0]?.roles[1]?.cases.map((item) => item.key)).toEqual(['c2'])
    expect(timeline.unlinkedCases).toEqual([])
  })

  it('keeps each role without organization as its own group', () => {
    const timeline = groupExperience([experience('a', null), experience('b', null)], [])

    expect(timeline.organizations.map((group) => group.key)).toEqual(['a', 'b'])
    expect(timeline.organizations.every((group) => group.organization === null)).toBe(true)
  })

  it('moves unlinked cases and cases of non-public experiences to unlinkedCases', () => {
    const timeline = groupExperience([experience('current', 'Org A')], [workCase('linked', 'current'), workCase('none', null), workCase('hidden', 'hidden-role')])

    expect(timeline.unlinkedCases.map((item) => item.key)).toEqual(['none', 'hidden'])
  })

  it('returns an empty timeline for empty input', () => {
    expect(groupExperience([], [])).toEqual({ organizations: [], unlinkedCases: [] })
  })
})

describe('groupProjects', () => {
  it('splits client and personal projects preserving API order', () => {
    const groups = groupProjects([project('p1', 'personal'), project('c1', 'client'), project('p2', 'personal')])

    expect(groups.client.map((item) => item.key)).toEqual(['c1'])
    expect(groups.personal.map((item) => item.key)).toEqual(['p1', 'p2'])
  })

  it('returns empty groups', () => {
    expect(groupProjects([])).toEqual({ client: [], personal: [] })
  })
})

describe('previewItems', () => {
  it('shows the first three until expanded', () => {
    const items = [1, 2, 3, 4, 5]

    expect(previewItems(items, false)).toEqual({ visible: [1, 2, 3], hiddenCount: 2 })
    expect(previewItems(items, true)).toEqual({ visible: items, hiddenCount: 0 })
    expect(previewItems([1, 2], false)).toEqual({ visible: [1, 2], hiddenCount: 0 })
  })
})

describe('groupTechnologies', () => {
  it('follows the site group order and skips empty columns', () => {
    const technologies: Technology[] = [
      { key: 'mysql', name: 'MySQL', category: 'data' },
      { key: 'php', name: 'PHP', category: 'backend' },
      { key: 'laravel', name: 'Laravel', category: 'backend' },
    ]

    expect(
      groupTechnologies(technologies, [
        { key: 'backend', label: 'Backend' },
        { key: 'data', label: 'Datos' },
        { key: 'integration', label: 'Integraciones' },
      ]),
    ).toEqual([
      { key: 'backend', label: 'Backend', names: ['PHP', 'Laravel'] },
      { key: 'data', label: 'Datos', names: ['MySQL'] },
    ])
  })
})
```

`web/src/lib/gallery.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { thumbnailSlots, wrapIndex } from '@/lib/gallery'

describe('thumbnailSlots', () => {
  it('shows no thumbnails for zero or one image', () => {
    expect(thumbnailSlots(0)).toEqual({ visible: 0, overflow: 0 })
    expect(thumbnailSlots(1)).toEqual({ visible: 0, overflow: 0 })
  })

  it('fills four tiles: every image up to four, otherwise three thumbnails and a +N tile', () => {
    expect(thumbnailSlots(3)).toEqual({ visible: 3, overflow: 0 })
    expect(thumbnailSlots(4)).toEqual({ visible: 4, overflow: 0 })
    expect(thumbnailSlots(5)).toEqual({ visible: 3, overflow: 2 })
    expect(thumbnailSlots(12)).toEqual({ visible: 3, overflow: 9 })
  })
})

describe('wrapIndex', () => {
  it('wraps previous and next around the gallery', () => {
    expect(wrapIndex(-1, 5)).toBe(4)
    expect(wrapIndex(5, 5)).toBe(0)
    expect(wrapIndex(2, 5)).toBe(2)
    expect(wrapIndex(3, 0)).toBe(0)
  })
})
```

`web/src/lib/format.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { emailAddress, formatMonth, formatPeriod, formatYears } from '@/lib/format'

describe('dates', () => {
  it('formats API months per locale', () => {
    expect(formatMonth('2025-09', 'es')).toBe('sept 2025')
    expect(formatMonth('2025-05', 'en')).toBe('May 2025')
  })

  it('formats current and closed periods', () => {
    expect(formatPeriod('2025-09', null, 'es')).toBe('sept 2025 – actualidad')
    expect(formatPeriod('2025-05', '2025-09', 'en')).toBe('May 2025 – Sep 2025')
  })

  it('formats optional education years', () => {
    expect(formatYears(null, null)).toBeNull()
    expect(formatYears(null, 2021)).toBe('2021')
    expect(formatYears(2022, 2022)).toBe('2022')
    expect(formatYears(2020, 2022)).toBe('2020 – 2022')
    expect(formatYears(2023, null)).toBe('2023')
  })
})

describe('emailAddress', () => {
  it('shows the address behind a mailto link', () => {
    expect(emailAddress('mailto:synthetic@example.test')).toBe('synthetic@example.test')
    expect(emailAddress('synthetic@example.test')).toBe('synthetic@example.test')
  })
})
```

`web/src/lib/sections.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import type { Profile, Site } from '@/lib/api'
import { aboutStatement, hasAboutContent, hasContactContent, sectionNumber, visibleSections } from '@/lib/sections'

const profile: Profile = {
  name: 'Synthetic',
  location: null,
  work_modes: [],
  headline: 'Headline',
  short_summary: 'Summary.',
  introduction: 'Introduction.',
  availability: 'Availability.',
  statement: null,
  closing: null,
  cta: 'CTA',
  photo: null,
}

const site: Site = {
  projects_empty_message: 'Empty.',
  contact_intro: 'Intro.',
  technology_groups: [],
  professional_links: [],
  work_principles: [],
  education: [],
  languages: [],
  cv: null,
}

describe('visibleSections', () => {
  it('shows every section while regions load or fail, in the fixed order', () => {
    expect(
      visibleSections({
        experience: { status: 'loading', hasItems: false },
        stack: { status: 'error', hasItems: false },
        about: true,
        contact: true,
      }),
    ).toEqual(['experience', 'projects', 'stack', 'about', 'contact'])
  })

  it('hides loaded empty regions and structural sections without content; projects always stay', () => {
    expect(
      visibleSections({
        experience: { status: 'ready', hasItems: false },
        stack: { status: 'ready', hasItems: false },
        about: false,
        contact: false,
      }),
    ).toEqual(['projects'])
  })
})

describe('section helpers', () => {
  it('numbers sections by their fixed position', () => {
    expect(sectionNumber('experience')).toBe('01')
    expect(sectionNumber('contact')).toBe('05')
  })

  it('uses the first work principle as the about statement', () => {
    expect(aboutStatement(site)).toBeNull()
    expect(aboutStatement({ ...site, work_principles: [{ key: 'a', statement: 'First.' }, { key: 'b', statement: 'Second.' }] })).toBe('First.')
  })

  it('detects about and contact content', () => {
    expect(hasAboutContent(profile, site)).toBe(false)
    expect(hasAboutContent({ ...profile, work_modes: ['remote'] }, site)).toBe(true)
    expect(hasAboutContent(profile, { ...site, languages: [{ key: 'en', name: 'English', level: 'b2' }] })).toBe(true)
    expect(hasContactContent(site)).toBe(false)
    expect(hasContactContent({ ...site, cv: { url: '/cv.pdf', label: 'CV' } })).toBe(true)
  })
})
```

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/lib/grouping.test.ts src/lib/gallery.test.ts src/lib/format.test.ts src/lib/sections.test.ts`
Expected: FAIL — the four modules do not exist.

- [ ] **Step 2: Implement the modules**

`web/src/lib/grouping.ts`:

```ts
import type { Experience, Project, Technology, TechnologyCategory, TechnologyGroup, WorkCase } from '@/lib/api'

export type RoleEntry = { experience: Experience; cases: WorkCase[] }
export type OrganizationGroup = { key: string; organization: string | null; roles: RoleEntry[] }
export type ExperienceTimeline = { organizations: OrganizationGroup[]; unlinkedCases: WorkCase[] }

/**
 * Organizations in order of first appearance, each with its roles in API
 * order and each role with its linked cases. A case whose experience is not
 * public (or that has none) goes to `unlinkedCases`.
 */
export function groupExperience(experiences: Experience[], workCases: WorkCase[]): ExperienceTimeline {
  const publicKeys = new Set(experiences.map((experience) => experience.key))
  const casesByRole = new Map<string, WorkCase[]>()
  const unlinkedCases: WorkCase[] = []

  for (const workCase of workCases) {
    if (workCase.experience_key !== null && publicKeys.has(workCase.experience_key)) {
      casesByRole.set(workCase.experience_key, [...(casesByRole.get(workCase.experience_key) ?? []), workCase])
    } else {
      unlinkedCases.push(workCase)
    }
  }

  const organizations: OrganizationGroup[] = []
  const byOrganization = new Map<string, OrganizationGroup>()

  for (const experience of experiences) {
    const role: RoleEntry = { experience, cases: casesByRole.get(experience.key) ?? [] }
    const existing = experience.organization === null ? undefined : byOrganization.get(experience.organization)

    if (existing) {
      existing.roles.push(role)
      continue
    }

    const group: OrganizationGroup = { key: experience.key, organization: experience.organization, roles: [role] }
    organizations.push(group)
    if (experience.organization !== null) byOrganization.set(experience.organization, group)
  }

  return { organizations, unlinkedCases }
}

export type ProjectGroups = { client: Project[]; personal: Project[] }

export function groupProjects(projects: Project[]): ProjectGroups {
  return {
    client: projects.filter((project) => project.kind === 'client'),
    personal: projects.filter((project) => project.kind === 'personal'),
  }
}

/** Projects shown per group before "Show more projects". */
export const PROJECT_PREVIEW_LIMIT = 3

export function previewItems<T>(items: T[], expanded: boolean, limit = PROJECT_PREVIEW_LIMIT): { visible: T[]; hiddenCount: number } {
  if (expanded || items.length <= limit) return { visible: items, hiddenCount: 0 }
  return { visible: items.slice(0, limit), hiddenCount: items.length - limit }
}

export type TechnologyColumn = { key: TechnologyCategory; label: string; names: string[] }

/** Stack columns in the order and with the labels of `site.technology_groups`; empty columns are skipped. */
export function groupTechnologies(technologies: Technology[], groups: TechnologyGroup[]): TechnologyColumn[] {
  return groups
    .map((group) => ({
      key: group.key,
      label: group.label,
      names: technologies.filter((technology) => technology.category === group.key).map((technology) => technology.name),
    }))
    .filter((column) => column.names.length > 0)
}
```

`web/src/lib/gallery.ts`:

```ts
/** Tiles under the main screenshot: every image up to four, otherwise three thumbnails plus a "+N" tile. */
export const GALLERY_THUMB_LIMIT = 4

export function thumbnailSlots(count: number, limit = GALLERY_THUMB_LIMIT): { visible: number; overflow: number } {
  if (count <= 1) return { visible: 0, overflow: 0 }
  if (count <= limit) return { visible: count, overflow: 0 }
  return { visible: limit - 1, overflow: count - (limit - 1) }
}

export function wrapIndex(index: number, count: number): number {
  if (count <= 0) return 0
  return ((index % count) + count) % count
}
```

`web/src/lib/format.ts`:

```ts
import type { Locale } from '@/lib/api'

const MONTHS: Record<Locale, readonly string[]> = {
  es: ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sept', 'oct', 'nov', 'dic'],
  en: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
}

const PRESENT: Record<Locale, string> = { es: 'actualidad', en: 'present' }

/** `YYYY-MM` from the API to a short localized month and year. */
export function formatMonth(value: string, locale: Locale): string {
  const [year, month] = value.split('-')
  return `${MONTHS[locale][Number(month) - 1]} ${year}`
}

export function formatPeriod(start: string, end: string | null, locale: Locale): string {
  return `${formatMonth(start, locale)} – ${end === null ? PRESENT[locale] : formatMonth(end, locale)}`
}

export function formatYears(start: number | null, end: number | null): string | null {
  if (start === null && end === null) return null
  if (start === null || end === null || start === end) return String(start ?? end)
  return `${start} – ${end}`
}

export function emailAddress(href: string): string {
  return href.replace(/^mailto:/, '')
}
```

`web/src/lib/sections.ts`:

```ts
import type { Profile, Site } from '@/lib/api'

export type SectionId = 'experience' | 'projects' | 'stack' | 'about' | 'contact'

export const SECTION_ORDER: readonly SectionId[] = ['experience', 'projects', 'stack', 'about', 'contact']

export function sectionNumber(id: SectionId): string {
  return String(SECTION_ORDER.indexOf(id) + 1).padStart(2, '0')
}

export type RegionStatus = 'loading' | 'ready' | 'error'

export type SectionAvailability = {
  experience: { status: RegionStatus; hasItems: boolean }
  stack: { status: RegionStatus; hasItems: boolean }
  about: boolean
  contact: boolean
}

/**
 * A regional section stays while it loads or fails (it shows its own state and
 * Retry) and disappears only when it loaded empty. Projects always stay: an
 * empty result shows `site.projects_empty_message`.
 */
export function visibleSections(input: SectionAvailability): SectionId[] {
  const region = ({ status, hasItems }: { status: RegionStatus; hasItems: boolean }) => status !== 'ready' || hasItems

  const visible: Record<SectionId, boolean> = {
    experience: region(input.experience),
    projects: true,
    stack: region(input.stack),
    about: input.about,
    contact: input.contact,
  }

  return SECTION_ORDER.filter((id) => visible[id])
}

export function aboutStatement(site: Site): string | null {
  return site.work_principles[0]?.statement ?? null
}

export function hasAboutContent(profile: Profile, site: Site): boolean {
  return (
    aboutStatement(site) !== null ||
    site.education.length > 0 ||
    site.languages.length > 0 ||
    profile.location !== null ||
    profile.work_modes.length > 0
  )
}

export function hasContactContent(site: Site): boolean {
  return site.professional_links.length > 0 || site.cv !== null
}
```

- [ ] **Step 3: Run the tests**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/lib/grouping.test.ts src/lib/gallery.test.ts src/lib/format.test.ts src/lib/sections.test.ts`
Expected: PASS.

- [ ] **Step 4: Run the full front check and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'`
Expected: all green (`noUnusedLocals` does not apply to exported functions).

```bash
git add web/src/lib/grouping.ts web/src/lib/grouping.test.ts web/src/lib/gallery.ts web/src/lib/gallery.test.ts web/src/lib/format.ts web/src/lib/format.test.ts web/src/lib/sections.ts web/src/lib/sections.test.ts
git commit -m "feat(web): add pure grouping, gallery, formatting and section availability rules"
```

---

### Task 5: Scene timeline math and two-frame blending

**Files:**
- Create: `web/src/lib/scene-timeline.ts`, `web/src/lib/scene-timeline.test.ts`
- Modify: `web/src/lib/section-opacity.ts`, `web/src/lib/section-opacity.test.ts`
- Modify: `web/src/lib/scrub-math.ts`, `web/src/lib/scrub-math.test.ts`
- Modify: `web/src/useVideoScrub.ts`, `web/src/App.tsx` (video source only)

**Interfaces:**
- Produces:
  - `@/lib/section-opacity`: `NAV_LIGHT_THRESHOLD = 0.7` (other exports unchanged).
  - `@/lib/scene-timeline`: `SCROLL_VIDEO_SRC = '/media/scroll/ink-tree-network-v1.mp4'`; `SCROLL_POSTER_SRC = '/media/scroll/ink-tree-network-v1-poster.webp'`; `VEIL_OPACITY = 0.45`; `ramp(p, from, to): number`; `correctionEnvelope(p): number`; `correctionFilter(p): string` (`'none'` outside 0.55–0.75); `columnVeilOpacity(p): number`; `navVeilOpacity(p): number`; `landscapeOpacity(p): number`; `starsOpacity(p): number`; `linksOpacity(p): number`.
  - `@/lib/scrub-math`: `blendFrames(frames: ReadonlyArray<{ ts: number }>, seconds: number): { index: number; next: number; weight: number } | null`.
  - `useVideoScrub(videoSrc)` keeps its return shape `{ containerRef, videoRef, canvasRef, scrollProgress, canvasLive }`; the canvas now takes the decoded frame size and blends neighbour frames.

- [ ] **Step 1: Write the failing tests**

`web/src/lib/scene-timeline.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import {
  VEIL_OPACITY,
  columnVeilOpacity,
  correctionEnvelope,
  correctionFilter,
  landscapeOpacity,
  linksOpacity,
  navVeilOpacity,
  starsOpacity,
} from '@/lib/scene-timeline'

describe('transition correction', () => {
  it('is a triangle over 0.55–0.75 peaking at 0.65', () => {
    expect(correctionEnvelope(0.5)).toBe(0)
    expect(correctionEnvelope(0.55)).toBe(0)
    expect(correctionEnvelope(0.6)).toBeCloseTo(0.5)
    expect(correctionEnvelope(0.65)).toBeCloseTo(1)
    expect(correctionEnvelope(0.7)).toBeCloseTo(0.5)
    expect(correctionEnvelope(0.75)).toBe(0)
  })

  it('maps the envelope to the approved CSS filter', () => {
    expect(correctionFilter(0.2)).toBe('none')
    expect(correctionFilter(0.65)).toBe('contrast(1.35) saturate(1.25) brightness(0.94)')
    expect(correctionFilter(0.6)).toBe('contrast(1.175) saturate(1.125) brightness(0.97)')
  })
})

describe('veils', () => {
  it('column veil rises 0.66–0.70, holds to 0.78 and fades by 0.84', () => {
    expect(columnVeilOpacity(0.65)).toBe(0)
    expect(columnVeilOpacity(0.68)).toBeCloseTo(VEIL_OPACITY / 2)
    expect(columnVeilOpacity(0.74)).toBeCloseTo(VEIL_OPACITY)
    expect(columnVeilOpacity(0.81)).toBeCloseTo(VEIL_OPACITY / 2)
    expect(columnVeilOpacity(0.9)).toBe(0)
  })

  it('nav veil does not exist before the nav turns light at 0.70', () => {
    expect(navVeilOpacity(0.69)).toBe(0)
    expect(navVeilOpacity(0.7)).toBeCloseTo(VEIL_OPACITY)
    expect(navVeilOpacity(0.78)).toBeCloseTo(VEIL_OPACITY)
    expect(navVeilOpacity(0.84)).toBe(0)
  })
})

describe('backdrop', () => {
  it('fades the landscape into stars over 0.50–0.75 and draws links over 0.75–0.90', () => {
    expect(landscapeOpacity(0.4)).toBe(1)
    expect(landscapeOpacity(0.625)).toBeCloseTo(0.5)
    expect(landscapeOpacity(0.8)).toBe(0)
    expect(starsOpacity(0.4)).toBe(0)
    expect(starsOpacity(0.625)).toBeCloseTo(0.5)
    expect(starsOpacity(0.8)).toBe(1)
    expect(linksOpacity(0.74)).toBe(0)
    expect(linksOpacity(0.825)).toBeCloseTo(0.5)
    expect(linksOpacity(0.95)).toBe(1)
  })
})
```

Append to `web/src/lib/section-opacity.test.ts` (add `NAV_LIGHT_THRESHOLD` to its import list):

```ts
describe('navigation color', () => {
  it('switches to light text only once dark text stops passing contrast', () => {
    expect(NAV_LIGHT_THRESHOLD).toBe(0.7)
  })
})
```

Append to `web/src/lib/scrub-math.test.ts` (add `blendFrames` to its import list):

```ts
describe('blendFrames', () => {
  const frames = [{ ts: 0 }, { ts: 62_500 }, { ts: 125_000 }]

  it('returns null for an empty bank', () => {
    expect(blendFrames([], 1)).toBeNull()
  })

  it('weights the two neighbours by the fractional position', () => {
    expect(blendFrames(frames, 0.03125)).toEqual({ index: 0, next: 1, weight: 0.5 })
    expect(blendFrames(frames, 0.0625)).toEqual({ index: 1, next: 2, weight: 0 })
  })

  it('clamps before the first and after the last frame', () => {
    expect(blendFrames(frames, -1)).toEqual({ index: 0, next: 0, weight: 0 })
    expect(blendFrames(frames, 9)).toEqual({ index: 2, next: 2, weight: 0 })
  })
})
```

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/lib/scene-timeline.test.ts src/lib/section-opacity.test.ts src/lib/scrub-math.test.ts`
Expected: FAIL — `scene-timeline` missing, threshold is `0.55`, `blendFrames` not exported.

- [ ] **Step 2: Implement the math**

`web/src/lib/scene-timeline.ts`:

```ts
import { NAV_LIGHT_THRESHOLD } from '@/lib/section-opacity'

export const SCROLL_VIDEO_SRC = '/media/scroll/ink-tree-network-v1.mp4'
export const SCROLL_POSTER_SRC = '/media/scroll/ink-tree-network-v1-poster.webp'

/** Darkest opacity of both veils (#1A1411), measured to keep beat 3 and the nav above AA. */
export const VEIL_OPACITY = 0.45

const clamp01 = (value: number) => Math.min(1, Math.max(0, value))

export function ramp(p: number, from: number, to: number): number {
  return clamp01((p - from) / (to - from))
}

/** Low-contrast blend of the video (frames ≈44–60): triangle 0.55 → 0.65 → 0.75. */
export function correctionEnvelope(p: number): number {
  if (p <= 0.55 || p >= 0.75) return 0
  return p <= 0.65 ? (p - 0.55) / 0.1 : (0.75 - p) / 0.1
}

export function correctionFilter(p: number): string {
  const envelope = correctionEnvelope(p)
  if (envelope === 0) return 'none'
  const value = (amount: number) => Number(amount.toFixed(3))
  return `contrast(${value(1 + 0.35 * envelope)}) saturate(${value(1 + 0.25 * envelope)}) brightness(${value(1 - 0.06 * envelope)})`
}

const veilFadeOut = (p: number) => VEIL_OPACITY * (1 - ramp(p, 0.78, 0.84))

export function columnVeilOpacity(p: number): number {
  if (p < 0.66) return 0
  if (p < 0.7) return VEIL_OPACITY * ramp(p, 0.66, 0.7)
  return veilFadeOut(p)
}

/** Appears together with the light nav text (a 150 ms CSS fade smooths the step). */
export function navVeilOpacity(p: number): number {
  return p < NAV_LIGHT_THRESHOLD ? 0 : veilFadeOut(p)
}

export function landscapeOpacity(p: number): number {
  return 1 - ramp(p, 0.5, 0.75)
}

export function starsOpacity(p: number): number {
  return ramp(p, 0.5, 0.75)
}

export function linksOpacity(p: number): number {
  return ramp(p, 0.75, 0.9)
}
```

In `web/src/lib/section-opacity.ts` replace the threshold block with:

```ts
/**
 * The navbar switches to light text at p 0.70: measured per frame, dark text
 * passes AA up to frame 56 and the nav veil keeps light text above AA from here.
 */
export const NAV_LIGHT_THRESHOLD = 0.7
```

Append to `web/src/lib/scrub-math.ts`:

```ts
/**
 * The two frames around `seconds` and how far between them it sits, so an
 * 81-frame bank can be drawn as a continuous cross-fade.
 */
export function blendFrames(
  frames: ReadonlyArray<{ ts: number }>,
  seconds: number,
): { index: number; next: number; weight: number } | null {
  const count = frames.length
  if (count === 0) return null

  const t = seconds * 1e6
  if (t <= frames[0].ts) return { index: 0, next: 0, weight: 0 }
  if (t >= frames[count - 1].ts) return { index: count - 1, next: count - 1, weight: 0 }

  let lo = 0
  let hi = count - 1
  while (hi - lo > 1) {
    const mid = (lo + hi) >> 1
    if (frames[mid].ts <= t) lo = mid
    else hi = mid
  }

  const span = frames[hi].ts - frames[lo].ts
  return { index: lo, next: hi, weight: span > 0 ? (t - frames[lo].ts) / span : 0 }
}
```

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/lib/scene-timeline.test.ts src/lib/section-opacity.test.ts src/lib/scrub-math.test.ts`
Expected: PASS.

- [ ] **Step 3: Blend frames at native size in `useVideoScrub`**

In `web/src/useVideoScrub.ts`:

1. Replace `import { clampProgress, nearestIndex, stepTowards } from '@/lib/scrub-math'` with `import { blendFrames, clampProgress, stepTowards } from '@/lib/scrub-math'`.
2. Replace the whole `draw` function with:

```ts
    const draw = () => {
      const blend = blendFrames(bank, current)
      if (!blend || !ctx) return
      warmLRU(blend.index)

      // 5 % weight steps: smooth to the eye without redrawing on every tick.
      const weight = Math.round(blend.weight * 20) / 20
      const key = blend.index + weight
      if (key === lastDrawn) return

      const base = lru.get(blend.index)
      if (!base) return
      if (canvas.width !== base.width || canvas.height !== base.height) {
        canvas.width = base.width
        canvas.height = base.height
      }

      const overlay = weight > 0 ? lru.get(blend.next) : null
      ctx.globalAlpha = 1
      ctx.drawImage(base, 0, 0, canvas.width, canvas.height)
      if (overlay) {
        ctx.globalAlpha = weight
        ctx.drawImage(overlay, 0, 0, canvas.width, canvas.height)
        ctx.globalAlpha = 1
      }
      // Without the neighbour bitmap yet, draw the base now and blend on a later tick.
      if (weight > 0 && !overlay) return

      lastDrawn = key
      if (!painted) {
        painted = true
        setCanvasLive(true)
      }
    }
```

(`warmLRU` already warms `index - 1` to `index + 2`, which includes `next`.)

- [ ] **Step 4: Use the local video in the current App**

In `web/src/App.tsx`, delete the `VIDEO_SRC` constant (the CloudFront URL), add `import { SCROLL_POSTER_SRC, SCROLL_VIDEO_SRC } from '@/lib/scene-timeline'`, replace both `VIDEO_SRC` usages with `SCROLL_VIDEO_SRC`, and add `poster={SCROLL_POSTER_SRC}` to the `<video>` element. (The scene is rebuilt in Task 6.)

- [ ] **Step 5: Run the full front check and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'`
Expected: all green.

```bash
git add web/src/lib/scene-timeline.ts web/src/lib/scene-timeline.test.ts web/src/lib/section-opacity.ts web/src/lib/section-opacity.test.ts web/src/lib/scrub-math.ts web/src/lib/scrub-math.test.ts web/src/useVideoScrub.ts web/src/App.tsx
git commit -m "feat(web): add scene timeline curves and blend neighbour video frames"
```

---

### Task 6: Interface copy, redesigned scroll scene and App shell

**Files:**
- Modify: `web/src/content.ts`, `web/src/content.test.ts` (full rewrites)
- Modify: `web/src/lib/scene.ts`, `web/src/lib/scene.test.ts` (full rewrites)
- Create: `web/src/lib/constellation.ts`, `web/src/lib/constellation.test.ts`
- Create: `web/src/hooks/usePrefersReducedMotion.ts`, `web/src/hooks/useRegion.ts`
- Create: `web/src/components/Stagger.tsx`, `web/src/components/SceneBackdrop.tsx`, `web/src/components/ScrollScene.tsx`
- Modify: `web/src/App.tsx` (full rewrite)

**Interfaces:**
- Consumes: `@/lib/api` (Task 3), `@/lib/sections` `SectionId` (Task 4), `@/lib/scene-timeline` and `@/lib/section-opacity` (Task 5), `useVideoScrub`.
- Produces:
  - `@/content`: `type UiCopy` (shape below); `uiCopy(locale: Locale): UiCopy`; `resolveLocale(pathname: string): Locale`; `alternateLocale(locale: Locale): Locale`; `localePath(locale: Locale, hash?: string): string` (`es` → `/`, `en` → `/en`, hash appended).
  - `@/lib/scene`: `type SceneContent = { name: string; headline: string; statement: Statement | null; summary: string; closing: { lineOne: string; lineTwo: string | null }; eyebrow: string | null; photo: Photo | null; email: ProfessionalLink | null }`; `type SceneInput`; `buildScene(input: SceneInput): SceneContent`.
  - `@/lib/constellation`: `BACKDROP_WIDTH = 1600`, `BACKDROP_HEIGHT = 900`, `type Star = { x: number; y: number; r: number }`, `STARS: readonly Star[]`, `LINKS: readonly (readonly [number, number])[]`.
  - `usePrefersReducedMotion(): boolean`.
  - `@/hooks/useRegion`: `type RegionState<T> = { status: 'loading' } | { status: 'ready'; data: T } | { status: 'error' }`; `useRegion<R extends Region>(locale: Locale, region: R): { state: RegionState<RegionData[R]>; retry: () => void }`.
  - `Stagger({ visible, delay, className?, children })`.
  - `SceneBackdrop({ progress: number; reducedMotion: boolean })`.
  - `ScrollScene({ scrub: ReturnType<typeof useVideoScrub>; scene: SceneContent | null; status: 'loading' | 'ready' | 'error'; ui: UiCopy; onRetry: () => void })` — the element with `id="top"`.

- [ ] **Step 1: Write the failing tests**

Replace `web/src/content.test.ts` with:

```ts
import { describe, expect, it } from 'vitest'
import { alternateLocale, localePath, resolveLocale, uiCopy } from '@/content'

function shape(value: unknown): unknown {
  if (typeof value === 'function') return 'function'
  if (typeof value === 'object' && value !== null) {
    return Object.fromEntries(
      Object.keys(value)
        .sort()
        .map((key) => [key, shape((value as Record<string, unknown>)[key])]),
    )
  }
  return typeof value
}

describe('locale routing', () => {
  it('uses the path prefix and defaults to Spanish', () => {
    expect(resolveLocale('/')).toBe('es')
    expect(resolveLocale('/es')).toBe('es')
    expect(resolveLocale('/en')).toBe('en')
    expect(resolveLocale('/en/')).toBe('en')
    expect(resolveLocale('/english')).toBe('es')
  })

  it('links to the other language keeping the current anchor', () => {
    expect(alternateLocale('es')).toBe('en')
    expect(alternateLocale('en')).toBe('es')
    expect(localePath('en', '#projects')).toBe('/en#projects')
    expect(localePath('es', '#about')).toBe('/#about')
    expect(localePath('es')).toBe('/')
  })
})

describe('uiCopy', () => {
  it('defines exactly the same interface keys in both locales', () => {
    expect(shape(uiCopy('es'))).toEqual(shape(uiCopy('en')))
  })

  it('pluralizes counters', () => {
    expect(uiCopy('es').projects.showMore(1)).toBe('Ver 1 proyecto más')
    expect(uiCopy('en').projects.showMore(3)).toBe('Show 3 more projects')
    expect(uiCopy('en').projects.gallery.more(2)).toBe('+2')
  })
})
```

Replace `web/src/lib/scene.test.ts` with:

```ts
import { describe, expect, it } from 'vitest'
import { buildScene, type SceneInput } from '@/lib/scene'

const input: SceneInput = {
  profile: {
    name: 'Synthetic Engineer',
    location: null,
    work_modes: [],
    headline: 'Synthetic headline',
    short_summary: 'Synthetic summary.',
    introduction: 'Synthetic introduction.',
    availability: 'Synthetic availability.',
    statement: { lead: 'Lead', emphasis: 'Emphasis', tail: 'Tail' },
    closing: { line_one: 'Line one', line_two: 'Line two' },
    cta: 'Synthetic CTA',
    photo: { url: '/storage/profiles/photo.png', alt: 'Synthetic portrait' },
  },
  site: {
    projects_empty_message: 'Empty.',
    contact_intro: 'Intro.',
    technology_groups: [],
    professional_links: [
      { key: 'linkedin', label: 'LinkedIn', href: 'https://linkedin.test/synthetic' },
      { key: 'email', label: 'Email me', href: 'mailto:synthetic@example.test' },
    ],
    work_principles: [],
    education: [],
    languages: [],
    cv: null,
  },
  technologies: [
    { key: 'php', name: 'PHP', category: 'backend' },
    { key: 'mysql', name: 'MySQL', category: 'data' },
    { key: 'laravel', name: 'Laravel', category: 'backend' },
  ],
}

describe('buildScene', () => {
  it('maps published CMS content onto the three beats', () => {
    const scene = buildScene(input)

    expect(scene.name).toBe('Synthetic Engineer')
    expect(scene.headline).toBe('Synthetic headline')
    expect(scene.photo).toEqual({ url: '/storage/profiles/photo.png', alt: 'Synthetic portrait' })
    expect(scene.statement).toEqual({ lead: 'Lead', emphasis: 'Emphasis', tail: 'Tail' })
    expect(scene.closing).toEqual({ lineOne: 'Line one', lineTwo: 'Line two' })
    expect(scene.eyebrow).toBe('PHP | Laravel')
    expect(scene.email?.href).toBe('mailto:synthetic@example.test')
  })

  it('falls back to approved profile fields when optional groups are empty', () => {
    const scene = buildScene({
      ...input,
      profile: { ...input.profile, statement: null, closing: null, photo: null },
      site: { ...input.site, professional_links: [] },
      technologies: [],
    })

    expect(scene.statement).toBeNull()
    expect(scene.summary).toBe('Synthetic summary.')
    expect(scene.closing).toEqual({ lineOne: 'Synthetic availability.', lineTwo: null })
    expect(scene.eyebrow).toBeNull()
    expect(scene.email).toBeNull()
    expect(scene.photo).toBeNull()
  })
})
```

`web/src/lib/constellation.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { BACKDROP_WIDTH, LINKS, STARS } from '@/lib/constellation'

describe('constellation', () => {
  it('keeps at most 20 % of the stars in the left third, where the text column sits', () => {
    const left = STARS.filter((star) => star.x < BACKDROP_WIDTH / 3)
    expect(left.length / STARS.length).toBeLessThanOrEqual(0.2)
  })

  it('links only existing, distinct stars', () => {
    for (const [from, to] of LINKS) {
      expect(from).not.toBe(to)
      expect(STARS[from]).toBeDefined()
      expect(STARS[to]).toBeDefined()
    }
  })
})
```

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/content.test.ts src/lib/scene.test.ts src/lib/constellation.test.ts`
Expected: FAIL — `alternateLocale` and `constellation` do not exist; `scene.photo` is undefined.

- [ ] **Step 2: Interface copy**

Replace `web/src/content.ts` with:

```ts
/**
 * Interface strings only. Professional content (name, headline, statement,
 * experience, projects, links, CV) comes from the Laravel public API; never add it here.
 */
import type { DeliveryStatus, LanguageLevel, Locale, ProjectKind, WorkMode } from '@/lib/api'
import type { SectionId } from '@/lib/sections'

export type { Locale }

export type UiCopy = {
  skipToContent: string
  navLabel: string
  backToTop: string
  menu: { open: string; close: string; title: string }
  theme: { toDark: string; toLight: string }
  language: { switchTo: string }
  cvLabel: string
  sections: Record<SectionId, { nav: string; title: string }>
  loading: string
  failure: string
  regionFailure: string
  retry: string
  experience: {
    otherCases: string
    caseLabels: { context: string; problem: string; contribution: string; approach: string; outcome: string; technologies: string }
  }
  projects: {
    groups: Record<ProjectKind, string>
    kind: Record<ProjectKind, string>
    status: Record<DeliveryStatus, string>
    labels: { problem: string; solution: string; result: string; stack: string; demo: string; repository: string }
    showMore: (count: number) => string
    gallery: {
      viewFull: string
      previous: string
      next: string
      close: string
      more: (count: number) => string
      image: (position: number, total: number) => string
    }
  }
  about: { education: string; languages: string; location: string; modes: Record<WorkMode, string>; levels: Record<LanguageLevel, string> }
  contact: { newTab: string }
}

/** `/en` and `/en/...` render English; `/`, `/es` and anything else render Spanish. */
export function resolveLocale(pathname: string): Locale {
  return /^\/en(\/|$)/.test(pathname) ? 'en' : 'es'
}

export function alternateLocale(locale: Locale): Locale {
  return locale === 'es' ? 'en' : 'es'
}

export function localePath(locale: Locale, hash = ''): string {
  return `${locale === 'en' ? '/en' : '/'}${hash}`
}

const LEVELS: Omit<Record<LanguageLevel, string>, 'native'> = { a1: 'A1', a2: 'A2', b1: 'B1', b2: 'B2', c1: 'C1', c2: 'C2' }

const COPY: Record<Locale, UiCopy> = {
  es: {
    skipToContent: 'Saltar al contenido',
    navLabel: 'Navegación principal',
    backToTop: 'Volver al inicio',
    menu: { open: 'Menú', close: 'Cerrar menú', title: 'Índice' },
    theme: { toDark: 'Cambiar a tema oscuro', toLight: 'Cambiar a tema claro' },
    language: { switchTo: 'Ver en inglés' },
    cvLabel: 'CV',
    sections: {
      experience: { nav: 'Experiencia', title: 'Experiencia laboral' },
      projects: { nav: 'Proyectos', title: 'Proyectos' },
      stack: { nav: 'Stack', title: 'Stack' },
      about: { nav: 'Sobre mí', title: 'Sobre mí' },
      contact: { nav: 'Contacto', title: 'Contacto' },
    },
    loading: 'Cargando…',
    failure: 'No pudimos cargar el contenido.',
    regionFailure: 'No pudimos cargar esta sección.',
    retry: 'Reintentar',
    experience: {
      otherCases: 'Otros casos',
      caseLabels: {
        context: 'Contexto',
        problem: 'Problema',
        contribution: 'Aporte',
        approach: 'Enfoque técnico',
        outcome: 'Resultado',
        technologies: 'Tecnologías',
      },
    },
    projects: {
      groups: { client: 'Para clientes', personal: 'Personales' },
      kind: { client: 'Para cliente', personal: 'Personal' },
      status: { in_production: 'En producción', in_use: 'En uso', public_demo: 'Demo pública', in_development: 'En desarrollo' },
      labels: { problem: 'Problema', solution: 'Solución', result: 'Resultado', stack: 'Stack', demo: 'Ver demo', repository: 'Ver código' },
      showMore: (count) => `Ver ${count} ${count === 1 ? 'proyecto más' : 'proyectos más'}`,
      gallery: {
        viewFull: 'Ver en tamaño completo',
        previous: 'Captura anterior',
        next: 'Captura siguiente',
        close: 'Cerrar visor',
        more: (count) => `+${count}`,
        image: (position, total) => `Captura ${position} de ${total}`,
      },
    },
    about: {
      education: 'Formación',
      languages: 'Idiomas',
      location: 'Ubicación',
      modes: { on_site: 'Presencial', hybrid: 'Híbrido', remote: 'Remoto' },
      levels: { native: 'Nativo', ...LEVELS },
    },
    contact: { newTab: '(se abre en una pestaña nueva)' },
  },
  en: {
    skipToContent: 'Skip to content',
    navLabel: 'Primary navigation',
    backToTop: 'Back to top',
    menu: { open: 'Menu', close: 'Close menu', title: 'Index' },
    theme: { toDark: 'Switch to dark theme', toLight: 'Switch to light theme' },
    language: { switchTo: 'View in Spanish' },
    cvLabel: 'CV',
    sections: {
      experience: { nav: 'Experience', title: 'Work experience' },
      projects: { nav: 'Projects', title: 'Projects' },
      stack: { nav: 'Stack', title: 'Stack' },
      about: { nav: 'About', title: 'About me' },
      contact: { nav: 'Contact', title: 'Contact' },
    },
    loading: 'Loading…',
    failure: 'We could not load the content.',
    regionFailure: 'We could not load this section.',
    retry: 'Retry',
    experience: {
      otherCases: 'Other cases',
      caseLabels: {
        context: 'Context',
        problem: 'Problem',
        contribution: 'Contribution',
        approach: 'Technical approach',
        outcome: 'Outcome',
        technologies: 'Technologies',
      },
    },
    projects: {
      groups: { client: 'Client projects', personal: 'Personal projects' },
      kind: { client: 'Client', personal: 'Personal' },
      status: { in_production: 'In production', in_use: 'In use', public_demo: 'Public demo', in_development: 'In development' },
      labels: { problem: 'Problem', solution: 'Solution', result: 'Result', stack: 'Stack', demo: 'View demo', repository: 'View code' },
      showMore: (count) => `Show ${count} more ${count === 1 ? 'project' : 'projects'}`,
      gallery: {
        viewFull: 'View full size',
        previous: 'Previous screenshot',
        next: 'Next screenshot',
        close: 'Close viewer',
        more: (count) => `+${count}`,
        image: (position, total) => `Screenshot ${position} of ${total}`,
      },
    },
    about: {
      education: 'Education',
      languages: 'Languages',
      location: 'Location',
      modes: { on_site: 'On-site', hybrid: 'Hybrid', remote: 'Remote' },
      levels: { native: 'Native', ...LEVELS },
    },
    contact: { newTab: '(opens in a new tab)' },
  },
}

export function uiCopy(locale: Locale): UiCopy {
  return COPY[locale]
}
```

- [ ] **Step 3: Scene model and constellation data**

Replace `web/src/lib/scene.ts` with:

```ts
import type { Photo, ProfessionalLink, Statement, StructuralContent, Technology } from '@/lib/api'

export type SceneInput = StructuralContent & { technologies: Technology[] }

/** Presentation model for the scroll scene, derived only from published CMS content. */
export type SceneContent = {
  name: string
  headline: string
  /** Three-tier statement; `null` means render `summary` as a single tier. */
  statement: Statement | null
  summary: string
  /** Closing title; without the CMS closing group it falls back to availability on one line. */
  closing: { lineOne: string; lineTwo: string | null }
  eyebrow: string | null
  photo: Photo | null
  email: ProfessionalLink | null
}

export function buildScene({ profile, site, technologies }: SceneInput): SceneContent {
  const backend = technologies.filter((technology) => technology.category === 'backend').map((technology) => technology.name)

  return {
    name: profile.name,
    headline: profile.headline,
    statement: profile.statement,
    summary: profile.short_summary,
    closing: profile.closing
      ? { lineOne: profile.closing.line_one, lineTwo: profile.closing.line_two }
      : { lineOne: profile.availability, lineTwo: null },
    eyebrow: backend.length > 0 ? backend.join(' | ') : null,
    photo: profile.photo,
    email: site.professional_links.find((link) => link.key === 'email') ?? null,
  }
}
```

`web/src/lib/constellation.ts`:

```ts
/** Backdrop coordinate space; the SVG scales it with `xMidYMid slice`, like the video's `object-fit: cover`. */
export const BACKDROP_WIDTH = 1600
export const BACKDROP_HEIGHT = 900

export type Star = { x: number; y: number; r: number }

/** Night sky over the tree (right two thirds); the left third holds the text column. */
export const STARS: readonly Star[] = [
  { x: 180, y: 140, r: 1.6 },
  { x: 310, y: 90, r: 1.4 },
  { x: 420, y: 260, r: 1.2 },
  { x: 640, y: 120, r: 1.8 },
  { x: 760, y: 230, r: 1.3 },
  { x: 880, y: 80, r: 1.6 },
  { x: 1010, y: 170, r: 2 },
  { x: 1130, y: 95, r: 1.4 },
  { x: 1240, y: 210, r: 1.8 },
  { x: 1350, y: 120, r: 1.3 },
  { x: 1460, y: 250, r: 1.6 },
  { x: 1180, y: 320, r: 1.2 },
  { x: 960, y: 300, r: 1.4 },
  { x: 1400, y: 360, r: 1.2 },
  { x: 700, y: 340, r: 1.1 },
]

export const LINKS: readonly (readonly [number, number])[] = [
  [3, 5],
  [5, 6],
  [6, 7],
  [7, 8],
  [8, 9],
  [9, 10],
  [6, 12],
  [8, 11],
  [10, 13],
  [4, 12],
]
```

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/content.test.ts src/lib/scene.test.ts src/lib/constellation.test.ts`
Expected: PASS.

- [ ] **Step 4: Hooks**

`web/src/hooks/usePrefersReducedMotion.ts`:

```ts
import { useEffect, useState } from 'react'

const QUERY = '(prefers-reduced-motion: reduce)'

export function usePrefersReducedMotion(): boolean {
  const [reduced, setReduced] = useState(() => window.matchMedia(QUERY).matches)

  useEffect(() => {
    const query = window.matchMedia(QUERY)
    const onChange = () => setReduced(query.matches)
    query.addEventListener('change', onChange)
    return () => query.removeEventListener('change', onChange)
  }, [])

  return reduced
}
```

`web/src/hooks/useRegion.ts`:

```ts
import { useCallback, useEffect, useState } from 'react'
import { loadRegion, type Locale, type Region, type RegionData } from '@/lib/api'

export type RegionState<T> = { status: 'loading' } | { status: 'ready'; data: T } | { status: 'error' }

/** Loads one regional collection; its failure never affects the rest of the page. */
export function useRegion<R extends Region>(locale: Locale, region: R): { state: RegionState<RegionData[R]>; retry: () => void } {
  const [state, setState] = useState<RegionState<RegionData[R]>>({ status: 'loading' })
  const [attempt, setAttempt] = useState(0)

  useEffect(() => {
    const controller = new AbortController()
    setState({ status: 'loading' })
    loadRegion(locale, region, controller.signal)
      .then((data) => setState({ status: 'ready', data }))
      .catch(() => {
        if (!controller.signal.aborted) setState({ status: 'error' })
      })
    return () => controller.abort()
  }, [locale, region, attempt])

  const retry = useCallback(() => setAttempt((value) => value + 1), [])

  return { state, retry }
}
```

- [ ] **Step 5: Scene components**

`web/src/components/Stagger.tsx`:

```tsx
import { useEffect, useState, type ReactNode } from 'react'

const ENTRANCE_EASE = 'cubic-bezier(0.16,1,0.3,1)'

/** Child entrance of a beat once its opacity passes STAGGER_THRESHOLD (reduced motion removes the transition in CSS). */
export function Stagger({ visible, delay, className, children }: { visible: boolean; delay: number; className?: string; children: ReactNode }) {
  const [ready, setReady] = useState(false)

  useEffect(() => {
    let second = 0
    const first = requestAnimationFrame(() => {
      second = requestAnimationFrame(() => setReady(true))
    })
    return () => {
      cancelAnimationFrame(first)
      cancelAnimationFrame(second)
    }
  }, [])

  const shown = ready && visible

  return (
    <div
      className={className}
      style={{
        opacity: shown ? 1 : 0,
        transform: shown ? 'translateY(0)' : 'translateY(24px)',
        transition: `opacity 0.8s ${ENTRANCE_EASE} ${delay}ms, transform 0.8s ${ENTRANCE_EASE} ${delay}ms`,
      }}
    >
      {children}
    </div>
  )
}
```

`web/src/components/SceneBackdrop.tsx`:

```tsx
import { BACKDROP_HEIGHT, BACKDROP_WIDTH, LINKS, STARS } from '@/lib/constellation'
import { landscapeOpacity, linksOpacity, ramp, starsOpacity } from '@/lib/scene-timeline'

/** Sketched landscape by day that gives way to a light constellation by night; same in both themes. */
export function SceneBackdrop({ progress, reducedMotion }: { progress: number; reducedMotion: boolean }) {
  const drift = reducedMotion ? 0 : -progress * 40
  const sunDrop = ramp(progress, 0.5, 0.75) * 140

  return (
    <svg
      aria-hidden="true"
      className="pointer-events-none absolute inset-0 h-full w-full"
      viewBox={`0 0 ${BACKDROP_WIDTH} ${BACKDROP_HEIGHT}`}
      preserveAspectRatio="xMidYMid slice"
    >
      <defs>
        <filter id="scene-star-halo" x="-300%" y="-300%" width="700%" height="700%">
          <feGaussianBlur stdDeviation="4" />
        </filter>
      </defs>

      <g style={{ opacity: landscapeOpacity(progress) }} transform={`translate(0 ${drift})`} fill="none" strokeLinecap="round">
        <g stroke="#665244" strokeOpacity={0.24} strokeWidth={1.5}>
          <path d="M0 700 C 220 640 420 660 620 700 S 1000 760 1200 690 S 1500 640 1600 670" />
          <path d="M0 760 C 260 720 520 740 760 770 S 1180 800 1600 740" />
          <path d="M1080 210 c 30 -24 78 -24 102 0 c 26 -12 62 2 64 28 h -190 c -6 -16 6 -28 24 -28 z" />
          <path d="M380 170 c 24 -18 60 -18 80 0 c 20 -9 48 2 50 22 h -150 c -4 -12 4 -22 20 -22 z" />
        </g>
        <circle cx={1320} cy={260 + sunDrop} r={46} stroke="#9A4E2A" strokeOpacity={0.22} strokeWidth={1.5} />
      </g>

      <g style={{ opacity: linksOpacity(progress) }} stroke="#E38B50" strokeOpacity={0.2} strokeWidth={1} strokeDasharray="2 6">
        {LINKS.map(([from, to]) => (
          <line key={`${from}-${to}`} x1={STARS[from].x} y1={STARS[from].y} x2={STARS[to].x} y2={STARS[to].y} />
        ))}
      </g>

      <g style={{ opacity: starsOpacity(progress) }} fill="#E38B50">
        {STARS.map((star) => (
          <g key={`${star.x}-${star.y}`}>
            <circle cx={star.x} cy={star.y} r={star.r * 3.5} fillOpacity={0.32} filter="url(#scene-star-halo)" />
            <circle cx={star.x} cy={star.y} r={star.r} fillOpacity={0.75} />
          </g>
        ))}
      </g>
    </svg>
  )
}
```

`web/src/components/ScrollScene.tsx`:

```tsx
import { ArrowRight } from 'lucide-react'
import type { UiCopy } from '@/content'
import { SceneBackdrop } from '@/components/SceneBackdrop'
import { Stagger } from '@/components/Stagger'
import { usePrefersReducedMotion } from '@/hooks/usePrefersReducedMotion'
import type { SceneContent } from '@/lib/scene'
import { SCROLL_POSTER_SRC, SCROLL_VIDEO_SRC, columnVeilOpacity, correctionFilter } from '@/lib/scene-timeline'
import { STAGGER_THRESHOLD, sectionOneOpacity, sectionThreeOpacity, sectionTwoOpacity } from '@/lib/section-opacity'
import type { useVideoScrub } from '@/useVideoScrub'

type Props = {
  scrub: ReturnType<typeof useVideoScrub>
  scene: SceneContent | null
  status: 'loading' | 'ready' | 'error'
  ui: UiCopy
  onRetry: () => void
}

// Fully transparent beats leave the tab order; their copy stays in the sr-only block.
const inertWhenHidden = (opacity: number) => (opacity === 0 ? { inert: '' } : {})

export function ScrollScene({ scrub, scene, status, ui, onRetry }: Props) {
  const { containerRef, videoRef, canvasRef, scrollProgress: p, canvasLive } = scrub
  const reducedMotion = usePrefersReducedMotion()

  const o1 = sectionOneOpacity(p)
  const o2 = sectionTwoOpacity(p)
  const o3 = sectionThreeOpacity(p)
  const media = 'absolute inset-0 h-full w-full object-cover object-[70%_50%] lg:object-center'

  return (
    <div ref={containerRef} id="top" className="relative h-[500vh]">
      <div className="sticky top-0 h-screen w-full overflow-hidden bg-[#F5EFE6]">
        <div className="absolute inset-0" style={{ filter: correctionFilter(p) }}>
          <video ref={videoRef} src={SCROLL_VIDEO_SRC} poster={SCROLL_POSTER_SRC} muted playsInline preload="auto" aria-hidden="true" className={media} />
          <canvas
            ref={canvasRef}
            width={848}
            height={480}
            aria-hidden="true"
            className={`${media} transition-opacity duration-300 ${canvasLive ? 'opacity-100' : 'opacity-0'}`}
          />
        </div>

        <SceneBackdrop progress={p} reducedMotion={reducedMotion} />

        <div
          aria-hidden="true"
          className="pointer-events-none absolute inset-y-0 left-0 w-full lg:w-1/2"
          style={{
            opacity: columnVeilOpacity(p),
            background: 'linear-gradient(to right, #1A1411 0%, #1A1411 62%, rgba(26, 20, 17, 0) 100%)',
          }}
        />

        {scene && (
          <div className="sr-only">
            <h1>{scene.headline}</h1>
            {scene.photo && <img src={scene.photo.url} alt={scene.photo.alt} />}
            <p>{scene.name}</p>
            <p>{scene.statement ? `${scene.statement.lead} ${scene.statement.emphasis} ${scene.statement.tail}` : scene.summary}</p>
            {scene.eyebrow && <p>{scene.eyebrow}</p>}
            <p>{scene.closing.lineTwo ? `${scene.closing.lineOne} ${scene.closing.lineTwo}` : scene.closing.lineOne}</p>
          </div>
        )}

        <div className="pointer-events-none relative mx-auto flex h-full max-w-content items-center px-6 sm:px-10 lg:px-16">
          <div className="grid w-full lg:max-w-[40%]">
            {status === 'loading' && (
              <p className="label text-scene-ink" style={{ gridArea: '1 / 1' }} role="status">
                {ui.loading}
              </p>
            )}

            {status === 'error' && (
              <div role="alert" className="pointer-events-auto flex flex-col items-start gap-6 text-scene-ink" style={{ gridArea: '1 / 1' }}>
                <p className="label">{ui.failure}</p>
                <button type="button" onClick={onRetry} className="min-h-11 rounded-full border border-scene-ink px-6 text-sm font-semibold">
                  {ui.retry}
                </button>
              </div>
            )}

            {scene && (
              <>
                <section aria-hidden="true" {...inertWhenHidden(o1)} style={{ gridArea: '1 / 1', opacity: o1 }}>
                  {scene.photo && (
                    <Stagger visible={o1 > STAGGER_THRESHOLD} delay={0}>
                      <img
                        src={scene.photo.url}
                        alt=""
                        width={128}
                        height={128}
                        decoding="async"
                        className="h-24 w-24 rounded-full border border-scene-accent object-cover lg:h-32 lg:w-32"
                      />
                    </Stagger>
                  )}
                  <Stagger visible={o1 > STAGGER_THRESHOLD} delay={120}>
                    <p className="label mt-6 text-scene-ink">{scene.name}</p>
                  </Stagger>
                  <Stagger visible={o1 > STAGGER_THRESHOLD} delay={240}>
                    <p className="mt-3 font-display text-[clamp(2.25rem,5vw,4.5rem)] font-light leading-[1.05] text-scene-ink">{scene.headline}</p>
                  </Stagger>
                </section>

                <section aria-hidden="true" {...inertWhenHidden(o2)} style={{ gridArea: '1 / 1', opacity: o2 }}>
                  <Stagger visible={o2 > STAGGER_THRESHOLD} delay={0}>
                    <p className="font-display text-[clamp(1.75rem,3.4vw,3.25rem)] font-light leading-tight text-scene-ink">
                      {scene.statement ? (
                        <>
                          {scene.statement.lead} <em className="italic text-scene-accent">{scene.statement.emphasis}</em>{' '}
                          <span className="text-scene-muted">{scene.statement.tail}</span>
                        </>
                      ) : (
                        scene.summary
                      )}
                    </p>
                  </Stagger>
                </section>

                <section aria-hidden="true" {...inertWhenHidden(o3)} style={{ gridArea: '1 / 1', opacity: o3 }}>
                  {scene.eyebrow && (
                    <Stagger visible={o3 > STAGGER_THRESHOLD} delay={0}>
                      <p className="label text-scene-light">{scene.eyebrow}</p>
                    </Stagger>
                  )}
                  <Stagger visible={o3 > STAGGER_THRESHOLD} delay={150}>
                    <p className="mt-4 font-display text-[clamp(2rem,4vw,3.75rem)] font-light leading-[1.1] text-scene-light">
                      {scene.closing.lineOne}
                      {scene.closing.lineTwo && (
                        <>
                          <br />
                          <span className="italic text-scene-glow">{scene.closing.lineTwo}</span>
                        </>
                      )}
                    </p>
                  </Stagger>
                  {scene.email && (
                    <Stagger visible={o3 > STAGGER_THRESHOLD} delay={300}>
                      {/* Not focusable: keyboard and screen reader users reach the same email in Contact. */}
                      <a
                        href={scene.email.href}
                        tabIndex={-1}
                        className={`mt-8 inline-flex min-h-11 items-center gap-3 font-mono text-xs font-medium uppercase tracking-[0.18em] text-scene-glow ${o3 > STAGGER_THRESHOLD ? 'pointer-events-auto' : ''}`}
                      >
                        {scene.email.label}
                        <ArrowRight size={16} aria-hidden="true" />
                      </a>
                    </Stagger>
                  )}
                </section>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
```

- [ ] **Step 6: App shell**

Replace `web/src/App.tsx` with:

```tsx
import { useCallback, useEffect, useMemo, useState } from 'react'
import { ScrollScene } from '@/components/ScrollScene'
import { resolveLocale, uiCopy } from '@/content'
import { useRegion } from '@/hooks/useRegion'
import { loadStructuralContent, type StructuralContent } from '@/lib/api'
import { buildScene } from '@/lib/scene'
import { SCROLL_VIDEO_SRC } from '@/lib/scene-timeline'
import { useVideoScrub } from '@/useVideoScrub'

type StructuralState = { status: 'loading' } | { status: 'ready'; content: StructuralContent } | { status: 'error' }

export default function App() {
  const locale = useMemo(() => resolveLocale(window.location.pathname), [])
  const ui = uiCopy(locale)
  const scrub = useVideoScrub(SCROLL_VIDEO_SRC)
  const [structural, setStructural] = useState<StructuralState>({ status: 'loading' })
  const [attempt, setAttempt] = useState(0)
  const technologies = useRegion(locale, 'technologies')

  useEffect(() => {
    document.documentElement.lang = locale
  }, [locale])

  useEffect(() => {
    const controller = new AbortController()
    setStructural({ status: 'loading' })
    loadStructuralContent(locale, controller.signal)
      .then((content) => setStructural({ status: 'ready', content }))
      .catch(() => {
        if (!controller.signal.aborted) setStructural({ status: 'error' })
      })
    return () => controller.abort()
  }, [locale, attempt])

  const content = structural.status === 'ready' ? structural.content : null

  useEffect(() => {
    if (content) document.title = `${content.profile.name} — ${content.profile.headline}`
  }, [content])

  const scene = useMemo(
    () =>
      content
        ? buildScene({ ...content, technologies: technologies.state.status === 'ready' ? technologies.state.data : [] })
        : null,
    [content, technologies.state],
  )

  const retryStructural = useCallback(() => setAttempt((value) => value + 1), [])

  return (
    <main id="content" tabIndex={-1} className="outline-none" aria-busy={structural.status === 'loading'}>
      <ScrollScene scrub={scrub} scene={scene} status={structural.status} ui={ui} onRetry={retryStructural} />
    </main>
  )
}
```

- [ ] **Step 7: Run the full front check, look at the scene and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'`
Expected: all green.

Start the dev stack (`export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 up -d`), open http://127.0.0.1:8016 in a visible browser window and scroll the scene once: photo, name and headline on the left; statement with italic emphasis; closing and email CTA in light text over the veil; landscape fading into stars. Note anything off in the task report; detailed QA is Task 11.

```bash
git add web/src/content.ts web/src/content.test.ts web/src/lib/scene.ts web/src/lib/scene.test.ts web/src/lib/constellation.ts web/src/lib/constellation.test.ts web/src/hooks/usePrefersReducedMotion.ts web/src/hooks/useRegion.ts web/src/components/Stagger.tsx web/src/components/SceneBackdrop.tsx web/src/components/ScrollScene.tsx web/src/App.tsx
git commit -m "feat(web): redesign the scroll scene with photo, left column, veils and night backdrop"
```

---

### Task 7: Navigation — bar, mobile index, theme and language controls

Components only; `App` renders them in Task 10 (typecheck compiles every file under `src/`).

**Files:**
- Create: `web/src/hooks/useActiveSection.ts`
- Create: `web/src/components/ThemeToggle.tsx`, `web/src/components/LanguageSwitch.tsx`, `web/src/components/SiteNav.tsx`

**Interfaces:**
- Consumes: `UiCopy`, `alternateLocale`, `localePath` (Task 6); `Theme` (Task 1); `SectionId`, `sectionNumber` (Task 4); `CvLink`, `Photo` (Task 3).
- Produces:
  - `useActiveSection(ids: readonly SectionId[]): SectionId | null` — the section crossing the middle band of the viewport.
  - `ThemeToggle({ ui: UiCopy; theme: Theme; onToggle: () => void })`.
  - `LanguageSwitch({ ui: UiCopy; locale: Locale })` — links to the other language, keeping the current `#anchor`.
  - `SiteNav({ ui, locale, name: string | null, avatar: Photo | null, cv: CvLink | null, sections: SectionId[], activeSection: SectionId | null, overScene: boolean, lightText: boolean, veilOpacity: number, theme: Theme, onToggleTheme: () => void })`.

- [ ] **Step 1: Active section hook**

`web/src/hooks/useActiveSection.ts`:

```ts
import { useEffect, useState } from 'react'
import type { SectionId } from '@/lib/sections'

/** The section crossing a band just above the middle of the viewport. */
export function useActiveSection(ids: readonly SectionId[]): SectionId | null {
  const [active, setActive] = useState<SectionId | null>(null)
  const key = ids.join('|')

  useEffect(() => {
    const elements = key
      .split('|')
      .map((id) => document.getElementById(id))
      .filter((element): element is HTMLElement => element !== null)

    if (elements.length === 0) {
      setActive(null)
      return
    }

    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) setActive(entry.target.id as SectionId)
        }
      },
      { rootMargin: '-40% 0px -55% 0px' },
    )
    elements.forEach((element) => observer.observe(element))

    return () => observer.disconnect()
  }, [key])

  return active
}
```

- [ ] **Step 2: Theme and language controls**

`web/src/components/ThemeToggle.tsx`:

```tsx
import { Moon, Sun } from 'lucide-react'
import type { UiCopy } from '@/content'
import type { Theme } from '@/lib/theme'

export function ThemeToggle({ ui, theme, onToggle }: { ui: UiCopy; theme: Theme; onToggle: () => void }) {
  const label = theme === 'dark' ? ui.theme.toLight : ui.theme.toDark

  return (
    <button
      type="button"
      onClick={onToggle}
      aria-label={label}
      title={label}
      className="flex h-11 w-11 items-center justify-center rounded-full transition-opacity hover:opacity-70"
    >
      {theme === 'dark' ? <Sun size={18} aria-hidden="true" /> : <Moon size={18} aria-hidden="true" />}
    </button>
  )
}
```

`web/src/components/LanguageSwitch.tsx`:

```tsx
import { alternateLocale, localePath, type Locale, type UiCopy } from '@/content'

const LOCALES: readonly Locale[] = ['es', 'en']

/** "ES / EN": the current language is boxed; the other one opens the same section in that language. */
export function LanguageSwitch({ ui, locale }: { ui: UiCopy; locale: Locale }) {
  const target = alternateLocale(locale)

  return (
    <span className="flex items-center font-mono text-xs font-medium">
      {LOCALES.map((option, index) => (
        <span key={option} className="flex items-center">
          {index > 0 && (
            <span aria-hidden="true" className="px-1 opacity-60">
              /
            </span>
          )}
          {option === locale ? (
            <span aria-current="true" lang={option} className="flex min-h-11 items-center">
              <span className="rounded-sm border border-current px-1.5 py-0.5">{option.toUpperCase()}</span>
            </span>
          ) : (
            <a
              href={localePath(target)}
              hrefLang={target}
              lang={target}
              aria-label={ui.language.switchTo}
              // The anchor is read at click time so the other language opens on the same section.
              onClick={(event) => {
                event.currentTarget.href = localePath(target, window.location.hash)
              }}
              className="flex min-h-11 min-w-9 items-center justify-center px-1.5 transition-opacity hover:opacity-70"
            >
              {option.toUpperCase()}
            </a>
          )}
        </span>
      ))}
    </span>
  )
}
```

- [ ] **Step 3: Site navigation**

`web/src/components/SiteNav.tsx`:

```tsx
import { Menu, X } from 'lucide-react'
import { useEffect, useRef, useState, type MouseEvent } from 'react'
import { LanguageSwitch } from '@/components/LanguageSwitch'
import { ThemeToggle } from '@/components/ThemeToggle'
import type { Locale, UiCopy } from '@/content'
import type { CvLink, Photo } from '@/lib/api'
import { sectionNumber, type SectionId } from '@/lib/sections'
import type { Theme } from '@/lib/theme'

type Props = {
  ui: UiCopy
  locale: Locale
  name: string | null
  avatar: Photo | null
  cv: CvLink | null
  sections: SectionId[]
  activeSection: SectionId | null
  /** True while the scroll scene fills the viewport: transparent bar tinted by the frame. */
  overScene: boolean
  lightText: boolean
  veilOpacity: number
  theme: Theme
  onToggleTheme: () => void
}

export function SiteNav({ ui, locale, name, avatar, cv, sections, activeSection, overScene, lightText, veilOpacity, theme, onToggleTheme }: Props) {
  const dialogRef = useRef<HTMLDialogElement>(null)
  const menuButtonRef = useRef<HTMLButtonElement>(null)
  const [menuOpen, setMenuOpen] = useState(false)

  useEffect(() => {
    const dialog = dialogRef.current
    if (!dialog) return
    if (menuOpen && !dialog.open) dialog.showModal()
    if (!menuOpen && dialog.open) dialog.close()
    document.body.style.overflow = menuOpen ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [menuOpen])

  useEffect(() => {
    const desktop = window.matchMedia('(min-width: 1024px)')
    const onChange = () => {
      if (desktop.matches) setMenuOpen(false)
    }
    desktop.addEventListener('change', onChange)
    return () => desktop.removeEventListener('change', onChange)
  }, [])

  // Close the modal before scrolling: a modal dialog and the scroll lock would otherwise swallow the jump.
  const navigateFromMenu = (event: MouseEvent<HTMLAnchorElement>, id: SectionId) => {
    event.preventDefault()
    dialogRef.current?.close()
    document.body.style.overflow = ''
    document.getElementById(id)?.scrollIntoView()
    window.history.pushState(null, '', `#${id}`)
  }

  const tone = overScene ? (lightText ? 'text-scene-light' : 'text-scene-ink') : 'text-ink'
  const secondaryControls = (
    <>
      <LanguageSwitch ui={ui} locale={locale} />
      <ThemeToggle ui={ui} theme={theme} onToggle={onToggleTheme} />
      {cv && (
        <a href={cv.url} aria-label={cv.label} className="flex min-h-11 items-center px-1 transition-opacity hover:opacity-70">
          <span className="rounded-sm border border-current px-2 py-0.5 font-mono text-xs font-medium">{ui.cvLabel}</span>
        </a>
      )}
    </>
  )

  return (
    <header className={`fixed inset-x-0 top-0 z-50 transition-colors duration-300 ${overScene ? 'bg-transparent' : 'border-b border-line bg-canvas'}`}>
      <a
        href="#content"
        className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-[60] focus:rounded focus:bg-canvas focus:px-4 focus:py-2 focus:text-ink"
      >
        {ui.skipToContent}
      </a>

      {overScene && (
        <div aria-hidden="true" className="pointer-events-none absolute inset-0 bg-scene-veil transition-opacity duration-150" style={{ opacity: veilOpacity }} />
      )}

      <nav aria-label={ui.navLabel} className={`relative mx-auto flex h-16 max-w-content items-center justify-between gap-6 px-6 transition-colors duration-500 sm:px-10 lg:px-16 ${tone}`}>
        <a href="#top" className="flex min-h-11 items-center gap-3">
          {avatar && (
            <img
              src={avatar.url}
              alt=""
              width={28}
              height={28}
              decoding="async"
              className={`h-7 rounded-full object-cover transition-[opacity,width] duration-300 ${overScene ? 'w-0 opacity-0' : 'w-7 opacity-100'}`}
            />
          )}
          <span className="font-display text-lg font-light">{name ?? ui.backToTop}</span>
        </a>

        <ul className="hidden items-center gap-8 lg:flex">
          {sections.map((id) => {
            const active = !overScene && activeSection === id
            return (
              <li key={id}>
                <a href={`#${id}`} aria-current={active ? 'location' : undefined} className="relative flex min-h-11 items-center gap-2 text-sm font-semibold">
                  {active && <span className="label text-accent">{sectionNumber(id)}</span>}
                  {ui.sections[id].nav}
                  {active && <span aria-hidden="true" className="absolute inset-x-0 bottom-2 h-0.5 bg-accent" />}
                </a>
              </li>
            )
          })}
        </ul>

        <div className="hidden items-center gap-2 lg:flex">{secondaryControls}</div>

        <button
          ref={menuButtonRef}
          type="button"
          onClick={() => setMenuOpen(true)}
          aria-haspopup="dialog"
          aria-expanded={menuOpen}
          className="flex min-h-11 items-center gap-2 text-sm font-semibold lg:hidden"
        >
          <Menu size={18} aria-hidden="true" />
          {ui.menu.open}
        </button>
      </nav>

      <dialog
        ref={dialogRef}
        aria-label={ui.menu.title}
        onClose={() => {
          setMenuOpen(false)
          menuButtonRef.current?.focus()
        }}
        className="m-0 h-full max-h-none w-full max-w-none bg-canvas p-0 text-ink backdrop:bg-scene-veil/60"
      >
        <div className="flex h-full flex-col px-6 pb-10 pt-4 sm:px-10">
          <div className="flex h-12 items-center justify-between">
            <span className="label text-muted">{ui.menu.title}</span>
            <button
              type="button"
              onClick={() => setMenuOpen(false)}
              aria-label={ui.menu.close}
              className="flex h-11 w-11 items-center justify-center rounded-full border border-line"
            >
              <X size={18} aria-hidden="true" />
            </button>
          </div>

          <ol className="flex flex-1 flex-col justify-center gap-2">
            {sections.map((id) => (
              <li key={id}>
                <a href={`#${id}`} onClick={(event) => navigateFromMenu(event, id)} className="flex min-h-11 items-baseline gap-4 py-2">
                  <span className="label text-accent">{sectionNumber(id)}</span>
                  <span className="font-display text-3xl font-light">{ui.sections[id].nav}</span>
                </a>
              </li>
            ))}
          </ol>

          <div className="flex flex-wrap items-center gap-3 border-t border-line pt-6">{secondaryControls}</div>
        </div>
      </dialog>
    </header>
  )
}
```

- [ ] **Step 4: Run the full front check and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'`
Expected: all green.

```bash
git add web/src/hooks/useActiveSection.ts web/src/components/ThemeToggle.tsx web/src/components/LanguageSwitch.tsx web/src/components/SiteNav.tsx
git commit -m "feat(web): add the site navigation with mobile index, theme and language controls"
```

---

### Task 8: Dawn transition, section shell, experience timeline and stack

**Files:**
- Modify: `web/src/lib/sections.ts`, `web/src/lib/sections.test.ts`, `web/src/index.css`
- Create: `web/src/components/DawnTransition.tsx`, `web/src/components/Section.tsx`, `web/src/components/RegionStatus.tsx`, `web/src/components/Accordion.tsx`, `web/src/components/TechnologyChips.tsx`, `web/src/components/ExperienceSection.tsx`, `web/src/components/StackSection.tsx`

**Interfaces:**
- Consumes: `RegionState` (Task 6), `groupExperience`, `groupTechnologies` (Task 4), `formatPeriod` (Task 4), `UiCopy` (Task 6).
- Produces:
  - `@/lib/sections`: `combineStatus(...statuses: RegionStatus[]): RegionStatus` (error wins over loading, loading over ready).
  - `DawnTransition()`; `Section({ id: SectionId; label: string; title: string; children })` renders `<section id aria-labelledby>` with the `0N · Label` eyebrow and the H2; `RegionStatus({ ui; status: 'loading' | 'error'; onRetry })`; `Accordion({ title: string; children })` (H4 button with `aria-expanded`); `TechnologyChips({ label: string; technologies: Technology[] })`.
  - `ExperienceSection({ ui: UiCopy; locale: Locale; experiences: RegionState<Experience[]>; workCases: RegionState<WorkCase[]>; onRetry: () => void })`.
  - `StackSection({ ui: UiCopy; technologies: RegionState<Technology[]>; groups: TechnologyGroup[]; onRetry: () => void })`.

- [ ] **Step 1: Write the failing test**

Append to `web/src/lib/sections.test.ts` (add `combineStatus` to the import list):

```ts
describe('combineStatus', () => {
  it('fails if any region failed, loads while any loads, and is ready only when all are', () => {
    expect(combineStatus('ready', 'error', 'loading')).toBe('error')
    expect(combineStatus('ready', 'loading')).toBe('loading')
    expect(combineStatus('ready', 'ready')).toBe('ready')
  })
})
```

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/lib/sections.test.ts`
Expected: FAIL — `combineStatus` is not exported.

- [ ] **Step 2: Implement `combineStatus`**

Append to `web/src/lib/sections.ts`:

```ts
/** A section fed by several regions (experience needs experiences and work cases). */
export function combineStatus(...statuses: RegionStatus[]): RegionStatus {
  if (statuses.includes('error')) return 'error'
  if (statuses.includes('loading')) return 'loading'
  return 'ready'
}
```

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web pnpm vitest run src/lib/sections.test.ts`
Expected: PASS.

- [ ] **Step 3: Dawn and timeline node styles**

Append to `web/src/index.css`, before the `@media (prefers-reduced-motion: reduce)` block:

```css
/* Scene → sections seam: the night frame fades into the page canvas. */
.dawn {
  height: 24vh;
  background: linear-gradient(to bottom, #1d140f 0%, #5a3f2e 35%, #c9ae93 70%, rgb(var(--color-canvas)) 100%);
}

[data-theme='dark'] .dawn {
  height: 32px;
  background: linear-gradient(to bottom, #1d140f, rgb(var(--color-canvas)));
}

/* Timeline nodes: a soft ring by day; in the dark theme, the glow of the tree nodes. */
.timeline-node {
  box-shadow: 0 0 0 3px rgb(154 78 42 / 0.18);
}

[data-theme='dark'] .timeline-node {
  box-shadow:
    0 0 0 4px rgb(227 139 80 / 0.22),
    0 0 14px rgb(227 139 80 / 0.55);
}
```

- [ ] **Step 4: Shared section components**

`web/src/components/DawnTransition.tsx`:

```tsx
export function DawnTransition() {
  return <div aria-hidden="true" className="dawn" />
}
```

`web/src/components/Section.tsx`:

```tsx
import type { ReactNode } from 'react'
import { sectionNumber, type SectionId } from '@/lib/sections'

export function Section({ id, label, title, children }: { id: SectionId; label: string; title: string; children: ReactNode }) {
  return (
    <section id={id} aria-labelledby={`${id}-title`} className="scroll-mt-16 px-6 py-24 sm:px-10 lg:px-16 lg:py-32">
      <div className="mx-auto max-w-content">
        <p className="label text-accent">
          {sectionNumber(id)} · {label}
        </p>
        <h2 id={`${id}-title`} className="mt-4 font-display text-[clamp(2.25rem,4.5vw,4rem)] font-light leading-[1.05]">
          {title}
        </h2>
        <div className="mt-12 lg:mt-16">{children}</div>
      </div>
    </section>
  )
}
```

`web/src/components/RegionStatus.tsx`:

```tsx
import type { UiCopy } from '@/content'

export function RegionStatus({ ui, status, onRetry }: { ui: UiCopy; status: 'loading' | 'error'; onRetry: () => void }) {
  if (status === 'loading') {
    return (
      <p role="status" className="label text-muted">
        {ui.loading}
      </p>
    )
  }

  return (
    <div role="alert" className="flex flex-col items-start gap-4 rounded-2xl border border-line bg-surface p-6">
      <p className="text-muted">{ui.regionFailure}</p>
      <button type="button" onClick={onRetry} className="min-h-11 rounded-full border border-line px-5 text-sm font-semibold">
        {ui.retry}
      </button>
    </div>
  )
}
```

`web/src/components/Accordion.tsx`:

```tsx
import { Minus, Plus } from 'lucide-react'
import { useId, useState, type ReactNode } from 'react'

/** Independent disclosure: several can be open at once. */
export function Accordion({ title, children }: { title: string; children: ReactNode }) {
  const [open, setOpen] = useState(false)
  const panelId = useId()

  return (
    <div className="border-t border-line last:border-b">
      <h4>
        <button
          type="button"
          aria-expanded={open}
          aria-controls={panelId}
          onClick={() => setOpen((value) => !value)}
          className="flex min-h-11 w-full items-center justify-between gap-6 py-4 text-left font-semibold aria-expanded:text-accent"
        >
          <span>{title}</span>
          {open ? <Minus size={18} aria-hidden="true" className="shrink-0 text-accent" /> : <Plus size={18} aria-hidden="true" className="shrink-0 text-accent" />}
        </button>
      </h4>
      <div id={panelId} hidden={!open} className="pb-8">
        {children}
      </div>
    </div>
  )
}
```

`web/src/components/TechnologyChips.tsx`:

```tsx
import type { Technology } from '@/lib/api'

export function TechnologyChips({ label, technologies }: { label: string; technologies: Technology[] }) {
  if (technologies.length === 0) return null

  return (
    <ul aria-label={label} className="mt-6 flex flex-wrap gap-2">
      {technologies.map((technology) => (
        <li key={technology.key} className="rounded-full border border-line px-3 py-1 font-mono text-xs">
          {technology.name}
        </li>
      ))}
    </ul>
  )
}
```

- [ ] **Step 5: Experience timeline**

`web/src/components/ExperienceSection.tsx`:

```tsx
import { Accordion } from '@/components/Accordion'
import { RegionStatus } from '@/components/RegionStatus'
import { Section } from '@/components/Section'
import { TechnologyChips } from '@/components/TechnologyChips'
import type { Locale, UiCopy } from '@/content'
import type { RegionState } from '@/hooks/useRegion'
import type { Experience, WorkCase } from '@/lib/api'
import { formatPeriod } from '@/lib/format'
import { groupExperience } from '@/lib/grouping'
import { combineStatus } from '@/lib/sections'

type Props = {
  ui: UiCopy
  locale: Locale
  experiences: RegionState<Experience[]>
  workCases: RegionState<WorkCase[]>
  onRetry: () => void
}

function CaseDetails({ ui, workCase }: { ui: UiCopy; workCase: WorkCase }) {
  const labels = ui.experience.caseLabels
  const blocks = [
    [labels.problem, workCase.problem],
    [labels.contribution, workCase.contribution],
    [labels.approach, workCase.technical_approach],
    [labels.outcome, workCase.outcome],
  ] as const

  return (
    <>
      <p className="label text-accent">{labels.context}</p>
      <p className="mt-2 max-w-3xl text-muted">{workCase.context}</p>
      <dl className="mt-6 grid gap-6 md:grid-cols-2">
        {blocks.map(([label, text]) => (
          <div key={label}>
            <dt className="label text-accent">{label}</dt>
            <dd className="mt-2">{text}</dd>
          </div>
        ))}
      </dl>
      <TechnologyChips label={labels.technologies} technologies={workCase.technologies} />
    </>
  )
}

export function ExperienceSection({ ui, locale, experiences, workCases, onRetry }: Props) {
  const status = combineStatus(experiences.status, workCases.status)

  return (
    <Section id="experience" label={ui.sections.experience.nav} title={ui.sections.experience.title}>
      {status !== 'ready' || experiences.status !== 'ready' || workCases.status !== 'ready' ? (
        <RegionStatus ui={ui} status={status === 'ready' ? 'loading' : status} onRetry={onRetry} />
      ) : (
        <Timeline ui={ui} locale={locale} experiences={experiences.data} workCases={workCases.data} />
      )}
    </Section>
  )
}

function Timeline({ ui, locale, experiences, workCases }: { ui: UiCopy; locale: Locale; experiences: Experience[]; workCases: WorkCase[] }) {
  const { organizations, unlinkedCases } = groupExperience(experiences, workCases)

  return (
    <>
      <ol className="relative space-y-16 border-l border-node pl-8 lg:pl-12">
        {organizations.map((group) => (
          <li key={group.key}>
            {group.organization && <h3 className="font-display text-3xl font-light">{group.organization}</h3>}
            <ol className="mt-8 space-y-14">
              {group.roles.map(({ experience, cases }) => (
                <li key={experience.key} className="relative">
                  <span aria-hidden="true" className="timeline-node absolute -left-[calc(2rem+5px)] top-1.5 h-2.5 w-2.5 rounded-full bg-node lg:-left-[calc(3rem+5px)]" />
                  <p className="label text-muted">{formatPeriod(experience.start, experience.end, locale)}</p>
                  <h4 className="mt-2 font-display text-2xl font-light">{experience.role}</h4>
                  <p className="mt-3 max-w-3xl text-muted">{experience.summary}</p>
                  {experience.highlights.length > 0 && (
                    <ul className="mt-4 max-w-3xl list-disc space-y-2 pl-5 marker:text-accent">
                      {experience.highlights.map((highlight) => (
                        <li key={highlight}>{highlight}</li>
                      ))}
                    </ul>
                  )}
                  {cases.length > 0 && (
                    <div className="mt-8 max-w-4xl">
                      {cases.map((workCase) => (
                        <Accordion key={workCase.key} title={workCase.title}>
                          <CaseDetails ui={ui} workCase={workCase} />
                        </Accordion>
                      ))}
                    </div>
                  )}
                </li>
              ))}
            </ol>
          </li>
        ))}
      </ol>

      {unlinkedCases.length > 0 && (
        <div className="mt-20 max-w-4xl">
          <h3 className="font-display text-2xl font-light">{ui.experience.otherCases}</h3>
          <div className="mt-6">
            {unlinkedCases.map((workCase) => (
              <Accordion key={workCase.key} title={workCase.title}>
                <CaseDetails ui={ui} workCase={workCase} />
              </Accordion>
            ))}
          </div>
        </div>
      )}
    </>
  )
}
```

- [ ] **Step 6: Stack**

`web/src/components/StackSection.tsx`:

```tsx
import { RegionStatus } from '@/components/RegionStatus'
import { Section } from '@/components/Section'
import type { UiCopy } from '@/content'
import type { RegionState } from '@/hooks/useRegion'
import type { Technology, TechnologyGroup } from '@/lib/api'
import { groupTechnologies } from '@/lib/grouping'

type Props = { ui: UiCopy; technologies: RegionState<Technology[]>; groups: TechnologyGroup[]; onRetry: () => void }

export function StackSection({ ui, technologies, groups, onRetry }: Props) {
  return (
    <Section id="stack" label={ui.sections.stack.nav} title={ui.sections.stack.title}>
      {technologies.status !== 'ready' ? (
        <RegionStatus ui={ui} status={technologies.status} onRetry={onRetry} />
      ) : (
        <ul className="grid grid-cols-2 gap-x-8 gap-y-12 lg:grid-cols-4">
          {groupTechnologies(technologies.data, groups).map((column) => (
            <li key={column.key}>
              <h3 className="label text-accent">{column.label}</h3>
              <ul className="mt-4 space-y-2">
                {column.names.map((name) => (
                  <li key={name} className="text-lg">
                    {name}
                  </li>
                ))}
              </ul>
            </li>
          ))}
        </ul>
      )}
    </Section>
  )
}
```

- [ ] **Step 7: Run the full front check and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'`
Expected: all green.

```bash
git add web/src/lib/sections.ts web/src/lib/sections.test.ts web/src/index.css web/src/components/DawnTransition.tsx web/src/components/Section.tsx web/src/components/RegionStatus.tsx web/src/components/Accordion.tsx web/src/components/TechnologyChips.tsx web/src/components/ExperienceSection.tsx web/src/components/StackSection.tsx
git commit -m "feat(web): add the dawn seam, experience timeline with case accordions and stack columns"
```

---

### Task 9: Projects — dossiers, gallery viewer and "show more"

**Files:**
- Create: `web/src/components/ProjectGallery.tsx`, `web/src/components/ProjectDossier.tsx`, `web/src/components/ProjectsSection.tsx`

**Interfaces:**
- Consumes: `groupProjects`, `previewItems`, `PROJECT_PREVIEW_LIMIT` (Task 4); `thumbnailSlots`, `wrapIndex` (Task 4); `Section`, `RegionStatus`, `TechnologyChips` (Task 8); `RegionState` (Task 6).
- Produces:
  - `ProjectGallery({ ui: UiCopy; title: string; images: ProjectImage[] })` — main 16:10 screenshot, four tiles (every image up to four; otherwise three thumbnails and a `+N` tile that opens the viewer), a "view full size" button, and a native `<dialog>` viewer with previous/next, arrow keys and Escape.
  - `ProjectDossier({ ui: UiCopy; project: Project })` — `<article id="project-{key}">` with meta line, H3, summary, problem/solution/result, stack chips, demo and repository links, and the gallery.
  - `ProjectsSection({ ui: UiCopy; projects: RegionState<Project[]>; emptyMessage: string; onRetry: () => void })` — groups `#client-projects` then `#personal-projects`, three per group before "show more".

- [ ] **Step 1: Gallery**

`web/src/components/ProjectGallery.tsx`:

```tsx
import { ChevronLeft, ChevronRight, Maximize2, X } from 'lucide-react'
import { useRef, useState } from 'react'
import type { UiCopy } from '@/content'
import type { ProjectImage } from '@/lib/api'
import { thumbnailSlots, wrapIndex } from '@/lib/gallery'

type Props = { ui: UiCopy; title: string; images: ProjectImage[] }

export function ProjectGallery({ ui, title, images }: Props) {
  const [selected, setSelected] = useState(0)
  const [viewerIndex, setViewerIndex] = useState(0)
  const dialogRef = useRef<HTMLDialogElement>(null)
  const triggerRef = useRef<HTMLElement | null>(null)
  const copy = ui.projects.gallery
  const { visible, overflow } = thumbnailSlots(images.length)
  const main = images[wrapIndex(selected, images.length)]
  const current = images[wrapIndex(viewerIndex, images.length)]

  const openViewer = (index: number, trigger: HTMLElement) => {
    triggerRef.current = trigger
    setViewerIndex(index)
    dialogRef.current?.showModal()
  }

  const step = (delta: number) => setViewerIndex((index) => wrapIndex(index + delta, images.length))

  return (
    <div>
      <div className="overflow-hidden rounded-2xl border border-line bg-surface">
        <img src={main.url} alt={main.alt} width={1600} height={1000} loading="lazy" decoding="async" className="aspect-[16/10] w-full object-cover" />
      </div>

      {visible > 0 && (
        // Four tiles on desktop; a horizontal carousel on narrow screens.
        <div className="mt-3 flex gap-2 overflow-x-auto pb-1 lg:grid lg:grid-cols-4 lg:overflow-visible lg:pb-0">
          {images.slice(0, visible).map((image, index) => (
            <button
              key={image.url}
              type="button"
              onClick={() => setSelected(index)}
              aria-current={index === selected ? 'true' : undefined}
              aria-label={copy.image(index + 1, images.length)}
              className={`aspect-[16/10] w-28 shrink-0 overflow-hidden rounded-lg border-2 lg:w-auto ${index === selected ? 'border-accent' : 'border-line'}`}
            >
              <img src={image.url} alt="" width={160} height={100} loading="lazy" decoding="async" className="h-full w-full object-cover" />
            </button>
          ))}

          {overflow > 0 && (
            <button
              type="button"
              onClick={(event) => openViewer(visible, event.currentTarget)}
              aria-label={`${copy.more(overflow)} · ${copy.viewFull}`}
              className="flex aspect-[16/10] w-28 shrink-0 items-center justify-center rounded-lg border-2 border-line font-mono text-sm lg:w-auto"
            >
              {copy.more(overflow)}
            </button>
          )}
        </div>
      )}

      <button
        type="button"
        onClick={(event) => openViewer(selected, event.currentTarget)}
        className="mt-2 inline-flex min-h-11 items-center gap-2 text-sm font-semibold"
      >
        <Maximize2 size={16} aria-hidden="true" />
        {copy.viewFull}
      </button>

      <dialog
        ref={dialogRef}
        aria-label={title}
        onClose={() => triggerRef.current?.focus()}
        onKeyDown={(event) => {
          if (event.key === 'ArrowLeft') step(-1)
          if (event.key === 'ArrowRight') step(1)
        }}
        className="m-auto max-h-[92vh] w-[min(92vw,1400px)] max-w-none overflow-hidden rounded-2xl bg-canvas p-0 text-ink backdrop:bg-scene-veil/80"
      >
        <div className="flex items-center justify-between gap-4 border-b border-line px-4 py-2">
          <p className="label text-muted" aria-live="polite">
            {copy.image(wrapIndex(viewerIndex, images.length) + 1, images.length)}
          </p>
          <button type="button" onClick={() => dialogRef.current?.close()} aria-label={copy.close} className="flex h-11 w-11 items-center justify-center rounded-full">
            <X size={18} aria-hidden="true" />
          </button>
        </div>

        <img src={current.url} alt={current.alt} decoding="async" className="max-h-[75vh] w-full object-contain" />

        {images.length > 1 && (
          <div className="flex justify-between px-4 py-2">
            <button type="button" onClick={() => step(-1)} aria-label={copy.previous} className="flex h-11 w-11 items-center justify-center rounded-full border border-line">
              <ChevronLeft size={18} aria-hidden="true" />
            </button>
            <button type="button" onClick={() => step(1)} aria-label={copy.next} className="flex h-11 w-11 items-center justify-center rounded-full border border-line">
              <ChevronRight size={18} aria-hidden="true" />
            </button>
          </div>
        )}
      </dialog>
    </div>
  )
}
```

- [ ] **Step 2: Dossier**

`web/src/components/ProjectDossier.tsx`:

```tsx
import { ArrowUpRight } from 'lucide-react'
import { ProjectGallery } from '@/components/ProjectGallery'
import { TechnologyChips } from '@/components/TechnologyChips'
import type { UiCopy } from '@/content'
import type { Project } from '@/lib/api'

function ExternalLink({ href, label, newTab }: { href: string; label: string; newTab: string }) {
  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      className="inline-flex min-h-11 items-center gap-2 rounded-full border border-line px-5 text-sm font-semibold transition-colors hover:border-accent"
    >
      {label}
      <ArrowUpRight size={16} aria-hidden="true" />
      <span className="sr-only"> {newTab}</span>
    </a>
  )
}

export function ProjectDossier({ ui, project }: { ui: UiCopy; project: Project }) {
  const labels = ui.projects.labels
  const meta = [ui.projects.kind[project.kind], project.client_name, project.role, ui.projects.status[project.status]].filter(
    (part): part is string => Boolean(part),
  )
  const blocks = [
    [labels.problem, project.problem],
    [labels.solution, project.solution],
    [labels.result, project.result],
  ] as const

  return (
    <article
      id={`project-${project.key}`}
      tabIndex={-1}
      aria-labelledby={`project-${project.key}-title`}
      className="grid scroll-mt-20 gap-10 outline-none lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] lg:gap-16"
    >
      <div>
        <p className="label text-muted">{meta.join(' · ')}</p>
        <h3 id={`project-${project.key}-title`} className="mt-3 font-display text-4xl font-light leading-tight">
          {project.title}
        </h3>
        <p className="mt-4 text-lg text-muted">{project.summary}</p>

        <dl className="mt-8 space-y-6">
          {blocks.map(([label, text]) => (
            <div key={label}>
              <dt className="label text-accent">{label}</dt>
              <dd className="mt-2">{text}</dd>
            </div>
          ))}
        </dl>

        <TechnologyChips label={labels.stack} technologies={project.technologies} />

        {(project.demo_url || project.repository_url) && (
          <div className="mt-8 flex flex-wrap gap-3">
            {project.demo_url && <ExternalLink href={project.demo_url} label={labels.demo} newTab={ui.contact.newTab} />}
            {project.repository_url && <ExternalLink href={project.repository_url} label={labels.repository} newTab={ui.contact.newTab} />}
          </div>
        )}
      </div>

      {project.images.length > 0 && <ProjectGallery ui={ui} title={project.title} images={project.images} />}
    </article>
  )
}
```

- [ ] **Step 3: Projects section**

`web/src/components/ProjectsSection.tsx`:

```tsx
import { useEffect, useState } from 'react'
import { ProjectDossier } from '@/components/ProjectDossier'
import { RegionStatus } from '@/components/RegionStatus'
import { Section } from '@/components/Section'
import type { UiCopy } from '@/content'
import type { RegionState } from '@/hooks/useRegion'
import type { Project, ProjectKind } from '@/lib/api'
import { PROJECT_PREVIEW_LIMIT, groupProjects, previewItems } from '@/lib/grouping'

type Props = { ui: UiCopy; projects: RegionState<Project[]>; emptyMessage: string; onRetry: () => void }

const KINDS: readonly ProjectKind[] = ['client', 'personal']

function ProjectGroup({ ui, kind, projects }: { ui: UiCopy; kind: ProjectKind; projects: Project[] }) {
  const [expanded, setExpanded] = useState(false)
  const { visible, hiddenCount } = previewItems(projects, expanded)

  // Move focus to the first revealed project so keyboard users continue from there.
  useEffect(() => {
    if (expanded) document.getElementById(`project-${projects[PROJECT_PREVIEW_LIMIT]?.key}`)?.focus()
  }, [expanded, projects])

  return (
    <section id={`${kind}-projects`} aria-label={ui.projects.groups[kind]} className="scroll-mt-20">
      <p className="label text-muted">{ui.projects.groups[kind]}</p>
      <div className="mt-10 space-y-24">
        {visible.map((project) => (
          <ProjectDossier key={project.key} ui={ui} project={project} />
        ))}
      </div>
      {hiddenCount > 0 && (
        <button type="button" onClick={() => setExpanded(true)} className="mt-14 min-h-11 rounded-full border border-line px-6 text-sm font-semibold transition-colors hover:border-accent">
          {ui.projects.showMore(hiddenCount)}
        </button>
      )}
    </section>
  )
}

export function ProjectsSection({ ui, projects, emptyMessage, onRetry }: Props) {
  const groups = projects.status === 'ready' ? groupProjects(projects.data) : null

  return (
    <Section id="projects" label={ui.sections.projects.nav} title={ui.sections.projects.title}>
      {projects.status !== 'ready' ? (
        <RegionStatus ui={ui} status={projects.status} onRetry={onRetry} />
      ) : groups && groups.client.length + groups.personal.length === 0 ? (
        <p className="max-w-2xl text-lg text-muted">{emptyMessage}</p>
      ) : (
        <div className="space-y-28">
          {KINDS.filter((kind) => groups && groups[kind].length > 0).map((kind) => (
            <ProjectGroup key={kind} ui={ui} kind={kind} projects={groups ? groups[kind] : []} />
          ))}
        </div>
      )}
    </Section>
  )
}
```

- [ ] **Step 4: Run the full front check and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'`
Expected: all green.

```bash
git add web/src/components/ProjectGallery.tsx web/src/components/ProjectDossier.tsx web/src/components/ProjectsSection.tsx
git commit -m "feat(web): add project dossiers with screenshot gallery viewer and show more"
```

---

### Task 10: About, contact and the final page composition

**Files:**
- Create: `web/src/components/AboutSection.tsx`, `web/src/components/ContactSection.tsx`
- Modify: `web/src/App.tsx` (full rewrite)

**Interfaces:**
- Consumes: every component and helper from Tasks 1–9: `useTheme`, `useRegion`, `useActiveSection`, `useVideoScrub`, `SiteNav`, `ScrollScene`, `DawnTransition`, `ExperienceSection`, `ProjectsSection`, `StackSection`, `Section`, `visibleSections`, `combineStatus`, `hasAboutContent`, `hasContactContent`, `aboutStatement`, `formatYears`, `emailAddress`, `navVeilOpacity`, `NAV_LIGHT_THRESHOLD`.
- Produces:
  - `AboutSection({ ui: UiCopy; profile: Profile; site: Site })` — statement on the left (Fraunces), education, languages and location · work modes on the right.
  - `ContactSection({ ui: UiCopy; site: Site })` — `contact_intro`, then email (visible address) →, LinkedIn ↗, GitHub ↗, and an outlined CV button when a CV is published.
  - `App` — structural content, four regions with independent retry, navigation state, section availability and deep-link scrolling once content exists.

- [ ] **Step 1: About**

`web/src/components/AboutSection.tsx`:

```tsx
import { Section } from '@/components/Section'
import type { UiCopy } from '@/content'
import type { Profile, Site } from '@/lib/api'
import { formatYears } from '@/lib/format'
import { aboutStatement } from '@/lib/sections'

export function AboutSection({ ui, profile, site }: { ui: UiCopy; profile: Profile; site: Site }) {
  const statement = aboutStatement(site)
  const place = [profile.location, ...profile.work_modes.map((mode) => ui.about.modes[mode])].filter(
    (part): part is string => Boolean(part),
  )

  return (
    <Section id="about" label={ui.sections.about.nav} title={ui.sections.about.title}>
      <div className="grid gap-12 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:gap-20">
        {statement ? (
          <blockquote className="font-display text-[clamp(1.75rem,3vw,2.75rem)] font-light leading-snug">“{statement}”</blockquote>
        ) : (
          <div aria-hidden="true" />
        )}

        <dl className="space-y-8">
          {site.education.length > 0 && (
            <div>
              <dt className="label text-accent">{ui.about.education}</dt>
              <dd className="mt-3">
                <ul className="space-y-4">
                  {site.education.map((entry) => (
                    <li key={entry.key}>
                      <p className="font-semibold">{entry.program}</p>
                      <p className="text-muted">
                        {[entry.institution, formatYears(entry.start_year, entry.end_year), entry.detail]
                          .filter((part): part is string => Boolean(part))
                          .join(' · ')}
                      </p>
                    </li>
                  ))}
                </ul>
              </dd>
            </div>
          )}

          {site.languages.length > 0 && (
            <div>
              <dt className="label text-accent">{ui.about.languages}</dt>
              <dd className="mt-3 text-muted">
                {site.languages.map((language) => `${language.name} ${ui.about.levels[language.level]}`).join(' · ')}
              </dd>
            </div>
          )}

          {place.length > 0 && (
            <div>
              <dt className="label text-accent">{ui.about.location}</dt>
              <dd className="mt-3 text-muted">{place.join(' · ')}</dd>
            </div>
          )}
        </dl>
      </div>
    </Section>
  )
}
```

- [ ] **Step 2: Contact**

`web/src/components/ContactSection.tsx`:

```tsx
import { ArrowRight, ArrowUpRight, Download } from 'lucide-react'
import { Section } from '@/components/Section'
import type { UiCopy } from '@/content'
import type { ProfessionalLink, ProfessionalLinkKey, Site } from '@/lib/api'
import { emailAddress } from '@/lib/format'

const ORDER: readonly ProfessionalLinkKey[] = ['email', 'linkedin', 'github']

export function ContactSection({ ui, site }: { ui: UiCopy; site: Site }) {
  const links = ORDER.map((key) => site.professional_links.find((link) => link.key === key)).filter(
    (link): link is ProfessionalLink => link !== undefined,
  )

  return (
    <Section id="contact" label={ui.sections.contact.nav} title={ui.sections.contact.title}>
      <div className="grid gap-12 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-end lg:gap-20">
        <p className="max-w-2xl font-display text-[clamp(1.5rem,2.6vw,2.25rem)] font-light leading-snug">{site.contact_intro}</p>

        <div>
          <ul>
            {links.map((link) => {
              const isEmail = link.key === 'email'
              return (
                <li key={link.key} className="border-b border-line first:border-t">
                  <a
                    href={link.href}
                    {...(isEmail ? {} : { target: '_blank', rel: 'noopener noreferrer' })}
                    className="flex min-h-14 items-center justify-between gap-6 py-3 transition-colors hover:text-accent"
                  >
                    <span>
                      <span className="block text-lg">{link.label}</span>
                      {isEmail && <span className="mt-1 block font-mono text-sm text-muted">{emailAddress(link.href)}</span>}
                    </span>
                    {isEmail ? <ArrowRight size={18} aria-hidden="true" /> : <ArrowUpRight size={18} aria-hidden="true" />}
                    {!isEmail && <span className="sr-only">{ui.contact.newTab}</span>}
                  </a>
                </li>
              )
            })}
          </ul>

          {site.cv && (
            <a
              href={site.cv.url}
              className="mt-8 inline-flex min-h-11 items-center gap-2 rounded-sm border border-accent px-4 font-mono text-xs font-medium uppercase tracking-[0.18em] text-accent transition-colors hover:bg-accent hover:text-canvas"
            >
              <Download size={14} aria-hidden="true" />
              {site.cv.label}
            </a>
          )}
        </div>
      </div>
    </Section>
  )
}
```

- [ ] **Step 3: Compose the page**

Replace `web/src/App.tsx` with:

```tsx
import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { AboutSection } from '@/components/AboutSection'
import { ContactSection } from '@/components/ContactSection'
import { DawnTransition } from '@/components/DawnTransition'
import { ExperienceSection } from '@/components/ExperienceSection'
import { ProjectsSection } from '@/components/ProjectsSection'
import { ScrollScene } from '@/components/ScrollScene'
import { SiteNav } from '@/components/SiteNav'
import { StackSection } from '@/components/StackSection'
import { resolveLocale, uiCopy } from '@/content'
import { useActiveSection } from '@/hooks/useActiveSection'
import { useRegion } from '@/hooks/useRegion'
import { useTheme } from '@/hooks/useTheme'
import { loadStructuralContent, type StructuralContent } from '@/lib/api'
import { buildScene } from '@/lib/scene'
import { SCROLL_VIDEO_SRC, navVeilOpacity } from '@/lib/scene-timeline'
import { NAV_LIGHT_THRESHOLD } from '@/lib/section-opacity'
import { combineStatus, hasAboutContent, hasContactContent, visibleSections, type SectionId } from '@/lib/sections'
import { useVideoScrub } from '@/useVideoScrub'

type StructuralState = { status: 'loading' } | { status: 'ready'; content: StructuralContent } | { status: 'error' }

export default function App() {
  const locale = useMemo(() => resolveLocale(window.location.pathname), [])
  const ui = uiCopy(locale)
  const scrub = useVideoScrub(SCROLL_VIDEO_SRC)
  const { theme, toggle: toggleTheme } = useTheme()
  const [structural, setStructural] = useState<StructuralState>({ status: 'loading' })
  const [attempt, setAttempt] = useState(0)

  const technologies = useRegion(locale, 'technologies')
  const experiences = useRegion(locale, 'experiences')
  const workCases = useRegion(locale, 'work-cases')
  const projects = useRegion(locale, 'projects')

  useEffect(() => {
    document.documentElement.lang = locale
  }, [locale])

  useEffect(() => {
    const controller = new AbortController()
    setStructural({ status: 'loading' })
    loadStructuralContent(locale, controller.signal)
      .then((content) => setStructural({ status: 'ready', content }))
      .catch(() => {
        if (!controller.signal.aborted) setStructural({ status: 'error' })
      })
    return () => controller.abort()
  }, [locale, attempt])

  const content = structural.status === 'ready' ? structural.content : null

  useEffect(() => {
    if (content) document.title = `${content.profile.name} — ${content.profile.headline}`
  }, [content])

  const scene = useMemo(
    () => (content ? buildScene({ ...content, technologies: technologies.state.status === 'ready' ? technologies.state.data : [] }) : null),
    [content, technologies.state],
  )

  const experienceStatus = combineStatus(experiences.state.status, workCases.state.status)
  const sections: SectionId[] = content
    ? visibleSections({
        experience: {
          status: experienceStatus,
          hasItems:
            experiences.state.status === 'ready' &&
            workCases.state.status === 'ready' &&
            experiences.state.data.length + workCases.state.data.length > 0,
        },
        stack: {
          status: technologies.state.status,
          hasItems: technologies.state.status === 'ready' && technologies.state.data.length > 0,
        },
        about: hasAboutContent(content.profile, content.site),
        contact: hasContactContent(content.site),
      })
    : []

  const activeSection = useActiveSection(sections)

  // Content renders after the first paint, so a deep link (#projects, or the anchor kept by the
  // language switch) is applied once its target exists.
  const deepLinkHandled = useRef(false)
  useEffect(() => {
    if (deepLinkHandled.current || !content) return
    const id = window.location.hash.slice(1)
    if (id === '') {
      deepLinkHandled.current = true
      return
    }
    const target = document.getElementById(id)
    if (target) {
      target.scrollIntoView()
      deepLinkHandled.current = true
    }
  }, [content, sections, experiences.state, workCases.state, projects.state])

  const retryStructural = useCallback(() => setAttempt((value) => value + 1), [])
  const retryExperience = useCallback(() => {
    experiences.retry()
    workCases.retry()
  }, [experiences, workCases])

  const progress = scrub.scrollProgress

  return (
    <>
      <SiteNav
        ui={ui}
        locale={locale}
        name={content?.profile.name ?? null}
        avatar={content?.profile.photo ?? null}
        cv={content?.site.cv ?? null}
        sections={sections}
        activeSection={activeSection}
        overScene={progress < 1}
        lightText={progress > NAV_LIGHT_THRESHOLD}
        veilOpacity={navVeilOpacity(progress)}
        theme={theme}
        onToggleTheme={toggleTheme}
      />

      <main id="content" tabIndex={-1} className="outline-none" aria-busy={structural.status === 'loading'}>
        <ScrollScene scrub={scrub} scene={scene} status={structural.status} ui={ui} onRetry={retryStructural} />

        {content && (
          <>
            <DawnTransition />
            {sections.includes('experience') && (
              <ExperienceSection ui={ui} locale={locale} experiences={experiences.state} workCases={workCases.state} onRetry={retryExperience} />
            )}
            {sections.includes('projects') && (
              <ProjectsSection ui={ui} projects={projects.state} emptyMessage={content.site.projects_empty_message} onRetry={projects.retry} />
            )}
            {sections.includes('stack') && (
              <StackSection ui={ui} technologies={technologies.state} groups={content.site.technology_groups} onRetry={technologies.retry} />
            )}
            {sections.includes('about') && <AboutSection ui={ui} profile={content.profile} site={content.site} />}
            {sections.includes('contact') && <ContactSection ui={ui} site={content.site} />}
          </>
        )}
      </main>
    </>
  )
}
```

- [ ] **Step 4: Run the full front check**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'`
Expected: all green.

- [ ] **Step 5: Smoke the page and commit**

Publishing content is Luciano's editorial decision: ask him to review and publish the Phase 6 drafts in Filament (http://127.0.0.1:8016/admin — Profile, Site, the Coned experiences and cases, ReservaHub, education, languages and technologies; Trucks and Drinks stays draft until its texts exist), or to explicitly approve publishing them on this local stack for QA. With content published, open http://127.0.0.1:8016 and http://127.0.0.1:8016/en in a visible browser window. Confirm every section renders, the navigation turns solid after the scene, and `/en#projects` opens on Projects. Record anything off in the task report; the full checklist is Task 11.

```bash
git add web/src/components/AboutSection.tsx web/src/components/ContactSection.tsx web/src/App.tsx
git commit -m "feat(web): compose the portfolio with about, contact, navigation and regional retries"
```

---

### Task 11: Documentation, verification and browser QA

**Files:**
- Modify: `docs/ARCHITECTURE.md`, `ROADMAP.md`, `README.md`, `docs/content/ASSET_INVENTORY.md`
- Create: `docs/testing/PHASE_6_BROWSER_QA.md`

**Interfaces:**
- Consumes: the finished front (Tasks 1–10) and the CMS/API (plan `2026-09-14-phase-6-cms-api.md`).
- Produces: documentation matching the implementation and a recorded browser QA.

- [ ] **Step 1: `docs/ARCHITECTURE.md`**

In `## Frontend`, replace the bullet `- lucide-react y mp4box 0.5.x.` with:

```markdown
- lucide-react, mp4box 0.5.x y fuentes autoalojadas con Fontsource (Fraunces Variable, Instrument Sans, IBM Plex Mono).
```

Replace the paragraph that starts with `El front es una SPA sin render de servidor.` (the first paragraph under `### Renderizado y datos`) with:

```markdown
El front es una SPA sin render de servidor. `resolveLocale` toma el idioma del path (`/en` -> inglés; `/` y `/es` -> español). `loadStructuralContent` pide `profile` y `site`; si alguno falla, la página muestra solo el error general con Reintentar. `useRegion` carga en paralelo `experiences`, `work-cases`, `projects` y `technologies`: cada colección es regional y su fallo muestra un error con Reintentar solo en su sección. Todo cuerpo se valida como `unknown` en `web/src/lib/api.ts`. Las decisiones de presentación son funciones puras con tests en `web/src/lib/` (`groupExperience`, `groupProjects`, `groupTechnologies`, `visibleSections`, `formatPeriod`, `buildScene`, curvas de la escena); los componentes de `web/src/components/` solo las renderizan. `web/src/content.ts` contiene solo textos de interfaz, nunca contenido profesional. El tema (`light`/`dark`) se fija antes del primer pintado con `data-theme` desde `localStorage` (`portfolio-theme`) o `prefers-color-scheme`; los tokens viven en `docs/design/phase-6/DESIGN_TOKENS.md`. Consecuencia conocida: sin render de servidor, el HTML inicial no contiene el contenido; SEO/metadata se resuelven en Fase 8.
```

Replace the whole subsection `### Escena de scroll con video (Fase 6, implementado)` — from that heading down to and including the bullet `- No hay scroll-jacking: el scroll nativo nunca se intercepta.` — with:

```markdown
### Escena de scroll con video (Fase 6, implementado)

Specs: `docs/superpowers/specs/2026-09-14-phase-6-cinematic-scroll-design.md` (lógica de scroll) y `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md` (sección 7, visual). No usa Motion ni GSAP/Lenis.

- Pista de 500vh con escena sticky: `<video>` local (`/media/scroll/ink-tree-network-v1.mp4`, H.264 todo intra, póster `-poster.webp`), `<canvas>` al tamaño nativo del cuadro, fondo SVG (`SceneBackdrop`) y tres beats en la columna izquierda.
- `useVideoScrub` sigue el scroll con lerp (`LERP_TAU = 8`, `SNAP = 0.002`); con WebCodecs arma un banco de frames WebP (mp4box + `VideoDecoder`, `LEAD = 24`, reintento por software, watchdog de 60 s) y dibuja los dos cuadros vecinos mezclados según la posición fraccional (`blendFrames`), con una LRU de `ImageBitmap` (`LRU_MAX = 24`). Sin WebCodecs, con movimiento reducido o ante un fallo, cae a `video.currentTime`.
- `web/src/lib/scene-timeline.ts` define la corrección de contraste de la transición (triángulo 0,55–0,75), los velos de la columna y de la barra, el paso paisaje → constelación y `NAV_LIGHT_THRESHOLD = 0.70`, con valores medidos cuadro por cuadro (`docs/design/phase-6/scroll-video/PROMPTS.md`).
- Beat 1: foto de Profile, nombre y headline; beat 2: frase en tres partes o `short_summary`; beat 3: tecnologías backend, cierre o `availability` y CTA de correo. Los beats son decorativos para lectores de pantalla; el mismo copy está en un bloque `sr-only`.
- La barra es transparente sobre la escena y sólida en las secciones, con sección activa, avatar, ES/EN, tema, CV y menú a pantalla completa en móvil.
- No hay scroll-jacking: el scroll nativo nunca se intercepta.
```

- [ ] **Step 2: `ROADMAP.md`**

In `### Escena de scroll con video (nuevo front base en `web/`)`, replace `- [ ] Rediseño visual con la nueva foto (la lógica de scroll se conserva).` with `- [x] Rediseño visual con la nueva foto (la lógica de scroll se conserva).`, and insert after that checklist:

```markdown
### Portfolio completo (front)

Plan: `docs/superpowers/plans/2026-09-14-phase-6-front-visual.md`.

- [x] Paleta Terracota clara y oscura, fuentes autoalojadas y tema sin parpadeo.
- [x] Video todo intra con póster, mezcla de cuadros, corrección de la transición, velos y fondo paisaje → constelación.
- [x] Navegación transparente/sólida con sección activa, avatar, idioma, tema, CV y menú móvil.
- [x] Experiencia en línea de tiempo con casos desplegables.
- [x] Proyectos para clientes y personales en dossier con galería y visor.
- [x] Stack, Sobre mí y Contacto.
- [ ] QA en navegador real con ventana visible (`docs/testing/PHASE_6_BROWSER_QA.md`).
```

- [ ] **Step 3: `README.md`**

Replace the bullet that starts with `` - `web/` — portfolio público Vite + React 18 (SPA).`` with:

```markdown
- `web/` — portfolio público Vite + React 18 (SPA). Desde Fase 6 reemplaza al front Next.js de Fase 5: escena de scroll con video y secciones de experiencia, proyectos, stack, sobre mí y contacto, todo leído de la API pública de Laravel. Los medios estáticos de la escena viven en `web/public/media/scroll/`; `web/public/media/images/` y `web/public/media/loops/` quedan reservadas para medios decorativos futuros (nunca contenido profesional, que se sube desde Filament).
```

- [ ] **Step 4: `docs/content/ASSET_INVENTORY.md`**

Insert these rows immediately after the `AST-PHOTO-PROFILE` row:

```markdown
| AST-SCROLL-VIDEO | Decorative scroll-scene illustration (ink tree becoming a node network, day to night) | ES / EN | AI-generated for this site from prompts in `docs/design/phase-6/scroll-video/PROMPTS.md` | `/media/scroll/ink-tree-network-v1.mp4` | Approved by Luciano on 2026-09-14; served by the Phase 6 front (H.264 all-intra, 848×480, 81 frames, no audio) | Decorative (`aria-hidden`); the scene copy is available as text | No personal data, text, logos or people in the frames. |
| AST-SCROLL-POSTER | First frame of the scroll video, shown while it loads | ES / EN | Extracted from `AST-SCROLL-VIDEO` | `/media/scroll/ink-tree-network-v1-poster.webp` | Served by the Phase 6 front | Decorative | Same as `AST-SCROLL-VIDEO`. |
| AST-FONTS | Self-hosted web fonts: Fraunces Variable, Instrument Sans, IBM Plex Mono | ES / EN | Fontsource npm packages (SIL Open Font License 1.1) bundled by Vite | `/assets/*.woff2` (hashed by the build) | Served by the Phase 6 front; no external font request | Not applicable | Open-source fonts; no personal data. |
```

- [ ] **Step 5: Automated verification**

```bash
export GATEWAY_PORT=8016
docker compose -p portfolio-phase6 run --rm --no-deps web sh -c 'pnpm typecheck && pnpm test:run && npx vite build'
docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact
docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint --test
node infra/validation/validate-repository.mjs
git diff --stat main...HEAD
```

Expected: front typecheck, tests and build green; API suite green; Pint clean; validator exits 0. Read the branch diff and confirm (Global Constraints) that no file names the external design reference or its author.

- [ ] **Step 6: Browser QA in a visible window**

Create `docs/testing/PHASE_6_BROWSER_QA.md`:

```markdown
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
```

Run the checks with the claude-in-chrome browser tools in a visible window (resize to 1440×900 and 375×812; emulate reduced motion through the OS or DevTools setting; for row 17 stop the API briefly with `docker compose -p portfolio-phase6 stop api` after the page loaded structural content, click Retry after `docker compose -p portfolio-phase6 start api`). Write `OK` or the observed problem in each result cell. Fix any problem in its owning component with a failing test first when the logic lives in `web/src/lib/`, re-run Step 5, and only then tick the QA item in `ROADMAP.md`.

- [ ] **Step 7: Commit**

```bash
git add docs/ARCHITECTURE.md ROADMAP.md README.md docs/content/ASSET_INVENTORY.md docs/testing/PHASE_6_BROWSER_QA.md
git commit -m "docs: record the phase 6 portfolio front and its browser QA"
```

