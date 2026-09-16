<?php

namespace App\Domain\Publishing;

use App\Enums\ProfessionalLinkType;
use App\Enums\ProjectKind;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class PublicationValidator
{
    /**
     * Application maximum for names, titles, labels, roles, CTA text, and
     * other bounded `VARCHAR(255)` columns (spec section 8).
     */
    private const BOUNDED_MAX_LENGTH = 255;

    /**
     * Application maximum for `TEXT` narrative columns (summary,
     * introduction, problem, solution, approach, outcome, statement, and
     * highlight content). MySQL `TEXT` holds up to 65,535 bytes; this is a
     * generous, uniform application ceiling well under that limit even at
     * 4 bytes/character (spec section 8).
     */
    private const NARRATIVE_MAX_LENGTH = 10000;

    /**
     * Application maximum for alt text (spec section 8).
     */
    private const ALT_TEXT_MAX_LENGTH = 500;

    /** Maximum ordered screenshots per project (spec §4.2). */
    public const PROJECT_IMAGE_LIMIT = 12;

    public const PROJECT_IMAGE_MAX_BYTES = 8 * 1024 * 1024;

    /** @var list<string> */
    public const PROJECT_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Optional bilingual Profile copy used by the Phase 6 scroll scene.
     */
    private const PROFILE_SCENE_PAIRS = ['statement_lead', 'statement_emphasis', 'statement_tail', 'closing_line_one', 'closing_line_two'];

    /** @return list<PublicationIssue> */
    public function issues(Model $content): array
    {
        $class = $content::class;

        return match ($content::class) {
            Profile::class => $this->profileIssues($content),
            SiteConfiguration::class => $this->siteConfigurationIssues($content),
            Experience::class => $this->experienceIssues($content),
            WorkCase::class => $this->workCaseIssues($content),
            Project::class => $this->projectIssues($content),
            Technology::class => $this->technologyIssues($content),
            ExpertiseArea::class => $this->expertiseAreaIssues($content),
            WorkPrinciple::class => $this->workPrincipleIssues($content),
            EducationEntry::class => $this->educationEntryIssues($content),
            ProfessionalLink::class => $this->professionalLinkIssues($content),
            CvDocument::class => $this->cvDocumentIssues($content),
            default => throw new \InvalidArgumentException("No publication validator is defined for [{$class}]."),
        };
    }

    public function assertPublishable(Model $content): void
    {
        $this->assertIssues($this->issues($content));
    }

    public function assertKey(Model $content): void
    {
        $this->assertIssues($this->keyIssues($content));
    }

    /** @param list<PublicationIssue> $issues */
    public function assertIssues(array $issues): void
    {
        if ($issues !== []) {
            throw new PublicationValidationException($issues);
        }
    }

    /** @return list<PublicationIssue> */
    private function profileIssues(Profile $profile): array
    {
        return [
            ...$this->required($profile, ['name']),
            ...$this->requiredPairs($profile, ['headline', 'short_summary', 'introduction', 'availability', 'cta']),
            ...$this->optionalPairs($profile, self::PROFILE_SCENE_PAIRS),
            ...$this->imageAssetIssues($profile, 'photo', 5 * 1024 * 1024),
            ...$this->maxLength($profile, ['name'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($profile, ['cta'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($profile, ['headline', 'short_summary', 'introduction', 'availability', ...self::PROFILE_SCENE_PAIRS], self::NARRATIVE_MAX_LENGTH),
            ...$this->maxLength($profile, ['photo_alt_es', 'photo_alt_en'], self::ALT_TEXT_MAX_LENGTH),
        ];
    }

    /** @return list<PublicationIssue> */
    private function siteConfigurationIssues(SiteConfiguration $configuration): array
    {
        return [
            ...$this->requiredPairs($configuration, [
                'projects_empty_message', 'contact_intro', 'technology_backend_label', 'technology_data_label',
                'technology_integration_label', 'technology_collaboration_label',
            ]),
            ...$this->maxLengthPairs($configuration, ['projects_empty_message', 'contact_intro'], self::NARRATIVE_MAX_LENGTH),
            ...$this->maxLengthPairs($configuration, [
                'technology_backend_label', 'technology_data_label', 'technology_integration_label', 'technology_collaboration_label',
            ], self::BOUNDED_MAX_LENGTH),
        ];
    }

    /** @return list<PublicationIssue> */
    private function experienceIssues(Experience $experience): array
    {
        $issues = [
            ...$this->keyIssues($experience),
            ...$this->requiredPairs($experience, ['role', 'summary']),
            ...$this->optionalPairs($experience, ['organization_label']),
            ...$this->experienceDateIssues($experience),
            ...$this->maxLengthPairs($experience, ['role', 'organization_label'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($experience, ['summary'], self::NARRATIVE_MAX_LENGTH),
        ];

        $highlights = $experience->relationLoaded('highlights')
            ? $experience->getRelation('highlights')
            : $experience->highlights()->get();

        foreach ($highlights->values() as $index => $highlight) {
            $issues = [...$issues, ...$this->highlightIssues($highlight, "highlights.{$index}")];
        }

        return $issues;
    }

    /** @return list<PublicationIssue> */
    private function workCaseIssues(WorkCase $workCase): array
    {
        return [
            ...$this->keyIssues($workCase),
            ...$this->requiredPairs($workCase, ['title', 'context', 'problem', 'contribution', 'technical_approach', 'outcome']),
            ...$this->maxLengthPairs($workCase, ['title'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($workCase, ['context', 'problem', 'contribution', 'technical_approach', 'outcome'], self::NARRATIVE_MAX_LENGTH),
        ];
    }

    /** @return list<PublicationIssue> */
    private function projectIssues(Project $project): array
    {
        return [
            ...$this->keyIssues($project),
            ...$this->requiredPairs($project, ['title', 'summary', 'problem', 'solution', 'role', 'result']),
            ...$this->required($project, ['delivery_status']),
            ...$this->projectClientIssues($project),
            ...$this->httpsUrlIssues($project, ['demo_url', 'repository_url']),
            ...$this->projectImageIssues($project),
            ...$this->maxLength($project, ['client_name'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($project, ['title', 'role'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($project, ['summary', 'problem', 'solution', 'result'], self::NARRATIVE_MAX_LENGTH),
        ];
    }

    /** @return list<PublicationIssue> */
    private function projectImageIssues(Project $project): array
    {
        /** @var Collection<int, ProjectImage> $images */
        $images = $project->relationLoaded('images')
            ? $project->getRelation('images')
            : ($project->exists ? $project->images()->get() : collect());

        $issues = [];
        if ($images->count() > self::PROJECT_IMAGE_LIMIT) {
            $issues[] = $this->issue('too_many_images', 'images', 'A project can have at most 12 images.');
        }

        foreach ($images->values() as $index => $image) {
            $prefix = "images.{$index}.";
            $issues = [
                ...$issues,
                ...$this->requiredPairs($image, ['alt'], $prefix),
                ...$this->maxLengthPairs($image, ['alt'], self::ALT_TEXT_MAX_LENGTH, $prefix),
            ];
            if (! in_array($image->mime, self::PROJECT_IMAGE_MIMES, true)) {
                $issues[] = $this->issue('invalid_asset_mime', "{$prefix}mime", 'The owned asset MIME type is not allowed.');
            }
            if ((int) $image->size < 1 || (int) $image->size > self::PROJECT_IMAGE_MAX_BYTES) {
                $issues[] = $this->issue('asset_size_exceeded', "{$prefix}size", 'The owned asset exceeds the allowed size.');
            }
        }

        return $issues;
    }

    /** @return list<PublicationIssue> */
    private function projectClientIssues(Project $project): array
    {
        $kind = $project->kind instanceof ProjectKind ? $project->kind : ProjectKind::tryFrom((string) $project->kind);

        return match (true) {
            $kind === null => [$this->issue('required', 'kind', 'The project kind is required for publication.')],
            $kind === ProjectKind::Client && ! $this->hasValue($project->client_name) => [$this->issue('required', 'client_name', 'A client project requires the client name.')],
            $kind === ProjectKind::Personal && $this->hasValue($project->client_name) => [$this->issue('client_name_not_allowed', 'client_name', 'A personal project cannot name a client.')],
            default => [],
        };
    }

    /** @return list<PublicationIssue> */
    private function technologyIssues(Technology $technology): array
    {
        return [
            ...$this->keyIssues($technology),
            ...$this->required($technology, ['name']),
            ...$this->iconAssetIssues($technology),
            ...$this->maxLength($technology, ['name'], self::BOUNDED_MAX_LENGTH),
        ];
    }

    /** @return list<PublicationIssue> */
    private function expertiseAreaIssues(ExpertiseArea $area): array
    {
        return [
            ...$this->keyIssues($area),
            ...$this->requiredPairs($area, ['title']),
            ...$this->optionalPairs($area, ['description']),
            ...$this->maxLengthPairs($area, ['title'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($area, ['description'], self::NARRATIVE_MAX_LENGTH),
        ];
    }

    /** @return list<PublicationIssue> */
    private function workPrincipleIssues(WorkPrinciple $principle): array
    {
        return [
            ...$this->keyIssues($principle),
            ...$this->requiredPairs($principle, ['statement']),
            ...$this->maxLengthPairs($principle, ['statement'], self::NARRATIVE_MAX_LENGTH),
        ];
    }

    /** @return list<PublicationIssue> */
    private function educationEntryIssues(EducationEntry $entry): array
    {
        return [
            ...$this->keyIssues($entry),
            ...$this->required($entry, ['institution']),
            ...$this->requiredPairs($entry, ['program']),
            ...$this->optionalPairs($entry, ['detail']),
            ...$this->educationYearIssues($entry),
            ...$this->maxLength($entry, ['institution'], self::BOUNDED_MAX_LENGTH),
            ...$this->maxLengthPairs($entry, ['program', 'detail'], self::BOUNDED_MAX_LENGTH),
        ];
    }

    /** @return list<PublicationIssue> */
    private function educationYearIssues(EducationEntry $entry): array
    {
        $issues = [];
        foreach (['start_year', 'end_year'] as $field) {
            $year = $entry->getAttribute($field);
            if ($year !== null && ((int) $year < 1000 || (int) $year > 9999)) {
                $issues[] = $this->issue('invalid_year', $field, 'The year must have four digits.');
            }
        }
        if ($issues === [] && $entry->start_year !== null && $entry->end_year !== null && (int) $entry->end_year < (int) $entry->start_year) {
            $issues[] = $this->issue('invalid_date_range', 'end_year', 'The end year must not precede the start year.');
        }

        return $issues;
    }

    /** @return list<PublicationIssue> */
    private function professionalLinkIssues(ProfessionalLink $link): array
    {
        $issues = [
            ...$this->requiredPairs($link, ['label']),
            ...$this->maxLengthPairs($link, ['label'], self::BOUNDED_MAX_LENGTH),
        ];
        $type = $link->type instanceof ProfessionalLinkType ? $link->type : ProfessionalLinkType::tryFrom((string) $link->type);
        $destination = (string) $link->destination;

        if ($type === ProfessionalLinkType::Email && (! filter_var($destination, FILTER_VALIDATE_EMAIL) || str_starts_with($destination, 'mailto:'))) {
            $issues[] = $this->issue('invalid_email_destination', 'destination', 'The email destination must be a valid email address without a mailto prefix.');
        }

        if (in_array($type, [ProfessionalLinkType::LinkedIn, ProfessionalLinkType::GitHub], true) && ! $this->isHttpsUrl($destination)) {
            $issues[] = $this->issue('invalid_https_url', 'destination', 'The destination must be a valid HTTPS URL.');
        }

        return $issues;
    }

    /** @return list<PublicationIssue> */
    private function cvDocumentIssues(CvDocument $document): array
    {
        $issues = [
            ...$this->required($document, ['label']),
            ...$this->maxLength($document, ['label'], self::BOUNDED_MAX_LENGTH),
        ];
        $hasAny = $this->hasValue($document->private_path) || $this->hasValue($document->mime) || $document->size !== null;

        if (! $hasAny) {
            return [...$issues, $this->issue('asset_required', 'private_path', 'A published CV requires a private PDF.')];
        }

        if (! $this->hasValue($document->private_path) || ! $this->hasValue($document->mime) || $document->size === null) {
            return [...$issues, $this->issue('asset_metadata_incomplete', 'private_path', 'The private PDF metadata is incomplete.')];
        }

        if ($document->mime !== 'application/pdf' || ! Str::endsWith((string) $document->private_path, '.pdf')) {
            $issues[] = $this->issue('invalid_asset_mime', 'mime', 'The CV must be a PDF.');
        }

        if ((int) $document->size > 5 * 1024 * 1024 || (int) $document->size < 1) {
            $issues[] = $this->issue('asset_size_exceeded', 'size', 'The CV exceeds the allowed size.');
        }

        return $issues;
    }

    /** @return list<PublicationIssue> */
    private function keyIssues(Model $content): array
    {
        if (! array_key_exists('key', $content->getAttributes())) {
            return [];
        }

        $key = (string) $content->getAttribute('key');
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key) !== 1) {
            return [$this->issue('invalid_key', 'key', 'The public key must be a lowercase ASCII slug.')];
        }

        if ($content->exists && $content::query()->where('key', $key)->whereKeyNot($content->getKey())->exists()) {
            return [$this->issue('key_not_unique', 'key', 'The public key is already in use.')];
        }

        return [];
    }

    /** @return list<PublicationIssue> */
    private function experienceDateIssues(Experience $experience): array
    {
        $issues = [];
        $startYear = (int) $experience->start_year;
        $startMonth = (int) $experience->start_month;
        $endYear = $experience->end_year === null ? null : (int) $experience->end_year;
        $endMonth = $experience->end_month === null ? null : (int) $experience->end_month;

        if ($startYear < 1000 || $startYear > 9999 || $startMonth < 1 || $startMonth > 12) {
            $issues[] = $this->issue('invalid_start_date', 'start_year', 'The start month and year are invalid.');
        }

        if (($endYear === null) !== ($endMonth === null)) {
            $issues[] = $this->issue('date_pair', 'end_year', 'The end month and year must be present together.');
        } elseif ($endYear !== null && ($endYear < 1000 || $endYear > 9999 || $endMonth < 1 || $endMonth > 12 || ($endYear === $startYear && $endMonth < $startMonth) || $endYear < $startYear)) {
            $issues[] = $this->issue('invalid_date_range', 'end_year', 'The end date must not precede the start date.');
        }

        return $issues;
    }

    /** @return list<PublicationIssue> */
    private function highlightIssues(ExperienceHighlight $highlight, string $prefix): array
    {
        return [
            ...$this->requiredPairs($highlight, ['content'], "{$prefix}."),
            ...$this->maxLengthPairs($highlight, ['content'], self::NARRATIVE_MAX_LENGTH, "{$prefix}."),
        ];
    }

    /** @param list<string> $fields @return list<PublicationIssue> */
    private function required(Model $content, array $fields): array
    {
        $issues = [];
        foreach ($fields as $field) {
            if (! $this->hasValue($content->getAttribute($field))) {
                $issues[] = $this->issue('required', $field, 'This field is required for publication.');
            }
        }

        return $issues;
    }

    /** @param list<string> $pairs @return list<PublicationIssue> */
    private function requiredPairs(Model $content, array $pairs, string $prefix = ''): array
    {
        $issues = [];
        foreach ($pairs as $pair) {
            foreach (['es', 'en'] as $locale) {
                $field = "{$pair}_{$locale}";
                if (! $this->hasValue($content->getAttribute($field))) {
                    $issues[] = $this->issue('required_translation', "{$prefix}{$field}", 'Both Spanish and English values are required for publication.');
                }
            }
        }

        return $issues;
    }

    /** @param list<string> $pairs @return list<PublicationIssue> */
    private function optionalPairs(Model $content, array $pairs): array
    {
        $issues = [];
        foreach ($pairs as $pair) {
            if ($this->hasValue($content->getAttribute("{$pair}_es")) !== $this->hasValue($content->getAttribute("{$pair}_en"))) {
                $issues[] = $this->issue('translation_pair', $pair, 'Spanish and English values must be present together.');
            }
        }

        return $issues;
    }

    /** @param list<string> $fields @return list<PublicationIssue> */
    private function maxLength(Model $content, array $fields, int $max, string $prefix = ''): array
    {
        $issues = [];
        foreach ($fields as $field) {
            $value = $content->getAttribute($field);
            if (is_string($value) && mb_strlen($value) > $max) {
                $issues[] = $this->issue('max_length_exceeded', "{$prefix}{$field}", "This field must not exceed {$max} characters.");
            }
        }

        return $issues;
    }

    /** @param list<string> $pairs @return list<PublicationIssue> */
    private function maxLengthPairs(Model $content, array $pairs, int $max, string $prefix = ''): array
    {
        $fields = [];
        foreach ($pairs as $pair) {
            $fields[] = "{$pair}_es";
            $fields[] = "{$pair}_en";
        }

        return $this->maxLength($content, $fields, $max, $prefix);
    }

    /** @param list<string> $fields @return list<PublicationIssue> */
    private function httpsUrlIssues(Model $content, array $fields): array
    {
        $issues = [];
        foreach ($fields as $field) {
            $value = $content->getAttribute($field);
            if ($this->hasValue($value) && ! $this->isHttpsUrl((string) $value)) {
                $issues[] = $this->issue('invalid_https_url', $field, 'The destination must be a valid HTTPS URL.');
            }
        }

        return $issues;
    }

    /** @return list<PublicationIssue> */
    private function imageAssetIssues(Model $content, string $prefix, int $maximumBytes): array
    {
        $issues = $this->assetMetadataIssues($content, $prefix, ['image/jpeg', 'image/png', 'image/webp'], $maximumBytes);
        if (! $this->hasValue($content->getAttribute("{$prefix}_private_path"))) {
            return $issues;
        }

        $es = $this->hasValue($content->getAttribute("{$prefix}_alt_es"));
        $en = $this->hasValue($content->getAttribute("{$prefix}_alt_en"));
        if ($es !== $en) {
            $issues[] = $this->issue('translation_pair', "{$prefix}_alt", 'Image alt text must be present in both locales.');
        } elseif (! $es) {
            $issues[] = $this->issue('required_translation', "{$prefix}_alt_es", 'Image alt text is required in both locales.');
            $issues[] = $this->issue('required_translation', "{$prefix}_alt_en", 'Image alt text is required in both locales.');
        }

        return $issues;
    }

    /** @return list<PublicationIssue> */
    private function iconAssetIssues(Technology $technology): array
    {
        return $this->assetMetadataIssues($technology, 'icon', ['image/png', 'image/webp'], 1024 * 1024);
    }

    /** @param list<string> $allowedMimes @return list<PublicationIssue> */
    private function assetMetadataIssues(Model $content, string $prefix, array $allowedMimes, int $maximumBytes): array
    {
        $path = $content->getAttribute("{$prefix}_private_path");
        $mime = $content->getAttribute("{$prefix}_mime");
        $size = $content->getAttribute("{$prefix}_size");
        $hasAny = $this->hasValue($path) || $this->hasValue($mime) || $size !== null;
        if (! $hasAny) {
            return [];
        }

        if (! $this->hasValue($path) || ! $this->hasValue($mime) || $size === null) {
            return [$this->issue('asset_metadata_incomplete', "{$prefix}_private_path", 'Owned asset metadata is incomplete.')];
        }

        $issues = [];
        if (! in_array($mime, $allowedMimes, true)) {
            $issues[] = $this->issue('invalid_asset_mime', "{$prefix}_mime", 'The owned asset MIME type is not allowed.');
        }
        if ((int) $size < 1 || (int) $size > $maximumBytes) {
            $issues[] = $this->issue('asset_size_exceeded', "{$prefix}_size", 'The owned asset exceeds the allowed size.');
        }

        return $issues;
    }

    private function hasValue(mixed $value): bool
    {
        return ! is_string($value) ? $value !== null : trim($value) !== '';
    }

    private function isHttpsUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false && parse_url($value, PHP_URL_SCHEME) === 'https';
    }

    private function issue(string $code, string $path, string $message): PublicationIssue
    {
        return new PublicationIssue($code, $path, $message);
    }
}
