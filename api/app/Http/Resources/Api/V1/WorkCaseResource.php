<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\SupportedLocale;
use App\Models\WorkCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkCase */
final class WorkCaseResource extends JsonResource
{
    public function __construct(WorkCase $resource, private readonly SupportedLocale $locale)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $suffix = $this->locale->value;

        return [
            'key' => $this->key,
            'experience_key' => $this->experience?->key,
            'title' => $this->{"title_{$suffix}"},
            'context' => $this->{"context_{$suffix}"},
            'problem' => $this->{"problem_{$suffix}"},
            'contribution' => $this->{"contribution_{$suffix}"},
            'technical_approach' => $this->{"technical_approach_{$suffix}"},
            'outcome' => $this->{"outcome_{$suffix}"},
            'technologies' => $this->technologies->map(fn ($technology): array => (new TechnologyResource($technology))->resolve())->values()->all(),
        ];
    }
}
