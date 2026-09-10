<?php

namespace Tests\Unit\Domain\Content;

use App\Domain\Content\InitialPortfolioContent;
use App\Enums\ProfessionalLinkType;
use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use PHPUnit\Framework\TestCase;

final class InitialPortfolioContentTest extends TestCase
{
    /** @return array<string, mixed> */
    private function data(): array
    {
        return InitialPortfolioContent::data();
    }

    public function test_it_exposes_every_top_level_key(): void
    {
        $data = $this->data();

        foreach ([
            'profile',
            'site',
            'professional_links',
            'technologies',
            'expertise_areas',
            'work_cases',
            'work_principles',
            'experiences',
            'projects',
            'assets',
        ] as $key) {
            $this->assertArrayHasKey($key, $data, "Missing top-level key: {$key}.");
        }
    }

    public function test_collection_sizes_match_the_approved_content(): void
    {
        $data = $this->data();

        $this->assertCount(3, $data['professional_links']);
        $this->assertCount(5, $data['technologies']);
        $this->assertCount(6, $data['expertise_areas']);
        $this->assertCount(4, $data['work_cases']);
        $this->assertCount(1, $data['work_principles']);
    }

    public function test_experiences_and_projects_are_explicitly_empty(): void
    {
        $data = $this->data();

        $this->assertSame([], $data['experiences']);
        $this->assertSame([], $data['projects']);
    }

    public function test_stable_keys_and_types_are_unique(): void
    {
        $data = $this->data();

        $technologyKeys = array_column($data['technologies'], 'key');
        $this->assertSame($technologyKeys, array_values(array_unique($technologyKeys)));

        $linkTypes = array_map(
            static fn (array $link): string => $link['type']->value,
            $data['professional_links'],
        );
        $this->assertSame($linkTypes, array_values(array_unique($linkTypes)));

        $expertiseKeys = array_column($data['expertise_areas'], 'key');
        $this->assertSame($expertiseKeys, array_values(array_unique($expertiseKeys)));

        $workCaseKeys = array_column($data['work_cases'], 'key');
        $this->assertSame($workCaseKeys, array_values(array_unique($workCaseKeys)));
    }

    public function test_every_technology_category_is_a_valid_enum_case(): void
    {
        foreach ($this->data()['technologies'] as $technology) {
            $this->assertInstanceOf(TechnologyCategory::class, $technology['category']);
        }
    }

    public function test_positions_are_zero_indexed_and_sequential(): void
    {
        $data = $this->data();

        foreach (['technologies', 'expertise_areas', 'work_cases', 'work_principles', 'professional_links'] as $collection) {
            foreach (array_values($data[$collection]) as $index => $row) {
                $this->assertSame($index, $row['position'], "{$collection}[{$index}] position mismatch.");
            }
        }
    }

    public function test_lists_are_zero_indexed(): void
    {
        $data = $this->data();

        $this->assertArrayHasKey(0, $data['work_cases']);
        $this->assertSame(array_keys($data['work_cases']), range(0, count($data['work_cases']) - 1));
        $this->assertSame(array_keys($data['technologies']), range(0, count($data['technologies']) - 1));
    }

    public function test_exact_approved_bilingual_profile_values(): void
    {
        $profile = $this->data()['profile'];

        $this->assertSame('Luciano González', $profile['name']);
        $this->assertSame('Backend Developer | PHP & Laravel', $profile['headline_es']);
        $this->assertSame('Backend Developer | PHP & Laravel', $profile['headline_en']);
        $this->assertSame('Ver experiencia', $profile['cta_es']);
        $this->assertSame('View experience', $profile['cta_en']);
        $this->assertSame(
            'Backend development focused on APIs, business logic, data, and application maintenance.',
            $profile['short_summary_en'],
        );
    }

    public function test_exact_approved_work_case_values(): void
    {
        $case = $this->data()['work_cases'][0];

        $this->assertSame('integrations-synchronization', $case['key']);
        $this->assertSame('Integraciones y sincronización', $case['title_es']);
        $this->assertSame('Integrations and synchronization', $case['title_en']);
        $this->assertSame(
            'Trabajo backend para integrar y mantener comunicación con APIs externas, con atención a la consistencia de los flujos y al mantenimiento responsable de la integración.',
            $case['context_es'],
        );
    }

    public function test_exact_approved_expertise_and_link_values(): void
    {
        $data = $this->data();

        $this->assertSame('php-laravel-development', $data['expertise_areas'][0]['key']);
        $this->assertSame('Desarrollo y mantenimiento con PHP y Laravel.', $data['expertise_areas'][0]['title_es']);
        $this->assertSame('Development and maintenance with PHP and Laravel.', $data['expertise_areas'][0]['title_en']);

        $linkedIn = $data['professional_links'][0];
        $this->assertSame(ProfessionalLinkType::LinkedIn, $linkedIn['type']);
        $this->assertSame('https://www.linkedin.com/in/luciano-gonzález-590350294', $linkedIn['destination']);
        $this->assertSame('lucianogonzalez12004@gmail.com', $data['professional_links'][2]['destination']);
    }

    public function test_exact_approved_technology_rows(): void
    {
        $data = $this->data();

        $this->assertSame(
            [
                ['php', 'PHP', TechnologyCategory::Backend],
                ['laravel', 'Laravel', TechnologyCategory::Backend],
                ['mysql', 'MySQL', TechnologyCategory::Data],
                ['rest-apis', 'REST APIs', TechnologyCategory::Integration],
                ['angular', 'Angular', TechnologyCategory::Collaboration],
            ],
            array_map(
                static fn (array $t): array => [$t['key'], $t['name'], $t['category']],
                $data['technologies'],
            ),
        );
    }

    public function test_site_technology_group_labels_are_all_null(): void
    {
        $site = $this->data()['site'];

        foreach ([
            'technology_backend_label_es',
            'technology_backend_label_en',
            'technology_data_label_es',
            'technology_data_label_en',
            'technology_integration_label_es',
            'technology_integration_label_en',
            'technology_collaboration_label_es',
            'technology_collaboration_label_en',
        ] as $column) {
            $this->assertArrayHasKey($column, $site, "Missing site column: {$column}.");
            $this->assertNull($site[$column], "Site column {$column} must be null.");
        }
    }

    public function test_work_case_detail_fields_are_all_null(): void
    {
        foreach ($this->data()['work_cases'] as $case) {
            foreach ([
                'problem_es', 'problem_en',
                'contribution_es', 'contribution_en',
                'technical_approach_es', 'technical_approach_en',
                'outcome_es', 'outcome_en',
            ] as $field) {
                $this->assertArrayHasKey($field, $case, "Missing work-case field: {$field}.");
                $this->assertNull($case[$field], "Work case {$case['key']} field {$field} must be null.");
            }
        }
    }

    public function test_expertise_area_descriptions_are_null(): void
    {
        foreach ($this->data()['expertise_areas'] as $area) {
            $this->assertNull($area['description_es']);
            $this->assertNull($area['description_en']);
        }
    }

    public function test_asset_source_paths_are_the_exact_approved_tracked_files(): void
    {
        $assets = $this->data()['assets'];

        $this->assertSame('docs/content/approved-assets/professional-photo.jpg', $assets['photo']);
        $this->assertSame('docs/content/approved-assets/cv-es.pdf', $assets['cv_es']);
        $this->assertSame('docs/content/approved-assets/cv-en.pdf', $assets['cv_en']);
    }

    public function test_asset_alt_and_cv_labels_carry_the_approved_inventory_text(): void
    {
        $assets = $this->data()['assets'];

        $this->assertSame('Retrato profesional de Luciano González sobre fondo naranja', $assets['photo_alt_es']);
        $this->assertSame('Professional portrait of Luciano González against an orange background', $assets['photo_alt_en']);
        $this->assertSame('Descargar CV de Luciano González en español (PDF)', $assets['cv_es_label']);
        $this->assertSame("Download Luciano González's CV in English (PDF)", $assets['cv_en_label']);
    }

    public function test_singletons_carry_the_visible_draft_gap_markers(): void
    {
        $data = $this->data();

        foreach (['profile', 'site'] as $singleton) {
            $this->assertSame('default', $data[$singleton]['singleton_key']);
            $this->assertSame(PublicationStatus::Draft, $data[$singleton]['status']);
            $this->assertFalse($data[$singleton]['is_visible']);
            $this->assertNull($data[$singleton]['published_at']);
        }

        foreach (['professional_links', 'technologies', 'expertise_areas', 'work_cases', 'work_principles'] as $collection) {
            foreach ($data[$collection] as $row) {
                $this->assertSame(PublicationStatus::Draft, $row['status']);
                $this->assertFalse($row['is_visible']);
                $this->assertNull($row['published_at']);
            }
        }
    }

    /**
     * Comment-free code (docblocks describe the ban and legitimately name the
     * forbidden calls, so they are stripped before scanning).
     */
    private function sourceCode(): string
    {
        return php_strip_whitespace(
            dirname(__DIR__, 4).'/app/Domain/Content/InitialPortfolioContent.php',
        );
    }

    public function test_source_never_reads_documents_or_assets_at_runtime(): void
    {
        $source = $this->sourceCode();

        $this->assertNotSame('', $source);

        foreach (['fopen', 'file_get_contents', 'fgets', 'fread', 'preg_match', 'preg_replace', 'str_getcsv', 'Storage::', 'Http::', '->get('] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source, "Dataset must not use {$forbidden} at runtime.");
        }
    }

    public function test_source_contains_no_employer_role_or_start_date_literals(): void
    {
        $source = $this->sourceCode();

        foreach (['start_year', 'start_month', "'organization'", "'role'", 'ExperienceHighlight', 'Experience::', 'Project::'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source, "Dataset must not carry {$forbidden}.");
        }
    }
}
