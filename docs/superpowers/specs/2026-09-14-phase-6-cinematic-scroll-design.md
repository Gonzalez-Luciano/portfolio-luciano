# Phase 6 — Cinematic scroll-tied video scene

- **Date:** 2026-09-14
- **Branch / worktree:** `feat/phase-6-cinematic-scroll` / `.worktrees/phase-6-cinematic-scroll`
- **Authority:** the "Scroll Tied Video Section" prompt supplied by Luciano for Phase 6, to be followed literally, plus the four decisions below.

## 1. Human decisions (2026-09-14)

| Question | Decision |
| --- | --- |
| Where the scene lives and its stack | New standalone app `cinematic/` with the prompt's stack (Vite + React 18 + TypeScript + Tailwind CSS 3 + lucide-react + mp4box 0.5.x). `web/` (Next.js) is untouched. |
| Copy | Keep the prompt's structure, type, spacing, colors and motion; replace the Vectrus copy with the approved initial content used by `PortfolioContentSeeder` (`InitialPortfolioContent`). |
| Languages | Spanish at `/cinematic/`, English at `/cinematic/?lang=en`. No visible language switcher (the prompt has none). |
| Runtime data | Static snapshot of the seeder literals in `cinematic/src/content.ts`. No API calls: seeded rows are draft/hidden and the public API would return nothing. |

### 1.1 Revision (2026-09-14, later the same day)

Luciano superseded the decisions above:

| Question | Decision |
| --- | --- |
| Front base | The Phase 5 Next.js front is removed. The scene becomes the public front in `web/` (same Vite stack), served by the gateway at `/`, `/es`, `/en`. |
| Data | No static snapshot: the scene reads published content from `/api/v1/{locale}/profile`, `/site`, `/technologies`. |
| Split copy | Option (b): new optional bilingual Profile pairs managed in Filament — `statement_lead/emphasis/tail` and `closing_line_one/two` — exposed as `statement` and `closing` (null unless complete). |
| Visual layer | Provisional. The scroll logic (`useVideoScrub`, opacity curves, sticky 500vh track) stays; the visual design will be redefined with a new photo. |
| Development data | Published directly in the development database (`portfolio-phase6` stack), not through a seeder or import. |

Front mapping after the revision: nav = `profile.name` (active) + `site.professional_links`; CV only when `site.cv` is published; section 1 = `headline` / `name`; section 2 = `statement` (fallback `short_summary`); section 3 = backend technology names, `closing` (fallback `availability`), email link.

## 2. Copy mapping (original, superseded by 1.1)

| Prompt slot | ES | EN | Source |
| --- | --- | --- | --- |
| Nav 1 (active) | Luciano González | Luciano González | `profile.name` |
| Nav 2-5 | Trabajo · Especialización · Forma de trabajo · Contacto | Work · Expertise · Approach · Contact | `web/messages/*.json`, linking `/{locale}#work|#expertise|#approach|#contact` |
| NEWS | CV → `/cv/luciano-gonzalez-es.pdf` | CV → `/cv/luciano-gonzalez-en.pdf` | public CV routes, `assets.cv_*_label` as accessible name |
| MENU | Menú | Menu | `web/messages/*.json` |
| S1 H1 / subtitle | Backend Developer \| PHP & Laravel / Luciano González | same | `profile.headline_*`, `profile.name` |
| S2 H2 (base / 80% / 50%) | Desarrollo backend orientado a APIs, / lógica de negocio, datos / y mantenimiento de aplicaciones | Backend development focused on APIs, / business logic, data, / and application maintenance | `profile.short_summary_*` split, final period dropped |
| S3 eyebrow | PHP \| Laravel | PHP \| Laravel | `technologies` |
| S3 H2 | Disponible para conversar / sobre oportunidades backend. | Available to discuss / backend opportunities. | `profile.availability_*` (approved excerpt) |
| S3 CTA | Enviame un correo → mailto | Email me → mailto | `professional_links` email |
| Mobile footer NEWS / CONTACT | CV / Contacto | CV / Contact | as above |

## 3. Implemented as specified

Page architecture (500vh track, sticky full-viewport scene, video + 1920×1080 canvas + overlay), scroll progress formula with resize/orientationchange re-measure, the three sequential opacity curves, Stagger (0.8s, 24px, `cubic-bezier(0.16,1,0.3,1)`, threshold 0.3), navbar entrance and color flip at p > 0.55, mobile hamburger and full-screen overlay, section layouts and delays, the exact video URL (muted, playsInline, preload auto, never `play()`), the exact font link and title, and `useVideoScrub` with LERP_TAU 8, SNAP 0.002, LRU_MAX 24, LEAD 24, 60s watchdog, WebCodecs + mp4box frame bank of WebP blobs, binary-search nearest frame, LRU `ImageBitmap` warming, software-decode retry and `currentTime` fallback.

## 4. Additions required by repository rules (not visual changes)

- Accessible names for icon-only buttons; `aria-current` on the active link; `aria-expanded` on menu triggers; dialog semantics, Escape to close, focus moved to Close and restored on close.
- Icon buttons scroll to the next section hold (S1 → p 0.45, S2 down → p 0.80, S2 up → top) instead of being inert controls.
- Fully transparent sections are `inert`; interactive children only receive pointer events while their section is visible.
- `prefers-reduced-motion`: native smooth scroll disabled and CSS transitions collapsed (the hook already skips lerp and the frame bank).

## 5. Deliberate deviations from AGENTS.md / ROADMAP defaults

- The prompt forbids GSAP/Lenis and prescribes Vite/React 18/Tailwind 3, so this scene does not use Motion or GSAP and does not share `web/`'s stack.
- The scene is a sticky scroll-driven section, not scroll-jacking: native scroll is never intercepted.
- The ROADMAP Phase 6 Motion microinteractions and node-field tasks are not addressed by this scene and stay open.

## 6. Docker

`compose.yaml` adds the `cinematic` dev service (Node 24 + pnpm, `cinematic_node_modules` volume, internal port 5173, health check). The portfolio gateway routes `/cinematic` → `/cinematic/` and `/cinematic/*` → `cinematic:5173`; no new host port is published.
