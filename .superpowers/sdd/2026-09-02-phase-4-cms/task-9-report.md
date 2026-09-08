# Task 9 report: shared Filament transition UI and singleton editors

## What was implemented

- `api/app/Filament/Support/EditorialActions.php` — a static factory of four
  Filament `Action`s (`publish`, `show`, `hide`, `return_to_draft`). Each
  method takes a `Closure(): Model $record` and an optional
  `Closure(Model): void $afterSuccess`, calls exactly the matching domain
  action (`PublishContent`, `ShowContent`, `HideContent`,
  `ReturnContentToDraft`) via `app(...)`, and translates the result into a
  Filament notification. `PublicationValidationException` is caught and its
  `issues()` (code + path + message per issue) are rendered in the
  notification body; any other `\Throwable` (e.g. `AssetOperationException`)
  is caught generically. `return_to_draft` uses `->requiresConfirmation()`.
  Visibility of each button is gated on the record's current
  status/is_visible so only the applicable transition(s) show. No
  validation/transition rule is reimplemented — every method's only logic is
  wiring + notification.
- `api/app/Filament/Pages/EditProfile.php` and
  `api/app/Filament/Pages/EditSiteConfiguration.php` — custom Filament 5
  pages (`extends Filament\Pages\Page implements HasForms`, `use
  InteractsWithForms`) that load/edit the `default` singleton row.
  - `mount()` runs `DB::table(...)->insertOrIgnore(['singleton_key' =>
    'default', ...])` before `firstOrFail()` — the idempotent ensure the spec
    allows, using the Query Builder exactly as the migration does. This only
    runs when an admin opens the page; no public controller was touched.
  - ES/EN fields are explicit, named per entity inside two `Tabs\Tab`
    instances ("Spanish"/"English") — no generic translation-field
    abstraction.
  - Editorial fields (status/visibility/published_at) are rendered via
    `Filament\Forms\Components\Placeholder` (a read-only `TextEntry`, not a
    `Field`), so they never appear as writable form components and can never
    round-trip through `getState()`.
  - `save()` calls `UpdateContent::__invoke($record, $attributes)` with only
    the plain editable attributes (photo upload and both alt-text fields are
    stripped from the array first). After a successful save it checks
    `status === Published && is_visible` and shows a `warning()` notification
    ("this change is immediately public") instead of a plain success one.
  - Profile's `FileUpload::make('photo')->storeFiles(false)` stages a
    `Livewire\Features\SupportFileUploads\TemporaryUploadedFile` (which
    `extends Illuminate\Http\UploadedFile`); `save()` passes it straight to
    `ReplaceOwnedAsset::__invoke($profile, $upload)`. A separate `remove_photo`
    header action calls `RemoveOwnedAsset`. Neither ever writes
    `photo_public_path`/`photo_private_path` etc. directly.
  - `name` and all other publish-only-required fields are plain, non-`required()`
    form inputs; publication-time completeness is enforced entirely by
    `PublicationValidator`/`PublishContent`/`UpdateContent`'s existing
    `assertPublishable` call.

## A design decision that needed judgment: photo alt text

`UpdateContent::PROTECTED_ATTRIBUTES` (and the mirrored list in
`EditorialMutationGuard::SENSITIVE_ATTRIBUTES`) includes `photo_alt_es` /
`photo_alt_en`, so they can never be passed through `UpdateContent`, and no
domain action (`ReplaceOwnedAsset`/`AssetLifecycleService::replace()`) writes
alt text either — that service only fills path/mime/size. There is currently
no dedicated domain action that updates alt text on an existing row. Since
the task requires the admin UI to actually be able to set alt text (and the
new `EditorialActionTest` exercises photo-alt publish-validation scenarios),
`EditProfile::save()` handles a changed alt pair with a narrowly-scoped
direct mutation wrapped in `App\Domain\Publishing\EditorialMutationContext`
(the exact primitive `AssetLifecycleService`/`UpdateContent` already use to
signal "an authorized transition is being coordinated" to
`EditorialMutationGuard`):

```php
$updated = app(EditorialMutationContext::class)->run(function () use ($updated, $altEs, $altEn): Profile {
    $updated->forceFill(['photo_alt_es' => $altEs, 'photo_alt_en' => $altEn])->save();
    return $updated->fresh();
});
```

This never touches an asset path column, reuses an existing domain-layer
primitive rather than inventing new rules, and the model observer still
revalidates on save when the record is published (`updating()` →
`assertPublishable`). I judged this preferable to silently making alt text
uneditable after the initial photo upload. Flagging this explicitly as a
call I made rather than something the brief spelled out.

## Filament 5.7 APIs confirmed by reading vendor source

All read inside the `api-test` container (named volume `api_vendor`, not
present on the host):
- `vendor/filament/filament/src/Pages/BasePage.php` — `BasePage extends
  Livewire\Component implements HasActions, HasRenderHookScopes, HasSchemas`,
  already wires `InteractsWithActions` + `InteractsWithSchemas`, so a plain
  `Page` subclass gets action/schema support for free.
- `vendor/filament/forms/src/Concerns/InteractsWithForms.php` — confirms the
  `form(Schema $schema): Schema` method convention still works in v5 (via the
  deprecated-but-functional `getForms()`/`cacheForms()` path), matching what
  the task brief described.
- `vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php` — confirms
  `$this->form` in Blade resolves via `ResolvesDynamicLivewireProperties::__get()`
  → `getSchema('form')`.
- `vendor/filament/forms/src/Components/BaseFileUpload.php:467` —
  `storeFiles(bool|Closure $condition = true): static` exists exactly as the
  brief described.
- `vendor/filament/forms/src/Components/Placeholder.php` — `class Placeholder
  extends \Filament\Infolists\Components\TextEntry` (not `Field`), which is
  why it can never appear in `Schema::getFlatFields()`/`getState()`.
- `vendor/filament/actions/src/Testing/TestsActions.php` and
  `vendor/filament/forms/src/Testing/TestsForms.php` — confirmed
  `callAction`, `assertActionExists`/`assertActionDoesNotExist`,
  `assertFormFieldExists`/`assertFormFieldDoesNotExist`, `fillForm` are all
  registered Livewire-testable macros usable from a plain `Livewire::test(...)`.
- `vendor/filament/notifications/src/Notification.php:249` —
  `Notification::assertNotified(string $title)` matches by exact title
  string, used by the tests.
- `vendor/filament/actions/src/Concerns/CanRequireConfirmation.php` —
  `requiresConfirmation()` / `isConfirmationRequired()`.

## TDD evidence

RED (`docker compose --profile test run --rm api-test php artisan test
--filter='SingletonPageTest|EditorialActionTest' --compact`), first run
before any page/action code existed: both files failed as expected on
missing classes (`App\Filament\Pages\EditProfile` / `EditSiteConfiguration`
not found). After adding the page/action code but before fixing a
`Placeholder` namespace typo, it still failed (25 failed / 7 passed) with
`Class "Filament\Schemas\Components\Placeholder" not found` — confirming the
tests actually exercise the real component tree, not just class existence.

GREEN, after fixing the `Placeholder` import to
`Filament\Forms\Components\Placeholder`:
```
Tests\Feature\Filament\EditorialActionTest ....... 10 passed
Tests\Feature\Filament\SingletonPageTest ......... 22 passed
Tests: 32 passed (185 assertions)
```

Full suite (`docker compose --profile test run --rm api-test php artisan test
--compact`): `Tests: 190 passed (1050 assertions)` — 158 pre-existing +32 new,
zero regressions or failures. (The suite logs some expected `WARNING`/`ERROR`
lines from tests that deliberately exercise compensating-failure paths in
earlier tasks; these are pre-existing, unrelated to this task, and the run
still reports 190 passed.)

Pint: `docker compose run --rm --no-deps api ./vendor/bin/pint --test`
initially flagged import ordering in the 4 new/changed files; running Pint
(no `--test`) auto-fixed them (import order + one
`fully_qualified_strict_types` normalization in the test file), and a
follow-up `--test` run reports `PASS ... 131 files`. Re-ran the focused tests
and the full suite again after the auto-format — still 32/32 and 190/190.

## Files changed

- `api/app/Filament/Support/EditorialActions.php` (new)
- `api/app/Filament/Pages/EditProfile.php` (new)
- `api/app/Filament/Pages/EditSiteConfiguration.php` (new)
- `api/resources/views/filament/pages/edit-profile.blade.php` (new)
- `api/resources/views/filament/pages/edit-site-configuration.blade.php` (new)
- `api/tests/Feature/Filament/SingletonPageTest.php` (new)
- `api/tests/Feature/Filament/EditorialActionTest.php` (new)

## Self-review findings

- No create/duplicate/delete route or action exists for either singleton —
  verified both by asserting the corresponding `App\Filament\Resources\*`
  classes don't exist and by asserting the Livewire actions
  `create`/`duplicate`/`delete` are absent from both pages.
- Confirmed (existing, unmodified) `ProfileController`/`SiteController`
  public GET behavior still 404s and creates nothing when the singleton row
  is deleted — reused the exact `DB::table(...)->delete()` +
  `assertJsonPath('error.code', 'not_found')` pattern from
  `LocalizedContentApiTest`, and added an explicit row-count assertion.
  I did not modify any public controller.
- `PublicationValidator`'s `requiredPairs()` treats every Profile/SiteConfiguration
  bilingual field as *required*, not optional — there is no `optionalPairs()`
  call for either model (confirmed by reading `PublicationValidator.php`
  directly). `EditorialActionTest` tests this actual behavior (leaving one
  locale of `technology_backend_label` blank fails publication) rather than
  a nonexistent "optional pair" rule.
- Verified `PublishContent`, `ShowContent`, `HideContent`,
  `ReturnContentToDraft` are called with no reimplemented precondition logic
  — `EditorialActions`'s `visible()` closures only gate which buttons are
  *shown*; the actual transition/validation always happens inside the real
  domain action.
- `getHeaderActions()` on both pages returns actions from `EditorialActions`
  plus (Profile only) a local `remove_photo` action that calls
  `RemoveOwnedAsset` directly, matching the brief's "separate action" wording.

## Concerns

- The photo-alt-text handling in `EditProfile::save()` (see design-decision
  section above) is my own extrapolation to fill a gap in the existing
  domain-action surface (no action updates alt text alone). It reuses
  `EditorialMutationContext` rather than adding new domain code, but it is a
  judgment call worth a second look rather than something spelled out
  verbatim in the task brief or spec.
