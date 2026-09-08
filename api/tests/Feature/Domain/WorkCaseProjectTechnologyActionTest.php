<?php

namespace Tests\Feature\Domain;

use App\Domain\Content\Actions\UpdateContentWithTechnologies;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Models\Project;
use App\Models\Technology;
use App\Models\WorkCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/**
 * Covers `UpdateContentWithTechnologies`, the one small shared domain action
 * that updates a WorkCase's or Project's plain attributes and its ordered
 * technology pivot together, atomically. Its transaction/context/
 * cache-invalidation ordering mirrors `UpdateOwnedAssetAltText` and
 * `UpdateExperienceAggregate`; this is where that contract is proven,
 * including the exact regression class Task 9 originally shipped (a plain
 * unrelated field edit silently wiping a relation because the mutation
 * wasn't committed as one atomic, validated unit).
 */
final class WorkCaseProjectTechnologyActionTest extends TestCase
{
    use RefreshDatabase;

    // ---- Basic combined update, for both supported entity types ----

    public function test_it_updates_plain_attributes_and_technologies_together_for_a_work_case(): void
    {
        $workCase = WorkCase::factory()->draft()->create(['title_es' => 'Original', 'title_en' => 'Original']);
        $technology = Technology::factory()->draft()->create();

        $updated = app(UpdateContentWithTechnologies::class)(
            $workCase,
            ['title_es' => 'Actualizado', 'title_en' => 'Updated'],
            [['technology_id' => $technology->id, 'position' => 3]],
        );

        $this->assertSame('Actualizado', $updated->title_es);
        $this->assertSame($technology->id, $updated->technologies()->sole()->id);
        $this->assertSame(3, $updated->technologies()->sole()->pivot->position);
    }

    public function test_it_updates_plain_attributes_and_technologies_together_for_a_project(): void
    {
        $project = Project::factory()->draft()->create(['title_es' => 'Original', 'title_en' => 'Original']);
        $technology = Technology::factory()->draft()->create();

        $updated = app(UpdateContentWithTechnologies::class)(
            $project,
            ['title_es' => 'Actualizado', 'title_en' => 'Updated'],
            [['technology_id' => $technology->id, 'position' => 5]],
        );

        $this->assertSame('Actualizado', $updated->title_es);
        $this->assertSame($technology->id, $updated->technologies()->sole()->id);
        $this->assertSame(5, $updated->technologies()->sole()->pivot->position);
    }

    public function test_it_persists_technology_pivot_position_matching_submitted_order(): void
    {
        $workCase = WorkCase::factory()->draft()->create();
        $first = Technology::factory()->create(['key' => 'synthetic-technology-alpha']);
        $second = Technology::factory()->create(['key' => 'synthetic-technology-beta']);

        app(UpdateContentWithTechnologies::class)(
            $workCase,
            [],
            [
                ['technology_id' => $second->id, 'position' => 0],
                ['technology_id' => $first->id, 'position' => 1],
            ],
        );

        $pivots = DB::table('technology_work_case')->where('work_case_id', $workCase->getKey())->orderBy('position')->get();
        $this->assertCount(2, $pivots);
        $this->assertSame($second->id, $pivots[0]->technology_id);
        $this->assertSame(0, $pivots[0]->position);
        $this->assertSame($first->id, $pivots[1]->technology_id);
        $this->assertSame(1, $pivots[1]->position);
    }

    // ---- Protected attributes ----

    public function test_it_rejects_protected_attributes(): void
    {
        $workCase = WorkCase::factory()->draft()->create();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Sensitive editorial mutation');

        app(UpdateContentWithTechnologies::class)(
            $workCase,
            ['status' => PublicationStatus::Published, 'is_visible' => true, 'published_at' => now()],
            [],
        );
    }

    // ---- Duplicate/unknown technology rejection, atomically ----

    public function test_duplicate_technology_is_rejected_and_nothing_is_persisted(): void
    {
        $workCase = WorkCase::factory()->draft()->create(['title_es' => 'Original']);
        $technology = Technology::factory()->create();

        try {
            app(UpdateContentWithTechnologies::class)(
                $workCase,
                ['title_es' => 'Modificado'],
                [
                    ['technology_id' => $technology->id, 'position' => 0],
                    ['technology_id' => $technology->id, 'position' => 1],
                ],
            );
            $this->fail('A duplicate technology relation must reject the whole update.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('duplicate_relation', $exception->issues()[0]->code);
            $this->assertSame('Original', $workCase->fresh()->title_es);
            $this->assertSame(0, DB::table('technology_work_case')->where('work_case_id', $workCase->getKey())->count());
        }
    }

    public function test_unknown_technology_is_rejected_and_nothing_is_persisted(): void
    {
        $project = Project::factory()->draft()->create(['title_es' => 'Original']);

        try {
            app(UpdateContentWithTechnologies::class)(
                $project,
                ['title_es' => 'Modificado'],
                [['technology_id' => 999999, 'position' => 0]],
            );
            $this->fail('An unknown technology relation must reject the whole update.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('invalid_relation', $exception->issues()[0]->code);
            $this->assertSame('technologies.0.technology_id', $exception->issues()[0]->path);
            $this->assertSame('Original', $project->fresh()->title_es);
            $this->assertSame(0, DB::table('project_technology')->where('project_id', $project->getKey())->count());
        }
    }

    // ---- Full-aggregate validation before commit, on a published record ----

    public function test_invalid_attribute_and_technology_combination_on_a_published_work_case_fails_atomically(): void
    {
        $workCase = $this->publishedVisibleWorkCase();
        $originalTitleEs = $workCase->title_es;
        $technology = Technology::factory()->draft()->create();

        try {
            app(UpdateContentWithTechnologies::class)(
                $workCase,
                ['title_es' => 'Modificado', 'title_en' => null],
                [['technology_id' => $technology->id, 'position' => 0]],
            );
            $this->fail('An incomplete required pair on a published record must reject the whole update.');
        } catch (PublicationValidationException) {
            $this->assertSame($originalTitleEs, $workCase->fresh()->title_es);
            $this->assertSame(0, $workCase->technologies()->count());
        }
    }

    public function test_invalid_https_url_and_technology_combination_on_a_published_project_fails_atomically(): void
    {
        $project = $this->publishedVisibleProject();
        $originalTitleEs = $project->title_es;
        $technology = Technology::factory()->draft()->create();

        try {
            app(UpdateContentWithTechnologies::class)(
                $project,
                ['title_es' => 'Modificado', 'demo_url' => 'http://insecure.example.test'],
                [['technology_id' => $technology->id, 'position' => 0]],
            );
            $this->fail('An invalid HTTPS URL on a published record must reject the whole update.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('invalid_https_url', $exception->issues()[0]->code);
            $this->assertSame($originalTitleEs, $project->fresh()->title_es);
            $this->assertSame(0, $project->technologies()->count());
        }
    }

    // ---- Regression: editing an unrelated field must not wipe technologies ----

    public function test_editing_an_unrelated_field_does_not_wipe_existing_technologies_on_a_work_case(): void
    {
        $workCase = WorkCase::factory()->draft()->create(['title_es' => 'Original', 'context_es' => 'Contexto original']);
        $technology = Technology::factory()->create();
        $workCase->technologies()->attach($technology, ['position' => 0]);

        $updated = app(UpdateContentWithTechnologies::class)(
            $workCase,
            ['context_es' => 'Contexto actualizado'],
            [['technology_id' => $technology->id, 'position' => 0]],
        );

        $this->assertSame('Contexto actualizado', $updated->context_es);
        $technologies = $updated->technologies;
        $this->assertCount(1, $technologies);
        $this->assertSame($technology->getKey(), $technologies[0]->getKey());
        $this->assertSame(0, $technologies[0]->pivot->position);
    }

    public function test_editing_an_unrelated_field_does_not_wipe_existing_technologies_on_a_project(): void
    {
        $project = Project::factory()->draft()->create(['title_es' => 'Original', 'summary_es' => 'Resumen original']);
        $technology = Technology::factory()->create();
        $project->technologies()->attach($technology, ['position' => 0]);

        $updated = app(UpdateContentWithTechnologies::class)(
            $project,
            ['summary_es' => 'Resumen actualizado'],
            [['technology_id' => $technology->id, 'position' => 0]],
        );

        $this->assertSame('Resumen actualizado', $updated->summary_es);
        $technologies = $updated->technologies;
        $this->assertCount(1, $technologies);
        $this->assertSame($technology->getKey(), $technologies[0]->getKey());
        $this->assertSame(0, $technologies[0]->pivot->position);
    }

    // ---- Cache invalidation, proven via the public API rather than "no exception thrown" ----

    public function test_it_invalidates_the_public_work_cases_cache_after_updating_technologies(): void
    {
        $workCase = $this->publishedVisibleWorkCase();
        $technology = Technology::factory()->create(['key' => 'synthetic-technology-cache']);
        $this->makePublic($technology);

        $this->getJson('/api/v1/es/work-cases')
            ->assertOk()
            ->assertJsonPath('data.0.title', $workCase->title_es)
            ->assertJsonPath('data.0.technologies', []);

        app(UpdateContentWithTechnologies::class)(
            $workCase,
            ['title_es' => 'Caso actualizado'],
            [['technology_id' => $technology->id, 'position' => 0]],
        );

        $this->getJson('/api/v1/es/work-cases')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Caso actualizado')
            ->assertJsonPath('data.0.technologies.0.key', 'synthetic-technology-cache');
    }

    public function test_it_invalidates_the_public_projects_cache_after_updating_technologies(): void
    {
        $project = $this->publishedVisibleProject();
        $technology = Technology::factory()->create(['key' => 'synthetic-technology-cache']);
        $this->makePublic($technology);

        $this->getJson('/api/v1/es/projects')
            ->assertOk()
            ->assertJsonPath('data.0.title', $project->title_es)
            ->assertJsonPath('data.0.technologies', []);

        app(UpdateContentWithTechnologies::class)(
            $project,
            ['title_es' => 'Proyecto actualizado'],
            [['technology_id' => $technology->id, 'position' => 0]],
        );

        $this->getJson('/api/v1/es/projects')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Proyecto actualizado')
            ->assertJsonPath('data.0.technologies.0.key', 'synthetic-technology-cache');
    }

    private function publishedVisibleWorkCase(): WorkCase
    {
        $workCase = WorkCase::factory()->draft()->create([
            'title_es' => 'Caso técnico sintético', 'title_en' => 'Synthetic technical case',
            'context_es' => 'Contexto técnico sintético.', 'context_en' => 'Synthetic technical context.',
            'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.',
            'contribution_es' => 'Contribución técnica sintética.', 'contribution_en' => 'Synthetic technical contribution.',
            'technical_approach_es' => 'Enfoque técnico sintético.', 'technical_approach_en' => 'Synthetic technical approach.',
            'outcome_es' => 'Resultado técnico sintético.', 'outcome_en' => 'Synthetic technical outcome.',
        ]);
        $this->makePublic($workCase);

        return $workCase->fresh();
    }

    private function publishedVisibleProject(): Project
    {
        $project = Project::factory()->draft()->create([
            'title_es' => 'Proyecto técnico sintético', 'title_en' => 'Synthetic technical project',
            'summary_es' => 'Resumen técnico sintético.', 'summary_en' => 'Synthetic technical summary.',
            'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.',
            'solution_es' => 'Solución técnica sintética.', 'solution_en' => 'Synthetic technical solution.',
        ]);
        $this->makePublic($project);

        return $project->fresh();
    }

    /**
     * Forces a row directly into a published, visible state via the query
     * builder (bypassing Eloquent events), matching the established fixture
     * pattern in ExperienceResourceTest/PublicApiContractTest since the
     * editorial mutation guard forbids creating or updating into a
     * published state through Eloquent outside of the domain actions.
     */
    private function makePublic(Model $model): void
    {
        $values = [
            'status' => PublicationStatus::Published->value,
            'is_visible' => true,
            'published_at' => now(),
        ];
        if (array_key_exists('key_locked', $model->getAttributes())) {
            $values['key_locked'] = true;
        }
        DB::table($model->getTable())->where('id', $model->getKey())->update($values);
        $model->refresh();
    }
}
