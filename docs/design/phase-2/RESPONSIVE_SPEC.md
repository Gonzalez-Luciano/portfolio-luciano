# Phase 2 responsive specification

Responsive behavior follows content fit, with `/es` and `/en` independently verified. The primary content width is `90rem` (`1440px` at the default root size), centered. Component gaps use `DESIGN_TOKENS.md`; there are no arbitrary one-off gaps.

## Grid and reference-width matrix

| Width | Grid and gutters | Header/navigation | Hero and Work | Projects, expertise, contact |
|---:|---|---|---|---|
| `320px` | 4 columns, `20px` gutters | Full-screen Menu below `64rem` | Portrait-first stack; sequential dossiers | Stacked projects; single reading flow |
| `360px` | 4 columns, `20px` gutters | Full-screen Menu below `64rem` | Portrait-first stack; sequential dossiers | Stacked projects; single reading flow |
| `390px` | 4 columns, `20px` gutters | Full-screen Menu below `64rem` | Portrait-first stack; sequential dossiers | Stacked projects; single reading flow |
| `768px` (`48rem`) | 8 columns, `32px` gutters | Full-screen Menu below `64rem` | Portrait-first stack; sequential dossiers | Project dossiers horizontal at `48rem+`; expertise/contact may align by content fit |
| `1024px` (`64rem`) | 12 columns, `48px` gutters | Sticky desktop header at `64rem+` | Responsive Split hero; Indexed Detail vertical tabs | Horizontal project dossiers; multi-column alignment where copy fits |
| `1440px` (`90rem`) | 12 columns, `64px` gutters from `80rem`; centered max width `90rem` | Sticky desktop header | Responsive Split hero; Indexed Detail vertical tabs | Horizontal dossiers and multi-column alignment |

## Exact breakpoint contracts

| Range | Container contract | Required switches |
|---|---|---|
| Below `48rem` | 4 columns; `20px` gutters | Single-reading-flow expertise/contact; projects stack; mobile menu; portrait-first hero; Work Numbered Dossiers. |
| `48rem` through `63.99rem` | 8 columns; `32px` gutters | Project Dossiers become horizontal at `48rem`; mobile menu, portrait-first hero, and Work Numbered Dossiers remain. |
| `64rem` through `79.99rem` | 12 columns; `48px` gutters | Sticky desktop header replaces menu; hero changes to split; Work changes to Indexed Detail vertical tabs. |
| `80rem` through `90rem` | 12 columns; `64px` gutters | Same desktop compositions with wide-desktop gutters. |
| At and above `90rem` | Centered primary content max-width `90rem`; retain `64px` gutters | Do not widen reading measures beyond the max-width contract. |

The full-screen numbered Menu is used below `64rem`; the desktop header is used at `64rem+`. Below `64rem`, every Work case is a sequential semantic Numbered Dossier. At `64rem+`, Work is Indexed Detail with vertical tabs; inactive panels may hide only after enhancement, and all cases remain expanded without JavaScript. Hero split/stack switches at `64rem`. Project horizontal/stack switches at `48rem`.

## Reflow and component acceptance

Every component—header and menu, hero and portrait, editorial bridge, Work and experience, expertise and technologies, Projects, Approach, Contact/CV, loading, recoverable error, and broad error—must meet the following at each matrix width:

| Acceptance area | Exact contract |
|---|---|
| Spanish and English | Review separately. Breakpoints never assume identical line lengths; labels wrap or recompose without clipping or overlap. |
| `320px` reflow | No horizontal page scroll, clipped content, hidden actions, or overlapping controls. |
| `200%` zoom | Same no-clipping/no-overlap/no-horizontal-scroll result; reading order and all actions remain available. |
| Touch | Intended controls retain at least `44 × 44px` targets, including menu, close, theme, language, navigation, retry, contact, download, and external-link controls. |
| Portrait crop | Portrait-first mobile frame and desktop split use separate art-directed crops; no text overlaps the face, and face/shoulders remain intact. |
| No animation | With transitions disabled or reduced motion requested, content, focus, fragment placement, menu states, selected Work state, loading, and errors remain understandable and usable. |
| Content fit | Body normally remains around `60–70ch`; long localized copy pushes layout naturally rather than truncating. |
| Projects | The durable zero-project section remains visible; no empty cards, placeholders, or invented project material appears. |
| Interaction | Mobile Menu contains focus, closes with Escape, locks background scroll, restores trigger focus, and closes after a destination. Desktop Work supports Up/Down/Home/End/Enter/Space. |

Direct fragments use sticky-header scroll margin at every width. Focus lands on a sensible section landmark or heading without duplicate tab stops. Smooth scrolling uses the standard motion token only without reduced motion; otherwise it is immediate.
