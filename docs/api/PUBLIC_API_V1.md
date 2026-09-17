# Public API v1 contract

This document describes the exact, currently-implemented shape of the
localized public read API added in Phase 4, plus the two stable CV download
routes and the unchanged Phase 3 version endpoint. Every fact below is traced
to the real Laravel source (routes, controllers, Resources, middleware,
exception handling) rather than to illustrative examples — where this file and
the approved design spec (`docs/superpowers/specs/2026-09-02-phase-4-cms-design.md`)
disagree on a byte-level detail, this file wins because it is checked against
the application by
`api/tests/Feature/Documentation/ApiContractDocumentationTest.php`.

There is no `?locale=`, `Accept-Language` negotiation, implicit locale
detection, item-by-key route, pagination, or public mutation endpoint anywhere
in this contract.

## Routes

| Method | Path | Rate limiter | Controller |
|---|---|---|---|
| GET | `/api/v1` | `public-api` | inline closure → `ApiVersionResource` |
| GET | `/api/v1/{locale}/profile` | `public-api` | `App\Http\Controllers\Api\V1\ProfileController` |
| GET | `/api/v1/{locale}/experiences` | `public-api` | `App\Http\Controllers\Api\V1\ExperienceController` |
| GET | `/api/v1/{locale}/work-cases` | `public-api` | `App\Http\Controllers\Api\V1\WorkCaseController` |
| GET | `/api/v1/{locale}/projects` | `public-api` | `App\Http\Controllers\Api\V1\ProjectController` |
| GET | `/api/v1/{locale}/technologies` | `public-api` | `App\Http\Controllers\Api\V1\TechnologyController` |
| GET | `/api/v1/{locale}/site` | `public-api` | `App\Http\Controllers\Api\V1\SiteController` |
| GET | `/cv/luciano-gonzalez-es.pdf` | `cv-download` | `App\Http\Controllers\CvDownloadController@es` |
| GET | `/cv/luciano-gonzalez-en.pdf` | `cv-download` | `App\Http\Controllers\CvDownloadController@en` |

Registered in `api/routes/api.php` (the six locale-prefixed routes, plus the
unchanged `v1` version route, all under the `throttle:public-api` limiter; the
locale-prefixed group additionally carries the `supported-locale` middleware
alias, which resolves to `App\Http\Middleware\RequireSupportedLocale`) and
`api/routes/web.php` (the two CV routes, named `cv.download.es` and
`cv.download.en`, under `throttle:cv-download`).

`{locale}` accepts only `es` or `en` (`App\Enums\SupportedLocale`). Any other
value — including a syntactically similar one such as `fr` — is rejected by
`RequireSupportedLocale` before any locale-conditioned content query or
Resource is built, and the middleware never falls back to another locale.

`public-api` allows 60 requests per minute per client IP.
`cv-download` allows 30 requests per minute per client IP. Both are defined in
`api/app/Providers/AppServiceProvider.php`.

## Envelope

Success and error responses are mutually exclusive; a response never carries
both keys.

Success:

```json
{"data": {}}
```

`data` is an object for `profile` and `site`, and an array for `experiences`,
`work-cases`, `projects`, and `technologies`. An empty published-content
collection returns HTTP 200 with `"data": []`, never an error.

Error (`App\Http\Responses\ApiErrorResponse`):

```json
{
  "error": {
    "code": "not_found",
    "message": "The requested API resource was not found.",
    "details": {}
  }
}
```

`details` is always a JSON object (`{}` when there is nothing to report, never
`[]` or `null`). `code` is a stable `snake_case` string. The HTTP status is
never duplicated inside the body.

## Error codes

All of the following are produced only for requests under `/api` (or any
request that already expects JSON); this envelope is registered in
`api/bootstrap/app.php` and gated by `$request->is('api') || $request->is('api/*')`
or `$request->expectsJson()`.

| `code` | HTTP status | Exact `message` | Trigger |
|---|---:|---|---|
| `unsupported_locale` | 404 | `The requested locale is not supported.` | `{locale}` is not `es`/`en`; produced directly by `RequireSupportedLocale` before any query. |
| `not_found` | 404 | `The requested API resource was not found.` | Any unmatched `/api/*` route, or a singleton (`Profile`/`SiteConfiguration`) or scoped `firstOrFail()` lookup that finds no publicly-available row — Laravel converts the underlying `ModelNotFoundException` into a `NotFoundHttpException`, which this handler maps to `not_found`. |
| `method_not_allowed` | 405 | `The requested HTTP method is not allowed.` | Wrong HTTP verb on an existing route; the original `Allow` header is preserved on the response. |
| `content_temporarily_unavailable` | 503 | `Public content is temporarily unavailable. Please try again later.` | `App\Exceptions\PublicContentUnavailable`, thrown by `PublicContentCache` when a public cache-rebuild lock cannot be acquired within its bound (see Cache section below). Never falls back to an unfiltered or stale representation. |
| `rate_limited` | 429 | `Too many requests. Please try again later.` | The `public-api` limiter only. |
| `http_error` | (matches the underlying HTTP exception's status) | `The API request could not be completed.` | Fallback for any other `Symfony\Component\HttpKernel\Exception\HttpExceptionInterface` under `/api`. |

The `cv-download` limiter has no custom response registered, so a 429 from the
two CV routes is Laravel's own default throttle response, **not** this JSON
envelope — consistent with the CV routes living outside `/api/v1` (see CV
routes below).

## Resource field contracts

Every mapped field is read straight from the named database column via the
locale suffix; no field name derived from a database `_es`/`_en` suffix is
ever used as a public JSON key, and no internal ID, position, pivot column,
timestamp, the publication `status`, `is_visible`, or `key_locked` value is
ever emitted (see "Never exposed" below).

### `GET /api/v1/{locale}/profile` — `ProfileResource`

```json
{
  "data": {
    "name": "string",
    "location": null,
    "work_modes": ["on_site", "hybrid", "remote"],
    "headline": "string",
    "short_summary": "string",
    "introduction": "string",
    "availability": "string",
    "statement": {"lead": "string", "emphasis": "string", "tail": "string"},
    "closing": {"line_one": "string", "line_two": "string"},
    "cta": "string",
    "photo": null
  }
}
```

| Field | Type | Source |
|---|---|---|
| `name` | `string` | `name` |
| `location` | `string \| null` | `location` (not translated) |
| `work_modes` | `array<"on_site" \| "hybrid" \| "remote">` (always present, may be empty) | `work_modes` JSON; always emitted in `App\Enums\WorkMode` declaration order |
| `headline` | `string` | `headline_{locale}` |
| `short_summary` | `string` | `short_summary_{locale}` |
| `introduction` | `string` | `introduction_{locale}` |
| `availability` | `string` | `availability_{locale}` |
| `statement` | `{lead: string, emphasis: string, tail: string} \| null` | `statement_lead_{locale}`, `statement_emphasis_{locale}`, `statement_tail_{locale}`; always present, `null` unless all three are non-blank |
| `closing` | `{line_one: string, line_two: string} \| null` | `closing_line_one_{locale}`, `closing_line_two_{locale}`; always present, `null` unless both are non-blank |
| `cta` | `string` | `cta_{locale}` |
| `photo` | `{url: string, alt: string} \| null` | present only when `photo_public_path` is non-null **and** that path is verified to exist on the `public` disk at request time; otherwise `null` |

`photo.url` is `"/storage/" . ltrim(photo_public_path, '/')`; `photo.alt` is
`photo_alt_{locale}`. A missing/unpublished Profile singleton row returns the
`not_found` error, not a defaulted object.

### `GET /api/v1/{locale}/experiences` — `ExperienceResource` (array)

```json
{
  "data": [
    {
      "key": "string",
      "organization": null,
      "role": "string",
      "start": "YYYY-MM",
      "end": null,
      "summary": "string",
      "highlights": ["string"],
      "technologies": [{"key": "string", "name": "string", "category": "backend", "icon": null}]
    }
  ]
}
```

| Field | Type | Source |
|---|---|---|
| `key` | `string` | `key` |
| `organization` | `string \| null` | `organization_label_{locale}`; always present, `null` when the optional bilingual pair is empty |
| `role` | `string` | `role_{locale}` |
| `start` | `string` (`YYYY-MM`, zero-padded month) | `start_year`/`start_month` |
| `end` | `string \| null` | `end_year`/`end_month`; `null` for a current (open-ended) experience |
| `summary` | `string` | `summary_{locale}` |
| `highlights` | `array<string>` (always present, may be empty) | each highlight's `content_{locale}`, ordered by the highlight's own `position` then `id` |
| `technologies` | `array<TechnologyResource>` (always present, may be empty) | published+visible technologies only, ordered by pivot `position` then `technologies.key` |

There is no `is_current` field — a current experience is represented purely by
`end: null`.

### `GET /api/v1/{locale}/work-cases` — `WorkCaseResource` (array)

```json
{
  "data": [
    {
      "key": "string",
      "experience_key": null,
      "title": "string",
      "context": "string",
      "problem": "string",
      "contribution": "string",
      "technical_approach": "string",
      "outcome": "string",
      "technologies": []
    }
  ]
}
```

Each of `title`, `context`, `problem`, `contribution`, `technical_approach`,
`outcome` maps directly to its `{field}_{locale}` column; all six are required
bilingual pairs. There is no confidentiality-disclaimer field. `technologies`
follows the same shape/ordering rule as Experiences.

`experience_key` is `string | null`: the `key` of the linked Experience only
while that Experience is published and visible; `null` when the case is not
linked, the Experience is hidden or draft, or it was deleted (the database
link is cleared with `ON DELETE SET NULL`). Changing an Experience invalidates
both the `experiences` and `work-cases` caches.

### `GET /api/v1/{locale}/projects` — `ProjectResource` (array)

```json
{
  "data": [
    {
      "key": "string",
      "kind": "client",
      "client_name": "string",
      "title": "string",
      "role": "string",
      "status": "in_use",
      "summary": "string",
      "problem": "string",
      "solution": "string",
      "result": "string",
      "featured": false,
      "images": [{"url": "/storage/projects/uuid.webp", "alt": "string"}],
      "demo_url": null,
      "repository_url": null,
      "technologies": []
    }
  ]
}
```

| Field | Type | Source |
|---|---|---|
| `key` | `string` | `key` |
| `kind` | `"client" \| "personal"` | `kind` (`App\Enums\ProjectKind`) |
| `client_name` | `string \| null` | `client_name`; always `null` for a personal project (database check `projects_client_name_kind_check`) |
| `role` | `string` | `role_{locale}` |
| `status` | `"in_production" \| "in_use" \| "public_demo" \| "in_development"` | `delivery_status` (`App\Enums\ProjectDeliveryStatus`); this is the delivery state, never the publication state |
| `title` | `string` | `title_{locale}` |
| `summary` | `string` | `summary_{locale}` |
| `problem` | `string` | `problem_{locale}` |
| `solution` | `string` | `solution_{locale}` |
| `result` | `string` | `result_{locale}` |
| `featured` | `bool` | `featured` |
| `images` | `array<{url: string, alt: string}>` (always present, may be empty) | `project_images` rows ordered by `position` then `id`; an image is listed only when its `public_path` is non-null **and** verified to exist on the `public` disk; `alt` is `alt_{locale}`; at most 12 |
| `demo_url` | `string \| null` | `demo_url` |
| `repository_url` | `string \| null` | `repository_url` |
| `technologies` | `array<TechnologyResource>` (always present, may be empty) | same shape/ordering rule as above |

There is no `name`, `technical_description`, or `video_url` field.

### `GET /api/v1/{locale}/technologies` — `TechnologyResource` (array)

The same shape is used both top-level and nested inside Experience/WorkCase/
Project:

```json
{"key": "string", "name": "string", "category": "backend", "icon": null}
```

| Field | Type | Source |
|---|---|---|
| `key` | `string` | `key` |
| `name` | `string` | `name` (not localized — brand/technology names are not translated) |
| `category` | `"backend" \| "data" \| "integration" \| "collaboration"` | `category` (`App\Enums\TechnologyCategory`) |
| `icon` | `{url: string} \| null` | present only when `icon_public_path` is non-null and verified to exist on the `public` disk; decorative, no `alt` key |

A Technology nested inside another entity's `technologies` array independently
requires that Technology itself to be published and visible — the same
`publiclyAvailable()` scope is applied to the pivot query.

### `GET /api/v1/{locale}/site` — `SiteResource`

```json
{
  "data": {
    "projects_empty_message": "string",
    "contact_intro": "string",
    "technology_groups": [
      {"key": "backend", "label": "string"},
      {"key": "data", "label": "string"},
      {"key": "integration", "label": "string"},
      {"key": "collaboration", "label": "string"}
    ],
    "professional_links": [
      {"key": "email", "label": "string", "href": "mailto:address@example.test"}
    ],
    "expertise_areas": [
      {"key": "string", "title": "string", "description": null}
    ],
    "work_principles": [
      {"key": "string", "statement": "string"}
    ],
    "education": [
      {"key": "string", "institution": "string", "program": "string", "detail": null, "start_year": null, "end_year": 2021}
    ],
    "languages": [
      {"key": "string", "name": "string", "level": "b2"}
    ],
    "cv": {"url": "/cv/luciano-gonzalez-es.pdf", "label": "string"}
  }
}
```

| Field | Type | Source |
|---|---|---|
| `projects_empty_message` | `string` | `SiteConfiguration.projects_empty_message_{locale}` |
| `contact_intro` | `string` | `SiteConfiguration.contact_intro_{locale}` |
| `technology_groups` | `array` of exactly 4 entries, **always in this order**: `backend`, `data`, `integration`, `collaboration` | `key` is `TechnologyCategory::cases()`'s value; `label` is `SiteConfiguration.technology_{key}_label_{locale}` |
| `professional_links` | `array<object>` (always present, may be empty) | published+visible `ProfessionalLink` rows, ordered by `position` then `type`; `key` is the link `type` value (`linkedin`/`github`/`email`), `label` is `label_{locale}`, `href` is `destination` verbatim except `type = email`, where it is prefixed `mailto:` only in this Resource (the stored value never contains the prefix) |
| `expertise_areas` | `array<object>` (always present, may be empty) | published+visible `ExpertiseArea` rows, ordered by `position` then `key`; `title` from `title_{locale}` (required), `description` from `description_{locale}` (optional pair, `null` when empty) |
| `work_principles` | `array<object>` (always present, may be empty) | published+visible `WorkPrinciple` rows, ordered by `position` then `key`; `statement` from `statement_{locale}` |
| `education` | `array<object>` (always present, may be empty) | published+visible `EducationEntry` rows, ordered by `position` then `key`; `institution` is not translated; `program` from `program_{locale}` (required); `detail` from `detail_{locale}` (optional pair, `null` when empty); `start_year`/`end_year` are `int \| null` |
| `languages` | `array<object>` (always present, may be empty) | published+visible `Language` rows, ordered by `position` then `key`; `name` from `name_{locale}`; `level` is `"native" \| "a1" \| "a2" \| "b1" \| "b2" \| "c1" \| "c2"` (`App\Enums\LanguageLevel`) |
| `cv` | `{url: string, label: string} \| null` | `null` unless a `CvDocument` row for the exact requested locale is published+visible **and** its private PDF is verified to exist on the `local` disk; `url` is always the fixed route for that locale (`/cv/luciano-gonzalez-{locale}.pdf`), never a generated/expiring URL; `label` is the row's plain `label` column |

Site publication does not require any child collection or a CV row to be
present — each collection and the CV slot is independently optional. A missing
or unpublished `SiteConfiguration` singleton row returns the `not_found`
error.

## Technology group canonical order

`SiteResource` iterates `App\Enums\TechnologyCategory::cases()` directly, so
the `technology_groups` array is always emitted in this exact declaration
order:

```text
backend, data, integration, collaboration
```

## Public collection ordering

Every top-level published collection (`experiences`, `work-cases`, `projects`,
`technologies`) is ordered by its own `position` column ascending, then by
`key` ascending as a deterministic tie-break (`ProfessionalLink`, which has no
`key` column, ties on `type`). Nested `technologies` arrays are ordered by the
pivot's `position` column ascending, then by `technologies.key` ascending.
Gaps and ties in `position` are valid and are never renumbered by the API.

## What is never exposed

No Resource in this contract ever emits: database IDs, `position`, pivot table
columns, private or public storage paths, timestamps (`created_at`,
`updated_at`, `published_at`), the publication `status`, `is_visible`,
`key_locked`, a `_es`/`_en`-suffixed field name, a work-case confidentiality
note, `video_url`, or any SEO/Open Graph field. None of these exist in any
Phase 4 Resource's `toArray()`.

## CV routes

`App\Http\Controllers\CvDownloadController` (registered outside `/api`, in
`api/routes/web.php`) looks up the `CvDocument` row for the route's fixed
locale through the same `publiclyAvailable()` scope (published + visible) on
every request — there is no dependency on the cached `site` response, so
hiding, drafting, or deleting a CV takes effect immediately for this route.

The route returns a plain, sanitized web 404 (`abort_unless(..., 404)`, no API
JSON envelope, since these routes are outside `/api`) when any of the
following holds:

- no `CvDocument` row exists for that locale;
- the row is not published and visible;
- the row's `private_path` does not actually exist on the `local` disk.

On success it streams the private original via
`Storage::disk('local')->download()` with:

- `Content-Type: application/pdf`
- `X-Content-Type-Options: nosniff`
- `Cache-Control: private, no-store`
- a safe, stable download filename: `luciano-gonzalez-es.pdf` or
  `luciano-gonzalez-en.pdf` (never the internal generated storage filename)

The route never accepts a user-controlled path, never serves a `/storage/*`
alias, and the file is never reachable through the `public` disk — CV files
are always private and only ever leave storage through this controller.

## Cache

`App\Support\PublicContentCache` is the sole authority for public-content
cache keys, locking, and invalidation.

Cache keys are exactly:

```text
public-content:v1:{locale}:{endpoint}
```

where `{endpoint}` is one of `profile`, `experiences`, `work-cases`,
`projects`, `technologies`, `site` (`App\Enums\PublicEndpoint`). `v1` here is
the cache representation namespace and is independent of the `/api/v1` route
version. A cache miss acquires the lock key
`public-content-rebuild:v1:{locale}:{endpoint}`, re-checks the cache inside
the lock, rebuilds from the published+visible scopes on a genuine miss, and
stores the result with `Cache::forever()`. Empty arrays are cached the same as
non-empty ones. A public request waits at most 5 seconds for that lock before
the request fails with `content_temporarily_unavailable` (503); an
administrative mutation's own lock wait bound is 10 seconds. There is no TTL
expiry, cache tag, or `Cache::flush()` involved in public content.

## Version endpoint (unchanged from Phase 3)

```text
GET /api/v1
```

```json
{"data": {"status": "ok", "version": "v1"}}
```
