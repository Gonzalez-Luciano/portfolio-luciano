# Phase 2 validation report

- Date: 2026-08-12 (America/Argentina/Buenos_Aires)
- Scope: static, dependency-free Phase 2 prototype only.
- Local server: `http://127.0.0.1:4173`, served from the repository root with the prescribed Python `http.server` command. `GET /docs/prototypes/phase-2/es/` returned `200 OK`.
- Browser: Codex in-app Browser, using its responsive viewport capability.

## Artifact inventory

| Deliverable | Evidence |
| --- | --- |
| Sitemap, wireframes, tokens and responsive contract | `SITEMAP.md`, `WIREFRAMES.md`, `DESIGN_TOKENS.md`, `RESPONSIVE_SPEC.md` |
| Bilingual high-fidelity prototype | `docs/prototypes/phase-2/es/index.html`, `en/index.html`, local CSS and progressive-enhancement scripts |
| States | loading, section error, site error, missing-CV, and explicitly structural populated-project fixture under `docs/prototypes/phase-2/states/` |
| Asset provenance | `docs/prototypes/phase-2/assets/ASSET_MANIFEST.md` |
| Visual evidence | 10 required baseline PNGs plus corrected focused, no-JS, and state screenshots under `docs/prototypes/phase-2/evidence/` |

## Automated verification

Executed from the Phase 2 worktree on 2026-08-12:

```text
> node docs/design/phase-2/tests/validate-artifacts.mjs
Phase 2 artifact contracts pass.

> node docs/design/phase-2/tests/validate-contrast.mjs
light primary: 13.89:1 (minimum 4.5:1)
light muted: 5.73:1 (minimum 4.5:1)
light accent: 4.94:1 (minimum 4.5:1)
light meaningful border: 3.47:1 (minimum 3:1)
light error: 6.90:1 (minimum 4.5:1)
dark primary: 15.00:1 (minimum 4.5:1)
dark muted: 8.46:1 (minimum 4.5:1)
dark accent: 6.39:1 (minimum 4.5:1)
dark meaningful border: 4.06:1 (minimum 3:1)
dark error: 8.67:1 (minimum 4.5:1)

> & 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' docs/design/phase-2/tests/validate-assets.py
Phase 2 assets pass.

> git diff --check
exit 0
```

The artifact validator also proves the semantic shell, approved localized content, local fonts/assets, zero-project contract, state safety, and the corrected state-theme bootstrap contract.

## Asset sizes and hashes

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| `InstrumentSans-Variable.woff2` | 88,784 | `aa72922aafcc0dc18f36ec1d805b0212057dabe8b9d5b8b57f67035aea1b826d` |
| `IBMPlexMono-Regular-Latin1.woff2` | 17,544 | `e8993d946649b9d01abb1ed06d574b19d8ea3e66b5c3948602db335c44c18e56` |
| `IBMPlexMono-SemiBold-Latin1.woff2` | 17,872 | `b7acd05041ab65f3b7039e218ddd893065e11a07e85ea85019473152a51b6b7d` |
| `profile-portrait.webp` | 77,638 | `3999cd8675b6923cf4a5c63455791b7cceb1faa237ac596d5e37afd640492489` |
| `profile-wide.webp` | 45,342 | `37b0647960591aeee3b3cae9821dd55a930d2bef3a4150dc3fbba91b9449c29f` |

The asset validator confirms the two WebP derivatives' dimensions, maximum size, empty EXIF, and manifest integrity.

## Viewport, locale, and theme matrix

All 20 required combinations passed the composition, one-H1, theme, zero-project, 44px-target, and horizontal-overflow checks.

| Viewport | ES light | ES dark | EN light | EN dark | Verified composition |
| --- | --- | --- | --- | --- | --- |
| 1440 x 900 | PASS | PASS | PASS | PASS | Desktop header, split Hero, indexed Work tabs, horizontal future-dossier contract |
| 1024 x 1366 | PASS | PASS | PASS | PASS | Exact `64rem` desktop/indexed boundary |
| 768 x 1024 | PASS | PASS | PASS | PASS | Full-screen mobile-menu control, portrait-first Hero, all Work dossiers visible |
| 390 x 844 | PASS | PASS | PASS | PASS | Intentional mobile composition; visible tested targets at least 44px |
| 360 x 800 | PASS | PASS | PASS | PASS | Narrow wrapping without page overflow |

At 320 x 800, ES/EN in light/dark each passed: `scrollWidth <= innerWidth`, heading fit, and all visible tested controls measured at least 44px. At the default vertical scrollbar configuration, `clientWidth` is 15px smaller than `innerWidth`; the page's `scrollWidth` equals `innerWidth`, which is the no-horizontal-page-scroll condition.

The required baseline evidence files are:

```text
desktop-1440-es-light.png   desktop-1440-en-dark.png
tablet-1024-es-light.png    tablet-1024-en-dark.png
tablet-768-es-light.png     tablet-768-en-dark.png
mobile-390-es-light.png     mobile-390-en-dark.png
mobile-360-es-light.png     mobile-360-en-dark.png
```

Additional focused evidence: `mobile-menu-es-light.png`, `work-tabs-en-light.png`, and `zero-project-es-light.png` were recaptured with an actual light document theme. `state-site-error-mobile-light.png`, `state-site-error-mobile-dark.png`, `state-site-error-tablet-light.png`, and `state-site-error-dark.png` prove the corrected broad-failure shell. `no-js-390-es-light.png` and `no-js-390-en-dark.png` prove the genuine disabled-JavaScript fallback.

## Interaction and accessibility checks

| Check | Result | Evidence |
| --- | --- | --- |
| Semantic structure | PASS | Browser matrix: one `h1`; source/validator: main landmark, labeled sections, headings, bilingual portrait alts, and decorative fixture media `aria-hidden`. |
| Theme and language persistence | PASS | In ES, selecting dark set `aria-pressed="true"` and the localized light-action label; mobile language navigation to EN retained dark and used the English light-action label. |
| Direct fragments | PASS | `/es/#projects` focused `projects-heading`, updated the hash, and scrolled to the Projects section. Mobile menu destination selection focused `work-heading` after closing. |
| Mobile menu open/close | PASS | Click open focused Close and applied scroll lock; Click close removed lock and restored focus to Menu; destination click closed then focused the target heading. |
| Desktop Work tabs | PASS | At 1024px, pointer selection changed exactly one visible panel. ArrowDown moved focus without activation; Enter activated; Home moved focus to first; End then Space activated the last case. |
| Active and focus treatment | PASS | Browser visual evidence shows 3px orange outline plus geometry; active desktop navigation uses index, accent rule, and weight rather than color only. |
| Contact and external links | PASS | Validator checks exact LinkedIn, GitHub, `mailto:`, safe `rel`, English CV action, and Spanish CV omission. |
| States and confidentiality | PASS | Each state has no overflow in light and dark; loading has `aria-busy`/status/static skeletons; errors are plain-language alerts; the fixture is structural, labeled, and URL-free. |
| Broad site-error shell below 64rem | PASS | At 390 x 844 and 768 x 1024 in light/dark, both visible controls have 44px minimum heights: language was 46–47px wide and Theme 82–84px wide; the Theme click changed the mobile document state to dark with `aria-pressed="true"`. |
| Genuine disabled-JavaScript fallback | PASS | Regular Playwright contexts with `javaScriptEnabled: false` checked ES/light and EN/dark at 390px: system canvas colors applied, `data-theme` and `.js` were absent, Menu/Theme controls were hidden, localized `noscript` navigation was visible, all dossiers remained visible, and no horizontal overflow occurred. |
| Static/reduced-motion policy | PASS | Search found no active transition or animation. Skeletons declare `animation: none; transition: none`; the reduced-motion rule additionally clamps nonessential timing. `navigation.js` uses one one-shot `window.setTimeout` only to schedule fragment-heading focus after navigation; it is not a continuous timer or Phase 6 motion. No Phase 6 runtime, canvas, WebGL, GSAP, or Motion exists. |

### Browser-control limitations recorded honestly

The original in-app Browser session supported the base matrix, then became unavailable during review follow-up (`agent.browsers.list()` returned `[]`). The permitted frontend-testing fallback used the locally available Playwright package and installed Chromium 1217 after the in-app Browser path was exhausted. Its only non-app console noise was the static server's absent `/favicon.ico`; no prototype resource or runtime error was observed.

- **200% browser zoom: NOT RUN as an exact browser-zoom check.** Both attempted `Ctrl++` spellings left `visualViewport.scale` at `1`. An equivalent 512px CSS-viewport reflow check passed for both locales, but it is not recorded as a 200% browser-zoom PASS.
- **JavaScript-disabled visual run: PASS with fallback Playwright.** The disabled-JavaScript contexts described above exercised the actual local pages, not a source-only substitute.
- **Forced-colors visual run: NOT RUN in-browser.** The capability is unavailable. Source review confirms the Hero has a forced-colors fallback and all semantics/outline focus remain present; this is not labeled as emulated forced-colors PASS.
- **Complete native-dialog Tab/Shift+Tab/Escape traversal: NOT RUN end-to-end.** The browser bridge did not surface native dialog default actions for those key probes, although button, destination, and Work keyboard commands work. Source review confirms native `<dialog>`, `cancel`, scroll-lock, and restoration logic. A human assistive-technology/browser pass remains appropriate before release.

## Photo crop review

The wide and portrait derivatives preserve Luciano's face, hair, shoulders, natural color, and orange background. Desktop evidence confirms a readable 7/5 split without text over the face; mobile evidence confirms an upper portrait frame and an independent copy surface. Light and dark screenshots retain the same readable hierarchy and photo prominence. No image metadata or confidential content was detected by the asset validator.

## Corrected finding

| Finding | Viewport/locale/theme | Owning file | Correction | Retest |
| --- | --- | --- | --- | --- |
| Four state pages had no safe theme bootstrap, so a stored/system dark preference could not reach them. | State pages, both themes | `states/loading.html`, `section-error.html`, `missing-cv.html`, `projects-populated.html`; site-error bootstrap was also inconsistent | Added the same validated `light`/`dark` bootstrap used by the localized pages and extended the artifact contract for every state page. | Artifact, contrast, asset validators passed; all five state pages passed in light and dark at 768 x 1024. |
| The broad site-error shell hid its only language control below `64rem`. | 390 x 844 and 768 x 1024, light/dark | `states/site-error.html`, `styles/header.css` | Added a state-specific language-control hook and a below-`64rem` visibility rule; Theme remains visible without relying on a mobile menu. | Four rendered Playwright combinations passed, including 44px targets, no overflow, safe links, and a mobile Theme interaction. |
| Focused screenshot filenames said `light` while their pixels showed dark theme. | Focused evidence | `docs/prototypes/phase-2/evidence/` | Recaptured `zero-project-es-light.png`, `mobile-menu-es-light.png`, and `work-tabs-en-light.png` in a fresh light Playwright context, asserting `data-theme="light"` before capture. | Visual inspection and runtime checks confirm light canvas and the expected focused state. |
| No-JS behavior relied on JavaScript to choose a theme and displayed unusable JS-only controls. | 390px, ES/light and EN/dark | `styles/tokens.css`, `styles/header.css`, `scripts/main.js`, locale HTML behavior, `README.md` | Added a CSS `prefers-color-scheme` fallback when no `data-theme` exists; gates Theme/Menu behind `html.js`; preserves `noscript` navigation. | Validator contract plus genuine `javaScriptEnabled: false` Playwright checks passed in both system themes. |

## Remaining non-blocking considerations

- Perform exact 200% zoom, forced-colors, and full native-dialog keyboard traversal manually in a browser that exposes those controls before a production release.
- The Spanish CV metadata/reapproval issue remains unresolved; the Spanish action remains omitted. The combined multi-institution wording remains intentionally unapproved and absent.
- Phase 6 motion was **not implemented**. This Phase 2 prototype is static and dependency-free.
- The final whole-branch GPT-5.6 Sol review is intentionally deferred to the controller; this implementer did not perform or claim that review.
