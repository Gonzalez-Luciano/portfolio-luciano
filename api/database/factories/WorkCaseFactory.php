<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\Technology;
use App\Models\WorkCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkCase> */
final class WorkCaseFactory extends Factory
{
    protected $model = WorkCase::class;

    public function definition(): array
    {
        return ['key' => 'synthetic-work-case-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'title_es' => null, 'title_en' => null, 'context_es' => null, 'context_en' => null, 'problem_es' => null, 'problem_en' => null, 'contribution_es' => null, 'contribution_en' => null, 'technical_approach_es' => null, 'technical_approach_en' => null, 'outcome_es' => null, 'outcome_en' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
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

    public function withTechnologies(int $count = 1): static
    {
        return $this->afterCreating(function (WorkCase $workCase) use ($count): void {
            Technology::factory()->count($count)->create()->each(fn (Technology $technology, int $position) => $workCase->technologies()->attach($technology, ['position' => $position]));
        });
    }

    private function publishedAttributes(bool $visible): array
    {
        return ['key_locked' => true, 'title_es' => 'Caso técnico sintético', 'title_en' => 'Synthetic technical case', 'context_es' => 'Contexto técnico sintético.', 'context_en' => 'Synthetic technical context.', 'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.', 'contribution_es' => 'Contribución técnica sintética.', 'contribution_en' => 'Synthetic technical contribution.', 'technical_approach_es' => 'Enfoque técnico sintético.', 'technical_approach_en' => 'Synthetic technical approach.', 'outcome_es' => 'Resultado técnico sintético.', 'outcome_en' => 'Synthetic technical outcome.', 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
