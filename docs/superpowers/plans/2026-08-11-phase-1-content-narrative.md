# Phase 1 Content and Narrative Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Produce an approved, bilingual, confidentiality-safe content package and asset inventory that can drive Phase 2 design and later CMS implementation without inventing professional claims.

**Architecture:** Treat verified source material as input, publication classification as a mandatory gate, Spanish copy as the initial editorial source, and English as a reviewed equivalent rather than an independent rewrite. Keep content artifacts in `docs/content/`; no application or CMS code is created in this phase.

**Tech Stack:** Markdown, Git, human review, repository documentation conventions.

## Global Constraints

- Positioning remains **Backend Developer | PHP & Laravel**, targeting Backend PHP/Laravel Jr. roles.
- The message “this developer solves real backend problems” must be understandable in under ten seconds.
- Banking/payment integrations and education-management work have first priority.
- Never invent metrics, identities, customers, institutions, achievements, or production scale.
- Quantitative claims require verifiable evidence before publication.
- Uncertainty about publication safety defaults to non-publication.
- Spanish and English must carry equivalent meaning; English requires human review.
- Public CVs are PDF only, separately managed in Spanish and English.
- No application code, Next.js/Laravel initialization, Docker files, or dependency installation belongs to this plan.
- Use a short-lived `docs/phase-1-content` branch in an isolated worktree when execution begins; do not merge it into `main` without explicit approval.
- If subagents are used, route each child explicitly to the least powerful suitable model and follow the repository’s subagent model-safety policy.

---

## File map

- Create `docs/content/SOURCE_INVENTORY.md`: provenance and verification state for every professional claim, link, CV, and asset.
- Create `docs/content/CONFIDENTIALITY_MATRIX.md`: item-by-item publication classification and review evidence.
- Create `docs/content/CONTENT.es.md`: approved Spanish portfolio narrative organized by public section.
- Create `docs/content/GLOSSARY.md`: canonical Spanish/English technical terminology and protected proper nouns.
- Create `docs/content/CONTENT.en.md`: reviewed English equivalent of approved Spanish content.
- Create `docs/content/ASSET_INVENTORY.md`: photo, CV, contact-link, and future project-asset readiness.
- Modify `docs/PROJECT.md` only if approved content changes product positioning or scope.
- Modify `ROADMAP.md` only after the corresponding Phase 1 acceptance conditions are actually met.

### Task 1: Establish verified source inventory

**Files:**
- Create: `docs/content/SOURCE_INVENTORY.md`

**Interfaces:**
- Consumes: Luciano-provided CVs, LinkedIn profile, GitHub profile, employment notes, and approved personal records.
- Produces: stable source IDs (`SRC-001`, `SRC-002`, …) referenced by all later content and confidentiality records.

- [ ] **Step 1: Create the source inventory structure**

  Add sections for `Professional profile`, `Employment and responsibilities`, `Backend work cases`, `Contact links`, `CV files`, and `Visual assets`. Use this exact table schema in each section:

  ```markdown
  | Source ID | Description | Owner/location | Verification | Publication authority | Notes |
  |---|---|---|---|---|---|
  ```

  Allowed verification values are `verified by Luciano`, `externally verifiable`, and `not verified`. Allowed publication-authority values are `public`, `requires review`, and `not public`.

- [ ] **Step 2: Inventory only material actually supplied or linked by Luciano**

  Assign each item a source ID. Do not copy secrets or confidential source material into the repository; record a safe description and owner/location instead.

- [ ] **Step 3: Check every planned professional theme has evidence**

  Run:

  ```powershell
  rg -n "API|Laravel|pago|banc|educa|MySQL|Artisan|programad|legacy|mantenimiento" docs/content/SOURCE_INVENTORY.md
  ```

  Expected: each theme intended for public copy has at least one source entry. Missing evidence remains excluded from drafted claims.

- [ ] **Step 4: Obtain Luciano’s source-inventory approval**

  Present all entries marked `not verified` or `requires review`. Update their state only from Luciano’s explicit answer.

- [ ] **Step 5: Commit the approved inventory**

  ```powershell
  git add docs/content/SOURCE_INVENTORY.md
  git commit -m "docs: inventory verified portfolio sources"
  ```

### Task 2: Build the content-specific confidentiality matrix

**Files:**
- Create: `docs/content/CONFIDENTIALITY_MATRIX.md`

**Interfaces:**
- Consumes: source IDs from `SOURCE_INVENTORY.md` and the Phase 0 confidentiality policy.
- Produces: one publication decision for every source that could influence public content.

- [ ] **Step 1: Create the matrix using the approved policy**

  Use this exact schema:

  ```markdown
  | Item ID | Source IDs | Candidate topic | Classification | Required transformation | Reviewer | Decision evidence |
  |---|---|---|---|---|---|---|
  ```

  Assign item IDs as `PUB-001`, `PUB-002`, and so on. Classification must be one of `prohibited`, `anonymize and review`, or `publishable after accuracy review`.

- [ ] **Step 2: Classify every candidate professional item**

  Include employer/client identity, institutions, providers, workflows, incidents, architecture, screenshots, logs, database/API examples, and every quantitative statement that may appear publicly.

- [ ] **Step 3: Record concrete transformations**

  Replace vague instructions with observable transformations such as “remove provider name and account identifiers,” “replace institution name with education-management platform,” or “exclude unverifiable number.” Prohibited items must say `exclude from all public artifacts`.

- [ ] **Step 4: Perform the hidden-data review for artifacts**

  For each screenshot or technical example, record review results for names, emails, IDs, URLs, tokens, database names, institution names, transaction data, browser tabs, terminal paths, logs, and metadata.

- [ ] **Step 5: Obtain Luciano’s explicit matrix approval**

  Do not begin public copy until every used item has an approved classification and transformation.

- [ ] **Step 6: Commit the matrix**

  ```powershell
  git add docs/content/CONFIDENTIALITY_MATRIX.md
  git commit -m "docs: approve portfolio confidentiality matrix"
  ```

### Task 3: Draft and approve the Spanish narrative

**Files:**
- Create: `docs/content/CONTENT.es.md`
- Modify: `docs/PROJECT.md` only if explicit product-scope approval requires it.

**Interfaces:**
- Consumes: approved source IDs and confidentiality transformations.
- Produces: canonical Spanish copy blocks with traceable source annotations kept outside public prose.

- [ ] **Step 1: Create the Spanish content structure**

  Add sections in this order: `Hero`, `Presentación profesional`, `Experiencia`, `Casos de trabajo`, `Áreas de especialización`, `Proyectos`, `Tecnologías`, `Forma de trabajo`, and `Contacto y CV`.

- [ ] **Step 2: Draft positioning and calls to action**

  Provide one primary headline, one short value proposition, one extended introduction, one primary CTA, and one availability statement. Keep `Backend Developer | PHP & Laravel` unchanged unless Luciano explicitly approves a positioning change.

- [ ] **Step 3: Draft anonymized experience and cases**

  Write evidence-backed descriptions for payment/banking integrations, education management, REST APIs/business rules, MySQL, automation/scheduled work, and production/legacy maintenance. Add an HTML comment after each block containing its source and matrix IDs, for example:

  ```markdown
  <!-- sources: SRC-003, SRC-008; publication: PUB-002 -->
  ```

- [ ] **Step 4: Define the zero-project state**

  Write honest public copy that does not fabricate project cards and keeps professional experience useful when zero projects are published.

- [ ] **Step 5: Run claim and confidentiality scans**

  ```powershell
  rg -n -i "\b[0-9]+(%|x|k|m|millones?|usuarios?|transacciones?)\b|banco|cliente|institución|https?://|token|password|secret" docs/content/CONTENT.es.md
  ```

  Expected: every match is either safe public content backed by the matrix or removed before approval.

- [ ] **Step 6: Obtain section-by-section approval from Luciano**

  Record approval in a final `Review record` section containing the review date and the exact approved section names.

- [ ] **Step 7: Commit approved Spanish copy**

  ```powershell
  git add docs/content/CONTENT.es.md docs/PROJECT.md
  git commit -m "docs: approve Spanish portfolio narrative"
  ```

### Task 4: Define terminology and produce English content

**Files:**
- Create: `docs/content/GLOSSARY.md`
- Create: `docs/content/CONTENT.en.md`

**Interfaces:**
- Consumes: approved `CONTENT.es.md` blocks and their source/matrix annotations.
- Produces: canonical terminology and an English document with one-to-one section equivalence.

- [ ] **Step 1: Create the bilingual glossary**

  Use columns `Spanish`, `English`, `Usage note`, and `Do not use`. Include at least backend, REST API, business logic, payment integration, education management system, scheduled task, Artisan command, query optimization, data consistency, legacy system, draft, and published.

- [ ] **Step 2: Translate approved content without adding claims**

  Preserve section order, meaning, emphasis, source comments, and confidentiality transformations. Do not translate technology or brand names unless the glossary explicitly requires it.

- [ ] **Step 3: Compare structural equivalence**

  Run:

  ```powershell
  rg "^## " docs/content/CONTENT.es.md
  rg "^## " docs/content/CONTENT.en.md
  ```

  Expected: the same number and order of public content sections in both files.

- [ ] **Step 4: Perform human English review**

  Review technical accuracy, natural phrasing, junior-level positioning, and equivalence. Record reviewer and date in the English document’s `Review record`.

- [ ] **Step 5: Repeat the claim/confidentiality scan in English**

  ```powershell
  rg -n -i "\b[0-9]+(%|x|k|m|million|users?|transactions?)\b|bank|client|institution|https?://|token|password|secret" docs/content/CONTENT.en.md
  ```

  Expected: every match is approved and traceable or removed.

- [ ] **Step 6: Commit glossary and English copy**

  ```powershell
  git add docs/content/GLOSSARY.md docs/content/CONTENT.en.md
  git commit -m "docs: approve English portfolio narrative"
  ```

### Task 5: Approve contact, CV, and visual asset readiness

**Files:**
- Create: `docs/content/ASSET_INVENTORY.md`

**Interfaces:**
- Consumes: verified contact URLs, two approved CV PDFs, and candidate professional photos.
- Produces: an implementation-ready asset manifest without committing private or unfinished assets.

- [ ] **Step 1: Create the asset manifest**

  Use categories `Contact`, `CV`, `Professional photo`, and `Future project media`, with columns `Asset ID`, `Public purpose`, `Language`, `Source owner/location`, `Target public path`, `Status`, `Accessibility text`, and `Confidentiality review`.

- [ ] **Step 2: Verify contact destinations**

  Record the exact approved LinkedIn, GitHub, and public email destinations. Do not invent or normalize usernames without Luciano’s confirmation.

- [ ] **Step 3: Verify CV contract readiness**

  Confirm separate approved Spanish and English PDFs and target paths `/cv/luciano-gonzalez-es.pdf` and `/cv/luciano-gonzalez-en.pdf`. A missing file is recorded as `unavailable—hide download`; it is never substituted with the other language.

- [ ] **Step 4: Review the professional photo candidates**

  Record selected source, horizontal/vertical crop feasibility, optimization needs, alt-text decision, light/dark suitability, and confidentiality review. Actual image processing remains a later approved task.

- [ ] **Step 5: Obtain Luciano’s asset-manifest approval**

  Confirm every public contact and selected asset; keep absent future-project media explicitly absent rather than fabricating entries.

- [ ] **Step 6: Commit the manifest**

  ```powershell
  git add docs/content/ASSET_INVENTORY.md
  git commit -m "docs: approve portfolio asset inventory"
  ```

### Task 6: Validate and close Phase 1

**Files:**
- Modify: `ROADMAP.md`

**Interfaces:**
- Consumes: all five approved Phase 1 content artifacts.
- Produces: a verified Phase 1 status and a clean handoff to Phase 2 design.

- [ ] **Step 1: Check required artifacts and forbidden placeholders**

  ```powershell
  $files = @('docs/content/SOURCE_INVENTORY.md','docs/content/CONFIDENTIALITY_MATRIX.md','docs/content/CONTENT.es.md','docs/content/GLOSSARY.md','docs/content/CONTENT.en.md','docs/content/ASSET_INVENTORY.md'); $files | ForEach-Object { if (-not (Test-Path -LiteralPath $_)) { throw "Missing $_" } }; $forbidden = @(('T' + 'BD'), ('T' + 'ODO'), ('FIX' + 'ME'), ('X' * 3)); if (Select-String -Path $files -Pattern $forbidden -SimpleMatch) { throw 'Unresolved placeholder found' }
  ```

  Expected: exit code 0 with no missing files or placeholders.

- [ ] **Step 2: Verify source traceability and reviews**

  ```powershell
  rg -n "sources: SRC-[0-9]+.*publication: PUB-[0-9]+" docs/content/CONTENT.es.md docs/content/CONTENT.en.md
  rg -n "Review record|verified by Luciano|Confidentiality review" docs/content
  ```

  Expected: every substantive experience/case block is traceable and both language and asset reviews are recorded.

- [ ] **Step 3: Perform manual acceptance review**

  Confirm that the backend message is understandable in under ten seconds; payment/banking and education-management experience have priority; both languages are equivalent; zero projects works honestly; all contact/CV decisions are explicit; and no unapproved confidential content remains.

- [ ] **Step 4: Update only completed Phase 1 roadmap checkboxes**

  Leave any unmet item unchecked and record the precise missing input in the handoff. Do not mark the phase complete based solely on document existence.

- [ ] **Step 5: Run documentation verification**

  ```powershell
  git diff --check
  git status --short
  ```

  Expected: no whitespace errors; status lists only intended Phase 1 documentation changes.

- [ ] **Step 6: Commit Phase 1 closure**

  ```powershell
  git add ROADMAP.md docs/content
  git commit -m "docs: complete phase 1 content foundation"
  ```

- [ ] **Step 7: Stop for explicit merge and Phase 2 approval**

  Present the branch commits, remaining risks, and uncompleted checkboxes. Do not merge into `main` and do not begin Phase 2 until Luciano explicitly approves both actions.
