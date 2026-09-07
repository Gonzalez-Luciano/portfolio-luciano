<?php

namespace Tests\Feature\Domain;

use App\Domain\Content\Actions\UpdateExperienceAggregate;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\Technology;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use LogicException;
use Tests\TestCase;

final class ExperienceAggregateActionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_validates_the_complete_proposed_aggregate_before_changing_a_published_experience(): void
    {
        $experience = Experience::factory()->publishedHidden()->create();
        ExperienceHighlight::factory()->bilingual()->create(['experience_id' => $experience->id]);
        $technology = Technology::factory()->publishedHidden()->create();

        try {
            app(UpdateExperienceAggregate::class)(
                $experience,
                ['summary_es' => 'Resumen actualizado'],
                [['content_es' => 'Hito incompleto', 'content_en' => null, 'position' => 0]],
                [['technology_id' => $technology->id, 'position' => 0]],
            );
            $this->fail('An incomplete highlight must reject the entire aggregate.');
        } catch (PublicationValidationException) {
            $this->assertSame('Synthetic experience summary.', $experience->fresh()->summary_en);
            $this->assertSame(1, $experience->highlights()->count());
            $this->assertSame(0, $experience->technologies()->count());
        }
    }

    public function test_it_commits_parent_highlights_and_contextual_technologies_as_one_validated_aggregate(): void
    {
        $experience = Experience::factory()->publishedHidden()->create();
        $technology = Technology::factory()->publishedHidden()->create();

        $updated = app(UpdateExperienceAggregate::class)(
            $experience,
            ['summary_es' => 'Resumen actualizado', 'summary_en' => 'Updated summary'],
            [['content_es' => 'Hito actualizado', 'content_en' => 'Updated highlight', 'position' => 4]],
            [['technology_id' => $technology->id, 'position' => 7]],
        );

        $this->assertSame('Resumen actualizado', $updated->summary_es);
        $this->assertSame('Hito actualizado', $updated->highlights()->sole()->content_es);
        $this->assertSame(7, $updated->technologies()->sole()->pivot->position);
    }

    public function test_it_cannot_be_used_to_return_a_published_experience_to_draft(): void
    {
        $experience = Experience::factory()->publishedHidden()->create();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Sensitive editorial mutation');

        app(UpdateExperienceAggregate::class)(
            $experience,
            [
                'status' => PublicationStatus::Draft,
                'is_visible' => false,
                'published_at' => null,
            ],
            [],
            [],
        );
    }

    public function test_it_rejects_unknown_technology_relations_before_writing_the_aggregate(): void
    {
        $experience = Experience::factory()->publishedHidden()->create();

        try {
            app(UpdateExperienceAggregate::class)(
                $experience,
                [],
                [],
                [['technology_id' => 999999, 'position' => 0]],
            );
            $this->fail('An unknown technology relation must reject the aggregate.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('invalid_relation', $exception->issues()[0]->code);
            $this->assertSame('technologies.0.technology_id', $exception->issues()[0]->path);
            $this->assertSame(0, $experience->technologies()->count());
        }
    }
}
