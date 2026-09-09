<?php

namespace Tests\Feature\Database;

use App\Enums\OwnedAssetKind;
use App\Enums\ProfessionalLinkType;
use App\Enums\PublicationStatus;
use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Enums\TechnologyCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PhaseFourSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_enums_expose_only_the_approved_values(): void
    {
        $this->assertSame(['draft', 'published'], array_column(PublicationStatus::cases(), 'value'));
        $this->assertSame(['es', 'en'], array_column(SupportedLocale::cases(), 'value'));
        $this->assertSame(['linkedin', 'github', 'email'], array_column(ProfessionalLinkType::cases(), 'value'));
        $this->assertSame(['backend', 'data', 'integration', 'collaboration'], array_column(TechnologyCategory::cases(), 'value'));
        $this->assertSame(['profile', 'site', 'experiences', 'work-cases', 'projects', 'technologies'], array_column(PublicEndpoint::cases(), 'value'));
        $this->assertSame(['profile-photo', 'project-image', 'technology-icon', 'cv'], array_column(OwnedAssetKind::cases(), 'value'));
    }

    public function test_migrations_create_exactly_one_empty_draft_hidden_singleton_of_each_kind(): void
    {
        $this->assertDatabaseCount('profiles', 1);
        $this->assertDatabaseHas('profiles', [
            'singleton_key' => 'default',
            'status' => 'draft',
            'is_visible' => false,
            'published_at' => null,
            'name' => null,
        ]);
        $this->assertDatabaseCount('site_configurations', 1);
        $this->assertDatabaseHas('site_configurations', [
            'singleton_key' => 'default',
            'status' => 'draft',
            'is_visible' => false,
            'published_at' => null,
        ]);

        $this->assertDatabaseRejects(fn () => DB::table('profiles')->insert(['singleton_key' => 'alternate']));
        $this->assertDatabaseRejects(fn () => DB::table('site_configurations')->insert(['singleton_key' => 'default']));
    }

    public function test_publishable_and_owned_asset_checks_reject_invalid_local_states(): void
    {
        $this->assertDatabaseRejects(fn () => DB::table('work_cases')->insert([
            ...$this->keyedRow('invalid-draft-state'),
            'is_visible' => true,
        ]));
        $this->assertDatabaseRejects(fn () => DB::table('projects')->insert([
            ...$this->keyedRow('incomplete-image'),
            'image_private_path' => 'projects/private.png',
        ]));
        $this->assertDatabaseRejects(fn () => DB::table('technologies')->insert([
            ...$this->keyedRow('invalid-public-icon'),
            'name' => 'PHP',
            'category' => 'backend',
            'icon_private_path' => 'technologies/private.png',
            'icon_mime' => 'image/png',
            'icon_size' => 1,
            'icon_public_path' => 'technologies/public.png',
        ]));
    }

    public function test_the_publication_state_check_rejects_draft_with_a_timestamp_and_published_without_one(): void
    {
        $this->assertDatabaseRejects(fn () => DB::table('work_cases')->insert([
            ...$this->keyedRow('draft-with-a-published-at'),
            'published_at' => now(),
        ]));

        $this->assertDatabaseRejects(fn () => DB::table('work_cases')->insert([
            ...$this->keyedRow('published-without-a-published-at'),
            'status' => 'published',
            'key_locked' => true,
            'published_at' => null,
        ]));

        DB::table('work_cases')->insert([
            ...$this->keyedRow('published-and-hidden'),
            'status' => 'published',
            'key_locked' => true,
            'published_at' => now(),
        ]);

        $this->assertDatabaseHas('work_cases', ['key' => 'published-and-hidden', 'is_visible' => false]);
    }

    public function test_schema_enforces_unique_keys_link_types_and_cv_locales(): void
    {
        DB::table('experiences')->insert($this->experienceRow('first-experience'));

        $this->assertDatabaseRejects(fn () => DB::table('experiences')->insert($this->experienceRow('first-experience')));

        DB::table('professional_links')->insert($this->professionalLinkRow('linkedin'));

        $this->assertDatabaseRejects(fn () => DB::table('professional_links')->insert($this->professionalLinkRow('linkedin')));

        DB::table('cv_documents')->insert($this->cvDocumentRow('es'));

        $this->assertDatabaseRejects(fn () => DB::table('cv_documents')->insert($this->cvDocumentRow('es')));
    }

    public function test_experience_dates_require_complete_end_months_and_chronological_ranges(): void
    {
        $this->assertDatabaseRejects(fn () => DB::table('experiences')->insert([
            ...$this->experienceRow('partial-end-date'),
            'end_year' => 2024,
        ]));
        $this->assertDatabaseRejects(fn () => DB::table('experiences')->insert([
            ...$this->experienceRow('backward-end-date'),
            'start_year' => 2025,
            'start_month' => 2,
            'end_year' => 2025,
            'end_month' => 1,
        ]));

        DB::table('experiences')->insert([
            ...$this->experienceRow('matching-month'),
            'end_year' => 2024,
            'end_month' => 1,
        ]);

        $this->assertDatabaseHas('experiences', ['key' => 'matching-month']);
    }

    public function test_positions_can_tie_but_cannot_be_negative(): void
    {
        DB::table('work_cases')->insert($this->keyedRow('first-position', position: 0));
        DB::table('work_cases')->insert($this->keyedRow('second-position', position: 0));

        $this->assertDatabaseRejects(fn () => DB::table('work_cases')->insert($this->keyedRow('negative-position', position: -1)));
    }

    public function test_pivots_reject_duplicate_pairs_cascade_with_the_parent_and_restrict_technology_deletion(): void
    {
        $experienceId = DB::table('experiences')->insertGetId($this->experienceRow('pivot-owner'));
        $technologyId = DB::table('technologies')->insertGetId([
            ...$this->keyedRow('pivot-technology'),
            'name' => 'Laravel',
            'category' => 'backend',
        ]);

        DB::table('experience_technology')->insert([
            'experience_id' => $experienceId,
            'technology_id' => $technologyId,
            'position' => 0,
        ]);

        $this->assertDatabaseRejects(fn () => DB::table('experience_technology')->insert([
            'experience_id' => $experienceId,
            'technology_id' => $technologyId,
            'position' => 1,
        ]));
        $this->assertDatabaseRejects(fn () => DB::table('technologies')->where('id', $technologyId)->delete());

        DB::table('experiences')->where('id', $experienceId)->delete();

        $this->assertDatabaseMissing('experience_technology', ['technology_id' => $technologyId]);
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
    private function keyedRow(string $key, int $position = 0): array
    {
        return [
            'key' => $key,
            'key_locked' => false,
            'position' => $position,
            'status' => 'draft',
            'is_visible' => false,
            'published_at' => null,
        ];
    }

    /** @return array<string, int|bool|string|null> */
    private function experienceRow(string $key): array
    {
        return [
            ...$this->keyedRow($key),
            'start_year' => 2024,
            'start_month' => 1,
            'end_year' => null,
            'end_month' => null,
        ];
    }

    /** @return array<string, int|bool|string|null> */
    private function professionalLinkRow(string $type): array
    {
        return [
            'type' => $type,
            'position' => 0,
            'destination' => 'https://example.test/profile',
            'status' => 'draft',
            'is_visible' => false,
            'published_at' => null,
        ];
    }

    /** @return array<string, int|bool|string|null> */
    private function cvDocumentRow(string $locale): array
    {
        return [
            'locale' => $locale,
            'status' => 'draft',
            'is_visible' => false,
            'published_at' => null,
        ];
    }
}
