<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\SupportedLocale;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Project */
final class ProjectResource extends JsonResource
{
    public function __construct(Project $resource, private readonly SupportedLocale $locale)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $suffix = $this->locale->value;

        return [
            'key' => $this->key,
            'title' => $this->{"title_{$suffix}"},
            'summary' => $this->{"summary_{$suffix}"},
            'problem' => $this->{"problem_{$suffix}"},
            'solution' => $this->{"solution_{$suffix}"},
            'featured' => $this->featured,
            'image' => $this->image($suffix),
            'demo_url' => $this->demo_url,
            'repository_url' => $this->repository_url,
            'technologies' => $this->technologies->map(fn ($technology): array => (new TechnologyResource($technology))->resolve())->values()->all(),
        ];
    }

    /** @return ?array{url: string, alt: string} */
    private function image(string $suffix): ?array
    {
        if ($this->image_public_path === null || ! Storage::disk('public')->exists($this->image_public_path)) {
            return null;
        }

        return ['url' => '/storage/'.ltrim($this->image_public_path, '/'), 'alt' => $this->{"image_alt_{$suffix}"}];
    }
}
