<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\ProfessionalLinkType;
use App\Enums\SupportedLocale;
use App\Enums\TechnologyCategory;
use App\Models\SiteConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array{site: SiteConfiguration, locale: SupportedLocale, professional_links: iterable, expertise_areas: iterable, work_principles: iterable, education: iterable, cv: ?array{url: string, label: string}} */
final class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $site = $this['site'];
        $locale = $this['locale'];
        $suffix = $locale->value;

        return [
            'projects_empty_message' => $site->{"projects_empty_message_{$suffix}"},
            'contact_intro' => $site->{"contact_intro_{$suffix}"},
            'technology_groups' => array_map(
                fn (TechnologyCategory $category): array => [
                    'key' => $category->value,
                    'label' => $site->{"technology_{$category->value}_label_{$suffix}"},
                ],
                TechnologyCategory::cases(),
            ),
            'professional_links' => collect($this['professional_links'])->map(fn ($link): array => [
                'key' => $link->type->value,
                'label' => $link->{"label_{$suffix}"},
                'href' => $link->type === ProfessionalLinkType::Email ? 'mailto:'.$link->destination : $link->destination,
            ])->values()->all(),
            'expertise_areas' => collect($this['expertise_areas'])->map(fn ($area): array => [
                'key' => $area->key,
                'title' => $area->{"title_{$suffix}"},
                'description' => $area->{"description_{$suffix}"},
            ])->values()->all(),
            'work_principles' => collect($this['work_principles'])->map(fn ($principle): array => [
                'key' => $principle->key,
                'statement' => $principle->{"statement_{$suffix}"},
            ])->values()->all(),
            'education' => collect($this['education'])->map(fn ($entry): array => [
                'key' => $entry->key,
                'institution' => $entry->institution,
                'program' => $entry->{"program_{$suffix}"},
                'detail' => $entry->{"detail_{$suffix}"},
                'start_year' => $entry->start_year,
                'end_year' => $entry->end_year,
            ])->values()->all(),
            'cv' => $this['cv'],
        ];
    }
}
