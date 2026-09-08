<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PublicationStatus;
use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use App\Support\PublicContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProjectController extends Controller
{
    public function __invoke(Request $request, PublicContentCache $cache): JsonResponse
    {
        $locale = $request->attributes->get('supported_locale');
        assert($locale instanceof SupportedLocale);
        $data = $cache->remember($locale, PublicEndpoint::Projects, function () use ($locale): array {
            $projects = Project::query()->publiclyAvailable()->with([
                'technologies' => static fn ($query) => $query->reorder()
                    ->where('status', PublicationStatus::Published)
                    ->where('is_visible', true)
                    ->orderByPivot('position')
                    ->orderBy('technologies.key'),
            ])->get();

            return $projects->map(fn (Project $project): array => (new ProjectResource($project, $locale))->resolve())->values()->all();
        });

        return response()->json(['data' => $data]);
    }
}
