<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TechnologyResource;
use App\Models\Technology;
use App\Support\PublicContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TechnologyController extends Controller
{
    public function __invoke(Request $request, PublicContentCache $cache): JsonResponse
    {
        $locale = $request->attributes->get('supported_locale');
        assert($locale instanceof SupportedLocale);
        $data = $cache->remember($locale, PublicEndpoint::Technologies, static function (): array {
            return Technology::query()->publiclyAvailable()->get()
                ->map(fn (Technology $technology): array => (new TechnologyResource($technology))->resolve())
                ->values()
                ->all();
        });

        return response()->json(['data' => $data]);
    }
}
