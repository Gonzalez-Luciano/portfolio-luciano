<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\SiteConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteConfiguration> */
final class SiteConfigurationFactory extends Factory
{
    protected $model = SiteConfiguration::class;

    public function definition(): array
    {
        return ['singleton_key' => 'default', 'projects_empty_message_es' => null, 'projects_empty_message_en' => null, 'contact_intro_es' => null, 'contact_intro_en' => null, 'technology_backend_label_es' => null, 'technology_backend_label_en' => null, 'technology_data_label_es' => null, 'technology_data_label_en' => null, 'technology_integration_label_es' => null, 'technology_integration_label_en' => null, 'technology_collaboration_label_es' => null, 'technology_collaboration_label_en' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
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
        return ['projects_empty_message_es' => 'No hay proyectos sintéticos publicados.', 'projects_empty_message_en' => 'No synthetic projects are published.', 'contact_intro_es' => 'Contacto técnico sintético.', 'contact_intro_en' => 'Synthetic technical contact.', 'technology_backend_label_es' => 'Backend sintético', 'technology_backend_label_en' => 'Synthetic backend', 'technology_data_label_es' => 'Datos sintéticos', 'technology_data_label_en' => 'Synthetic data', 'technology_integration_label_es' => 'Integración sintética', 'technology_integration_label_en' => 'Synthetic integration', 'technology_collaboration_label_es' => 'Colaboración sintética', 'technology_collaboration_label_en' => 'Synthetic collaboration', 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
