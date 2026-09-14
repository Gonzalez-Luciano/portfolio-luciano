<?php

namespace Tests\Feature\Console;

use App\Domain\Content\InitialImportException;
use App\Domain\Content\InitialPortfolioContent;
use App\Domain\Content\InitialPortfolioImporter;
use App\Enums\PublicationStatus;
use App\Enums\SupportedLocale;
use App\Models\CvDocument;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

/**
 * Safety contract for `portfolio:import-initial-content` (spec §29). The
 * command must fill the two existing pristine Phase 4 structural singletons and
 * create the approved collections as draft/hidden/unpublished rows, and it must
 * reject — before any database or filesystem mutation — a database that is not
 * an exact empty editorial baseline.
 */
final class ImportInitialPortfolioContentTest extends TestCase
{
    use DatabaseMigrations;

    /** The full managed content graph snapshotted around every invocation. */
    private const CONTENT_TABLES = [
        'profiles', 'site_configurations', 'experiences', 'experience_highlights',
        'work_cases', 'projects', 'technologies', 'expertise_areas', 'work_principles',
        'professional_links', 'cv_documents', 'experience_technology',
        'technology_work_case', 'project_technology',
    ];

    /** The twelve tables that must be literally empty for the import to run. */
    private const EMPTY_TABLES = [
        'experiences', 'experience_highlights', 'work_cases', 'projects',
        'technologies', 'expertise_areas', 'work_principles',
        'professional_links', 'cv_documents', 'experience_technology',
        'technology_work_case', 'project_technology',
    ];

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->tempFiles = [];

        parent::tearDown();
    }

    public function test_a_freshly_migrated_database_is_the_exact_empty_editorial_baseline(): void
    {
        $this->assertSame(1, DB::table('profiles')->count());
        $this->assertSame(1, DB::table('site_configurations')->count());
        $this->assertSame('default', DB::table('profiles')->value('singleton_key'));
        $this->assertSame('default', DB::table('site_configurations')->value('singleton_key'));

        foreach (self::EMPTY_TABLES as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} must start empty.");
        }
    }

    public function test_it_fills_the_existing_singletons_in_place_and_creates_draft_collections(): void
    {
        $profileId = DB::table('profiles')->where('singleton_key', 'default')->value('id');
        $siteId = DB::table('site_configurations')->where('singleton_key', 'default')->value('id');

        $this->artisan('portfolio:import-initial-content')->assertSuccessful();

        // The two structural singletons are filled by id; no replacement row.
        $this->assertSame(1, DB::table('profiles')->count());
        $this->assertSame(1, DB::table('site_configurations')->count());
        $this->assertSame($profileId, DB::table('profiles')->where('singleton_key', 'default')->value('id'));
        $this->assertSame($siteId, DB::table('site_configurations')->where('singleton_key', 'default')->value('id'));

        $profile = Profile::query()->sole();
        $this->assertSame('Luciano González', $profile->name);
        $this->assertNotNull($profile->headline_es);
        $this->assertNotNull($profile->introduction_en);
        $this->assertSame(PublicationStatus::Draft, $profile->status);
        $this->assertFalse($profile->is_visible);
        $this->assertNull($profile->published_at);

        // Photo is attached to the existing profile, private-only.
        $this->assertMatchesRegularExpression('#^profiles/[0-9a-f-]+\.png$#', $profile->photo_private_path);
        $this->assertNull($profile->photo_public_path);
        $this->assertSame('image/png', $profile->photo_mime);
        $this->assertSame(1268838, $profile->photo_size);
        $this->assertSame('Retrato profesional de Luciano González con camisa blanca frente a una pared de tono cálido', $profile->photo_alt_es);
        $this->assertSame('Professional portrait of Luciano González in a white shirt against a warm-toned wall', $profile->photo_alt_en);
        Storage::disk('local')->assertExists($profile->photo_private_path);

        $site = SiteConfiguration::query()->sole();
        $this->assertNotNull($site->projects_empty_message_es);
        $this->assertNotNull($site->contact_intro_en);
        $this->assertSame('Backend', $site->technology_backend_label_es);
        $this->assertSame('Frontend & collaboration', $site->technology_collaboration_label_en);
        $this->assertSame(PublicationStatus::Draft, $site->status);
        $this->assertFalse($site->is_visible);
        $this->assertNull($site->published_at);

        $this->assertSame(3, ProfessionalLink::query()->count());
        $this->assertSame(9, Technology::query()->count());
        $this->assertSame(6, ExpertiseArea::query()->count());
        $this->assertSame(4, WorkCase::query()->count());
        $this->assertSame(1, WorkPrinciple::query()->count());

        foreach ([ProfessionalLink::class, Technology::class, ExpertiseArea::class, WorkCase::class, WorkPrinciple::class] as $model) {
            foreach ($model::query()->get() as $row) {
                $this->assertSame(PublicationStatus::Draft, $row->status, $model.' rows must be draft.');
                $this->assertFalse((bool) $row->is_visible, $model.' rows must be hidden.');
                $this->assertNull($row->published_at, $model.' rows must be unpublished.');
            }
        }

        // Two private CV documents, correct locale + accessibility label.
        $cvs = CvDocument::query()->get();
        $this->assertCount(2, $cvs);
        foreach ($cvs as $cv) {
            $this->assertSame(PublicationStatus::Draft, $cv->status);
            $this->assertFalse((bool) $cv->is_visible);
            $this->assertNull($cv->published_at);
            $this->assertSame('application/pdf', $cv->mime);
            $this->assertMatchesRegularExpression('#^cv/[0-9a-f-]+\.pdf$#', $cv->private_path);
            Storage::disk('local')->assertExists($cv->private_path);
        }
        $spanish = $cvs->firstWhere('locale', SupportedLocale::Spanish);
        $english = $cvs->firstWhere('locale', SupportedLocale::English);
        $this->assertSame('Descargar CV', $spanish->label);
        $this->assertSame(49861, $spanish->size);
        $this->assertSame('Download CV', $english->label);
        $this->assertSame(47061, $english->size);

        // Nothing invented for Experience / Projects / pivots; no users; no public copies.
        foreach (['experiences', 'experience_highlights', 'projects', 'experience_technology', 'technology_work_case', 'project_technology'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} must stay empty.");
        }
        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertCount(3, Storage::disk('local')->allFiles());
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function pristineViolationProvider(): array
    {
        $published = ['status' => 'published', 'is_visible' => 0, 'published_at' => '2026-01-01 00:00:00'];
        $publishedVisible = ['status' => 'published', 'is_visible' => 1, 'published_at' => '2026-01-01 00:00:00'];

        return [
            'profile name value present' => ['profiles', ['name' => 'Someone Else']],
            'profile localized copy present' => ['profiles', ['headline_es' => 'Encabezado', 'headline_en' => 'Headline']],
            'profile cta copy present' => ['profiles', ['cta_es' => 'Ir', 'cta_en' => 'Go']],
            'profile private photo metadata present' => ['profiles', ['photo_private_path' => 'profiles/manual.jpg', 'photo_mime' => 'image/jpeg', 'photo_size' => 2048]],
            'profile published but hidden' => ['profiles', $published],
            'profile published and visible' => ['profiles', $publishedVisible],
            'site projects-empty message present' => ['site_configurations', ['projects_empty_message_es' => 'x', 'projects_empty_message_en' => 'x']],
            'site contact intro present' => ['site_configurations', ['contact_intro_es' => 'x', 'contact_intro_en' => 'x']],
            'site technology group label present' => ['site_configurations', ['technology_backend_label_es' => 'Backend', 'technology_backend_label_en' => 'Backend']],
            'site published but hidden' => ['site_configurations', $published],
            'site published and visible' => ['site_configurations', $publishedVisible],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    #[DataProvider('pristineViolationProvider')]
    public function test_it_rejects_a_non_pristine_singleton_before_any_mutation(string $table, array $attributes): void
    {
        DB::table($table)->where('singleton_key', 'default')->update($attributes);
        $before = $this->snapshot();

        $this->artisan('portfolio:import-initial-content')->assertFailed();

        $this->assertUnchanged($before);
    }

    public function test_it_rejects_an_unexpected_extra_profile_singleton_row(): void
    {
        $this->assertRejectsExtraSingleton('profiles');
    }

    public function test_it_rejects_an_unexpected_extra_site_configuration_singleton_row(): void
    {
        $this->assertRejectsExtraSingleton('site_configurations');
    }

    /** @return array<string, array{0: string}> */
    public static function nonEmptyContentTableProvider(): array
    {
        return [
            'experiences' => ['experiences'],
            'experience_highlights' => ['experience_highlights'],
            'work_cases' => ['work_cases'],
            'projects' => ['projects'],
            'technologies' => ['technologies'],
            'expertise_areas' => ['expertise_areas'],
            'work_principles' => ['work_principles'],
            'professional_links' => ['professional_links'],
            'cv_documents' => ['cv_documents'],
            'experience_technology' => ['experience_technology'],
            'technology_work_case' => ['technology_work_case'],
            'project_technology' => ['project_technology'],
        ];
    }

    #[DataProvider('nonEmptyContentTableProvider')]
    public function test_it_rejects_any_preexisting_row_in_the_content_graph(string $table): void
    {
        $this->seedOneRow($table);
        $before = $this->snapshot();

        $this->artisan('portfolio:import-initial-content')->assertFailed();

        $this->assertUnchanged($before);
        // No dataset row and no asset file was written before the rejection.
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_second_execution_is_rejected_with_no_further_mutation(): void
    {
        $this->artisan('portfolio:import-initial-content')->assertSuccessful();
        $before = $this->snapshot();

        $this->artisan('portfolio:import-initial-content')->assertFailed();

        $this->assertUnchanged($before);
    }

    public function test_a_second_execution_is_rejected_even_after_an_admin_publishes_content(): void
    {
        $this->artisan('portfolio:import-initial-content')->assertSuccessful();

        DB::table('technologies')->where('key', 'php')->update([
            'status' => 'published',
            'is_visible' => 1,
            'key_locked' => 1,
            'published_at' => '2026-02-02 00:00:00',
        ]);
        $before = $this->snapshot();

        $this->artisan('portfolio:import-initial-content')->assertFailed();

        $this->assertUnchanged($before);
    }

    public function test_it_rejects_a_missing_profile_photo_source(): void
    {
        $this->assertImportRejected($this->contentWith(['assets.photo' => 'docs/content/approved-assets/missing-photo.jpg']));
    }

    public function test_it_rejects_a_profile_photo_source_that_is_not_an_image(): void
    {
        $this->assertImportRejected($this->contentWith(['assets.photo' => 'docs/content/approved-assets/cv-es.pdf']));
    }

    public function test_it_rejects_an_oversize_profile_photo_source(): void
    {
        $real = base_path('../docs/content/approved-assets/professional-photo.png');
        $fixture = $this->tempFixture('photo.png', (string) file_get_contents($real).str_repeat('0', 5 * 1024 * 1024));

        $this->assertImportRejected($this->contentWith(['assets.photo' => $fixture]));
    }

    public function test_it_rejects_a_missing_cv_source(): void
    {
        $this->assertImportRejected($this->contentWith(['assets.cv_es' => 'docs/content/approved-assets/nonexistent/cv-es.pdf']));
    }

    public function test_it_rejects_a_cv_source_that_is_not_detected_as_pdf(): void
    {
        $fixture = $this->tempFixture('cv-es.pdf', 'this content is plainly not a pdf document');

        $this->assertImportRejected($this->contentWith(['assets.cv_es' => $fixture]));
    }

    public function test_it_rejects_an_oversize_cv_source(): void
    {
        $fixture = $this->tempFixture('cv-en.pdf', "%PDF-1.4\n".str_repeat('0', 5 * 1024 * 1024 + 32));

        $this->assertImportRejected($this->contentWith(['assets.cv_en' => $fixture]));
    }

    public function test_it_rejects_a_swapped_locale_to_source_mapping(): void
    {
        $this->assertImportRejected($this->contentWith([
            'assets.cv_es' => 'docs/content/approved-assets/cv-en.pdf',
            'assets.cv_en' => 'docs/content/approved-assets/cv-es.pdf',
        ]));
    }

    public function test_it_rejects_a_dataset_with_duplicate_stable_keys(): void
    {
        $content = InitialPortfolioContent::data();
        $content['technologies'][] = $content['technologies'][0];

        $this->assertImportRejected($content);
    }

    public function test_it_rejects_a_dataset_whose_experiences_slice_is_not_empty(): void
    {
        $content = InitialPortfolioContent::data();
        $content['experiences'] = [['key' => 'invented-experience']];

        $this->assertImportRejected($content);
    }

    public function test_the_importer_forces_draft_state_even_when_the_dataset_carries_published_markers(): void
    {
        $content = InitialPortfolioContent::data();
        $publishedMarkers = ['status' => 'published', 'is_visible' => true, 'published_at' => '2026-01-01 00:00:00'];
        $content['profile'] = array_merge($content['profile'], $publishedMarkers);
        $content['site'] = array_merge($content['site'], $publishedMarkers);
        $content['technologies'][0] = array_merge($content['technologies'][0], $publishedMarkers);
        $content['work_principles'][0] = array_merge($content['work_principles'][0], $publishedMarkers);

        app(InitialPortfolioImporter::class)($content);

        $rows = [
            Profile::query()->sole(),
            SiteConfiguration::query()->sole(),
            Technology::query()->where('key', $content['technologies'][0]['key'])->sole(),
            WorkPrinciple::query()->where('key', $content['work_principles'][0]['key'])->sole(),
        ];
        foreach ($rows as $row) {
            $this->assertSame(PublicationStatus::Draft, $row->status, $row::class.' must be imported as draft.');
            $this->assertFalse((bool) $row->is_visible, $row::class.' must be imported hidden.');
            $this->assertNull($row->published_at, $row::class.' must be imported unpublished.');
        }

        // Every collection row, not only the tampered ones.
        foreach ([ProfessionalLink::class, Technology::class, ExpertiseArea::class, WorkCase::class, WorkPrinciple::class] as $model) {
            foreach ($model::query()->get() as $row) {
                $this->assertSame(PublicationStatus::Draft, $row->status);
                $this->assertFalse((bool) $row->is_visible);
                $this->assertNull($row->published_at);
            }
        }
    }

    public function test_the_editorial_column_constants_stay_in_sync_with_the_live_schema(): void
    {
        $reflection = new ReflectionClass(InitialPortfolioImporter::class);
        /** @var list<string> $profileColumns */
        $profileColumns = $reflection->getReflectionConstant('PROFILE_EDITORIAL_COLUMNS')->getValue();
        /** @var list<string> $siteColumns */
        $siteColumns = $reflection->getReflectionConstant('SITE_EDITORIAL_COLUMNS')->getValue();

        $profileSchema = Schema::getColumnListing('profiles');
        $siteSchema = Schema::getColumnListing('site_configurations');

        $this->assertNotEmpty($profileColumns);
        $this->assertNotEmpty($siteColumns);
        foreach ($profileColumns as $column) {
            $this->assertContains($column, $profileSchema, "profiles.{$column} must be a real column.");
        }
        foreach ($siteColumns as $column) {
            $this->assertContains($column, $siteSchema, "site_configurations.{$column} must be a real column.");
        }
    }

    public function test_a_failure_after_an_asset_write_rolls_back_and_deletes_only_this_attempts_files(): void
    {
        $before = $this->snapshot();
        CvDocument::creating(static function (): void {
            throw new \RuntimeException('Forced failure after the profile photo has been written.');
        });

        try {
            app(InitialPortfolioImporter::class)(InitialPortfolioContent::data());
            $this->fail('The forced failure must abort the import.');
        } catch (InitialImportException $exception) {
            $this->assertInstanceOf(\RuntimeException::class, $exception->getPrevious());
        }

        $this->assertUnchanged($before);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_compensation_never_deletes_a_preexisting_private_file(): void
    {
        Storage::disk('local')->put('profiles/preexisting-sentinel.jpg', 'sentinel');
        Storage::disk('local')->put('cv/preexisting-sentinel.pdf', 'sentinel');
        $before = $this->snapshot();

        CvDocument::creating(static function (): void {
            throw new \RuntimeException('Forced failure after the profile photo has been written.');
        });

        try {
            app(InitialPortfolioImporter::class)(InitialPortfolioContent::data());
            $this->fail('The forced failure must abort the import.');
        } catch (InitialImportException) {
            // expected
        }

        Storage::disk('local')->assertExists('profiles/preexisting-sentinel.jpg');
        Storage::disk('local')->assertExists('cv/preexisting-sentinel.pdf');
        $this->assertUnchanged($before);
    }

    public function test_a_storage_failure_during_a_later_asset_write_triggers_compensation(): void
    {
        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $calls = 0;
        $failingLocal = Mockery::mock();
        $failingLocal->shouldReceive('putFileAs')->andReturnUsing(function (...$args) use (&$calls, $local) {
            $calls++;

            return $calls === 1 ? $local->putFileAs(...$args) : false;
        });
        foreach (['get', 'exists', 'delete', 'allFiles', 'files', 'put', 'size', 'mimeType', 'readStream'] as $method) {
            $failingLocal->shouldReceive($method)->andReturnUsing(fn (...$args) => $local->{$method}(...$args));
        }
        Storage::shouldReceive('disk')->with('local')->andReturn($failingLocal);
        Storage::shouldReceive('disk')->with('public')->andReturn($public);

        try {
            app(InitialPortfolioImporter::class)(InitialPortfolioContent::data());
            $this->fail('A storage failure must abort the import.');
        } catch (InitialImportException) {
            // expected
        }

        $this->assertSame([], $local->allFiles());
        $this->assertSame([], $public->allFiles());
        $this->assertSame(1, Profile::query()->count());
        $this->assertNull(Profile::query()->sole()->photo_private_path);
        $this->assertSame(0, CvDocument::query()->count());
        $this->assertSame(0, Technology::query()->count());
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides  dot-path => value
     * @return array<string, mixed>
     */
    private function contentWith(array $overrides): array
    {
        $content = InitialPortfolioContent::data();
        foreach ($overrides as $path => $value) {
            data_set($content, $path, $value);
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function assertImportRejected(array $content): void
    {
        $before = $this->snapshot();

        try {
            app(InitialPortfolioImporter::class)($content);
            $this->fail('The importer must reject this content before mutating anything.');
        } catch (InitialImportException) {
            // expected
        }

        $this->assertUnchanged($before);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function assertRejectsExtraSingleton(string $table): void
    {
        $check = "{$table}_singleton_key_check";

        try {
            DB::statement("ALTER TABLE {$table} ALTER CHECK {$check} NOT ENFORCED");
            DB::table($table)->insert([
                'singleton_key' => 'unexpected',
                'status' => 'draft',
                'is_visible' => 0,
                'published_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $before = $this->snapshot();

            $this->artisan('portfolio:import-initial-content')->assertFailed();

            $this->assertUnchanged($before);
        } finally {
            DB::table($table)->where('singleton_key', 'unexpected')->delete();
            DB::statement("ALTER TABLE {$table} ALTER CHECK {$check} ENFORCED");
        }
    }

    private function seedOneRow(string $table): void
    {
        $now = now();

        match ($table) {
            'experiences' => DB::table('experiences')->insert(['key' => 'seed-exp', 'start_year' => 2020, 'start_month' => 1, 'created_at' => $now, 'updated_at' => $now]),
            'experience_highlights' => DB::table('experience_highlights')->insert([
                'experience_id' => DB::table('experiences')->insertGetId(['key' => 'seed-exp', 'start_year' => 2020, 'start_month' => 1, 'created_at' => $now, 'updated_at' => $now]),
                'position' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]),
            'work_cases' => DB::table('work_cases')->insert(['key' => 'seed-wc', 'created_at' => $now, 'updated_at' => $now]),
            'projects' => DB::table('projects')->insert(['key' => 'seed-proj', 'created_at' => $now, 'updated_at' => $now]),
            'technologies' => DB::table('technologies')->insert(['key' => 'seed-tech', 'category' => 'backend', 'created_at' => $now, 'updated_at' => $now]),
            'expertise_areas' => DB::table('expertise_areas')->insert(['key' => 'seed-ea', 'created_at' => $now, 'updated_at' => $now]),
            'work_principles' => DB::table('work_principles')->insert(['key' => 'seed-wp', 'created_at' => $now, 'updated_at' => $now]),
            'professional_links' => DB::table('professional_links')->insert(['type' => 'linkedin', 'destination' => 'https://example.com', 'created_at' => $now, 'updated_at' => $now]),
            'cv_documents' => DB::table('cv_documents')->insert(['locale' => 'es', 'created_at' => $now, 'updated_at' => $now]),
            'experience_technology' => DB::table('experience_technology')->insert([
                'experience_id' => DB::table('experiences')->insertGetId(['key' => 'seed-exp', 'start_year' => 2020, 'start_month' => 1, 'created_at' => $now, 'updated_at' => $now]),
                'technology_id' => DB::table('technologies')->insertGetId(['key' => 'seed-tech', 'category' => 'backend', 'created_at' => $now, 'updated_at' => $now]),
                'position' => 0,
            ]),
            'technology_work_case' => DB::table('technology_work_case')->insert([
                'work_case_id' => DB::table('work_cases')->insertGetId(['key' => 'seed-wc', 'created_at' => $now, 'updated_at' => $now]),
                'technology_id' => DB::table('technologies')->insertGetId(['key' => 'seed-tech', 'category' => 'backend', 'created_at' => $now, 'updated_at' => $now]),
                'position' => 0,
            ]),
            'project_technology' => DB::table('project_technology')->insert([
                'project_id' => DB::table('projects')->insertGetId(['key' => 'seed-proj', 'created_at' => $now, 'updated_at' => $now]),
                'technology_id' => DB::table('technologies')->insertGetId(['key' => 'seed-tech', 'category' => 'backend', 'created_at' => $now, 'updated_at' => $now]),
                'position' => 0,
            ]),
        };
    }

    private function tempFixture(string $name, string $contents): string
    {
        $path = sys_get_temp_dir().'/import-test-'.bin2hex(random_bytes(6)).'-'.$name;
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        $tables = [];
        foreach (self::CONTENT_TABLES as $table) {
            $tables[$table] = DB::table($table)->get()
                ->map(static fn ($row): array => (array) $row)
                ->sortBy(static fn (array $row): string => json_encode($row, JSON_THROW_ON_ERROR))
                ->values()
                ->all();
        }

        return [
            'tables' => $tables,
            'users' => DB::table('users')->count(),
            'local' => $this->diskState('local'),
            'public' => $this->diskState('public'),
        ];
    }

    /** @return array<string, string> */
    private function diskState(string $disk): array
    {
        $state = [];
        foreach (Storage::disk($disk)->allFiles() as $file) {
            $state[$file] = md5((string) Storage::disk($disk)->get($file));
        }
        ksort($state);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $before
     */
    private function assertUnchanged(array $before): void
    {
        $this->assertEquals($before, $this->snapshot(), 'The rejected import must perform zero database and zero filesystem mutation.');
    }
}
