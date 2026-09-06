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
