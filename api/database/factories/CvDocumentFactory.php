<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Enums\SupportedLocale;
use App\Models\CvDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CvDocument> */
final class CvDocumentFactory extends Factory
{
    protected $model = CvDocument::class;

    public function definition(): array
    {
        return ['locale' => SupportedLocale::English, 'label' => null, 'private_path' => null, 'mime' => null, 'size' => null, 'status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null];
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

    public function spanish(): static
    {
        return $this->state(['locale' => SupportedLocale::Spanish]);
    }

    private function publishedAttributes(bool $visible): array
    {
        return ['label' => 'Synthetic technical CV', 'private_path' => 'cv/synthetic-technical-cv.pdf', 'mime' => 'application/pdf', 'size' => 2048, 'status' => PublicationStatus::Published, 'is_visible' => $visible, 'published_at' => now()];
    }
}
