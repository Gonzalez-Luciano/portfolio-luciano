<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Models\Profile;
use App\Support\PublicContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileController extends Controller
{
    public function __invoke(Request $request, PublicContentCache $cache): JsonResponse
    {
        $locale = $request->attributes->get('supported_locale');
        assert($locale instanceof SupportedLocale);
        $data = $cache->remember($locale, PublicEndpoint::Profile, function () use ($locale): array {
            $profile = Profile::query()->publiclyAvailable()->where('singleton_key', 'default')->firstOrFail();

            return (new ProfileResource($profile, $locale))->resolve();
        });

        return response()->json(['data' => $data]);
    }
}
