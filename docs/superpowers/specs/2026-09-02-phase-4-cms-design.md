# Phase 4 — CMS and data model design

**Status:** Written specification proposed for approval

**Date:** 2026-09-02

**Branch:** `feat/phase-4-cms`

**Worktree:** `C:\Users\lucho\Desktop\portfolio-luciano\.worktrees\phase-4-cms`

## 1. Purpose and authority

Phase 4 creates a deliberately small relational CMS in Laravel and Filament so
that all relevant public portfolio content can be edited without changing React
source. Laravel remains the source of truth. The public result is exposed through
an explicit localized JSON API that Phase 5 can consume without redesigning the
content model.

This specification refines, and does not silently replace:

- `AGENTS.md`;
- `ROADMAP.md`, especially Phase 4 and the Phase 5 boundary;
- `docs/PROJECT.md`;
- `docs/ARCHITECTURE.md`;
- `docs/SERVER_ARCHITECTURE.md`;
- `docs/DEPLOYMENT.md`;
- approved Phase 1 content, source inventory, asset inventory, and
  confidentiality matrix;
- the approved Phase 2 experience design;
- the implemented and verified Phase 3 Laravel, Filament, API-envelope,
  authentication, MySQL, storage, Docker, and test foundations;
- all Phase 4 design decisions approved during brainstorming.

Where older architecture documentation lists broader future possibilities, this
specification selects only the approved Phase 4 subset. In particular, Project
video and SEO configuration are not Phase 4 fields.

## 2. Goals and acceptance contract

After implementation:

1. An authorized administrator can manage profile, site configuration,
   experiences, work cases, projects, technologies, professional links,
   expertise areas, work principles, CV slots, and owned assets in Filament.
2. Draft or hidden content never remains in a public API cache or public asset
   location.
3. Spanish and English content is clear to edit and structurally identical to
   consume.
4. Published content cannot be saved in a bilingual, relational, temporal, or
   asset-invalid state.
5. Zero projects, zero CV slots, and empty public child collections are valid.
6. Public ordering is deterministic even when positions have gaps or ties.
7. The API exposes stable public keys and localized values, never administrative
   IDs, state, database column suffixes, pivots, or filesystem paths.
8. Filesystem/DB coordination handles expected partial failures explicitly
   without pretending to provide a distributed ACID transaction.
9. Cache invalidation and rebuild locking prevent an old public representation
   from being persisted after content is withdrawn.
10. The implementation uses the existing stack and introduces no generic CMS,
    media library, translation package, Redis service, workflow engine, or
    frontend Phase 5 work.

## 3. Chosen architecture

The CMS is an explicit relational domain:

- one Eloquent model per real entity;
- typed tables and columns;
- separate `_es` and `_en` columns for translated fields;
- application enums for genuinely closed domains;
- database enums/checks/foreign keys/unique constraints aligned with durable
  invariants;
- explicit Eloquent relationships;
- Filament resources and singleton pages built directly on that domain;
- small shared services only for publication validation, asset lifecycle, and
  public cache coordination;
- thin public controllers and explicit Laravel JSON Resources.

Rejected alternatives are JSON content blobs, translation tables or packages,
generic key-value configuration, dynamic taxonomies, a generic `Media` model,
repositories, a base CMS model, opaque publication traits, GraphQL, a page
builder, revision tables, parallel draft/published copies, event sourcing,
multi-tenancy, and an editorial approval workflow.

The additional class count of the relational approach is intentional. Shared
code must represent a concrete responsibility; it must not hide the rules that
make each model understandable.

## 4. Closed domains

Application backed enums are the only application-level source for these sets:

| Enum | Values |
|---|---|
| `PublicationStatus` | `draft`, `published` |
| `SupportedLocale` | `es`, `en` |
| `ProfessionalLinkType` | `linkedin`, `github`, `email` |
| `TechnologyCategory` | `backend`, `data`, `integration`, `collaboration` |

The database uses equivalent closed `ENUM` columns. No `other`, dynamically
created category, tag, or fallback locale exists.

The canonical technology-group order is:

```text
backend -> data -> integration -> collaboration
```

## 5. Common publication contract

Every independently publishable entity contains:

- `status`, default `draft`;
- `is_visible`, default `false`;
- nullable `published_at`;
- normal `created_at` and `updated_at` timestamps.

The exact valid states are:

| State | `status` | `is_visible` | `published_at` | Public |
|---|---|---:|---|---:|
| Draft | `draft` | `false` | `null` | no |
| Published and visible | `published` | `true` | non-null | yes |
| Published and hidden | `published` | `false` | non-null | no |

Every publishable table has a database `CHECK` equivalent to:

```sql
(
  status = 'draft'
  AND is_visible = 0
  AND published_at IS NULL
)
OR
(
  status = 'published'
  AND published_at IS NOT NULL
)
```

This check protects the state invariant; it is not a substitute for domain
validation or asset operations.

Publication behavior is:

- Publish performs `draft -> published + hidden`, runs all strong rules, and
  sets a new `published_at`; exposing it is the separate Show action;
- a published record may be visible or hidden;
- hide retains `published_at`;
- show performs `published + hidden -> published + visible`, reruns every strong
  publication rule, and publishes any owned image copy;
- `published -> draft` warns the administrator, closes public asset access,
  forces `is_visible=false`, and clears `published_at`;
- a later publication establishes a new timestamp, so `published_at` describes
  the current/latest publication rather than historical first publication;
- every save while `status=published`, including a hidden record, must continue
  to satisfy all strong publication rules;
- an incomplete edit requires first returning the one existing record to draft.

There is one version per entity. There are no revisions, snapshots, approval
states, draft copies, `published_version_id`, or version history.

All public scopes require both:

```sql
status = 'published' AND is_visible = 1
```

## 6. Bilingual integrity

Translated data uses explicit pairs such as `title_es` and `title_en`. Drafts
may contain incomplete translated content while being edited. Publication and
every subsequent published save enforce:

- a required translated field is nonblank in both locales;
- an optional translated field is either nonblank in both locales or blank in
  both locales;
- one-sided translated values are invalid;
- whitespace-only strings count as blank;
- asset alt-text pairs follow the same rule and any asset that requires alt text
  cannot be published without both values.

The publication validator owns these cross-field rules. Database constraints
remain focused on durable local invariants that MySQL can express clearly; they
do not attempt to reproduce aggregate validation involving child rows, pivots,
or filesystem existence.

Filament presents translated fields in parallel ES/EN tabs. There is no hidden
translation abstraction behind those tabs.

## 7. Stable public keys and ordering

`Experience`, `WorkCase`, `Project`, `Technology`, `ExpertiseArea`, and
`WorkPrinciple` have a required, nontranslated public key. It is exposed as
`key`, never as a route parameter in Phase 4.

Keys:

- are nonempty ASCII slugs matching `^[a-z0-9]+(-[a-z0-9]+)*$`;
- are unique inside their own table through a database `UNIQUE` constraint;
- never regenerate when a title changes;
- are normally editable before first publication;
- require a deliberate warned action after first publication;
- have no alias or history table.

Because `published_at` is deliberately cleared on return to draft, keyed models
also contain an internal `key_locked BOOLEAN NOT NULL DEFAULT false`. The first
successful transition to `published` sets it to true and it never returns to
false. It is not publication state or revision history; it only preserves the
approved rule that an identity which has once been public cannot later become an
ordinary editorial field. It is never serialized publicly.

Each keyed table also checks that `status=published` implies `key_locked=true`.
The application never exposes an action that clears this marker.

Ordered top-level collections contain `position`, an unsigned/nonnegative
integer with no unique constraint. Filament attempts to make positions
consecutive, but gaps and ties are valid database states.

Public order is always:

- keyed entities: `position`, then `key`;
- `ProfessionalLink`: `position`, then `type`;
- experience highlights: `position`, then child `id` as an internal stable
  tie-breaker;
- contextual technologies: `pivot.position`, then `Technology.key`.

No public response exposes `position` or the highlight ID; array order carries
the presentation order.

## 8. Relational schema

All primary keys are internal unsigned bigint autoincrements. All content tables
use normal timestamps and no soft deletes. Translated columns are nullable at the
database layer so an empty draft can be saved. The strong validator determines
what is required for publication.

Structural type policy is explicit:

- public keys are ASCII `VARCHAR(100)`;
- singleton keys are ASCII `VARCHAR(32)`;
- names, titles, labels, roles, CTA text, MIME values, and categories use bounded
  strings/enums appropriate to a maximum of 255 characters;
- email is limited to 254 characters and URL values to 2,048 characters;
- narrative, summary, introduction, problem, solution, approach, outcome, and
  statement columns use `TEXT` with application maximum-length validation;
- alt text is a bounded string with a 500-character application maximum;
- generated storage paths use bounded 512-character strings;
- byte sizes use unsigned bigint;
- `position` uses unsigned integer;
- years use unsigned small integer plus the stated range checks, and months use
  unsigned tiny integer plus `1..12` checks.

An empty singleton draft requires nullable editorial values such as Profile
`name`. In collection models, keys and structural closed-domain values are
non-null even in draft; fields described as required *for publication* may be
null until publication. ProfessionalLink destination and Experience start date
are explicit always-required exceptions already mandated by their contracts.

Owned-asset column groups have local database checks: private path, MIME, and
size are either all null or all non-null; a public path implies a complete
private group and `published + visible`. Filesystem existence and alt-text
completeness remain action/publication-validator responsibilities.

### 8.1 `profiles`

`Profile` is the single identity/presentation record.

| Column group | Columns |
|---|---|
| Singleton | `singleton_key='default'` |
| Identity | `name` |
| Required bilingual content for publication | `headline_es/en`, `short_summary_es/en`, `introduction_es/en`, `availability_es/en`, `cta_es/en` |
| Optional owned photo | `photo_private_path`, `photo_public_path`, `photo_mime`, `photo_size` |
| Photo accessibility | `photo_alt_es`, `photo_alt_en` |
| Editorial | common publication columns |

The photo is optional. If present on a published record, the private original,
validated technical metadata, and both alt texts are required. `location` and CV
do not belong to Profile.

### 8.2 `site_configurations`

`SiteConfiguration` is the single typed collection of site-level text that is
required before Phase 5, not a generic setting store.

| Column group | Columns |
|---|---|
| Singleton | `singleton_key='default'` |
| Required bilingual site text | `projects_empty_message_es/en`, `contact_intro_es/en` |
| Required bilingual technology labels | `technology_backend_label_es/en`, `technology_data_label_es/en`, `technology_integration_label_es/en`, `technology_collaboration_label_es/en` |
| Editorial | common publication columns |

SEO, Open Graph, analytics, arbitrary keys, section-builder JSON, and CV paths do
not belong to this table. SEO remains Phase 8.

### 8.3 Real singleton guarantee

Both singleton tables use:

```text
singleton_key NOT NULL DEFAULT 'default'
UNIQUE(singleton_key)
CHECK(singleton_key = 'default')
```

The migration inserts one empty draft `default` row into each table using the
Query Builder/DB facade only. Migrations never invoke Eloquent models, observers,
Filament, or domain actions. Production deployment therefore needs migrations,
not the development content seeder, to obtain the structural singleton slots.

As defense against exceptional manual deletion, singleton access uses a small
idempotent `insertOrIgnore`/ensure operation before resolving `default`. This is
not a second creation strategy and cannot create a different logical key.
Filament offers edit only: no create, duplicate, or delete.

### 8.4 `experiences`

| Column group | Columns |
|---|---|
| Identity/order | `key`, `key_locked`, `position` |
| Required bilingual content for publication | `organization_label_es/en`, `role_es/en`, `summary_es/en` |
| Monthly dates | `start_year`, `start_month`, `end_year`, `end_month` |
| Editorial | common publication columns |

`start_year` and `start_month` are always required. Start/end years use the
four-digit `1000..9999` range needed by the `YYYY-MM` contract; months use
`1..12`. End year/month are both null or both present. If present, the combined
end year/month is equal to or later than the start. Current experience means
only that the end pair is null; there is no persisted `is_current`.

### 8.5 `experience_highlights`

Highlights are owned children, not independently publishable content:

- `experience_id` FK;
- `content_es`, `content_en`;
- nonnegative `position`;
- timestamps, retained because they provide useful administrative traceability
  for nested edits.

They have no key, status, visibility, or `published_at`. Deleting the parent
cascades. Every existing highlight of a published experience must be complete in
both languages. Highlights are managed only within the Experience aggregate.

### 8.6 `work_cases`

| Column group | Columns |
|---|---|
| Identity/order | `key`, `key_locked`, `position` |
| Required bilingual content for publication | `title_es/en`, `context_es/en`, `problem_es/en`, `contribution_es/en`, `technical_approach_es/en`, `outcome_es/en` |
| Editorial | common publication columns |

The model is structured evidence, not a WYSIWYG blob. It has no public
`confidentiality_note`; confidentiality is enforced by source approval,
anonymization, validation, and editorial review rather than a disclaimer field.

### 8.7 `projects`

| Column group | Columns |
|---|---|
| Identity/order | `key`, `key_locked`, `position` |
| Required bilingual content for publication | `title_es/en`, `summary_es/en`, `problem_es/en`, `solution_es/en` |
| Presentation | `featured BOOLEAN DEFAULT false` |
| Optional destinations | `demo_url`, `repository_url` |
| Optional owned image | `image_private_path`, `image_public_path`, `image_mime`, `image_size`, `image_alt_es/en` |
| Editorial | common publication columns |

`featured` is retained because the approved Phase 2 Project Dossier contract
explicitly requires future CMS featured state. It does not replace position.
Both optional destinations, when present, must be valid HTTPS URLs. Image alt
text is required in both languages when an image is present on a published
project. There is no `video_url`, individual public project route, or fake
project. Zero rows and zero public rows are valid.

### 8.8 `technologies`

| Column group | Columns |
|---|---|
| Identity/order | `key`, `key_locked`, `position` |
| Canonical content | `name`, required for publication and not translated |
| Closed category | `category` (`TechnologyCategory`) |
| Optional owned decorative icon | `icon_private_path`, `icon_public_path`, `icon_mime`, `icon_size` |
| Editorial | common publication columns |

Each technology belongs to exactly one category. There is no category model,
level, percentage, rating, tag, or translated technology name. Phase 4 icons are
decorative and have no alt field. SVG is unsupported.

### 8.9 `expertise_areas`

| Column group | Columns |
|---|---|
| Identity/order | `key`, `key_locked`, `position` |
| Required bilingual content for publication | `title_es/en` |
| Optional bilingual pair | `description_es/en` |
| Editorial | common publication columns |

### 8.10 `work_principles`

| Column group | Columns |
|---|---|
| Identity/order | `key`, `key_locked`, `position` |
| Required bilingual content for publication | `statement_es/en` |
| Editorial | common publication columns |

It deliberately has one statement rather than invented title/description
fields.

### 8.11 `professional_links`

| Column group | Columns |
|---|---|
| Public identity/order | unique `type`, `position` |
| Destination | `destination` |
| Required bilingual content for publication | `label_es/en` |
| Editorial | common publication columns |

`linkedin` and `github` accept only valid HTTPS URLs. `email` stores a valid
email address without a `mailto:` prefix. Type is both the database identity and
the public `key`. There is no `other`.

### 8.12 `cv_documents`

| Column group | Columns |
|---|---|
| Logical slot | unique `locale` (`es` or `en`) |
| Localized content | `label` |
| Private PDF | `private_path`, `mime`, `size` |
| Editorial | common publication columns |

There is at most one physical row per locale: valid physical states are zero
rows, only ES, only EN, or both. Locale is immutable after creation. Because the
row already represents one locale, it does not duplicate `label_es/en`.

A published CV requires a nonblank label, validated private PDF, and an existing
private file. Deleting it removes the file safely, invalidates `site` for both
locales, empties the slot, makes `site.cv` null, and makes its stable download
route return 404.

The currently known Spanish PDF with incorrect metadata is not represented by a
new approval field or workflow state. `PortfolioContentSeeder` does not load it;
the Spanish slot remains physically empty. If a developer must retain a manual
administrative reference, it remains an explicitly created draft/hidden record
outside the approved seed and cannot be made available merely because it exists.
A corrected and human-approved PDF is later uploaded deliberately through
Filament and then follows the ordinary publication action. The application does
not claim to infer human reapproval. Technical PDF metadata validation is added
only if the installed platform can perform it reliably without a new complex
parser dependency.

## 9. Relationships and deletion

Technology has explicit many-to-many relationships with Experience, WorkCase,
and Project through:

- `experience_technology`;
- `technology_work_case`;
- `project_technology`.

Each pivot contains only the two FKs and a nonnegative `position`. Each has a
unique constraint on its FK pair. Position is contextual and not unique.

Foreign-key behavior is:

- parent (`experience`, `work_case`, or `project`) to pivot uses `ON DELETE
  CASCADE` and deletes only its pivot rows;
- Technology to each pivot uses `ON DELETE RESTRICT` (or MySQL's equivalent
  restrictive action);
- deleting a parent never deletes a Technology;
- deleting a Technology is restricted while any pivot references it;
- Filament and the domain action explain existing relations before attempting
  deletion, while the FK remains the final database protection;
- hiding a Technology does not detach it;
- nested public serialization independently excludes any related Technology
  that is not published and visible.

Hard delete with explicit confirmation is available for Experience, WorkCase,
Project, ExpertiseArea, WorkPrinciple, ProfessionalLink, and CvDocument.
Profile and SiteConfiguration cannot be deleted. There are no soft deletes.

Owned assets and child rows are handled by the owning deletion action. A storage
or restricted-FK failure is controlled and must leave the recoverable prior
state rather than partially claiming success.

## 10. Concrete indexes

The CMS is small, so indexes exist only for a constraint or actual query:

| Tables | Index | Justified query/constraint |
|---|---|---|
| Six keyed entity tables | `UNIQUE(key)` | stable identity and deliberate key change validation |
| Ordered publishable keyed collections | `(status, is_visible, position, key)` | public filter plus deterministic order |
| `professional_links` | `UNIQUE(type)` | one closed link per type |
| `professional_links` | `(status, is_visible, position, type)` | public site child filter/order |
| `cv_documents` | `UNIQUE(locale)` | 0..1 physical row per locale and direct lookup |
| Singleton tables | `UNIQUE(singleton_key)` | one `default` slot and direct lookup |
| `experience_highlights` | `(experience_id, position, id)` | ordered aggregate load |
| Each pivot | `UNIQUE(parent_id, technology_id)` | duplicate prevention and owner-side load |

FK-supporting indexes required by MySQL are created with the foreign keys. No
individual speculative index is added for each of `status`, `is_visible`,
`position`, or `key`, and no position column is unique.

## 11. Publication validation and mutation actions

The application uses concrete actions for:

- publish;
- show;
- hide;
- return to draft;
- hard delete;
- owned-asset upload/replacement/removal;
- aggregate edits involving highlights or technology pivots;
- reorder;
- deliberate locked-key change.

A shared publication validator returns structured blocking issues and can throw
the domain validation exception used by Filament. Model-specific rule providers
remain explicit. The read-only review page consumes exactly the same issue list.

Model observers are a final defensive boundary, not a transition engine. They:

- reject impossible local state;
- reject a sensitive transition or asset-reference change made outside its
  authorized domain action;
- validate a published simple entity when its complete state is available;
- invalidate ordinary mutations after commit.

They do not silently turn `draft + visible` into another state, publish files,
withdraw files, or pretend to validate an aggregate whose child/pivot changes
have not been prepared. Experience aggregate validation occurs in its action
after proposed highlights and pivot changes are assembled and before commit.

Domain actions enter a narrowly scoped editorial-mutation context understood by
the defensive observers. This context is not authorization, workflow, or a way
to bypass validation: it identifies that required transition/lifecycle effects
are being coordinated. Application flows do not use `saveQuietly()` to evade
observers.

## 12. Owned-asset lifecycle

There is no generic `Media` model. Profile owns its photo, Project its image,
Technology its icon, and CvDocument its PDF. Paths use application-generated
UUID-like names under entity-specific namespaces and never derive directly from
the uploaded filename. References are never shared between owners.

Steady-state rules for photo/image/icon are:

- the private `local` disk contains the original while its owner exists;
- the public disk contains a copy only while the owner and asset are actually
  public;
- hidden and draft owners have no public copy and no steady-state public path;
- the API emits a URL only after `public` storage confirms that path exists;
- all URLs are relative same-origin URLs, never internal paths.

Validation limits are:

| Asset | Accepted content | Maximum |
|---|---|---:|
| Profile photo | JPEG, PNG, WebP | 5 MiB |
| Project image | JPEG, PNG, WebP | 8 MiB |
| Technology icon | PNG, WebP | 1 MiB |
| CV | real PDF content and `.pdf` extension | 5 MiB |

MIME/content inspection is authoritative; supplied extension alone is not.
SVG, executable content, S3, signed URLs, and `/storage/*` CV URLs are excluded.

### 12.1 Publish or replace a public image asset

The approved order is:

1. store the new original under a new private generated path;
2. validate content, size, owner state, bilingual alt where applicable, and the
   complete proposed entity;
3. calculate a new generated public path without creating it;
4. commit the database mutation referencing the new private and intended public
   paths;
5. create the public copy only after DB commit;
6. verify the public copy exists;
7. invalidate affected cache entries;
8. remove the previous public/private asset only after the replacement is
   confirmed.

The previous public asset remains available until the new one is confirmed. If
public-copy creation or verification fails, a compensating DB mutation restores
the former reference/editorial state, the former public asset remains, newly
created paths are removed where safe, both locales are invalidated, and Filament
receives a controlled error.

There is a deliberate short window in which DB may reference the intended new
public path before its copy exists. The serializer checks existence and never
emits a premature URL. This is preferable to exposing a file before its owner is
committed public.

For a draft or published-hidden owner, upload/replacement stops after private
validation and the committed private-reference change; no public path or copy is
created. If DB commit fails, the staged file is removed and the old reference is
retained. The former private file is removed only after the new reference is
confirmed. Removing an optional private-only asset commits the cleared reference
before deleting the file; a deletion failure restores the still-existing former
reference when safe and returns a controlled error.

### 12.2 Withdraw visibility, return to draft, or delete

For photo/image/icon content whose access is being reduced:

1. acquire the relevant public-cache mutation locks;
2. remove and verify the public copy;
3. forget affected ES and EN cache entries;
4. execute and commit the DB transition or deletion;
5. forget both locales again;
6. complete safe private cleanup for deletion, then release locks.

If DB mutation fails, the public copy is restored from the private original,
caches are forgotten again, and the previous database state remains. The first
invalidation is an intentional exception to normal after-commit invalidation: it
prioritizes closing access. The second invalidation closes the concurrent-rebuild
race.

Filesystem cleanup uses bounded retries and compensation while the old valid
reference remains recoverable. A final filesystem failure never becomes silent
success: the operation returns an administrative error and logs a generated
operation identifier. Since MySQL and filesystem are not a distributed
transaction, the implementation promises tested compensation for reachable
partial failures, not mathematically impossible atomicity. It must not delete
the only known valid original before a failed DB mutation can be restored.

### 12.3 CV exception

CV files always remain private. Stable Laravel routes are:

```text
/cv/luciano-gonzalez-es.pdf
/cv/luciano-gonzalez-en.pdf
```

The route maps its closed locale to a CvDocument row, reads current DB state on
every request, requires `published + visible`, verifies the private file and PDF
metadata, and streams it with `Content-Type: application/pdf` and a safe stable
download filename. Any unavailable condition returns a sanitized web 404. The
route is outside `/api/v1`, so its error body need not use the API JSON envelope.
It never accepts a user-controlled path and never serves a `/storage/*` alias.

CV replacement stores and validates the new private file, changes the reference,
and only then removes the previous file. Hide/draft/delete immediately changes
authorization through DB state and never depends on the cached `site` response.

## 13. Public cache and concurrency

`PublicContentCache` is the sole authority for key construction, endpoint
dependencies, `rememberForever`, locking, and invalidation. It caches only the
serializable value placed under `data`, never Eloquent models or HTTP responses.

Keys are exactly:

```text
public-content:v1:{locale}:{endpoint}
```

where endpoint is one of `profile`, `experiences`, `work-cases`, `projects`,
`technologies`, or `site`. `v1` is the cache representation namespace and may be
bumped independently of the `/api/v1` route version. Empty arrays are cached.
There are no tags, TTL-based coherence, or `Cache::flush()`.

### 13.1 Actual Phase 3 stores

Repository reality is:

- the development API service in `compose.yaml` sets `CACHE_STORE=file`;
- `api/.env.example` and Laravel config default to `database`;
- the disposable automated test service sets `CACHE_STORE=array`;
- the standard cache and cache-lock migration already exists.

Laravel 13 `FileStore` and `DatabaseStore` both implement `LockProvider`. The
file store coordinates Apache workers sharing the one API container filesystem;
the database store coordinates through MySQL. No Redis is necessary.

Runtime cache for public content must implement Laravel's `LockProvider`. The
service checks this capability and fails explicitly rather than weakening the
contract. A production handoff may retain `file` for a single API container with
shared local cache paths or use the already configured `database` store; it must
not select a non-locking store. `array` is acceptable for ordinary single-process
tests, but lock/concurrency integration tests explicitly override to `file` or
`database`.

### 13.2 Miss/rebuild lock

A miss acquires:

```text
public-content-rebuild:v1:{locale}:{endpoint}
```

It then checks the cache again inside the lock, builds only from published and
visible scopes on a second miss, stores forever, and releases. Cache hits do not
take locks. The initial constants are a 60-second lease, a maximum five-second
public wait, and a maximum ten-second administrative mutation wait. A timeout
returns a controlled error (`content_temporarily_unavailable` with HTTP 503 for
the public API); it never falls back to an unfiltered or stale database
representation. These are safety bounds, not a TTL for public content.

### 13.3 Mutation lock and race closure

A visibility-reducing mutation acquires the same rebuild locks for every
affected endpoint/locale before withdrawing the asset or executing the first
forget. Multiple locks are always acquired in lexicographically sorted key order
and released in reverse order to prevent deadlocks.

Keeping the locks through withdrawal, first forget, DB commit, and second forget
prevents this race:

```text
A forgets
B starts rebuilding old DB state
A commits and forgets again
B writes the old value late
```

Request B cannot begin the rebuild while A owns the key lock. Normal hits remain
lock-free. A response already read before withdrawal cannot be recalled, but no
old representation remains persistently cacheable after the transition.

### 13.4 Dependency map

Every invalidation affects ES and EN:

| Mutation | Endpoints |
|---|---|
| Profile or photo | `profile` |
| SiteConfiguration | `site` |
| ProfessionalLink | `site` |
| ExpertiseArea | `site` |
| WorkPrinciple | `site` |
| CvDocument or PDF | `site` |
| Experience, highlight, or pivot | `experiences` |
| WorkCase or pivot | `work-cases` |
| Project, image, or pivot | `projects` |
| Technology, icon, name, category, key, state | `technologies`, `experiences`, `work-cases`, `projects` |

Create, edit, draft edit, key change, reorder, publish, show, hide, return to
draft, relationship change, asset mutation, and delete all use this map.

Ordinary changes invalidate after commit. Pivots and reorder actions invalidate
explicitly because Eloquent relationship synchronization does not provide the
required owner-model event. Technology always invalidates all four endpoints
where its representation may appear.

A cache-forget failure after a successful DB commit is not treated as an ACID
rollback condition. The application performs bounded retry, logs safely, and
returns an appropriate administrative error. It does not reconstruct arbitrary
prior DB state solely because the cache backend failed.

Cache forget is idempotent: an already absent key counts as success. A falsey
store result is treated as a backend failure only when a follow-up check still
finds the key; the service must not report an ordinary cold-cache absence as an
error.

## 14. Filament administration

The existing Phase 3 `users.is_admin` and `canAccessPanel()` boundary remains the
only administrative role model. There is no registration, team, tenant,
permission package, or approval chain.

Navigation groups are presentation only:

- **Profile and site:** Profile, SiteConfiguration, ProfessionalLink,
  ExpertiseArea, WorkPrinciple, CvDocument;
- **Career:** Experience, WorkCase, Project;
- **Technologies:** Technology.

No corresponding generic CMS-section domain abstraction is created.

### 14.1 Forms and actions

- Profile and SiteConfiguration use dedicated edit pages for `default`.
- Publish, Show, Hide, Return to draft, Delete, asset replacement, and locked-key
  change are explicit actions.
- Publish creates the validated published-hidden state; Show is the only action
  that makes that record publicly visible.
- Status, visibility, and timestamp may be shown read-only; a normal form field
  cannot bypass actions.
- A published/visible edit warns that a valid save has immediate public effect.
- Return to draft warns that it removes the record from the portfolio.
- ES and EN fields use matching tabs; key, dates, position, category, URLs,
  relationships, state, and technical asset controls remain outside.
- Cross-locale errors identify both sides of a failed pair.
- Upload widgets stage files privately and delegate final reference changes to
  the asset action; normal `save()` never writes a new final asset path directly.
- Lists provide status/visibility badges, filters, and deterministic display.
- Reorder uses Filament drag-and-drop where appropriate and invalidates the
  public endpoint when content is public.

### 14.2 Aggregate and relationship UX

ExperienceHighlight is edited as an owned ordered part of Experience, not a
separate Resource. Highlights and technology pivots are prepared and validated
with the parent in one aggregate action. WorkCase and Project similarly manage
their ordered contextual technologies without duplicate associations.

Technology shows every Experience, WorkCase, and Project relation before delete.
The UI blocks a related delete and the database FK independently restricts it.

CvDocument creation offers only missing locales and disappears when both slots
exist. Locale becomes read-only after creation. The current incorrect Spanish
PDF is absent rather than represented by an invented approval checkbox.

Locked keys are edited only through a warned action that explains frontend
identity impact, validates format/uniqueness, and invalidates both locales.

### 14.3 Read-only prepublication review

Every manageable entity has an administrative read-only review view containing:

- Spanish and English content together;
- publication state, visibility, and relevant dates;
- order and relationships;
- contextual technologies in order;
- owned assets and CV information where relevant;
- the concrete blocking issues returned by the publication validator.

For SiteConfiguration, ProfessionalLink, ExpertiseArea, WorkPrinciple, and
CvDocument are informative dependencies. Empty collections or no CV do not block
site publication. The review has no public URL, token, or visual-fidelity promise
for Phase 5.

## 15. Localized public API

Phase 3's root `GET /api/v1` remains unchanged. Phase 4 adds:

```text
GET /api/v1/{locale}/profile
GET /api/v1/{locale}/experiences
GET /api/v1/{locale}/work-cases
GET /api/v1/{locale}/projects
GET /api/v1/{locale}/technologies
GET /api/v1/{locale}/site
```

Only `es` and `en` are accepted. The route must enter a locale-validation
middleware/controller edge that can produce the existing API error envelope; a
route regex must not discard `fr` into an uncontrolled framework response. The
middleware rejects unsupported locale before content queries or Resource
construction and never falls back to another locale.

There is no `?locale=`, `Accept-Language`, implicit detection, item-by-key route,
pagination, or public mutation endpoint.

Success and error remain mutually exclusive:

```json
{"data": {}}
```

```json
{
  "error": {
    "code": "not_found",
    "message": "Resource not found.",
    "details": {}
  }
}
```

Unsupported locale is a controlled 404 with `code=unsupported_locale`.
Unavailable Profile or SiteConfiguration is a controlled 404 with
`code=not_found`. Collections return HTTP 200 with `data: []`. API errors contain
no HTTP-status duplicate, stack, SQL, or internal path.

Controllers validate locale, call `PublicContentCache`, query explicit public
scopes with filtered/ordered eager relations on a miss, transform through JSON
Resources, and return the envelope. Resources select `_es` or `_en` explicitly;
database suffixes never enter public keys.

## 16. Exact JSON Resource contract

Nullable optional fields remain present as `null`. Collections remain arrays.
The ES and EN structures are identical; only localized values differ.

### 16.1 Profile

DB-to-API mapping is one-to-one:

| API | Database source |
|---|---|
| `name` | `name` |
| `headline` | `headline_{locale}` |
| `short_summary` | `short_summary_{locale}` |
| `introduction` | `introduction_{locale}` |
| `availability` | `availability_{locale}` |
| `cta` | `cta_{locale}` |
| `photo.url` | verified `photo_public_path` through public storage URL generation |
| `photo.alt` | `photo_alt_{locale}` |

```json
{
  "data": {
    "name": "Luciano González",
    "headline": "Backend Developer | PHP & Laravel",
    "short_summary": "...",
    "introduction": "...",
    "availability": "...",
    "cta": "...",
    "photo": null
  }
}
```

Photo is null when no verified public copy exists. There is no `location`.

### 16.2 Experiences

```json
{
  "data": [
    {
      "key": "backend-development",
      "organization": "Contexto profesional anonimizado",
      "role": "Backend Developer",
      "start": "2024-03",
      "end": null,
      "summary": "...",
      "highlights": ["..."],
      "technologies": [
        {
          "key": "laravel",
          "name": "Laravel",
          "category": "backend",
          "icon": null
        }
      ]
    }
  ]
}
```

`organization`, `role`, and `summary` map to their locale columns. Start/end are
normalized from monthly columns as `YYYY-MM`; end is null for a current
experience. There is no redundant `is_current`.

### 16.3 Work cases

```json
{
  "data": [
    {
      "key": "payment-integrations",
      "title": "...",
      "context": "...",
      "problem": "...",
      "contribution": "...",
      "technical_approach": "...",
      "outcome": "...",
      "technologies": []
    }
  ]
}
```

Each content key maps directly to its `{field}_{locale}` pair. There is no
public confidentiality-disclaimer field.

### 16.4 Projects

```json
{
  "data": [
    {
      "key": "project-key",
      "title": "...",
      "summary": "...",
      "problem": "...",
      "solution": "...",
      "featured": false,
      "image": null,
      "demo_url": null,
      "repository_url": null,
      "technologies": []
    }
  ]
}
```

Image, when present, is `{ "url": "...", "alt": "..." }`. There is no
`name`, `technical_description`, or `video_url` field.

### 16.5 Technologies

The same representation is used top-level and nested:

```json
{
  "key": "laravel",
  "name": "Laravel",
  "category": "backend",
  "icon": null
}
```

Icon, when present, is `{ "url": "..." }` and is decorative. Nested queries
independently require the Technology to be published and visible.

### 16.6 Site

```json
{
  "data": {
    "projects_empty_message": "...",
    "contact_intro": "...",
    "technology_groups": [
      {"key": "backend", "label": "Backend"},
      {"key": "data", "label": "Datos"},
      {"key": "integration", "label": "Integraciones"},
      {"key": "collaboration", "label": "Colaboración"}
    ],
    "professional_links": [
      {
        "key": "email",
        "label": "Correo electrónico",
        "href": "mailto:address@example.test"
      }
    ],
    "expertise_areas": [
      {
        "key": "rest-apis",
        "title": "...",
        "description": null
      }
    ],
    "work_principles": [
      {
        "key": "maintainability",
        "statement": "..."
      }
    ],
    "cv": {
      "url": "/cv/luciano-gonzalez-es.pdf",
      "label": "Descargar CV"
    }
  }
}
```

The first two fields and group labels map directly to SiteConfiguration locale
columns. Technology groups are an ordered array so the consumer does not infer
object-key order. Each aggregated collection has its own public scope and may be
empty independently. Site publication does not require any child collection or
CV.

ProfessionalLink exposes `type` as `key`, selects `label_{locale}`, and maps
email to `mailto:` only in the Resource; persisted email remains semantic. HTTPS
destinations pass through. ExpertiseArea maps its locale title/description.
WorkPrinciple maps only `statement_{locale}`.

`cv` uses only the matching locale row and is null when the slot is absent, not
public, or lacks a valid existing private PDF. The stable URL is relative to the
same origin.

## 17. Filament/API asset URL behavior

No Resource exposes private or public storage paths. Photo/image/icon URLs are
created through the configured public disk only after existence verification.
The mere presence of a file in storage does not make its owner public. A missing
verified copy yields a null asset representation and a safe operational log,
never a broken URL.

CV availability is stricter: `site.cv` is null and the download route returns
404 unless the row, state, and private PDF all pass current checks.

## 18. Seed and factory strategy

Factories produce clearly synthetic technical content for tests. They provide
explicit helpers for draft, published-visible, published-hidden, relation, and
asset scenarios. Tests decide when a fixture is published; factories are not a
substitute for publication-transition tests.

`PortfolioContentSeeder` is an explicit development/review seeder. It:

- uses only approved Phase 1 public material and the approved Phase 2 technology
  grouping;
- updates the structural Profile/SiteConfiguration `default` rows rather than
  creating competing singleton records;
- uses `updateOrCreate` for its own approved stable keys/types;
- leaves every record `draft`, hidden, and without `published_at`;
- creates no Project rows;
- creates no user, administrator, password, token, or secret;
- does not upload or copy asset files to either application disk and therefore
  cannot copy anything to public storage;
- does not load the incorrect Spanish PDF;
- creates no CvDocument slot as a side effect of content seeding;
- does not require production configuration;
- never deletes records merely because they are absent from seed input.

Approved technology grouping comes from the Phase 2 prototype:

- PHP and Laravel -> `backend`;
- MySQL -> `data`;
- REST APIs -> `integration`;
- Angular -> `collaboration`.

Structured real records may remain incomplete drafts where approved Phase 1
copy does not safely populate every field. The seeder never invents employers,
clients, dates, metrics, results, technologies, or confidential details merely
to make a record publishable.

Seeder tests run it twice and prove:

- no duplicates;
- the same logical identifiers are updated;
- all seeded content remains draft/hidden/unpublished;
- on a clean content database, the seeder creates zero projects;
- zero created users/admins;
- no public assets;
- unrelated manually created records, including a Project used by the
  non-destructive test, remain intact.

## 19. Security and confidentiality

- Public JSON routes are read-only and retain the existing `public-api` limiter
  of 60 requests per minute per IP.
- The two CV routes use one small named limiter of 30 requests per minute per IP,
  built from the same standard Laravel rate-limiting capability; no new security
  system is introduced.
- Filament remains authenticated and authorized by the Phase 3 admin boundary.
- All input is validated server-side.
- URLs use a real HTTPS scheme; email is validated and stored without `mailto:`.
- MIME/content, extension where relevant, and size are checked.
- Requests never control storage paths.
- Uploaded executable/SVG content is rejected.
- CV routing derives a closed locale, not a requested path.
- API/admin errors are sanitized.
- Logs contain entity type, operation, and a generated operation ID, but not
  binary content, credentials, email, or full private paths in normal logs.
- No source/confidential working material enters the CMS merely because it is
  locally available.
- Uncertain real content defaults to nonpublication.

The CV limiter does not attempt bot protection, authentication, or Phase 9
hardening.

## 20. Test strategy

Persistence, constraints, transactions, and real-query behavior run against the
disposable MySQL 8.4 service. SQLite is not an alternative.

### 20.1 Domain and database

Tests cover:

- every enum, check, FK, unique, and nonnegative position constraint;
- the full publication-state matrix and timestamp reset/republish behavior;
- required and optional bilingual pairs for every entity;
- monthly date range, end-pair parity, chronology, and derived current state;
- key syntax/uniqueness, first-publication locking, warned key change, and no
  title-driven regeneration;
- singleton migration rows, uniqueness, nondeletable application behavior, and
  exceptional ensure behavior;
- CV 0..1 locale slots and locale immutability;
- highlight aggregate integrity;
- pivot duplication, order, cascade, and Technology restrict behavior;
- published-hidden strong validation;
- direct sensitive-transition rejection;
- hard deletion and zero-project validity.

### 20.2 API

Feature/contract tests cover:

- every exact ES and EN shape in section 16;
- identical structural keys across locales;
- controlled unsupported locale before content queries and without fallback;
- singleton 404 and empty collection 200;
- published-and-visible filtering at top level and in nested technologies;
- deterministic primary and tie-break ordering;
- normalized monthly dates and null end;
- optional nulls and empty arrays;
- absence of internal ID, position, pivot, status, timestamp, path, and suffix
  fields;
- verified public image URLs and missing-copy null behavior;
- private CV success headers/name and every controlled 404 condition;
- public JSON and CV rate limiters.

### 20.3 Lifecycle and cache

Storage fakes and integration tests cover:

- private upload failure;
- validation failure;
- DB commit failure;
- public-copy creation/verification failure;
- public withdrawal failure;
- replacement failure;
- compensation and bounded cleanup retry;
- delete behavior;
- no orphan in each simulated recoverable path;
- preservation of the prior valid asset after failed replacement;
- every dependency invalidation in both locales;
- after-commit ordinary invalidation;
- pre/post-commit withdrawal invalidation;
- empty-array caching and cache hits;
- locale separation;
- cache-forget failure after DB commit without fictional DB rollback;
- sorted multi-lock acquisition and timeout behavior.

A dedicated concurrency test uses `file` or `database`, not `array`, and
coordinates separate execution contexts/barriers to prove that a late miss
cannot write old content after visibility reduction. Ordinary unit tests may use
`array` where interprocess semantics are irrelevant.

### 20.4 Filament

Laravel/Livewire/Filament tests, rather than brittle end-to-end browser tests
where unnecessary, cover:

- non-admin denial and admin access;
- singleton pages and missing create/duplicate/delete actions;
- list badges/filters and reorder;
- ES/EN tabs and cross-field feedback;
- publication/show/hide/draft actions and warnings;
- published-save validation;
- upload/replacement delegation to lifecycle services;
- aggregate highlights/pivots;
- duplicate relation prevention;
- related Technology deletion protection;
- CV missing-locale creation and immutable locale;
- locked-key change;
- deletion confirmation;
- read-only review output and validator issue reuse.

## 21. Documentation and implementation deliverables

Implementation produces:

- migrations and MySQL constraints/indexes;
- enums, Eloquent models, relationships, public scopes, observers, validators,
  and mutation actions;
- asset lifecycle and public cache services;
- Filament resources/pages/actions/review views;
- public routes, controllers, middleware, Resources, and CV streaming;
- factories and `PortfolioContentSeeder`;
- automated tests and Phase 4 verification evidence;
- a version-controlled exact public API contract document;
- updates to `docs/ARCHITECTURE.md`, `README.md`, and `docs/DEPLOYMENT.md`;
- `ROADMAP.md` checkboxes only after implementation and verification are truly
  complete.

`docs/DEPLOYMENT.md` documents repository runtime/handoff requirements,
including a lock-capable cache store and durable private/public media. It does
not inspect, operate, or reconfigure the external home server, final production
Compose, cloudflared, firewall, backups, or other hosted projects.

## 22. Explicitly out of scope

Phase 4 does not implement:

- the Phase 5 public frontend or complete Next.js integration;
- public preview or preview tokens;
- revisions, version history, approval workflow, or extra editorial states;
- soft deletes;
- public detail pages or routing by key;
- key aliases/history;
- Project video;
- SEO, Open Graph, analytics, or Phase 8 metadata;
- animation or Phase 6 behavior;
- S3, signed URLs, Redis introduced solely for CMS cache, queues, workers, or a
  reconciliation daemon;
- generic media/CMS/translation/taxonomy/page-builder architecture;
- public registration, multiple roles, teams, tenants, or enterprise RBAC;
- final Phase 9 hardening.

Minimal frontend changes are allowed only if required to preserve/test the Phase
3 typed API boundary. No portfolio section UI is built.

## 23. Traceability to Phase 4 roadmap

| Roadmap requirement | Specification ownership |
|---|---|
| Profile/site/experiences/cases/projects/technologies/links | Sections 8 and 9 |
| Media/CV | Sections 12 and 17 |
| Draft/published, visibility, order, bilingual fields | Sections 5–7 |
| Filament CRUD/management | Section 14 |
| Status filters, validation, reorder, delete confirmation | Section 14 |
| Preview/review | Section 14.3 |
| Public localized endpoints | Sections 15–16 |
| Published-only behavior and zero projects | Sections 5, 15, and 16 |
| Consistent JSON Resources/errors | Sections 15–16 |
| Initial cache | Section 13 |
| Demonstration seed | Section 18 |
| API contract documentation | Sections 16 and 21 |
| Tests | Section 20 |

## 24. Design completion conditions

The design is complete only when its written form has been reviewed for:

- no placeholders or undefined resource fields;
- no contradictory publication combination;
- explicit bilingual required/optional behavior;
- explicit ownership and deletion for every relation/asset;
- exact locale routing and error behavior;
- exact public shapes and DB-to-API mappings;
- cache invalidation for every dependency and concurrent miss protection;
- no unapproved real claim or automatic Spanish-CV publication;
- zero projects and empty child collections;
- no Phase 5, Phase 8, or generic CMS scope leakage;
- compatibility with Phase 3 API/auth/storage/MySQL/Docker boundaries;
- continued use of the one Phase 4 branch/worktree.

After Luciano explicitly approves this written specification, the next step is
Superpowers `writing-plans` in this same branch/worktree. No implementation may
start until the complete plan is written, self-reviewed, and the parent model is
manually switched as required by the Phase 4 execution gate.
