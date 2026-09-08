# Task 6 — Localized public API report

## Scope

Implemented only the Phase 4 localized public content API:

- route-localized, read-only endpoints for profile, experiences, work cases,
  projects, technologies, and site;
- safe `es`/`en` validation using the existing API error envelope;
- explicit Resources and public-only queries backed by `PublicContentCache`;
- focused API contract tests.

No CV streaming/download route, seeding, Filament work, frontend work, or
documentation/roadmap changes were made.

## RED → GREEN evidence

1. Added `LocalizedContentApiTest` and `PublicApiContractTest` before adding
   routes, middleware, controllers, or Resources.
2. Initial focused command:

   ```text
   docker compose --profile test run --rm api-test php artisan test --filter='(LocalizedContentApiTest|PublicApiContractTest)'
   ```

   After correcting test-file syntax and publication-guard-safe fixtures, it
   failed as expected because all localized routes were absent: expected 200,
   received the existing `404 not_found`; the unsupported locale assertion also
   received `not_found` instead of `unsupported_locale`.
3. Added the locale middleware, routes, six thin controllers, and six explicit
   Resources.
4. GREEN focused command (rerun after Pint):

   ```text
   docker compose --profile test run --rm api-test php artisan test --filter='(LocalizedContentApiTest|PublicApiContractTest)'
   ```

   Result: `10 passed (79 assertions)`.

## Implementation notes

- `RequireSupportedLocale` accepts the route parameter broadly and uses
  `SupportedLocale::tryFrom()`. Unsupported locales return the established
  JSON error envelope with `404 unsupported_locale` before any content query.
- The route locale is stored on the request and is the only locale source;
  query-string locale and `Accept-Language` are ignored.
- Controllers query only `publiclyAvailable()` content. Nested technologies
  receive their own published-and-visible filter and contextual pivot order.
- Cache closures store only resolved array data using the existing
  `PublicContentCache` and `PublicEndpoint` enum values.
- Resources omit identifiers, editorial/status data, pivot fields, storage
  paths, translation-column suffixes, and excluded future fields. Optional
  fields remain explicit `null` and collections remain `[]`.
- Image/photo/icon values require a verified `public` disk copy and emit a
  relative `/storage/...` URL only then. CV availability checks the private
  disk but emits only the fixed same-origin `/cv/luciano-gonzalez-{locale}.pdf`
  link.

## Files

- `api/bootstrap/app.php`
- `api/routes/api.php`
- `api/app/Http/Middleware/RequireSupportedLocale.php`
- `api/app/Http/Controllers/Api/V1/ProfileController.php`
- `api/app/Http/Controllers/Api/V1/ExperienceController.php`
- `api/app/Http/Controllers/Api/V1/WorkCaseController.php`
- `api/app/Http/Controllers/Api/V1/ProjectController.php`
- `api/app/Http/Controllers/Api/V1/TechnologyController.php`
- `api/app/Http/Controllers/Api/V1/SiteController.php`
- `api/app/Http/Resources/Api/V1/ProfileResource.php`
- `api/app/Http/Resources/Api/V1/ExperienceResource.php`
- `api/app/Http/Resources/Api/V1/WorkCaseResource.php`
- `api/app/Http/Resources/Api/V1/ProjectResource.php`
- `api/app/Http/Resources/Api/V1/TechnologyResource.php`
- `api/app/Http/Resources/Api/V1/SiteResource.php`
- `api/tests/Feature/Api/V1/LocalizedContentApiTest.php`
- `api/tests/Feature/Api/V1/PublicApiContractTest.php`

## Verification

```text
docker compose --profile test run --rm api-test vendor/bin/pint
```

Result: exit 0. Pint formatted the repository PHP files; six style issues in
the task's changed files were fixed.

```text
docker compose --profile test run --rm api-test php artisan test --filter='(LocalizedContentApiTest|PublicApiContractTest)'
```

Result: exit 0, `10 passed (79 assertions)`.

```text
docker compose --profile test run --rm api-test php artisan test --compact
```

Result: exit 0, `140 passed (604 assertions)` in 86.72 seconds.

```text
docker compose --profile test run --rm api-test php artisan route:list --path=api/v1
```

Result: exit 0. Listed the existing `api/v1` version route plus all six
localized public routes.

```text
git diff --check
```

Result: exit 0, no whitespace errors.

## Full-suite investigation

An initial compact full-suite invocation failed with migration errors such as
`table already exists` and `table does not exist`. Investigation showed that a
prior full Docker test invocation was still running against the same disposable
MySQL service, creating concurrent `RefreshDatabase` migration activity. After
both runners exited, a single clean non-overlapping full run passed as recorded
above. No code change was made for this environment-level contention.

## Commit

```text
feat(api): expose localized phase 4 public content
```

## Concerns

- The public site resource can advertise a stable CV URL only; serving that
  file privately is intentionally deferred to Task 7.
- The `public` disk URL is deliberately a relative `/storage/...` URL for
  public image copies. No private path is serialized.

## Fix round 1 — review findings

### Changes

- Replaced the repeated nested-technology `status`/`is_visible` predicates in
  `ExperienceController`, `WorkCaseController`, and `ProjectController` with
  `Technology::publiclyAvailable()`. The scope is invoked before `reorder()` so
  its public predicates are retained while the contextual `pivot.position`,
  then key ordering remains the approved output order.
- Strengthened `LocalizedContentApiTest` with nonempty published Spanish and
  English Experience, WorkCase, Project, highlight, and nested Technology
  fixtures. The test now recursively verifies equal response structure and
  checks deliberately different ES/EN localized values for all three managed
  collections. This prevents an always-Spanish collection Resource from being
  accepted by structural checks against empty arrays.
- Added positive public-copy assertions for `Project.image` and
  `Technology.icon` after creating verified public-disk copies.

### TDD and verification

The expanded behavioral tests were added and run before the controller policy
refactor. They passed against the existing locale-selection behavior, confirming
that this review round is a policy-deduplication refactor rather than a current
observable locale bug; after the scope substitution the same tests remained
green.

```text
docker compose --profile test run --rm api-test php artisan test --filter='(LocalizedContentApiTest|PublicApiContractTest)'
```

Result before and after refactor: exit 0, `10 passed (189 assertions)`.

```text
docker compose --profile test run --rm api-test vendor/bin/pint --test
```

Result: exit 0, `122 files` checked.

```text
docker compose --profile test run --rm api-test php artisan test --compact
```

Result: exit 0, `140 passed (714 assertions)` in 84.37 seconds.
