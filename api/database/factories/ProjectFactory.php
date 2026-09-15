<?php

namespace Database\Factories;

use App\Enums\ProjectDeliveryStatus;
use App\Enums\ProjectKind;
use App\Enums\PublicationStatus;
use App\Models\Project;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
final class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return ['key' => 'synthetic-project-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'kind' => ProjectKind::Personal, 'client_name' => null, 'title_es' => null, 'title_en' => null, 'role_es' => null, 'role_en' => null, 'delivery_status' => null, 'summary_es' => null, 'summary_en' => null, 'problem_es' => null, 'problem_en' => null, 'solution_es' => null, 'solution_en' => null, 'result_es' => null, 'result_en' => null, 'featured' => false, 'demo_url' => null, 'repository_url' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null]);
    }

    public function client(string $name = 'Synthetic Client'): static
    {
        return $this->state(['kind' => ProjectKind::Client, 'client_name' => $name]);
    }

    public function publishedHidden(): static
    {
        return $this->state($this->publishedAttributes(false));
    }

    public function publishedVisible(): static
    {
        return $this->state($this->publishedAttributes(true));
    }

    public function featured(): static
    {
        return $this->state(['featured' => true]);
    }

    public function withTechnologies(int $count = 1): static
    {
        return $this->afterCreating(function (Project $project) use ($count): void {
            Technology::factory()->count($count)->create()->each(fn (Technology $technology, int $position) => $project->technologies()->attach($technology, ['position' => $position]));
        });
    }

    private function publishedAttributes(bool $visible): array
    {
        return ['key_locked' => true, 'title_es' => 'Proyecto técnico sintético', 'title_en' => 'Synthetic technical project', 'role_es' => 'Rol técnico sintético', 'role_en' => 'Synthetic technical role', 'delivery_status' => ProjectDeliveryStatus::InDevelopment, 'summary_es' => 'Resumen técnico sintético.', 'summary_en' => 'Synthetic technical summary.', 'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.', 'solution_es' => 'Solución técnica sintética.', 'solution_en' => 'Synthetic technical solution.', 'result_es' => 'Resultado técnico sintético.', 'result_en' => 'Synthetic technical result.', 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
