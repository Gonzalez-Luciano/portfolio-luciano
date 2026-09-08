<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\SupportedLocale;
use App\Models\Experience;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Experience */
final class ExperienceResource extends JsonResource
{
    public function __construct(Experience $resource, private readonly SupportedLocale $locale)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $suffix = $this->locale->value;

        return [
            'key' => $this->key,
            'organization' => $this->{"organization_label_{$suffix}"},
            'role' => $this->{"role_{$suffix}"},
            'start' => $this->month($this->start_year, $this->start_month),
            'end' => $this->end_year === null ? null : $this->month($this->end_year, $this->end_month),
            'summary' => $this->{"summary_{$suffix}"},
            'highlights' => $this->highlights->map(fn ($highlight): mixed => $highlight->{"content_{$suffix}"})->values()->all(),
            'technologies' => $this->technologies->map(fn ($technology): array => (new TechnologyResource($technology))->resolve())->values()->all(),
        ];
    }

    private function month(int $year, ?int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }
}
