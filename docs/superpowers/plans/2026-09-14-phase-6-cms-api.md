# Fase 6 — CMS y API: implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the Laravel CMS and the public API v1 so the Phase 6 portfolio can show client and personal projects with galleries, experience-linked work cases, education, languages, location and work modes, all editable and scalable from Filament.

**Architecture:** Additive MySQL migrations plus the existing domain patterns: every write goes through a domain action or service inside `EditorialMutationContext`, `PublicationValidator` owns publication rules, `AssetLifecycleService` owns public/private media, and `PublicContentDependencies` decides cache invalidation. New collections (`project_images`, `education_entries`, `languages`) follow the keyed/published pattern of `expertise_areas`; the gallery is a child table managed only through `ProjectGalleryService`.

**Tech Stack:** PHP 8.5, Laravel 13, Filament 5.7, MySQL 8.4, PHPUnit, Pint, Docker Compose.

**Spec:** `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md` (sections 3, 4, 5, 9 and 12). Companion plan for the front: `docs/superpowers/plans/2026-09-14-phase-6-front-visual.md` (it consumes the API contract produced here).

## Global Constraints

- Work only in `.worktrees/phase-6-cinematic-scroll` on branch `feat/phase-6-cinematic-scroll`, against the isolated stack `-p portfolio-phase6` (`GATEWAY_PORT=8016`). Never touch the main `portfolio` stack, the VPS or production.
- Never name the site used as a design reference, nor its author, in any file, comment, doc, test, or commit.
- No secrets in git. Commits use Conventional Commits and carry no attribution lines.
- Migrations are additive and new files only; never edit an existing migration.
- Public API keys never carry `_es`/`_en` suffixes, IDs, positions, paths, timestamps, `is_visible`, `key_locked` or publication `status`. Optional fields are always present with `null`; empty collections are `[]`.
- Project delivery state is stored as `delivery_status` (the `status` column already holds the publication state) and exposed in the API as `status`.
- Screenshot limit per project: **12**. Accepted images: `image/jpeg`, `image/png`, `image/webp`, 1 byte to 8 MiB. Alt text: 500 characters, ES/EN pair required to publish.
- Cache map (spec 4.8): Project, ProjectImage → `projects`; WorkCase → `work-cases`; Experience → `experiences` + `work-cases`; ExperienceHighlight → `experiences`; EducationEntry, Language, SiteConfiguration, ProfessionalLink, ExpertiseArea, WorkPrinciple, CvDocument → `site`; Profile → `profile`; Technology → `technologies`, `experiences`, `work-cases`, `projects`.
- Content loaded for Luciano is created as **draft**, hidden, unpublished, through domain actions (never SQL).

## Commands (run from the worktree root)

| Purpose | Command |
|---|---|
| One test class | `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact --filter=<ClassOrMethod>` |
| Full API suite | `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact` |
| Format files | `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint <paths>` |
| Check format | `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint --test` |
| Repository validator | `node infra/validation/validate-repository.mjs` |

Paths inside the `api` container are relative to `api/` (for example `app/Models/Project.php`). Git paths are relative to the worktree root (for example `api/app/Models/Project.php`).

## File map

| Area | Files |
|---|---|
| Project fields | `api/database/migrations/2026_09_15_000000_add_delivery_fields_to_projects.php`, `api/app/Enums/ProjectKind.php`, `api/app/Enums/ProjectDeliveryStatus.php`, `api/app/Models/Project.php`, `api/database/factories/ProjectFactory.php` |
| Gallery | `api/database/migrations/2026_09_15_000001_create_project_images_table.php`, `api/app/Models/ProjectImage.php`, `api/database/factories/ProjectImageFactory.php`, `api/app/Domain/Assets/ProjectGalleryService.php`, `api/app/Domain/Content/Actions/SyncProjectImages.php` |
| Work case link | `api/database/migrations/2026_09_15_000002_add_experience_to_work_cases.php`, `api/app/Models/WorkCase.php`, `api/app/Models/Experience.php` |
| Education | `api/database/migrations/2026_09_15_000003_create_education_entries_table.php`, `api/app/Models/EducationEntry.php`, `api/database/factories/EducationEntryFactory.php`, `api/app/Filament/Resources/EducationEntries/**` |
| Languages | `api/database/migrations/2026_09_15_000004_create_languages_table.php`, `api/app/Enums/LanguageLevel.php`, `api/app/Models/Language.php`, `api/database/factories/LanguageFactory.php`, `api/app/Filament/Resources/Languages/**` |
| Profile | `api/database/migrations/2026_09_15_000005_add_location_to_profiles.php`, `api/app/Enums/WorkMode.php`, `api/app/Models/Profile.php`, `api/app/Filament/Pages/EditProfile.php` |
| Shared domain | `api/app/Domain/Publishing/PublicationValidator.php`, `api/app/Domain/Publishing/EditorialMutationGuard.php`, `api/app/Domain/Assets/AssetLifecycleService.php`, `api/app/Support/PublicContentDependencies.php`, `api/app/Providers/AppServiceProvider.php`, the four `PROTECTED_ATTRIBUTES` lists |
| Admin glue | `api/app/Filament/Support/ReviewLink.php`, `api/app/Filament/Pages/ReviewContent.php`, `api/app/Filament/Support/PublicationReviewPresenter.php` |
| API | `api/app/Http/Resources/Api/V1/{Project,WorkCase,Profile,Site}Resource.php`, `api/app/Http/Controllers/Api/V1/{Project,WorkCase,Site}Controller.php`, `docs/api/PUBLIC_API_V1.md` |
| Content | `api/database/seeders/PhaseSixDraftContentSeeder.php` |
| Docs | `docs/ARCHITECTURE.md`, `ROADMAP.md`, `docs/content/ASSET_INVENTORY.md` |

---

### Task 1: Project kind, client, role, delivery status and result

**Files:**
- Create: `api/database/migrations/2026_09_15_000000_add_delivery_fields_to_projects.php`
- Create: `api/app/Enums/ProjectKind.php`, `api/app/Enums/ProjectDeliveryStatus.php`
- Create: `api/tests/Feature/Database/PhaseSixSchemaTest.php`
- Modify: `api/app/Models/Project.php`, `api/database/factories/ProjectFactory.php`
- Modify: `api/app/Domain/Publishing/PublicationValidator.php` (`projectIssues`)
- Modify: `api/app/Filament/Resources/Projects/Schemas/ProjectForm.php`, `api/app/Filament/Resources/Projects/Pages/EditProject.php`, `api/app/Filament/Resources/Projects/Pages/CreateProject.php`
- Modify: `api/app/Filament/Support/PublicationReviewPresenter.php` (`project`)
- Modify: `api/app/Http/Resources/Api/V1/ProjectResource.php`, `docs/api/PUBLIC_API_V1.md`
- Modify tests: `PublicationValidatorTest`, `ProjectResourceTest`, `PublicApiContractTest`, `EditorialMutationGuardTest`, `AssetTransitionActionTest`, `WorkCaseProjectTechnologyActionTest`

**Interfaces:**
- Produces: `App\Enums\ProjectKind` (`Client = 'client'`, `Personal = 'personal'`); `App\Enums\ProjectDeliveryStatus` (`InProduction = 'in_production'`, `InUse = 'in_use'`, `PublicDemo = 'public_demo'`, `InDevelopment = 'in_development'`); `Project` attributes `kind`, `client_name`, `role_es`, `role_en`, `delivery_status`, `result_es`, `result_en`; `ProjectFactory::client(string $name = 'Synthetic Client')`.
- API `GET /projects` item gains `kind`, `client_name`, `role`, `status`, `result`.

- [ ] **Step 1: Write the failing schema test**

Create `api/tests/Feature/Database/PhaseSixSchemaTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use App\Enums\ProjectDeliveryStatus;
use App\Enums\ProjectKind;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PhaseSixSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_enums_expose_only_the_approved_values(): void
    {
        $this->assertSame(['client', 'personal'], array_column(ProjectKind::cases(), 'value'));
        $this->assertSame(['in_production', 'in_use', 'public_demo', 'in_development'], array_column(ProjectDeliveryStatus::cases(), 'value'));
    }

    public function test_projects_default_to_personal_and_only_client_projects_may_name_a_client(): void
    {
        DB::table('projects')->insert($this->projectRow('default-kind'));
        $this->assertDatabaseHas('projects', ['key' => 'default-kind', 'kind' => 'personal', 'client_name' => null]);

        DB::table('projects')->insert([...$this->projectRow('client-project'), 'kind' => 'client', 'client_name' => 'Synthetic Client']);
        $this->assertDatabaseHas('projects', ['key' => 'client-project', 'client_name' => 'Synthetic Client']);

        $this->assertDatabaseRejects(fn () => DB::table('projects')->insert([
            ...$this->projectRow('personal-with-client'),
            'kind' => 'personal',
            'client_name' => 'Synthetic Client',
        ]));
        $this->assertDatabaseRejects(fn () => DB::table('projects')->insert([
            ...$this->projectRow('unknown-delivery'),
            'delivery_status' => 'archived',
        ]));
    }

    /** @param callable(): mixed $operation */
    private function assertDatabaseRejects(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected the database constraint to reject the operation.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    /** @return array<string, int|bool|string|null> */
    private function projectRow(string $key): array
    {
        return [
            'key' => $key,
            'key_locked' => false,
            'position' => 0,
            'status' => 'draft',
            'is_visible' => false,
            'published_at' => null,
        ];
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact --filter=PhaseSixSchemaTest`
Expected: FAIL — `Class "App\Enums\ProjectKind" not found`.

- [ ] **Step 3: Add the enums and the migration**

`api/app/Enums/ProjectKind.php`:

```php
<?php

namespace App\Enums;

enum ProjectKind: string
{
    case Client = 'client';
    case Personal = 'personal';
}
```

`api/app/Enums/ProjectDeliveryStatus.php`:

```php
<?php

namespace App\Enums;

enum ProjectDeliveryStatus: string
{
    case InProduction = 'in_production';
    case InUse = 'in_use';
    case PublicDemo = 'public_demo';
    case InDevelopment = 'in_development';
}
```

`api/database/migrations/2026_09_15_000000_add_delivery_fields_to_projects.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 project dossier fields (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.1).
 *
 * `delivery_status` is named apart from the existing publication `status`
 * column; the public API exposes it as `status`. Every new editorial column is
 * nullable so drafts can be saved incomplete; publication rules live in
 * PublicationValidator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('kind', ['client', 'personal'])->default('personal')->after('position');
            $table->string('client_name')->nullable()->after('kind');
            $table->string('role_es')->nullable()->after('title_en');
            $table->string('role_en')->nullable()->after('role_es');
            $table->enum('delivery_status', ['in_production', 'in_use', 'public_demo', 'in_development'])->nullable()->after('role_en');
            $table->text('result_es')->nullable()->after('solution_en');
            $table->text('result_en')->nullable()->after('result_es');
        });

        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_client_name_kind_check CHECK (kind = 'client' OR client_name IS NULL)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE projects DROP CHECK projects_client_name_kind_check');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['kind', 'client_name', 'role_es', 'role_en', 'delivery_status', 'result_es', 'result_en']);
        });
    }
};
```

- [ ] **Step 4: Run the schema test to verify it passes**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact --filter=PhaseSixSchemaTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Write the failing validator test**

Append to `api/tests/Feature/Domain/PublicationValidatorTest.php` (before the closing brace) and add `use App\Enums\ProjectKind;` to the imports:

```php
    public function test_it_requires_project_role_result_delivery_status_and_a_coherent_client(): void
    {
        $incomplete = Project::factory()->publishedHidden()->make([
            'role_en' => null,
            'result_es' => ' ',
            'delivery_status' => null,
            'kind' => ProjectKind::Client,
            'client_name' => null,
        ]);
        $personalWithClient = Project::factory()->publishedHidden()->make([
            'kind' => ProjectKind::Personal,
            'client_name' => 'Synthetic Client',
        ]);
        $validClient = Project::factory()->publishedHidden()->client()->make();

        $this->assertSame([
            ['code' => 'required_translation', 'path' => 'role_en'],
            ['code' => 'required_translation', 'path' => 'result_es'],
            ['code' => 'required', 'path' => 'delivery_status'],
            ['code' => 'required', 'path' => 'client_name'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($incomplete)));
        $this->assertSame(
            [['code' => 'client_name_not_allowed', 'path' => 'client_name']],
            array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($personalWithClient)),
        );
        $this->assertSame([], app(PublicationValidator::class)->issues($validClient));
    }
```

- [ ] **Step 6: Run it to verify it fails**

Run: `... php artisan test --compact --filter=test_it_requires_project_role_result_delivery_status_and_a_coherent_client`
Expected: FAIL — `Call to undefined method Database\Factories\ProjectFactory::client()`.

- [ ] **Step 7: Update the model and the factory**

In `api/app/Models/Project.php` add the imports `use App\Enums\ProjectDeliveryStatus;` and `use App\Enums\ProjectKind;`, then replace `$fillable` and `casts()`:

```php
    protected $fillable = ['key', 'key_locked', 'position', 'kind', 'client_name', 'title_es', 'title_en', 'role_es', 'role_en', 'delivery_status', 'summary_es', 'summary_en', 'problem_es', 'problem_en', 'solution_es', 'solution_en', 'result_es', 'result_en', 'featured', 'demo_url', 'repository_url', 'image_private_path', 'image_public_path', 'image_mime', 'image_size', 'image_alt_es', 'image_alt_en', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['key_locked' => 'boolean', 'position' => 'integer', 'kind' => ProjectKind::class, 'delivery_status' => ProjectDeliveryStatus::class, 'featured' => 'boolean', 'image_size' => 'integer', 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }
```

In `api/database/factories/ProjectFactory.php` add the imports `use App\Enums\ProjectDeliveryStatus;` and `use App\Enums\ProjectKind;`, then replace `definition()` and `publishedAttributes()` and add `client()`:

```php
    public function definition(): array
    {
        return ['key' => 'synthetic-project-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'kind' => ProjectKind::Personal, 'client_name' => null, 'title_es' => null, 'title_en' => null, 'role_es' => null, 'role_en' => null, 'delivery_status' => null, 'summary_es' => null, 'summary_en' => null, 'problem_es' => null, 'problem_en' => null, 'solution_es' => null, 'solution_en' => null, 'result_es' => null, 'result_en' => null, 'featured' => false, 'demo_url' => null, 'repository_url' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
    }

    public function client(string $name = 'Synthetic Client'): static
    {
        return $this->state(['kind' => ProjectKind::Client, 'client_name' => $name]);
    }

    private function publishedAttributes(bool $visible): array
    {
        return ['key_locked' => true, 'title_es' => 'Proyecto técnico sintético', 'title_en' => 'Synthetic technical project', 'role_es' => 'Rol técnico sintético', 'role_en' => 'Synthetic technical role', 'delivery_status' => ProjectDeliveryStatus::InDevelopment, 'summary_es' => 'Resumen técnico sintético.', 'summary_en' => 'Synthetic technical summary.', 'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.', 'solution_es' => 'Solución técnica sintética.', 'solution_en' => 'Synthetic technical solution.', 'result_es' => 'Resultado técnico sintético.', 'result_en' => 'Synthetic technical result.', 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
```

- [ ] **Step 8: Update the validator**

In `api/app/Domain/Publishing/PublicationValidator.php` add `use App\Enums\ProjectKind;` and replace `projectIssues()`; add `projectClientIssues()` right after it:

```php
    /** @return list<PublicationIssue> */
    private function projectIssues(Project $project): array
    {
        return [
            ...$this->keyIssues($project),
            ...$this->requiredPairs($project, ['title', 'summary', 'problem', 'solution', 'role', 'result']),
            ...$this->required($project, ['delivery_status']),
            ...$this->projectClientIssues($project),
            ...$this->httpsUrlIssues($project, ['demo_url', 'repository_url']),
            ...$this->imageAssetIssues($project, 'image', 8 * 1024 * 1024),
            ...$this->maxLength($project, ['client_name'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($project, ['title', 'role'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($project, ['summary', 'problem', 'solution', 'result'], self::NARRATIVE_MAX_LENGTH),
            ...$this->maxLength($project, ['image_alt_es', 'image_alt_en'], self::ALT_TEXT_MAX_LENGTH),
        ];
    }

    /** @return list<PublicationIssue> */
    private function projectClientIssues(Project $project): array
    {
        $kind = $project->kind instanceof ProjectKind ? $project->kind : ProjectKind::tryFrom((string) $project->kind);

        return match (true) {
            $kind === null => [$this->issue('required', 'kind', 'The project kind is required for publication.')],
            $kind === ProjectKind::Client && ! $this->hasValue($project->client_name) => [$this->issue('required', 'client_name', 'A client project requires the client name.')],
            $kind === ProjectKind::Personal && $this->hasValue($project->client_name) => [$this->issue('client_name_not_allowed', 'client_name', 'A personal project cannot name a client.')],
            default => [],
        };
    }
```

- [ ] **Step 9: Run the validator tests**

Run: `... php artisan test --compact --filter=PublicationValidatorTest`
Expected: the new test passes. `test_publish_transitions_a_valid_draft_to_the_hidden_published_state_and_locks_its_key` now FAILS with `required_translation` (its manual fixture lacks the new fields) — fixed in Step 10.

- [ ] **Step 10: Update manual project publication fixtures**

Add these attributes to every manually built project that is later validated for publication:

```php
'role_es' => 'Rol técnico sintético', 'role_en' => 'Synthetic technical role',
'result_es' => 'Resultado técnico sintético.', 'result_en' => 'Synthetic technical result.',
'delivery_status' => 'in_development',
```

Exact places:
- `api/tests/Feature/Domain/PublicationValidatorTest.php` — the `Project::factory()->draft()->create([...])` array in `test_publish_transitions_a_valid_draft_to_the_hidden_published_state_and_locks_its_key`.
- `api/tests/Feature/Domain/EditorialMutationGuardTest.php` — the array in `test_publish_rechecks_the_locked_state_before_performing_its_transition` and the array in `publishedProject()`.
- `api/tests/Feature/Assets/AssetTransitionActionTest.php` — the array in `projectForPublication()`.
- `api/tests/Feature/Domain/WorkCaseProjectTechnologyActionTest.php` — the array in `publishedVisibleProject()`.
- `api/tests/Feature/Filament/ProjectResourceTest.php` — replace `completeAttributes()` with:

```php
    private function completeAttributes(): array
    {
        return [
            'title_es' => 'Proyecto técnico sintético', 'title_en' => 'Synthetic technical project',
            'role_es' => 'Rol técnico sintético', 'role_en' => 'Synthetic technical role',
            'delivery_status' => 'in_development',
            'summary_es' => 'Resumen técnico sintético.', 'summary_en' => 'Synthetic technical summary.',
            'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.',
            'solution_es' => 'Solución técnica sintética.', 'solution_en' => 'Synthetic technical solution.',
            'result_es' => 'Resultado técnico sintético.', 'result_en' => 'Synthetic technical result.',
        ];
    }
```

Run: `... php artisan test --compact --filter="PublicationValidatorTest|EditorialMutationGuardTest|AssetTransitionActionTest|WorkCaseProjectTechnologyActionTest|ProjectResourceTest"`
Expected: PASS.

- [ ] **Step 11: Write the failing Filament tests**

In `api/tests/Feature/Filament/ProjectResourceTest.php` add `use App\Enums\ProjectKind;`, rename `test_all_four_bilingual_pairs_are_required_to_publish` to `test_all_bilingual_pairs_are_required_to_publish` with the field list `['title', 'role', 'summary', 'problem', 'solution', 'result']`, and add:

```php
    public function test_client_projects_require_a_client_name_to_publish(): void
    {
        $project = Project::factory()->create([...$this->completeAttributes(), 'kind' => ProjectKind::Client, 'client_name' => null]);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $project->refresh()->status);
    }

    public function test_editing_kind_client_and_delivery_fields_persists_them(): void
    {
        $project = Project::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'kind' => ProjectKind::Client->value,
                'client_name' => 'Synthetic Client',
                'delivery_status' => 'in_use',
                'role_es' => 'Backend', 'role_en' => 'Backend',
                'result_es' => 'Resultado sintético.', 'result_en' => 'Synthetic result.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $project->refresh();
        $this->assertSame(ProjectKind::Client, $project->kind);
        $this->assertSame('Synthetic Client', $project->client_name);
        $this->assertSame('in_use', $project->delivery_status->value);
        $this->assertSame('Synthetic result.', $project->result_en);
    }

    public function test_switching_a_project_to_personal_clears_its_client_name(): void
    {
        $project = Project::factory()->client()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['kind' => ProjectKind::Personal->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $project->refresh();
        $this->assertSame(ProjectKind::Personal, $project->kind);
        $this->assertNull($project->client_name);
    }
```

Run: `... php artisan test --compact --filter=ProjectResourceTest`
Expected: FAIL — the form has no `kind` field (`assertHasNoFormErrors` / attribute assertions fail).

- [ ] **Step 12: Add the form fields and normalize the client name**

In `api/app/Filament/Resources/Projects/Schemas/ProjectForm.php` add the imports `use App\Enums\ProjectDeliveryStatus;`, `use App\Enums\ProjectKind;`, `use Filament\Schemas\Components\Utilities\Get;`. Insert after the `key` field:

```php
                Select::make('kind')
                    ->label('Kind')
                    ->options([
                        ProjectKind::Client->value => 'Client project',
                        ProjectKind::Personal->value => 'Personal project',
                    ])
                    ->default(ProjectKind::Personal->value)
                    ->required()
                    ->live(),
                TextInput::make('client_name')
                    ->label('Client name')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => self::isClient($get('kind')))
                    ->helperText('Required to publish a client project. Personal projects never store a client.'),
                Select::make('delivery_status')
                    ->label('Delivery status')
                    ->options([
                        ProjectDeliveryStatus::InProduction->value => 'In production',
                        ProjectDeliveryStatus::InUse->value => 'In use',
                        ProjectDeliveryStatus::PublicDemo->value => 'Public demo',
                        ProjectDeliveryStatus::InDevelopment->value => 'In development',
                    ])
                    ->helperText('Required to publish.'),
```

Inside the `Spanish` tab insert after `title_es`: `TextInput::make('role_es')->label('Role (ES)')->maxLength(255),` and after `solution_es`: `Textarea::make('result_es')->label('Result (ES)')->maxLength(10000),`. Inside the `English` tab insert `TextInput::make('role_en')->label('Role (EN)')->maxLength(255),` after `title_en` and `Textarea::make('result_en')->label('Result (EN)')->maxLength(10000),` after `solution_en`.

Add this method to the class:

```php
    public static function isClient(mixed $kind): bool
    {
        return $kind === ProjectKind::Client || $kind === ProjectKind::Client->value;
    }
```

In `api/app/Filament/Resources/Projects/Pages/EditProject.php` add `use App\Filament\Resources\Projects\Schemas\ProjectForm;` and, in `handleRecordUpdate()`, right after the `unset(...)` call:

```php
        // A hidden client field is not dehydrated, so switching to a personal
        // project must clear the stored client explicitly (DB check
        // projects_client_name_kind_check).
        if (! ProjectForm::isClient($data['kind'] ?? $record->kind)) {
            $data['client_name'] = null;
        }
```

In `api/app/Filament/Resources/Projects/Pages/CreateProject.php` add `use App\Filament\Resources\Projects\Schemas\ProjectForm;` and, after the existing `unset(...)`:

```php
        if (! ProjectForm::isClient($data['kind'] ?? null)) {
            $data['client_name'] = null;
        }
```

Run: `... php artisan test --compact --filter=ProjectResourceTest`
Expected: PASS.

- [ ] **Step 13: Show the new fields on the review page**

In `api/app/Filament/Support/PublicationReviewPresenter.php`, in `project()`, replace the `bilingual` and `fields` arrays with:

```php
            'bilingual' => [
                ['label' => 'Title', 'es' => $project->title_es, 'en' => $project->title_en],
                ['label' => 'Role', 'es' => $project->role_es, 'en' => $project->role_en],
                ['label' => 'Summary', 'es' => $project->summary_es, 'en' => $project->summary_en],
                ['label' => 'Problem', 'es' => $project->problem_es, 'en' => $project->problem_en],
                ['label' => 'Solution', 'es' => $project->solution_es, 'en' => $project->solution_en],
                ['label' => 'Result', 'es' => $project->result_es, 'en' => $project->result_en],
            ],
            'fields' => [
                ['label' => 'Kind', 'value' => $project->kind?->value],
                ['label' => 'Client', 'value' => $project->client_name],
                ['label' => 'Delivery status', 'value' => $project->delivery_status?->value],
                ['label' => 'Featured', 'value' => $project->featured ? 'Yes' : 'No'],
                ['label' => 'Demo URL', 'value' => $project->demo_url],
                ['label' => 'Repository URL', 'value' => $project->repository_url],
            ],
```

- [ ] **Step 14: Write the failing API contract test**

In `api/tests/Feature/Api/V1/PublicApiContractTest.php`, in `test_collection_resources_emit_exact_types_normalized_dates_and_no_editorial_data`:

1. Add to the `makePublic($project, [...])` array: `'kind' => 'client', 'client_name' => 'Synthetic Client', 'role_es' => 'Backend sintético', 'role_en' => 'Synthetic backend', 'delivery_status' => 'in_use', 'result_es' => 'Resultado técnico sintético.', 'result_en' => 'Synthetic technical result.',`
2. Replace the `/api/v1/es/projects` assertion with:

```php
        $this->getJson('/api/v1/es/projects')->assertExactJson(['data' => [[
            'key' => 'project', 'kind' => 'client', 'client_name' => 'Synthetic Client', 'title' => 'Proyecto técnico sintético',
            'role' => 'Backend sintético', 'status' => 'in_use', 'summary' => 'Resumen técnico sintético.', 'problem' => 'Problema técnico sintético.',
            'solution' => 'Solución técnica sintética.', 'result' => 'Resultado técnico sintético.', 'featured' => false, 'image' => null,
            'demo_url' => null, 'repository_url' => null,
            'technologies' => [['key' => 'laravel', 'name' => 'Laravel', 'category' => 'backend', 'icon' => null]],
        ]]]);
```

3. In `test_public_responses_recursively_exclude_internal_and_editorial_fields`, replace `'status',` in the forbidden list with `'"status":"draft"', '"status":"published"',` (projects now expose their delivery `status`).

Run: `... php artisan test --compact --filter=PublicApiContractTest`
Expected: FAIL — the response has no `kind` key.

- [ ] **Step 15: Expose the fields**

In `api/app/Http/Resources/Api/V1/ProjectResource.php` replace the returned array in `toArray()` with:

```php
        return [
            'key' => $this->key,
            'kind' => $this->kind->value,
            'client_name' => $this->client_name,
            'title' => $this->{"title_{$suffix}"},
            'role' => $this->{"role_{$suffix}"},
            'status' => $this->delivery_status?->value,
            'summary' => $this->{"summary_{$suffix}"},
            'problem' => $this->{"problem_{$suffix}"},
            'solution' => $this->{"solution_{$suffix}"},
            'result' => $this->{"result_{$suffix}"},
            'featured' => $this->featured,
            'image' => $this->image($suffix),
            'demo_url' => $this->demo_url,
            'repository_url' => $this->repository_url,
            'technologies' => $this->technologies->map(fn ($technology): array => (new TechnologyResource($technology))->resolve())->values()->all(),
        ];
```

Run: `... php artisan test --compact --filter="PublicApiContractTest|LocalizedContentApiTest"`
Expected: PASS.

- [ ] **Step 16: Document the contract**

In `docs/api/PUBLIC_API_V1.md`, section `GET /api/v1/{locale}/projects`:

Replace the JSON example object with:

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
      "image": null,
      "demo_url": null,
      "repository_url": null,
      "technologies": []
    }
  ]
}
```

Add these rows to the field table, after `key`:

```markdown
| `kind` | `"client" \| "personal"` | `kind` (`App\Enums\ProjectKind`) |
| `client_name` | `string \| null` | `client_name`; always `null` for a personal project (database check `projects_client_name_kind_check`) |
| `role` | `string` | `role_{locale}` |
| `status` | `"in_production" \| "in_use" \| "public_demo" \| "in_development"` | `delivery_status` (`App\Enums\ProjectDeliveryStatus`); this is the delivery state, never the publication state |
```

and after `solution`:

```markdown
| `result` | `string` | `result_{locale}` |
```

In "What is never exposed", replace "`status`, `is_visible`" with "the publication `status`, `is_visible`".

- [ ] **Step 17: Format, run the suite and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint app database tests`
Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact`
Expected: all tests pass.

```bash
git add api docs/api/PUBLIC_API_V1.md
git commit -m "feat(api): add project kind, client, role, delivery status and result"
```

### Task 2: Project image gallery — schema, lifecycle and API

Replaces the single project image with an ordered `project_images` table. This task keeps the suite green by porting every single-image test in the same commit; the Filament gallery editor arrives in Task 3.

**Files:**
- Create: `api/database/migrations/2026_09_15_000001_create_project_images_table.php`
- Create: `api/app/Models/ProjectImage.php`, `api/database/factories/ProjectImageFactory.php`
- Create tests: `api/tests/Feature/Database/ProjectImageMigrationTest.php`, `api/tests/Feature/Assets/ProjectGalleryLifecycleTest.php`
- Modify: `api/app/Models/Project.php`
- Modify: `api/app/Domain/Assets/AssetLifecycleService.php`
- Modify: `api/app/Domain/Publishing/PublicationValidator.php`, `api/app/Domain/Publishing/EditorialMutationGuard.php`
- Modify: `api/app/Domain/Content/Actions/UpdateContent.php`, `UpdateContentWithTechnologies.php`, `UpdateExperienceAggregate.php` (drop the `image_*` protected line)
- Modify: `api/app/Support/PublicContentDependencies.php`, `api/app/Providers/AppServiceProvider.php`
- Modify: `api/app/Filament/Resources/Projects/Schemas/ProjectForm.php`, `Pages/EditProject.php`, `Pages/CreateProject.php`
- Modify: `api/app/Filament/Support/PublicationReviewPresenter.php`
- Modify: `api/app/Http/Resources/Api/V1/ProjectResource.php`, `api/app/Http/Controllers/Api/V1/ProjectController.php`, `docs/api/PUBLIC_API_V1.md`
- Modify tests: `AssetLifecycleTest`, `AssetTransitionActionTest`, `PrivateDiskAccessTest`, `EditorialMutationGuardTest`, `PublicationValidatorTest`, `PublicationReviewPageTest`, `ProjectResourceTest`, `PhaseFourSchemaTest`, `PublicApiContractTest`, `PublicContentCacheTest`

**Interfaces:**
- Consumes: Task 1 project fields and factory states.
- Produces: `App\Models\ProjectImage` (`project_id`, `position`, `private_path`, `public_path`, `mime`, `size`, `alt_es`, `alt_en`; `project(): BelongsTo`); `Project::images(): HasMany` ordered by `position`, `id`; `AssetLifecycleService::stage(new ProjectImage, UploadedFile): StagedAsset` stores under `projects/`; `ProjectImage` mutations require an active `EditorialMutationContext`; `PublicContentDependencies::for(ProjectImage::class) === [PublicEndpoint::Projects]`; validator issue paths `images`, `images.{n}.mime`, `images.{n}.size`, `images.{n}.alt_es`, `images.{n}.alt_en`; API project item `images: list<{url: string, alt: string}>` replacing `image`.

- [ ] **Step 1: Write the failing migration test**

Create `api/tests/Feature/Database/ProjectImageMigrationTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ProjectImageMigrationTest extends TestCase
{
    use DatabaseMigrations;

    private const MIGRATION = 'database/migrations/2026_09_15_000001_create_project_images_table.php';

    public function test_it_moves_an_existing_single_project_image_to_the_first_gallery_position(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION])->assertSuccessful();

        $projectId = DB::table('projects')->insertGetId([
            'key' => 'legacy-project', 'key_locked' => false, 'position' => 0,
            'image_private_path' => 'projects/legacy.png', 'image_public_path' => null,
            'image_mime' => 'image/png', 'image_size' => 68,
            'image_alt_es' => 'Captura heredada', 'image_alt_en' => 'Legacy screenshot',
            'status' => 'draft', 'is_visible' => false, 'published_at' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('migrate', ['--path' => self::MIGRATION])->assertSuccessful();

        $this->assertDatabaseHas('project_images', [
            'project_id' => $projectId, 'position' => 0,
            'private_path' => 'projects/legacy.png', 'public_path' => null,
            'mime' => 'image/png', 'size' => 68,
            'alt_es' => 'Captura heredada', 'alt_en' => 'Legacy screenshot',
        ]);
        foreach (['image_private_path', 'image_public_path', 'image_mime', 'image_size', 'image_alt_es', 'image_alt_en'] as $column) {
            $this->assertFalse(Schema::hasColumn('projects', $column), "projects.{$column} must be dropped.");
        }
    }

    public function test_gallery_rows_cascade_with_their_project_and_reject_invalid_sizes(): void
    {
        $projectId = DB::table('projects')->insertGetId([
            'key' => 'gallery-owner', 'key_locked' => false, 'position' => 0,
            'status' => 'draft', 'is_visible' => false, 'published_at' => null,
        ]);
        $row = [
            'project_id' => $projectId, 'position' => 0, 'private_path' => 'projects/a.png',
            'public_path' => null, 'mime' => 'image/png', 'size' => 1, 'alt_es' => null, 'alt_en' => null,
        ];
        DB::table('project_images')->insert($row);

        try {
            DB::table('project_images')->insert([...$row, 'size' => 0]);
            $this->fail('A zero-byte gallery image must be rejected.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        DB::table('projects')->where('id', $projectId)->delete();
        $this->assertDatabaseCount('project_images', 0);
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `... php artisan test --compact --filter=ProjectImageMigrationTest`
Expected: FAIL — the rollback path does not exist / table `project_images` does not exist.

- [ ] **Step 3: Write the migration**

`api/database/migrations/2026_09_15_000001_create_project_images_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ordered project screenshots (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.2).
 *
 * The former single `projects.image_*` asset moves to position 0 of the new
 * gallery and its columns and checks are dropped. A gallery row always has a
 * private original; `public_path` is set only while the owning project is
 * published and visible (enforced by AssetLifecycleService and
 * ProjectGalleryService, since a CHECK cannot read the parent row).
 */
return new class extends Migration
{
    private const IMAGE_COLUMNS = ['image_private_path', 'image_public_path', 'image_mime', 'image_size', 'image_alt_es', 'image_alt_en'];

    public function up(): void
    {
        Schema::create('project_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('private_path', 512);
            $table->string('public_path', 512)->nullable();
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->string('alt_es', 500)->nullable();
            $table->string('alt_en', 500)->nullable();
            $table->timestamps();
            $table->index(['project_id', 'position', 'id']);
        });

        DB::statement('ALTER TABLE project_images ADD CONSTRAINT project_images_position_check CHECK (position >= 0)');
        DB::statement('ALTER TABLE project_images ADD CONSTRAINT project_images_size_check CHECK (size >= 1)');

        DB::table('projects')->whereNotNull('image_private_path')->orderBy('id')->each(function (object $project): void {
            DB::table('project_images')->insert([
                'project_id' => $project->id,
                'position' => 0,
                'private_path' => $project->image_private_path,
                'public_path' => $project->image_public_path,
                'mime' => $project->image_mime,
                'size' => $project->image_size,
                'alt_es' => $project->image_alt_es,
                'alt_en' => $project->image_alt_en,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DB::statement('ALTER TABLE projects DROP CHECK projects_image_private_group_check');
        DB::statement('ALTER TABLE projects DROP CHECK projects_image_public_state_check');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(self::IMAGE_COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('image_private_path', 512)->nullable()->after('repository_url');
            $table->string('image_public_path', 512)->nullable()->after('image_private_path');
            $table->string('image_mime')->nullable()->after('image_public_path');
            $table->unsignedBigInteger('image_size')->nullable()->after('image_mime');
            $table->string('image_alt_es', 500)->nullable()->after('image_size');
            $table->string('image_alt_en', 500)->nullable()->after('image_alt_es');
        });

        DB::table('project_images')->where('position', 0)->orderBy('id')->each(function (object $image): void {
            DB::table('projects')->where('id', $image->project_id)->update([
                'image_private_path' => $image->private_path,
                'image_public_path' => $image->public_path,
                'image_mime' => $image->mime,
                'image_size' => $image->size,
                'image_alt_es' => $image->alt_es,
                'image_alt_en' => $image->alt_en,
            ]);
        });

        DB::statement('ALTER TABLE projects ADD CONSTRAINT projects_image_private_group_check CHECK ((image_private_path IS NULL AND image_mime IS NULL AND image_size IS NULL) OR (image_private_path IS NOT NULL AND image_mime IS NOT NULL AND image_size IS NOT NULL))');
        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_image_public_state_check CHECK (image_public_path IS NULL OR (image_private_path IS NOT NULL AND image_mime IS NOT NULL AND image_size IS NOT NULL AND status = 'published' AND is_visible = 1))");

        Schema::dropIfExists('project_images');
    }
};
```

Run: `... php artisan test --compact --filter=ProjectImageMigrationTest`
Expected: PASS (2 tests). Other suites that still reference `image_*` fail until Step 14; that is expected mid-task.

- [ ] **Step 4: Add the model, relation and factory**

`api/app/Models/ProjectImage.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One ordered screenshot of a Project. Rows are only ever written through
 * ProjectGalleryService or AssetLifecycleService (EditorialMutationGuard
 * rejects any change outside an EditorialMutationContext).
 */
final class ProjectImage extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'position', 'private_path', 'public_path', 'mime', 'size', 'alt_es', 'alt_en'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'size' => 'integer'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
```

`api/database/factories/ProjectImageFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProjectImage> */
final class ProjectImageFactory extends Factory
{
    protected $model = ProjectImage::class;

    public function definition(): array
    {
        return ['project_id' => Project::factory(), 'position' => 0, 'private_path' => 'projects/'.Str::uuid().'.png', 'public_path' => null, 'mime' => 'image/png', 'size' => 68, 'alt_es' => 'Captura técnica sintética', 'alt_en' => 'Synthetic technical screenshot'];
    }
}
```

In `api/app/Models/Project.php`: add `use Illuminate\Database\Eloquent\Relations\HasMany;`; remove the six `image_*` entries from `$fillable` and `'image_size' => 'integer'` from `casts()`; add:

```php
    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('position')->orderBy('id');
    }
```

- [ ] **Step 5: Write the failing guard, dependency and validator tests**

In `api/tests/Feature/Domain/EditorialMutationGuardTest.php` add `use App\Models\ProjectImage;` and `use App\Models\Technology;`, then replace `test_it_rejects_direct_owned_asset_reference_changes` and add a gallery test:

```php
    public function test_it_rejects_direct_owned_asset_reference_changes(): void
    {
        $technology = Technology::factory()->draft()->create();
        $technology->forceFill([
            'icon_private_path' => 'technologies/private.webp',
            'icon_mime' => 'image/webp',
            'icon_size' => 12,
        ]);

        $this->expectException(LogicException::class);
        $technology->save();
    }

    public function test_it_rejects_project_image_changes_outside_a_domain_action(): void
    {
        $project = Project::factory()->draft()->create();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Project images must be changed through a domain action');

        ProjectImage::factory()->for($project)->create();
    }
```

In `api/tests/Feature/Cache/PublicContentCacheTest.php` add `use App\Models\ProjectImage;` and, after the `Project::class` assertion in `test_it_expands_every_model_dependency_to_the_approved_endpoints`:

```php
        $this->assertSame([PublicEndpoint::Projects], $dependencies->for(ProjectImage::class));
```

In `api/tests/Feature/Domain/PublicationValidatorTest.php` add `use App\Models\ProjectImage;`, then:

1. Replace `test_it_validates_project_destinations_and_owned_asset_metadata` with:

```php
    public function test_it_validates_project_destinations_and_every_gallery_image(): void
    {
        $project = Project::factory()->publishedHidden()->make(['demo_url' => 'http://example.test']);
        $project->setRelation('images', collect([
            ProjectImage::factory()->make(['alt_en' => null]),
            ProjectImage::factory()->make(['mime' => 'image/svg+xml', 'size' => 9 * 1024 * 1024]),
        ]));

        $this->assertSame([
            ['code' => 'invalid_https_url', 'path' => 'demo_url'],
            ['code' => 'required_translation', 'path' => 'images.0.alt_en'],
            ['code' => 'invalid_asset_mime', 'path' => 'images.1.mime'],
            ['code' => 'asset_size_exceeded', 'path' => 'images.1.size'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($project)));
    }

    public function test_it_rejects_more_than_twelve_project_images(): void
    {
        $project = Project::factory()->publishedHidden()->make();
        $project->setRelation('images', ProjectImage::factory()->count(13)->make());

        $issues = app(PublicationValidator::class)->issues($project);

        $this->assertSame('too_many_images', $issues[0]->code);
        $this->assertSame('images', $issues[0]->path);
    }
```

2. In `test_it_validates_owned_asset_metadata_for_every_asset_owner`, delete the `$project = Project::factory()->publishedHidden()->make([...]);` statement and the `...app(PublicationValidator::class)->issues($project),` line (the expected list is unchanged).

Run: `... php artisan test --compact --filter="EditorialMutationGuardTest|PublicContentCacheTest|PublicationValidatorTest"`
Expected: FAIL — no exception for the gallery row, no `ProjectImage` dependency, no `images.*` issues.

- [ ] **Step 6: Guard, dependencies, provider and protected lists**

In `api/app/Domain/Publishing/EditorialMutationGuard.php`:
- add `use App\Models\ProjectImage;`;
- delete the line `'image_private_path', 'image_public_path', 'image_mime', 'image_size', 'image_alt_es', 'image_alt_en',` from `SENSITIVE_ATTRIBUTES`;
- in `creating()` and `updating()`, right after `$this->assertHighlightMutationContext($content);`, insert:

```php
        $this->assertGalleryMutationContext($content);
        if ($content instanceof ProjectImage) {
            return;
        }
```

- in `deleting()`, after `$this->assertHighlightMutationContext($content);`, insert `$this->assertGalleryMutationContext($content);`;
- add the method:

```php
    private function assertGalleryMutationContext(Model $content): void
    {
        if ($content instanceof ProjectImage && ! $this->context->isActive()) {
            throw new \LogicException('Project images must be changed through a domain action.');
        }
    }
```

- add `ProjectImage::class` to the `isManagedContent()` list after `Project::class`.

In `api/app/Providers/AppServiceProvider.php` add `use App\Models\ProjectImage;` and `ProjectImage::class` after `Project::class` in the observed list.

In `api/app/Support/PublicContentDependencies.php` add `use App\Models\ProjectImage;` and replace `Project::class => [PublicEndpoint::Projects],` with:

```php
            Project::class,
            ProjectImage::class => [PublicEndpoint::Projects],
```

Delete the line `'image_private_path', 'image_public_path', 'image_mime', 'image_size', 'image_alt_es', 'image_alt_en',` from `PROTECTED_ATTRIBUTES` in `UpdateContent.php`, `UpdateContentWithTechnologies.php` and `UpdateExperienceAggregate.php`.

- [ ] **Step 7: Validator gallery rules**

In `api/app/Domain/Publishing/PublicationValidator.php` add `use App\Models\ProjectImage;` and `use Illuminate\Support\Collection;`, add the constants below `ALT_TEXT_MAX_LENGTH`:

```php
    /** Maximum ordered screenshots per project (spec §4.2). */
    public const PROJECT_IMAGE_LIMIT = 12;

    public const PROJECT_IMAGE_MAX_BYTES = 8 * 1024 * 1024;

    /** @var list<string> */
    public const PROJECT_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
```

In `projectIssues()` replace the two lines `...$this->imageAssetIssues($project, 'image', 8 * 1024 * 1024),` and `...$this->maxLength($project, ['image_alt_es', 'image_alt_en'], self::ALT_TEXT_MAX_LENGTH),` with a single `...$this->projectImageIssues($project),` placed right after the `httpsUrlIssues` line. Add:

```php
    /** @return list<PublicationIssue> */
    private function projectImageIssues(Project $project): array
    {
        /** @var Collection<int, ProjectImage> $images */
        $images = $project->relationLoaded('images')
            ? $project->getRelation('images')
            : ($project->exists ? $project->images()->get() : collect());

        $issues = [];
        if ($images->count() > self::PROJECT_IMAGE_LIMIT) {
            $issues[] = $this->issue('too_many_images', 'images', 'A project can have at most 12 images.');
        }

        foreach ($images->values() as $index => $image) {
            $prefix = "images.{$index}.";
            $issues = [
                ...$issues,
                ...$this->requiredPairs($image, ['alt'], $prefix),
                ...$this->maxLengthPairs($image, ['alt'], self::ALT_TEXT_MAX_LENGTH, $prefix),
            ];
            if (! in_array($image->mime, self::PROJECT_IMAGE_MIMES, true)) {
                $issues[] = $this->issue('invalid_asset_mime', "{$prefix}mime", 'The owned asset MIME type is not allowed.');
            }
            if ((int) $image->size < 1 || (int) $image->size > self::PROJECT_IMAGE_MAX_BYTES) {
                $issues[] = $this->issue('asset_size_exceeded', "{$prefix}size", 'The owned asset exceeds the allowed size.');
            }
        }

        return $issues;
    }
```

Run: `... php artisan test --compact --filter="EditorialMutationGuardTest|PublicContentCacheTest|PublicationValidatorTest"`
Expected: PASS.

- [ ] **Step 8: Write the failing gallery lifecycle test**

Create `api/tests/Feature/Assets/ProjectGalleryLifecycleTest.php`:

```php
<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\AssetOperationException;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\HideContent;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ShowContent;
use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationValidationException;
use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class ProjectGalleryLifecycleTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_show_publishes_a_verified_public_copy_of_every_gallery_image(): void
    {
        $project = $this->publishedProjectWithImages(2);

        $shown = app(ShowContent::class)($project);

        $this->assertTrue($shown->is_visible);
        $images = $shown->images()->get();
        $this->assertCount(2, $images);
        foreach ($images as $image) {
            $this->assertMatchesRegularExpression('#^projects/[0-9a-f-]+\.png$#', $image->public_path);
            Storage::disk('public')->assertExists($image->public_path);
        }
    }

    public function test_hide_withdraws_every_public_gallery_copy(): void
    {
        $shown = app(ShowContent::class)($this->publishedProjectWithImages(2));
        $publicPaths = $shown->images()->pluck('public_path')->all();

        $hidden = app(HideContent::class)($shown);

        $this->assertFalse($hidden->is_visible);
        $this->assertSame([null, null], $hidden->images()->pluck('public_path')->all());
        foreach ($publicPaths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_delete_removes_gallery_rows_public_copies_and_private_originals(): void
    {
        $shown = app(ShowContent::class)($this->publishedProjectWithImages(2));
        $images = $shown->images()->get();

        app(DeleteContent::class)($shown);

        $this->assertDatabaseCount('project_images', 0);
        foreach ($images as $image) {
            Storage::disk('local')->assertMissing($image->private_path);
            Storage::disk('public')->assertMissing($image->public_path);
        }
    }

    public function test_publication_requires_alt_text_for_every_gallery_image(): void
    {
        $project = $this->draftProjectWithImages(1);
        app(EditorialMutationContext::class)->run(fn () => $project->images()->firstOrFail()->forceFill(['alt_en' => null])->save());

        try {
            app(PublishContent::class)($project->fresh());
            $this->fail('A gallery image without alt text must block publication.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('images.0.alt_en', $exception->issues()[0]->path);
        }
    }

    public function test_a_failed_public_copy_leaves_the_project_hidden_without_public_references(): void
    {
        $project = $this->publishedProjectWithImages(1);
        $local = Storage::disk('local');
        $failingPublic = Mockery::mock();
        $failingPublic->shouldReceive('put')->once()->andReturnFalse();
        $failingPublic->shouldReceive('exists')->andReturnFalse();
        $failingPublic->shouldReceive('delete')->andReturnTrue();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($failingPublic);

        try {
            app(ShowContent::class)($project);
            $this->fail('A failed public copy must be controlled.');
        } catch (AssetOperationException) {
            $project->refresh();
            $this->assertFalse($project->is_visible);
            $this->assertSame([null], $project->images()->pluck('public_path')->all());
        }
    }

    private function publishedProjectWithImages(int $count): Project
    {
        return app(PublishContent::class)($this->draftProjectWithImages($count));
    }

    private function draftProjectWithImages(int $count): Project
    {
        $project = Project::factory()->create([
            'title_es' => 'Proyecto sintético', 'title_en' => 'Synthetic project',
            'role_es' => 'Rol sintético', 'role_en' => 'Synthetic role',
            'delivery_status' => 'in_development',
            'summary_es' => 'Resumen sintético.', 'summary_en' => 'Synthetic summary.',
            'problem_es' => 'Problema sintético.', 'problem_en' => 'Synthetic problem.',
            'solution_es' => 'Solución sintética.', 'solution_en' => 'Synthetic solution.',
            'result_es' => 'Resultado sintético.', 'result_en' => 'Synthetic result.',
        ]);

        app(EditorialMutationContext::class)->run(function () use ($project, $count): void {
            for ($position = 0; $position < $count; $position++) {
                $path = "projects/synthetic-{$position}.png";
                Storage::disk('local')->put($path, 'synthetic-png');
                ProjectImage::factory()->for($project)->create(['position' => $position, 'private_path' => $path, 'size' => 13]);
            }
        });

        return $project->fresh();
    }
}
```

Run: `... php artisan test --compact --filter=ProjectGalleryLifecycleTest`
Expected: FAIL — `show` does not copy gallery images (public paths are `null`) and `AssetLifecycleService::definition()` still references `image_*` columns.

- [ ] **Step 9: Teach AssetLifecycleService about galleries**

In `api/app/Domain/Assets/AssetLifecycleService.php`:

1. Add `use App\Models\ProjectImage;`.
2. In `definition()` replace the `Project::class => [...]` entry with:

```php
            ProjectImage::class => ['namespace' => 'projects', 'publicNamespace' => 'projects', 'private' => 'private_path', 'publicPath' => 'public_path', 'mime' => 'mime', 'size' => 'size', 'mimes' => ['image/jpeg', 'image/png', 'image/webp'], 'maximum' => 8 * 1024 * 1024, 'public' => true, 'cv' => false],
```

   `ProjectImage` is only ever passed to `stage()`; its row lifecycle belongs to `ProjectGalleryService` (Task 3) and to the Project branches below.

3. In `altColumns()` delete the `Project::class => ['es' => 'image_alt_es', 'en' => 'image_alt_en'],` entry.
4. In `show()`, replace

```php
        $definition = $this->definition($owner, false);
        if ($definition === null) {
            return $this->changeState($owner, PublicationStatus::Published, true, false);
        }
```

with

```php
        if ($owner instanceof Project) {
            return $this->showProject($owner);
        }

        $definition = $this->definition($owner, false);
        if ($definition === null) {
            return $this->changeState($owner, PublicationStatus::Published, true, false);
        }
```

5. Add `showProject()`:

```php
    /**
     * Publishes one verified public copy per gallery image, in the same
     * commit-then-copy order as a single owned asset. Any copy failure
     * withdraws the copies made so far and leaves the project hidden with no
     * public reference.
     */
    private function showProject(Project $owner): Model
    {
        $images = $owner->images()->get();
        foreach ($images as $image) {
            if (! Storage::disk('local')->exists($image->private_path)) {
                throw new AssetOperationException('The private original is unavailable.');
            }
        }
        $this->validator->assertPublishable($owner);

        if ($images->isEmpty()) {
            return $this->changeState($owner, PublicationStatus::Published, true, false);
        }

        $operationId = (string) Str::uuid();
        $paths = $images->mapWithKeys(static fn (ProjectImage $image): array => [
            $image->getKey() => 'projects/'.Str::uuid().'.'.pathinfo($image->private_path, PATHINFO_EXTENSION),
        ])->all();

        $updated = DB::transaction(function () use ($owner, $paths): Model {
            $locked = $this->locked($owner);

            return $this->context->run(function () use ($locked, $paths): Model {
                foreach ($paths as $id => $path) {
                    ProjectImage::query()->whereKey($id)->update(['public_path' => $path]);
                }
                $locked->forceFill(['is_visible' => true])->save();

                return $locked->fresh();
            });
        });

        $copied = [];
        try {
            foreach ($images as $image) {
                $this->copyPublic($image->private_path, $paths[$image->getKey()]);
                $copied[] = $paths[$image->getKey()];
            }
        } catch (\Throwable $exception) {
            foreach ($copied as $path) {
                Storage::disk('public')->delete($path);
            }
            DB::transaction(function () use ($owner): void {
                $locked = $this->locked($owner);
                $this->context->run(function () use ($locked): void {
                    ProjectImage::query()->where('project_id', $locked->getKey())->update(['public_path' => null]);
                    $locked->forceFill(['is_visible' => false])->save();
                });
            });
            $this->cache->invalidate($this->dependencies->for($owner));

            throw $this->operationFailure('show', $owner, $operationId, $exception);
        }

        $this->cache->invalidate($this->dependencies->for($owner));

        return $updated->fresh();
    }
```

6. In `reduce()`:
   - after `$old = $definition === null ? null : $this->snapshot($owner, $definition);` add `$gallery = $this->gallerySnapshot($owner);`;
   - add `$gallery` to the `use (...)` list of both outer closures;
   - right after the `if ($old !== null) { $this->deletePublic(...); }` block add:

```php
                $withdrawn = [];
                try {
                    foreach ($gallery as $image) {
                        if ($image['public'] !== null) {
                            $this->deletePublic($image['public'], $owner, $operationId, 'withdraw_gallery_public_copy');
                            $withdrawn[] = $image;
                        }
                    }
                } catch (\Throwable $exception) {
                    $this->restoreGalleryCopies($withdrawn, $owner, $operationId);

                    throw $exception;
                }
```

   - in the `catch` of the pre-commit `invalidate()` call, before `throw`, add `$this->restoreGalleryCopies($gallery, $owner, $operationId);`;
   - inside the innermost `context->run` closure (the one receiving `$locked`), add before `$locked->forceFill($attributes)->save();`:

```php
                            if ($locked instanceof Project) {
                                ProjectImage::query()->where('project_id', $locked->getKey())->whereNotNull('public_path')->update(['public_path' => null]);
                            }
```

   - in the `catch (\Throwable $exception)` that follows `DB::transaction(...)`, insert directly after the existing `if (! $committed && $old !== null ...) { ... }` block:

```php
                    if (! $committed) {
                        $this->restoreGalleryCopies($gallery, $owner, $operationId);
                    }
```

   - after the existing `if ($delete && $old !== null) {...}` block add:

```php
                if ($delete) {
                    try {
                        foreach ($gallery as $image) {
                            $this->removePrivateOrFail($image['private']);
                        }
                    } catch (\Throwable $exception) {
                        throw $this->operationFailure('delete_gallery_private_cleanup', $owner, $operationId, $exception);
                    }
                }
```

7. Add the two helpers:

```php
    /** @return list<array{private: string, public: ?string}> */
    private function gallerySnapshot(Model $owner): array
    {
        if (! $owner instanceof Project) {
            return [];
        }

        return ProjectImage::query()->where('project_id', $owner->getKey())->orderBy('position')->orderBy('id')->get()
            ->map(static fn (ProjectImage $image): array => ['private' => $image->private_path, 'public' => $image->public_path])
            ->all();
    }

    /** @param list<array{private: string, public: ?string}> $images */
    private function restoreGalleryCopies(array $images, Model $owner, string $operationId): void
    {
        foreach ($images as $image) {
            if ($image['public'] === null) {
                continue;
            }
            try {
                $this->copyPublic($image['private'], $image['public']);
            } catch (\Throwable) {
                $this->log($owner, $operationId, 'restore_gallery_public_copy_failed');
            }
        }
    }
```

Run: `... php artisan test --compact --filter=ProjectGalleryLifecycleTest`
Expected: PASS (5 tests).

- [ ] **Step 10: Remove the single-image admin surface**

`api/app/Filament/Resources/Projects/Schemas/ProjectForm.php`: delete `TextInput::make('image_alt_es')...`, `TextInput::make('image_alt_en')...`, the `FileUpload::make('image')...` field, the `Placeholder::make('image_display')...` field, and the now-unused `use Filament\Forms\Components\FileUpload;`.

`api/app/Filament/Resources/Projects/Pages/EditProject.php`:
- delete `removeImageAction()` and its entry in `getHeaderActions()`;
- delete the imports `RemoveOwnedAsset`, `ReplaceOwnedAsset`, `UpdateOwnedAssetAltText`, `Illuminate\Http\UploadedFile`;
- in the `mutateFormDataBeforeFill()` docblock delete the two sentences about the image upload field;
- replace `handleRecordUpdate()` with:

```php
    /**
     * `technologies` is never mutated directly, only through
     * `UpdateContentWithTechnologies`, which replaces the pivot wholesale
     * and commits it together with the parent's own plain attributes in a
     * single transaction. The repeater's submitted item order becomes each
     * item's explicit `position`.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Failing loudly on a missing key, rather than defaulting to an empty
        // list, guards against a future hidden()/dehydrated() change silently
        // deleting every technology.
        if (! array_key_exists('technologies', $data)) {
            throw new \LogicException('The project form did not submit its technologies state.');
        }
        $technologiesInput = $data['technologies'];

        // Defense in depth against a tampered payload: none of these are
        // plain editable attributes on this page.
        unset(
            $data['technologies'], $data['images'],
            $data['key'], $data['key_locked'], $data['position'], $data['status'], $data['is_visible'], $data['published_at'],
        );

        // A hidden client field is not dehydrated, so switching to a personal
        // project must clear the stored client explicitly (DB check
        // projects_client_name_kind_check).
        if (! ProjectForm::isClient($data['kind'] ?? $record->kind)) {
            $data['client_name'] = null;
        }

        $technologies = [];
        foreach (array_values($technologiesInput) as $position => $technology) {
            $technologies[] = [
                'technology_id' => (int) $technology['technology_id'],
                'position' => $position,
            ];
        }

        try {
            /** @var Project $updated */
            $updated = app(UpdateContentWithTechnologies::class)($record, $data, $technologies);
        } catch (PublicationValidationException $exception) {
            $this->notifyValidationFailure('Save', $exception);

            throw new Halt;
        }

        $this->record = $updated;

        if ($updated->status === PublicationStatus::Published && $updated->is_visible) {
            Notification::make()
                ->warning()
                ->title('Saved')
                ->body('This content is published and visible: the change is immediately public.')
                ->send();
        }

        return $updated;
    }
```

`api/app/Filament/Resources/Projects/Pages/CreateProject.php`: replace `unset($data['image'], $data['image_alt_es'], $data['image_alt_en'], $data['technologies']);` with `unset($data['technologies'], $data['images']);` and in its docblock replace "The image upload, its alt text, and technologies are hidden" with "The screenshot gallery and technologies are hidden" and "all three are defensively stripped" with "both are defensively stripped".

`api/app/Filament/Support/PublicationReviewPresenter.php`: add `use App\Models\ProjectImage;`; in `project()` add after the `$technologies` line:

```php
        $images = $project->relationLoaded('images') ? $project->getRelation('images') : $project->images;
```

and replace the `assets` entry with:

```php
            'assets' => $images->isEmpty()
                ? [$this->assetInfo('Screenshots', null, null, null)]
                : $images->values()->map(fn (ProjectImage $image, int $index): array => $this->assetInfo(
                    'Screenshot '.($index + 1), $image->private_path, $image->mime, $image->size, $image->alt_es, $image->alt_en,
                ))->all(),
```

- [ ] **Step 11: Expose `images` in the API**

`api/app/Http/Resources/Api/V1/ProjectResource.php`: add `use App\Models\ProjectImage;`; replace `'image' => $this->image($suffix),` with `'images' => $this->images($suffix),` and replace the `image()` method with:

```php
    /** @return list<array{url: string, alt: string}> */
    private function images(string $suffix): array
    {
        return $this->resource->images
            ->filter(static fn (ProjectImage $image): bool => $image->public_path !== null && Storage::disk('public')->exists($image->public_path))
            ->map(static fn (ProjectImage $image): array => ['url' => '/storage/'.ltrim($image->public_path, '/'), 'alt' => $image->{"alt_{$suffix}"}])
            ->values()
            ->all();
    }
```

`api/app/Http/Controllers/Api/V1/ProjectController.php`: in the `with([...])` array add `'images',` before `'technologies' => ...`.

`docs/api/PUBLIC_API_V1.md`, projects section: in the JSON example replace `"image": null,` with `"images": [{"url": "/storage/projects/uuid.webp", "alt": "string"}],`; replace the `image` table row with:

```markdown
| `images` | `array<{url: string, alt: string}>` (always present, may be empty) | `project_images` rows ordered by `position` then `id`; an image is listed only when its `public_path` is non-null **and** verified to exist on the `public` disk; `alt` is `alt_{locale}`; at most 12 |
```

- [ ] **Step 12: Port the remaining single-image tests**

`api/tests/Feature/Assets/AssetLifecycleTest.php` — the single-owned-asset behaviour is now exercised through the Technology icon owner:
- in `test_it_rejects_invalid_real_content_and_size_for_each_owned_asset_kind` replace `[Project::factory()->create(), UploadedFile::fake()->create('vector.svg', 12, 'image/svg+xml')],` with `[new ProjectImage, UploadedFile::fake()->create('vector.svg', 12, 'image/svg+xml')],`;
- replace `use App\Models\Project;` with `use App\Models\ProjectImage;`;
- replace the four Project-based tests with:

```php
    public function test_replacing_a_hidden_asset_commits_only_the_new_private_reference_and_cleans_the_old_original(): void
    {
        $technology = Technology::factory()->create();
        $service = app(AssetLifecycleService::class);
        $service->replace($technology, $this->png('first.png'));
        $firstPath = $technology->fresh()->icon_private_path;

        $service->replace($technology->fresh(), $this->png('second.png'));
        $technology->refresh();

        $this->assertNotSame($firstPath, $technology->icon_private_path);
        $this->assertNull($technology->icon_public_path);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($technology->icon_private_path);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_private_upload_failure_leaves_no_reference_or_orphan(): void
    {
        $technology = Technology::factory()->create();
        $local = Mockery::mock();
        $local->shouldReceive('putFileAs')->once()->andReturnFalse();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);

        try {
            app(AssetLifecycleService::class)->replace($technology, $this->png('upload.png'));
            $this->fail('A private upload failure must be controlled.');
        } catch (AssetOperationException) {
            $technology->refresh();
            $this->assertNull($technology->icon_private_path);
            $this->assertNull($technology->icon_public_path);
        }
    }

    public function test_private_cleanup_failure_restores_the_old_hidden_reference(): void
    {
        $technology = Technology::factory()->create();
        $service = app(AssetLifecycleService::class);
        $service->replace($technology, $this->png('old.png'));
        $oldPath = $technology->fresh()->icon_private_path;
        $local = Mockery::mock();
        $local->shouldReceive('delete')->with($oldPath)->once()->andReturnFalse();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);

        try {
            app(RemoveOwnedAsset::class)($technology->fresh());
            $this->fail('A failed private cleanup must not claim success.');
        } catch (AssetOperationException) {
            $this->assertSame($oldPath, $technology->fresh()->icon_private_path);
        }
    }

    public function test_database_failure_removes_the_staged_file_and_preserves_the_old_reference(): void
    {
        $technology = Technology::factory()->create();
        $service = app(AssetLifecycleService::class);
        $service->replace($technology, $this->png('old.png'));
        $oldPath = $technology->fresh()->icon_private_path;
        $stale = $technology->fresh();
        $stale->setAttribute('id', 999999);

        try {
            $service->replace($stale, $this->png('new.png'));
            $this->fail('A database failure must be controlled.');
        } catch (AssetOperationException) {
            $this->assertSame($oldPath, $technology->fresh()->icon_private_path);
            $this->assertSame([$oldPath], Storage::disk('local')->allFiles());
        }
    }
```

`api/tests/Feature/Assets/AssetTransitionActionTest.php` — the transition behaviour of a single public asset is exercised through a Technology icon; galleries are covered by `ProjectGalleryLifecycleTest`. Apply the renames (Git Bash, worktree root):

```bash
sed -i \
  -e 's/use App\\Models\\Project;/use App\\Models\\Technology;/' \
  -e 's/visibleProjectWithImage/visibleTechnologyWithIcon/g' \
  -e 's/projectForPublication/technologyForPublication/g' \
  -e 's/image_private_path/icon_private_path/g' \
  -e 's/image_public_path/icon_public_path/g' \
  -e "s/'projects'/'technologies'/g" \
  -e 's/Project::updated/Technology::updated/g' \
  -e 's/\$project/$technology/g' \
  -e "s/'project\.png'/'icon.png'/g" \
  api/tests/Feature/Assets/AssetTransitionActionTest.php
```

If the Bash tool refuses the command (it rejects some `$` patterns), apply the same nine renames with the Edit tool (`replace_all: true`), in the order listed. Then replace the two helpers at the bottom of that file with:

```php
    private function visibleTechnologyWithIcon(): Technology
    {
        $technology = $this->technologyForPublication();
        app(AssetLifecycleService::class)->replace($technology, $this->png('icon.png'));

        return app(ShowContent::class)(app(PublishContent::class)($technology->fresh()));
    }

    private function technologyForPublication(): Technology
    {
        return Technology::factory()->create(['name' => 'Synthetic Technical Component']);
    }
```

and rename the variable-typed docblocks if any mention "project". Confirm with `grep -n "roject" api/tests/Feature/Assets/AssetTransitionActionTest.php` that no Project reference remains.

`api/tests/Feature/PrivateDiskAccessTest.php` — replace `test_no_storage_alias_ever_serves_a_private_project_image` with:

```php
    public function test_no_storage_alias_ever_serves_a_private_project_image(): void
    {
        $path = 'projects/synthetic.webp';
        Storage::disk('local')->put($path, 'synthetic-image-original');

        $project = Project::factory()->create();
        DB::table('project_images')->insert([
            'project_id' => $project->id, 'position' => 0, 'private_path' => $path, 'public_path' => null,
            'mime' => 'image/webp', 'size' => strlen('synthetic-image-original'), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->get('/storage/'.$path);

        $response->assertNotFound();
        $response->assertDontSee($path, false);
    }
```

`api/tests/Feature/Filament/PublicationReviewPageTest.php` — add `use App\Domain\Publishing\EditorialMutationContext;` and replace `test_project_review_shows_image_asset_presence_and_metadata_without_a_raw_path` with:

```php
    public function test_project_review_shows_gallery_asset_presence_and_metadata_without_a_raw_path(): void
    {
        $project = Project::factory()->create();
        $image = app(EditorialMutationContext::class)->run(fn () => $project->images()->create([
            'position' => 0, 'private_path' => 'projects/synthetic-review.png', 'mime' => 'image/png', 'size' => 68,
            'alt_es' => 'Captura sintética', 'alt_en' => 'Synthetic screenshot',
        ]));
        $this->authenticateAdmin();

        $component = Livewire::test(ReviewContent::class, ['type' => 'project', 'record' => $project->getKey()])
            ->assertSee('Present')
            ->assertSee('image/png');

        $this->assertStringNotContainsString($image->private_path, $component->html());
    }
```

Remove the `ReplaceOwnedAsset` import from that file only if nothing else in it uses it (`grep -n ReplaceOwnedAsset` first).

`api/tests/Feature/Filament/ProjectResourceTest.php`:
- delete the sections "Optional image with alt-text-required-when-present" and "Lifecycle-controlled image upload/removal" (the six tests from `test_image_alt_text_is_not_required_for_publication_without_an_image` through `test_editing_image_alt_text_goes_through_update_owned_asset_alt_text`);
- replace `test_deleting_a_project_cascades_to_technology_pivots_and_the_owned_asset` with:

```php
    public function test_deleting_a_project_cascades_to_technology_pivots_and_gallery_originals(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $project = Project::factory()->create();
        Storage::disk('local')->put('projects/cover.png', 'synthetic-png');
        app(EditorialMutationContext::class)->run(fn () => $project->images()->create([
            'position' => 0, 'private_path' => 'projects/cover.png', 'mime' => 'image/png', 'size' => 13,
            'alt_es' => 'Portada sintética', 'alt_en' => 'Synthetic cover',
        ]));
        $technology = Technology::factory()->create();
        $project->technologies()->attach($technology, ['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, Project::query()->count());
        $this->assertSame(0, DB::table('project_images')->count());
        $this->assertSame(0, DB::table('project_technology')->count());
        $this->assertSame(1, Technology::query()->count());
        Storage::disk('local')->assertMissing('projects/cover.png');
    }
```

- replace `use App\Domain\Content\Actions\ReplaceOwnedAsset;` with `use App\Domain\Publishing\EditorialMutationContext;`.

`api/tests/Feature/Database/PhaseFourSchemaTest.php`:
- in `test_owned_asset_alt_text_columns_are_widened_to_five_hundred_characters` delete the two `['projects', ...]` rows;
- in `test_publishable_and_owned_asset_checks_reject_invalid_local_states` delete the `projects` `incomplete-image` assertion (the gallery equivalent lives in `ProjectImageMigrationTest`).

`api/tests/Feature/Api/V1/PublicApiContractTest.php`, in `test_collection_resources_emit_exact_types_normalized_dates_and_no_editorial_data`:
- delete the `'image_private_path' => ..., 'image_alt_en' => 'Image',` keys from the project `makePublic()` array;
- after `$project->technologies()->attach(...)` insert:

```php
        DB::table('project_images')->insert([
            ['project_id' => $project->id, 'position' => 1, 'private_path' => 'projects/second-private.jpg', 'public_path' => 'projects/second.jpg', 'mime' => 'image/jpeg', 'size' => 1, 'alt_es' => 'Segunda', 'alt_en' => 'Second', 'created_at' => now(), 'updated_at' => now()],
            ['project_id' => $project->id, 'position' => 0, 'private_path' => 'projects/private.jpg', 'public_path' => 'projects/image.jpg', 'mime' => 'image/jpeg', 'size' => 1, 'alt_es' => 'Imagen', 'alt_en' => 'Image', 'created_at' => now(), 'updated_at' => now()],
        ]);
```

- in the projects `assertExactJson` replace `'image' => null,` with `'images' => [],`;
- after `Storage::disk('public')->put('projects/image.jpg', 'image');` add `Storage::disk('public')->put('projects/second.jpg', 'image');` and replace the `data.0.image` assertion with:

```php
        $this->getJson('/api/v1/en/projects')->assertJsonPath('data.0.images', [
            ['url' => '/storage/projects/image.jpg', 'alt' => 'Image'],
            ['url' => '/storage/projects/second.jpg', 'alt' => 'Second'],
        ]);
```

- [ ] **Step 13: Verify no single-image reference remains**

Run: `grep -rn "image_private_path\|image_public_path\|image_alt_\|image_mime\|image_size\|remove_image" api/app api/database/factories api/tests docs/api`
Expected: matches only inside `api/database/migrations/2026_09_15_000001_create_project_images_table.php` and `api/tests/Feature/Database/ProjectImageMigrationTest.php`.

- [ ] **Step 14: Format, run the suite and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint app database tests`
Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact`
Expected: all tests pass.

```bash
git add api docs/api/PUBLIC_API_V1.md
git commit -m "feat(api)!: replace the single project image with an ordered gallery"
```

### Task 3: Gallery editing service and Filament screenshot repeater

**Files:**
- Create: `api/app/Domain/Assets/ProjectGalleryService.php`, `api/app/Domain/Content/Actions/SyncProjectImages.php`
- Create test: `api/tests/Feature/Domain/ProjectGalleryServiceTest.php`
- Modify: `api/app/Filament/Resources/Projects/Schemas/ProjectForm.php`, `api/app/Filament/Resources/Projects/Pages/EditProject.php`
- Modify test: `api/tests/Feature/Filament/ProjectResourceTest.php`

**Interfaces:**
- Consumes: `ProjectImage`, `Project::images()`, `AssetLifecycleService::stage()`, `PublicationValidator::PROJECT_IMAGE_LIMIT` (Task 2).
- Produces: `ProjectGalleryService::sync(Project $project, list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}> $items): Project` — the list order becomes `position`; an item with `id` keeps that image (replacing its file when `upload` is set); an item without `id` adds an image and requires `upload`; existing images missing from the list are removed. `SyncProjectImages::__invoke(Project, array $items): Project` delegates to it. Throws `PublicationValidationException` (issues `too_many_images`, `invalid_relation`, `asset_required`, `max_length_exceeded`, or publication issues for a published project) and `AssetOperationException` (storage failures).

- [ ] **Step 1: Write the failing service test**

Create `api/tests/Feature/Domain/ProjectGalleryServiceTest.php`:

```php
<?php

namespace Tests\Feature\Domain;

use App\Domain\Assets\AssetOperationException;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ShowContent;
use App\Domain\Content\Actions\SyncProjectImages;
use App\Domain\Publishing\PublicationValidationException;
use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class ProjectGalleryServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_it_adds_ordered_screenshots_to_a_draft_project_without_public_copies(): void
    {
        $project = Project::factory()->create();

        $updated = app(SyncProjectImages::class)($project, [
            $this->item(null, $this->png('first.png'), 'Primera', 'First'),
            $this->item(null, $this->png('second.png'), 'Segunda', 'Second'),
        ]);

        $images = $updated->images;
        $this->assertSame(['First', 'Second'], $images->pluck('alt_en')->all());
        $this->assertSame([0, 1], $images->pluck('position')->all());
        $this->assertSame([null, null], $images->pluck('public_path')->all());
        foreach ($images as $image) {
            $this->assertMatchesRegularExpression('#^projects/[0-9a-f-]+\.png$#', $image->private_path);
            Storage::disk('local')->assertExists($image->private_path);
        }
    }

    public function test_it_rejects_a_thirteenth_screenshot_before_storing_anything(): void
    {
        $project = Project::factory()->create();
        $items = array_map(fn (int $index): array => $this->item(null, $this->png("shot-{$index}.png")), range(0, 12));

        try {
            app(SyncProjectImages::class)($project, $items);
            $this->fail('A thirteenth screenshot must be rejected.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('too_many_images', $exception->issues()[0]->code);
        }

        $this->assertSame(0, $project->images()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_published_project_rejects_a_screenshot_without_alt_text_and_discards_the_upload(): void
    {
        $project = app(PublishContent::class)(Project::factory()->create($this->completeAttributes()));

        try {
            app(SyncProjectImages::class)($project, [$this->item(null, $this->png('shot.png'), 'Captura', null)]);
            $this->fail('Publication rules must apply to the proposed gallery.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('images.0.alt_en', $exception->issues()[0]->path);
        }

        $this->assertSame(0, $project->images()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_it_reorders_updates_alt_text_and_removes_screenshots(): void
    {
        $project = Project::factory()->create();
        $initial = app(SyncProjectImages::class)($project, [
            $this->item(null, $this->png('a.png'), 'A', 'A'),
            $this->item(null, $this->png('b.png'), 'B', 'B'),
            $this->item(null, $this->png('c.png'), 'C', 'C'),
        ])->images;
        [$a, $b, $c] = [$initial[0], $initial[1], $initial[2]];

        $updated = app(SyncProjectImages::class)($project->fresh(), [
            $this->item($c->id, null, 'C nueva', 'C new'),
            $this->item($a->id, null, 'A', 'A'),
        ]);

        $this->assertSame([$c->id, $a->id], $updated->images->pluck('id')->all());
        $this->assertSame(['C new', 'A'], $updated->images->pluck('alt_en')->all());
        $this->assertSame($c->private_path, $updated->images[0]->private_path);
        Storage::disk('local')->assertMissing($b->private_path);
    }

    public function test_replacing_a_file_keeps_the_image_and_retires_the_old_original(): void
    {
        $project = Project::factory()->create();
        $image = app(SyncProjectImages::class)($project, [$this->item(null, $this->png('old.png'))])->images[0];

        $updated = app(SyncProjectImages::class)($project->fresh(), [$this->item($image->id, $this->png('new.png'))]);

        $this->assertSame($image->id, $updated->images[0]->id);
        $this->assertNotSame($image->private_path, $updated->images[0]->private_path);
        Storage::disk('local')->assertMissing($image->private_path);
        Storage::disk('local')->assertExists($updated->images[0]->private_path);
    }

    public function test_a_visible_project_gets_public_copies_for_new_screenshots_and_loses_retired_ones(): void
    {
        $project = $this->visibleProjectWithOneImage();
        $old = $project->images[0];

        $updated = app(SyncProjectImages::class)($project, [$this->item(null, $this->png('new.png'), 'Nueva', 'New')]);

        $new = $updated->images[0];
        $this->assertNotNull($new->public_path);
        Storage::disk('public')->assertExists($new->public_path);
        Storage::disk('public')->assertMissing($old->public_path);
        Storage::disk('local')->assertMissing($old->private_path);
    }

    public function test_it_rejects_an_image_id_from_another_project(): void
    {
        $other = Project::factory()->create();
        $foreign = app(SyncProjectImages::class)($other, [$this->item(null, $this->png('foreign.png'))])->images[0];
        $project = Project::factory()->create();

        try {
            app(SyncProjectImages::class)($project, [$this->item($foreign->id, null)]);
            $this->fail('A foreign image id must be rejected.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('invalid_relation', $exception->issues()[0]->code);
            $this->assertSame('images.0.id', $exception->issues()[0]->path);
        }

        $this->assertSame(1, $other->images()->count());
    }

    public function test_a_failed_public_copy_restores_the_previous_gallery_and_discards_the_upload(): void
    {
        $project = $this->visibleProjectWithOneImage();
        $old = $project->images[0];
        $local = Storage::disk('local');
        $failingPublic = Mockery::mock();
        $failingPublic->shouldReceive('put')->andReturnFalse();
        $failingPublic->shouldReceive('exists')->andReturnFalse();
        $failingPublic->shouldReceive('delete')->andReturnTrue();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($failingPublic);

        try {
            app(SyncProjectImages::class)($project, [
                $this->item($old->id, null, $old->alt_es, $old->alt_en),
                $this->item(null, $this->png('new.png'), 'Nueva', 'New'),
            ]);
            $this->fail('A failed public copy must be controlled.');
        } catch (AssetOperationException) {
            $images = $project->images()->get();
            $this->assertSame([$old->id], $images->pluck('id')->all());
            $this->assertSame($old->public_path, $images[0]->public_path);
            $this->assertSame([$old->private_path], $local->allFiles());
        }
    }

    private function visibleProjectWithOneImage(): Project
    {
        $project = Project::factory()->create($this->completeAttributes());
        app(SyncProjectImages::class)($project, [$this->item(null, $this->png('cover.png'), 'Portada', 'Cover')]);

        return app(ShowContent::class)(app(PublishContent::class)($project->fresh()))->load('images');
    }

    /** @return array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string} */
    private function item(?int $id, ?UploadedFile $upload, ?string $altEs = 'Captura sintética', ?string $altEn = 'Synthetic screenshot'): array
    {
        return ['id' => $id, 'upload' => $upload, 'alt_es' => $altEs, 'alt_en' => $altEn];
    }

    /** @return array<string, string> */
    private function completeAttributes(): array
    {
        return [
            'title_es' => 'Proyecto sintético', 'title_en' => 'Synthetic project',
            'role_es' => 'Rol sintético', 'role_en' => 'Synthetic role',
            'delivery_status' => 'in_development',
            'summary_es' => 'Resumen sintético.', 'summary_en' => 'Synthetic summary.',
            'problem_es' => 'Problema sintético.', 'problem_en' => 'Synthetic problem.',
            'solution_es' => 'Solución sintética.', 'solution_en' => 'Synthetic solution.',
            'result_es' => 'Resultado sintético.', 'result_en' => 'Synthetic result.',
        ];
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9SAAAAABJRU5ErkJggg=='),
        );
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `... php artisan test --compact --filter=ProjectGalleryServiceTest`
Expected: FAIL — `Class "App\Domain\Content\Actions\SyncProjectImages" not found`.

- [ ] **Step 3: Implement the service and the action**

`api/app/Domain/Assets/ProjectGalleryService.php`:

```php
<?php

namespace App\Domain\Assets;

use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationIssue;
use App\Domain\Publishing\PublicationValidator;
use App\Enums\PublicationStatus;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Support\PublicContentCache;
use App\Support\PublicContentDependencies;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Replaces a project's ordered screenshot gallery as one validated unit.
 *
 * Order of operations: validate the proposed list, stage new private
 * originals, validate publication rules against the proposed gallery (alt
 * text is therefore always checked together with its file), commit every row
 * change in one transaction, copy public files only for a published and
 * visible project, then retire replaced or removed files. A failed public copy
 * restores the previous rows and discards the staged uploads, so the old
 * gallery stays intact.
 */
final class ProjectGalleryService
{
    public function __construct(
        private readonly AssetLifecycleService $assets,
        private readonly EditorialMutationContext $context,
        private readonly PublicationValidator $validator,
        private readonly PublicContentCache $cache,
        private readonly PublicContentDependencies $dependencies,
    ) {}

    /**
     * @param  list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>  $items
     */
    public function sync(Project $project, array $items): Project
    {
        $items = array_values($items);
        /** @var Collection<int, ProjectImage> $existing */
        $existing = $project->images()->get();
        $this->assertItems($existing->keyBy('id'), $items);

        if ($this->unchanged($existing, $items)) {
            return $project->fresh('images');
        }

        $staged = $this->stageUploads($items);
        $visible = $project->status === PublicationStatus::Published && $project->is_visible;
        $plan = $this->plan($existing->keyBy('id'), $items, $staged, $visible);

        try {
            $this->assertPublishable($project, $plan);
        } catch (\Throwable $exception) {
            $this->deleteStaged($staged);

            throw $exception;
        }

        $keptIds = array_values(array_filter(array_column($plan, 'id')));
        $retired = $this->retiredFiles($existing, $plan);

        try {
            DB::transaction(function () use ($project, $plan, $keptIds): void {
                Project::query()->lockForUpdate()->findOrFail($project->getKey());

                $this->context->run(function () use ($project, $plan, $keptIds): void {
                    ProjectImage::query()->where('project_id', $project->getKey())->whereNotIn('id', $keptIds)->delete();

                    foreach ($plan as $row) {
                        $attributes = Arr::except($row, ['id']);
                        if ($row['id'] === null) {
                            $project->images()->create($attributes);
                        } else {
                            ProjectImage::query()->findOrFail($row['id'])->forceFill($attributes)->save();
                        }
                    }
                });
            });
        } catch (\Throwable $exception) {
            $this->deleteStaged($staged);

            throw new AssetOperationException('The project gallery could not be saved.', 0, $exception);
        }

        if ($visible) {
            $copied = [];
            try {
                foreach ($plan as $index => $row) {
                    if (isset($staged[$index])) {
                        $this->copyPublic($row['private_path'], (string) $row['public_path']);
                        $copied[] = (string) $row['public_path'];
                    }
                }
            } catch (\Throwable $exception) {
                foreach ($copied as $path) {
                    Storage::disk('public')->delete($path);
                }
                $this->restore($project, $existing);
                $this->deleteStaged($staged);
                $this->cache->invalidate($this->dependencies->for($project));

                throw new AssetOperationException('The project gallery could not be saved.', 0, $exception);
            }
        }

        $this->cache->invalidate($this->dependencies->for($project));
        $this->retire($project, $retired);

        return $project->fresh('images');
    }

    /**
     * @param  Collection<int, ProjectImage>  $byId
     * @param  list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>  $items
     */
    private function assertItems(Collection $byId, array $items): void
    {
        $issues = [];
        if (count($items) > PublicationValidator::PROJECT_IMAGE_LIMIT) {
            $issues[] = new PublicationIssue('too_many_images', 'images', 'A project can have at most 12 images.');
        }

        $seen = [];
        foreach ($items as $index => $item) {
            $id = $item['id'];
            if ($id !== null && (! $byId->has($id) || isset($seen[$id]))) {
                $issues[] = new PublicationIssue('invalid_relation', "images.{$index}.id", 'The image does not belong to this project.');
            }
            if ($id !== null) {
                $seen[$id] = true;
            }
            if ($id === null && ! $item['upload'] instanceof UploadedFile) {
                $issues[] = new PublicationIssue('asset_required', "images.{$index}.file", 'A new screenshot requires an image file.');
            }
            foreach (['alt_es', 'alt_en'] as $field) {
                if (is_string($item[$field]) && mb_strlen($item[$field]) > 500) {
                    $issues[] = new PublicationIssue('max_length_exceeded', "images.{$index}.{$field}", 'This field must not exceed 500 characters.');
                }
            }
        }

        $this->validator->assertIssues($issues);
    }

    /**
     * @param  Collection<int, ProjectImage>  $existing
     * @param  list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>  $items
     */
    private function unchanged(Collection $existing, array $items): bool
    {
        if (count($items) !== $existing->count()) {
            return false;
        }

        foreach ($existing->values() as $index => $image) {
            $item = $items[$index];
            if ($item['upload'] !== null
                || $item['id'] !== $image->getKey()
                || $this->blankToNull($item['alt_es']) !== $image->alt_es
                || $this->blankToNull($item['alt_en']) !== $image->alt_en) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>  $items
     * @return array<int, StagedAsset>
     */
    private function stageUploads(array $items): array
    {
        $staged = [];
        try {
            foreach ($items as $index => $item) {
                if ($item['upload'] instanceof UploadedFile) {
                    $staged[$index] = $this->assets->stage(new ProjectImage, $item['upload']);
                }
            }
        } catch (\Throwable $exception) {
            $this->deleteStaged($staged);

            throw $exception;
        }

        return $staged;
    }

    /**
     * @param  Collection<int, ProjectImage>  $byId
     * @param  list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>  $items
     * @param  array<int, StagedAsset>  $staged
     * @return list<array{id: ?int, position: int, private_path: string, public_path: ?string, mime: string, size: int, alt_es: ?string, alt_en: ?string}>
     */
    private function plan(Collection $byId, array $items, array $staged, bool $visible): array
    {
        $plan = [];
        foreach ($items as $index => $item) {
            $current = $item['id'] === null ? null : $byId->get($item['id']);
            $new = $staged[$index] ?? null;
            $privatePath = $new !== null ? $new->privatePath : $current->private_path;

            $plan[] = [
                'id' => $current?->getKey(),
                'position' => $index,
                'private_path' => $privatePath,
                'public_path' => match (true) {
                    ! $visible => null,
                    $new !== null => 'projects/'.Str::uuid().'.'.pathinfo($privatePath, PATHINFO_EXTENSION),
                    default => $current->public_path,
                },
                'mime' => $new !== null ? $new->mime : $current->mime,
                'size' => $new !== null ? $new->size : $current->size,
                'alt_es' => $this->blankToNull($item['alt_es']),
                'alt_en' => $this->blankToNull($item['alt_en']),
            ];
        }

        return $plan;
    }

    /** @param list<array<string, mixed>> $plan */
    private function assertPublishable(Project $project, array $plan): void
    {
        if ($project->status !== PublicationStatus::Published) {
            return;
        }

        $candidate = clone $project;
        $candidate->setRelation('images', new Collection(array_map(
            static fn (array $row): ProjectImage => (new ProjectImage)->forceFill(Arr::except($row, ['id'])),
            $plan,
        )));
        $this->validator->assertPublishable($candidate);
    }

    /**
     * @param  Collection<int, ProjectImage>  $existing
     * @param  list<array<string, mixed>>  $plan
     * @return list<array{private: string, public: ?string}>
     */
    private function retiredFiles(Collection $existing, array $plan): array
    {
        $rows = collect($plan)->keyBy('id');
        $retired = [];
        foreach ($existing as $image) {
            $row = $rows->get($image->getKey());
            if ($row === null || $row['private_path'] !== $image->private_path) {
                $retired[] = ['private' => $image->private_path, 'public' => $image->public_path];
            }
        }

        return $retired;
    }

    /** @param Collection<int, ProjectImage> $snapshot */
    private function restore(Project $project, Collection $snapshot): void
    {
        DB::transaction(function () use ($project, $snapshot): void {
            $this->context->run(function () use ($project, $snapshot): void {
                ProjectImage::query()->where('project_id', $project->getKey())->delete();
                if ($snapshot->isNotEmpty()) {
                    DB::table('project_images')->insert(
                        $snapshot->map(static fn (ProjectImage $image): array => $image->getRawOriginal())->values()->all(),
                    );
                }
            });
        });
    }

    /** @param list<array{private: string, public: ?string}> $files */
    private function retire(Project $project, array $files): void
    {
        $failed = false;
        foreach ($files as $file) {
            if ($file['public'] !== null) {
                Storage::disk('public')->delete($file['public']);
                $failed = $failed || Storage::disk('public')->exists($file['public']);
            }
            Storage::disk('local')->delete($file['private']);
            $failed = $failed || Storage::disk('local')->exists($file['private']);
        }

        if ($failed) {
            Log::warning('Project gallery file retirement failed.', ['entity_type' => Project::class, 'entity_id' => $project->getKey()]);

            throw new AssetOperationException('The gallery was saved, but a retired file could not be removed.');
        }
    }

    private function copyPublic(string $privatePath, string $publicPath): void
    {
        $contents = Storage::disk('local')->get($privatePath);
        if (! Storage::disk('public')->put($publicPath, $contents) || ! Storage::disk('public')->exists($publicPath)) {
            throw new AssetOperationException('The public copy could not be verified.');
        }
    }

    /** @param array<int, StagedAsset> $staged */
    private function deleteStaged(array $staged): void
    {
        foreach ($staged as $asset) {
            Storage::disk('local')->delete($asset->privatePath);
        }
    }

    private function blankToNull(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : $value;
    }
}
```

`api/app/Domain/Content/Actions/SyncProjectImages.php`:

```php
<?php

namespace App\Domain\Content\Actions;

use App\Domain\Assets\ProjectGalleryService;
use App\Models\Project;
use Illuminate\Http\UploadedFile;

final class SyncProjectImages
{
    public function __construct(private readonly ProjectGalleryService $gallery) {}

    /** @param list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}> $items */
    public function __invoke(Project $project, array $items): Project
    {
        return $this->gallery->sync($project, $items);
    }
}
```

- [ ] **Step 4: Run the service test**

Run: `... php artisan test --compact --filter=ProjectGalleryServiceTest`
Expected: PASS (8 tests).

- [ ] **Step 5: Write the failing Filament gallery tests**

Append to `api/tests/Feature/Filament/ProjectResourceTest.php` (before `completeAttributes()`), adding `use App\Domain\Content\Actions\SyncProjectImages;`:

```php
    // ---- Screenshot gallery ----

    public function test_uploading_screenshots_through_the_form_stores_them_in_order(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $project = Project::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['images' => [
                ['id' => null, 'file' => $this->png('first.png'), 'alt_es' => 'Primera', 'alt_en' => 'First'],
                ['id' => null, 'file' => $this->png('second.png'), 'alt_es' => 'Segunda', 'alt_en' => 'Second'],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['First', 'Second'], $project->images()->pluck('alt_en')->all());
    }

    public function test_the_gallery_form_rejects_more_than_twelve_screenshots(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();
        $this->authenticateAdmin();
        $items = array_map(fn (int $index): array => ['id' => null, 'file' => $this->png("shot-{$index}.png"), 'alt_es' => 'Captura', 'alt_en' => 'Shot'], range(0, 12));

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['images' => $items])
            ->call('save')
            ->assertHasFormErrors(['images']);

        $this->assertSame(0, $project->images()->count());
    }

    public function test_saving_unrelated_fields_keeps_existing_screenshots(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $project = Project::factory()->create();
        $image = app(SyncProjectImages::class)($project, [
            ['id' => null, 'upload' => $this->png('cover.png'), 'alt_es' => 'Portada', 'alt_en' => 'Cover'],
        ])->images[0];
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['summary_es' => 'Resumen actualizado sintético'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$image->id], $project->images()->pluck('id')->all());
        Storage::disk('local')->assertExists($image->private_path);
    }
```

Run: `... php artisan test --compact --filter=ProjectResourceTest`
Expected: FAIL — the form has no `images` field.

- [ ] **Step 6: Add the repeater and wire the edit page**

In `api/app/Filament/Resources/Projects/Schemas/ProjectForm.php` add the imports `use App\Domain\Publishing\PublicationValidator;`, `use Filament\Forms\Components\FileUpload;`, `use Filament\Forms\Components\Hidden;`, and insert right before `Repeater::make('technologies')`:

```php
                Repeater::make('images')
                    ->label('Screenshots')
                    ->addActionLabel('Add screenshot')
                    ->schema([
                        Hidden::make('id'),
                        Placeholder::make('screenshot_state')
                            ->label('Screenshot')
                            ->content(fn (Get $get): string => filled($get('id')) ? 'Saved screenshot' : 'New screenshot'),
                        FileUpload::make('file')
                            ->label('Image file')
                            ->image()
                            ->storeFiles(false)
                            ->acceptedFileTypes(PublicationValidator::PROJECT_IMAGE_MIMES)
                            ->maxSize(8 * 1024)
                            ->helperText('JPEG, PNG, or WebP, up to 8 MiB. Required for a new screenshot; leave empty to keep a saved one.'),
                        TextInput::make('alt_es')->label('Alt text (ES)')->maxLength(500),
                        TextInput::make('alt_en')->label('Alt text (EN)')->maxLength(500),
                    ])
                    ->reorderable()
                    ->maxItems(PublicationValidator::PROJECT_IMAGE_LIMIT)
                    ->defaultItems(0)
                    ->columns(1)
                    ->helperText('Drag to reorder (the first one is the main screenshot). Up to 12. Alt text in both languages is required to publish.')
                    ->hidden(fn (?Project $record): bool => $record === null)
                    ->dehydrated(fn (?Project $record): bool => $record !== null),
                Placeholder::make('images_notice')
                    ->label('Screenshots')
                    ->content('Save the project first, then add screenshots here.')
                    ->visible(fn (?Project $record): bool => $record === null),
```

In `api/app/Filament/Resources/Projects/Pages/EditProject.php`:
- add the imports `use App\Domain\Content\Actions\SyncProjectImages;`, `use App\Models\ProjectImage;`, `use Illuminate\Http\UploadedFile;`;
- in `mutateFormDataBeforeFill()`, before `return $data;`:

```php
        $data['images'] = $record->images->map(fn (ProjectImage $image): array => [
            'id' => $image->getKey(),
            'file' => null,
            'alt_es' => $image->alt_es,
            'alt_en' => $image->alt_en,
        ])->all();
```

- in `handleRecordUpdate()`, capture the gallery before the `unset(...)` call:

```php
        $imagesInput = $data['images'] ?? null;
```

- and, right after the `try { ... UpdateContentWithTechnologies ... } catch (...) { ... }` block:

```php
        if (is_array($imagesInput)) {
            try {
                $updated = app(SyncProjectImages::class)($updated, self::galleryItems($imagesInput));
            } catch (\Throwable $exception) {
                EditorialActions::notifyAssetFailure('Save', $exception);

                throw new Halt;
            }
        }
```

- add the helper:

```php
    /**
     * Normalizes the repeater state into the gallery service contract. A
     * FileUpload may dehydrate a single file either bare or wrapped in a
     * one-item array.
     *
     * @param  array<array-key, array<string, mixed>>  $input
     * @return list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>
     */
    private static function galleryItems(array $input): array
    {
        return array_values(array_map(static function (array $item): array {
            $file = $item['file'] ?? null;
            if (is_array($file)) {
                $file = array_values($file)[0] ?? null;
            }

            return [
                'id' => filled($item['id'] ?? null) ? (int) $item['id'] : null,
                'upload' => $file instanceof UploadedFile ? $file : null,
                'alt_es' => $item['alt_es'] ?? null,
                'alt_en' => $item['alt_en'] ?? null,
            ];
        }, $input));
    }
```

Run: `... php artisan test --compact --filter="ProjectResourceTest|ProjectGalleryServiceTest"`
Expected: PASS.

- [ ] **Step 7: Format, run the suite and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint app tests`
Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact`
Expected: all tests pass.

```bash
git add api
git commit -m "feat(filament): edit ordered project screenshots with up to twelve images"
```

### Task 4: Link work cases to experiences

**Files:**
- Create: `api/database/migrations/2026_09_15_000002_add_experience_to_work_cases.php`
- Modify: `api/app/Models/WorkCase.php`, `api/app/Models/Experience.php`
- Modify: `api/app/Filament/Resources/WorkCases/Schemas/WorkCaseForm.php`
- Modify: `api/app/Filament/Support/PublicationReviewPresenter.php` (`workCase`)
- Modify: `api/app/Support/PublicContentDependencies.php`
- Modify: `api/app/Http/Resources/Api/V1/WorkCaseResource.php`, `api/app/Http/Controllers/Api/V1/WorkCaseController.php`, `docs/api/PUBLIC_API_V1.md`
- Modify tests: `PhaseSixSchemaTest`, `WorkCaseResourceTest`, `PublicApiContractTest`, `PublicContentCacheTest`

**Interfaces:**
- Produces: `work_cases.experience_id` (nullable FK, `nullOnDelete`); `WorkCase::experience(): BelongsTo`; `Experience::workCases(): HasMany`; `WorkCaseForm::experienceLabel(Experience $experience): string` returning `"{organization} · {role} · MM/YYYY – MM/YYYY|present"`; `PublicContentDependencies::for(Experience::class) === [PublicEndpoint::Experiences, PublicEndpoint::WorkCases]`; API work case item `experience_key: string|null`.

- [ ] **Step 1: Write the failing tests**

Append to `api/tests/Feature/Database/PhaseSixSchemaTest.php`:

```php
    public function test_deleting_an_experience_unlinks_its_work_cases(): void
    {
        $experienceId = DB::table('experiences')->insertGetId([
            ...$this->projectRow('linked-experience'),
            'start_year' => 2025, 'start_month' => 9, 'end_year' => null, 'end_month' => null,
        ]);
        $workCaseId = DB::table('work_cases')->insertGetId([...$this->projectRow('linked-case'), 'experience_id' => $experienceId]);

        DB::table('experiences')->where('id', $experienceId)->delete();

        $this->assertDatabaseHas('work_cases', ['id' => $workCaseId, 'experience_id' => null]);
    }
```

In `api/tests/Feature/Cache/PublicContentCacheTest.php` replace the Experience assertion with:

```php
        $this->assertSame([PublicEndpoint::Experiences, PublicEndpoint::WorkCases], $dependencies->for(Experience::class));
```

Append to `api/tests/Feature/Filament/WorkCaseResourceTest.php`, adding `use App\Filament\Resources\WorkCases\Schemas\WorkCaseForm;` and `use App\Models\Experience;`:

```php
    // ---- Experience link ----

    public function test_a_work_case_can_be_linked_to_an_experience(): void
    {
        $experience = Experience::factory()->withOrganization()->create();
        $workCase = WorkCase::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->assertFormFieldExists('experience_id')
            ->fillForm(['experience_id' => $experience->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($experience->id, $workCase->refresh()->experience_id);

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->fillForm(['experience_id' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($workCase->refresh()->experience_id);
    }

    public function test_experience_options_describe_organization_role_and_dates(): void
    {
        $current = Experience::factory()->make([
            'organization_label_es' => 'Organización sintética', 'role_es' => 'Rol sintético',
            'start_year' => 2025, 'start_month' => 9, 'end_year' => null, 'end_month' => null,
        ]);
        $ended = Experience::factory()->make([
            'key' => 'ended-role', 'organization_label_es' => null, 'role_es' => null,
            'start_year' => 2025, 'start_month' => 5, 'end_year' => 2025, 'end_month' => 9,
        ]);

        $this->assertSame('Organización sintética · Rol sintético · 09/2025 – present', WorkCaseForm::experienceLabel($current));
        $this->assertSame('No organization · ended-role · 05/2025 – 09/2025', WorkCaseForm::experienceLabel($ended));
    }
```

In `api/tests/Feature/Api/V1/PublicApiContractTest.php`:
- in `test_collection_resources_emit_exact_types_normalized_dates_and_no_editorial_data`, change `$workCase = WorkCase::factory()->create(['key' => 'case']);` to `$workCase = WorkCase::factory()->create(['key' => 'case', 'experience_id' => $experience->id]);` and add `'experience_key' => 'experience',` after `'key' => 'case',` in the work-cases `assertExactJson`;
- add:

```php
    public function test_work_case_experience_key_is_exposed_only_while_the_experience_is_public(): void
    {
        $experience = Experience::factory()->create(['key' => 'linked-role']);
        $workCase = WorkCase::factory()->create(['key' => 'linked-case', 'experience_id' => $experience->id]);
        $this->makePublic($experience, [
            'role_es' => 'Rol sintético', 'role_en' => 'Synthetic role',
            'summary_es' => 'Resumen sintético.', 'summary_en' => 'Synthetic summary.',
        ]);
        $this->makePublic($workCase);

        $this->getJson('/api/v1/es/work-cases')->assertJsonPath('data.0.experience_key', 'linked-role');

        DB::table('experiences')->where('id', $experience->id)->update(['is_visible' => false]);
        Cache::flush();
        $this->getJson('/api/v1/es/work-cases')->assertJsonPath('data.0.experience_key', null);

        DB::table('experiences')->where('id', $experience->id)->delete();
        Cache::flush();
        $this->getJson('/api/v1/es/work-cases')->assertJsonPath('data.0.experience_key', null);
        $this->assertDatabaseHas('work_cases', ['id' => $workCase->id, 'experience_id' => null]);
    }
```

Run: `... php artisan test --compact --filter="PhaseSixSchemaTest|PublicContentCacheTest|WorkCaseResourceTest|PublicApiContractTest"`
Expected: FAIL — unknown column `experience_id`.

- [ ] **Step 2: Migration and relations**

`api/database/migrations/2026_09_15_000002_add_experience_to_work_cases.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional work case → experience link (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.3).
 * Deleting an experience leaves its cases unlinked instead of deleting them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_cases', function (Blueprint $table) {
            $table->foreignId('experience_id')->nullable()->after('position')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('experience_id');
        });
    }
};
```

`api/app/Models/WorkCase.php`: add `use Illuminate\Database\Eloquent\Relations\BelongsTo;`, add `'experience_id'` to `$fillable` after `'position'`, add `'experience_id' => 'integer'` to `casts()`, and add:

```php
    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }
```

`api/app/Models/Experience.php`: add:

```php
    public function workCases(): HasMany
    {
        return $this->hasMany(WorkCase::class)->orderBy('position')->orderBy('key');
    }
```

`api/app/Support/PublicContentDependencies.php`: replace

```php
            Experience::class,
            ExperienceHighlight::class => [PublicEndpoint::Experiences],
```

with

```php
            Experience::class => [PublicEndpoint::Experiences, PublicEndpoint::WorkCases],
            ExperienceHighlight::class => [PublicEndpoint::Experiences],
```

- [ ] **Step 3: Form select**

In `api/app/Filament/Resources/WorkCases/Schemas/WorkCaseForm.php` add `use App\Models\Experience;` and insert after the `key` field:

```php
                Select::make('experience_id')
                    ->label('Experience')
                    ->options(fn (): array => Experience::query()->orderBy('position')->orderBy('key')->get()
                        ->mapWithKeys(fn (Experience $experience): array => [$experience->getKey() => self::experienceLabel($experience)])
                        ->all())
                    ->searchable()
                    ->placeholder('Not linked')
                    ->helperText('Optional. Linked cases appear under that role; unlinked cases appear under "Other cases".'),
```

Add the method:

```php
    public static function experienceLabel(Experience $experience): string
    {
        $start = sprintf('%02d/%04d', $experience->start_month, $experience->start_year);
        $end = $experience->isCurrent() ? 'present' : sprintf('%02d/%04d', $experience->end_month, $experience->end_year);

        return implode(' · ', [
            $experience->organization_label_es ?: 'No organization',
            $experience->role_es ?: $experience->key,
            "{$start} – {$end}",
        ]);
    }
```

In `api/app/Filament/Support/PublicationReviewPresenter.php`, in `workCase()` add after the `bilingual` array:

```php
            'fields' => [
                ['label' => 'Experience', 'value' => $workCase->experience?->key],
            ],
```

- [ ] **Step 4: API**

`api/app/Http/Resources/Api/V1/WorkCaseResource.php`: add `'experience_key' => $this->experience?->key,` after `'key' => $this->key,`.

`api/app/Http/Controllers/Api/V1/WorkCaseController.php`: in `with([...])` add before `'technologies' => ...`:

```php
                'experience' => static fn ($query) => $query->publiclyAvailable(),
```

`docs/api/PUBLIC_API_V1.md`, work-cases section: add `"experience_key": null,` after `"key": "string",` in the JSON example and append after the paragraph that lists the six bilingual fields:

```markdown
`experience_key` is `string | null`: the `key` of the linked Experience only
while that Experience is published and visible; `null` when the case is not
linked, the Experience is hidden or draft, or it was deleted (the database
link is cleared with `ON DELETE SET NULL`). Changing an Experience invalidates
both the `experiences` and `work-cases` caches.
```

Run: `... php artisan test --compact --filter="PhaseSixSchemaTest|PublicContentCacheTest|WorkCaseResourceTest|PublicApiContractTest|LocalizedContentApiTest"`
Expected: PASS.

- [ ] **Step 5: Format, run the suite and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint app database tests`
Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact`
Expected: all tests pass.

```bash
git add api docs/api/PUBLIC_API_V1.md
git commit -m "feat(api): link work cases to their experience"
```

### Task 5: Education entries

**Files:**
- Create: `api/database/migrations/2026_09_15_000003_create_education_entries_table.php`
- Create: `api/app/Models/EducationEntry.php`, `api/database/factories/EducationEntryFactory.php`
- Create: `api/app/Filament/Resources/EducationEntries/EducationEntryResource.php`, `Schemas/EducationEntryForm.php`, `Tables/EducationEntriesTable.php`, `Pages/CreateEducationEntry.php`, `Pages/EditEducationEntry.php`, `Pages/ListEducationEntries.php`
- Create test: `api/tests/Feature/Filament/EducationEntryResourceTest.php`
- Modify: `PublicationValidator.php`, `EditorialMutationGuard.php`, `AppServiceProvider.php`, `PublicContentDependencies.php`, `ReviewLink.php`, `ReviewContent.php`, `PublicationReviewPresenter.php`
- Modify: `api/app/Http/Controllers/Api/V1/SiteController.php`, `api/app/Http/Resources/Api/V1/SiteResource.php`, `docs/api/PUBLIC_API_V1.md`
- Modify tests: `PhaseSixSchemaTest`, `PublicationValidatorTest`, `PublicContentCacheTest`, `PublicationReviewPageTest`, `PublicApiContractTest`

**Interfaces:**
- Produces: `App\Models\EducationEntry` (`key`, `key_locked`, `position`, `institution`, `program_es`, `program_en`, `detail_es`, `detail_en`, `start_year`, `end_year`, publication columns; `scopePubliclyAvailable`); `EducationEntryFactory` states `draft()`, `publishedHidden()`, `publishedVisible()`; review type `education-entry`; dependency `EducationEntry → [Site]`; API `site.education: list<{key, institution, program, detail: ?string, start_year: ?int, end_year: ?int}>` ordered by `position`, `key`.

- [ ] **Step 1: Write the failing domain tests**

Append to `api/tests/Feature/Database/PhaseSixSchemaTest.php`:

```php
    public function test_education_years_must_be_valid_and_chronological(): void
    {
        DB::table('education_entries')->insert([...$this->projectRow('open-dates'), 'start_year' => null, 'end_year' => 2021]);
        $this->assertDatabaseHas('education_entries', ['key' => 'open-dates', 'end_year' => 2021]);

        $this->assertDatabaseRejects(fn () => DB::table('education_entries')->insert([
            ...$this->projectRow('backward-years'), 'start_year' => 2022, 'end_year' => 2021,
        ]));
        $this->assertDatabaseRejects(fn () => DB::table('education_entries')->insert([
            ...$this->projectRow('short-year'), 'end_year' => 21,
        ]));
    }
```

Append to `api/tests/Feature/Domain/PublicationValidatorTest.php`, adding `use App\Models\EducationEntry;`:

```php
    public function test_it_validates_education_entries(): void
    {
        $entry = EducationEntry::factory()->publishedHidden()->make([
            'institution' => ' ',
            'program_en' => null,
            'detail_es' => 'Detalle sintético',
            'detail_en' => null,
            'start_year' => 2023,
            'end_year' => 2022,
        ]);

        $this->assertSame([
            ['code' => 'required', 'path' => 'institution'],
            ['code' => 'required_translation', 'path' => 'program_en'],
            ['code' => 'translation_pair', 'path' => 'detail'],
            ['code' => 'invalid_date_range', 'path' => 'end_year'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($entry)));
        $this->assertSame([], app(PublicationValidator::class)->issues(EducationEntry::factory()->publishedHidden()->make()));
    }
```

In `api/tests/Feature/Cache/PublicContentCacheTest.php` add `use App\Models\EducationEntry;` and:

```php
        $this->assertSame([PublicEndpoint::Site], $dependencies->for(EducationEntry::class));
```

Run: `... php artisan test --compact --filter="PhaseSixSchemaTest|PublicationValidatorTest|PublicContentCacheTest"`
Expected: FAIL — table `education_entries` / class `EducationEntry` do not exist.

- [ ] **Step 2: Migration, model and factory**

`api/database/migrations/2026_09_15_000003_create_education_entries_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Structured education list for the About section (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_entries', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->boolean('key_locked')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->string('institution')->nullable();
            $table->string('program_es')->nullable();
            $table->string('program_en')->nullable();
            $table->string('detail_es')->nullable();
            $table->string('detail_en')->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_visible')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'is_visible', 'position', 'key']);
        });

        foreach ([
            'education_entries_publication_state_check' => "(status = 'draft' AND is_visible = 0 AND published_at IS NULL) OR (status = 'published' AND published_at IS NOT NULL)",
            'education_entries_key_locked_check' => "status <> 'published' OR key_locked = 1",
            'education_entries_position_check' => 'position >= 0',
            'education_entries_start_year_check' => 'start_year IS NULL OR start_year BETWEEN 1000 AND 9999',
            'education_entries_end_year_check' => 'end_year IS NULL OR end_year BETWEEN 1000 AND 9999',
            'education_entries_chronology_check' => 'start_year IS NULL OR end_year IS NULL OR end_year >= start_year',
        ] as $name => $expression) {
            DB::statement("ALTER TABLE education_entries ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('education_entries');
    }
};
```

`api/app/Models/EducationEntry.php`:

```php
<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EducationEntry extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'key_locked', 'position', 'institution', 'program_es', 'program_en', 'detail_es', 'detail_en', 'start_year', 'end_year', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['key_locked' => 'boolean', 'position' => 'integer', 'start_year' => 'integer', 'end_year' => 'integer', 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true)->orderBy('position')->orderBy('key');
    }
}
```

`api/database/factories/EducationEntryFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\EducationEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EducationEntry> */
final class EducationEntryFactory extends Factory
{
    protected $model = EducationEntry::class;

    public function definition(): array
    {
        return ['key' => 'synthetic-education-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'institution' => null, 'program_es' => null, 'program_en' => null, 'detail_es' => null, 'detail_en' => null, 'start_year' => null, 'end_year' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null]);
    }

    public function publishedHidden(): static
    {
        return $this->state($this->publishedAttributes(false));
    }

    public function publishedVisible(): static
    {
        return $this->state($this->publishedAttributes(true));
    }

    private function publishedAttributes(bool $visible): array
    {
        return ['key_locked' => true, 'institution' => 'Institución sintética', 'program_es' => 'Programa sintético', 'program_en' => 'Synthetic program', 'end_year' => 2021, 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
```

- [ ] **Step 3: Validator, guard, provider and dependencies**

`PublicationValidator.php`: add `use App\Models\EducationEntry;`; in `issues()` add `EducationEntry::class => $this->educationEntryIssues($content),` after the `WorkPrinciple` arm; add:

```php
    /** @return list<PublicationIssue> */
    private function educationEntryIssues(EducationEntry $entry): array
    {
        return [
            ...$this->keyIssues($entry),
            ...$this->required($entry, ['institution']),
            ...$this->requiredPairs($entry, ['program']),
            ...$this->optionalPairs($entry, ['detail']),
            ...$this->educationYearIssues($entry),
            ...$this->maxLength($entry, ['institution'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($entry, ['program', 'detail'], self::BOUNDED_MAX_LENGTH),
        ];
    }

    /** @return list<PublicationIssue> */
    private function educationYearIssues(EducationEntry $entry): array
    {
        $issues = [];
        foreach (['start_year', 'end_year'] as $field) {
            $year = $entry->getAttribute($field);
            if ($year !== null && ((int) $year < 1000 || (int) $year > 9999)) {
                $issues[] = $this->issue('invalid_year', $field, 'The year must have four digits.');
            }
        }
        if ($issues === [] && $entry->start_year !== null && $entry->end_year !== null && (int) $entry->end_year < (int) $entry->start_year) {
            $issues[] = $this->issue('invalid_date_range', 'end_year', 'The end year must not precede the start year.');
        }

        return $issues;
    }
```

`EditorialMutationGuard.php`: add `use App\Models\EducationEntry;` and `EducationEntry::class` to the `isManagedContent()` list.
`AppServiceProvider.php`: add `use App\Models\EducationEntry;` and `EducationEntry::class` to the observed list.
`PublicContentDependencies.php`: add `use App\Models\EducationEntry;` and `EducationEntry::class,` to the list of models mapped to `[PublicEndpoint::Site]` (right after `SiteConfiguration::class,`).

Run: `... php artisan test --compact --filter="PhaseSixSchemaTest|PublicationValidatorTest|PublicContentCacheTest"`
Expected: PASS.

- [ ] **Step 4: Write the failing Filament test**

Create `api/tests/Feature/Filament/EducationEntryResourceTest.php`:

```php
<?php

namespace Tests\Feature\Filament;

use App\Enums\PublicationStatus;
use App\Filament\Resources\EducationEntries\Pages\CreateEducationEntry;
use App\Filament\Resources\EducationEntries\Pages\EditEducationEntry;
use App\Filament\Resources\EducationEntries\Pages\ListEducationEntries;
use App\Models\EducationEntry;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class EducationEntryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_reach_education_pages_and_guests_cannot(): void
    {
        $entry = EducationEntry::factory()->create();

        $this->get(ListEducationEntries::getUrl())->assertRedirect(route('filament.admin.auth.login'));

        $this->authenticateAdmin();
        $this->get(ListEducationEntries::getUrl())->assertOk();
        $this->get(CreateEducationEntry::getUrl())->assertOk();
        $this->get(EditEducationEntry::getUrl(['record' => $entry]))->assertOk();
    }

    public function test_non_administrator_is_denied_the_education_list(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        $this->get(ListEducationEntries::getUrl())->assertForbidden();
    }

    public function test_administrator_creates_several_education_entries_as_ordered_drafts(): void
    {
        $this->authenticateAdmin();

        foreach (['synthetic-school', 'synthetic-course'] as $key) {
            Livewire::test(CreateEducationEntry::class)
                ->fillForm(['key' => $key, 'institution' => 'Institución sintética', 'program_es' => 'Programa', 'program_en' => 'Program', 'end_year' => 2021])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $entries = EducationEntry::query()->orderBy('position')->get();
        $this->assertSame(['synthetic-school', 'synthetic-course'], $entries->pluck('key')->all());
        $this->assertSame([0, 1], $entries->pluck('position')->all());
        $this->assertSame([PublicationStatus::Draft, PublicationStatus::Draft], $entries->pluck('status')->all());
    }

    public function test_publication_requires_institution_and_both_program_translations(): void
    {
        $entry = EducationEntry::factory()->create(['institution' => 'Institución sintética', 'program_es' => 'Programa']);
        $this->authenticateAdmin();

        Livewire::test(EditEducationEntry::class, ['record' => $entry->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');
        $this->assertSame(PublicationStatus::Draft, $entry->refresh()->status);

        Livewire::test(EditEducationEntry::class, ['record' => $entry->getKey()])
            ->fillForm(['program_en' => 'Program', 'detail_es' => 'Detalle', 'detail_en' => 'Detail'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->callAction('publish')
            ->assertNotified('Publish succeeded');
        $this->assertSame(PublicationStatus::Published, $entry->refresh()->status);
    }

    public function test_reorder_action_changes_the_position(): void
    {
        $entry = EducationEntry::factory()->create(['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ListEducationEntries::class)
            ->callTableAction('reorder', $entry, data: ['position' => 3])
            ->assertHasNoTableActionErrors();

        $this->assertSame(3, $entry->refresh()->position);
    }

    private function authenticateAdmin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        return $admin;
    }
}
```

In `api/tests/Feature/Filament/PublicationReviewPageTest.php` add `use App\Filament\Resources\EducationEntries\Pages\EditEducationEntry;` and `use App\Models\EducationEntry;`, then add `'education-entry' => ['education-entry', fn () => EducationEntry::factory()->create()],` to `manageableEntities()` and `'education-entry' => ['education-entry', fn () => EducationEntry::factory()->create(), fn ($record) => EditEducationEntry::getUrl(['record' => $record])],` to `editUrlCases()`.

Run: `... php artisan test --compact --filter="EducationEntryResourceTest|PublicationReviewPageTest"`
Expected: FAIL — class `ListEducationEntries` not found.

- [ ] **Step 5: Filament resource**

`api/app/Filament/Resources/EducationEntries/EducationEntryResource.php`:

```php
<?php

namespace App\Filament\Resources\EducationEntries;

use App\Filament\Resources\EducationEntries\Pages\CreateEducationEntry;
use App\Filament\Resources\EducationEntries\Pages\EditEducationEntry;
use App\Filament\Resources\EducationEntries\Pages\ListEducationEntries;
use App\Filament\Resources\EducationEntries\Schemas\EducationEntryForm;
use App\Filament\Resources\EducationEntries\Tables\EducationEntriesTable;
use App\Filament\Support\ReviewLink;
use App\Models\EducationEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EducationEntryResource extends Resource
{
    protected static ?string $model = EducationEntry::class;

    protected static ?string $navigationLabel = 'Education';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Profile and site';

    public static function form(Schema $schema): Schema
    {
        return EducationEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EducationEntriesTable::configure($table)->pushRecordActions([ReviewLink::rowAction()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEducationEntries::route('/'),
            'create' => CreateEducationEntry::route('/create'),
            'edit' => EditEducationEntry::route('/{record}/edit'),
        ];
    }
}
```

`api/app/Filament/Resources/EducationEntries/Schemas/EducationEntryForm.php`:

```php
<?php

namespace App\Filament\Resources\EducationEntries\Schemas;

use App\Enums\PublicationStatus;
use App\Models\EducationEntry;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class EducationEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Key')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->helperText('Lowercase slug (letters, digits, hyphens). Change later using the "Change key" action.')
                    ->disabledOn('edit'),

                TextInput::make('institution')
                    ->label('Institution')
                    ->maxLength(255)
                    ->helperText('Required to publish. Not translated.'),
                TextInput::make('start_year')
                    ->label('Start year')
                    ->numeric()
                    ->integer()
                    ->minValue(1000)
                    ->maxValue(9999)
                    ->helperText('Optional.'),
                TextInput::make('end_year')
                    ->label('End year')
                    ->numeric()
                    ->integer()
                    ->minValue(1000)
                    ->maxValue(9999)
                    ->helperText('Optional. Must not precede the start year.'),

                Tabs::make('locales')->tabs([
                    Tab::make('Spanish')->schema([
                        TextInput::make('program_es')->label('Program (ES)')->maxLength(255),
                        TextInput::make('detail_es')->label('Detail (ES)')->maxLength(255)
                            ->helperText('Optional. Spanish and English must be filled together.'),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('program_en')->label('Program (EN)')->maxLength(255),
                        TextInput::make('detail_en')->label('Detail (EN)')->maxLength(255),
                    ]),
                ]),

                Placeholder::make('position_display')
                    ->label('Position')
                    ->content(fn (?EducationEntry $record): string => $record !== null ? (string) $record->position : 'Assigned on creation')
                    ->helperText('Use the "Change position" table action to reorder.'),
                Placeholder::make('key_locked_display')
                    ->label('Key locked')
                    ->content(fn (?EducationEntry $record): string => ($record?->key_locked ?? false) ? 'Yes' : 'No'),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?EducationEntry $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?EducationEntry $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?EducationEntry $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }
}
```

`api/app/Filament/Resources/EducationEntries/Tables/EducationEntriesTable.php`:

```php
<?php

namespace App\Filament\Resources\EducationEntries\Tables;

use App\Domain\Content\Actions\ReorderContent;
use App\Enums\PublicationStatus;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EducationEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('key')->label('Key'),
                TextColumn::make('program_es')->label('Program (ES)')->limit(40),
                TextColumn::make('institution')->label('Institution')->limit(40),
                TextColumn::make('end_year')->label('End year'),
                TextColumn::make('position')->label('Position')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PublicationStatus $state): string => ucfirst($state->value))
                    ->color(fn (PublicationStatus $state): string => $state === PublicationStatus::Published ? 'success' : 'gray'),
                TextColumn::make('is_visible')
                    ->label('Visibility')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Visible' : 'Hidden')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    PublicationStatus::Draft->value => 'Draft',
                    PublicationStatus::Published->value => 'Published',
                ]),
                TernaryFilter::make('is_visible')
                    ->label('Visibility')
                    ->trueLabel('Visible')
                    ->falseLabel('Hidden'),
            ])
            ->recordActions([
                self::reorderAction(),
                EditAction::make(),
            ]);
    }

    private static function reorderAction(): Action
    {
        return Action::make('reorder')
            ->label('Change position')
            ->icon('heroicon-o-arrows-up-down')
            ->schema([
                TextInput::make('position')
                    ->label('New position')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required()
                    ->default(fn (Model $record): int => $record->position),
            ])
            ->action(function (Model $record, array $data): void {
                try {
                    app(ReorderContent::class)($record, (int) $data['position']);
                } catch (\InvalidArgumentException $exception) {
                    Notification::make()->danger()->title('Reorder failed')->body($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('Position updated')->send();
            });
    }
}
```

`api/app/Filament/Resources/EducationEntries/Pages/ListEducationEntries.php`:

```php
<?php

namespace App\Filament\Resources\EducationEntries\Pages;

use App\Filament\Resources\EducationEntries\EducationEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEducationEntries extends ListRecords
{
    protected static string $resource = EducationEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
```

`api/app/Filament/Resources/EducationEntries/Pages/CreateEducationEntry.php`:

```php
<?php

namespace App\Filament\Resources\EducationEntries\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\EducationEntries\EducationEntryResource;
use App\Models\EducationEntry;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEducationEntry extends CreateRecord
{
    protected static string $resource = EducationEntryResource::class;

    /**
     * Plain Eloquent create is permitted: the editorial mutation guard only
     * forbids creating already-published content. New entries are appended
     * after the current last position.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['status'] = PublicationStatus::Draft;
        $data['is_visible'] = false;
        $data['published_at'] = null;
        $data['key_locked'] = false;
        $data['position'] = (int) (EducationEntry::query()->max('position') ?? -1) + 1;

        // A cleared numeric input submits an empty string, which MySQL rejects for SMALLINT.
        foreach (['start_year', 'end_year'] as $year) {
            if (($data[$year] ?? null) === '') {
                $data[$year] = null;
            }
        }

        return EducationEntry::query()->create($data);
    }
}
```

`api/app/Filament/Resources/EducationEntries/Pages/EditEducationEntry.php`:

```php
<?php

namespace App\Filament\Resources\EducationEntries\Pages;

use App\Domain\Content\Actions\ChangePublicKey;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Resources\EducationEntries\EducationEntryResource;
use App\Filament\Support\EditorialActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class EditEducationEntry extends EditRecord
{
    protected static string $resource = EducationEntryResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            EditorialActions::publish(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::show(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::hide(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::returnToDraft(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            $this->changeKeyAction(),
            DeleteAction::make()->using(function (Model $record): bool {
                app(DeleteContent::class)($record);

                return true;
            }),
        ];
    }

    private function changeKeyAction(): Action
    {
        return Action::make('change_key')
            ->label('Change key')
            ->color('warning')
            ->schema([
                TextInput::make('key')
                    ->label('New key')
                    ->required()
                    ->maxLength(100)
                    ->default(fn (Model $record): string => $record->key)
                    ->unique(table: 'education_entries', column: 'key', ignoreRecord: true),
            ])
            ->requiresConfirmation(fn (Model $record): bool => (bool) $record->key_locked)
            ->modalDescription('This key is already locked by a previous publication. Changing it affects any public references to it.')
            ->action(function (Model $record, array $data): void {
                try {
                    $updated = app(ChangePublicKey::class)($record, $data['key'], confirmed: true);
                } catch (PublicationValidationException $exception) {
                    $this->notifyValidationFailure('Change key', $exception);

                    return;
                } catch (\LogicException $exception) {
                    Notification::make()->danger()->title('Change key failed')->body($exception->getMessage())->send();

                    return;
                }

                $this->syncRecord($updated);
                Notification::make()->success()->title('Key changed')->send();
            });
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['key'], $data['key_locked'], $data['position'], $data['status'], $data['is_visible'], $data['published_at']);

        foreach (['start_year', 'end_year'] as $year) {
            if (array_key_exists($year, $data) && ($data[$year] === '' || $data[$year] === null)) {
                $data[$year] = null;
            }
        }

        try {
            $updated = app(UpdateContent::class)($record, $data);
        } catch (PublicationValidationException $exception) {
            $this->notifyValidationFailure('Save', $exception);

            throw new Halt;
        }

        $this->record = $updated;

        if ($updated->status === PublicationStatus::Published && $updated->is_visible) {
            Notification::make()
                ->warning()
                ->title('Saved')
                ->body('This content is published and visible: the change is immediately public.')
                ->send();
        }

        return $updated;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        $record = $this->getRecord();

        if ($record->status === PublicationStatus::Published && $record->is_visible) {
            return null;
        }

        return 'Saved';
    }

    private function notifyValidationFailure(string $label, PublicationValidationException $exception): void
    {
        $body = collect($exception->issues())
            ->map(fn ($issue): string => "[{$issue->code}] {$issue->path}: {$issue->message}")
            ->implode("\n");

        Notification::make()
            ->danger()
            ->title("{$label} failed")
            ->body($body)
            ->send();
    }

    private function syncRecord(Model $updated): void
    {
        $this->record = $updated;
    }
}
```

- [ ] **Step 6: Review page glue**

`api/app/Filament/Support/ReviewLink.php`: add `use App\Models\EducationEntry;` and `EducationEntry::class => 'education-entry',` to `TYPES` (after `WorkPrinciple::class`).

`api/app/Filament/Pages/ReviewContent.php`: add `use App\Filament\Resources\EducationEntries\Pages\EditEducationEntry;` and the match arm `'education-entry' => EditEducationEntry::getUrl(['record' => $content]),`.

`api/app/Filament/Support/PublicationReviewPresenter.php`: add `use App\Models\EducationEntry;`; add `EducationEntry::class => $this->educationEntry($content),` to `present()`; in `siteConfiguration()` add `$this->statusBreakdown('Education entries', EducationEntry::query()),` to `dependencies` after `Work principles`; add:

```php
    /** @return array<string, mixed> */
    private function educationEntry(EducationEntry $entry): array
    {
        return array_merge($this->base('Education entry', $entry->key, $entry->position, $entry->status->value, $entry->is_visible, $entry->published_at?->toDateTimeString()), [
            'dates' => [
                ['label' => 'Start year', 'value' => $entry->start_year === null ? 'Not set' : (string) $entry->start_year],
                ['label' => 'End year', 'value' => $entry->end_year === null ? 'Not set' : (string) $entry->end_year],
            ],
            'bilingual' => [
                ['label' => 'Program', 'es' => $entry->program_es, 'en' => $entry->program_en],
                ['label' => 'Detail', 'es' => $entry->detail_es, 'en' => $entry->detail_en],
            ],
            'fields' => [
                ['label' => 'Institution', 'value' => $entry->institution],
            ],
            'issues' => $this->issues($entry),
        ]);
    }
```

Run: `... php artisan test --compact --filter="EducationEntryResourceTest|PublicationReviewPageTest"`
Expected: PASS.

- [ ] **Step 7: Write the failing site API test**

In `api/tests/Feature/Api/V1/PublicApiContractTest.php` add `use App\Models\EducationEntry;`; in `test_site_resource_has_ordered_groups_independently_filtered_collections_and_a_private_cv_contract` add `'education' => [],` after the `work_principles` entry of the exact JSON; add:

```php
    public function test_site_resource_exposes_public_education_in_order(): void
    {
        $this->publishSite();
        $course = EducationEntry::factory()->create(['key' => 'course', 'position' => 1]);
        $school = EducationEntry::factory()->create(['key' => 'school', 'position' => 0]);
        $hidden = EducationEntry::factory()->create(['key' => 'hidden-entry', 'position' => 2]);
        $this->makePublic($school, ['institution' => 'Instituto sintético', 'program_es' => 'Bachiller sintético', 'program_en' => 'Synthetic diploma', 'end_year' => 2021]);
        $this->makePublic($course, ['institution' => 'Centro sintético', 'program_es' => 'Curso sintético', 'program_en' => 'Synthetic course', 'detail_es' => 'Detalle', 'detail_en' => 'Detail', 'start_year' => 2022, 'end_year' => 2022]);
        $this->makePublic($hidden, ['institution' => 'Oculto', 'program_es' => 'Oculto', 'program_en' => 'Hidden', 'is_visible' => false]);

        $this->getJson('/api/v1/en/site')->assertJsonPath('data.education', [
            ['key' => 'school', 'institution' => 'Instituto sintético', 'program' => 'Synthetic diploma', 'detail' => null, 'start_year' => null, 'end_year' => 2021],
            ['key' => 'course', 'institution' => 'Centro sintético', 'program' => 'Synthetic course', 'detail' => 'Detail', 'start_year' => 2022, 'end_year' => 2022],
        ]);
    }
```

and add `EducationEntry::class` to the `in_array($model::class, [...])` list inside `makePublic()`.

Run: `... php artisan test --compact --filter=PublicApiContractTest`
Expected: FAIL — `data.education` is missing.

- [ ] **Step 8: Expose education on the site endpoint**

`api/app/Http/Controllers/Api/V1/SiteController.php`: add `use App\Models\EducationEntry;` and, in the `SiteResource` array, `'education' => EducationEntry::query()->publiclyAvailable()->get(),` after `work_principles`.

`api/app/Http/Resources/Api/V1/SiteResource.php`: extend the `@mixin` array shape with `education: iterable`, and add after `work_principles`:

```php
            'education' => collect($this['education'])->map(fn ($entry): array => [
                'key' => $entry->key,
                'institution' => $entry->institution,
                'program' => $entry->{"program_{$suffix}"},
                'detail' => $entry->{"detail_{$suffix}"},
                'start_year' => $entry->start_year,
                'end_year' => $entry->end_year,
            ])->values()->all(),
```

`docs/api/PUBLIC_API_V1.md`, site section: add to the JSON example after `work_principles`:

```json
    "education": [
      {"key": "string", "institution": "string", "program": "string", "detail": null, "start_year": null, "end_year": 2021}
    ],
```

and the table row after `work_principles`:

```markdown
| `education` | `array<object>` (always present, may be empty) | published+visible `EducationEntry` rows, ordered by `position` then `key`; `institution` is not translated; `program` from `program_{locale}` (required); `detail` from `detail_{locale}` (optional pair, `null` when empty); `start_year`/`end_year` are `int \| null` |
```

Run: `... php artisan test --compact --filter="PublicApiContractTest|LocalizedContentApiTest|ApiContractDocumentationTest"`
Expected: PASS.

- [ ] **Step 9: Format, run the suite and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint app database tests`
Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact`
Expected: all tests pass.

```bash
git add api docs/api/PUBLIC_API_V1.md
git commit -m "feat(cms): manage education entries and expose them on the site endpoint"
```

### Task 6: Languages

**Files:**
- Create: `api/database/migrations/2026_09_15_000004_create_languages_table.php`
- Create: `api/app/Enums/LanguageLevel.php`, `api/app/Models/Language.php`, `api/database/factories/LanguageFactory.php`
- Create: `api/app/Filament/Resources/Languages/LanguageResource.php`, `Schemas/LanguageForm.php`, `Tables/LanguagesTable.php`, `Pages/CreateLanguage.php`, `Pages/EditLanguage.php`, `Pages/ListLanguages.php`
- Create test: `api/tests/Feature/Filament/LanguageResourceTest.php`
- Modify: `PublicationValidator.php`, `EditorialMutationGuard.php`, `AppServiceProvider.php`, `PublicContentDependencies.php`, `ReviewLink.php`, `ReviewContent.php`, `PublicationReviewPresenter.php`
- Modify: `SiteController.php`, `SiteResource.php`, `docs/api/PUBLIC_API_V1.md`
- Modify tests: `PhaseSixSchemaTest`, `PublicationValidatorTest`, `PublicContentCacheTest`, `PublicationReviewPageTest`, `PublicApiContractTest`

**Interfaces:**
- Produces: `App\Enums\LanguageLevel` (`Native = 'native'`, `A1 = 'a1'`, `A2 = 'a2'`, `B1 = 'b1'`, `B2 = 'b2'`, `C1 = 'c1'`, `C2 = 'c2'`); `App\Models\Language` (`key`, `key_locked`, `position`, `name_es`, `name_en`, `level`, publication columns); factory states `draft()`, `publishedHidden()`, `publishedVisible()`; review type `language`; dependency `Language → [Site]`; API `site.languages: list<{key, name, level}>` ordered by `position`, `key`.

- [ ] **Step 1: Write the failing domain tests**

Append to `api/tests/Feature/Database/PhaseSixSchemaTest.php` (add `use App\Enums\LanguageLevel;`):

```php
    public function test_language_levels_are_a_closed_set(): void
    {
        $this->assertSame(['native', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'], array_column(LanguageLevel::cases(), 'value'));

        DB::table('languages')->insert([...$this->projectRow('english'), 'level' => 'b2']);
        $this->assertDatabaseHas('languages', ['key' => 'english', 'level' => 'b2']);

        $this->assertDatabaseRejects(fn () => DB::table('languages')->insert([...$this->projectRow('fluent'), 'level' => 'fluent']));
    }
```

Append to `api/tests/Feature/Domain/PublicationValidatorTest.php` (add `use App\Models\Language;`):

```php
    public function test_it_validates_languages(): void
    {
        $language = Language::factory()->publishedHidden()->make(['name_en' => ' ', 'level' => null]);

        $this->assertSame([
            ['code' => 'required_translation', 'path' => 'name_en'],
            ['code' => 'required', 'path' => 'level'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($language)));
        $this->assertSame([], app(PublicationValidator::class)->issues(Language::factory()->publishedHidden()->make()));
    }
```

In `api/tests/Feature/Cache/PublicContentCacheTest.php` add `use App\Models\Language;` and:

```php
        $this->assertSame([PublicEndpoint::Site], $dependencies->for(Language::class));
```

Run: `... php artisan test --compact --filter="PhaseSixSchemaTest|PublicationValidatorTest|PublicContentCacheTest"`
Expected: FAIL — class `App\Enums\LanguageLevel` not found.

- [ ] **Step 2: Enum, migration, model and factory**

`api/app/Enums/LanguageLevel.php`:

```php
<?php

namespace App\Enums;

enum LanguageLevel: string
{
    case Native = 'native';
    case A1 = 'a1';
    case A2 = 'a2';
    case B1 = 'b1';
    case B2 = 'b2';
    case C1 = 'c1';
    case C2 = 'c2';
}
```

`api/database/migrations/2026_09_15_000004_create_languages_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Structured language list for the About section (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.6).
 * `level` is nullable so drafts can be saved incomplete; publication requires it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->boolean('key_locked')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->string('name_es')->nullable();
            $table->string('name_en')->nullable();
            $table->enum('level', ['native', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'])->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_visible')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'is_visible', 'position', 'key']);
        });

        foreach ([
            'languages_publication_state_check' => "(status = 'draft' AND is_visible = 0 AND published_at IS NULL) OR (status = 'published' AND published_at IS NOT NULL)",
            'languages_key_locked_check' => "status <> 'published' OR key_locked = 1",
            'languages_position_check' => 'position >= 0',
        ] as $name => $expression) {
            DB::statement("ALTER TABLE languages ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
```

`api/app/Models/Language.php`:

```php
<?php

namespace App\Models;

use App\Enums\LanguageLevel;
use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Language extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'key_locked', 'position', 'name_es', 'name_en', 'level', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['key_locked' => 'boolean', 'position' => 'integer', 'level' => LanguageLevel::class, 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true)->orderBy('position')->orderBy('key');
    }
}
```

`api/database/factories/LanguageFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\LanguageLevel;
use App\Enums\PublicationStatus;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Language> */
final class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        return ['key' => 'synthetic-language-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'name_es' => null, 'name_en' => null, 'level' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null]);
    }

    public function publishedHidden(): static
    {
        return $this->state($this->publishedAttributes(false));
    }

    public function publishedVisible(): static
    {
        return $this->state($this->publishedAttributes(true));
    }

    private function publishedAttributes(bool $visible): array
    {
        return ['key_locked' => true, 'name_es' => 'Idioma sintético', 'name_en' => 'Synthetic language', 'level' => LanguageLevel::B2, 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
```

- [ ] **Step 3: Validator, guard, provider and dependencies**

`PublicationValidator.php`: add `use App\Models\Language;`; add `Language::class => $this->languageIssues($content),` to `issues()` after the `EducationEntry` arm; add:

```php
    /** @return list<PublicationIssue> */
    private function languageIssues(Language $language): array
    {
        return [
            ...$this->keyIssues($language),
            ...$this->requiredPairs($language, ['name']),
            ...$this->required($language, ['level']),
            ...$this->maxLengthPairs($language, ['name'], self::BOUNDED_MAX_LENGTH),
        ];
    }
```

`EditorialMutationGuard.php` and `AppServiceProvider.php`: add `use App\Models\Language;` and `Language::class` to their model lists.
`PublicContentDependencies.php`: add `use App\Models\Language;` and `Language::class,` to the `[PublicEndpoint::Site]` group.

Run: `... php artisan test --compact --filter="PhaseSixSchemaTest|PublicationValidatorTest|PublicContentCacheTest"`
Expected: PASS.

- [ ] **Step 4: Write the failing Filament test**

Create `api/tests/Feature/Filament/LanguageResourceTest.php`:

```php
<?php

namespace Tests\Feature\Filament;

use App\Enums\LanguageLevel;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Models\Language;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class LanguageResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_reach_language_pages_and_guests_cannot(): void
    {
        $language = Language::factory()->create();

        $this->get(ListLanguages::getUrl())->assertRedirect(route('filament.admin.auth.login'));

        $this->authenticateAdmin();
        $this->get(ListLanguages::getUrl())->assertOk();
        $this->get(CreateLanguage::getUrl())->assertOk();
        $this->get(EditLanguage::getUrl(['record' => $language]))->assertOk();
    }

    public function test_non_administrator_is_denied_the_language_list(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        $this->get(ListLanguages::getUrl())->assertForbidden();
    }

    public function test_administrator_creates_several_languages_as_ordered_drafts(): void
    {
        $this->authenticateAdmin();

        foreach (['spanish' => 'native', 'english' => 'b2'] as $key => $level) {
            Livewire::test(CreateLanguage::class)
                ->fillForm(['key' => $key, 'name_es' => 'Nombre', 'name_en' => 'Name', 'level' => $level])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $languages = Language::query()->orderBy('position')->get();
        $this->assertSame(['spanish', 'english'], $languages->pluck('key')->all());
        $this->assertSame([LanguageLevel::Native, LanguageLevel::B2], $languages->pluck('level')->all());
        $this->assertSame([0, 1], $languages->pluck('position')->all());
    }

    public function test_level_select_rejects_values_outside_the_enum(): void
    {
        $this->authenticateAdmin();

        Livewire::test(CreateLanguage::class)
            ->fillForm(['key' => 'synthetic-language', 'level' => 'fluent'])
            ->call('create')
            ->assertHasFormErrors(['level']);

        $this->assertSame(0, Language::query()->count());
    }

    public function test_publication_requires_both_names_and_a_level(): void
    {
        $language = Language::factory()->create(['name_es' => 'Inglés']);
        $this->authenticateAdmin();

        Livewire::test(EditLanguage::class, ['record' => $language->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');
        $this->assertSame(PublicationStatus::Draft, $language->refresh()->status);

        Livewire::test(EditLanguage::class, ['record' => $language->getKey()])
            ->fillForm(['name_en' => 'English', 'level' => 'b2'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->callAction('publish')
            ->assertNotified('Publish succeeded');
        $this->assertSame(PublicationStatus::Published, $language->refresh()->status);
    }

    private function authenticateAdmin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        return $admin;
    }
}
```

In `api/tests/Feature/Filament/PublicationReviewPageTest.php` add `use App\Filament\Resources\Languages\Pages\EditLanguage;` and `use App\Models\Language;`; add `'language' => ['language', fn () => Language::factory()->create()],` to `manageableEntities()` and `'language' => ['language', fn () => Language::factory()->create(), fn ($record) => EditLanguage::getUrl(['record' => $record])],` to `editUrlCases()`.

Run: `... php artisan test --compact --filter="LanguageResourceTest|PublicationReviewPageTest"`
Expected: FAIL — class `ListLanguages` not found.

- [ ] **Step 5: Filament resource**

`api/app/Filament/Resources/Languages/LanguageResource.php`:

```php
<?php

namespace App\Filament\Resources\Languages;

use App\Filament\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Filament\Resources\Languages\Schemas\LanguageForm;
use App\Filament\Resources\Languages\Tables\LanguagesTable;
use App\Filament\Support\ReviewLink;
use App\Models\Language;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|UnitEnum|null $navigationGroup = 'Profile and site';

    public static function form(Schema $schema): Schema
    {
        return LanguageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LanguagesTable::configure($table)->pushRecordActions([ReviewLink::rowAction()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLanguages::route('/'),
            'create' => CreateLanguage::route('/create'),
            'edit' => EditLanguage::route('/{record}/edit'),
        ];
    }
}
```

`api/app/Filament/Resources/Languages/Schemas/LanguageForm.php`:

```php
<?php

namespace App\Filament\Resources\Languages\Schemas;

use App\Enums\LanguageLevel;
use App\Enums\PublicationStatus;
use App\Models\Language;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Key')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->helperText('Lowercase slug (letters, digits, hyphens). Change later using the "Change key" action.')
                    ->disabledOn('edit'),

                Select::make('level')
                    ->label('Level')
                    ->options([
                        LanguageLevel::Native->value => 'Native',
                        LanguageLevel::A1->value => 'A1',
                        LanguageLevel::A2->value => 'A2',
                        LanguageLevel::B1->value => 'B1',
                        LanguageLevel::B2->value => 'B2',
                        LanguageLevel::C1->value => 'C1',
                        LanguageLevel::C2->value => 'C2',
                    ])
                    ->helperText('Required to publish.'),

                Tabs::make('locales')->tabs([
                    Tab::make('Spanish')->schema([
                        TextInput::make('name_es')->label('Name (ES)')->maxLength(255),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('name_en')->label('Name (EN)')->maxLength(255),
                    ]),
                ]),

                Placeholder::make('position_display')
                    ->label('Position')
                    ->content(fn (?Language $record): string => $record !== null ? (string) $record->position : 'Assigned on creation')
                    ->helperText('Use the "Change position" table action to reorder.'),
                Placeholder::make('key_locked_display')
                    ->label('Key locked')
                    ->content(fn (?Language $record): string => ($record?->key_locked ?? false) ? 'Yes' : 'No'),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?Language $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?Language $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?Language $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }
}
```

`api/app/Filament/Resources/Languages/Tables/LanguagesTable.php`:

```php
<?php

namespace App\Filament\Resources\Languages\Tables;

use App\Domain\Content\Actions\ReorderContent;
use App\Enums\LanguageLevel;
use App\Enums\PublicationStatus;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('key')->label('Key'),
                TextColumn::make('name_es')->label('Name (ES)'),
                TextColumn::make('name_en')->label('Name (EN)'),
                TextColumn::make('level')
                    ->label('Level')
                    ->formatStateUsing(fn (?LanguageLevel $state): string => $state === null ? '—' : strtoupper($state->value)),
                TextColumn::make('position')->label('Position')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PublicationStatus $state): string => ucfirst($state->value))
                    ->color(fn (PublicationStatus $state): string => $state === PublicationStatus::Published ? 'success' : 'gray'),
                TextColumn::make('is_visible')
                    ->label('Visibility')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Visible' : 'Hidden')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    PublicationStatus::Draft->value => 'Draft',
                    PublicationStatus::Published->value => 'Published',
                ]),
                TernaryFilter::make('is_visible')
                    ->label('Visibility')
                    ->trueLabel('Visible')
                    ->falseLabel('Hidden'),
            ])
            ->recordActions([
                self::reorderAction(),
                EditAction::make(),
            ]);
    }

    private static function reorderAction(): Action
    {
        return Action::make('reorder')
            ->label('Change position')
            ->icon('heroicon-o-arrows-up-down')
            ->schema([
                TextInput::make('position')
                    ->label('New position')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required()
                    ->default(fn (Model $record): int => $record->position),
            ])
            ->action(function (Model $record, array $data): void {
                try {
                    app(ReorderContent::class)($record, (int) $data['position']);
                } catch (\InvalidArgumentException $exception) {
                    Notification::make()->danger()->title('Reorder failed')->body($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('Position updated')->send();
            });
    }
}
```

`api/app/Filament/Resources/Languages/Pages/ListLanguages.php`:

```php
<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLanguages extends ListRecords
{
    protected static string $resource = LanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
```

`api/app/Filament/Resources/Languages/Pages/CreateLanguage.php`:

```php
<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Languages\LanguageResource;
use App\Models\Language;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLanguage extends CreateRecord
{
    protected static string $resource = LanguageResource::class;

    /**
     * Plain Eloquent create is permitted: the editorial mutation guard only
     * forbids creating already-published content. New languages are appended
     * after the current last position.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['status'] = PublicationStatus::Draft;
        $data['is_visible'] = false;
        $data['published_at'] = null;
        $data['key_locked'] = false;
        $data['position'] = (int) (Language::query()->max('position') ?? -1) + 1;

        return Language::query()->create($data);
    }
}
```

`api/app/Filament/Resources/Languages/Pages/EditLanguage.php`:

```php
<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Domain\Content\Actions\ChangePublicKey;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Languages\LanguageResource;
use App\Filament\Support\EditorialActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            EditorialActions::publish(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::show(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::hide(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::returnToDraft(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            $this->changeKeyAction(),
            DeleteAction::make()->using(function (Model $record): bool {
                app(DeleteContent::class)($record);

                return true;
            }),
        ];
    }

    private function changeKeyAction(): Action
    {
        return Action::make('change_key')
            ->label('Change key')
            ->color('warning')
            ->schema([
                TextInput::make('key')
                    ->label('New key')
                    ->required()
                    ->maxLength(100)
                    ->default(fn (Model $record): string => $record->key)
                    ->unique(table: 'languages', column: 'key', ignoreRecord: true),
            ])
            ->requiresConfirmation(fn (Model $record): bool => (bool) $record->key_locked)
            ->modalDescription('This key is already locked by a previous publication. Changing it affects any public references to it.')
            ->action(function (Model $record, array $data): void {
                try {
                    $updated = app(ChangePublicKey::class)($record, $data['key'], confirmed: true);
                } catch (PublicationValidationException $exception) {
                    $this->notifyValidationFailure('Change key', $exception);

                    return;
                } catch (\LogicException $exception) {
                    Notification::make()->danger()->title('Change key failed')->body($exception->getMessage())->send();

                    return;
                }

                $this->syncRecord($updated);
                Notification::make()->success()->title('Key changed')->send();
            });
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['key'], $data['key_locked'], $data['position'], $data['status'], $data['is_visible'], $data['published_at']);

        try {
            $updated = app(UpdateContent::class)($record, $data);
        } catch (PublicationValidationException $exception) {
            $this->notifyValidationFailure('Save', $exception);

            throw new Halt;
        }

        $this->record = $updated;

        if ($updated->status === PublicationStatus::Published && $updated->is_visible) {
            Notification::make()
                ->warning()
                ->title('Saved')
                ->body('This content is published and visible: the change is immediately public.')
                ->send();
        }

        return $updated;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        $record = $this->getRecord();

        if ($record->status === PublicationStatus::Published && $record->is_visible) {
            return null;
        }

        return 'Saved';
    }

    private function notifyValidationFailure(string $label, PublicationValidationException $exception): void
    {
        $body = collect($exception->issues())
            ->map(fn ($issue): string => "[{$issue->code}] {$issue->path}: {$issue->message}")
            ->implode("\n");

        Notification::make()
            ->danger()
            ->title("{$label} failed")
            ->body($body)
            ->send();
    }

    private function syncRecord(Model $updated): void
    {
        $this->record = $updated;
    }
}
```

- [ ] **Step 6: Review page glue**

`ReviewLink.php`: add `use App\Models\Language;` and `Language::class => 'language',` to `TYPES` after `EducationEntry::class`.
`ReviewContent.php`: add `use App\Filament\Resources\Languages\Pages\EditLanguage;` and the arm `'language' => EditLanguage::getUrl(['record' => $content]),`.
`PublicationReviewPresenter.php`: add `use App\Models\Language;`; add `Language::class => $this->language($content),` to `present()`; add `$this->statusBreakdown('Languages', Language::query()),` to the Site `dependencies` after `Education entries`; add:

```php
    /** @return array<string, mixed> */
    private function language(Language $language): array
    {
        return array_merge($this->base('Language', $language->key, $language->position, $language->status->value, $language->is_visible, $language->published_at?->toDateTimeString()), [
            'bilingual' => [
                ['label' => 'Name', 'es' => $language->name_es, 'en' => $language->name_en],
            ],
            'fields' => [
                ['label' => 'Level', 'value' => $language->level?->value],
            ],
            'issues' => $this->issues($language),
        ]);
    }
```

Run: `... php artisan test --compact --filter="LanguageResourceTest|PublicationReviewPageTest"`
Expected: PASS.

- [ ] **Step 7: Write the failing site API test**

In `api/tests/Feature/Api/V1/PublicApiContractTest.php` add `use App\Models\Language;`; add `'languages' => [],` after `'education' => [],` in the site exact JSON; add `Language::class` to the `makePublic()` keyed-model list; add:

```php
    public function test_site_resource_exposes_public_languages_in_order(): void
    {
        $this->publishSite();
        $english = Language::factory()->create(['key' => 'english', 'position' => 1]);
        $spanish = Language::factory()->create(['key' => 'spanish', 'position' => 0]);
        $this->makePublic($english, ['name_es' => 'Inglés', 'name_en' => 'English', 'level' => 'b2']);
        $this->makePublic($spanish, ['name_es' => 'Español', 'name_en' => 'Spanish', 'level' => 'native']);

        $this->getJson('/api/v1/es/site')->assertJsonPath('data.languages', [
            ['key' => 'spanish', 'name' => 'Español', 'level' => 'native'],
            ['key' => 'english', 'name' => 'Inglés', 'level' => 'b2'],
        ]);
    }
```

Run: `... php artisan test --compact --filter=PublicApiContractTest`
Expected: FAIL — `data.languages` is missing.

- [ ] **Step 8: Expose languages on the site endpoint**

`SiteController.php`: add `use App\Models\Language;` and `'languages' => Language::query()->publiclyAvailable()->get(),` after `education`.

`SiteResource.php`: extend the `@mixin` shape with `languages: iterable` and add after `education`:

```php
            'languages' => collect($this['languages'])->map(fn ($language): array => [
                'key' => $language->key,
                'name' => $language->{"name_{$suffix}"},
                'level' => $language->level->value,
            ])->values()->all(),
```

`docs/api/PUBLIC_API_V1.md`, site section: add after the `education` JSON block:

```json
    "languages": [
      {"key": "string", "name": "string", "level": "b2"}
    ],
```

and the row after `education`:

```markdown
| `languages` | `array<object>` (always present, may be empty) | published+visible `Language` rows, ordered by `position` then `key`; `name` from `name_{locale}`; `level` is `"native" \| "a1" \| "a2" \| "b1" \| "b2" \| "c1" \| "c2"` (`App\Enums\LanguageLevel`) |
```

Run: `... php artisan test --compact --filter="PublicApiContractTest|LocalizedContentApiTest|ApiContractDocumentationTest"`
Expected: PASS.

- [ ] **Step 9: Format, run the suite and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint app database tests`
Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact`
Expected: all tests pass.

```bash
git add api docs/api/PUBLIC_API_V1.md
git commit -m "feat(cms): manage languages and expose them on the site endpoint"
```

### Task 7: Profile location and work modes

**Files:**
- Create: `api/database/migrations/2026_09_15_000005_add_location_to_profiles.php`, `api/app/Enums/WorkMode.php`
- Modify: `api/app/Models/Profile.php`, `api/app/Domain/Publishing/PublicationValidator.php`, `api/app/Filament/Pages/EditProfile.php`, `api/app/Filament/Support/PublicationReviewPresenter.php`, `api/app/Domain/Content/InitialPortfolioImporter.php`, `api/app/Http/Resources/Api/V1/ProfileResource.php`, `docs/api/PUBLIC_API_V1.md`
- Modify tests: `PhaseSixSchemaTest`, `PublicationValidatorTest`, `SingletonPageTest`, `PublicApiContractTest`, `ImportInitialPortfolioContentTest`

**Interfaces:**
- Produces: `App\Enums\WorkMode` (`OnSite = 'on_site'`, `Hybrid = 'hybrid'`, `Remote = 'remote'`); `Profile::$location: ?string`, `Profile::$work_modes: ?list<string>` (cast `array`); API profile gains `location: string|null` and `work_modes: list<"on_site"|"hybrid"|"remote">` always in enum order.

- [ ] **Step 1: Write the failing tests**

Append to `api/tests/Feature/Database/PhaseSixSchemaTest.php` (add `use App\Enums\WorkMode;`):

```php
    public function test_work_modes_are_a_closed_set(): void
    {
        $this->assertSame(['on_site', 'hybrid', 'remote'], array_column(WorkMode::cases(), 'value'));
    }
```

Append to `api/tests/Feature/Domain/PublicationValidatorTest.php`:

```php
    public function test_it_validates_profile_location_and_work_modes(): void
    {
        $invalid = Profile::factory()->publishedHidden()->make(['location' => str_repeat('a', 256), 'work_modes' => ['remote', 'office']]);
        $duplicated = Profile::factory()->publishedHidden()->make(['work_modes' => ['remote', 'remote']]);
        $valid = Profile::factory()->publishedHidden()->make(['location' => 'Ciudad sintética', 'work_modes' => ['hybrid', 'remote']]);

        $codes = static fn (Profile $profile): array => array_map(
            static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path],
            app(PublicationValidator::class)->issues($profile),
        );

        $this->assertSame([
            ['code' => 'max_length_exceeded', 'path' => 'location'],
            ['code' => 'invalid_work_mode', 'path' => 'work_modes'],
        ], $codes($invalid));
        $this->assertSame([['code' => 'duplicate_work_mode', 'path' => 'work_modes']], $codes($duplicated));
        $this->assertSame([], $codes($valid));
    }
```

Append to `api/tests/Feature/Filament/SingletonPageTest.php` (add `use App\Enums\WorkMode;` if an enum is referenced; the test below uses plain strings):

```php
    public function test_profile_location_and_work_modes_are_editable(): void
    {
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->assertFormFieldExists('location')
            ->assertFormFieldExists('work_modes')
            ->fillForm(['location' => 'Ciudad sintética', 'work_modes' => ['remote', 'on_site']])
            ->call('save')
            ->assertNotified('Saved');

        $profile = Profile::query()->where('singleton_key', 'default')->firstOrFail();
        $this->assertSame('Ciudad sintética', $profile->location);
        $this->assertEqualsCanonicalizing(['remote', 'on_site'], $profile->work_modes);
    }
```

In `api/tests/Feature/Api/V1/PublicApiContractTest.php`:
- in both exact JSON blocks of `test_profile_resource_has_the_exact_public_shape_and_only_exposes_a_verified_public_photo`, add `'location' => null, 'work_modes' => [],` after `'name' => 'Luciano González',`;
- add:

```php
    public function test_profile_exposes_location_and_work_modes_in_canonical_order(): void
    {
        DB::table('profiles')->where('singleton_key', 'default')->update([
            'name' => 'Luciano González', 'headline_es' => 'Backend', 'headline_en' => 'Backend',
            'short_summary_es' => 'Resumen', 'short_summary_en' => 'Summary',
            'introduction_es' => 'Introducción', 'introduction_en' => 'Introduction',
            'availability_es' => 'Disponible', 'availability_en' => 'Available', 'cta_es' => 'Contacto', 'cta_en' => 'Contact',
            'location' => 'Ciudad sintética', 'work_modes' => json_encode(['remote', 'on_site']),
            'status' => PublicationStatus::Published->value, 'is_visible' => true, 'published_at' => now(),
        ]);

        $this->getJson('/api/v1/en/profile')
            ->assertJsonPath('data.location', 'Ciudad sintética')
            ->assertJsonPath('data.work_modes', ['on_site', 'remote']);
    }
```

In `api/tests/Feature/Console/ImportInitialPortfolioContentTest.php`, add to `pristineViolationProvider()`:

```php
            'profile location present' => ['profiles', ['location' => 'Ciudad sintética']],
```

Run: `... php artisan test --compact --filter="PhaseSixSchemaTest|PublicationValidatorTest|SingletonPageTest|PublicApiContractTest|ImportInitialPortfolioContentTest"`
Expected: FAIL — class `App\Enums\WorkMode` not found / unknown column `location`.

- [ ] **Step 2: Enum, migration and model**

`api/app/Enums/WorkMode.php`:

```php
<?php

namespace App\Enums;

/** Declaration order is the public order of `profile.work_modes`. */
enum WorkMode: string
{
    case OnSite = 'on_site';
    case Hybrid = 'hybrid';
    case Remote = 'remote';
}
```

`api/database/migrations/2026_09_15_000005_add_location_to_profiles.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * About section location and work modes (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.7).
 * The location is not translated; work modes are a JSON list of WorkMode values.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('location')->nullable()->after('name');
            $table->json('work_modes')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['location', 'work_modes']);
        });
    }
};
```

`api/app/Models/Profile.php`: add `'location', 'work_modes',` to `$fillable` right after `'name'`, and `'work_modes' => 'array'` to `casts()`.

- [ ] **Step 3: Validator**

In `PublicationValidator.php` add `use App\Enums\WorkMode;`; in `profileIssues()` add after the `required($profile, ['name'])` line:

```php
            ...$this->maxLength($profile, ['location'], self::BOUNDED_MAX_LENGTH),
            ...$this->workModeIssues($profile),
```

and add:

```php
    /** @return list<PublicationIssue> */
    private function workModeIssues(Profile $profile): array
    {
        $modes = $profile->work_modes;
        if ($modes === null) {
            return [];
        }

        $allowed = array_column(WorkMode::cases(), 'value');
        if (! is_array($modes) || ! array_is_list($modes) || array_diff($modes, $allowed) !== []) {
            return [$this->issue('invalid_work_mode', 'work_modes', 'Work modes must be a list of on_site, hybrid, or remote.')];
        }
        if (count($modes) !== count(array_unique($modes))) {
            return [$this->issue('duplicate_work_mode', 'work_modes', 'Each work mode can be selected only once.')];
        }

        return [];
    }
```

- [ ] **Step 4: Admin page, presenter and importer**

`api/app/Filament/Pages/EditProfile.php`: add `use App\Enums\WorkMode;` and `use Filament\Forms\Components\CheckboxList;`; insert right after the `name` TextInput:

```php
                TextInput::make('location')
                    ->label('Location')
                    ->maxLength(255)
                    ->helperText('Optional. Shown as written in both languages (for example "Mar del Plata, Argentina").'),
                CheckboxList::make('work_modes')
                    ->label('Work modes')
                    ->options([
                        WorkMode::OnSite->value => 'On site',
                        WorkMode::Hybrid->value => 'Hybrid',
                        WorkMode::Remote->value => 'Remote',
                    ])
                    ->columns(3)
                    ->helperText('Optional. The public site always lists them in this order.'),
```

`PublicationReviewPresenter.php`, in `profile()`, replace the `fields` array with:

```php
            'fields' => [
                ['label' => 'Name', 'value' => $profile->name],
                ['label' => 'Location', 'value' => $profile->location],
                ['label' => 'Work modes', 'value' => implode(', ', $profile->work_modes ?? [])],
            ],
```

`InitialPortfolioImporter.php`: in `PROFILE_EDITORIAL_COLUMNS` replace `'name', 'headline_es',` with `'name', 'location', 'work_modes', 'headline_es',`.

- [ ] **Step 5: API**

`api/app/Http/Resources/Api/V1/ProfileResource.php`: add `use App\Enums\WorkMode;`; update the `@return` shape with `location: ?string, work_modes: list<string>`; add after `'name' => $this->name,`:

```php
            'location' => $this->location,
            'work_modes' => $this->workModes(),
```

and the method:

```php
    /** @return list<string> Selected modes in WorkMode declaration order. */
    private function workModes(): array
    {
        $selected = is_array($this->work_modes) ? $this->work_modes : [];

        return array_values(array_filter(
            array_map(static fn (WorkMode $mode): string => $mode->value, WorkMode::cases()),
            static fn (string $mode): bool => in_array($mode, $selected, true),
        ));
    }
```

`docs/api/PUBLIC_API_V1.md`, profile section: add `"location": null,` and `"work_modes": ["on_site", "hybrid", "remote"],` after `"name": "string",` in the example; add the rows after `name`:

```markdown
| `location` | `string \| null` | `location` (not translated) |
| `work_modes` | `array<"on_site" \| "hybrid" \| "remote">` (always present, may be empty) | `work_modes` JSON; always emitted in `App\Enums\WorkMode` declaration order |
```

Replace "There is no `location` field." with nothing (delete the sentence) and remove "`location`," from the "What is never exposed" list.

Run: `... php artisan test --compact --filter="PhaseSixSchemaTest|PublicationValidatorTest|SingletonPageTest|PublicApiContractTest|ImportInitialPortfolioContentTest|LocalizedContentApiTest"`
Expected: PASS.

- [ ] **Step 6: Format, run the suite and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint app database tests`
Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact`
Expected: all tests pass.

```bash
git add api docs/api/PUBLIC_API_V1.md
git commit -m "feat(api): add profile location and work modes"
```

### Task 8: Draft content for Coned, projects, education and languages

Loads the spec §9 content as **drafts** through domain actions so Luciano reviews and publishes it in Filament. Sources: the approved CVs (`docs/content/approved-assets/cv-es.pdf`, `cv-en.pdf`), LinkedIn decisions D27 (organization "Coned", roles "Backend Engineer" Sept 2025–present and "Pasante" May–Sept 2025) and the public ReservaHub README. Trucks and Drinks is created with only its approved facts (client, backend role, in use); its problem/solution/result texts and screenshots stay empty until Luciano supplies them.

**Files:**
- Create: `api/database/seeders/PhaseSixDraftContentSeeder.php`
- Create test: `api/tests/Feature/Database/PhaseSixDraftContentSeederTest.php`

**Interfaces:**
- Consumes: `UpdateExperienceAggregate`, `UpdateContentWithTechnologies`, `UpdateContent` (existing); `EducationEntry`, `Language`, `ProjectKind`, `ProjectDeliveryStatus`, `LanguageLevel`, `WorkMode`, `WorkCase::experience_id`, `Profile::location/work_modes` (Tasks 1–7).
- Produces: explicit, idempotent seeder `php artisan db:seed --class=PhaseSixDraftContentSeeder`. Keys: technologies `sanctum`, `composer`, `inertia`, `typescript`, `javascript`, `html5`, `css`, `tailwindcss`, `bootstrap`, `github-actions`, `phpunit`, `git`, `java`, `python`, `c`; experiences `coned-backend-engineer`, `coned-intern`; projects `trucks-and-drinks`, `reservahub`; education `instituto-argentino-modelo`, `cfp-401`; languages `spanish`, `english`.

- [ ] **Step 1: Write the failing test**

Create `api/tests/Feature/Database/PhaseSixDraftContentSeederTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use App\Enums\LanguageLevel;
use App\Enums\ProjectKind;
use App\Enums\PublicationStatus;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Technology;
use App\Models\WorkCase;
use Database\Seeders\PhaseSixDraftContentSeeder;
use Database\Seeders\PortfolioContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PhaseSixDraftContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_loads_the_phase_six_content_as_idempotent_drafts(): void
    {
        $this->seed(PortfolioContentSeeder::class);

        $this->seed(PhaseSixDraftContentSeeder::class);
        $this->seed(PhaseSixDraftContentSeeder::class);

        $current = Experience::query()->where('key', 'coned-backend-engineer')->firstOrFail();
        $intern = Experience::query()->where('key', 'coned-intern')->firstOrFail();
        $this->assertSame(['Backend Engineer', 'Coned', 2025, 9, null], [$current->role_en, $current->organization_label_en, $current->start_year, $current->start_month, $current->end_year]);
        $this->assertSame(['Pasante', 'Intern', 2025, 5, 2025, 9], [$intern->role_es, $intern->role_en, $intern->start_year, $intern->start_month, $intern->end_year, $intern->end_month]);
        $this->assertCount(6, $current->highlights);
        $this->assertCount(2, $intern->highlights);

        $this->assertSame(4, WorkCase::query()->where('experience_id', $current->id)->count());

        $reservaHub = Project::query()->where('key', 'reservahub')->firstOrFail();
        $this->assertSame(ProjectKind::Personal, $reservaHub->kind);
        $this->assertSame('public_demo', $reservaHub->delivery_status->value);
        $this->assertSame('https://reservahub.lucianogonzalez.dev', $reservaHub->demo_url);
        $this->assertSame('https://github.com/Gonzalez-Luciano/ReservaHub', $reservaHub->repository_url);
        $this->assertCount(9, $reservaHub->technologies);

        $trucks = Project::query()->where('key', 'trucks-and-drinks')->firstOrFail();
        $this->assertSame([ProjectKind::Client, 'Trucks and Drinks', 'in_use', null], [$trucks->kind, $trucks->client_name, $trucks->delivery_status->value, $trucks->problem_es]);

        $this->assertSame(['instituto-argentino-modelo', 'cfp-401'], EducationEntry::query()->orderBy('position')->pluck('key')->all());
        $this->assertSame([LanguageLevel::Native, LanguageLevel::B2], Language::query()->orderBy('position')->pluck('level')->all());

        $profile = Profile::query()->where('singleton_key', 'default')->firstOrFail();
        $this->assertSame('Mar del Plata, Argentina', $profile->location);
        $this->assertSame(['on_site', 'hybrid', 'remote'], $profile->work_modes);

        $this->assertSame(1, Technology::query()->where('key', 'phpunit')->count());
        foreach ([Experience::class, Project::class, EducationEntry::class, Language::class, Technology::class] as $model) {
            $this->assertSame(0, $model::query()->where('status', PublicationStatus::Published)->count(), "{$model} must stay draft.");
        }
    }
}
```

Run: `... php artisan test --compact --filter=PhaseSixDraftContentSeederTest`
Expected: FAIL — `Target class [Database\Seeders\PhaseSixDraftContentSeeder] does not exist.`

- [ ] **Step 2: Write the seeder**

`api/database/seeders/PhaseSixDraftContentSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Content\Actions\UpdateContentWithTechnologies;
use App\Domain\Content\Actions\UpdateExperienceAggregate;
use App\Enums\LanguageLevel;
use App\Enums\ProjectDeliveryStatus;
use App\Enums\ProjectKind;
use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use App\Enums\WorkMode;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Technology;
use App\Models\WorkCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Phase 6 draft content (spec 2026-09-14-phase-6-portfolio-redesign-design.md §9).
 *
 * Every row is created as draft, hidden, unpublished through the same domain
 * actions Filament uses, and nothing is ever overwritten: an existing key is
 * skipped, a work case keeps a link someone already set, and the profile keeps
 * a location someone already typed. Publication is a human review step.
 *
 * Sources: approved CVs (docs/content/approved-assets/cv-*.pdf), the LinkedIn
 * role names and dates recorded as decision D27, and the public ReservaHub
 * README. Trucks and Drinks carries only its approved facts.
 *
 * Run explicitly, never from DatabaseSeeder::run():
 *   php artisan db:seed --class=PhaseSixDraftContentSeeder
 */
final class PhaseSixDraftContentSeeder extends Seeder
{
    private const DRAFT = ['status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null, 'key_locked' => false];

    /** Work cases approved in Phase 1 that describe the current Coned role. */
    private const CURRENT_ROLE_CASES = ['integrations-synchronization', 'education-management', 'data-and-automation', 'cross-layer-integration'];

    public function run(): void
    {
        $this->technologies();
        $this->experiences();
        $this->linkWorkCases();
        $this->projects();
        $this->education();
        $this->languages();
        $this->profile();
    }

    private function technologies(): void
    {
        $rows = [
            ['sanctum', 'Sanctum', TechnologyCategory::Backend],
            ['composer', 'Composer', TechnologyCategory::Backend],
            ['java', 'Java', TechnologyCategory::Backend],
            ['python', 'Python', TechnologyCategory::Backend],
            ['c', 'C', TechnologyCategory::Backend],
            ['github-actions', 'GitHub Actions', TechnologyCategory::Integration],
            ['phpunit', 'PHPUnit', TechnologyCategory::Integration],
            ['git', 'Git', TechnologyCategory::Integration],
            ['inertia', 'Inertia', TechnologyCategory::Collaboration],
            ['typescript', 'TypeScript', TechnologyCategory::Collaboration],
            ['javascript', 'JavaScript', TechnologyCategory::Collaboration],
            ['html5', 'HTML5', TechnologyCategory::Collaboration],
            ['css', 'CSS', TechnologyCategory::Collaboration],
            ['tailwindcss', 'TailwindCSS', TechnologyCategory::Collaboration],
            ['bootstrap', 'Bootstrap', TechnologyCategory::Collaboration],
        ];

        foreach ($rows as [$key, $name, $category]) {
            $this->createDraft(Technology::class, $key, ['name' => $name, 'category' => $category]);
        }
    }

    private function experiences(): void
    {
        $current = $this->createDraft(Experience::class, 'coned-backend-engineer', [
            'organization_label_es' => 'Coned', 'organization_label_en' => 'Coned',
            'role_es' => 'Backend Engineer', 'role_en' => 'Backend Engineer',
            'start_year' => 2025, 'start_month' => 9, 'end_year' => null, 'end_month' => null,
            'summary_es' => 'Desarrollo backend en PHP y Laravel sobre una plataforma de gestión educativa multiinstitución: APIs REST, lógica de negocio y módulos financieros y académicos.',
            'summary_en' => 'Backend development in PHP and Laravel on a multi-institution education management platform: REST APIs, business logic, and financial and academic modules.',
        ]);
        if ($current !== null) {
            app(UpdateExperienceAggregate::class)($current, [], $this->highlights([
                ['Integré los medios de pago SIRO, CES y Mercado Pago sobre Laravel, implementando sincronización de pagos, actualización automática de comprobantes y habilitación de alumnos según su estado de cuenta.', 'Integrated the SIRO, CES, and Mercado Pago payment gateways using Laravel, implementing payment synchronization, automatic receipt updates, and student account activation based on balance status.'],
                ['Desarrollé endpoints REST, validaciones y lógica de negocio en PHP/Laravel para módulos financieros: facturación, deudas, cuentas corrientes y comprobantes.', 'Developed REST endpoints, validations, and business logic in PHP/Laravel for financial modules: invoicing, outstanding debt, current accounts, and receipts.'],
                ['Mantengo y modernizo módulos académicos (mesas de examen, alumnos, materias, cursos, comisiones e inscripciones) dentro de una plataforma multiinstitución.', 'Maintain and modernize academic modules (exam boards, students, subjects, courses, class groups, and enrollments) within a multi-institution platform.'],
                ['Optimicé consultas SQL sobre MySQL y refactoricé código legacy, mejorando el rendimiento de procesos con alto volumen de datos.', 'Optimized SQL queries on MySQL and refactored legacy code, improving performance on high-volume data processes.'],
                ['Automaticé procesos internos mediante comandos Artisan y tareas programadas, e implementé control de acceso por roles y permisos.', 'Automated internal processes through Artisan commands and scheduled tasks, and implemented role- and permission-based access control.'],
                ['Integré endpoints backend con el frontend Angular y resolví incidencias de producción mediante debugging y análisis de errores.', 'Integrated backend endpoints with the Angular frontend and resolved production incidents through debugging and error analysis.'],
            ]), $this->technologyPivot(['php', 'laravel', 'mysql', 'rest-apis', 'angular']));
        }

        $intern = $this->createDraft(Experience::class, 'coned-intern', [
            'organization_label_es' => 'Coned', 'organization_label_en' => 'Coned',
            'role_es' => 'Pasante', 'role_en' => 'Intern',
            'start_year' => 2025, 'start_month' => 5, 'end_year' => 2025, 'end_month' => 9,
            'summary_es' => 'Pasantía como desarrollador full stack en productos internos de la plataforma educativa.',
            'summary_en' => 'Full stack developer internship on internal products of the education platform.',
        ]);
        if ($intern !== null) {
            app(UpdateExperienceAggregate::class)($intern, [], $this->highlights([
                ['Desarrollé una plataforma de onboarding para la creación y configuración de nuevas instituciones educativas, simplificando el proceso de alta en el sistema.', 'Built an onboarding platform for creating and configuring new educational institutions, streamlining the system setup process.'],
                ['Participé en la construcción de un sistema de soporte con gestión de tickets, CRM y estadísticas de uso de la aplicación.', 'Contributed to a support system featuring ticket management, CRM, and application usage statistics.'],
            ]), $this->technologyPivot(['php', 'laravel']));
        }
    }

    private function linkWorkCases(): void
    {
        $experience = Experience::query()->where('key', 'coned-backend-engineer')->first();
        if ($experience === null) {
            return;
        }

        WorkCase::query()->whereIn('key', self::CURRENT_ROLE_CASES)->whereNull('experience_id')->with('technologies')->get()
            ->each(function (WorkCase $workCase) use ($experience): void {
                $technologies = $workCase->technologies->values()->map(static fn (Technology $technology, int $position): array => [
                    'technology_id' => $technology->getKey(),
                    'position' => $position,
                ])->all();

                app(UpdateContentWithTechnologies::class)($workCase, ['experience_id' => $experience->getKey()], $technologies);
            });
    }

    private function projects(): void
    {
        $this->createDraft(Project::class, 'trucks-and-drinks', [
            'kind' => ProjectKind::Client, 'client_name' => 'Trucks and Drinks',
            'title_es' => 'Trucks and Drinks', 'title_en' => 'Trucks and Drinks',
            'role_es' => 'Backend', 'role_en' => 'Backend',
            'delivery_status' => ProjectDeliveryStatus::InUse,
        ]);

        $reservaHub = $this->createDraft(Project::class, 'reservahub', [
            'kind' => ProjectKind::Personal, 'client_name' => null,
            'title_es' => 'ReservaHub', 'title_en' => 'ReservaHub',
            'role_es' => 'Full stack', 'role_en' => 'Full stack',
            'delivery_status' => ProjectDeliveryStatus::PublicDemo,
            'summary_es' => 'SaaS de reservas por turnos multi-tenant.',
            'summary_en' => 'Multi-tenant appointment booking SaaS.',
            'problem_es' => 'Cualquier negocio que vende tiempo en franjas horarias necesita una regla simple y difícil de romper: que dos personas no puedan reservar el mismo turno.',
            'problem_en' => 'Any business that sells time in slots needs a simple rule that is hard to break: two people must never book the same slot.',
            'solution_es' => 'Motor de disponibilidad que combina horarios de empleados, pausas, licencias y feriados; la prevención de solapamientos se re-valida dentro de la transacción con advisory locks de PostgreSQL. Pasarela de pagos simulada con webhooks idempotentes y un comando de reconciliación, multi-tenancy por global scope y policies, tiempo real con Laravel Reverb y API REST con Sanctum.',
            'solution_en' => 'An availability engine that combines employee schedules, breaks, time off, and holidays; overlap prevention is re-validated inside the transaction with PostgreSQL advisory locks. A simulated payment gateway with idempotent webhooks and a reconciliation command, multi-tenancy through global scopes and policies, real-time updates with Laravel Reverb, and a REST API authenticated with Sanctum.',
            'result_es' => 'Demo pública y reiniciable, con suite de PHPUnit que incluye tests de concurrencia para reservas simultáneas y CI en GitHub Actions.',
            'result_en' => 'A public, resettable demo with a PHPUnit suite that includes concurrency tests for simultaneous bookings, and CI on GitHub Actions.',
            'demo_url' => 'https://reservahub.lucianogonzalez.dev',
            'repository_url' => 'https://github.com/Gonzalez-Luciano/ReservaHub',
        ]);
        if ($reservaHub !== null) {
            app(UpdateContentWithTechnologies::class)($reservaHub, [], $this->technologyPivot([
                'laravel', 'php', 'postgresql', 'redis', 'react', 'inertia', 'docker', 'phpunit', 'github-actions',
            ]));
        }
    }

    private function education(): void
    {
        $this->createDraft(EducationEntry::class, 'instituto-argentino-modelo', [
            'institution' => 'Instituto Argentino Modelo',
            'program_es' => 'Bachiller en Economía y Administración',
            'program_en' => 'High School Diploma in Economics and Administration',
            'end_year' => 2021,
        ]);
        $this->createDraft(EducationEntry::class, 'cfp-401', [
            'institution' => 'CFP N°401',
            'program_es' => 'Curso de Programación',
            'program_en' => 'Programming Course',
            'detail_es' => 'Introducción a C, fundamentos de Python y MySQL',
            'detail_en' => 'Introduction to C, Python and MySQL fundamentals',
            'start_year' => 2022,
            'end_year' => 2022,
        ]);
    }

    private function languages(): void
    {
        $this->createDraft(Language::class, 'spanish', ['name_es' => 'Español', 'name_en' => 'Spanish', 'level' => LanguageLevel::Native]);
        $this->createDraft(Language::class, 'english', ['name_es' => 'Inglés', 'name_en' => 'English', 'level' => LanguageLevel::B2]);
    }

    private function profile(): void
    {
        $profile = Profile::query()->where('singleton_key', 'default')->firstOrFail();
        if ($profile->location !== null) {
            return;
        }

        app(UpdateContent::class)($profile, [
            'location' => 'Mar del Plata, Argentina',
            'work_modes' => array_map(static fn (WorkMode $mode): string => $mode->value, WorkMode::cases()),
        ]);
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel|null the new draft, or null when the key already exists
     */
    private function createDraft(string $model, string $key, array $attributes): ?Model
    {
        if ($model::query()->where('key', $key)->exists()) {
            return null;
        }

        return $model::query()->create([
            ...$attributes,
            ...self::DRAFT,
            'key' => $key,
            'position' => (int) ($model::query()->max('position') ?? -1) + 1,
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pairs
     * @return list<array{content_es: string, content_en: string, position: int}>
     */
    private function highlights(array $pairs): array
    {
        return array_map(
            static fn (array $pair, int $position): array => ['content_es' => $pair[0], 'content_en' => $pair[1], 'position' => $position],
            $pairs,
            array_keys($pairs),
        );
    }

    /**
     * Existing technologies only, in the given order.
     *
     * @param  list<string>  $keys
     * @return list<array{technology_id: int, position: int}>
     */
    private function technologyPivot(array $keys): array
    {
        $ids = Technology::query()->whereIn('key', $keys)->pluck('id', 'key');

        return array_values(array_map(
            static fn (string $key, int $position): array => ['technology_id' => (int) $ids[$key], 'position' => $position],
            array_values(array_filter($keys, static fn (string $key): bool => $ids->has($key))),
            array_keys(array_values(array_filter($keys, static fn (string $key): bool => $ids->has($key)))),
        ));
    }
}
```

- [ ] **Step 3: Run the test**

Run: `... php artisan test --compact --filter=PhaseSixDraftContentSeederTest`
Expected: PASS.

- [ ] **Step 4: Format and commit**

Run: `export GATEWAY_PORT=8016 && docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint database tests`

```bash
git add api/database/seeders/PhaseSixDraftContentSeeder.php api/tests/Feature/Database/PhaseSixDraftContentSeederTest.php
git commit -m "feat(content): load phase 6 experience, project, education and language drafts"
```

### Task 9: Documentation, full verification and development data

**Files:**
- Modify: `docs/ARCHITECTURE.md`, `ROADMAP.md`, `docs/content/ASSET_INVENTORY.md`

**Interfaces:**
- Consumes: everything from Tasks 1–8.
- Produces: documentation matching the implemented CMS; the `portfolio-phase6` development database migrated and loaded with the Phase 6 drafts.

- [ ] **Step 1: Update the content model in `docs/ARCHITECTURE.md`**

In the section `## Modelo de contenido`, replace the whole `### Project` block (from `### Project` up to the line `La demo URL puede apuntar a subdominios alojados en el mismo servidor.`) with:

```markdown
### Project

- Tipo: para cliente o personal (`kind`), con nombre del cliente solo en proyectos para clientes.
- Título, rol y estado de entrega (`delivery_status`: en producción, en uso, demo pública, en desarrollo).
- Resumen, problema, solución y resultado.
- Galería ordenada de hasta 12 capturas con alt ES/EN (`project_images`).
- Tecnologías.
- Demo URL.
- Repository URL.
- Destacado.
- Publicación.
- Orden.

La demo URL puede apuntar a subdominios alojados en el mismo servidor.

### Education y Language

- Formación: institución, programa y detalle opcional (ES/EN), años opcionales.
- Idiomas: nombre (ES/EN) y nivel (`native`, `a1`…`c2`).
- Ambas son colecciones ordenadas y publicables que se exponen dentro de `site`.
```

Then insert this section immediately before the line `## Fase 5 — Sitio público (implementado; reemplazado en Fase 6)`:

```markdown
## Fase 6 — CMS del portfolio (implementado)

Spec: `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md` (secciones 4 y 5). Todo cambio es aditivo sobre el modelo de Fase 4 y conserva sus reglas: estados `draft`/`published`, `is_visible`, `key_locked`, validación bilingüe, guard de mutaciones y caché por endpoint.

- **Project** suma `kind`, `client_name`, `role_*`, `delivery_status` y `result_*`. Un check de MySQL (`projects_client_name_kind_check`) impide guardar un cliente en un proyecto personal; publicar exige rol, resultado, estado de entrega y, si es para cliente, el nombre del cliente.
- **Galería (`project_images`)**: reemplaza la imagen única (migrada a la posición 0). Filas escritas solo por `ProjectGalleryService` (acción `SyncProjectImages`) o `AssetLifecycleService`; el guard rechaza cualquier otro cambio. `sync()` valida la lista propuesta (máximo 12, IDs del mismo proyecto, alt ≤ 500), guarda originales privados, aplica las reglas de publicación sobre la galería propuesta, confirma filas en una transacción y solo entonces copia públicos si el proyecto está publicado y visible. Un fallo de copia restaura las filas anteriores y descarta las subidas. Mostrar un proyecto copia cada captura; ocultar, volver a borrador o borrar retira las copias (y al borrar, los originales).
- **WorkCase** tiene `experience_id` opcional (`ON DELETE SET NULL`); la API expone `experience_key` solo si la experiencia es pública. Cambiar una Experience invalida `experiences` y `work-cases`.
- **EducationEntry** y **Language** son recursos Filament propios en "Profile and site", con revisión, reorden, cambio de clave y borrado como las demás colecciones; invalidan `site`.
- **Profile** suma `location` (sin traducir) y `work_modes` (JSON de `on_site`, `hybrid`, `remote`; la API los emite en ese orden).
- **Contenido inicial de Fase 6**: `php artisan db:seed --class=PhaseSixDraftContentSeeder` crea borradores (experiencias de Coned, vínculo de casos, ReservaHub, Trucks and Drinks sin textos ni capturas, formación, idiomas, tecnologías del CV, ubicación y modalidades) mediante acciones de dominio. Es idempotente, nunca publica y nunca pisa datos existentes.
```

- [ ] **Step 2: Update `ROADMAP.md`**

In `# Fase 6 — Sistema de movimiento`, insert after the `### Escena de scroll con video (nuevo front base en `web/`)` checklist (before `### Motion`):

```markdown
### CMS y API del portfolio

Spec: `docs/superpowers/specs/2026-09-14-phase-6-portfolio-redesign-design.md`. Plan: `docs/superpowers/plans/2026-09-14-phase-6-cms-api.md`.

- [x] Proyectos para clientes y personales: tipo, cliente, rol, estado de entrega y resultado.
- [x] Galería ordenada de hasta 12 capturas por proyecto, editable en Filament.
- [x] Casos vinculados a su experiencia (`experience_key`).
- [x] Formación e idiomas como colecciones administrables.
- [x] Ubicación y modalidades de trabajo en Profile.
- [x] Borradores de contenido de Fase 6 cargados con acciones de dominio.
- [ ] Revisión y publicación humana de los borradores en Filament.
- [ ] Textos de problema, solución y resultado y capturas de Trucks and Drinks (los aporta Luciano).
```

- [ ] **Step 3: Update `docs/content/ASSET_INVENTORY.md`**

Replace the `AST-PROJECT-MEDIA` row with:

```markdown
| AST-PROJECT-MEDIA | Project screenshots (ordered gallery, up to 12 per project) | ES / EN | No asset supplied yet; Trucks and Drinks screenshots pending from Luciano | `/storage/projects/{uuid}.{jpg,png,webp}` public copies created by the CMS only while the project is published and visible | **Absent — the CMS gallery exists (Phase 6), no screenshot has been uploaded** | Required `alt_es`/`alt_en` per screenshot (max 500 characters) before publication | `PUB-030` to `PUB-033`: artifact-specific confidentiality and hidden-data review is required before any screenshot is uploaded to a project that will be published. |
```

- [ ] **Step 4: Run the full verification**

Run each command and read its output before continuing:

```bash
export GATEWAY_PORT=8016
docker compose -p portfolio-phase6 run --rm --no-deps api ./vendor/bin/pint --test
docker compose -p portfolio-phase6 --profile test run --rm api-test php artisan test --compact
node infra/validation/validate-repository.mjs
git diff --stat main...HEAD
```

Expected: Pint reports no files to fix; every test passes; the validator exits 0. Read the full branch diff once and confirm (Global Constraints) that no file names the external design reference or its author.

- [ ] **Step 5: Migrate and load the development stack**

```bash
export GATEWAY_PORT=8016
docker compose -p portfolio-phase6 up -d
docker compose -p portfolio-phase6 exec api php artisan migrate
docker compose -p portfolio-phase6 exec api php artisan db:seed --class=PhaseSixDraftContentSeeder
docker compose -p portfolio-phase6 exec api php artisan tinker --execute="echo App\Models\Project::count().' projects, '.App\Models\EducationEntry::count().' education, '.App\Models\Language::count().' languages';"
```

Expected: migrations `2026_09_15_000000` to `2026_09_15_000005` run; the tinker line prints at least `2 projects, 2 education, 2 languages`.

- [ ] **Step 6: Manual Filament check (http://127.0.0.1:8016/admin)**

Record the result of each item in the final report:

1. Projects: create one client and one personal project; the client field appears only for "Client project".
2. Edit a project: add three screenshots, drag to reorder, remove one, save; the order persists.
3. Try adding a 13th screenshot: the repeater does not allow it.
4. Work cases: the "Experience" select lists `Coned · Backend Engineer · 09/2025 – present`.
5. Education and Languages appear under "Profile and site" with list, create, edit, review and reorder.
6. Profile shows Location and Work modes.

- [ ] **Step 7: Commit**

```bash
git add docs/ARCHITECTURE.md ROADMAP.md docs/content/ASSET_INVENTORY.md
git commit -m "docs: record the phase 6 portfolio CMS"
```

