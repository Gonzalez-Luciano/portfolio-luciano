<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\Experience;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Experience> */
final class ExperienceFactory extends Factory
{
    protected $model = Experience::class;

    public function definition(): array
    {
        return ['key' => 'synthetic-experience-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'role_es' => null, 'role_en' => null, 'summary_es' => null, 'summary_en' => null, 'organization_label_es' => null, 'organization_label_en' => null, 'start_year' => 2024, 'start_month' => 1, 'end_year' => null, 'end_month' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
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

    public function current(): static
    {
        return $this->state(['end_year' => null, 'end_month' => null]);
    }

    public function ended(): static
    {
        return $this->state(['end_year' => 2025, 'end_month' => 1]);
    }

    public function withoutOrganization(): static
    {
        return $this->state(['organization_label_es' => null, 'organization_label_en' => null]);
    }

    public function withOrganization(): static
    {
        return $this->state(['organization_label_es' => 'Organización técnica sintética', 'organization_label_en' => 'Synthetic technical organization']);
    }

    public function withTechnologies(int $count = 1): static
    {
        return $this->afterCreating(function (Experience $experience) use ($count): void {
            Technology::factory()->count($count)->create()->each(fn (Technology $technology, int $position) => $experience->technologies()->attach($technology, ['position' => $position]));
        });
    }

    private function publishedAttributes(bool $visible): array
    {
        return ['key_locked' => true, 'role_es' => 'Ingeniero técnico sintético', 'role_en' => 'Synthetic technical engineer', 'summary_es' => 'Resumen de experiencia sintética.', 'summary_en' => 'Synthetic experience summary.', 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
