<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Profile> */
final class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return ['singleton_key' => 'default', 'name' => null, 'headline_es' => null, 'headline_en' => null, 'short_summary_es' => null, 'short_summary_en' => null, 'introduction_es' => null, 'introduction_en' => null, 'availability_es' => null, 'availability_en' => null, 'cta_es' => null, 'cta_en' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
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
        return ['name' => 'Synthetic Portfolio Engineer', 'headline_es' => 'Especialista técnico sintético', 'headline_en' => 'Synthetic technical specialist', 'short_summary_es' => 'Resumen técnico sintético.', 'short_summary_en' => 'Synthetic technical summary.', 'introduction_es' => 'Introducción técnica sintética.', 'introduction_en' => 'Synthetic technical introduction.', 'availability_es' => 'Disponibilidad sintética.', 'availability_en' => 'Synthetic availability.', 'cta_es' => 'Ver caso sintético', 'cta_en' => 'View synthetic case', 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
