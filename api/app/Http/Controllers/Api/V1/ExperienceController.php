<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ExperienceResource;
use App\Models\Experience;
use App\Support\PublicContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ExperienceController extends Controller
{
    public function __invoke(Request $request, PublicContentCache $cache): JsonResponse
    {
        $locale = $request->attributes->get('supported_locale');
        assert($locale instanceof SupportedLocale);
        $data = $cache->remember($locale, PublicEndpoint::Experiences, function () use ($locale): array {
            $experiences = Experience::query()->publiclyAvailable()->with([
                'highlights',
                'technologies' => static fn ($query) => $query->publiclyAvailable()
                    ->reorder()
                    ->orderByPivot('position')
                    ->orderBy('technologies.key'),
            ])->get();

            return $experiences->map(fn (Experience $experience): array => (new ExperienceResource($experience, $locale))->resolve())->values()->all();
        });

        return response()->json(['data' => $data]);
    }
}
