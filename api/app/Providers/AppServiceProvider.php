<?php

namespace App\Providers;

use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\EditorialMutationGuard;
use App\Http\Responses\ApiErrorResponse;
use App\Models\CvDocument;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EditorialMutationContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            Profile::class, SiteConfiguration::class, Experience::class, ExperienceHighlight::class,
            WorkCase::class, Project::class, ProjectImage::class, Technology::class, ExpertiseArea::class,
            WorkPrinciple::class, ProfessionalLink::class, CvDocument::class, EducationEntry::class,
        ] as $model) {
            $model::observe(EditorialMutationGuard::class);
        }

        RateLimiter::for('public-api', static fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->ip())
            ->response(static fn (Request $request, array $headers): ApiErrorResponse => ApiErrorResponse::make(
                code: 'rate_limited',
                message: 'Too many requests. Please try again later.',
                details: [],
                status: 429,
            )->withHeaders($headers)));

        RateLimiter::for('cv-download', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
    }
}
