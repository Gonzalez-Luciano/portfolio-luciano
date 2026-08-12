# Phase 2 Experience Design

**Date:** 2026-08-12  
**Status:** Design approved; written specification awaiting user review  
**Scope:** Complete experience design, design artifacts, and a static high-fidelity prototype. No Next.js/Laravel initialization, production frontend code, CMS integration, implementation dependencies, or Phase 6 motion system.

## 1. Purpose and success condition

Phase 2 designs the complete bilingual portfolio experience before complex animation or application implementation. The result must make this message immediately credible:

> Luciano González is a Backend Developer focused on PHP and Laravel who works on real backend systems.

The approved identity is **Operational Editorial**: professional, technical, warm, structured, and distinctive. It combines strong editorial typography, restrained system notation, the approved professional portrait, and visible evidence of backend work. It must not resemble a frontend-first portfolio, design agency, game, crypto product, generic SaaS landing page, or artificial terminal.

Phase 2 succeeds when the sitemap, wireframes, visual system, responsive specification, and static high-fidelity prototype satisfy every Phase 2 Roadmap requirement and remain clear with animation disabled.

## 2. Authoritative inputs and boundaries

This specification is subordinate to and consistent with:

- `AGENTS.md`
- `ROADMAP.md`
- `docs/PROJECT.md`
- `docs/ARCHITECTURE.md`
- `docs/SERVER_ARCHITECTURE.md`
- `docs/DEPLOYMENT.md`
- `docs/superpowers/specs/2026-08-11-phase-0-foundation-design.md`
- `docs/superpowers/plans/2026-08-11-phase-1-content-narrative.md`
- all approved files under `docs/content/`

Phase 1 has no issue that blocks Phase 2. Its remaining work is classified as follows:

- Photo crop direction, prototype-ready derivatives, and light/dark visual validation are resolved naturally in Phase 2.
- Public wording for multi-institution support remains deferred because no approved copy exists. Phase 2 supports that content structurally but does not invent it.
- Production media delivery and final CMS optimization remain later integration work.
- The Spanish CV's incorrect `/Lang(en-US)` tag must be corrected and the file reapproved before publication. Until a locale-specific CV is available and approved, its download action is omitted.
- Full CV text comparison may remain deferred and cannot be used to create new claims during Phase 2.

Phase 2 does not initialize `web/`, `api/`, or `infra/`; install packages; consume the future Laravel API; or implement Motion, GSAP, ScrollTrigger, node-field behavior, scroll narratives, or other Phase 6 work.

## 3. Sitemap and definitive information architecture

The public product remains one localized single page per canonical locale, `/es` and `/en`.

```text
/{locale}
├── Hero
├── Professional introduction (editorial bridge; no navigation item)
├── Work
│   ├── Anonymous work cases
│   └── Professional-experience context
├── Expertise
│   ├── Areas of specialization
│   └── Technologies
├── Projects
├── Approach
└── Contact and CV
```

Definitive content order:

1. Hero.
2. Concise professional-introduction bridge.
3. Work: anonymous cases first, professional-experience context second.
4. Expertise: specializations first, technologies second.
5. Projects.
6. Approach.
7. Contact and CV.

This cases-first sequence makes concrete backend evidence the first substantial content after the hero. It does not change the approved Phase 1 wording or invent greater detail.

## 4. Navigation and internal links

### Desktop

The header is persistently sticky and compact. It contains:

- a concise Luciano/backend identifier linking to the page start;
- five localized destinations: Work, Expertise, Projects, Approach, Contact;
- language control;
- theme control.

The active destination uses its two-digit section index, a `2px` accent rule, and semibold text; color alone is insufficient. The sticky surface uses the solid raised-surface token and the visual system's only routine elevation shadow.

### Mobile

The closed header retains the identity, theme control, and an explicitly named Menu control. Language selection moves into the menu.

The open menu is a full-screen editorial index below `64rem`; at `64rem` and above, the desktop header is used:

- numbered destinations `01` through `05`;
- large, separated touch targets;
- an explicit Close control;
- language selection near the lower edge;
- focus containment;
- Escape-to-close;
- background scroll lock;
- focus restoration to the Menu trigger;
- immediate close and correct focus/scroll placement after choosing a destination.

### Fragment behavior

- Use real locale-specific fragment links with stable ASCII IDs.
- Apply `scroll-margin` for the sticky header.
- Direct fragment URLs must land correctly without JavaScript.
- Keyboard activation must move focus to a sensible section landmark or heading without creating duplicate tab stops.
- Smooth scrolling uses the standard motion token only when reduced motion is not requested; otherwise navigation is immediate.
- Browser history and URL fragments remain understandable; navigation cannot depend on transient JavaScript state.

## 5. Section wireframe specification

### Hero

Use the approved **Responsive Split** composition.

Desktop places the backend message and primary CTA in the leading text region and the art-directed portrait in the second region. The name/title, concise value proposition, and photograph dominate together. The CTA retains the approved localized meaning, “View experience,” and targets the start of the Work evidence sequence.

Mobile is deliberately recomposed: the portrait receives an upper art-directed frame and the message receives an independent reading surface below it. Text cannot overlap the face. A bounded decorative background zone may contain a static system/node motif in Phase 2 and future Phase 6 behavior, but it cannot reduce readability or become necessary to understand the hero.

### Professional introduction

This is a short editorial bridge rather than a full “About” destination. It provides human context between the opening promise and work evidence, with a restrained line length and no decorative card container.

### Work cases and experience

At `64rem` (`1024px` with the default root size) and above, use the approved **Indexed Detail** composition:

- a visible ordered case index;
- a detailed panel for the selected case;
- clear active state beyond color;
- a vertical tabs interaction: Up/Down moves tab focus, Home/End moves to the first/last tab, and Enter/Space activates the focused case;
- all tab panels present in logical DOM order, with inactive panels hidden only after enhancement initializes;
- an expanded all-cases presentation when JavaScript is unavailable.

Below `1024px`, including most portrait tablets, use **Numbered Dossiers**. Every case appears as a sequential semantic article with its number, title, approved description, and relevant technology taxonomy. Nothing is hidden behind a carousel, horizontal scroller, or tabs.

Professional-experience context follows the cases. It is concise and supports the evidence rather than competing with it.

### Expertise

Areas of specialization appear as structured statements with short supporting copy. Technologies follow in meaningful functional groups rather than a logo cloud or decorative badge wall. Suggested groups may reflect the approved content model—backend, data, integration, and collaboration—but labels must be derived from approved content during execution.

No proficiency percentages, unverified levels, fake metrics, or ornamental progress bars are allowed.

### Projects

Use the approved **Project Dossiers** system. Each published project may contain:

- optional reviewed media;
- localized name and descriptions;
- technical explanation;
- technology list;
- optional demo link;
- optional repository link;
- stable order and featured state from the future CMS.

Desktop dossiers are horizontal records; narrow layouts stack media above content. When media is absent, the text region expands and no empty placeholder remains. Link controls appear only when their destinations exist.

With zero published projects, retain the Projects heading and approved honest Phase 1 message. Do not render empty cards, invented project names, “coming soon” promises, progress indicators, or unapproved GitHub repositories. Projects remains a navigation destination so publishing a project later does not alter page architecture.

### Approach

Use a short ordered working model based only on approved copy: understand the problem, protect logic/data consistency, leave maintainable solutions, and communicate clearly within the application's context. Avoid process theater, fake diagrams, and claims of a methodology not supported by Phase 1.

### Contact and CV

Create a strong, calm closing section with LinkedIn, GitHub, public email, and the locale-specific CV when available. There is no form, WhatsApp action, or meeting calendar. External links and download behavior must be explicit. A missing or unapproved CV is omitted rather than replaced with the other locale's file.

### Mobile menu

The mobile-menu wireframe must document closed, opening-independent open, focus, active-language, and close states. Phase 2 does not implement entrance/exit animation.

### Loading and error states

Loading uses stable section-shaped skeletons or neutral placeholders that preserve layout. Skeleton animation is not required; reduced-motion mode is static.

A recoverable section failure uses localized plain language and a retry control only when retry is meaningful. It does not expose stack traces, routes, server paths, or internal identifiers. Broad content/API failure preserves the page shell and usable navigation/contact information that is safely available, and presents a clear localized error state. Errors cannot imitate unpublished content.

## 6. Responsive system

Responsive decisions follow content fit rather than named devices.

Reference validation widths:

- Desktop: `1440 × 900`.
- Tablet wide/portrait: `1024 × 1366`.
- Tablet narrow/portrait: `768 × 1024`.
- Mobile: `390 × 844` and `360 × 800`.
- Minimum reflow check: `320` CSS pixels.

Grid guidance:

- Desktop: 12 columns, `64px` gutters from `80rem` upward and `48px` between `64rem` and `79.99rem`.
- Tablet: 8 columns and `32px` gutters from `48rem` through `63.99rem`.
- Mobile: 4 columns and `20px` gutters below `48rem`.
- Primary content max-width: `90rem` (`1440px` at the default root size), centered.
- Component gaps derive from the spacing tokens, not arbitrary one-off values.

Key composition switches:

- Work uses Indexed Detail at `min-width: 64rem`; otherwise it uses Numbered Dossiers.
- The hero changes from a side-by-side split to portrait-first stacking.
- Project dossiers change from horizontal to stacked.
- Expertise and contact move from multi-column alignment to a single reading flow.
- The desktop navigation becomes the full-screen mobile menu when the five destinations plus required controls no longer fit without crowding.

English and Spanish must be tested independently. No breakpoint may assume identical line lengths. The page must survive 200% zoom and text reflow without clipped content, overlapping controls, hidden actions, or horizontal page scrolling.

## 7. Visual identity and tokens

### Color semantics

Light theme baseline:

| Token role | Value | Intended use |
|---|---:|---|
| Canvas | `#EEE9DE` | Primary page background |
| Raised surface | `#F6F2E9` | Header, selected detail, functional separation |
| Primary text | `#1B1E1C` | Headings and body |
| Muted text | `#565B56` | Secondary descriptions and metadata |
| Accent | `#A94322` | Links, focus-supporting emphasis, indices, active rules |
| Meaningful border | `#817B70` | Controls and boundaries that require 3:1 contrast |
| Disabled text | `#5F635E` | Unavailable labeled controls; never the only state cue |
| Selected surface | `#E4D8C9` | Active case/project detail surface |
| Error | `#8B2E24` | Error text/icons with a written label |

Dark theme baseline:

| Token role | Value | Intended use |
|---|---:|---|
| Canvas | `#171918` | Primary page background |
| Raised surface | `#202321` | Header, selected detail, functional separation |
| Primary text | `#F1ECE1` | Headings and body |
| Muted text | `#B8B3A9` | Secondary descriptions and metadata |
| Accent | `#F07A4B` | Links, focus-supporting emphasis, indices, active rules |
| Meaningful border | `#777A74` | Controls and boundaries that require 3:1 contrast |
| Disabled text | `#96958F` | Unavailable labeled controls; never the only state cue |
| Selected surface | `#2B2926` | Active case/project detail surface |
| Error | `#FF9B8A` | Error text/icons with a written label |

Initial verified contrast ratios against their intended canvases are:

- Light primary: `13.89:1`.
- Light muted: `5.73:1`.
- Light accent: `4.94:1`.
- Dark primary: `15.0:1`.
- Dark muted: `8.46:1`.
- Dark accent: `6.39:1`.
- Light meaningful border: `3.47:1`.
- Dark meaningful border: `4.06:1`.
- Light error: `6.90:1`.
- Dark error: `8.67:1`.

Subtle decorative rules may use lower-contrast colors because they convey no required information; interactive boundaries use the meaningful-border token. Focus rings use the theme accent and are supplemented by outline geometry. Execution must verify every actual foreground/background pairing. The accent is not a decorative gradient. Theme quality must remain equivalent.

Theme behavior follows the Phase 0 contract: initial rendering respects the system preference, an explicit user choice persists, and the page avoids a noticeable incorrect-theme flash. The control exposes the current state with an accessible name and does not rely on sun/moon icons alone.

### Typography

- Primary family: Instrument Sans.
- Technical/utility family: IBM Plex Mono.
- Both families are sourced from their official open-source repositories and use the SIL Open Font License: [Instrument Sans](https://github.com/Instrument/instrument-sans) and [IBM Plex](https://github.com/IBM/plex). Self-host deliberately limited WOFF2 weights/subsets rather than depending on a third-party font request.
- Instrument Sans handles headings, body, navigation, and controls.
- IBM Plex Mono is restricted to indices, technical taxonomy, metadata, and compact system labels. It must not create a terminal simulation.

Fluid target ranges:

| Role | Range | Notes |
|---|---:|---|
| Display/H1 | `56–112px` | Tight measure; responsive clamp |
| Section/H2 | `40–72px` | Clear section rhythm |
| Subheading/H3 | `28–44px` | Cases and dossier titles |
| Lead | `20–26px` | Hero and editorial bridge |
| Body | `17–19px` | `1.6` line height |
| Utility | `12–14px` | No essential text below `12px` |

Body measure should normally remain within approximately `60–70ch`. Spanish and English wrapping must be reviewed at every target width.

### Spacing, geometry, borders, and shadows

- Base unit: `4px`.
- Named spacing steps: `4, 8, 12, 16, 24, 32, 48, 64, 96, 128`.
- Radius steps: `0, 4, 8px`.
- Standard rule: `1px`.
- Emphasis rule: `2px`.
- Large rounded cards, filler pills, excessive badges, and glass panels are prohibited.
- Sticky-header shadow: `0 8px 24px rgb(0 0 0 / 8%)` in light and `0 8px 24px rgb(0 0 0 / 24%)` in dark.
- The open full-screen mobile menu needs no floating-panel shadow because it replaces the viewport surface.
- Ordinary content sections remain grounded by rules, contrast, and spacing.

### Photography

The source portrait is the approved 1200×1600 orange-background image.

- Preserve recognizable natural color and the orange signature.
- Create separate art-directed portrait and wider responsive derivatives during Phase 2 execution.
- Retain the face and shoulders; do not stretch one crop across every ratio.
- Do not use a circular avatar, aggressive cutout, background replacement, strong duotone, or text over the face.
- Apply only restrained theme-specific tonal adjustment when necessary for harmony.
- Optimize dimensions, format, quality, and metadata.
- Re-review final derivatives for confidentiality/metadata and both-theme suitability.
- Use the approved bilingual alt text from `docs/content/ASSET_INVENTORY.md`.

### Iconography

- Use a restrained `20/24px` system with `1.5px` strokes for generic UI icons.
- Icons supplement visible text for menu, theme, language, download, and external-link actions.
- Social brand marks must be accurate and accessible if used.
- Technology presentation does not become a logo wall.
- Avoid decorative code glyphs and arbitrary infrastructure icons.

### Component states

- Hover: underline/rule or border emphasis plus restrained color change.
- Focus: persistent `3px` theme-accent ring with `2px` offset.
- Active: rule/index and text treatment; never color only.
- Pressed: tonal change without layout movement.
- Disabled/unavailable: muted and explicitly unavailable, or omitted when omission prevents confusion.
- Pointer targets: minimum `44 × 44px` where controls are intended for touch.
- Content cannot depend on hover.

## 8. Motion principles and Phase 6 boundary

Phase 2 records motion tokens only:

- Fast: `120ms`.
- Standard: `200ms`.
- Deliberate: `320ms`.
- Default easing: `cubic-bezier(0.2, 0.8, 0.2, 1)`.

Phase 2 may use these values for simple state previews only. It cannot implement hero timelines, section entrances, scroll-driven sequences, pinned storytelling, GSAP, ScrollTrigger, pointer-reactive nodes, canvas/WebGL effects, or animation performance systems. Reduced motion removes nonessential transitions. The full design must remain strong and understandable when every transition is disabled.

## 9. High-fidelity prototype strategy

Phase 2 execution creates a dependency-free prototype under:

`docs/prototypes/phase-2/`

Expected artifact responsibilities:

- semantic HTML for the complete page and landmarks;
- CSS custom properties for light/dark themes and responsive compositions;
- approved bilingual content and photograph;
- a small dependency-free script only for theme, locale, mobile menu, and desktop work-case selection;
- state demonstrations for loading, recoverable error, broad failure, missing CV, zero projects, and a clearly labeled structural populated-project example;
- no API, framework, package installation, build system, animation library, video, canvas, or WebGL.

The public/default prototype state uses zero projects. Any populated-project fixture is visibly labeled as structural demonstration content, cannot be confused with Luciano's work, and cannot enter production CMS content.

The prototype is a validation artifact, not a parallel CMS or future production source of truth. Phase 5 will translate approved patterns into focused Next.js components consuming Laravel-managed content.

## 10. Accessibility and interaction acceptance

The prototype and specifications must demonstrate:

- semantic landmarks and a single clear H1;
- logical heading hierarchy;
- skip-link behavior;
- keyboard access to every action;
- visible focus in both themes;
- correct mobile-menu focus containment/restoration;
- accessible language and theme names/states;
- current navigation state not conveyed by color alone;
- approved meaningful photo alt text;
- decorative system motifs hidden from assistive technology;
- no hover-only content;
- no horizontal page scroll at 320 CSS pixels or 200% zoom;
- WCAG AA contrast for text and meaningful boundaries;
- minimum touch targets;
- understandable content with JavaScript and transitions disabled;
- reduced-motion compatibility for future Phase 6 work.

## 11. Performance constraints

- Keep fonts self-hostable and limit families, weights, and subsets deliberately.
- Produce responsive optimized photo derivatives; do not load the source image indiscriminately at every size.
- Avoid video, canvas, WebGL, continuous timers, and animation dependencies.
- Preserve a fast, readable hero before future enhancements.
- Do not require client-side rendering for static content.
- Treat the future node field as an optional dynamically loaded enhancement outside Phase 2.
- Record representative asset sizes during prototype validation.

## 12. Validation matrix

Phase 2 execution must review:

- desktop, wide tablet, narrow tablet, and two mobile reference sizes;
- both themes at every representative composition;
- Spanish and English wrapping;
- zero and structural populated-project states;
- loading and error states;
- all interactive control states;
- keyboard-only traversal;
- 200% zoom and 320px reflow;
- contrast and focus visibility;
- photo crops in both themes;
- menu open/close and focus behavior;
- Work's Indexed Detail and Numbered Dossier variants;
- legibility with JavaScript disabled;
- legibility with all transition declarations disabled.

Visual review evidence should include representative screenshots for desktop, tablet, and mobile in light and dark themes. Any failure must be corrected in the prototype and relevant specification before a Roadmap checkbox is marked complete.

## 13. Explicit anti-pattern exclusions

The approved design excludes:

- arbitrary purple gradients;
- generic SaaS hero/feature/pricing composition;
- glassmorphism;
- bento grids used as default structure;
- endless rounded cards;
- filler pills and badges;
- fake dashboards, terminals, metrics, code, logs, or infrastructure screens;
- neon, crypto, gaming, or agency-template aesthetics;
- random particles;
- invented employers, banks, institutions, projects, results, or scale;
- animation whose purpose is to advertise animation capability.

System notation must map to real hierarchy, case indices, taxonomy, or navigation. If a decoration has no identity or hierarchy function, remove it.

## 14. Deliverables and Roadmap coverage

Phase 2 execution must ultimately produce:

1. Sitemap and definitive navigation behavior.
2. Wireframes for hero, experience, work cases, specializations, projects, technologies, approach, contact, mobile menu, loading, and error states.
3. Complete dual-theme design tokens covering palette, type, spacing, radii, borders, shadows, motion, photography, iconography, project dossiers, and interaction states.
4. Static high-fidelity prototype.
5. Desktop, tablet, and mobile visual reviews.
6. Light/dark equivalence review.
7. Contrast validation.
8. No-animation legibility validation.
9. Responsive specification.

Roadmap checkboxes remain unchecked until their concrete artifact and validation evidence exist. Specification approval alone does not complete Phase 2.

## 15. Documentation synchronization

This specification makes the detailed Phase 2 decisions. It does not change product positioning, frontend/backend boundaries, server topology, deployment, domain, ports, or application stack. Therefore:

- `docs/PROJECT.md` requires no scope update.
- `docs/ARCHITECTURE.md` requires no architectural update.
- `docs/SERVER_ARCHITECTURE.md` and `docs/DEPLOYMENT.md` require no update.
- `README.md` requires no setup update.
- `ROADMAP.md` remains unchanged until deliverables are implemented and verified.

## 16. Execution gate and later model routing

After this specification is approved, the current GPT-5.6 Sol parent must produce the complete Phase 2 implementation plan using Superpowers `writing-plans`. It must then stop before execution.

When the user later resumes execution:

- Parent/controller: GPT-5.6 Terra.
- Mechanical, completely specified work: GPT-5.6 Luna where appropriate and available.
- Normal implementation and integration: GPT-5.6 Terra.
- Task reviewers: GPT-5.6 Terra by default, adjusted to risk.
- Architecture/high-risk judgment: GPT-5.6 Sol only when genuinely necessary.
- Final whole-branch review: GPT-5.6 Sol.
- Every subagent model must be specified explicitly; no silent inheritance.

The planning session must not invoke `subagent-driven-development`, `executing-plans`, create an implementation worktree, spawn implementers, install dependencies, create the prototype, or begin Task 1.

## 17. Approval record

The user approved the design progressively on 2026-08-12:

- editorial introduction bridge;
- visible scalable zero-project state;
- Operational Editorial identity;
- cases-first evidence sequence;
- grouped five-destination navigation;
- header utility placement;
- full-screen editorial mobile menu;
- persistently sticky header;
- definitive section order;
- reciprocal editorial light/dark palette;
- Instrument Sans and IBM Plex Mono typography;
- Indexed Detail desktop Work at `64rem` and above plus Numbered Dossiers below `64rem`;
- Project Dossiers;
- Responsive Split hero;
- repository-local semantic HTML/CSS prototype;
- the consolidated information architecture, wireframes, visual system, prototype strategy, states, and validation requirements.

Written-spec review remains the required gate before implementation planning.
