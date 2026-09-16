<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CvDocuments\Pages\EditCvDocument;
use App\Filament\Resources\EducationEntries\Pages\EditEducationEntry;
use App\Filament\Resources\Experiences\Pages\EditExperience;
use App\Filament\Resources\ExpertiseAreas\Pages\EditExpertiseArea;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\ProfessionalLinks\Pages\EditProfessionalLink;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Technologies\Pages\EditTechnology;
use App\Filament\Resources\WorkCases\Pages\EditWorkCase;
use App\Filament\Resources\WorkPrinciples\Pages\EditWorkPrinciple;
use App\Filament\Support\PublicationReviewPresenter;
use App\Filament\Support\ReviewLink;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;

/**
 * A single, cross-cutting, read-only page reviewing any one manageable
 * entity's record: Spanish/English content together, publication state,
 * order/relationships, contextual technologies, owned assets/CV info, and
 * the exact blocking issues `PublicationValidator` would raise right now.
 *
 * Addressing: `{type}/{record}` route parameters (not a query string).
 * Filament's `Page::routes()` registers `Route::get(getRoutePath($panel),
 * static::class)` and Livewire full-page components receive matching route
 * parameters directly as `mount()` arguments (confirmed against the
 * installed Filament 5.7 source: `Filament\Resources\Pages\EditRecord`
 * itself declares `mount(int|string $record)` for its own `{record}`
 * segment, and `Filament\Pages\Dashboard::getRoutePath()` shows
 * `getRoutePath()` is freely overridable). An unknown `type` throws
 * `ModelNotFoundException` (via `ReviewLink::modelClassForType()`) and a
 * missing/incorrect `record` throws it via `findOrFail()`; both become
 * Laravel's normal controlled 404, with no custom handling here.
 *
 * This page performs no mutation: it has no form, no `save()`, and no
 * Publish/Show/Hide/Return-to-draft/Delete actions. Those remain on each
 * entity's own Edit page.
 */
final class ReviewContent extends Page
{
    protected static ?string $slug = 'review-content';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.review-content';

    public ?Model $content = null;

    /** @var array<string, mixed> */
    public array $review = [];

    public ?string $editUrl = null;

    public static function getRoutePath(Panel $panel): string
    {
        return '/'.self::getSlug($panel).'/{type}/{record}';
    }

    public function mount(string $type, int|string $record): void
    {
        $class = ReviewLink::modelClassForType($type);

        $this->content = $class::query()->findOrFail($record);
        $this->review = app(PublicationReviewPresenter::class)->present($this->content);
        $this->editUrl = $this->resolveEditUrl($type, $this->content);
    }

    public function getTitle(): string
    {
        return ($this->review['title'] ?? 'Review').' review';
    }

    private function resolveEditUrl(string $type, Model $content): ?string
    {
        return match ($type) {
            'profile' => EditProfile::getUrl(),
            'site-configuration' => EditSiteConfiguration::getUrl(),
            'experience' => EditExperience::getUrl(['record' => $content]),
            'work-case' => EditWorkCase::getUrl(['record' => $content]),
            'project' => EditProject::getUrl(['record' => $content]),
            'technology' => EditTechnology::getUrl(['record' => $content]),
            'expertise-area' => EditExpertiseArea::getUrl(['record' => $content]),
            'work-principle' => EditWorkPrinciple::getUrl(['record' => $content]),
            'education-entry' => EditEducationEntry::getUrl(['record' => $content]),
            'language' => EditLanguage::getUrl(['record' => $content]),
            'professional-link' => EditProfessionalLink::getUrl(['record' => $content]),
            'cv-document' => EditCvDocument::getUrl(['record' => $content]),
            default => null,
        };
    }
}
