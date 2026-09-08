# Task 5 Report — Compensated owned-asset lifecycle

## Status

Implemented the initial owned-asset lifecycle and transition-action surface in
the Phase 4 worktree. Profile photos, project images, technology icons and CVs
receive owner-specific private UUID paths. CVs remain private and do not create
public-disk copies.

## Changes

- Added asset validation and controlled-operation exceptions, a staged-asset
  value object, and `AssetLifecycleService`.
- Added explicit replace, remove, show, hide, return-to-draft and delete
  actions backed by the lifecycle service.
- Added private persistent media storage to the development API service at
  `/var/www/html/storage/app/private`, retaining the existing controlled public
  media volume.
- Updated repository validation for the new volume.
- Added focused storage-fake coverage for UUID staging, MIME/size rejection,
  private hidden replacement cleanup, CV private-only storage, show, hide,
  draft return and deletion cleanup.

## RED / GREEN proof

Initial focused execution after adding tests:

```powershell
docker compose --profile test run --rm api-test php artisan test --filter='AssetLifecycleTest|AssetTransitionActionTest'
```

RED result: 7 failures. The meaningful expected failure was
`Target class [App\Domain\Assets\AssetLifecycleService] does not exist.`
The first test revision also exposed the seed-created Profile singleton and a
missing GD extension; tests were corrected to use that singleton and a real
embedded PNG fixture before implementation.

GREEN result from the same command: **7 passed, 28 assertions**.

## Verification

```powershell
docker compose --profile test run --rm api-test php artisan test --filter='AssetLifecycleTest|AssetTransitionActionTest'
docker compose --profile test run --rm api-test php artisan test
docker compose --profile test run --rm api-test vendor/bin/pint
docker compose --profile test run --rm api-test vendor/bin/pint --test
node infra/validation/validate-repository.mjs
git diff --check
```

- Focused tests: **7 passed, 28 assertions**.
- The full backend suite was started and showed the added asset tests passing,
  but the captured command output was truncated before its final aggregate
  summary; it must be rerun for a fresh complete-suite count.
- Pint apply fixed one strict fully-qualified-name style issue; final Pint
  verification passed across **107 files**.
- Repository validation passed.
- `git diff --check` passed.

## Concerns

- The focused suite establishes the core happy paths and validation, but does
  not yet exercise every required injected filesystem/DB failure branch or
  cache-lock ordering observation from the task brief. Those fault-injection
  tests should be added before treating this task as release-complete.
- The service logs only operation identifier, entity class/id and operation;
  it intentionally omits binary data, user data and private paths.

## Commit

- `314024039c44a5b78f1a8f8c5ef95a51d66b0a7d` —
  `feat(api): add compensated owned asset lifecycle`

## Completion addendum — fault injection and ordering coverage

Additional storage-fake and fault-injection coverage now proves:

- failed private staging leaves no owner reference or orphan;
- real MIME/content, SVG and size policies reject invalid uploads;
- a locked-row/database failure removes the staged file and preserves the prior
  reference;
- failed public replacement restores the owner’s former private/public paths
  while the former public copy remains available;
- failed public withdrawal preserves the visible owner/reference;
- failed hidden-private cleanup restores the old database reference and returns
  a controlled administrative error;
- CV replacement remains private only;
- delete removes both the public copy and private original;
- a recording file cache store observes `withdraw -> first forget -> DB update
  / commit -> second forget`, with mutation-lock acquisition before withdrawal
  and release after the final forget.

The cleanup-restoration test was written first and exposed that a failed private
delete was previously logged as success after its DB reference had been cleared.
`AssetLifecycleService` now restores the old snapshot, invalidates both locales,
and throws its controlled exception in that case.

### Final verification

```powershell
docker compose --profile test run --rm api-test php artisan test --filter='AssetLifecycleTest|AssetTransitionActionTest'
docker compose --profile test run --rm api-test php artisan test --compact
docker compose --profile test run --rm api-test vendor/bin/pint --test
node infra/validation/validate-repository.mjs
git diff --check
```

- Focused assets: **13 passed, 53 assertions**.
- Complete backend suite: **122 passed, 496 assertions**.
- Pint initially found one ordered-import issue after the new test imports;
  Pint applied that fix and the final verification is run with the commit.
- Repository validation and diff check passed before the final formatting fix;
  both are rerun with the final commit verification.

## Review-fix round 1

### Fixes

- CV show now changes only editorial visibility. It never calculates, writes or
  copies a public path; CV hide likewise leaves its private original intact.
- Both singleton models (`Profile` and `SiteConfiguration`) are rejected by the
  deletion action.
- Required private cleanup now uses three bounded attempts. A visible
  replacement, removal or deletion whose committed-state cleanup still fails
  returns a controlled administrative error instead of silently reporting
  success; committed state is not fictionalized as rolled back.
- Visibility reduction records that the database mutation was persisted before
  handling an after-commit cache exception. A post-commit cache failure does
  not restore a withdrawn public copy; it is logged and returned as a
  controlled administrative failure.
- The recording store now supports fault injection and its order test continues
  to prove locks enclose withdrawal, first invalidation, mutation and later
  invalidation.

### TDD evidence

The new CV-show and singleton-delete tests initially failed RED: CV attempted
to force-fill an empty public-path attribute, and `SiteConfiguration` deletion
was accepted. The smallest production changes made CVs private-only in `show`
and rejected both singleton types; both tests then passed GREEN.

The visible-removal cleanup regression initially exposed the old behavior of
silently logging post-commit cleanup failure. The new retrying cleanup path
returns a controlled exception while preserving the already committed cleared
reference. The injected cache-forget test verifies the DB remains hidden and
the public copy stays withdrawn after after-commit invalidation failure.

### Review-fix verification

```powershell
docker compose --profile test run --rm api-test php artisan test --filter='AssetLifecycleTest|AssetTransitionActionTest'
docker compose --profile test run --rm api-test php artisan test --compact
docker compose --profile test run --rm api-test vendor/bin/pint --test
node infra/validation/validate-repository.mjs
git diff --check
```

- Focused assets: **17 passed, 67 assertions**.
- Clean full backend suite: **126 passed, 510 assertions**.
- Pint applied one fully-qualified-class style correction; final Pint, repository
  validation and diff checks are performed after this addendum is committed.
