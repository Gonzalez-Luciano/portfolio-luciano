<?php

namespace App\Filament\Support;

use App\Domain\Publishing\PublicationIssue;
use App\Domain\Publishing\PublicationValidator;
use App\Models\CvDocument;
use App\Models\Experience;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds the complete read-only snapshot shown on the `ReviewContent` page:
 * Spanish/English content together, publication state, order/relationships,
 * contextual technologies in order, owned assets/CV info, and the exact
 * blocking issues from `PublicationValidator`.
 *
 * Explicit-per-entity by design, mirroring `PublicationValidator::issues()`'s
 * own `match` dispatch: every branch below reads that entity's own known
 * fields/relations directly. This is deliberately not a generic/reflection-
 * based dumper — adding an eleventh manageable entity means adding an
 * eleventh branch here, exactly as it means adding one to the validator.
 *
 * Never exposes a raw filesystem path: `assetInfo()` reports only whether an
 * owned asset exists plus its MIME/size/alt text.
 */
final class PublicationReviewPresenter
{
    public function __construct(private readonly PublicationValidator $validator) {}

    /** @return array<string, mixed> */
    public function present(Model $content): array
    {
        return match ($content::class) {
            Profile::class => $this->profile($content),
            SiteConfiguration::class => $this->siteConfiguration($content),
            Experience::class => $this->experience($content),
            WorkCase::class => $this->workCase($content),
            Project::class => $this->project($content),
            Technology::class => $this->technology($content),
            ExpertiseArea::class => $this->expertiseArea($content),
            WorkPrinciple::class => $this->workPrinciple($content),
            ProfessionalLink::class => $this->professionalLink($content),
            CvDocument::class => $this->cvDocument($content),
            default => throw new \InvalidArgumentException('No publication review presenter is defined for ['.$content::class.'].'),
        };
    }

    /** @return array<string, mixed> */
    private function profile(Profile $profile): array
    {
        return array_merge($this->base('Profile', null, null, $profile->status->value, $profile->is_visible, $profile->published_at?->toDateTimeString()), [
            'bilingual' => [
                ['label' => 'Headline', 'es' => $profile->headline_es, 'en' => $profile->headline_en],
                ['label' => 'Short summary', 'es' => $profile->short_summary_es, 'en' => $profile->short_summary_en],
                ['label' => 'Introduction', 'es' => $profile->introduction_es, 'en' => $profile->introduction_en],
                ['label' => 'Availability', 'es' => $profile->availability_es, 'en' => $profile->availability_en],
                ['label' => 'Scroll statement: lead', 'es' => $profile->statement_lead_es, 'en' => $profile->statement_lead_en],
                ['label' => 'Scroll statement: emphasis', 'es' => $profile->statement_emphasis_es, 'en' => $profile->statement_emphasis_en],
                ['label' => 'Scroll statement: tail', 'es' => $profile->statement_tail_es, 'en' => $profile->statement_tail_en],
                ['label' => 'Closing title: line one', 'es' => $profile->closing_line_one_es, 'en' => $profile->closing_line_one_en],
                ['label' => 'Closing title: line two', 'es' => $profile->closing_line_two_es, 'en' => $profile->closing_line_two_en],
                ['label' => 'Call to action', 'es' => $profile->cta_es, 'en' => $profile->cta_en],
            ],
            'fields' => [
                ['label' => 'Name', 'value' => $profile->name],
            ],
            'assets' => [
                $this->assetInfo('Photo', $profile->photo_private_path, $profile->photo_mime, $profile->photo_size, $profile->photo_alt_es, $profile->photo_alt_en),
            ],
            'issues' => $this->issues($profile),
        ]);
    }

    /** @return array<string, mixed> */
    private function siteConfiguration(SiteConfiguration $configuration): array
    {
        return array_merge($this->base('Site configuration', null, null, $configuration->status->value, $configuration->is_visible, $configuration->published_at?->toDateTimeString()), [
            'bilingual' => [
                ['label' => 'Projects empty message', 'es' => $configuration->projects_empty_message_es, 'en' => $configuration->projects_empty_message_en],
                ['label' => 'Contact intro', 'es' => $configuration->contact_intro_es, 'en' => $configuration->contact_intro_en],
                ['label' => 'Backend label', 'es' => $configuration->technology_backend_label_es, 'en' => $configuration->technology_backend_label_en],
                ['label' => 'Data label', 'es' => $configuration->technology_data_label_es, 'en' => $configuration->technology_data_label_en],
                ['label' => 'Integration label', 'es' => $configuration->technology_integration_label_es, 'en' => $configuration->technology_integration_label_en],
                ['label' => 'Collaboration label', 'es' => $configuration->technology_collaboration_label_es, 'en' => $configuration->technology_collaboration_label_en],
            ],
            // Informative dependencies only: none of these ever appear
            // as blocking issues for SiteConfiguration's own
            // publication (`PublicationValidator::siteConfigurationIssues()`
            // never references them), an empty collection or absent CV
            // is a valid site state.
            'dependencies' => [
                $this->statusBreakdown('Professional links', ProfessionalLink::query()),
                $this->statusBreakdown('Expertise areas', ExpertiseArea::query()),
                $this->statusBreakdown('Work principles', WorkPrinciple::query()),
                $this->cvBreakdown(),
            ],
            'issues' => $this->issues($configuration),
        ]);
    }

    /** @return array<string, mixed> */
    private function experience(Experience $experience): array
    {
        $highlights = $experience->relationLoaded('highlights') ? $experience->getRelation('highlights') : $experience->highlights;
        $technologies = $experience->relationLoaded('technologies') ? $experience->getRelation('technologies') : $experience->technologies;

        return array_merge($this->base('Experience', $experience->key, $experience->position, $experience->status->value, $experience->is_visible, $experience->published_at?->toDateTimeString()), [
            'dates' => [
                ['label' => 'Start', 'value' => sprintf('%04d-%02d', $experience->start_year, $experience->start_month)],
                ['label' => 'End', 'value' => $experience->isCurrent() ? 'Current' : sprintf('%04d-%02d', $experience->end_year, $experience->end_month)],
            ],
            'bilingual' => [
                ['label' => 'Role', 'es' => $experience->role_es, 'en' => $experience->role_en],
                ['label' => 'Summary', 'es' => $experience->summary_es, 'en' => $experience->summary_en],
                ['label' => 'Organization label', 'es' => $experience->organization_label_es, 'en' => $experience->organization_label_en],
            ],
            'relationships' => [
                'Highlights' => $highlights->values()->map(fn ($highlight, int $position): array => [
                    'label' => 'Highlight '.($position + 1),
                    'es' => $highlight->content_es,
                    'en' => $highlight->content_en,
                ])->all(),
                'Contextual technologies' => $this->technologyList($technologies),
            ],
            'issues' => $this->issues($experience),
        ]);
    }

    /** @return array<string, mixed> */
    private function workCase(WorkCase $workCase): array
    {
        $technologies = $workCase->relationLoaded('technologies') ? $workCase->getRelation('technologies') : $workCase->technologies;

        return array_merge($this->base('Work case', $workCase->key, $workCase->position, $workCase->status->value, $workCase->is_visible, $workCase->published_at?->toDateTimeString()), [
            'bilingual' => [
                ['label' => 'Title', 'es' => $workCase->title_es, 'en' => $workCase->title_en],
                ['label' => 'Context', 'es' => $workCase->context_es, 'en' => $workCase->context_en],
                ['label' => 'Problem', 'es' => $workCase->problem_es, 'en' => $workCase->problem_en],
                ['label' => 'Contribution', 'es' => $workCase->contribution_es, 'en' => $workCase->contribution_en],
                ['label' => 'Technical approach', 'es' => $workCase->technical_approach_es, 'en' => $workCase->technical_approach_en],
                ['label' => 'Outcome', 'es' => $workCase->outcome_es, 'en' => $workCase->outcome_en],
            ],
            'relationships' => [
                'Contextual technologies' => $this->technologyList($technologies),
            ],
            'issues' => $this->issues($workCase),
        ]);
    }

    /** @return array<string, mixed> */
    private function project(Project $project): array
    {
        $technologies = $project->relationLoaded('technologies') ? $project->getRelation('technologies') : $project->technologies;

        return array_merge($this->base('Project', $project->key, $project->position, $project->status->value, $project->is_visible, $project->published_at?->toDateTimeString()), [
            'bilingual' => [
                ['label' => 'Title', 'es' => $project->title_es, 'en' => $project->title_en],
                ['label' => 'Role', 'es' => $project->role_es, 'en' => $project->role_en],
                ['label' => 'Summary', 'es' => $project->summary_es, 'en' => $project->summary_en],
                ['label' => 'Problem', 'es' => $project->problem_es, 'en' => $project->problem_en],
                ['label' => 'Solution', 'es' => $project->solution_es, 'en' => $project->solution_en],
                ['label' => 'Result', 'es' => $project->result_es, 'en' => $project->result_en],
            ],
            'fields' => [
                ['label' => 'Kind', 'value' => $project->kind?->value],
                ['label' => 'Client', 'value' => $project->client_name],
                ['label' => 'Delivery status', 'value' => $project->delivery_status?->value],
                ['label' => 'Featured', 'value' => $project->featured ? 'Yes' : 'No'],
                ['label' => 'Demo URL', 'value' => $project->demo_url],
                ['label' => 'Repository URL', 'value' => $project->repository_url],
            ],
            'relationships' => [
                'Contextual technologies' => $this->technologyList($technologies),
            ],
            'assets' => [
                $this->assetInfo('Image', $project->image_private_path, $project->image_mime, $project->image_size, $project->image_alt_es, $project->image_alt_en),
            ],
            'issues' => $this->issues($project),
        ]);
    }

    /** @return array<string, mixed> */
    private function technology(Technology $technology): array
    {
        $experiences = $technology->relationLoaded('experiences') ? $technology->getRelation('experiences') : $technology->experiences;
        $workCases = $technology->relationLoaded('workCases') ? $technology->getRelation('workCases') : $technology->workCases;
        $projects = $technology->relationLoaded('projects') ? $technology->getRelation('projects') : $technology->projects;

        return array_merge($this->base('Technology', $technology->key, $technology->position, $technology->status->value, $technology->is_visible, $technology->published_at?->toDateTimeString()), [
            'fields' => [
                ['label' => 'Name', 'value' => $technology->name],
                ['label' => 'Category', 'value' => $technology->category->name],
            ],
            'relationships' => [
                'Used by experiences' => $this->keyList($experiences),
                'Used by work cases' => $this->keyList($workCases),
                'Used by projects' => $this->keyList($projects),
            ],
            'assets' => [
                $this->assetInfo('Icon', $technology->icon_private_path, $technology->icon_mime, $technology->icon_size),
            ],
            'issues' => $this->issues($technology),
        ]);
    }

    /** @return array<string, mixed> */
    private function expertiseArea(ExpertiseArea $area): array
    {
        return array_merge($this->base('Expertise area', $area->key, $area->position, $area->status->value, $area->is_visible, $area->published_at?->toDateTimeString()), [
            'bilingual' => [
                ['label' => 'Title', 'es' => $area->title_es, 'en' => $area->title_en],
                ['label' => 'Description', 'es' => $area->description_es, 'en' => $area->description_en],
            ],
            'issues' => $this->issues($area),
        ]);
    }

    /** @return array<string, mixed> */
    private function workPrinciple(WorkPrinciple $principle): array
    {
        return array_merge($this->base('Work principle', $principle->key, $principle->position, $principle->status->value, $principle->is_visible, $principle->published_at?->toDateTimeString()), [
            'bilingual' => [
                ['label' => 'Statement', 'es' => $principle->statement_es, 'en' => $principle->statement_en],
            ],
            'issues' => $this->issues($principle),
        ]);
    }

    /** @return array<string, mixed> */
    private function professionalLink(ProfessionalLink $link): array
    {
        return array_merge($this->base('Professional link', null, $link->position, $link->status->value, $link->is_visible, $link->published_at?->toDateTimeString()), [
            'bilingual' => [
                ['label' => 'Label', 'es' => $link->label_es, 'en' => $link->label_en],
            ],
            'fields' => [
                ['label' => 'Type', 'value' => $link->type->value],
                ['label' => 'Destination', 'value' => $link->destination],
            ],
            'issues' => $this->issues($link),
        ]);
    }

    /** @return array<string, mixed> */
    private function cvDocument(CvDocument $document): array
    {
        return array_merge($this->base('CV document', null, null, $document->status->value, $document->is_visible, $document->published_at?->toDateTimeString()), [
            'fields' => [
                ['label' => 'Locale', 'value' => strtoupper($document->locale->value)],
                ['label' => 'Label', 'value' => $document->label],
            ],
            'assets' => [
                $this->assetInfo('PDF', $document->private_path, $document->mime, $document->size),
            ],
            'issues' => $this->issues($document),
        ]);
    }

    /** @return array<string, mixed> */
    private function base(string $title, ?string $key, ?int $position, string $status, bool $isVisible, ?string $publishedAt): array
    {
        return [
            'title' => $title,
            'key' => $key,
            'position' => $position,
            'status' => ucfirst($status),
            'is_visible' => $isVisible,
            'published_at' => $publishedAt,
            'dates' => [],
            'bilingual' => [],
            'fields' => [],
            'relationships' => [],
            'assets' => [],
            'dependencies' => [],
        ];
    }

    /**
     * @param  iterable<int, Technology>  $technologies
     * @return list<array{label: string, value: string}>
     */
    private function technologyList(iterable $technologies): array
    {
        return collect($technologies)->values()->map(fn (Technology $technology): array => [
            'label' => $technology->key,
            'value' => $technology->name,
        ])->all();
    }

    /** @return list<array{label: string, value: string}> */
    private function keyList(iterable $records): array
    {
        return collect($records)->values()->map(fn (Model $record): array => [
            'label' => $record->getAttribute('key'),
            'value' => ucfirst($record->status->value),
        ])->all();
    }

    /** @return array{label: string, exists: bool, mime: ?string, size: ?int, alt_es: ?string, alt_en: ?string} */
    private function assetInfo(string $label, ?string $privatePath, ?string $mime, ?int $size, ?string $altEs = null, ?string $altEn = null): array
    {
        return [
            'label' => $label,
            'exists' => $privatePath !== null,
            'mime' => $mime,
            'size' => $size,
            'alt_es' => $altEs,
            'alt_en' => $altEn,
        ];
    }

    /** @return array{label: string, summary: string} */
    private function statusBreakdown(string $label, Builder $query): array
    {
        $published = (clone $query)->where('status', 'published')->count();
        $draft = (clone $query)->where('status', 'draft')->count();

        return ['label' => $label, 'summary' => "{$published} published, {$draft} draft"];
    }

    /** @return array{label: string, summary: string} */
    private function cvBreakdown(): array
    {
        $spanish = CvDocument::query()->where('locale', 'es')->exists() ? 'present' : 'absent';
        $english = CvDocument::query()->where('locale', 'en')->exists() ? 'present' : 'absent';

        return ['label' => 'CV documents', 'summary' => "Spanish: {$spanish}, English: {$english}"];
    }

    /** @return list<array{code: string, path: string, message: string}> */
    private function issues(Model $content): array
    {
        return array_map(
            static fn (PublicationIssue $issue): array => ['code' => $issue->code, 'path' => $issue->path, 'message' => $issue->message],
            $this->validator->issues($content),
        );
    }
}
