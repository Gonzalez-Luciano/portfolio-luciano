# Phase 2 design tokens

These contracts define the semantic custom properties to copy into the Phase 2 prototype token stylesheet. They preserve the approved Operational Editorial identity: warm, structured, and backend-focused. They do not introduce application code, external assets, or Phase 6 motion.

## Semantic color tokens

| Token | Light | Dark | Purpose |
|---|---|---|---|
| `--color-canvas` | `#EEE9DE` | `#171918` | Primary page background |
| `--color-surface-raised` | `#F6F2E9` | `#202321` | Sticky header, selected detail, functional separation |
| `--color-text-primary` | `#1B1E1C` | `#F1ECE1` | Headings and body |
| `--color-text-muted` | `#565B56` | `#B8B3A9` | Secondary descriptions and metadata |
| `--color-accent` | `#A94322` | `#F07A4B` | Links, focus-supporting emphasis, indices, active rules |
| `--color-border-meaningful` | `#817B70` | `#777A74` | Controls and boundaries needing at least 3:1 contrast |
| `--color-text-disabled` | `#5F635E` | `#96958F` | Explicitly unavailable labeled controls; never the only state cue |
| `--color-surface-selected` | `#E4D8C9` | `#2B2926` | Active case or project detail surface |
| `--color-error` | `#8B2E24` | `#FF9B8A` | Error text and icons with a written label |

Decorative rules may be subtler only when they convey no required information. The accent is not a gradient. Theme quality and required-information contrast remain equivalent.

## Typography tokens

| Token | Value | Role |
|---|---|---|
| `--font-family-sans` | `Instrument Sans, sans-serif` | Headings, body, navigation, controls |
| `--font-family-mono` | `IBM Plex Mono, monospace` | Indices, technical taxonomy, metadata, compact system labels only |
| `--line-height-body` | `1.6` | Body copy |
| `--measure-reading` | `60ch–70ch` | Normal reading measure |

IBM Plex Mono must not be used to simulate a terminal. No essential text may be smaller than `12px`; Spanish and English wrapping requires review at every reference width.

```css
--font-size-display: clamp(3.5rem, 2rem + 6vw, 7rem);
--font-size-section: clamp(2.5rem, 1.75rem + 3vw, 4.5rem);
--font-size-subheading: clamp(1.75rem, 1.4rem + 1.4vw, 2.75rem);
--font-size-lead: clamp(1.25rem, 1.1rem + 0.6vw, 1.625rem);
--font-size-body: clamp(1.0625rem, 1.025rem + 0.15vw, 1.1875rem);
--font-size-utility: clamp(0.75rem, 0.72rem + 0.1vw, 0.875rem);
```

## Spacing and geometry tokens

| Token | Value |
|---|---:|
| `--space-1` | `4px` |
| `--space-2` | `8px` |
| `--space-3` | `12px` |
| `--space-4` | `16px` |
| `--space-5` | `24px` |
| `--space-6` | `32px` |
| `--space-7` | `48px` |
| `--space-8` | `64px` |
| `--space-9` | `96px` |
| `--space-10` | `128px` |
| `--radius-none` | `0` |
| `--radius-small` | `4px` |
| `--radius-medium` | `8px` |
| `--rule-standard` | `1px` |
| `--rule-emphasis` | `2px` |
| `--focus-ring-width` | `3px` |
| `--focus-ring-offset` | `2px` |
| `--target-minimum` | `44 × 44px` |
| `--breakpoint-desktop` | `64rem` |
| `--content-max-width` | `90rem` |

The base unit is `4px`. `--breakpoint-desktop` controls the approved desktop-header, split-hero, and Indexed Detail switch; `--content-max-width` caps the centered primary reading region. Large rounded cards, filler pills, excessive badges, and glass panels are prohibited. Required touch controls meet `44 × 44px` without relying on hover.

## Elevation and icon tokens

| Token | Light | Dark |
|---|---|---|
| `--shadow-sticky-header` | `0 8px 24px rgb(0 0 0 / 8%)` | `0 8px 24px rgb(0 0 0 / 24%)` |
| `--icon-box-compact` | `20px` | `20px` |
| `--icon-box-standard` | `24px` | `24px` |
| `--icon-stroke` | `1.5px` | `1.5px` |

The sticky-header shadow is the only routine elevation. The full-screen mobile menu replaces the viewport and has no floating-panel shadow. Icons accompany visible text for menu, theme, language, download, and external-link actions. Social marks, if used, have accurate accessible labels. Technologies remain textual rather than a logo wall.

## Motion tokens and state mappings

| Token | Value |
|---|---|
| `--duration-fast` | `120ms` |
| `--duration-standard` | `200ms` |
| `--duration-deliberate` | `320ms` |
| `--easing-standard` | `cubic-bezier(0.2, 0.8, 0.2, 1)` |

| State | Contract |
|---|---|
| Hover | Underline, rule, or border emphasis plus restrained color change. |
| Focus | Persistent `3px` `--color-accent` ring with `2px` offset; visible in both themes. |
| Active | Rule/index plus text treatment, never color alone. |
| Pressed | Tonal change only; no layout movement. |
| Disabled/unavailable | Muted and explicitly unavailable, or omitted when omission prevents confusion. |
| Reduced motion | Remove nonessential transitions and make navigation immediate; all content remains legible. |

These values are records for simple state previews only. They do not authorize hero timelines, scroll-driven effects, pointer-reactive nodes, canvas, WebGL, or other Phase 6 work.
