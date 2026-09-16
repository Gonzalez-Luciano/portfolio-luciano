<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SiteResource;
use App\Models\CvDocument;
use App\Models\EducationEntry;
use App\Models\ExpertiseArea;
use App\Models\Language;
use App\Models\ProfessionalLink;
use App\Models\SiteConfiguration;
use App\Models\WorkPrinciple;
use App\Support\PublicContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class SiteController extends Controller
{
    public function __invoke(Request $request, PublicContentCache $cache): JsonResponse
    {
        $locale = $request->attributes->get('supported_locale');
        assert($locale instanceof SupportedLocale);
        $data = $cache->remember($locale, PublicEndpoint::Site, function () use ($locale): array {
            $site = SiteConfiguration::query()->publiclyAvailable()->where('singleton_key', 'default')->firstOrFail();
            $cv = CvDocument::query()->publiclyAvailable()->where('locale', $locale)->first();

            return (new SiteResource([
                'site' => $site,
                'locale' => $locale,
                'professional_links' => ProfessionalLink::query()->publiclyAvailable()->get(),
                'expertise_areas' => ExpertiseArea::query()->publiclyAvailable()->get(),
                'work_principles' => WorkPrinciple::query()->publiclyAvailable()->get(),
                'education' => EducationEntry::query()->publiclyAvailable()->get(),
                'languages' => Language::query()->publiclyAvailable()->get(),
                'cv' => $cv !== null && Storage::disk('local')->exists($cv->private_path)
                    ? ['url' => "/cv/luciano-gonzalez-{$locale->value}.pdf", 'label' => $cv->label]
                    : null,
            ]))->resolve();
        });

        return response()->json(['data' => $data]);
    }
}
