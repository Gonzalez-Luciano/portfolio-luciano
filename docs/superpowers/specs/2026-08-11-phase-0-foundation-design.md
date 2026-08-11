# Phase 0 Foundation Design

**Date:** 2026-08-11  
**Status:** Approved design baseline; awaiting written-spec review  
**Scope:** Discovery and definition only. No application scaffolding, dependencies, or Docker implementation.

## 1. Purpose

This specification closes the decisions required to begin the portfolio's application workspace without changing the established product direction or multiproject server architecture.

The product is Luciano González's bilingual professional portfolio, positioned as **Backend Developer | PHP & Laravel**. It must demonstrate credible experience with real backend systems while remaining accessible, performant, secure, and careful with confidential information.

## 2. Repository identity and boundaries

- Local workspace directory: `portfolio-luciano`.
- Canonical GitHub repository slug: `portfolio`.
- Production hostname: `lucianogonzalez.dev`.
- The local directory does not need to match the remote repository slug.
- The portfolio is a monorepository containing `web/`, `api/`, `infra/`, and `docs/`.

The current Git repository and documentation baseline already exist. Phase 3 will initialize the application/framework workspace and create the missing project structure; it will not reinitialize Git.

## 3. Definitive technology constraints

### Frontend

- React with Next.js App Router.
- TypeScript strict mode.
- Tailwind CSS 4+.
- `pnpm` as the package manager.
- `next-intl` as the internationalization library.

### Backend and administration

- PHP 8.5.
- Laravel 13.
- Filament 5.
- Livewire 4+ as required by Filament 5.

Use compatible current stable patch and minor releases within these agreed major lines. Phase 0 does not pin arbitrary patch versions. PHP, Laravel, or Filament must not be downgraded automatically. If a concrete dependency later proves incompatible, implementation must stop and report the incompatibility before changing the agreed stack.

### Database

- MySQL 8.4 LTS using the official `mysql:8.4` Docker image line.
- Do not use `latest`; updates within 8.4 must be deliberate and verified.
- MySQL remains private inside the portfolio Docker networks and has independent persistence and backups.

## 4. URL and locale contract

### Canonical local-development URLs

- Root: `http://localhost:8000`
- Spanish portfolio: `http://localhost:8000/es`
- English portfolio: `http://localhost:8000/en`
- Public API base: `http://localhost:8000/api/v1`
- Administration: `http://localhost:8000/admin`

The gateway is the only canonical local-development entrypoint and the only service that publishes the portfolio host port. Next.js, Laravel, MySQL, and future internal services communicate through Docker-internal networking. Their container ports are not canonical development URLs.

When implemented, the gateway must proxy Next.js development WebSocket/HMR traffic correctly. The exact gateway technology remains deferred until infrastructure implementation.

### Localized routes

- Only `es` and `en` are initially supported.
- `/es` and `/en` are canonical; `/` never renders portfolio content and always redirects.
- Resolution priority is a valid explicit preference cookie, then supported `Accept-Language`, then Spanish at `/es`.
- Only an explicit UI language change is persisted. Automatically inferred language is not persisted, and invalid stored values are ignored.
- Local and production behavior must be equivalent.
- Locale detection is implemented once through `next-intl`, not duplicated across layers.

## 5. Application and server topology

```text
lucianogonzalez.dev
    -> shared host-level Cloudflare Tunnel
    -> http://127.0.0.1:8000
    -> portfolio project gateway
       -> Next.js
       -> Laravel / Filament
          -> MySQL
```

- `cloudflared` is one shared Linux host service and is not in the portfolio Compose stack.
- The portfolio remains isolated from other Dockerized projects.
- Only the gateway publishes `127.0.0.1:8000`; internal services are not directly exposed.
- Router port forwarding is not required for normal HTTP/HTTPS publication.
- The Cloudflare Tunnel token never belongs in this repository.

Definitive logical server paths:

- Applications root: `/srv/apps`.
- Backups root: `/srv/backups`.
- Portfolio deployment: `/srv/apps/portfolio`.
- Portfolio backups: `/srv/backups/portfolio`.

Linux distribution/version, CPU, RAM, physical storage, disk layout, and final physical placement of Docker data are deployment-preflight inputs. They are intentionally deferred and must not be inferred.

## 6. Public hostname convention

- The portfolio uses the apex `lucianogonzalez.dev`.
- Each deployed independent project receives `<slug>.lucianogonzalez.dev`.
- Slugs are lowercase, concise, descriptive, DNS-safe, and use hyphens only when needed.
- Frontend, API, and administration normally share the hostname through `/`, `/api/*`, and `/admin/*`.
- Separate API/admin hostnames require a documented reason why same-origin routing is unsuitable.
- Do not reserve names for projects that do not yet exist.

Before deployment, the central server registry records the project identifier, public hostname, loopback port, deployment path, and Cloudflare Tunnel mapping.

## 7. Git and release policy

- `main` is the only long-lived stable branch; there is no permanent `develop`.
- Use short-lived branches with `feat/`, `fix/`, `docs/`, `refactor/`, `test/`, `chore/`, or `infra/` where appropriate.
- Merge through pull requests after relevant CI passes.
- Disable direct pushes after remote branch protection is configured.
- Use isolated branches/worktrees for Superpowers plans when appropriate.
- Never merge into `main` without explicit approval, force-push, or rewrite shared history.
- Annotated tags from approved `main` commits represent meaningful milestones/releases such as `v0.1.0` and `v1.0.0`, not every merge.

## 8. Public CV contract

- Public CVs are PDF only, with separate Spanish and English files managed through Filament.
- The active locale selects the corresponding file.
- Stable paths are `/cv/luciano-gonzalez-es.pdf` and `/cv/luciano-gonzalez-en.pdf`.
- Editable/source formats are private.
- If one locale's file is unavailable, hide that download instead of serving the wrong language.

## 9. Confidentiality publication policy

### Prohibited

- Credentials, passwords, keys, secrets, tokens, certificates, and connection strings.
- Private endpoints and unnecessarily risky internal infrastructure details.
- Real financial/account/transaction data and identifiers.
- Customer, student, family, employee, or other personal data.
- Proprietary business rules, source code, internal documents, confidential identities, and anything covered by confidentiality obligations.

### Requires anonymization and manual review

- Non-public employers/clients, institutions, payment providers, workflows, incidents, and infrastructure.
- Screenshots, logs, database examples, and API payloads/responses.
- Quantitative results, performance claims, volumes, financial amounts, and operational metrics.

Visual and technical artifacts must be checked for hidden names, emails, IDs, URLs, tokens, database/institution names, transaction data, browser tabs, terminal paths, logs, and metadata.

### Generally publishable after accuracy review

- Luciano's own responsibilities.
- Intentionally public employer identities unless restricted.
- Public technologies, generic patterns, generalized problems/solutions, lessons, safe qualitative outcomes, and personal/open-source projects.

### Rules

- Never invent metrics, identities, customers, institutions, achievements, or production scale.
- Quantitative claims must be verifiable.
- Anonymization preserves the lesson while removing identifiers.
- Uncertainty defaults to non-publication.
- Public availability does not make internal details publishable.
- Confidentiality takes precedence over case-study detail.
- Phase 1 must complete a content-specific matrix before real professional content enters the CMS.

## 10. Phase boundaries and deferrals

Phase 0 does not create application code, initialize Next.js or Laravel, install dependencies, or create Docker files.

Explicitly deferred:

- Gateway technology, to infrastructure implementation.
- Physical server/disk discovery, to deployment preflight.
- Concrete future-project subdomains, until those projects exist.
- Content-specific classification, to Phase 1.
- Exact stable patch versions, to implementation dependency resolution.

These deferrals do not block the first implementation plan.

## 11. Acceptance

Phase 0 is ready for closure when this specification and authoritative documents agree, completed roadmap items are marked, deferred work has an owner and phase, and the user approves this written specification.
