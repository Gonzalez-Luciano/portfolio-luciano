<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\SupportedLocale;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Profile */
final class ProfileResource extends JsonResource
{
    public function __construct(Profile $resource, private readonly SupportedLocale $locale)
    {
        parent::__construct($resource);
    }

    /** @return array{name: string, headline: string, short_summary: string, introduction: string, availability: string, cta: string, photo: ?array{url: string, alt: string}} */
    public function toArray(Request $request): array
    {
        $suffix = $this->locale->value;

        return [
            'name' => $this->name,
            'headline' => $this->{"headline_{$suffix}"},
            'short_summary' => $this->{"short_summary_{$suffix}"},
            'introduction' => $this->{"introduction_{$suffix}"},
            'availability' => $this->{"availability_{$suffix}"},
            'cta' => $this->{"cta_{$suffix}"},
            'photo' => $this->photo($suffix),
        ];
    }

    /** @return ?array{url: string, alt: string} */
    private function photo(string $suffix): ?array
    {
        if ($this->photo_public_path === null || ! Storage::disk('public')->exists($this->photo_public_path)) {
            return null;
        }

        return ['url' => '/storage/'.ltrim($this->photo_public_path, '/'), 'alt' => $this->{"photo_alt_{$suffix}"}];
    }
}
