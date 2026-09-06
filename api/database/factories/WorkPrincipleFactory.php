<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\WorkPrinciple;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkPrinciple> */
final class WorkPrincipleFactory extends Factory
{
    protected $model = WorkPrinciple::class;

    public function definition(): array
    {
        return ['key' => 'synthetic-principle-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'statement_es' => null, 'statement_en' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
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

    private function publishedAttributes(bool $visible): array
    {
        return ['key_locked' => true, 'statement_es' => 'Principio técnico sintético.', 'statement_en' => 'Synthetic technical principle.', 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
