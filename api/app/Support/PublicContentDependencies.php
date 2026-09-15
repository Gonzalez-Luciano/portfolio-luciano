<?php

namespace App\Support;

use App\Enums\PublicEndpoint;
use App\Models\CvDocument;
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

final class PublicContentDependencies
{
    /**
     * @param  class-string|object  $model
     * @return list<PublicEndpoint>
     */
    public function for(string|object $model): array
    {
        return match (is_object($model) ? $model::class : $model) {
            Profile::class => [PublicEndpoint::Profile],
            SiteConfiguration::class,
            ProfessionalLink::class,
            ExpertiseArea::class,
            WorkPrinciple::class,
            CvDocument::class => [PublicEndpoint::Site],
            Experience::class,
            ExperienceHighlight::class => [PublicEndpoint::Experiences],
            WorkCase::class => [PublicEndpoint::WorkCases],
            Project::class,
            ProjectImage::class => [PublicEndpoint::Projects],
            Technology::class => [
                PublicEndpoint::Technologies,
                PublicEndpoint::Experiences,
                PublicEndpoint::WorkCases,
                PublicEndpoint::Projects,
            ],
            default => throw new \InvalidArgumentException('No public content dependencies are defined for ['.(is_object($model) ? $model::class : $model).'].'),
        };
    }
}
