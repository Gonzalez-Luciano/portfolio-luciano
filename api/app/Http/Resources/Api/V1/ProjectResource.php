<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\SupportedLocale;
use App\Models\Project;
use App\Models\ProjectImage;
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
            'kind' => $this->kind->value,
            'client_name' => $this->client_name,
            'title' => $this->{"title_{$suffix}"},
            'role' => $this->{"role_{$suffix}"},
            'status' => $this->delivery_status?->value,
            'summary' => $this->{"summary_{$suffix}"},
            'problem' => $this->{"problem_{$suffix}"},
            'solution' => $this->{"solution_{$suffix}"},
            'result' => $this->{"result_{$suffix}"},
            'featured' => $this->featured,
            'images' => $this->images($suffix),
            'demo_url' => $this->demo_url,
            'repository_url' => $this->repository_url,
            'technologies' => $this->technologies->map(fn ($technology): array => (new TechnologyResource($technology))->resolve())->values()->all(),
        ];
    }

    /** @return list<array{url: string, alt: string}> */
    private function images(string $suffix): array
    {
        return $this->resource->images
            ->filter(static fn (ProjectImage $image): bool => $image->public_path !== null && Storage::disk('public')->exists($image->public_path))
            ->map(static fn (ProjectImage $image): array => ['url' => '/storage/'.ltrim($image->public_path, '/'), 'alt' => $image->{"alt_{$suffix}"}])
            ->values()
            ->all();
    }
}
