# Task 3 Report — Public Content Cache and Lock Protocol

## Status

Implemented the Phase 4 sole public-content cache authority and its lock protocol. No public content endpoints were added.

## Delivered files

- `api/app/Support/PublicContentDependencies.php`
  - Exact model-to-endpoint dependency expansion, including Technology's four affected endpoints.
- `api/app/Support/PublicContentCache.php`
  - Exact versioned data/lock keys, forever data-array caching, cache-hit fast path, LockProvider enforcement, 60-second lease, five-second public wait, ten-second mutation wait, sorted multi-lock acquisition, reverse release, dual-locale invalidation, and bounded forget retry with sanitized logging.
- `api/app/Exceptions/PublicContentUnavailable.php`
  - Controlled exception for cache lock/invalidation failure.
- `api/bootstrap/app.php`
  - Existing Phase 3 envelope renderer extended only for `content_temporarily_unavailable` / HTTP 503 under `/api`.
- `api/tests/Feature/Cache/PublicContentCacheTest.php`
  - Keys, empty-array `rememberForever` behavior, array-only payloads, dependencies, and scoped invalidation tests.
- `api/tests/Feature/Cache/PublicContentCacheConcurrencyTest.php`
  - Lock capability enforcement, cache-hit no-lock behavior, file/database lock contract coverage, mutation locking, 503 envelope, and a two-process stale-rebuild race.
- `api/tests/Support/CacheRaceWorker.php`
  - Child-process roles for the real race protocol.

## TDD evidence

### RED

1. Added the cache and concurrency test suites before the production classes.
2. Ran:

   ```powershell
   docker compose --profile test run --rm api-test php artisan test --filter='PublicContentCacheTest|PublicContentCacheConcurrencyTest'
   ```

   Result: failed as expected because `App\Support\PublicContentCache`, `PublicContentDependencies`, and `PublicContentUnavailable` did not exist. The initially written data-provider annotations were corrected to PHPUnit 12 attributes before production implementation.
3. The planned command's historical “missing service” expectation did not apply in this worktree: `mysql-test` and the test image were available. The meaningful RED failure was therefore the missing cache implementation.

### GREEN

After the smallest cache service, dependencies, exception, renderer, and worker implementation:

```powershell
docker compose --profile test run --rm api-test php artisan test --filter='PublicContentCacheTest|PublicContentCacheConcurrencyTest'
```

Result: **13 passed, 55 assertions**. The `file` and `database` data-provider cases both passed, and neither concurrency case uses the `array` store.

## Verification commands

```powershell
# Focused cache / concurrency suite (file and database stores)
docker compose --profile test run --rm api-test php artisan test --filter='PublicContentCacheTest|PublicContentCacheConcurrencyTest'

# Full backend suite
docker compose --profile test run --rm api-test php artisan test

# Apply project formatting
docker compose --profile test run --rm api-test vendor/bin/pint

# Verify formatting
docker compose --profile test run --rm api-test vendor/bin/pint --test

# Whitespace / patch integrity
git diff --check
```

Final results:

- Focused: **13 passed, 55 assertions**.
- Full backend: **59 passed, 352 assertions**.
- Pint verification: **PASS (80 files)**.
- `git diff --check`: exit 0.

## Self-review

- Public cache keys are exclusively `public-content:v1:{locale}:{endpoint}` and rebuild keys are exclusively `public-content-rebuild:v1:{locale}:{endpoint}`.
- Values placed in the cache are resolver-returned arrays only; empty arrays are retained.
- Hits return before cache-store capability or lock acquisition; misses re-read the value inside the acquired lock.
- The lock provider is required before `Cache::lock()` is called for a miss or mutation path.
- Mutation keys use the endpoints × both locales Cartesian product, lexicographic sort, and reverse release.
- Invalidation does not use tags, TTL coherence, Redis, generic cache abstractions, or `Cache::flush()`.
- A falsey `forget()` is treated as successful only after a follow-up absence check; persistent failure retries three times and logs only the public cache key.
- The process race proves that a paused rebuild cannot leave its old representation cached after a visibility-reducing mutation commits.
- No endpoint/controller was introduced ahead of Task 6.

## Concerns

- The controlled exception renderer is verified through a temporary test route. Laravel reports that deliberately thrown test exception to the test log, but the HTTP response is the required sanitized 503 envelope; no public stack trace is exposed.
- Task 4+ actions must keep mutation locks across withdrawal, database commit, and the first/second explicit invalidation, as specified. This task supplies that primitive but intentionally does not add future mutation actions.

---

## Review-fix addendum

### Fixes applied

1. `PublicContentCache::remember()` now passes every resolver result through `dataArray()` before `Cache::forever()`. A model, `JsonResponse`, API envelope, or any other non-array value therefore raises `UnexpectedValueException` while the public key is still absent.
2. The process race now uses the migrated singleton `Profile` as persisted public state. The old rebuild queries `Profile::publiclyAvailable()` and writes the observed old data to its test marker before pausing. The mutation worker acquires both locale profile locks, runs the first invalidation, performs `is_visible = false` inside `DB::transaction()`, marks the commit, and runs the second invalidation before release. The test proves the Profile is no longer publicly available and neither locale cache key contains the old representation.
3. Concurrency setup now removes only the known public-content data keys; it no longer calls `Cache::flush()`. A file-store recording lock decorator asserts all four locale/endpoint keys are acquired in sorted lexical order and released in the exact reverse order.

### TDD review-fix evidence

#### RED

After adding the non-array parameterized test and complete ordering assertions, ran:

```powershell
docker compose --profile test run --rm api-test php artisan test --filter='PublicContentCacheTest|PublicContentCacheConcurrencyTest'
```

Result: **4 failures, 11 passed**. The three resolver variants failed with `TypeError` after the prior implementation had already executed `Cache::forever()`, proving the defect. The ordering test recorded releases using lock owners rather than lock names, exposing an incomplete test decorator; it was corrected before the production GREEN change.

#### GREEN

After validating the resolver output before storing and correcting the recording file-lock decorator:

```powershell
docker compose --profile test run --rm api-test php artisan test --filter='PublicContentCacheTest|PublicContentCacheConcurrencyTest'
```

Result: **15 passed, 58 assertions**. The process race passed once for `file` and once for `database`; no process concurrency scenario uses the array store.

### Final review-fix verification

```powershell
docker compose --profile test run --rm api-test php artisan test
docker compose --profile test run --rm api-test vendor/bin/pint
docker compose --profile test run --rm api-test vendor/bin/pint --test
git diff --check
```

Results:

- Full backend: **61 passed, 355 assertions**.
- Pint verification: **PASS (82 files)**.
- `git diff --check`: exit 0.

### Remaining concern

The API-envelope test intentionally throws the controlled exception and Laravel reports it to the test log; its HTTP response remains the required sanitized 503 envelope. No public error detail is exposed.
