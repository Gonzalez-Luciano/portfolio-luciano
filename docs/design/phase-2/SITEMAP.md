# Phase 2 sitemap and navigation contract

## Localized single-page map

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

`/es` and `/en` are the canonical localized pages. `/` redirects according to the established locale contract; navigation never uses locale-neutral content routes.

## Anchors and grouped navigation

| Order | ID | ES label | EN label | Included content | Active-nav boundary |
|---:|---|---|---|---|---|
| 0 | `top` | Inicio | Home | Hero | Start identity; not one of the five destination indices |
| 1 | `work` | Trabajo | Work | Anonymous work cases, then professional-experience context | First case through the end of experience |
| 2 | `expertise` | Especialización | Expertise | Areas of specialization, then technologies | First specialization through technology groups |
| 3 | `projects` | Proyectos | Projects | Published project dossiers or the approved zero-project state | Projects heading through its state or ordered dossiers |
| 4 | `approach` | Forma de trabajo | Approach | Approved working model | Approach heading through its reading beats |
| 5 | `contact` | Contacto | Contact | LinkedIn, GitHub, email, and the locale CV only when available | Contact heading through page end |

The desktop and mobile destination order is exactly Work, Expertise, Projects, Approach, Contact. The editorial professional-introduction bridge is intentionally not a navigation destination. Work stays cases-first; Expertise stays specializations-first.

## Sticky header

The persistently sticky compact desktop header contains a concise Luciano/backend identifier linking to `#top`, the five localized destination links, a language control, and a theme control. Its solid raised surface and routine sticky-header shadow are the only standard header elevation. The active destination shows its two-digit section index, a 2px accent rule, and semibold text; color alone never communicates current location.

Desktop utility controls remain in the header. At widths below `64rem`, the closed header retains identity, the theme control, and an explicitly named Menu control; language selection moves into the mobile menu.

## Fragments, focus, and motion

Every destination is a real locale-specific link such as `/es#work` or `/en#work`, using the stable ASCII IDs in the table. Each target receives sticky-header `scroll-margin`. Direct fragment URLs land correctly without JavaScript.

Keyboard activation moves focus to the destination section landmark or its sensible heading without introducing duplicate tab stops. Browser history and URL fragments remain understandable and are never replaced by transient JavaScript state. Smooth scrolling uses the standard motion token only when reduced motion is not requested; otherwise scrolling is immediate.

## Mobile dialog navigation

Below `64rem`, Menu opens a full-screen editorial index with numbered destinations `01`–`05`, large separated touch targets, an explicit Close control, and language selection near the lower edge. It contains focus, closes with Escape, locks background scroll, restores focus to the Menu trigger on close, and closes immediately with correct focus and scroll placement after a destination is chosen. At `64rem` and above the desktop header replaces this dialog.

## Projects and durable destinations

`#projects` is always present. With zero published projects it shows the approved honest localized zero-project copy in the same durable section architecture; it never shows empty cards, invented names, placeholders, progress indicators, or “coming soon” promises. Future published records use ordered Project Dossiers without changing the fragment or navigation structure.
