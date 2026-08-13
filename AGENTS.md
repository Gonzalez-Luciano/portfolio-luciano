# AGENTS.md — Portfolio profesional de Luciano González

## Purpose

This file contains the operating instructions for Codex when working in this repository.

Before making changes, always read:

1. `docs/PROJECT.md`
2. `docs/ARCHITECTURE.md`
3. `docs/SERVER_ARCHITECTURE.md` when work touches Docker, networking, ports, domains, deployment, CI/CD or server infrastructure
4. `docs/DEPLOYMENT.md` when work touches portfolio production or releases
5. `ROADMAP.md`
6. The existing code related to the requested task

`ROADMAP.md` is the source of truth for project phases and progress.
`docs/ARCHITECTURE.md` is the source of truth for technical boundaries.
`docs/PROJECT.md` is the source of truth for product goals and positioning.
`docs/SERVER_ARCHITECTURE.md` is the source of truth for shared server topology.

Do not silently contradict those documents.

---

## Project goal

Build a professional bilingual portfolio for Luciano González, positioned primarily as:

**Backend Developer | PHP & Laravel**

The first impression should be:

> This developer knows how to solve real backend problems.

The visual experience may be cinematic and experimental, but it must never make the portfolio look like a frontend-only, design-agency, gaming, crypto, or purely artistic website.

The strongest professional themes are:

1. PHP and Laravel backend development.
2. REST APIs and business logic.
3. Banking/payment API integrations.
4. Development and maintenance of education management systems.
5. MySQL, query optimization, and data consistency.
6. Scheduled jobs, Artisan commands, synchronization, and automation.
7. Maintenance of existing production systems and legacy code.

Never invent metrics, clients, banks, transaction volumes, business results, or confidential information.

---

## Approved architecture

The project is designed as a monorepository with these main areas:

- `web/` — Next.js / React public portfolio.
- `api/` — Laravel API and administration.
- `infra/` — Docker and infrastructure-related files.
- `docs/` — project and architecture documentation.

Responsibilities must remain separated.

### Frontend

Responsible for:

- Presentation.
- Responsive UI.
- Accessibility.
- Internationalization.
- SEO.
- Themes.
- Animations.
- Consuming the public Laravel API.

Do not move business rules into the frontend.

### Backend

Responsible for:

- Persistence.
- Validation.
- Administration authentication.
- Content publication.
- Draft/published state.
- Media management.
- Public API.
- Server-side authorization.
- Data integrity.

Laravel is the source of truth for managed content.

---

## Main stack

Use the stack defined in `docs/ARCHITECTURE.md`.

Current project direction:

### Public web

- React.
- Next.js App Router.
- TypeScript strict mode.
- Tailwind CSS.
- Motion for UI animation.
- GSAP / ScrollTrigger only for justified cinematic sequences.

### API / administration

- PHP.
- Laravel.
- MySQL.
- Filament for the administration panel.
- REST API.

### Infrastructure

- Linux physical server controlled by Luciano.
- Multiple independent Dockerized projects on the same host.
- Docker / Docker Compose.
- One shared host-level `cloudflared` service for the server.
- One public hostname/subdomain per project.
- Each project exposes only one loopback HTTP entrypoint to the host.
- The portfolio initially owns `127.0.0.1:8000`.
- MySQL remains private inside the portfolio Docker networks.
- GitHub Actions is initially used for CI validation; deployment is a separate concern.

Production compute and persistent data remain on Luciano's own server unless Luciano explicitly changes the architecture.

Do not add a framework, library, state manager, animation library, database, or infrastructure service without a concrete need.

---

## Required product behavior

The public site must eventually support:

- Spanish and English.
- Dark and light mode.
- Professional photo visible in the hero.
- Single-page portfolio structure per language.
- Professional experience.
- Anonymous real-world work cases.
- Skills / technologies.
- Project cards.
- LinkedIn link.
- GitHub link.
- Email link.
- CV download.

There is no contact form in the initial version.

New portfolio projects will be created later, so the site must work correctly with zero published projects.

---

## Animation rules

The intended animation level is cinematic, but content and performance have priority.

Use:

### Motion

For:

- Microinteractions.
- Hover/focus states.
- Component enter/exit.
- Layout transitions.
- Theme and language UI transitions.

### GSAP / ScrollTrigger

Only when Motion/CSS is not enough for:

- Scroll-driven narratives.
- Coordinated typography.
- Carefully justified pinned sections.
- Complex timelines.

Do not create scroll-jacking.

### Interactive node field

The hero should eventually include an interactive point/node system reacting to pointer proximity.

Requirements:

- Must not block hero readability.
- Must have a simpler mobile behavior.
- Must respect `prefers-reduced-motion`.
- Must pause or reduce work outside the viewport.
- Must not run unnecessarily in background tabs.
- Canvas 2D should be evaluated before heavier WebGL/3D solutions.
- React Three Fiber is not mandatory.

---

## Accessibility rules

Accessibility is a release requirement, not an optional cleanup phase.

Always preserve:

- Keyboard navigation.
- Visible focus.
- Semantic HTML.
- WCAG AA contrast.
- Accessible names.
- Alt text where appropriate.
- Reduced-motion support.
- Mobile usability.
- Content that does not depend on hover alone.
- Content that remains understandable without animations.

---

## Performance rules

Animation must not destroy performance.

Prefer:

- Server Components by default.
- Client Components only where interaction requires them.
- Dynamic loading for heavy visual effects.
- Optimized images.
- Optimized fonts.
- `transform` and `opacity` for animation when possible.
- Pausing animations outside the viewport.
- Mobile-specific simplification.

Avoid:

- Hydrating static sections unnecessarily.
- Multiple libraries solving the same problem.
- Infinite effects running outside the viewport.
- Heavy 3D only for visual novelty.

Measure before and after meaningful performance changes.

---

## Backend rules

For Laravel work:

- Follow existing project structure before introducing new patterns.
- Keep controllers thin.
- Validate input server-side.
- Keep authorization server-side.
- Use transactions for multi-step writes that must remain consistent.
- Prevent drafts from leaking into public API responses.
- Use API Resources or the project's established serialization pattern.
- Avoid raw SQL when Query Builder/Eloquent is clear enough.
- If raw SQL is justified, use bindings and document why.
- Never expose secrets, stack traces, internal paths, or credentials.
- Add tests for meaningful backend behavior.

---

## Frontend rules

For React / Next.js work:

- TypeScript must remain strict.
- Avoid `any` unless a temporary integration requires it and it is documented.
- Keep data-fetching separate from presentational concerns.
- Do not duplicate CMS production content in React files.
- Do not turn the whole application into Client Components.
- Keep components focused.
- Use accessible interactive primitives.
- Avoid a global state library unless the requirement clearly justifies it.
- Ensure both languages and both themes are considered for UI changes.

---


## Shared home-server production rules

The portfolio is one application among multiple independent projects hosted on the same Linux computer.

The shared server topology is defined in `docs/SERVER_ARCHITECTURE.md`.

Portfolio production contract:

```text
lucianogonzalez.dev
    -> Cloudflare Tunnel
    -> http://127.0.0.1:8000
    -> portfolio Docker gateway
```

Rules:

- Do not run a dedicated `cloudflared` container inside the portfolio project.
- `cloudflared` is a shared Linux service owned by the server.
- Do not start, stop, reconfigure or assume ownership of other projects.
- Do not use ports reserved by other projects.
- The portfolio initially owns `127.0.0.1:8000`.
- Only the portfolio gateway may publish that host port.
- Web, API and MySQL remain inside Docker networks.
- Never expose MySQL publicly.
- Do not require router port forwarding for normal HTTP/HTTPS access.
- Never store the Cloudflare Tunnel token in this repository.
- Laravel authentication remains mandatory for administration.
- Cloudflare Access may be added as defense in depth for `/admin`.
- Backups for the portfolio must be isolated from backups for other projects.

### Deployment responsibility boundary

This repository owns application and release readiness: services, runtime boundaries, environment contracts, persistence requirements, migrations, bootstrap, health checks, build/test commands, and deployment handoff documentation.

Actual Linux host inspection and operation belong to the external `home_server_ops_claude` workflow. Portfolio development agents must not duplicate or execute its host preflight, Docker installation, final production Compose, `cloudflared`, firewall, backup/restore, reboot recovery, multiproject registry, cloning, production secrets, or deployment procedures unless the user explicitly changes that responsibility boundary.

The target is a deployment-ready repository, not a prematurely deployed repository. Preserve `docs/SERVER_ARCHITECTURE.md` as the shared topology contract and `docs/DEPLOYMENT.md` as the application handoff consumed by external operations.

## Docker rules

Docker must provide a reproducible development/test environment and clear production-compatible application boundaries. Final production images, Compose and host-specific configuration are created or adjusted by the external operations workflow after inspecting the real server.

Expected portfolio services:

- Gateway/reverse proxy.
- Next.js frontend.
- Laravel backend.
- MySQL.

`cloudflared` is intentionally NOT a portfolio Docker service.

Only add Redis, queues, scheduler containers, mail services, or other infrastructure when a real requirement appears.

Requirements:

- No secrets committed to Git.
- Version container images deliberately.
- Persistent portfolio database/media volumes.
- Health checks where useful.
- Development and production builds must not be conflated.
- Document migrations, seed, startup, shutdown, backup, restore and rollback procedures.
- Application services communicate over project-specific Docker networks.
- MySQL must not publish a host port.
- Only the gateway may publish the portfolio entrypoint.
- Bind the production entrypoint to `127.0.0.1:8000`, not `0.0.0.0:8000`, unless a documented infrastructure change requires otherwise.
- The portfolio repository must not contain or require the Cloudflare Tunnel token.

---

## Port allocation rules

- Portfolio production entrypoint: `127.0.0.1:8000`.
- Check `docs/SERVER_ARCHITECTURE.md` before changing a host port.
- Never use a host port already allocated to another project.
- Internal containers should use Docker service DNS rather than host ports.
- If the assigned port changes, update the server port registry and documentation in the same change.

---

## Testing and verification

Do not mark a non-trivial task complete without verification.

Depending on the change, run the relevant checks:

### Frontend

- Lint.
- Type checking.
- Tests.
- Production build.
- Accessibility checks where applicable.

### Backend

- Laravel tests.
- Static analysis if configured.
- Formatting if configured.
- Migration checks when schema changes.

### Integration

Verify:

- Frontend/API contract.
- Draft content is not public.
- Language behavior.
- Theme behavior.
- Mobile behavior.
- Failure states.

If a check cannot be run, say so explicitly.

---

## Workflow for non-trivial tasks

Before implementation:

1. Read the required documentation.
2. Identify the active `ROADMAP.md` phase.
3. Inspect existing code and conventions.
4. State a concise implementation plan.
5. Identify risks or architecture decisions.

During implementation:

1. Work in small, reviewable changes.
2. Do not modify unrelated files.
3. Preserve current behavior unless the task requests a change.
4. Update tests with behavior changes.
5. Update documentation when architecture or workflow changes.

After implementation:

1. Run the relevant checks.
2. Review the diff.
3. Report what changed.
4. Report what was tested.
5. Report remaining work or risks.
6. Update roadmap checkboxes only when the task is actually complete.

---

## Planning rules

For broad features or multi-file changes:

- Create or update a written plan before coding.
- Break work into verifiable tasks.
- Each task should have a clear completion condition.
- Do not implement future roadmap phases unless explicitly requested.
- If a task reveals an architectural decision, document it before continuing.

### Branch and worktree continuity

A cohesive roadmap phase or feature should normally use one branch and one worktree from brainstorming and specification through implementation, reviews, fixes, and final integration. Reuse that branch and worktree when execution begins. Documentation/planning and implementation must not be split into separate worktrees, and per-task worktrees must not be created, unless the user explicitly requests it.

This continuity rule does not weaken the requirement that `main` remain stable, does not authorize destructive commands, force pushes, history rewriting, unauthorized merges, or branch deletion, and does not replace any required explicit user approval.

If Codex subagents or a workflow plugin are available, non-trivial work may be split into implementation, testing, and review responsibilities. The final result must still be checked against this repository's documentation and roadmap.

---

## Dependency policy

Before adding a dependency:

1. Confirm the feature is actually needed.
2. Check whether the platform or existing stack already solves it.
3. Prefer maintained packages.
4. Consider bundle/runtime cost.
5. Avoid overlapping libraries.
6. Document important architectural dependencies.

Do not install packages solely because they are popular.

---

## Security and confidentiality

Never:

- Commit API keys, tokens, passwords, or real credentials.
- Publish confidential employer/client data.
- Invent or expose internal banking information.
- Expose private endpoints or real production identifiers in public content.
- Put secrets in frontend environment variables.

Use `.env.example` with placeholders when environment variables are introduced.

---

## Documentation rules

When a significant decision changes:

- Update `docs/ARCHITECTURE.md`.

When portfolio production behavior changes:

- Update `docs/DEPLOYMENT.md`.

When shared-server topology, Cloudflare Tunnel strategy, port allocation, subdomain conventions, multiproject isolation or shared backup policy changes:

- Update `docs/SERVER_ARCHITECTURE.md`.

When product scope or positioning changes:

- Update `docs/PROJECT.md`.

When project progress changes:

- Update `ROADMAP.md`.

When developer setup changes:

- Update the project `README.md`.

Documentation and implementation must not knowingly contradict each other.

---

## Definition of done

A task is done only when:

- Requested behavior is implemented.
- Relevant tests/checks pass.
- No known regression is ignored.
- Accessibility and responsive impact were considered.
- Documentation is updated when required.
- The result respects the current roadmap phase and architecture.

## Subagent model safety

When Superpowers uses subagent-driven development:

- Never silently allow a subagent to inherit GPT-5.6 Sol.
- Mechanical tasks should use the cheapest suitable model.
- Integration/review tasks should use the standard model.
- Reserve the most capable model for architecture and final reviews.
- Before spawning a subagent, explicitly select its model when
  the Codex runtime supports model routing.
- If the runtime cannot explicitly control the child model,
  STOP before spawning multiple subagents and inform the user.
- Do not assume that a requested model override was applied.
