# Task 4 Report — Publication Validation and Guarded Editorial Mutations

## Status

Implemented Task 4's publication validator, guarded model mutations, and
transactional editorial actions. The work stays within the approved Task 4
boundary: Show, Hide, and Return-to-draft remain Task 5 asset-lifecycle
actions, so their transition/timestamp behavior was intentionally not added
here.

## Delivered

- `PublicationValidator`, `PublicationIssue`, and
  `PublicationValidationException` provide stable issue codes and field paths
  for all independently publishable models.
- `EditorialMutationContext` and `EditorialMutationGuard` register defensive
  observers for every managed model. They reject direct state, key, deletion,
  and owned-asset mutations; preserve the local publication-state invariant;
  revalidate every published save; and invalidate affected public endpoints
  only after commit.
- `PublishContent`, `UpdateContent`, `ChangePublicKey`,
  `UpdateExperienceAggregate`, and `ReorderContent` make their changes in
  database transactions. Publish creates the published-hidden state and locks
  keyed identities; ordinary updates cannot mutate protected fields; aggregate
  writes assemble and validate proposed highlights/technology pivots before
  persistence.
- The pre-existing scope/cache tests now construct deliberately direct database
  fixtures where they need to test an impossible-from-the-application-path
  state, rather than bypassing the new model guard through Eloquent.
- Validator and action coverage includes all publishable model factories,
  bilingual requirements and optional-pair parity, canonical Profile/Technology
  content, URL/email rules, assets, dates, highlights, invalid technology
  relations, cache invalidation, key stability, and direct-mutation rejection.

## TDD evidence for completion fixes

The inherited partial Task 4 implementation was preserved. For each production
change made while completing it, a behavior test was added and run RED before
the minimal implementation change, then run GREEN:

1. **Publish rechecks the record under `lockForUpdate`.**
   - RED: `test_publish_rechecks_the_locked_state_before_performing_its_transition`
     failed because a stale draft model could republish a record that had
     already become published.
   - GREEN: `PublishContent` now checks the locked record is still a draft.
2. **The Experience aggregate cannot perform a Task 5 state transition.**
   - RED: `test_it_cannot_be_used_to_return_a_published_experience_to_draft`
     failed because protected state attributes were accepted by the aggregate
     action.
   - GREEN: `UpdateExperienceAggregate` rejects protected editorial fields.
3. **Locked-key confirmation is checked under `lockForUpdate`.**
   - RED: `test_key_change_rechecks_the_locked_record_before_accepting_confirmation`
     failed because a stale unlocked instance could skip the confirmation rule.
   - GREEN: `ChangePublicKey` rechecks `key_locked` on the locked model before
     changing the key.

Each RED/GREEN command used the isolated Phase 4 test profile:

```powershell
docker compose --profile test run --rm api-test php artisan test --filter=<test>
```

## Verification

```powershell
docker compose --profile test run --rm api-test php artisan test --filter='PublicationValidatorTest|EditorialMutationGuardTest|ExperienceAggregateActionTest'
docker compose --profile test run --rm api-test php artisan test
docker compose --profile test run --rm api-test vendor/bin/pint
docker compose --profile test run --rm api-test vendor/bin/pint --test
git diff --check
```

Results:

- Focused Task 4 tests: **41 passed, 72 assertions**.
- Full backend suite: **102 passed, 427 assertions**.
- Pint apply and verification: **PASS (95 files)**.
- `git diff --check`: exit 0.

## Concern / explicit scope boundary

The Task 4 brief includes Show/Hide/Return-to-draft timestamp cases, but the
approved implementation plan assigns their actions and all asset withdrawal
ordering to Task 5. They are deliberately absent from this commit; no direct
state mutation provides a substitute path.
