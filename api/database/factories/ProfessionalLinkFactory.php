<?php

namespace Database\Factories;

use App\Enums\ProfessionalLinkType;
use App\Enums\PublicationStatus;
use App\Models\ProfessionalLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProfessionalLink> */
final class ProfessionalLinkFactory extends Factory
{
    protected $model = ProfessionalLink::class;

    public function definition(): array
    {
        return ['type' => ProfessionalLinkType::LinkedIn, 'position' => 0, 'destination' => 'https://example.test/synthetic-profile', 'label_es' => null, 'label_en' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
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

    public function github(): static
    {
        return $this->state(['type' => ProfessionalLinkType::GitHub, 'destination' => 'https://example.test/synthetic-github']);
    }

    public function email(): static
    {
        return $this->state(['type' => ProfessionalLinkType::Email, 'destination' => 'synthetic@example.test']);
    }

    private function publishedAttributes(bool $visible): array
    {
        return ['label_es' => 'Enlace técnico sintético', 'label_en' => 'Synthetic technical link', 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
