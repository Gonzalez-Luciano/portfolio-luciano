# Phase 2 static prototype

This dependency-free artifact validates the approved bilingual experience design. It is not the production portfolio, CMS, or application code.

Serve the repository root with:

```powershell
& 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' -m http.server 4173 --directory .
```

Open `http://127.0.0.1:4173/docs/prototypes/phase-2/`, `http://127.0.0.1:4173/docs/prototypes/phase-2/es/`, or `http://127.0.0.1:4173/docs/prototypes/phase-2/en/`. Future state URLs are listed in the index as their later tasks add them.

The theme button works with keyboard Enter/Space and persists only explicit `light` or `dark` choices in `localStorage` under `portfolio-prototype-theme`. Without JavaScript, CSS applies the system light/dark preference, the JavaScript-dependent theme and Menu buttons stay hidden, and the visible `noscript` navigation preserves every localized destination. Language links and all content remain ordinary HTML links/content.

Do not treat approved copy, portrait derivatives, contact destinations, or CV links here as a publication action. This prototype contains no production application code and no external runtime assets.

Validate with:

```powershell
node docs/design/phase-2/tests/validate-artifacts.mjs
node docs/design/phase-2/tests/validate-contrast.mjs
& 'C:\Users\lucho\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' docs/design/phase-2/tests/validate-assets.py
```
