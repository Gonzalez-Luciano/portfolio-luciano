# Phase 2 low-fidelity wireframes

These diagrams specify information hierarchy and responsive recomposition, not visual styling or new professional claims. Content comes only from the approved Phase 1 bilingual content records and asset inventory.

## Hero

### Desktop

```text
+-----------------------------------------------------------+
| sticky header: identity | five destinations | language theme|
+---------------------------+-------------------------------+
| Backend Developer | PHP & Laravel | approved portrait       |
| approved hero statement          | orange-background photo  |
| [approved primary CTA → Work]    | (no text over face)      |
+---------------------------+-------------------------------+
```

### Tablet

```text
+-------------------------------------------+
| compact header / desktop header at 64rem+  |
+----------------------+--------------------+
| title + statement    | portrait, reframed |
| CTA                  |                    |
+----------------------+--------------------+
```

### Mobile

```text
+---------------------------+
| identity | theme | Menu    |
+---------------------------+
| portrait first              |
| (separate art-directed crop)|
+---------------------------+
| Backend Developer | PHP &   |
| Laravel                    |
| approved statement + CTA   |
+---------------------------+
```

### Keyboard/focus

The identity and CTA are real links; Menu, language, and theme controls have visible focus. Decorative future system motifs are hidden from assistive technology and never needed to read the hero.

### Light/dark

Both themes keep the portrait recognizable, preserve the orange signature, and keep text on its own readable surface.

### Content source

Approved `Hero` in `CONTENT.es.md` / `CONTENT.en.md`; approved bilingual portrait alt text in `ASSET_INVENTORY.md`.

### Acceptance

One clear H1; no text overlaps the face; mobile is portrait-first rather than a squeezed desktop split.

## Professional introduction

### Desktop

```text
+-----------------------------------------------------------+
| #top editorial bridge                                     |
| concise approved professional-introduction paragraph      |
+-----------------------------------------------------------+
```

### Tablet

```text
+-------------------------------------------+
| editorial bridge; restrained reading width |
+-------------------------------------------+
```

### Mobile

```text
+---------------------------+
| approved introduction      |
| in single reading flow     |
+---------------------------+
```

### Keyboard/focus

It is reading content, not a navigation item or interactive card.

### Light/dark

Typography and muted supporting text retain equivalent readable contrast.

### Content source

Approved `Presentación profesional` / `Professional introduction`.

### Acceptance

It bridges the hero and evidence without becoming a separate destination.

## Work cases

### Desktop

```text
+---------------- #work: Work --------------------------------+
| 01 Integrations...  | selected case: approved heading/copy   |
| 02 Education...     | traceable technology taxonomy           |
| 03 Data...          |                                        |
| 04 Integration...   |                                        |
+--------------------+-----------------------------------------+
| professional-experience context follows below                |
+---------------------------------------------------------------+
```

### Tablet

At `64rem` and above, retain the Indexed Detail 4/8 index-detail composition; below it, switch to dossiers.

### Mobile

```text
+---------------------------+
| 01 approved case heading   |
| approved case description  |
| traceable technologies     |
+---------------------------+
| 02 approved case heading   |
| approved case description  |
+---------------------------+
| 03 ...                     |
+---------------------------+
| 04 ...                     |
+---------------------------+
```

### Keyboard/focus

Desktop is a vertical tab list: Up/Down moves focus, Home/End moves to ends, Enter/Space activates. All panels exist in logical source order and are visible without JavaScript. Narrow dossiers need no activation.

### Light/dark

The selected desktop detail surface, index/rule, and focus geometry remain clear in both themes; active state is not color-only.

### Content source

Approved `Casos de trabajo` / `Work cases`: Integrations and synchronization, Education management, Data and automation, Integration across layers.

### Acceptance

Cases appear before experience. Desktop Indexed Detail starts at `64rem`; below it all four sequential dossiers remain visible with no carousel or horizontal scroller.

## Professional experience

### Desktop

```text
+-----------------------------------------------------------+
| Experience                                                  |
| approved paragraph one                                     |
| approved paragraph two; anonymized context                 |
+-----------------------------------------------------------+
```

### Tablet

The two approved paragraphs follow the Work-case presentation in the same reading sequence.

### Mobile

```text
+---------------------------+
| Experience                 |
| approved paragraph 1       |
| approved paragraph 2       |
+---------------------------+
```

### Keyboard/focus

Semantic reading content follows the case interface; no hidden employer, date, or metric affordance exists.

### Light/dark

Rules and text distinguish it from cases without decorative cards.

### Content source

Approved `Experiencia` / `Experience` two-paragraph record.

### Acceptance

It supports evidence after cases and preserves anonymization.

## Specializations

### Desktop

```text
+---------------- #expertise --------------------------------+
| Areas of specialization                                    |
| statement 1                  | statement 2                 |
| statement 3                  | statement 4                 |
| statement 5                  | statement 6                 |
+-------------------------------------------------------------+
```

### Tablet

Structured statements may use two columns when their text fits.

### Mobile

```text
+---------------------------+
| Areas of specialization    |
| approved statements         |
| one reading column          |
+---------------------------+
```

### Keyboard/focus

This is static semantic list content, not a hover-only badge grid.

### Light/dark

Meaningful rules and body text meet equivalent contrast needs.

### Content source

Approved `Áreas de especialización` / `Areas of specialization` list.

### Acceptance

No proficiency percentages, progress bars, invented tools, or logo wall.

## Projects

### Desktop

```text
+---------------- #projects ---------------------------------+
| Projects / system label                                     |
| zero: approved honest localized message                     |
|                                                              |
| future populated record: [optional media] | dossier copy    |
+-------------------------------------------------------------+
```

### Tablet

Future dossiers retain media/body alignment where space permits; the public zero state remains visible and purposeful.

### Mobile

```text
+---------------------------+
| Projects                    |
| approved zero-project copy  |
| (no empty image/card)       |
+---------------------------+
| future: media above dossier |
+---------------------------+
```

### Keyboard/focus

Only existing demo/repository destinations render as links. The section remains a fragment destination with zero projects.

### Light/dark

The section rule and zero-state reading area are equivalent in both themes.

### Content source

Approved `Proyectos` / `Projects` zero-project copy.

### Acceptance

Desktop future dossiers are horizontal; narrow layouts stack. No placeholders, invented projects, or “coming soon” language.

## Technologies

### Desktop

```text
+-----------------------------------------------------------+
| Technologies                                               |
| Backend: PHP · Laravel                                    |
| Data: MySQL | Integration: REST APIs | Across layers: Angular|
+-----------------------------------------------------------+
```

### Tablet

Technology groups wrap by content rather than forming a decorative logo cloud.

### Mobile

```text
+---------------------------+
| Technologies               |
| grouped textual taxonomy   |
+---------------------------+
```

### Keyboard/focus

Technology names are text, unless a future approved destination makes one interactive.

### Light/dark

Utility typography supplements readable text; it never relies on low contrast.

### Content source

Approved `Tecnologías` / `Technologies`: PHP, Laravel, MySQL, REST APIs, Angular.

### Acceptance

Technology grouping communicates function without proficiency claims.

## Approach

### Desktop

```text
+---------------- #approach ---------------------------------+
| approved working-approach paragraph                         |
| 01 understand | 02 consistency | 03 maintainability | 04 communication |
+-------------------------------------------------------------+
```

### Tablet

Ordered reading beats wrap without changing their sequence.

### Mobile

```text
+---------------------------+
| Approach                    |
| approved paragraph           |
| 01 → 02 → 03 → 04           |
+---------------------------+
```

### Keyboard/focus

The four beats are explanatory structure, not a fake interactive process diagram.

### Light/dark

Indices and rules remain supporting hierarchy, not the only meaning carrier.

### Content source

Approved `Forma de trabajo` / `Working approach` paragraph.

### Acceptance

The original approved paragraph remains visible; beats add only its supported taxonomy.

## Contact

### Desktop

```text
+---------------- #contact ----------------------------------+
| Contact and CV                                               |
| [LinkedIn] [GitHub] [Email] [locale CV only when available] |
+-------------------------------------------------------------+
```

### Tablet

Actions retain their visible labels and at least 44px touch targets.

### Mobile

```text
+---------------------------+
| Contact                    |
| [LinkedIn              ]   |
| [GitHub                ]   |
| [Email                 ]   |
| [English CV, if available] |
+---------------------------+
```

### Keyboard/focus

Every action has an accessible visible name and focus state. No form, WhatsApp action, or calendar appears.

### Light/dark

Links, borders, and focus rings remain distinguishable at AA contrast.

### Content source

Approved `Contacto y CV` / `Contact and CV` and `ASSET_INVENTORY.md` destinations.

### Acceptance

A missing locale CV is omitted, never disabled or substituted with the other language file.

## Mobile menu

### Desktop

At `64rem` and above, this wireframe is replaced by the sticky desktop header.

### Tablet

```text
+---------------- full viewport ----------------+
| Close                                          |
| 01 Work                                        |
| 02 Expertise                                   |
| 03 Projects                                    |
| 04 Approach                                    |
| 05 Contact                                     |
|                              language control  |
+------------------------------------------------+
```

### Mobile

```text
+---------------------------+
| identity       [Close]     |
| 01 Work                    |
| 02 Expertise               |
| 03 Projects                |
| 04 Approach                |
| 05 Contact                 |
|                             |
| language near lower edge   |
+---------------------------+
```

### Keyboard/focus

Closed, independently opening open, focused, active-language, and close states are documented: focus is contained while open; Escape closes; a chosen destination closes immediately; closing restores focus to Menu; background scrolling is locked.

### Light/dark

The full-screen menu replaces the viewport surface and needs no floating-card shadow; both themes retain strong separation and focus.

### Content source

Localized navigation labels from the approved sitemap; no professional copy is added.

### Acceptance

Large numbered targets, named Menu/Close controls, language placement, and all focus/close states are present below `64rem`.

## Loading state

### Desktop

```text
+-----------------------------------------------------------+
| real shell / stable hero-shaped skeleton                   |
| Work skeleton rows | Projects skeleton | Contact skeleton  |
+-----------------------------------------------------------+
```

### Tablet

Skeleton geometry follows the active responsive composition and preserves section widths.

### Mobile

```text
+---------------------------+
| shell                       |
| portrait/text skeleton       |
| sequential content skeletons |
+---------------------------+
```

### Keyboard/focus

Visible localized loading status is announced; skeleton blocks are not interactive.

### Light/dark

Neutral placeholders preserve meaningful layout without relying on animation.

### Content source

Localized status only: `Cargando contenido del portfolio…` / `Loading portfolio content…`.

### Acceptance

Static stable geometry; no shimmer is required and reduced motion remains static.

## Recoverable section error

### Desktop

```text
+---------------- affected section -------------------------+
| error label + plain localized message                       |
| [Retry when meaningful]                                     |
+-------------------------------------------------------------+
```

### Tablet

The failed region alone is replaced; the surrounding page remains usable.

### Mobile

```text
+---------------------------+
| affected section error     |
| plain language              |
| [Retry                  ]  |
+---------------------------+
```

### Keyboard/focus

The alert and retry control are keyboard reachable; Retry is shown only where a retry is meaningful.

### Light/dark

Error text and a meaningful boundary use semantic error/border tokens plus written labeling.

### Content source

Localized plain language only; no unpublished replacement content or internal details.

### Acceptance

The state exposes no stack traces, URLs, paths, identifiers, or fake API behavior.

## Broad site error

### Desktop

```text
+-----------------------------------------------------------+
| identity | language | theme                                |
| We couldn't load the portfolio content                     |
| safe LinkedIn | GitHub | email | prototype index           |
+-----------------------------------------------------------+
```

### Tablet

The shell, localized error, navigation affordances, and safe contact actions persist.

### Mobile

```text
+---------------------------+
| identity | theme | Menu    |
| localized broad error       |
| safe contact actions        |
+---------------------------+
```

### Keyboard/focus

Header controls, safe contact links, and the return link remain keyboard usable; error text is announced appropriately.

### Light/dark

The shell remains equivalent and error meaning is written as well as colored.

### Content source

Exact localized state messages: `No pudimos cargar el contenido del portfolio` / `We couldn't load the portfolio content`.

### Acceptance

The page shell survives broad content failure without internal error details or imitation of unpublished content.
