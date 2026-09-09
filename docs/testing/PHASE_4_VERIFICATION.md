# Phase 4 verification record

## Status

This file is scaffolding written by Task 15 (documentation). It records the
planned command matrix and defines what "verified" means for Phase 4. Every
evidence slot below is intentionally empty and labelled `Not run yet` — no
result is claimed here. Task 16 is the only task authorized to replace those
slots with exact commands, dates, outcomes, and any justified limitations,
after which `ROADMAP.md`'s Phase 4 checkboxes may be marked complete.

This is not a production deployment record. It is development/test
foundation evidence only, produced from this repository's own Docker Compose
environment.

## Planned command matrix

| # | Command | Purpose | Evidence |
|---|---|---|---|
| 1 | `docker compose --profile test run --rm api-test php artisan migrate:fresh --force` | Prove the full Phase 1–4 migration set applies cleanly to an empty disposable MySQL 8.4 database, in order, with no manual patching. | Not run yet |
| 2 | `docker compose --profile test run --rm api-test php artisan test` | Run the complete Laravel/PHPUnit suite (Domain, Database, Api, Assets, Cache, Filament, Console, Media, Documentation) against MySQL. Baseline going into Task 16 is 418 passing (per the Task 15 brief); the recorded evidence must state the actual final count. | Not run yet |
| 3 | Cache concurrency suite against the `file` store | Run `PublicContentCacheConcurrencyTest`'s `lockCapableStores` data-provider case for `file` specifically (e.g. `--filter=PublicContentCacheConcurrencyTest`, confirming the `file store` dataset entry passes) to prove real Laravel `LockProvider` lock/rebuild/race behavior, not `array`. | Not run yet |
| 4 | Cache concurrency suite against the `database` store | Same suite, confirming the `database store` dataset entry passes, proving the same guarantee through the `cache`/`cache_locks` MySQL tables. | Not run yet |
| 5 | `docker compose run --rm --no-deps api ./vendor/bin/pint --test` | Confirm formatting compliance across every PHP file touched or added in Phase 4. | Not run yet |
| 6 | `node infra/validation/validate-repository.mjs` | Confirm the repository-wide structural/documentation validator still passes with Phase 4's files in place. | Not run yet |
| 7 | `docker compose run --rm --no-deps api php artisan route:list --path=api/v1` | Confirm the exact registered `api/v1` route list matches `docs/api/PUBLIC_API_V1.md` (version endpoint plus the six locale-prefixed routes, each with the documented limiter). | Not run yet |
| 8 | `docker compose run --rm --no-deps api php artisan route:list --path=cv` | Confirm the two stable CV routes are registered exactly as documented, with names `cv.download.es`/`cv.download.en` and the `cv-download` limiter. | Not run yet |
| 9 | Run `PortfolioContentSeeder` twice against the disposable test database, then rerun its own safety test | `docker compose --profile test run --rm api-test php artisan db:seed --class=PortfolioContentSeeder` (twice), plus the seeder's dedicated PHPUnit coverage, to reconfirm idempotency, draft-only output, and zero created projects/users/CV rows/public assets on a second run. | Not run yet |
| 10 | `git diff --check` | Confirm no whitespace-conflict markers across the branch diff. | Not run yet |
| 11 | `git status --short` | Confirm a clean working tree at the point evidence is recorded. | Not run yet |
| 12 | Full branch diff review from the Phase 3 base | Manual/agent scan for unresolved placeholder markers, Phase 5 code, secret-like values, public private-path leaks, and any unapproved field, cross-checked against `docs/superpowers/specs/2026-09-02-phase-4-cms-design.md` section 22's out-of-scope list. | Not run yet |
| 13 | Self-review against spec/prompt/roadmap/Phase 3 conventions | Confirm every approved spec section, the original Phase 4 prompt, `ROADMAP.md`'s Phase 4 items, and Phase 3's envelope/auth/rate-limit conventions are honored, and that Phase 4 does not implement anything from spec section 22. | Not run yet |
| 14 | Final whole-branch code review (`superpowers:requesting-code-review`) | Independent review pass over the complete Phase 4 diff; every confirmed finding resolved through a test-first loop with its own regression test and focused commit, then affected verification rerun. | Not run yet |

## What "passing" means for each row

- **Row 1** — the command exits `0` against `mysql-test` with no manual schema intervention.
- **Row 2** — the command exits `0`; the recorded count must be greater than or equal to the pre-Task-15 baseline (418), reflecting the documentation contract test added in Task 15 plus anything Task 16 itself adds.
- **Rows 3–4** — both named dataset entries (`file store`, `database store`) in `PublicContentCacheConcurrencyTest::lockCapableStores()` pass, not merely the whole suite in aggregate; this is the spec's explicit requirement that the interprocess race test never runs only against `array`.
- **Row 5** — zero files reported as needing formatting.
- **Row 6** — the validator's own exit code is `0`.
- **Rows 7–8** — the JSON/table route list output contains exactly the paths and limiter middleware documented in `docs/api/PUBLIC_API_V1.md`, with no undocumented extra public route and no documented route missing.
- **Row 9** — the second seeding run creates no duplicate row, updates the same logical identifiers, leaves every seeded row draft/hidden/unpublished, creates zero `Project`/`CvDocument`/user rows, and leaves any unrelated manually created record (including a `Project` used by the seeder's own non-destructive test) intact.
- **Rows 10–11** — both commands report nothing to fix.
- **Rows 12–14** — narrative findings, not exit codes; each finding is either "none found" with the scope actually inspected stated, or a linked fix commit.

## Evidence

_(Task 16 replaces this section. Until then, every row above reads `Not run yet` and no pass/fail claim exists anywhere in this file.)_
