<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WorkCaseResource;
use App\Models\WorkCase;
use App\Support\PublicContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkCaseController extends Controller
{
    public function __invoke(Request $request, PublicContentCache $cache): JsonResponse
    {
        $locale = $request->attributes->get('supported_locale');
        assert($locale instanceof SupportedLocale);
        $data = $cache->remember($locale, PublicEndpoint::WorkCases, function () use ($locale): array {
            $workCases = WorkCase::query()->publiclyAvailable()->with([
                'experience' => static fn ($query) => $query->publiclyAvailable(),
                'technologies' => static fn ($query) => $query->publiclyAvailable()
                    ->reorder()
                    ->orderByPivot('position')
                    ->orderBy('technologies.key'),
            ])->get();

            return $workCases->map(fn (WorkCase $workCase): array => (new WorkCaseResource($workCase, $locale))->resolve())->values()->all();
        });

        return response()->json(['data' => $data]);
    }
}
