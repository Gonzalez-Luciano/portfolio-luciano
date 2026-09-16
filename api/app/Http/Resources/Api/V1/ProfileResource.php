<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\SupportedLocale;
use App\Enums\WorkMode;
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

    /** @return array{name: string, location: ?string, work_modes: list<string>, headline: string, short_summary: string, introduction: string, availability: string, statement: ?array{lead: string, emphasis: string, tail: string}, closing: ?array{line_one: string, line_two: string}, cta: string, photo: ?array{url: string, alt: string}} */
    public function toArray(Request $request): array
    {
        $suffix = $this->locale->value;

        return [
            'name' => $this->name,
            'location' => $this->location,
            'work_modes' => $this->workModes(),
            'headline' => $this->{"headline_{$suffix}"},
            'short_summary' => $this->{"short_summary_{$suffix}"},
            'introduction' => $this->{"introduction_{$suffix}"},
            'availability' => $this->{"availability_{$suffix}"},
            'statement' => $this->group($suffix, ['lead' => 'statement_lead', 'emphasis' => 'statement_emphasis', 'tail' => 'statement_tail']),
            'closing' => $this->group($suffix, ['line_one' => 'closing_line_one', 'line_two' => 'closing_line_two']),
            'cta' => $this->{"cta_{$suffix}"},
            'photo' => $this->photo($suffix),
        ];
    }

    /**
     * An optional copy group is public only when every one of its localized parts is filled.
     *
     * @param  array<string, string>  $fields  output key => column prefix
     * @return ?array<string, string>
     */
    private function group(string $suffix, array $fields): ?array
    {
        $group = [];

        foreach ($fields as $key => $column) {
            $value = $this->{"{$column}_{$suffix}"};

            if (! is_string($value) || trim($value) === '') {
                return null;
            }

            $group[$key] = $value;
        }

        return $group;
    }

    /** @return list<string> Selected modes in WorkMode declaration order. */
    private function workModes(): array
    {
        $selected = is_array($this->work_modes) ? $this->work_modes : [];

        return array_values(array_filter(
            array_map(static fn (WorkMode $mode): string => $mode->value, WorkMode::cases()),
            static fn (string $mode): bool => in_array($mode, $selected, true),
        ));
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
