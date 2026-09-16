<?php

namespace Database\Factories;

use App\Enums\LanguageLevel;
use App\Enums\PublicationStatus;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Language> */
final class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        return ['key' => 'synthetic-language-'.$this->faker->unique()->numerify('###'), 'key_locked' => false, 'position' => 0, 'name_es' => null, 'name_en' => null, 'level' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
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
        return ['key_locked' => true, 'name_es' => 'Idioma sintético', 'name_en' => 'Synthetic language', 'level' => LanguageLevel::B2, 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
