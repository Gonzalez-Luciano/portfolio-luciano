<?php

namespace Tests\Feature\Database;

use App\Enums\LanguageLevel;
use App\Enums\ProjectDeliveryStatus;
use App\Enums\ProjectKind;
use App\Enums\WorkMode;
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

    public function test_language_levels_are_a_closed_set(): void
    {
        $this->assertSame(['native', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'], array_column(LanguageLevel::cases(), 'value'));

        DB::table('languages')->insert([...$this->projectRow('english'), 'level' => 'b2']);
        $this->assertDatabaseHas('languages', ['key' => 'english', 'level' => 'b2']);

        $this->assertDatabaseRejects(fn () => DB::table('languages')->insert([...$this->projectRow('fluent'), 'level' => 'fluent']));
    }

    public function test_work_modes_are_a_closed_set(): void
    {
        $this->assertSame(['on_site', 'hybrid', 'remote'], array_column(WorkMode::cases(), 'value'));
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
