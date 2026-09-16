<?php

namespace App\Filament\Support;

use App\Filament\Pages\ReviewContent;
use App\Models\CvDocument;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Closure;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The single place that maps a manageable entity model to the
 * `ReviewContent` page's `{type}` route segment, and builds the plain
 * link-style "Review" action every admin surface (the two singleton pages
 * and the eight Resources) points at that page with. Kept separate from
 * `PublicationReviewPresenter` because this is UI/routing glue (matching
 * the `EditorialActions` precedent), not presentation of entity content.
 */
final class ReviewLink
{
    /** @var array<class-string<Model>, string> */
    private const TYPES = [
        Profile::class => 'profile',
        SiteConfiguration::class => 'site-configuration',
        Experience::class => 'experience',
        WorkCase::class => 'work-case',
        Project::class => 'project',
        Technology::class => 'technology',
        ExpertiseArea::class => 'expertise-area',
        WorkPrinciple::class => 'work-principle',
        EducationEntry::class => 'education-entry',
        ProfessionalLink::class => 'professional-link',
        CvDocument::class => 'cv-document',
    ];

    public static function url(Model $record): string
    {
        $class = $record::class;
        $type = self::TYPES[$class] ?? throw new \InvalidArgumentException("No review type is registered for [{$class}].");

        return ReviewContent::getUrl(['type' => $type, 'record' => $record->getKey()]);
    }

    /**
     * A header action for a singleton page, where the record is only known
     * once the page has mounted.
     *
     * @param  Closure(): Model  $record
     */
    public static function action(Closure $record): Action
    {
        return Action::make('review')
            ->label('Review')
            ->color('gray')
            ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
            ->url(fn (): string => self::url($record()));
    }

    /**
     * A table row action for a Resource's list page, where Filament
     * supplies the record directly.
     */
    public static function rowAction(): Action
    {
        return Action::make('review')
            ->label('Review')
            ->color('gray')
            ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
            ->url(fn (Model $record): string => self::url($record));
    }

    /** @return class-string<Model> */
    public static function modelClassForType(string $type): string
    {
        $class = array_search($type, self::TYPES, true);

        if ($class === false) {
            throw new ModelNotFoundException("No manageable entity is registered for review type [{$type}].");
        }

        return $class;
    }
}
