<?php

namespace Tests\Feature\Database;

use App\Domain\Content\Actions\UpdateContent;
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

    public function test_it_never_overwrites_work_modes_an_admin_already_set_while_still_filling_a_missing_location(): void
    {
        $this->seed(PortfolioContentSeeder::class);

        $profile = Profile::query()->where('singleton_key', 'default')->firstOrFail();
        $this->assertNull($profile->location, 'This test requires a profile with no location yet.');
        app(UpdateContent::class)($profile, ['work_modes' => ['remote']]);

        $this->seed(PhaseSixDraftContentSeeder::class);

        $profile = $profile->fresh();
        $this->assertSame('Mar del Plata, Argentina', $profile->location);
        $this->assertSame(['remote'], $profile->work_modes);
    }
}
