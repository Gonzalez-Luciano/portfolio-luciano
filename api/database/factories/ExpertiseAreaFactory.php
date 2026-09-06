<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\ExpertiseArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExpertiseArea> */
final class ExpertiseAreaFactory extends Factory
{
    protected $model = ExpertiseArea::class;

    public function definition(): array
    {
        return ['key' => 'synthetic-expertise-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'title_es' => null, 'title_en' => null, 'description_es' => null, 'description_en' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
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

    public function withoutDescription(): static
    {
        return $this->state(['description_es' => null, 'description_en' => null]);
    }

    private function publishedAttributes(bool $visible): array
    {
        return ['key_locked' => true, 'title_es' => 'Área técnica sintética', 'title_en' => 'Synthetic technical area', 'description_es' => 'Descripción técnica sintética.', 'description_en' => 'Synthetic technical description.', 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
