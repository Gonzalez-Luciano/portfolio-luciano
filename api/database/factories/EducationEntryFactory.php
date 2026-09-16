<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\EducationEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EducationEntry> */
final class EducationEntryFactory extends Factory
{
    protected $model = EducationEntry::class;

    public function definition(): array
    {
        return ['key' => 'synthetic-education-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'institution' => null, 'program_es' => null, 'program_en' => null, 'detail_es' => null, 'detail_en' => null, 'start_year' => null, 'end_year' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
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
        return ['key_locked' => true, 'institution' => 'Institución sintética', 'program_es' => 'Programa sintético', 'program_en' => 'Synthetic program', 'end_year' => 2021, 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
