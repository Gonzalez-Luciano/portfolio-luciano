# Asset inventory

Status: **Approved by Luciano on 2026-08-11.**

This is an implementation-readiness record, not a publication action. A `public path` below is a future contract or an external contact destination; no listed file is publicly served by this repository at present. Future project media is intentionally absent.

| Asset ID | Public purpose | Language | Source owner/location | Target public path | Status | Accessibility text | Confidentiality review |
|---|---|---|---|---|---|---|---|
| AST-CONTACT-LINKEDIN | Professional contact link | ES / EN | `SRC-003`; URL supplied by Luciano | https://www.linkedin.com/in/luciano-gonzález-590350294 | Approved destination; implementation/publication pending | `LinkedIn de Luciano González` / `Luciano González's LinkedIn` | `PUB-027`: publish only the supplied canonical URL; do not derive profile claims. |
| AST-CONTACT-GITHUB | Professional contact link | ES / EN | `SRC-004`; URL supplied by Luciano | https://github.com/Gonzalez-Luciano | Approved destination; implementation/publication pending | `GitHub de Luciano González` / `Luciano González's GitHub` | `PUB-028`: publish only the supplied canonical URL; do not treat repositories as approved projects. |
| AST-CONTACT-EMAIL | Public email contact | ES / EN | `SRC-009`; email supplied by Luciano | `lucianogonzalez12004@gmail.com` | Approved destination; implementation/publication pending | `Enviar un correo a Luciano González` / `Email Luciano González` | `PUB-029`: only approved public email; do not add inferred contact metadata. |
| AST-CV-ES | Spanish CV PDF download | ES | `SRC-001`; `docs/content/approved-assets/cv-es.pdf` | `/cv/luciano-gonzalez-es.pdf` | Replacement approved on 2026-09-09; tracked asset and future-publication contract; target path not served yet | `Descargar CV de Luciano González en español (PDF)` | `SRC-001` is approved for future publication. The tracked PDF declares `/Lang(es-AR)`. Its wording is not automatic authority for new site claims; reconcile content explicitly during the Phase 5 final-content review. Serve only through the CMS/media workflow. |
| AST-CV-EN | English CV PDF download | EN | `SRC-002`; `docs/content/approved-assets/cv-en.pdf` | `/cv/luciano-gonzalez-en.pdf` | Replacement approved on 2026-09-09; tracked asset and future-publication contract; target path not served yet | `Download Luciano González's CV in English (PDF)` | `SRC-002` is approved for future publication. The tracked PDF declares `/Lang(en-US)`. Its wording is not automatic authority for new site claims; reconcile content explicitly during the Phase 5 final-content review. Serve only through the CMS/media workflow. |
| AST-PHOTO-PROFILE | Hero/profile photograph | ES / EN | `SRC-010`; `docs/content/approved-assets/professional-photo.png` | No public path assigned; future CMS/media workflow | Replaced on 2026-09-14 by a 1024 × 1024 square portrait supplied by Luciano; approved tracked asset and future publication, not publicly served; processing pending | `Retrato profesional de Luciano González con camisa blanca frente a una pared de tono cálido` / `Professional portrait of Luciano González in a white shirt against a warm-toned wall` | `SRC-010` is approved for future publication. No hidden-data/metadata review or image processing was performed in this task; re-review the final derivative before publication. |
| AST-PROJECT-MEDIA | Future project screenshots, videos, diagrams, and other media | ES / EN | No asset supplied | No public path assigned | **Explicitly absent — do not create a placeholder or publish media** | Not applicable until a reviewed asset exists | `PUB-030` to `PUB-033`: artifact-specific confidentiality and hidden-data review is required before any future media can be public. |

## Professional photo implementation review

| Review item | Decision |
|---|---|
| Selected source | `docs/content/approved-assets/professional-photo.png` (`SRC-010`, replaced 2026-09-14) |
| Composition and crop feasibility | Square (1024 × 1024). A centered head-and-shoulders composition allows square and vertical crops; a horizontal crop needs responsive art direction to retain the face and shoulders. The Phase 6 visual redesign decides the final crop. |
| Optimization needs | Future work: create responsive, optimized derivatives and verify format, dimensions, quality, and metadata handling within the CMS/media workflow. |
| Alt-text decision | Treat the image as meaningful profile imagery. Use the bilingual descriptive alt text recorded above; it identifies Luciano, the white shirt, and the warm-toned wall without adding unverified personal details. |
| Light/dark suitability | The warm beige wall and white shirt are both light, so subject separation relies on the face and hair. Confirm final contrast, crop, and surrounding UI treatment in both themes after integration. |
| Publication state | Approved for tracked storage and future publication, but no file is publicly served and no public path is assigned yet. |

## CV delivery rule

The Spanish and English downloads remain independent. When either approved file is unavailable at integration time, hide only that language's download; never substitute the other-language CV.

## Approval record

Luciano explicitly approved this complete asset manifest on 2026-08-11 and approved the replacement Spanish and English CV files on 2026-09-09. This approval does not change the recorded asset states: files remain unserved until the CMS/media workflow connects them, no image processing was performed in this task, and future project media remains absent.
