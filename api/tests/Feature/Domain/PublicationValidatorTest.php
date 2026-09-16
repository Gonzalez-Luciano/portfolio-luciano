<?php

namespace Tests\Feature\Domain;

use App\Domain\Content\Actions\PublishContent;
use App\Domain\Publishing\PublicationValidationException;
use App\Domain\Publishing\PublicationValidator;
use App\Enums\ProjectKind;
use App\Enums\PublicationStatus;
use App\Models\CvDocument;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\ExpertiseArea;
use App\Models\Language;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PublicationValidatorTest extends TestCase
{
    use DatabaseMigrations;

    #[DataProvider('completePublishedModels')]
    public function test_it_accepts_complete_published_models(string $model): void
    {
        $content = $model::factory()->publishedHidden()->make();

        $this->assertSame([], app(PublicationValidator::class)->issues($content));
    }

    public static function completePublishedModels(): array
    {
        return [
            'profile' => [Profile::class],
            'site configuration' => [SiteConfiguration::class],
            'experience' => [Experience::class],
            'work case' => [WorkCase::class],
            'project' => [Project::class],
            'technology' => [Technology::class],
            'expertise area' => [ExpertiseArea::class],
            'work principle' => [WorkPrinciple::class],
            'professional link' => [ProfessionalLink::class],
            'cv document' => [CvDocument::class],
        ];
    }

    #[DataProvider('requiredTranslationCases')]
    public function test_every_bilingual_model_reports_the_missing_locale_with_a_stable_issue(string $model, string $field): void
    {
        $content = $model::factory()->publishedHidden()->make([$field => ' ']);

        $issue = app(PublicationValidator::class)->issues($content)[0];

        $this->assertSame('required_translation', $issue->code);
        $this->assertSame($field, $issue->path);
    }

    public static function requiredTranslationCases(): array
    {
        return [
            'profile' => [Profile::class, 'headline_es'],
            'site configuration' => [SiteConfiguration::class, 'projects_empty_message_en'],
            'experience' => [Experience::class, 'role_es'],
            'work case' => [WorkCase::class, 'title_en'],
            'project' => [Project::class, 'summary_es'],
            'expertise area' => [ExpertiseArea::class, 'title_en'],
            'work principle' => [WorkPrinciple::class, 'statement_es'],
            'professional link' => [ProfessionalLink::class, 'label_en'],
        ];
    }

    public function test_it_returns_stable_paths_and_codes_for_required_bilingual_profile_content(): void
    {
        $profile = Profile::factory()->publishedHidden()->make(['name' => '   ', 'headline_en' => '   ']);

        $issues = app(PublicationValidator::class)->issues($profile);

        $this->assertSame([
            ['code' => 'required', 'path' => 'name'],
            ['code' => 'required_translation', 'path' => 'headline_en'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], $issues));
    }

    public function test_it_requires_optional_bilingual_pairs_to_be_present_or_absent_together(): void
    {
        $experience = Experience::factory()->publishedHidden()->make([
            'organization_label_es' => 'Organización sintética',
            'organization_label_en' => null,
        ]);

        $issue = app(PublicationValidator::class)->issues($experience)[0];

        $this->assertSame('translation_pair', $issue->code);
        $this->assertSame('organization_label', $issue->path);
    }

    public function test_profile_scene_copy_is_optional_but_bilingual_when_present(): void
    {
        $complete = Profile::factory()->publishedHidden()->make([
            'statement_lead_es' => 'Inicio', 'statement_lead_en' => 'Lead',
            'closing_line_two_es' => null, 'closing_line_two_en' => null,
        ]);

        $this->assertSame([], app(PublicationValidator::class)->issues($complete));

        $partial = Profile::factory()->publishedHidden()->make([
            'statement_emphasis_es' => 'Énfasis', 'statement_emphasis_en' => '   ',
            'closing_line_one_es' => null, 'closing_line_one_en' => 'Line one',
        ]);

        $this->assertSame([
            ['code' => 'translation_pair', 'path' => 'statement_emphasis'],
            ['code' => 'translation_pair', 'path' => 'closing_line_one'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($partial)));
    }

    public function test_it_validates_project_destinations_and_every_gallery_image(): void
    {
        $project = Project::factory()->publishedHidden()->make(['demo_url' => 'http://example.test']);
        $project->setRelation('images', collect([
            ProjectImage::factory()->make(['alt_en' => null]),
            ProjectImage::factory()->make(['mime' => 'image/svg+xml', 'size' => 9 * 1024 * 1024]),
        ]));

        $this->assertSame([
            ['code' => 'invalid_https_url', 'path' => 'demo_url'],
            ['code' => 'required_translation', 'path' => 'images.0.alt_en'],
            ['code' => 'invalid_asset_mime', 'path' => 'images.1.mime'],
            ['code' => 'asset_size_exceeded', 'path' => 'images.1.size'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($project)));
    }

    public function test_it_rejects_more_than_twelve_project_images(): void
    {
        $project = Project::factory()->publishedHidden()->make();
        $project->setRelation('images', ProjectImage::factory()->count(13)->make());

        $issues = app(PublicationValidator::class)->issues($project);

        $this->assertSame('too_many_images', $issues[0]->code);
        $this->assertSame('images', $issues[0]->path);
    }

    public function test_it_validates_experience_dates_and_every_existing_highlight(): void
    {
        $experience = Experience::factory()->publishedHidden()->make();
        $experience->forceFill([
            'start_year' => 2025, 'start_month' => 8, 'end_year' => 2025, 'end_month' => 7,
        ]);
        $highlight = ExperienceHighlight::factory()->make([
            'content_es' => 'Hito sintético',
            'content_en' => ' ',
        ]);
        $experience->setRelation('highlights', collect([$highlight]));
        $experience->setRelation('technologies', collect());

        $issues = app(PublicationValidator::class)->issues($experience);

        $this->assertSame([
            ['code' => 'invalid_date_range', 'path' => 'end_year'],
            ['code' => 'required_translation', 'path' => 'highlights.0.content_en'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], $issues));
    }

    public function test_it_validates_education_entries(): void
    {
        $entry = EducationEntry::factory()->publishedHidden()->make([
            'institution' => ' ',
            'program_en' => null,
            'detail_es' => 'Detalle sintético',
            'detail_en' => null,
            'start_year' => 2023,
            'end_year' => 2022,
        ]);

        $this->assertSame([
            ['code' => 'required', 'path' => 'institution'],
            ['code' => 'required_translation', 'path' => 'program_en'],
            ['code' => 'translation_pair', 'path' => 'detail'],
            ['code' => 'invalid_date_range', 'path' => 'end_year'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($entry)));
        $this->assertSame([], app(PublicationValidator::class)->issues(EducationEntry::factory()->publishedHidden()->make()));
    }

    public function test_it_validates_languages(): void
    {
        $language = Language::factory()->publishedHidden()->make(['name_en' => ' ', 'level' => null]);

        $this->assertSame([
            ['code' => 'required_translation', 'path' => 'name_en'],
            ['code' => 'required', 'path' => 'level'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($language)));
        $this->assertSame([], app(PublicationValidator::class)->issues(Language::factory()->publishedHidden()->make()));
    }

    public function test_it_validates_professional_link_destinations_and_technology_canonical_content(): void
    {
        $link = ProfessionalLink::factory()->email()->publishedHidden()->make(['destination' => 'mailto:not-an-email']);
        $technology = Technology::factory()->publishedHidden()->make([
            'name' => ' ',
            'icon_private_path' => 'icons/synthetic.svg',
            'icon_mime' => 'image/svg+xml',
            'icon_size' => 5,
        ]);

        $issues = [...app(PublicationValidator::class)->issues($link), ...app(PublicationValidator::class)->issues($technology)];

        $this->assertSame([
            ['code' => 'invalid_email_destination', 'path' => 'destination'],
            ['code' => 'required', 'path' => 'name'],
            ['code' => 'invalid_asset_mime', 'path' => 'icon_mime'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], $issues));
    }

    public function test_it_validates_owned_asset_metadata_for_every_asset_owner(): void
    {
        $profile = Profile::factory()->publishedHidden()->make([
            'photo_private_path' => 'profiles/synthetic.webp',
            'photo_mime' => 'image/webp',
            'photo_size' => 0,
            'photo_alt_es' => 'Retrato sintético',
            'photo_alt_en' => 'Synthetic portrait',
        ]);
        $technology = Technology::factory()->publishedHidden()->make([
            'icon_private_path' => 'icons/synthetic.png',
            'icon_mime' => 'image/png',
            'icon_size' => 0,
        ]);
        $cv = CvDocument::factory()->publishedHidden()->make(['size' => (5 * 1024 * 1024) + 1]);

        $issues = [
            ...app(PublicationValidator::class)->issues($profile),
            ...app(PublicationValidator::class)->issues($technology),
            ...app(PublicationValidator::class)->issues($cv),
        ];

        $this->assertSame([
            ['code' => 'asset_size_exceeded', 'path' => 'photo_size'],
            ['code' => 'asset_size_exceeded', 'path' => 'icon_size'],
            ['code' => 'asset_size_exceeded', 'path' => 'size'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], $issues));
    }

    public function test_it_flags_a_bounded_varchar_field_that_exceeds_its_application_maximum_length(): void
    {
        $project = Project::factory()->publishedHidden()->make(['title_es' => str_repeat('a', 256)]);

        $issues = app(PublicationValidator::class)->issues($project);
        $issue = collect($issues)->firstWhere('path', 'title_es');

        $this->assertNotNull($issue, 'An overlong bounded field must be reported as a blocking publication issue.');
        $this->assertSame('max_length_exceeded', $issue->code);
    }

    public function test_it_flags_a_narrative_text_field_that_exceeds_its_application_maximum_length(): void
    {
        $workCase = WorkCase::factory()->publishedHidden()->make(['outcome_es' => str_repeat('a', 10001)]);

        $issues = app(PublicationValidator::class)->issues($workCase);
        $issue = collect($issues)->firstWhere('path', 'outcome_es');

        $this->assertNotNull($issue, 'An overlong narrative field must be reported as a blocking publication issue.');
        $this->assertSame('max_length_exceeded', $issue->code);
    }

    public function test_it_flags_alt_text_that_exceeds_its_five_hundred_character_application_maximum(): void
    {
        $profile = Profile::factory()->publishedHidden()->make([
            'photo_private_path' => 'profiles/synthetic.webp',
            'photo_mime' => 'image/webp',
            'photo_size' => 1,
            'photo_alt_es' => str_repeat('a', 501),
            'photo_alt_en' => 'Synthetic portrait',
        ]);

        $issues = app(PublicationValidator::class)->issues($profile);
        $issue = collect($issues)->firstWhere('path', 'photo_alt_es');

        $this->assertNotNull($issue, 'Alt text over 500 characters must be reported as a blocking publication issue.');
        $this->assertSame('max_length_exceeded', $issue->code);
    }

    public function test_publish_transitions_a_valid_draft_to_the_hidden_published_state_and_locks_its_key(): void
    {
        $project = Project::factory()->draft()->create([
            'title_es' => 'Proyecto sintético', 'title_en' => 'Synthetic project',
            'summary_es' => 'Resumen sintético', 'summary_en' => 'Synthetic summary',
            'problem_es' => 'Problema sintético', 'problem_en' => 'Synthetic problem',
            'solution_es' => 'Solución sintética', 'solution_en' => 'Synthetic solution',
            'role_es' => 'Rol técnico sintético', 'role_en' => 'Synthetic technical role',
            'result_es' => 'Resultado técnico sintético.', 'result_en' => 'Synthetic technical result.',
            'delivery_status' => 'in_development',
        ]);

        $published = app(PublishContent::class)($project);

        $this->assertSame(PublicationStatus::Published, $published->status);
        $this->assertFalse($published->is_visible);
        $this->assertTrue($published->key_locked);
        $this->assertNotNull($published->published_at);
        $this->assertSame($project->key, $published->key);
    }

    public function test_publish_uses_the_same_structured_issues_as_review_validation(): void
    {
        $project = Project::factory()->draft()->create();

        try {
            app(PublishContent::class)($project);
            $this->fail('Publishing incomplete content must be blocked.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('required_translation', $exception->issues()[0]->code);
            $this->assertSame('title_es', $exception->issues()[0]->path);
        }
    }

    public function test_it_requires_project_role_result_delivery_status_and_a_coherent_client(): void
    {
        $incomplete = Project::factory()->publishedHidden()->make([
            'role_en' => null,
            'result_es' => ' ',
            'delivery_status' => null,
            'kind' => ProjectKind::Client,
            'client_name' => null,
        ]);
        $personalWithClient = Project::factory()->publishedHidden()->make([
            'kind' => ProjectKind::Personal,
            'client_name' => 'Synthetic Client',
        ]);
        $validClient = Project::factory()->publishedHidden()->client()->make();

        $this->assertSame([
            ['code' => 'required_translation', 'path' => 'role_en'],
            ['code' => 'required_translation', 'path' => 'result_es'],
            ['code' => 'required', 'path' => 'delivery_status'],
            ['code' => 'required', 'path' => 'client_name'],
        ], array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($incomplete)));
        $this->assertSame(
            [['code' => 'client_name_not_allowed', 'path' => 'client_name']],
            array_map(static fn ($issue): array => ['code' => $issue->code, 'path' => $issue->path], app(PublicationValidator::class)->issues($personalWithClient)),
        );
        $this->assertSame([], app(PublicationValidator::class)->issues($validClient));
    }
}
