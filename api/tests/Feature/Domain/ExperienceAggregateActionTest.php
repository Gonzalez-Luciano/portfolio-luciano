<?php

namespace Tests\Feature\Domain;

use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\UpdateExperienceAggregate;
use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\Technology;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use LogicException;
use Tests\TestCase;

final class ExperienceAggregateActionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_validates_the_complete_proposed_aggregate_before_changing_a_published_experience(): void
    {
        $experience = $this->publishedExperience();
        app(UpdateExperienceAggregate::class)(
            $experience,
            [],
            [['content_es' => 'Hito técnico sintético.', 'content_en' => 'Synthetic technical highlight.', 'position' => 0]],
            [],
        );
        $technology = Technology::factory()->draft()->create();

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
        $experience = $this->publishedExperience();
        $technology = Technology::factory()->draft()->create();

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
        $experience = $this->publishedExperience();

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
        $experience = $this->publishedExperience();

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

    public function test_it_allows_an_incomplete_draft_aggregate_to_be_saved(): void
    {
        $experience = Experience::factory()->draft()->create();

        $updated = app(UpdateExperienceAggregate::class)(
            $experience,
            ['summary_es' => 'Borrador incompleto'],
            [['content_es' => 'Hito incompleto', 'content_en' => null, 'position' => 0]],
            [],
        );

        $this->assertSame('Borrador incompleto', $updated->summary_es);
        $this->assertSame('Hito incompleto', $updated->highlights()->sole()->content_es);
        $this->assertNull($updated->highlights()->sole()->content_en);
    }

    public function test_direct_highlight_creation_is_rejected_outside_the_aggregate_action(): void
    {
        $experience = Experience::factory()->draft()->create();
        $highlight = ExperienceHighlight::factory()->bilingual()->make(['experience_id' => $experience->id]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('aggregate mutation context');

        $highlight->save();
    }

    public function test_a_generic_editorial_context_cannot_authorize_direct_highlight_creation(): void
    {
        $experience = Experience::factory()->draft()->create();
        $highlight = ExperienceHighlight::factory()->bilingual()->make(['experience_id' => $experience->id]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('aggregate mutation context');

        app(EditorialMutationContext::class)->run(static function () use ($highlight): void {
            $highlight->save();
        });
    }

    public function test_direct_highlight_update_and_delete_are_rejected_outside_the_aggregate_action(): void
    {
        $experience = Experience::factory()->draft()->create();
        $timestamp = now();
        DB::table('experience_highlights')->insert([
            'experience_id' => $experience->id,
            'content_es' => 'Hito sintético',
            'content_en' => 'Synthetic highlight',
            'position' => 0,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $highlight = ExperienceHighlight::query()->sole();
        $highlight->content_es = 'Edición no autorizada';

        try {
            $highlight->save();
            $this->fail('A direct highlight update must be rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('aggregate mutation context', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('aggregate mutation context');

        $highlight->delete();
    }

    public function test_aggregate_replacement_deletes_existing_highlights_through_guarded_model_events(): void
    {
        $experience = Experience::factory()->draft()->create();
        $initial = app(UpdateExperienceAggregate::class)(
            $experience,
            [],
            [['content_es' => 'Hito original', 'content_en' => 'Original highlight', 'position' => 0]],
            [],
        );
        $originalId = $initial->highlights()->sole()->id;
        $deletedIds = [];
        Event::listen('eloquent.deleted: '.ExperienceHighlight::class, static function (ExperienceHighlight $highlight) use (&$deletedIds): void {
            $deletedIds[] = $highlight->id;
        });

        $updated = app(UpdateExperienceAggregate::class)(
            $experience,
            [],
            [['content_es' => 'Hito reemplazado', 'content_en' => 'Replacement highlight', 'position' => 0]],
            [],
        );

        $this->assertSame([$originalId], $deletedIds);
        $this->assertSame('Hito reemplazado', $updated->highlights()->sole()->content_es);
    }

    private function publishedExperience(): Experience
    {
        $experience = Experience::factory()->draft()->create([
            'role_es' => 'Ingeniero técnico sintético',
            'role_en' => 'Synthetic technical engineer',
            'summary_es' => 'Resumen de experiencia sintética.',
            'summary_en' => 'Synthetic experience summary.',
        ]);

        return app(PublishContent::class)($experience);
    }
}
