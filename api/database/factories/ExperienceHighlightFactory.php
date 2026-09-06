<?php

namespace Database\Factories;

use App\Models\Experience;
use App\Models\ExperienceHighlight;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExperienceHighlight> */
final class ExperienceHighlightFactory extends Factory
{
    protected $model = ExperienceHighlight::class;

    public function definition(): array
    {
        return ['experience_id' => Experience::factory(), 'content_es' => null, 'content_en' => null, 'position' => 0];
    }

    public function bilingual(): static
    {
        return $this->state(['content_es' => 'Hito técnico sintético.', 'content_en' => 'Synthetic technical highlight.']);
    }
}
