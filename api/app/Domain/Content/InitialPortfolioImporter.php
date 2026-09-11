<?php

namespace App\Domain\Content;

use App\Domain\Assets\AssetLifecycleService;
use App\Domain\Publishing\EditorialMutationContext;
use App\Enums\ProfessionalLinkType;
use App\Enums\PublicationStatus;
use App\Enums\SupportedLocale;
use App\Enums\TechnologyCategory;
use App\Models\CvDocument;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Guarded one-time importer for the approved initial portfolio content
 * (spec 2026-09-09-phase-5-public-site-design.md §29).
 *
 * It fills the two existing pristine Phase 4 structural singletons
 * (`profiles.default`, `site_configurations.default`) in place and creates the
 * approved dataset collections as draft, hidden, unpublished rows. It NEVER
 * creates replacement singleton rows, publishes anything, invokes a Filament
 * publication action, or creates users.
 *
 * Nothing mutates until every preflight passes:
 *
 *  1. the exact empty editorial baseline (spec §29.2) — the two structural
 *     singletons must be editorially pristine and the other twelve managed
 *     content tables literally empty;
 *  2. dataset shape / stable-key uniqueness / category coherence;
 *  3. source-asset existence and validity against the real Phase 4 owned-asset
 *     policy (image MIME/dimensions/size; PDF extension/MIME/size), including
 *     the deterministic es -> cv-es.pdf / en -> cv-en.pdf mapping.
 *
 * All writes then run inside ONE database transaction and
 * {@see EditorialMutationContext::run()} so the Phase 4 mutation guard stays
 * authoritative. Photo and CV originals are stored through the reused
 * {@see AssetLifecycleService::replace()}. A database rollback cannot undo
 * filesystem writes, so this importer tracks the exact private paths its own
 * `replace()` calls produced and, on any failure after an asset write, deletes
 * only those newly created files — never a preexisting file, never the public
 * disk. This is a small command-specific compensation, not a generic media
 * transaction abstraction.
 */
final class InitialPortfolioImporter
{
    /** The twelve tables that must be literally empty (spec §29.2). */
    private const EMPTY_TABLES = [
        'experiences', 'experience_highlights', 'work_cases', 'projects',
        'technologies', 'expertise_areas', 'work_principles',
        'professional_links', 'cv_documents', 'experience_technology',
        'technology_work_case', 'project_technology',
    ];

    /** Profile editorial columns; every one must be null on a pristine singleton. */
    private const PROFILE_EDITORIAL_COLUMNS = [
        'name', 'headline_es', 'headline_en', 'short_summary_es', 'short_summary_en',
        'introduction_es', 'introduction_en', 'availability_es', 'availability_en',
        'cta_es', 'cta_en', 'photo_private_path', 'photo_public_path', 'photo_mime',
        'photo_size', 'photo_alt_es', 'photo_alt_en',
    ];

    /** SiteConfiguration editorial columns; every one must be null on a pristine singleton. */
    private const SITE_EDITORIAL_COLUMNS = [
        'projects_empty_message_es', 'projects_empty_message_en',
        'contact_intro_es', 'contact_intro_en',
        'technology_backend_label_es', 'technology_backend_label_en',
        'technology_data_label_es', 'technology_data_label_en',
        'technology_integration_label_es', 'technology_integration_label_en',
        'technology_collaboration_label_es', 'technology_collaboration_label_en',
    ];

    /**
     * Publication-state columns the importer owns outright: whatever the Task 11
     * dataset carries for these is discarded and re-stamped to
     * draft / hidden / unpublished, without exception (spec §29.3).
     *
     * @var list<string>
     */
    private const PUBLICATION_STATE_KEYS = ['status', 'is_visible', 'published_at'];

    /** @var list<string> */
    private const PHOTO_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Real Phase 4 owned-asset ceiling for the profile photo and each CV. */
    private const MAX_ASSET_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private readonly AssetLifecycleService $assets,
        private readonly EditorialMutationContext $context,
    ) {}

    /**
     * @param  array<string, mixed>  $content  {@see InitialPortfolioContent::data()}
     *                                         (tests may pass a deliberately malformed copy)
     */
    public function __invoke(array $content): void
    {
        $this->assertPristineBaseline();
        $this->assertDatasetShape($content);
        $sources = $this->assertAssetSources($content['assets']);

        $preexistingPrivateFiles = Storage::disk('local')->allFiles();
        $createdPrivatePaths = [];

        try {
            DB::transaction(function () use ($content, $sources, $preexistingPrivateFiles, &$createdPrivatePaths): void {
                $this->context->run(function () use ($content, $sources, $preexistingPrivateFiles, &$createdPrivatePaths): void {
                    $this->fillSingletons($content);
                    $this->createCollections($content);

                    $profile = Profile::query()->where('singleton_key', 'default')->firstOrFail();
                    $profile = $this->assets->replace($profile, $this->upload($sources['photo']));
                    $this->trackCreatedPath($profile->photo_private_path, $preexistingPrivateFiles, $createdPrivatePaths);
                    // Alt text is written directly here (not via
                    // AssetLifecycleService::updateAltText) so it stays inside this
                    // import's single transaction + EditorialMutationContext and
                    // avoids a redundant cache invalidation.
                    $profile->forceFill([
                        'photo_alt_es' => $content['assets']['photo_alt_es'],
                        'photo_alt_en' => $content['assets']['photo_alt_en'],
                    ])->save();

                    $cvPlan = [
                        ['locale' => SupportedLocale::Spanish, 'label' => (string) $content['assets']['cv_es_label'], 'source' => $sources['cv_es']],
                        ['locale' => SupportedLocale::English, 'label' => (string) $content['assets']['cv_en_label'], 'source' => $sources['cv_en']],
                    ];

                    foreach ($cvPlan as $plan) {
                        $document = CvDocument::query()->create([
                            'locale' => $plan['locale'],
                            'label' => $plan['label'],
                            'status' => PublicationStatus::Draft,
                            'is_visible' => false,
                            'published_at' => null,
                        ]);
                        $document = $this->assets->replace($document, $this->upload($plan['source']));
                        $this->trackCreatedPath($document->private_path, $preexistingPrivateFiles, $createdPrivatePaths);
                    }
                });
            });
        } catch (\Throwable $exception) {
            $this->compensate($createdPrivatePaths, $preexistingPrivateFiles);

            throw new InitialImportException('The initial content import failed; the transaction was rolled back and any file this attempt created was removed.', 0, $exception);
        }
    }

    /**
     * Spec §29.2: an explicit check against the real Profile / SiteConfiguration
     * schema, not a generic status/visibility check.
     */
    private function assertPristineBaseline(): void
    {
        $this->assertPristineSingleton('profiles', self::PROFILE_EDITORIAL_COLUMNS);
        $this->assertPristineSingleton('site_configurations', self::SITE_EDITORIAL_COLUMNS);

        foreach (self::EMPTY_TABLES as $table) {
            $count = DB::table($table)->count();
            if ($count !== 0) {
                throw new InitialImportPreflightException("The initial import requires an empty [{$table}] table, but it already holds {$count} row(s).");
            }
        }
    }

    /**
     * @param  list<string>  $editorialColumns
     */
    private function assertPristineSingleton(string $table, array $editorialColumns): void
    {
        // Fail loudly if a future migration renamed or dropped an editorial
        // column: a stale constant entry would otherwise silently pass the
        // per-column null check below and weaken the pristine guarantee.
        $unknownColumns = array_values(array_diff($editorialColumns, Schema::getColumnListing($table)));
        if ($unknownColumns !== []) {
            throw new \LogicException(sprintf(
                'The initial-import editorial-column list for [%s] is out of sync with the schema; unknown column(s): %s.',
                $table,
                implode(', ', $unknownColumns),
            ));
        }

        $rows = DB::table($table)->get();
        if ($rows->count() !== 1) {
            throw new InitialImportPreflightException("The [{$table}] table must contain exactly the one Phase 4 structural singleton row, but it contains {$rows->count()}.");
        }

        /** @var array<string, mixed> $row */
        $row = (array) $rows->first();

        if (($row['singleton_key'] ?? null) !== 'default') {
            throw new InitialImportPreflightException("The [{$table}] structural singleton must use singleton_key 'default'.");
        }

        foreach ($editorialColumns as $column) {
            if (($row[$column] ?? null) !== null) {
                throw new InitialImportPreflightException("The [{$table}] singleton is not editorially pristine: [{$column}] already holds a value.");
            }
        }

        if (($row['status'] ?? null) !== PublicationStatus::Draft->value) {
            throw new InitialImportPreflightException("The [{$table}] singleton must be in draft status.");
        }
        if ((int) ($row['is_visible'] ?? 0) !== 0) {
            throw new InitialImportPreflightException("The [{$table}] singleton must not be visible.");
        }
        if (($row['published_at'] ?? null) !== null) {
            throw new InitialImportPreflightException("The [{$table}] singleton must not be published.");
        }
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function assertDatasetShape(array $content): void
    {
        if (($content['experiences'] ?? null) !== []) {
            throw new InitialImportPreflightException('The initial dataset must carry an empty experiences collection (spec §29.5).');
        }
        if (($content['projects'] ?? null) !== []) {
            throw new InitialImportPreflightException('The initial dataset must carry an empty projects collection (spec §29.5).');
        }

        $this->assertUniqueStableKeys($content['technologies'], 'key', 'technologies');
        $this->assertUniqueStableKeys($content['expertise_areas'], 'key', 'expertise areas');
        $this->assertUniqueStableKeys($content['work_cases'], 'key', 'work cases');
        $this->assertUniqueStableKeys($content['work_principles'], 'key', 'work principles');

        $seenTypes = [];
        foreach ($content['professional_links'] as $link) {
            $type = $link['type'] ?? null;
            if (! $type instanceof ProfessionalLinkType) {
                throw new InitialImportPreflightException('Every professional link must carry a valid ProfessionalLinkType.');
            }
            if (isset($seenTypes[$type->value])) {
                throw new InitialImportPreflightException("Duplicate professional link type [{$type->value}] in the dataset.");
            }
            $seenTypes[$type->value] = true;
        }

        foreach ($content['technologies'] as $technology) {
            if (! ($technology['category'] ?? null) instanceof TechnologyCategory) {
                $key = is_string($technology['key'] ?? null) ? $technology['key'] : '?';
                throw new InitialImportPreflightException("Technology [{$key}] has an invalid category reference.");
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function assertUniqueStableKeys(array $rows, string $attribute, string $label): void
    {
        $seen = [];
        foreach ($rows as $row) {
            $key = $row[$attribute] ?? null;
            if (! is_string($key) || $key === '') {
                throw new InitialImportPreflightException("Every {$label} row requires a non-empty [{$attribute}].");
            }
            if (isset($seen[$key])) {
                throw new InitialImportPreflightException("Duplicate {$label} [{$attribute}] [{$key}] in the dataset.");
            }
            $seen[$key] = true;
        }
    }

    /**
     * Nonmutating asset preflight (spec §29.3): verify the three approved source
     * files against the real Phase 4 owned-asset policy. Does NOT write.
     *
     * @param  array<string, mixed>  $assets
     * @return array{photo: string, cv_es: string, cv_en: string}
     */
    private function assertAssetSources(array $assets): array
    {
        // Deterministic locale -> approved source mapping (spec §29.3).
        if (basename((string) $assets['cv_es']) !== 'cv-es.pdf' || basename((string) $assets['cv_en']) !== 'cv-en.pdf') {
            throw new InitialImportPreflightException("The dataset CV sources do not match the approved deterministic mapping 'es -> cv-es.pdf', 'en -> cv-en.pdf'.");
        }

        $photo = $this->resolveSource((string) $assets['photo']);
        $cvEs = $this->resolveSource((string) $assets['cv_es']);
        $cvEn = $this->resolveSource((string) $assets['cv_en']);

        $this->assertPhotoSource($photo);
        $this->assertCvSource($cvEs);
        $this->assertCvSource($cvEn);

        return ['photo' => $photo, 'cv_es' => $cvEs, 'cv_en' => $cvEn];
    }

    private function assertPhotoSource(string $path): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InitialImportPreflightException("The approved profile photo is missing or unreadable at [{$path}].");
        }

        $dimensions = @getimagesize($path);
        if ($dimensions === false || (int) ($dimensions[0] ?? 0) < 1 || (int) ($dimensions[1] ?? 0) < 1) {
            throw new InitialImportPreflightException('The approved profile photo is not a readable image with valid dimensions.');
        }

        $mime = (string) ($dimensions['mime'] ?? (mime_content_type($path) ?: ''));
        if (! in_array($mime, self::PHOTO_MIMES, true)) {
            throw new InitialImportPreflightException("The approved profile photo MIME [{$mime}] is not an allowed image type.");
        }

        $size = (int) filesize($path);
        if ($size < 1 || $size > self::MAX_ASSET_BYTES) {
            throw new InitialImportPreflightException('The approved profile photo violates the owned-asset size policy (1 byte to 5 MiB).');
        }
    }

    private function assertCvSource(string $path): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InitialImportPreflightException("An approved CV PDF is missing or unreadable at [{$path}].");
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new InitialImportPreflightException("The approved CV source [{$path}] does not carry a .pdf extension.");
        }

        $mime = mime_content_type($path) ?: '';
        if ($mime !== 'application/pdf') {
            throw new InitialImportPreflightException("The approved CV source [{$path}] is not detected as application/pdf (got [{$mime}]).");
        }

        $size = (int) filesize($path);
        if ($size < 1 || $size > self::MAX_ASSET_BYTES) {
            throw new InitialImportPreflightException('An approved CV PDF violates the owned-asset size policy (1 byte to 5 MiB).');
        }
    }

    /**
     * Dataset asset paths are repository-root-relative (for example
     * "docs/content/approved-assets/cv-es.pdf"). In the container `base_path()`
     * is /var/www/html and the sibling repository docs/ directory is mounted
     * read-only at /var/www/docs, so "<repo root>/<path>" resolves as
     * base_path('../'.<path>). An already-absolute path is used verbatim (test
     * fixtures / operator override).
     */
    private function resolveSource(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path('../'.ltrim($path, '/'));
    }

    /**
     * The two structural singletons are filled in place; the importer — not the
     * dataset — owns their publication state (spec §29.3).
     *
     * @param  array<string, mixed>  $content
     */
    private function fillSingletons(array $content): void
    {
        Profile::query()->where('singleton_key', 'default')->firstOrFail()
            ->forceFill($this->draftAttributes(Arr::except($content['profile'], ['singleton_key'])))
            ->save();

        SiteConfiguration::query()->where('singleton_key', 'default')->firstOrFail()
            ->forceFill($this->draftAttributes(Arr::except($content['site'], ['singleton_key'])))
            ->save();
    }

    /**
     * Every created publishable entity is draft / hidden / unpublished, without
     * exception (spec §29.3). The importer re-stamps that state on every row
     * rather than trusting the dataset's markers.
     *
     * @param  array<string, mixed>  $content
     */
    private function createCollections(array $content): void
    {
        foreach ($content['professional_links'] as $row) {
            ProfessionalLink::query()->create($this->draftAttributes($row));
        }
        foreach ($content['technologies'] as $row) {
            Technology::query()->create($this->draftAttributes($row));
        }
        foreach ($content['expertise_areas'] as $row) {
            ExpertiseArea::query()->create($this->draftAttributes($row));
        }
        foreach ($content['work_cases'] as $row) {
            WorkCase::query()->create($this->draftAttributes($row));
        }
        foreach ($content['work_principles'] as $row) {
            WorkPrinciple::query()->create($this->draftAttributes($row));
        }
    }

    /**
     * Strip whatever publication state the dataset carried and re-stamp the
     * mandatory draft-gap markers (spec §29.3).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function draftAttributes(array $row): array
    {
        return Arr::except($row, self::PUBLICATION_STATE_KEYS) + [
            'status' => PublicationStatus::Draft,
            'is_visible' => false,
            'published_at' => null,
        ];
    }

    private function upload(string $absolutePath): UploadedFile
    {
        return new UploadedFile(
            $absolutePath,
            basename($absolutePath),
            mime_content_type($absolutePath) ?: null,
            null,
            true,
        );
    }

    /**
     * @param  list<string>  $preexisting
     * @param  list<string>  $created
     */
    private function trackCreatedPath(?string $path, array $preexisting, array &$created): void
    {
        if ($path === null || $path === '' || in_array($path, $preexisting, true) || in_array($path, $created, true)) {
            return;
        }

        $created[] = $path;
    }

    /**
     * Spec §29.4: delete only the private originals this attempt created; never
     * a preexisting file, never the public disk.
     *
     * @param  list<string>  $createdPrivatePaths
     * @param  list<string>  $preexistingPrivateFiles
     */
    private function compensate(array $createdPrivatePaths, array $preexistingPrivateFiles): void
    {
        foreach ($createdPrivatePaths as $path) {
            if (in_array($path, $preexistingPrivateFiles, true)) {
                continue;
            }

            Storage::disk('local')->delete($path);
        }
    }
}

/**
 * Raised when the guarded initial-content import cannot complete. A
 * {@see InitialImportPreflightException} means a preflight rejected the run
 * before any mutation; the base class also wraps a failure that occurred during
 * the transactional write phase (after which the transaction is rolled back and
 * this attempt's new files are removed).
 */
class InitialImportException extends \RuntimeException {}

/**
 * Raised by a preflight check. Nothing has been written to the database or the
 * filesystem when this is thrown.
 */
class InitialImportPreflightException extends InitialImportException {}
