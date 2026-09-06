<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Technology> */
final class TechnologyFactory extends Factory
{
    protected $model = Technology::class;

    public function definition(): array
    {
        return ['key' => 'synthetic-technology-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'name' => 'Synthetic Technical Component', 'category' => TechnologyCategory::Backend, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null]);
    }

    public function publishedHidden(): static
    {
        return $this->state(['key_locked' => true, 'status' => PublicationStatus::Published, 'is_visible' => false, 'published_at' => now()]);
    }

    public function publishedVisible(): static
    {
        return $this->state(['key_locked' => true, 'status' => PublicationStatus::Published, 'is_visible' => true, 'published_at' => now()]);
    }
}
