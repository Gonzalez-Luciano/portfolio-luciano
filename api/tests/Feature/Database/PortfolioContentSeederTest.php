<?php

namespace Tests\Feature\Database;

use App\Enums\PublicationStatus;
use App\Models\CvDocument;
use App\Models\Experience;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Database\Seeders\PortfolioContentSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PortfolioContentSeederTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_running_the_seeder_twice_creates_no_duplicates_and_updates_in_place(): void
    {
        $manualProject = Project::factory()->create(['key' => 'manual-unrelated-project']);
        $manualProjectOriginal = $manualProject->fresh()->getAttributes();

        $this->seedPortfolioContent();

        $countsAfterFirstRun = $this->tableCounts();
        $technologyIdsAfterFirstRun = Technology::query()->pluck('id', 'key')->all();
        $expertiseIdsAfterFirstRun = ExpertiseArea::query()->pluck('id', 'key')->all();
        $workCaseIdsAfterFirstRun = WorkCase::query()->pluck('id', 'key')->all();
        $workPrincipleIdsAfterFirstRun = WorkPrinciple::query()->pluck('id', 'key')->all();
        $linkIdsAfterFirstRun = ProfessionalLink::query()->pluck('id', 'type')->all();
        $profileIdAfterFirstRun = Profile::query()->where('singleton_key', 'default')->value('id');
        $siteConfigurationIdAfterFirstRun = SiteConfiguration::query()->where('singleton_key', 'default')->value('id');

        $this->seedPortfolioContent();

        $countsAfterSecondRun = $this->tableCounts();
        $this->assertSame($countsAfterFirstRun, $countsAfterSecondRun);

        $this->assertSame($technologyIdsAfterFirstRun, Technology::query()->pluck('id', 'key')->all());
        $this->assertSame($expertiseIdsAfterFirstRun, ExpertiseArea::query()->pluck('id', 'key')->all());
        $this->assertSame($workCaseIdsAfterFirstRun, WorkCase::query()->pluck('id', 'key')->all());
        $this->assertSame($workPrincipleIdsAfterFirstRun, WorkPrinciple::query()->pluck('id', 'key')->all());
        $this->assertSame($linkIdsAfterFirstRun, ProfessionalLink::query()->pluck('id', 'type')->all());
        $this->assertSame($profileIdAfterFirstRun, Profile::query()->where('singleton_key', 'default')->value('id'));
        $this->assertSame($siteConfigurationIdAfterFirstRun, SiteConfiguration::query()->where('singleton_key', 'default')->value('id'));

        $manualProject->refresh();
        $this->assertSame($manualProjectOriginal, $manualProject->getAttributes());
    }

    public function test_every_seeded_row_is_draft_hidden_and_unpublished(): void
    {
        $this->seedPortfolioContent();

        $seededTables = [
            Profile::class => Profile::query()->where('singleton_key', 'default'),
            SiteConfiguration::class => SiteConfiguration::query()->where('singleton_key', 'default'),
            ProfessionalLink::class => ProfessionalLink::query(),
            Technology::class => Technology::query(),
            ExpertiseArea::class => ExpertiseArea::query(),
            WorkCase::class => WorkCase::query(),
            WorkPrinciple::class => WorkPrinciple::query(),
        ];

        foreach ($seededTables as $modelClass => $query) {
            $rows = $query->get();
            $this->assertGreaterThan(0, $rows->count(), "{$modelClass} should have at least one seeded row.");

            foreach ($rows as $row) {
                $this->assertSame(PublicationStatus::Draft, $row->status, "{$modelClass}#{$row->getKey()} must be draft.");
                $this->assertFalse($row->is_visible, "{$modelClass}#{$row->getKey()} must be hidden.");
                $this->assertNull($row->published_at, "{$modelClass}#{$row->getKey()} must not be published.");
            }
        }
    }

    public function test_it_does_not_create_experience_rows_without_approved_dates(): void
    {
        $this->seedPortfolioContent();

        $this->assertDatabaseCount('experiences', 0);
    }

    public function test_manual_unrelated_records_are_preserved_across_runs(): void
    {
        $manualProject = Project::factory()->create(['key' => 'manual-unrelated-project']);

        $this->seedPortfolioContent();
        $this->seedPortfolioContent();

        $manualProject->refresh();
        $this->assertSame('manual-unrelated-project', $manualProject->key);
        $this->assertDatabaseHas('projects', ['key' => 'manual-unrelated-project']);
    }

    public function test_on_a_clean_content_database_the_seeder_creates_zero_projects(): void
    {
        $this->seedPortfolioContent();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_it_creates_zero_cv_documents(): void
    {
        $this->seedPortfolioContent();

        $this->assertDatabaseCount('cv_documents', 0);
    }

    public function test_it_creates_zero_users_or_admins(): void
    {
        $this->seedPortfolioContent();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_writes_no_public_or_private_asset_files(): void
    {
        $this->seedPortfolioContent();
        $this->seedPortfolioContent();

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function seedPortfolioContent(): void
    {
        (new PortfolioContentSeeder)->run();
    }

    /** @return array<string, int> */
    private function tableCounts(): array
    {
        return [
            'profiles' => Profile::query()->count(),
            'site_configurations' => SiteConfiguration::query()->count(),
            'professional_links' => ProfessionalLink::query()->count(),
            'technologies' => Technology::query()->count(),
            'expertise_areas' => ExpertiseArea::query()->count(),
            'work_cases' => WorkCase::query()->count(),
            'work_principles' => WorkPrinciple::query()->count(),
            'experiences' => Experience::query()->count(),
            'projects' => Project::query()->count(),
            'cv_documents' => CvDocument::query()->count(),
        ];
    }
}
