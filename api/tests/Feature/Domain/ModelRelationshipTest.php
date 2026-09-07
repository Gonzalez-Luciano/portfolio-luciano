<?php

namespace Tests\Feature\Domain;

use App\Models\CvDocument;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_experience_highlights_are_ordered_by_position_then_id(): void
    {
        $experience = Experience::factory()->create();
        $later = ExperienceHighlight::factory()->create(['experience_id' => $experience->id, 'position' => 2]);
        $firstTie = ExperienceHighlight::factory()->create(['experience_id' => $experience->id, 'position' => 1]);
        $secondTie = ExperienceHighlight::factory()->create(['experience_id' => $experience->id, 'position' => 1]);

        $this->assertSame([$firstTie->id, $secondTie->id, $later->id], $experience->highlights()->pluck('id')->all());
    }

    public function test_experience_technologies_are_ordered_by_pivot_position_then_key(): void
    {
        $experience = Experience::factory()->create();
        $this->assertTechnologyPivotOrder($experience, 'technologies');
    }

    public function test_work_case_technologies_are_ordered_by_pivot_position_then_key(): void
    {
        $workCase = WorkCase::factory()->create();
        $this->assertTechnologyPivotOrder($workCase, 'technologies');
    }

    public function test_project_technologies_are_ordered_by_pivot_position_then_key(): void
    {
        $project = Project::factory()->create();
        $this->assertTechnologyPivotOrder($project, 'technologies');
    }

    public function test_publicly_available_scopes_exclude_drafts_and_published_hidden_records(): void
    {
        $models = [
            Experience::class,
            WorkCase::class,
            Project::class,
            Technology::class,
            ExpertiseArea::class,
            WorkPrinciple::class,
        ];

        foreach ($models as $model) {
            $model::factory()->draft()->create();
            $model::factory()->publishedHidden()->create();
            $visible = $model::factory()->publishedVisible()->create();

            $this->assertSame([$visible->getKey()], $model::publiclyAvailable()->pluck('id')->all(), $model);
        }

        ProfessionalLink::factory()->draft()->create(['type' => 'linkedin']);
        ProfessionalLink::factory()->publishedHidden()->create(['type' => 'github']);
        $visible = ProfessionalLink::factory()->publishedVisible()->create(['type' => 'email', 'destination' => 'synthetic@example.test']);
        $this->assertSame([$visible->id], ProfessionalLink::publiclyAvailable()->pluck('id')->all());
    }

    public function test_singleton_publicly_available_scopes_require_published_and_visible(): void
    {
        $profile = Profile::query()->sole();
        $site = SiteConfiguration::query()->sole();

        $this->assertCount(0, Profile::publiclyAvailable()->get());
        $this->assertCount(0, SiteConfiguration::publiclyAvailable()->get());

        // Scope tests deliberately construct a database-valid visible state; the
        // separate transition action remains the only application mutation path.
        DB::table('profiles')->where('id', $profile->id)->update([
            ...Profile::factory()->publishedVisible()->make()->getAttributes(),
            'updated_at' => now(),
        ]);
        DB::table('site_configurations')->where('id', $site->id)->update([
            ...SiteConfiguration::factory()->publishedVisible()->make()->getAttributes(),
            'updated_at' => now(),
        ]);

        $this->assertSame([$profile->id], Profile::publiclyAvailable()->pluck('id')->all());
        $this->assertSame([$site->id], SiteConfiguration::publiclyAvailable()->pluck('id')->all());
    }

    public function test_cv_document_publicly_available_scope_requires_published_and_visible(): void
    {
        CvDocument::factory()->draft()->spanish()->create();
        $english = CvDocument::factory()->publishedHidden()->create();

        $this->assertCount(0, CvDocument::publiclyAvailable()->get());

        DB::table('cv_documents')->where('id', $english->id)->update(['is_visible' => true]);

        $this->assertSame([$english->id], CvDocument::publiclyAvailable()->pluck('id')->all());
    }

    public function test_ordered_top_level_collections_use_their_approved_secondary_key(): void
    {
        $models = [
            Experience::class,
            WorkCase::class,
            Project::class,
            Technology::class,
            ExpertiseArea::class,
            WorkPrinciple::class,
        ];

        foreach ($models as $model) {
            $zeta = $model::factory()->publishedVisible()->create(['key' => 'zeta', 'position' => 5]);
            $alpha = $model::factory()->publishedVisible()->create(['key' => 'alpha', 'position' => 5]);
            $earlier = $model::factory()->publishedVisible()->create(['key' => 'middle', 'position' => 4]);

            $this->assertSame([$earlier->id, $alpha->id, $zeta->id], $model::publiclyAvailable()->pluck('id')->all(), $model);
        }

        $email = ProfessionalLink::factory()->publishedVisible()->create(['type' => 'email', 'destination' => 'synthetic@example.test', 'position' => 5]);
        $github = ProfessionalLink::factory()->publishedVisible()->create(['type' => 'github', 'position' => 5]);
        $linkedin = ProfessionalLink::factory()->publishedVisible()->create(['type' => 'linkedin', 'position' => 4]);

        $this->assertSame([$linkedin->id, $github->id, $email->id], ProfessionalLink::publiclyAvailable()->pluck('id')->all());
    }

    private function assertTechnologyPivotOrder(Experience|WorkCase|Project $owner, string $relation): void
    {
        $zeta = Technology::factory()->create(['key' => 'zeta']);
        $alpha = Technology::factory()->create(['key' => 'alpha']);
        $earlier = Technology::factory()->create(['key' => 'middle']);

        $owner->{$relation}()->attach([
            $zeta->id => ['position' => 2],
            $alpha->id => ['position' => 2],
            $earlier->id => ['position' => 1],
        ]);

        $this->assertSame(['middle', 'alpha', 'zeta'], $owner->{$relation}()->pluck('technologies.key')->all());
    }
}
