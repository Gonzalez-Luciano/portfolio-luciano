# Phase 2 Experience Design Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Produce and validate the complete Phase 2 sitemap, wireframes, design tokens, responsive specification, and dependency-free bilingual high-fidelity prototype without starting production application implementation or Phase 6 motion.

**Architecture:** Treat approved Phase 1 content and the Phase 2 specification as immutable inputs. Store permanent design documentation under `docs/design/phase-2/` and the executable static prototype under `docs/prototypes/phase-2/`; keep the prototype semantic, dependency-free, bilingual, and progressively enhanced by small ES modules. Validate document contracts, tokens, assets, content traceability, and interaction markup with repository-local scripts, then perform browser-based accessibility and visual QA before updating Roadmap checkboxes.

**Tech Stack:** Markdown, semantic HTML5, CSS custom properties, dependency-free JavaScript ES modules, Node.js standard library, Python/Pillow from the bundled Codex workspace runtime, self-hosted WOFF2 fonts, Git, and the in-app Browser for visual/interaction QA.

## Global Constraints

- Positioning remains exactly **Backend Developer | PHP & Laravel**.
- Use only approved content from `docs/content/CONTENT.es.md`, `docs/content/CONTENT.en.md`, `docs/content/ASSET_INVENTORY.md`, and `docs/content/CONFIDENTIALITY_MATRIX.md`.
- Never invent employers, clients, banks, institutions, metrics, results, project claims, or production scale.
- Default public prototype state contains zero published projects and the approved honest zero-project copy.
- Any populated-project demonstration is labeled `Structural prototype fixture — not Luciano's public work` and cannot enter production content.
- Do not create or modify `web/`, `api/`, `infra/`, package manifests, Docker files, or production application code.
- Do not install dependencies or initialize Next.js/Laravel.
- Do not implement Motion, GSAP, ScrollTrigger, canvas, WebGL, node interaction, scroll timelines, pinned sequences, or continuous effects.
- Use Instrument Sans and IBM Plex Mono only from their pinned official repositories and retain their SIL Open Font License files.
- Light tokens: canvas `#EEE9DE`, surface `#F6F2E9`, text `#1B1E1C`, muted `#565B56`, accent `#A94322`, border `#817B70`, disabled `#5F635E`, selected `#E4D8C9`, error `#8B2E24`.
- Dark tokens: canvas `#171918`, surface `#202321`, text `#F1ECE1`, muted `#B8B3A9`, accent `#F07A4B`, border `#777A74`, disabled `#96958F`, selected `#2B2926`, error `#FF9B8A`.
- Breakpoints are exact: mobile below `48rem`, tablet `48rem–63.99rem`, desktop/indexed Work at `64rem+`, wider desktop gutter at `80rem+`; max content width is `90rem`.
- Touch targets are at least `44 × 44px`; focus uses a `3px` accent ring with `2px` offset.
- Initial theme follows the system; only explicit `light` or `dark` selection persists under `portfolio-prototype-theme`.
- The page and every state remain understandable with JavaScript and all transitions disabled.
- Phase 2 documentation may be updated only when its artifact and evidence exist. Roadmap checkboxes stay unchecked until Task 10.
- Execute later from a parent/controller explicitly switched to GPT-5.6 Terra. Do not execute this plan from the current Sol planning session.

## Execution Model Routing

| Work | Required model |
|---|---|
| Parent/controller during execution | GPT-5.6 Terra |
| Mechanical asset download/derivation steps in Task 3 | GPT-5.6 Luna if available; otherwise GPT-5.6 Terra |
| Tasks 1–9 implementation/integration | GPT-5.6 Terra unless the task is explicitly routed to available Luna as above |
| Per-task reviewers | GPT-5.6 Terra by default |
| Architecture escalation | GPT-5.6 Sol only for a demonstrated high-risk contradiction |
| Final whole-branch review in Task 10 | GPT-5.6 Sol |

Every spawned agent must receive an explicit model. Never inherit the parent model silently. Execution must stop and report if explicit routing cannot be confirmed.

## File Map

### Permanent design documentation

- Create `docs/design/phase-2/SITEMAP.md`: canonical section order, anchor IDs, desktop/mobile navigation, fragment behavior, zero-project behavior.
- Create `docs/design/phase-2/WIREFRAMES.md`: low-fidelity desktop/tablet/mobile diagrams for all sections, menu, loading, and errors.
- Create `docs/design/phase-2/DESIGN_TOKENS.md`: semantic color, typography, spacing, geometry, elevation, icon, focus, and motion contracts.
- Create `docs/design/phase-2/RESPONSIVE_SPEC.md`: exact breakpoints, grids, component switches, reflow rules, and bilingual constraints.
- Create `docs/design/phase-2/VALIDATION_REPORT.md`: automated results, browser matrix, accessibility observations, screenshots, asset sizes, corrections, and acceptance record.
- Create `docs/design/phase-2/tests/validate-artifacts.mjs`: validates required design/prototype files, headings, anchors, bilingual content-key parity, forbidden external runtime assets, and state contracts.
- Create `docs/design/phase-2/tests/validate-contrast.mjs`: parses prototype color tokens and verifies required contrast pairs.
- Create `docs/design/phase-2/tests/validate-assets.py`: verifies image dimensions/format/metadata/size and required font/license files.

### Static high-fidelity prototype

- Create `docs/prototypes/phase-2/README.md`: local serving, state URLs, keyboard controls, source-of-truth warning, and validation commands.
- Create `docs/prototypes/phase-2/index.html`: prototype index linking to localized pages and state demonstrations; not a portfolio route.
- Create `docs/prototypes/phase-2/es/index.html`: complete Spanish page with zero projects.
- Create `docs/prototypes/phase-2/en/index.html`: complete English page with zero projects.
- Create `docs/prototypes/phase-2/states/loading.html`: stable loading composition.
- Create `docs/prototypes/phase-2/states/section-error.html`: localized recoverable section error and retry control.
- Create `docs/prototypes/phase-2/states/site-error.html`: broad content failure with safe shell/contact fallback.
- Create `docs/prototypes/phase-2/states/missing-cv.html`: explicit omitted-locale-CV behavior.
- Create `docs/prototypes/phase-2/states/projects-populated.html`: clearly labeled structural project dossiers with media-present and media-absent records.
- Create `docs/prototypes/phase-2/styles/tokens.css`: fonts and all semantic/custom-property tokens.
- Create `docs/prototypes/phase-2/styles/base.css`: reset, semantics, typography, skip link, focus, utilities, and no-JS defaults.
- Create `docs/prototypes/phase-2/styles/layout.css`: container, grids, global section rhythm, and breakpoint gutters.
- Create `docs/prototypes/phase-2/styles/header.css`: sticky desktop header and full-screen mobile dialog.
- Create `docs/prototypes/phase-2/styles/hero.css`: Responsive Split hero and portrait-first mobile composition.
- Create `docs/prototypes/phase-2/styles/work.css`: Indexed Detail at `64rem+` and Numbered Dossiers below.
- Create `docs/prototypes/phase-2/styles/content.css`: Expertise, Projects, Approach, Contact, CV, and shared dossier rules.
- Create `docs/prototypes/phase-2/styles/states.css`: skeleton, error, unavailable, and fixture-banner styles.
- Create `docs/prototypes/phase-2/scripts/theme.js`: system preference, explicit persistence, accessible toggle labels.
- Create `docs/prototypes/phase-2/scripts/menu.js`: dialog open/close, scroll lock, destination close, focus restoration.
- Create `docs/prototypes/phase-2/scripts/navigation.js`: fragment focus management and active grouped-destination state.
- Create `docs/prototypes/phase-2/scripts/work-tabs.js`: desktop vertical tabs and no-JS/narrow expanded cases.
- Create `docs/prototypes/phase-2/scripts/main.js`: imports and initializes the three focused modules.
- Create `docs/prototypes/phase-2/tools/build-assets.py`: deterministic portrait/wide WebP derivation with metadata removal.
- Create `docs/prototypes/phase-2/assets/ASSET_MANIFEST.md`: sources, pinned commits, licenses, hashes, dimensions, and confidentiality review.
- Create `docs/prototypes/phase-2/assets/fonts/InstrumentSans-Variable.woff2`.
- Create `docs/prototypes/phase-2/assets/fonts/IBMPlexMono-Regular-Latin1.woff2`.
- Create `docs/prototypes/phase-2/assets/fonts/IBMPlexMono-SemiBold-Latin1.woff2`.
- Create `docs/prototypes/phase-2/assets/fonts/InstrumentSans-OFL.txt`.
- Create `docs/prototypes/phase-2/assets/fonts/IBMPlexMono-OFL.txt`.
- Create `docs/prototypes/phase-2/assets/images/profile-portrait.webp`: `900 × 1200` portrait derivative.
- Create `docs/prototypes/phase-2/assets/images/profile-wide.webp`: `1200 × 900` mobile/wider derivative.
- Create `docs/prototypes/phase-2/evidence/`: final browser screenshots named by viewport, locale, and theme.

---

### Task 1: Sitemap and complete low-fidelity wireframes

**Files:**
- Create: `docs/design/phase-2/SITEMAP.md`
- Create: `docs/design/phase-2/WIREFRAMES.md`
- Create: `docs/design/phase-2/tests/validate-artifacts.mjs`

**Interfaces:**
- Consumes: `ROADMAP.md`, approved Phase 2 spec, Phase 1 bilingual content headings.
- Produces: stable section IDs `top`, `work`, `expertise`, `projects`, `approach`, `contact`; exact navigation order; complete named wireframe inventory; `node docs/design/phase-2/tests/validate-artifacts.mjs` entrypoint used by every later task.

- [ ] **Step 1: Write the first failing artifact-contract validator**

Create `validate-artifacts.mjs` with Node standard-library imports and these initial checks:

```js
import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '../../../..');
const read = (path) => readFileSync(resolve(root, path), 'utf8');
const required = [
  'docs/design/phase-2/SITEMAP.md',
  'docs/design/phase-2/WIREFRAMES.md',
];

for (const path of required) assert.ok(existsSync(resolve(root, path)), `Missing ${path}`);

const sitemap = read(required[0]);
for (const id of ['top', 'work', 'expertise', 'projects', 'approach', 'contact']) {
  assert.match(sitemap, new RegExp(`\\| \\`${id}\\` \\|`), `Missing anchor ${id}`);
}

const wireframes = read(required[1]);
for (const heading of [
  'Hero', 'Professional introduction', 'Work cases', 'Professional experience',
  'Specializations', 'Projects', 'Technologies', 'Approach', 'Contact',
  'Mobile menu', 'Loading state', 'Recoverable section error', 'Broad site error'
]) assert.ok(wireframes.includes(`## ${heading}`), `Missing wireframe ${heading}`);

console.log('Phase 2 artifact contracts pass.');
```

- [ ] **Step 2: Run the validator and confirm the intended failure**

Run:

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
```

Expected: failure beginning with `Missing docs/design/phase-2/SITEMAP.md`.

- [ ] **Step 3: Write the sitemap and navigation contract**

Create `SITEMAP.md` with:

```text
/{locale}
├── #top — Hero
├── editorial bridge — no navigation destination
├── #work — cases first, experience second
├── #expertise — specializations then technologies
├── #projects — zero or ordered project dossiers
├── #approach — working model
└── #contact — LinkedIn, GitHub, email, locale CV when available
```

Add an anchor table with columns `Order`, `ID`, `ES label`, `EN label`, `Included content`, and `Active-nav boundary`. Use:

| Order | ID | ES label | EN label |
|---:|---|---|---|
| 0 | `top` | Inicio | Home |
| 1 | `work` | Trabajo | Work |
| 2 | `expertise` | Especialización | Expertise |
| 3 | `projects` | Proyectos | Projects |
| 4 | `approach` | Forma de trabajo | Approach |
| 5 | `contact` | Contacto | Contact |

Document the exact sticky-header, fragment, direct-link, focus-target, reduced-motion, desktop utility, mobile dialog, and zero-project rules from Sections 3–4 of the spec.

- [ ] **Step 4: Write every low-fidelity wireframe**

Create `WIREFRAMES.md` with one `##` heading per validator requirement. Under every heading include `Desktop`, `Tablet`, `Mobile`, `Keyboard/focus`, `Light/dark`, `Content source`, and `Acceptance` subsections. Use ASCII boxes to show layout, including:

- Responsive Split hero and portrait-first mobile hero.
- Indexed Detail Work at `64rem+` and sequential Numbered Dossiers below.
- Structured specialization statements and grouped technology text.
- Horizontal/stacked Project Dossiers and the visible zero state.
- Ordered Approach steps.
- Contact actions with locale-CV omission.
- Full-screen numbered mobile menu.
- Stable skeleton geometry, recoverable section error, and broad failure shell.

Do not use invented professional copy inside diagrams; use section labels or exact Phase 1 excerpts.

- [ ] **Step 5: Run the artifact validator**

Run:

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
git diff --check
```

Expected: `Phase 2 artifact contracts pass.` and no whitespace errors.

- [ ] **Step 6: Review Task 1 acceptance**

Confirm that every Phase 2 Roadmap wireframe item has a named artifact, mobile is recomposed rather than collapsed, and no document invents content.

- [ ] **Step 7: Commit Task 1**

```powershell
git add docs/design/phase-2/SITEMAP.md docs/design/phase-2/WIREFRAMES.md docs/design/phase-2/tests/validate-artifacts.mjs
git commit -m "docs: define phase 2 sitemap and wireframes"
```

### Task 2: Design tokens and exact responsive specification

**Files:**
- Create: `docs/design/phase-2/DESIGN_TOKENS.md`
- Create: `docs/design/phase-2/RESPONSIVE_SPEC.md`
- Create: `docs/design/phase-2/tests/validate-contrast.mjs`
- Modify: `docs/design/phase-2/tests/validate-artifacts.mjs`

**Interfaces:**
- Consumes: approved token values and responsive decisions from the spec.
- Produces: exact CSS custom-property names later copied into `styles/tokens.css`; exact `48rem`, `64rem`, `80rem`, and `90rem` contracts; contrast validator entrypoint.

- [ ] **Step 1: Extend the artifact validator before creating token documents**

Add required files and literal checks for all approved colors, `Instrument Sans`, `IBM Plex Mono`, `64rem`, `90rem`, `44 × 44px`, `3px`, and `2px`.

- [ ] **Step 2: Run the validator and confirm it fails on the missing token document**

Run:

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
```

Expected: failure naming `docs/design/phase-2/DESIGN_TOKENS.md`.

- [ ] **Step 3: Write the complete design-token reference**

Create `DESIGN_TOKENS.md` with tables for:

- the nine light and nine dark semantic colors from Global Constraints;
- Instrument Sans roles and IBM Plex Mono restrictions;
- type tokens `--font-size-display`, `--font-size-section`, `--font-size-subheading`, `--font-size-lead`, `--font-size-body`, `--font-size-utility`;
- spacing `--space-1` through `--space-10` = `4, 8, 12, 16, 24, 32, 48, 64, 96, 128px`;
- radii `0`, `4px`, `8px`;
- rules `1px`, `2px`;
- focus `3px` plus `2px` offset;
- shadows `0 8px 24px rgb(0 0 0 / 8%)` light and `24%` dark;
- motion `120ms`, `200ms`, `320ms`, `cubic-bezier(0.2, 0.8, 0.2, 1)`;
- 20/24px icon boxes and 1.5px strokes;
- iconography rules: visible text accompanies menu, theme, language, download, and external-link icons; social marks use accurate accessible labels; technologies remain textual rather than a logo wall;
- hover, focus, active, pressed, disabled, and reduced-motion mappings.

Include this exact fluid type contract:

```css
--font-size-display: clamp(3.5rem, 2rem + 6vw, 7rem);
--font-size-section: clamp(2.5rem, 1.75rem + 3vw, 4.5rem);
--font-size-subheading: clamp(1.75rem, 1.4rem + 1.4vw, 2.75rem);
--font-size-lead: clamp(1.25rem, 1.1rem + 0.6vw, 1.625rem);
--font-size-body: clamp(1.0625rem, 1.025rem + 0.15vw, 1.1875rem);
--font-size-utility: clamp(0.75rem, 0.72rem + 0.1vw, 0.875rem);
```

- [ ] **Step 4: Write the responsive specification**

Create `RESPONSIVE_SPEC.md` with a matrix for `320`, `360`, `390`, `768`, `1024`, and `1440px`. Specify:

- 4-column/20px mobile, 8-column/32px tablet, 12-column/48px desktop, and 12-column/64px wide-desktop gutters;
- full-screen mobile menu below `64rem` and desktop header at `64rem+`;
- Work dossiers below `64rem` and vertical tabs at `64rem+`;
- hero split/stack switch at `64rem`;
- project horizontal/stack switch at `48rem`;
- exact Spanish/English overflow, 200% zoom, 320px reflow, touch, crop, and no-animation acceptance for every component.

- [ ] **Step 5: Write the contrast validator**

Create `validate-contrast.mjs` with the WCAG relative-luminance formula and these minimum assertions:

```js
const pairs = [
  ['#1B1E1C', '#EEE9DE', 4.5, 'light primary'],
  ['#565B56', '#EEE9DE', 4.5, 'light muted'],
  ['#A94322', '#EEE9DE', 4.5, 'light accent'],
  ['#817B70', '#EEE9DE', 3, 'light meaningful border'],
  ['#8B2E24', '#EEE9DE', 4.5, 'light error'],
  ['#F1ECE1', '#171918', 4.5, 'dark primary'],
  ['#B8B3A9', '#171918', 4.5, 'dark muted'],
  ['#F07A4B', '#171918', 4.5, 'dark accent'],
  ['#777A74', '#171918', 3, 'dark meaningful border'],
  ['#FF9B8A', '#171918', 4.5, 'dark error'],
];
```

Fail with the pair name and measured ratio; print every passing ratio to two decimals.

- [ ] **Step 6: Run both validators**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
node docs/design/phase-2/tests/validate-contrast.mjs
git diff --check
```

Expected: both validators pass; contrast output meets or exceeds each threshold.

- [ ] **Step 7: Commit Task 2**

```powershell
git add docs/design/phase-2/DESIGN_TOKENS.md docs/design/phase-2/RESPONSIVE_SPEC.md docs/design/phase-2/tests
git commit -m "docs: define phase 2 tokens and responsive contract"
```

### Task 3: Reproducible fonts and professional-photo derivatives

**Files:**
- Create: `docs/design/phase-2/tests/validate-assets.py`
- Create: `docs/prototypes/phase-2/tools/build-assets.py`
- Create: `docs/prototypes/phase-2/assets/ASSET_MANIFEST.md`
- Create: all files under `docs/prototypes/phase-2/assets/fonts/` and `assets/images/` listed in the file map.

**Interfaces:**
- Consumes: `docs/content/approved-assets/professional-photo.jpg`; Instrument Sans commit `7fa22308a3d0c94ee2b3cd537a1196b65db34a3e`; IBM Plex commit `bf260093582f04622aacc1e9f9ca604d7ccd0c42`.
- Produces: self-contained font/image URLs under `../assets/`; portrait image exactly `900 × 1200`; wide image exactly `1200 × 900`; recorded SHA-256 hashes and metadata review.

- [ ] **Step 1: Write the failing asset validator**

Create `validate-assets.py` using `pathlib`, `hashlib`, and Pillow. Assert:

```python
EXPECTED_IMAGES = {
    "profile-portrait.webp": ((900, 1200), 180_000),
    "profile-wide.webp": ((1200, 900), 180_000),
}
EXPECTED_FONTS = (
    "InstrumentSans-Variable.woff2",
    "IBMPlexMono-Regular-Latin1.woff2",
    "IBMPlexMono-SemiBold-Latin1.woff2",
    "InstrumentSans-OFL.txt",
    "IBMPlexMono-OFL.txt",
)
```

For each image assert `WEBP`, exact dimensions, file size below the stated bound, and empty EXIF. Assert every font/license is non-empty and the manifest contains each SHA-256.

- [ ] **Step 2: Run the validator and confirm it fails**

```powershell
& 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' docs/design/phase-2/tests/validate-assets.py
```

Expected: failure naming the first absent asset.

- [ ] **Step 3: Create the deterministic image builder**

Implement `build-assets.py` with this exact processing core:

```python
from pathlib import Path
from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parents[4]
SOURCE = ROOT / "docs/content/approved-assets/professional-photo.jpg"
OUTPUT = ROOT / "docs/prototypes/phase-2/assets/images"

with Image.open(SOURCE) as image:
    image = image.convert("RGB")
    portrait = ImageOps.fit(image, (900, 1200), Image.Resampling.LANCZOS, centering=(0.5, 0.42))
    wide = ImageOps.fit(image, (1200, 900), Image.Resampling.LANCZOS, centering=(0.5, 0.34))
    portrait.save(OUTPUT / "profile-portrait.webp", "WEBP", quality=82, method=6)
    wide.save(OUTPUT / "profile-wide.webp", "WEBP", quality=82, method=6)
```

The script must create `OUTPUT` and must not overwrite the approved source.

- [ ] **Step 4: Download the three pinned WOFF2 files and two licenses**

Use `Invoke-WebRequest` with these exact pinned URLs:

```powershell
$fontDir = 'docs/prototypes/phase-2/assets/fonts'
New-Item -ItemType Directory -Force -Path $fontDir | Out-Null
Invoke-WebRequest 'https://raw.githubusercontent.com/Instrument/instrument-sans/7fa22308a3d0c94ee2b3cd537a1196b65db34a3e/fonts/webfonts/InstrumentSans%5Bwdth,wght%5D.woff2' -OutFile "$fontDir/InstrumentSans-Variable.woff2"
Invoke-WebRequest 'https://raw.githubusercontent.com/Instrument/instrument-sans/7fa22308a3d0c94ee2b3cd537a1196b65db34a3e/OFL.txt' -OutFile "$fontDir/InstrumentSans-OFL.txt"
Invoke-WebRequest 'https://raw.githubusercontent.com/IBM/plex/bf260093582f04622aacc1e9f9ca604d7ccd0c42/packages/plex-mono/fonts/split/woff2/IBMPlexMono-Regular-Latin1.woff2' -OutFile "$fontDir/IBMPlexMono-Regular-Latin1.woff2"
Invoke-WebRequest 'https://raw.githubusercontent.com/IBM/plex/bf260093582f04622aacc1e9f9ca604d7ccd0c42/packages/plex-mono/fonts/split/woff2/IBMPlexMono-SemiBold-Latin1.woff2' -OutFile "$fontDir/IBMPlexMono-SemiBold-Latin1.woff2"
Invoke-WebRequest 'https://raw.githubusercontent.com/IBM/plex/bf260093582f04622aacc1e9f9ca604d7ccd0c42/packages/plex-mono/LICENSE.txt' -OutFile "$fontDir/IBMPlexMono-OFL.txt"
```

Do not run `npm install`; these are version-pinned design assets.

- [ ] **Step 5: Generate the two photo derivatives**

```powershell
& 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' docs/prototypes/phase-2/tools/build-assets.py
```

Expected: both WebP files are created without modifying the source JPEG.

- [ ] **Step 6: Visually inspect both derivatives**

Open both files with the local image viewer. Confirm face and shoulders remain intact, no stretching occurs, the orange background remains natural, and neither crop clips hair, eyes, jaw, or essential shirt context. If a crop fails, change only the corresponding `centering` tuple, regenerate, and record the final tuple in the manifest.

- [ ] **Step 7: Create the asset manifest**

Record source path, exact upstream URLs/commits, license paths, dimensions, byte sizes, SHA-256 values, Pillow quality/method, final centering tuples, EXIF removal result, alt text from `ASSET_INVENTORY.md`, and a statement that the derivatives contain no new generated visual content.

- [ ] **Step 8: Run asset and documentation validation**

```powershell
& 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' docs/design/phase-2/tests/validate-assets.py
node docs/design/phase-2/tests/validate-artifacts.mjs
git diff --check
```

Expected: all assets and hashes pass; no source asset changes appear in `git status`.

- [ ] **Step 9: Commit Task 3**

```powershell
git add docs/design/phase-2/tests/validate-assets.py docs/prototypes/phase-2/tools docs/prototypes/phase-2/assets
git commit -m "docs: add phase 2 prototype assets"
```

### Task 4: Prototype foundation, bilingual semantic shells, and theme behavior

**Files:**
- Create: `docs/prototypes/phase-2/README.md`
- Create: `docs/prototypes/phase-2/index.html`
- Create: `docs/prototypes/phase-2/es/index.html`
- Create: `docs/prototypes/phase-2/en/index.html`
- Create: `docs/prototypes/phase-2/styles/tokens.css`
- Create: `docs/prototypes/phase-2/styles/base.css`
- Create: `docs/prototypes/phase-2/styles/layout.css`
- Create: `docs/prototypes/phase-2/scripts/theme.js`
- Create: `docs/prototypes/phase-2/scripts/main.js`
- Modify: `docs/design/phase-2/tests/validate-artifacts.mjs`

**Interfaces:**
- Consumes: Task 2 token names, Task 3 local asset paths, exact Phase 1 Spanish/English copy.
- Produces: two equivalent localized documents; common `data-content-key` contract; `initTheme()` function; local-only CSS/font/image references; semantic landmarks used by later tasks.

- [ ] **Step 1: Extend the validator with the prototype-shell contract**

Require the README, root index, both localized pages, three CSS files, and two scripts. For both localized pages assert:

- one `<html lang="es">` or `<html lang="en">`;
- one `<main id="main-content">`;
- one `<h1>` containing `Backend Developer | PHP & Laravel`;
- IDs `top`, `work`, `expertise`, `projects`, `approach`, `contact`;
- a skip link to `#main-content`;
- no `http://` or `https://` in `src`, stylesheet `href`, or font CSS;
- identical sorted sets of `data-content-key` values.

- [ ] **Step 2: Run the validator and confirm the missing-shell failure**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
```

Expected: failure naming `docs/prototypes/phase-2/es/index.html`.

- [ ] **Step 3: Create `tokens.css` from the approved token document**

Define the local fonts and semantic variables exactly:

```css
@font-face {
  font-family: "Instrument Sans";
  src: url("../assets/fonts/InstrumentSans-Variable.woff2") format("woff2-variations");
  font-style: normal;
  font-weight: 400 700;
  font-stretch: 75% 100%;
  font-display: swap;
}

@font-face {
  font-family: "IBM Plex Mono";
  src: url("../assets/fonts/IBMPlexMono-Regular-Latin1.woff2") format("woff2");
  font-style: normal;
  font-weight: 400;
  font-display: swap;
}

:root {
  color-scheme: light dark;
  --color-canvas: #EEE9DE;
  --color-surface: #F6F2E9;
  --color-text: #1B1E1C;
  --color-muted: #565B56;
  --color-accent: #A94322;
  --color-border: #817B70;
  --color-disabled: #5F635E;
  --color-selected: #E4D8C9;
  --color-error: #8B2E24;
}

:root[data-theme="dark"] {
  --color-canvas: #171918;
  --color-surface: #202321;
  --color-text: #F1ECE1;
  --color-muted: #B8B3A9;
  --color-accent: #F07A4B;
  --color-border: #777A74;
  --color-disabled: #96958F;
  --color-selected: #2B2926;
  --color-error: #FF9B8A;
}
```

Add the exact typography, spacing, radius, border, shadow, motion, container, and gutter variables from `DESIGN_TOKENS.md`; do not create alternate unnamed color values in later CSS.

- [ ] **Step 4: Create the base and layout CSS**

`base.css` must include box sizing, body defaults, semantic typography, 60–70ch prose measure, link states, `.skip-link`, `:focus-visible`, `[hidden]`, `.visually-hidden`, `.no-js-only`, and `@media (prefers-reduced-motion: reduce)` that sets transition duration to `0.01ms` and disables smooth scrolling.

`layout.css` must implement:

```css
.container {
  width: min(100% - (2 * var(--page-gutter)), var(--container-max));
  margin-inline: auto;
}

section[id] { scroll-margin-top: calc(var(--header-height) + var(--space-4)); }

@media (min-width: 48rem) { :root { --page-gutter: 2rem; } }
@media (min-width: 64rem) { :root { --page-gutter: 3rem; } }
@media (min-width: 80rem) { :root { --page-gutter: 4rem; } }
```

Use section block spacing `clamp(4rem, 8vw, 8rem)` and preserve a 320px no-overflow baseline.

- [ ] **Step 5: Create equivalent localized semantic shells**

Both pages must use this order and matching `data-content-key` values:

```text
hero.title
hero.value
hero.cta
hero.availability
intro.body
work.case.integrations
work.case.education
work.case.data-automation
work.case.layers
work.experience.primary
work.experience.secondary
expertise.list
projects.zero
technologies.list
approach.body
contact.body
contact.linkedin
contact.github
contact.email
```

Copy the corresponding text exactly from `CONTENT.es.md` and `CONTENT.en.md`. Keep source/publication comments adjacent in HTML comments. The shells may contain unstyled section containers at this stage, but no lorem ipsum or invented project content.

Use the approved localized photo alt text exactly. Spanish omits a CV download because its PDF language metadata is not ready; English may link to the approved tracked English PDF using a prototype-relative path and explicit `(PDF)` label.

- [ ] **Step 6: Implement flash-resistant theme initialization**

Put this minimal bootstrap before stylesheet loading in both localized `<head>` blocks:

```html
<script>
  (() => {
    const saved = localStorage.getItem('portfolio-prototype-theme');
    const dark = saved === 'dark' || (!saved && matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.dataset.theme = dark ? 'dark' : 'light';
  })();
</script>
```

Export `initTheme()` from `theme.js`. It binds `[data-theme-toggle]`, toggles only `light`/`dark`, persists the explicit value, updates `aria-pressed`, and sets localized accessible labels from `data-label-light`/`data-label-dark`.

- [ ] **Step 7: Create the prototype index and README**

The root index links to `es/`, `en/`, and each future state page and displays `Design-validation artifact — not the production portfolio`. The README includes:

```powershell
& 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' -m http.server 4173 --directory .
```

It lists the local URLs, keyboard controls, no-production-content warning, and all three validator commands.

- [ ] **Step 8: Run automated shell validation**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
node docs/design/phase-2/tests/validate-contrast.mjs
& 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' docs/design/phase-2/tests/validate-assets.py
git diff --check
```

Expected: all validators pass and no external runtime font/image/style request exists.

- [ ] **Step 9: Browser-smoke both localized shells**

Serve the repository at port `4173`. Open `/docs/prototypes/phase-2/es/` and `/en/`. Confirm correct `lang`, title, local fonts, source portrait, light/dark toggle persistence across locale navigation, and usable no-JS content after disabling JavaScript and reloading.

- [ ] **Step 10: Commit Task 4**

```powershell
git add docs/prototypes/phase-2 docs/design/phase-2/tests/validate-artifacts.mjs
git commit -m "docs: build phase 2 prototype foundation"
```

### Task 5: Sticky navigation and accessible editorial mobile menu

**Files:**
- Create: `docs/prototypes/phase-2/styles/header.css`
- Create: `docs/prototypes/phase-2/scripts/menu.js`
- Create: `docs/prototypes/phase-2/scripts/navigation.js`
- Modify: `docs/prototypes/phase-2/es/index.html`
- Modify: `docs/prototypes/phase-2/en/index.html`
- Modify: `docs/prototypes/phase-2/scripts/main.js`
- Modify: `docs/design/phase-2/tests/validate-artifacts.mjs`

**Interfaces:**
- Consumes: IDs and labels from `SITEMAP.md`; `initTheme()` from Task 4.
- Produces: `[data-site-header]`, `[data-desktop-nav]`, `#mobile-menu`, `[data-menu-open]`, `[data-menu-close]`; `initMenu()` and `initNavigation()` exports.

- [ ] **Step 1: Add failing header/menu assertions**

For both localized pages require:

```text
data-site-header
href="#work"
href="#expertise"
href="#projects"
href="#approach"
href="#contact"
data-theme-toggle
data-menu-open
<dialog id="mobile-menu"
aria-labelledby="mobile-menu-title"
data-menu-close
```

Also assert five mobile entries contain visible indices `01`–`05` and language links point to the equivalent other-locale page.

- [ ] **Step 2: Run the validator and confirm the menu assertion fails**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
```

Expected: failure naming `data-site-header` or `mobile-menu`.

- [ ] **Step 3: Add the sticky desktop header markup**

Use a visible `LG / Backend` home link, the five localized navigation labels, language link, theme button, and menu button. Give the navigation an accessible localized label. Set `aria-current="location"` initially on no section; later active styling uses `[aria-current]`.

- [ ] **Step 4: Add the mobile dialog markup**

Use native `<dialog id="mobile-menu" aria-labelledby="mobile-menu-title">`. Add explicit Close text, numbered anchor links, language selection, and the approved availability statement. Do not place the theme control inside the dialog; it stays in the closed header.

- [ ] **Step 5: Implement `initMenu()`**

Use this behavior contract:

```js
export function initMenu() {
  const dialog = document.querySelector('#mobile-menu');
  const open = document.querySelector('[data-menu-open]');
  const close = dialog?.querySelector('[data-menu-close]');
  if (!dialog || !open || !close) return;

  const closeMenu = () => {
    dialog.close();
    document.documentElement.classList.remove('menu-open');
    open.focus();
  };

  open.addEventListener('click', () => {
    dialog.showModal();
    document.documentElement.classList.add('menu-open');
    close.focus();
  });
  close.addEventListener('click', closeMenu);
  dialog.addEventListener('cancel', (event) => { event.preventDefault(); closeMenu(); });
  dialog.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', () => {
      dialog.close();
      document.documentElement.classList.remove('menu-open');
    });
  });
}
```

Do not trap focus manually; native modal dialog semantics provide containment.

- [ ] **Step 6: Implement fragment focus and active navigation in `initNavigation()`**

Export `initNavigation()` from `navigation.js`. For every `a[href^="#"]`, listen for activation, allow the native fragment update, then focus the destination's first heading using temporary `tabindex="-1"`. Use one `IntersectionObserver` with `rootMargin: '-25% 0px -65% 0px'` to set `aria-current="location"` on every desktop/mobile link whose hash matches the most recently intersecting grouped section. Remove `aria-current` from all other section links. The `#top` identity link is excluded from grouped active state. When IntersectionObserver is unavailable, fragment navigation and focus still work without active-state enhancement.

- [ ] **Step 7: Style both header modes**

Below `64rem`, hide desktop navigation and show menu/theme utilities. Make the dialog fill the viewport, use the raised-surface/canvas tokens, numbered 44px+ rows, and no entrance animation. At `64rem+`, show grouped desktop navigation and both utilities, hide the menu trigger, use the exact sticky shadow, and keep header content within `90rem`.

Style `[aria-current="location"]` with two-digit index, `2px` accent rule, and semibold text. Do not rely on color alone.

- [ ] **Step 8: Import and initialize both navigation modules**

Update `main.js` to import and call `initMenu()` and `initNavigation()` once after DOM parsing. Keep `initTheme()` from Task 4. The module must not throw when a control is absent on a state page.

- [ ] **Step 9: Run automated validation**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
git diff --check
```

Expected: header/menu contract passes in both locales.

- [ ] **Step 10: Perform keyboard browser checks**

At `390 × 844`: Tab to Menu, press Enter, verify focus lands on Close; Tab/Shift+Tab remain inside the modal; Escape closes and restores Menu focus; reopen and select Work, verify dialog closes and Work heading receives visible focus. At `1440 × 900`: tab through all five destinations, language, and theme with visible focus and no hidden mobile controls in the order. Scroll through every grouped section and verify the matching link alone receives `aria-current="location"` plus the index/rule/text active treatment.

- [ ] **Step 11: Commit Task 5**

```powershell
git add docs/prototypes/phase-2/es/index.html docs/prototypes/phase-2/en/index.html docs/prototypes/phase-2/styles/header.css docs/prototypes/phase-2/scripts docs/design/phase-2/tests/validate-artifacts.mjs
git commit -m "docs: add accessible phase 2 navigation"
```

### Task 6: Responsive Split hero and editorial introduction

**Files:**
- Create: `docs/prototypes/phase-2/styles/hero.css`
- Modify: `docs/prototypes/phase-2/es/index.html`
- Modify: `docs/prototypes/phase-2/en/index.html`
- Modify: `docs/design/phase-2/tests/validate-artifacts.mjs`

**Interfaces:**
- Consumes: Task 3 portrait/wide assets, approved hero/introduction copy, `#work` destination.
- Produces: `.hero`, `.hero__copy`, `.hero__portrait`, `.editorial-bridge`; exact hero keys and no-overlap responsive behavior.

- [ ] **Step 1: Add failing hero assertions**

Require one H1, `data-content-key="hero.title"`, a CTA `href="#work"`, a `<picture>`, both `profile-portrait.webp` and `profile-wide.webp`, approved localized alt text, and `data-content-key="intro.body"` outside the hero section.

- [ ] **Step 2: Run validation and confirm the `<picture>` assertion fails**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
```

- [ ] **Step 3: Build the exact semantic hero structure**

Use this structure in each locale with only approved localized text:

```html
<section id="top" class="hero" aria-labelledby="hero-title">
  <div class="hero__copy">
    <p class="utility-label">Backend Developer / PHP &amp; Laravel</p>
    <h1 id="hero-title" data-content-key="hero.title">…</h1>
    <p class="hero__value" data-content-key="hero.value">…</p>
    <a class="text-action" href="#work" data-content-key="hero.cta">…</a>
    <p class="hero__availability" data-content-key="hero.availability">…</p>
  </div>
  <picture class="hero__portrait">
    <source media="(max-width: 63.99rem)" srcset="../assets/images/profile-wide.webp">
    <img src="../assets/images/profile-portrait.webp" width="900" height="1200" alt="…">
  </picture>
</section>
```

Correct relative paths for `es/` and `en/` are `../assets/...`.

- [ ] **Step 4: Add the editorial bridge**

Place the exact approved introduction in a separate `<section class="editorial-bridge" aria-labelledby="intro-title">` after the hero. Its localized heading may be visually hidden because it is not a navigation destination, but semantic structure must remain clear.

- [ ] **Step 5: Implement Responsive Split styling**

Below `64rem`, render portrait first and copy on an independent surface with no text overlap. At `64rem+`, use a 7/5 grid split with copy leading and portrait second. Keep the image subject visible with `object-position` matching the approved manifest and reserve any static system motif outside the text/face bounding boxes. The motif must be CSS-only, `aria-hidden`, and visually absent when forced colors are active.

- [ ] **Step 6: Verify responsive, theme, and no-animation behavior**

Inspect `360 × 800`, `390 × 844`, `768 × 1024`, `1024 × 1366`, and `1440 × 900` in both themes. Confirm title and portrait dominate, English/Spanish do not overlap, face/hair/shoulders remain intact, CTA remains visible, and disabling CSS transitions changes no layout.

- [ ] **Step 7: Run validators and commit Task 6**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
node docs/design/phase-2/tests/validate-contrast.mjs
git diff --check
git add docs/prototypes/phase-2/es/index.html docs/prototypes/phase-2/en/index.html docs/prototypes/phase-2/styles/hero.css docs/design/phase-2/tests/validate-artifacts.mjs
git commit -m "docs: build phase 2 hero composition"
```

### Task 7: Cases-first Work with desktop indexed detail and narrow dossiers

**Files:**
- Create: `docs/prototypes/phase-2/styles/work.css`
- Create: `docs/prototypes/phase-2/scripts/work-tabs.js`
- Modify: `docs/prototypes/phase-2/es/index.html`
- Modify: `docs/prototypes/phase-2/en/index.html`
- Modify: `docs/prototypes/phase-2/scripts/main.js`
- Modify: `docs/design/phase-2/tests/validate-artifacts.mjs`

**Interfaces:**
- Consumes: four approved case blocks and two experience paragraphs per locale.
- Produces: `#work`, `[data-work-tabs]`, four `[data-work-tab]`, four `[data-work-panel]`, `initWorkTabs()`; all panels expanded below `64rem` and without JavaScript.

- [ ] **Step 1: Add failing Work structure assertions**

For each locale require four exact case keys, four `data-work-tab` buttons, four `data-work-panel` articles, `aria-controls`/`aria-labelledby` pairs, and experience content after the case container. Assert no `carousel`, horizontal-scroller role, or invented numeric metric pattern appears.

- [ ] **Step 2: Run validation and confirm the tabs assertion fails**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
```

- [ ] **Step 3: Add the cases-first semantic markup**

Use case IDs and keys:

| Number | ID | Content key |
|---:|---|---|
| 01 | `case-integrations` | `work.case.integrations` |
| 02 | `case-education` | `work.case.education` |
| 03 | `case-data-automation` | `work.case.data-automation` |
| 04 | `case-layers` | `work.case.layers` |

Each tab button references its panel. Each panel contains the exact approved heading, paragraph, and only technologies traceable to that case's Phase 1 source comment. All panels start visible in HTML; JavaScript applies `hidden` only in desktop enhanced mode.

- [ ] **Step 4: Place professional experience after cases**

Render the two approved experience paragraphs under a visible localized Experience heading after the case interface. Do not merge employer identity, dates, metrics, or multi-institution wording into this content.

- [ ] **Step 5: Implement `initWorkTabs()`**

Use `matchMedia('(min-width: 64rem)')`. In desktop mode set vertical `aria-orientation`, keep one `aria-selected="true"`, set inactive tab `tabindex="-1"`, and hide inactive panels. Up/Down moves focus, Home/End jumps, Enter/Space activates. In narrow mode restore all tabs to normal document order, remove `hidden`, and present each tab label as its dossier heading without requiring activation.

- [ ] **Step 6: Style both approved modes**

Below `64rem`, use numbered dossier articles separated by rules with title, copy, and restrained technology taxonomy. At `64rem+`, use a 4/8 index-detail grid; active tab gets number + 2px rule + semibold text, selected panel uses `--color-selected`, and panel dimensions do not jump between cases. No cards, pills, terminal chrome, or fake diagrams.

- [ ] **Step 7: Test interaction and fallback**

At `1440 × 900`, verify all specified keys activate correct panels with keyboard and pointer. Resize to `768 × 1024` while a non-first case is selected and confirm all four dossiers appear. Disable JavaScript/reload at desktop and confirm all cases remain readable in source order.

- [ ] **Step 8: Run validators and commit Task 7**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
git diff --check
git add docs/prototypes/phase-2/es/index.html docs/prototypes/phase-2/en/index.html docs/prototypes/phase-2/styles/work.css docs/prototypes/phase-2/scripts docs/design/phase-2/tests/validate-artifacts.mjs
git commit -m "docs: build responsive work evidence prototype"
```

### Task 8: Expertise, scalable Project Dossiers, Approach, and Contact

**Files:**
- Create: `docs/prototypes/phase-2/styles/content.css`
- Modify: `docs/prototypes/phase-2/es/index.html`
- Modify: `docs/prototypes/phase-2/en/index.html`
- Modify: `docs/design/phase-2/tests/validate-artifacts.mjs`

**Interfaces:**
- Consumes: approved specialization, technology, approach, contact, zero-project, link, email, and CV records.
- Produces: complete `#expertise`, `#projects`, `#approach`, and `#contact`; `.project-dossier` contract reusable by Task 9 fixtures; safe locale-specific contact behavior.

- [ ] **Step 1: Add failing content-section assertions**

For both locale pages require:

- Expertise heading plus specialization list and technology groups.
- `#projects` with exactly one `.projects-zero` and no `.project-dossier` in the default page.
- Exact approved zero-project localized sentence.
- Approach heading and approved body.
- LinkedIn URL `https://www.linkedin.com/in/luciano-gonz%C3%A1lez-590350294` or the exact Unicode-equivalent canonical URL.
- GitHub URL `https://github.com/Gonzalez-Luciano`.
- `mailto:lucianogonzalez12004@gmail.com`.
- external links with safe visible labels and `rel="me noopener noreferrer"` where a new browsing context is used.
- no Spanish CV link and one English PDF action referencing `cv-en.pdf`.

- [ ] **Step 2: Run validation and confirm the zero-project/contact assertion fails**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
```

- [ ] **Step 3: Build Expertise without a logo cloud**

Render the six approved specialization items as structured statements. Group the approved technologies by supported function:

- Backend: PHP, Laravel.
- Data: MySQL.
- Integration: REST APIs.
- Collaboration across layers: Angular.

These group labels are organizational UI, not proficiency claims. Do not add percentage levels, years, bars, unapproved tools, or technology icons.

- [ ] **Step 4: Build the permanent zero-project section**

Use a Projects heading, system label, and exact approved zero-state copy. Give the state the same section width, upper rule, and minimum reading area that future Project Dossiers use, but render no empty image box, fake card, “coming soon,” or disabled project link.

Document the reusable populated record in an HTML comment and CSS class contract:

```html
<article class="project-dossier">
  <figure class="project-dossier__media"><!-- optional reviewed media --></figure>
  <div class="project-dossier__body">
    <p class="utility-label"><!-- ordered project index --></p>
    <h3><!-- localized name --></h3>
    <p><!-- localized technical summary --></p>
    <ul class="project-dossier__technologies"><!-- technology names --></ul>
    <div class="project-dossier__links"><!-- render only existing destinations --></div>
  </div>
</article>
```

- [ ] **Step 5: Build Approach and Contact**

Approach uses the approved paragraph and four visual reading beats derived without new claims: understand, consistency, maintainability, communication. Keep the original approved paragraph visible; the beats are exact keywords/taxonomy, not rewritten achievements.

Contact uses visible text actions for LinkedIn, GitHub, email, and the English CV only. Spanish displays no empty/disabled CV slot. Use the approved bilingual accessible names. There is no form, WhatsApp, or calendar.

- [ ] **Step 6: Style all four sections**

Use rules and whitespace rather than rounded card repetition. At `48rem+`, specialization statements may use two columns; technology groups remain textual. Future `.project-dossier` uses a 5/7 media/body split at `48rem+` and stacks below; `.project-dossier--no-media` lets body span the full grid. Contact actions are at least 44px tall and become full-width separated rows on mobile.

- [ ] **Step 7: Verify themes, links, and zero state**

At every reference width, confirm zero Projects remains purposeful, no navigation destination disappears, both themes have equivalent boundaries, external destinations are exact, Spanish CV is omitted, English PDF label is explicit, and 200% zoom produces no horizontal scrolling.

- [ ] **Step 8: Run validators and commit Task 8**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
node docs/design/phase-2/tests/validate-contrast.mjs
git diff --check
git add docs/prototypes/phase-2/es/index.html docs/prototypes/phase-2/en/index.html docs/prototypes/phase-2/styles/content.css docs/design/phase-2/tests/validate-artifacts.mjs
git commit -m "docs: complete phase 2 portfolio sections"
```

### Task 9: Loading, error, missing-CV, and populated-project state demonstrations

**Files:**
- Create: `docs/prototypes/phase-2/states/loading.html`
- Create: `docs/prototypes/phase-2/states/section-error.html`
- Create: `docs/prototypes/phase-2/states/site-error.html`
- Create: `docs/prototypes/phase-2/states/missing-cv.html`
- Create: `docs/prototypes/phase-2/states/projects-populated.html`
- Create: `docs/prototypes/phase-2/styles/states.css`
- Modify: `docs/prototypes/phase-2/index.html`
- Modify: `docs/design/phase-2/tests/validate-artifacts.mjs`

**Interfaces:**
- Consumes: shell/tokens/components from Tasks 4–8.
- Produces: directly addressable state URLs; `.state-banner`, `.skeleton`, `.error-panel`, `.project-dossier` examples; explicit fixture-safety contract.

- [ ] **Step 1: Add failing state-page assertions**

Require all five pages. Assert:

- loading contains `aria-busy="true"`, a localized status message, and `.skeleton` blocks;
- section error contains `role="alert"`, localized plain-language text, and a 44px+ retry button;
- site error preserves header, language/theme controls, safe contact links, and contains no internal error details;
- missing CV shows both locale examples and no fallback from one locale to the other;
- populated projects contains the exact banner `Structural prototype fixture — not Luciano's public work`, `data-fixture="structural"`, one dossier with media, one `.project-dossier--no-media`, and no real/demo/repository URL.

- [ ] **Step 2: Run validation and confirm the first state-page failure**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
```

- [ ] **Step 3: Build the stable loading page**

Reuse the real header and section widths. Render hero, Work, Projects, and Contact-shaped skeletons with fixed aspect ratios/min-heights matching the finished prototype. Add visible localized `Loading portfolio content… / Cargando contenido del portfolio…` status text. Skeletons are static by default and always static under reduced motion; do not use shimmer.

- [ ] **Step 4: Build recoverable section and broad site errors**

Section error replaces only one content region, names the affected section generically, and provides a retry button for demonstration. The button may reset its own visual state locally; it cannot imply an API exists.

Site error keeps identity, language/theme controls, LinkedIn/GitHub/email, and a link back to the prototype index. Use only `We couldn't load the portfolio content` / `No pudimos cargar el contenido del portfolio`; do not expose status codes, endpoints, paths, stack traces, or retry loops.

- [ ] **Step 5: Build the missing-CV demonstration**

Show two labeled locale panels. Spanish omits its CV action while retaining other contact links; English shows its own action. Add an explanatory design annotation outside the simulated public UI: `A locale never receives the other locale's CV as fallback.`

- [ ] **Step 6: Build the populated-project structural fixture**

Use neutral labels `Prototype project record A` and `Prototype project record B`, not plausible product names. Add the exact fixture banner before the section. Record A demonstrates reviewed-media geometry with a CSS abstract media block marked `aria-hidden="true"`; Record B demonstrates no-media full-width copy. Use only fields `Problem`, `Backend solution`, `Technical decisions`, `Technologies`, `Demo link absent`, and `Repository link absent`. Do not state any achievement or domain.

- [ ] **Step 7: Style states and update the index**

Use real semantic tokens. Meaningful error borders use the 3:1 border token and error text uses the error token plus an icon/text label. Disabled/unavailable examples use text plus state label, never opacity alone. Link every state from the prototype index.

- [ ] **Step 8: Verify state accessibility and no-animation behavior**

Keyboard-test retry and shell controls. Inspect light/dark modes, 320px reflow, 200% zoom, and transitions disabled. Confirm the structural fixture cannot be mistaken for Luciano's work and no state invents confidential or professional content.

- [ ] **Step 9: Run validators and commit Task 9**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
node docs/design/phase-2/tests/validate-contrast.mjs
git diff --check
git add docs/prototypes/phase-2/states docs/prototypes/phase-2/styles/states.css docs/prototypes/phase-2/index.html docs/design/phase-2/tests/validate-artifacts.mjs
git commit -m "docs: add phase 2 prototype states"
```

### Task 10: Full browser QA, evidence, documentation closure, and final review

**Files:**
- Create: `docs/design/phase-2/VALIDATION_REPORT.md`
- Create: final PNGs under `docs/prototypes/phase-2/evidence/`
- Modify: prototype/docs files only when a QA defect requires correction.
- Modify: `ROADMAP.md` only after every corresponding artifact and check passes.

**Interfaces:**
- Consumes: all Tasks 1–9 outputs and validators.
- Produces: complete Phase 2 evidence matrix, corrected final prototype, Roadmap truth, final Sol whole-branch review result.

- [ ] **Step 1: Run the complete automated suite from a clean command prompt**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
node docs/design/phase-2/tests/validate-contrast.mjs
& 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' docs/design/phase-2/tests/validate-assets.py
git diff --check
```

Expected: all commands exit 0. Record exact command output and date in `VALIDATION_REPORT.md`.

- [ ] **Step 2: Start the local static server**

```powershell
Start-Process -FilePath 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' -ArgumentList @('-m','http.server','4173','--directory','.') -WindowStyle Hidden
```

Verify `http://127.0.0.1:4173/docs/prototypes/phase-2/es/` responds before browser QA.

- [ ] **Step 3: Run the required viewport/theme/locale matrix**

Use the in-app Browser and record PASS/FAIL for both `/es/` and `/en/` at:

| Viewport | Light | Dark | Required composition |
|---|---|---|---|
| `1440 × 900` | ES + EN | ES + EN | Desktop header, split hero, Indexed Detail Work, horizontal projects contract |
| `1024 × 1366` | ES + EN | ES + EN | `64rem` desktop/indexed boundary |
| `768 × 1024` | ES + EN | ES + EN | Mobile menu, portrait-first hero, dossier Work |
| `390 × 844` | ES + EN | ES + EN | Intentional mobile composition and 44px targets |
| `360 × 800` | ES + EN | ES + EN | Narrow mobile wrapping |

Also test `320px` reflow and browser zoom at `200%` in both locales. Record overflow, wrap, crop, and focus results separately.

- [ ] **Step 4: Capture representative visual evidence**

Save ten screenshots, one light and one dark per reference viewport, pairing ES light and EN dark unless a defect requires additional evidence:

```text
desktop-1440-es-light.png
desktop-1440-en-dark.png
tablet-1024-es-light.png
tablet-1024-en-dark.png
tablet-768-es-light.png
tablet-768-en-dark.png
mobile-390-es-light.png
mobile-390-en-dark.png
mobile-360-es-light.png
mobile-360-en-dark.png
```

Capture additional focused screenshots for the open mobile menu, desktop Work tabs, zero-project state, populated structural fixture, loading, and errors when those states are not legible in the ten baseline images.

- [ ] **Step 5: Run keyboard and accessibility checks**

Record:

- skip link visibility and destination;
- complete Tab/Shift+Tab order;
- mobile dialog focus containment, Escape, destination close, and focus restoration;
- Work Up/Down/Home/End/Enter/Space behavior at `64rem+`;
- all cases readable below `64rem` and with JavaScript disabled;
- theme labels/states and persistence;
- language links;
- visible focus in both themes;
- one H1, heading order, landmarks, accessible names, portrait alt, decorative `aria-hidden` motifs;
- no hover-only content;
- touch target measurements;
- meaningful boundaries and text contrast;
- forced-colors usability;
- `prefers-reduced-motion` and transition-disabled legibility.

- [ ] **Step 6: Correct every observed defect in the owning file**

For each FAIL, add a row with `Finding`, `Viewport/locale/theme`, `Owning file`, `Correction`, and `Retest`. Make the smallest correction in the component/token responsible, rerun the relevant automated suite, and repeat the exact browser scenario until PASS. Do not compensate with one-off inline styles.

- [ ] **Step 7: Complete the validation report**

`VALIDATION_REPORT.md` must contain:

- artifact inventory;
- automated command outputs;
- contrast ratios;
- font/image byte sizes and hashes;
- viewport/theme/locale matrix;
- keyboard/accessibility matrix;
- loading/error/zero/populated-project state results;
- photo crop review;
- JavaScript-disabled/no-transition results;
- corrected findings;
- remaining non-blocking considerations;
- explicit statement that Phase 6 motion was not implemented.

- [ ] **Step 8: Update only earned Roadmap checkboxes**

After all evidence passes:

- Mark Phase 1 photo items `Preparar recorte horizontal y vertical`, `Preparar versión optimizada`, and `Verificar integración en modo claro y oscuro` complete.
- Mark every Phase 2 information-architecture, wireframe, visual-system, and prototype checkbox complete.
- Leave the multi-institution Phase 1 wording item unchecked.
- Leave the Spanish CV metadata/reapproval issue documented and unresolved.
- Do not change Phase 3 or Phase 6 checkboxes.

Add no new scope to `PROJECT.md`, `ARCHITECTURE.md`, `SERVER_ARCHITECTURE.md`, `DEPLOYMENT.md`, or `README.md` unless execution discovers a genuine contradiction; such a contradiction requires explicit architecture review before modification.

- [ ] **Step 9: Run final clean-tree verification**

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
node docs/design/phase-2/tests/validate-contrast.mjs
& 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' docs/design/phase-2/tests/validate-assets.py
git diff --check
git status --short
```

Expected: validators pass; status lists only intended Task 10 evidence/report/Roadmap and any documented QA corrections.

- [ ] **Step 10: Obtain per-task Terra review and final whole-branch Sol review**

The final GPT-5.6 Sol reviewer must compare the entire branch against:

- `docs/superpowers/specs/2026-08-12-phase-2-experience-design.md`;
- every Phase 2 Roadmap line;
- Phase 0/1 approved decisions;
- accessibility, responsive, confidentiality, performance, anti-pattern, and Phase 6 boundary rules.

The review must return either `APPROVED` or actionable findings with file/line references. Resolve every finding and rerun Step 9 before proceeding.

- [ ] **Step 11: Commit Phase 2 closure**

```powershell
git add ROADMAP.md docs/design/phase-2 docs/prototypes/phase-2
git commit -m "docs: complete phase 2 experience design"
```

- [ ] **Step 12: Stop for explicit integration approval**

Report commits, validators, evidence, remaining non-blocking considerations, and the final Sol review. Do not merge to `main`, start Phase 3, or start Phase 6 without Luciano's explicit approval.

## Plan Acceptance Checklist

- Every Phase 2 Roadmap item maps to one concrete task and artifact.
- Every task ends with an independently reviewable deliverable and verification cycle.
- All files, IDs, token names, breakpoints, content sources, commands, state strings, and commits are explicit.
- The prototype remains dependency-free and outside production application directories.
- Both languages, both themes, desktop/tablet/mobile, keyboard, focus, contrast, reduced motion, photography, zero projects, future project dossiers, loading, and errors are covered.
- No advanced motion, application initialization, dependency installation, or implementation worktree is part of the planning session.
- Execution begins only after the parent/controller is GPT-5.6 Terra and every child model is explicit.

## Planning-Session Execution Gate

This document is the terminal artifact of the GPT-5.6 Sol planning session. Do not invoke `subagent-driven-development`, `executing-plans`, create an implementation worktree, spawn an implementer, install assets/dependencies, create any file from Tasks 1–10, or execute Task 1 until Luciano switches the parent/controller to GPT-5.6 Terra and explicitly resumes.
