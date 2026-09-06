<?php

namespace Tests\Feature\Domain;

use App\Enums\PublicationStatus;
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
use Tests\TestCase;

final class FactoryStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_factories_produce_incomplete_drafts_with_synthetic_content_when_present(): void
    {
        $factories = [
            Profile::factory(), SiteConfiguration::factory(), Experience::factory(), ExperienceHighlight::factory(),
            WorkCase::factory(), Project::factory(), Technology::factory(), ExpertiseArea::factory(),
            WorkPrinciple::factory(), ProfessionalLink::factory(), CvDocument::factory(),
        ];

        foreach ($factories as $factory) {
            $model = $factory->make();

            if ($model instanceof ExperienceHighlight) {
                $this->assertNull($model->content_es);

                continue;
            }

            $this->assertSame(PublicationStatus::Draft, $model->status);
            $this->assertFalse($model->is_visible);
            $this->assertNull($model->published_at);
        }

        $this->assertNull(Profile::factory()->make()->name);
        $this->assertNull(Project::factory()->make()->title_es);
        $this->assertStringContainsString('Synthetic', Technology::factory()->make()->name);
    }

    public function test_all_publishable_factories_offer_published_hidden_and_visible_states(): void
    {
        $factories = [
            Profile::factory(), SiteConfiguration::factory(), Experience::factory(), WorkCase::factory(), Project::factory(),
            Technology::factory(), ExpertiseArea::factory(), WorkPrinciple::factory(), ProfessionalLink::factory(), CvDocument::factory(),
        ];

        foreach ($factories as $factory) {
            $hidden = $factory->publishedHidden()->make();
            $visible = $factory->publishedVisible()->make();

            $this->assertSame(PublicationStatus::Published, $hidden->status);
            $this->assertFalse($hidden->is_visible);
            $this->assertNotNull($hidden->published_at);
            $this->assertSame(PublicationStatus::Published, $visible->status);
            $this->assertTrue($visible->is_visible);
            $this->assertNotNull($visible->published_at);
        }
    }

    public function test_experience_factory_supports_bilingual_optional_pair_and_monthly_current_or_ended_states(): void
    {
        $anonymous = Experience::factory()->withoutOrganization()->current()->make();
        $identified = Experience::factory()->withOrganization()->ended()->make();

        $this->assertNull($anonymous->organization_label_es);
        $this->assertNull($anonymous->organization_label_en);
        $this->assertTrue($anonymous->isCurrent());
        $this->assertNotNull($identified->organization_label_es);
        $this->assertNotNull($identified->organization_label_en);
        $this->assertFalse($identified->isCurrent());
        $this->assertNotNull($identified->end_year);
        $this->assertNotNull($identified->end_month);
    }

    public function test_factories_attach_related_technologies_with_ordered_pivot_positions(): void
    {
        $experience = Experience::factory()->withTechnologies(3)->create();
        $workCase = WorkCase::factory()->withTechnologies(3)->create();
        $project = Project::factory()->withTechnologies(3)->create();

        foreach ([$experience, $workCase, $project] as $owner) {
            $technologies = $owner->technologies()->get();
            $this->assertCount(3, $technologies);
            $this->assertSame([0, 1, 2], $technologies->pluck('pivot.position')->all());
            $this->assertTrue($technologies->every(fn (Technology $technology): bool => str_contains($technology->name, 'Synthetic')));
        }
    }
}
