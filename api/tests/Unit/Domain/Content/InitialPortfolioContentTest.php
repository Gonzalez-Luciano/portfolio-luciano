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

    /**
     * Exhaustive verbatim pin of EVERY approved Profile string. Any future
     * drift in one field fails exactly this test and names the field.
     */
    public function test_profile_pins_every_approved_string(): void
    {
        $profile = $this->data()['profile'];

        $expected = [
            'name' => 'Luciano González',
            'headline_es' => 'Backend Developer | PHP & Laravel',
            'headline_en' => 'Backend Developer | PHP & Laravel',
            'short_summary_es' => 'Desarrollo backend orientado a APIs, lógica de negocio, datos y mantenimiento de aplicaciones.',
            'short_summary_en' => 'Backend development focused on APIs, business logic, data, and application maintenance.',
            'introduction_es' => 'Soy Luciano González, Backend Developer con foco en PHP, Laravel, MySQL y APIs REST. Me interesa construir y mantener soluciones claras, confiables y sostenibles, atendiendo tanto al desarrollo de funcionalidades como a la evolución de aplicaciones existentes.',
            'introduction_en' => 'I am Luciano González, a Backend Developer focused on PHP, Laravel, MySQL, and REST APIs. I am interested in building and maintaining clear, reliable, and sustainable solutions, addressing both feature development and the evolution of existing applications.',
            'availability_es' => 'Disponible para conversar sobre oportunidades backend con PHP y Laravel.',
            'availability_en' => 'Available to discuss backend opportunities with PHP and Laravel.',
            'cta_es' => 'Ver experiencia',
            'cta_en' => 'View experience',
        ];

        foreach ($expected as $field => $value) {
            $this->assertSame($value, $profile[$field], "Profile.{$field} drifted from approved copy.");
        }
    }

    /**
     * Exhaustive verbatim pin of EVERY approved SiteConfiguration copy string
     * (the eight Technology-group label columns are separately pinned null).
     */
    public function test_site_pins_every_approved_string(): void
    {
        $site = $this->data()['site'];

        $expected = [
            'projects_empty_message_es' => 'Actualmente no hay proyectos publicados en el portfolio. Los próximos proyectos se incorporarán cuando cuenten con una presentación técnica y pública adecuada.',
            'projects_empty_message_en' => 'There are currently no published projects in the portfolio. Future projects will be added when they have an appropriate technical and public presentation.',
            'contact_intro_es' => 'Podés encontrarme en LinkedIn y GitHub, o escribirme por correo electrónico. El CV estará disponible para descarga desde el portfolio.',
            'contact_intro_en' => 'You can find me on LinkedIn and GitHub, or contact me by email. The CV will be available to download from the portfolio.',
        ];

        foreach ($expected as $field => $value) {
            $this->assertSame($value, $site[$field], "Site.{$field} drifted from approved copy.");
        }
    }

    /**
     * Exhaustive verbatim pin of EVERY approved professional-link value:
     * type, position, destination, and both accessibility labels for all 3.
     */
    public function test_professional_links_pin_every_approved_value(): void
    {
        $links = $this->data()['professional_links'];

        $expected = [
            [
                'type' => ProfessionalLinkType::LinkedIn,
                'position' => 0,
                'destination' => 'https://www.linkedin.com/in/luciano-gonzález-590350294',
                'label_es' => 'LinkedIn de Luciano González',
                'label_en' => "Luciano González's LinkedIn",
            ],
            [
                'type' => ProfessionalLinkType::GitHub,
                'position' => 1,
                'destination' => 'https://github.com/Gonzalez-Luciano',
                'label_es' => 'GitHub de Luciano González',
                'label_en' => "Luciano González's GitHub",
            ],
            [
                'type' => ProfessionalLinkType::Email,
                'position' => 2,
                'destination' => 'lucianogonzalez12004@gmail.com',
                'label_es' => 'Enviar un correo a Luciano González',
                'label_en' => 'Email Luciano González',
            ],
        ];

        foreach ($expected as $index => $row) {
            foreach ($row as $field => $value) {
                $this->assertSame($value, $links[$index][$field], "professional_links[{$index}].{$field} drifted.");
            }
        }
    }

    /**
     * Exhaustive verbatim pin of EVERY approved ExpertiseArea key + bilingual
     * title (all six, in order).
     */
    public function test_expertise_areas_pin_every_approved_title(): void
    {
        $areas = $this->data()['expertise_areas'];

        $expected = [
            ['php-laravel-development', 'Desarrollo y mantenimiento con PHP y Laravel.', 'Development and maintenance with PHP and Laravel.'],
            ['rest-apis-business-logic', 'APIs REST y lógica de negocio.', 'REST APIs and business logic.'],
            ['external-integrations', 'Integraciones con servicios externos.', 'Integrations with external services.'],
            ['mysql-query-improvement', 'MySQL y mejora de consultas.', 'MySQL and query improvement.'],
            ['scheduled-backend-automation', 'Procesos programados y automatización backend.', 'Scheduled processes and backend automation.'],
            ['application-legacy-maintenance', 'Mantenimiento de aplicaciones y código heredado.', 'Application and legacy code maintenance.'],
        ];

        $this->assertSame(
            $expected,
            array_map(
                static fn (array $a): array => [$a['key'], $a['title_es'], $a['title_en']],
                $areas,
            ),
        );
    }

    /**
     * Exhaustive verbatim pin of EVERY approved Work Case key + bilingual
     * title + bilingual context (all four, in order). The four detail fields
     * are separately pinned null.
     */
    public function test_work_cases_pin_every_approved_title_and_context(): void
    {
        $cases = $this->data()['work_cases'];

        $expected = [
            [
                'integrations-synchronization',
                'Integraciones y sincronización',
                'Integrations and synchronization',
                'Trabajo backend para integrar y mantener comunicación con APIs externas, con atención a la consistencia de los flujos y al mantenimiento responsable de la integración.',
                'Backend work to integrate and maintain communication with external APIs, with attention to flow consistency and responsible integration maintenance.',
            ],
            [
                'education-management',
                'Gestión educativa',
                'Education management',
                'Desarrollo y mantenimiento de funcionalidades para una plataforma de gestión educativa, con una mirada centrada en los procesos backend y en la evolución del código existente.',
                'Development and maintenance of features for an education management platform, with a focus on backend processes and the evolution of existing code.',
            ],
            [
                'data-and-automation',
                'Datos y automatización',
                'Data and automation',
                'Mejora de consultas MySQL y desarrollo de procesos backend programados para apoyar la continuidad operativa de aplicaciones.',
                'Improving MySQL queries and developing scheduled backend processes to support the operational continuity of applications.',
            ],
            [
                'cross-layer-integration',
                'Integración entre capas',
                'Integration across layers',
                'Participación en integraciones entre una API Laravel y una interfaz Angular, manteniendo el alcance en el nivel de tecnologías y colaboración técnica.',
                'Participation in integrations between a Laravel API and an Angular interface, keeping the scope at the level of technologies and technical collaboration.',
            ],
        ];

        $this->assertSame(
            $expected,
            array_map(
                static fn (array $c): array => [$c['key'], $c['title_es'], $c['title_en'], $c['context_es'], $c['context_en']],
                $cases,
            ),
        );
    }

    /**
     * Exhaustive verbatim pin of the single approved Work Principle.
     */
    public function test_work_principle_pins_every_approved_string(): void
    {
        $principle = $this->data()['work_principles'][0];

        $this->assertSame('working-approach', $principle['key']);
        $this->assertSame(
            'Priorizo entender el problema, cuidar la consistencia de la lógica y dejar soluciones mantenibles. El trabajo se comunica con claridad y se adapta al contexto técnico de cada aplicación.',
            $principle['statement_es'],
        );
        $this->assertSame(
            'I prioritize understanding the problem, maintaining logic consistency, and leaving maintainable solutions. Work is communicated clearly and adapted to the technical context of each application.',
            $principle['statement_en'],
        );
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

        // Precise runtime document/asset IO. Deliberately NOT '->get(' — that
        // would false-positive on a legitimate collection/array accessor.
        $forbidden = [
            'file_get_contents', 'fopen', 'fread', 'fgets', 'fgetcsv', 'fscanf',
            'readfile', 'file_put_contents', 'str_getcsv',
            'SplFileObject', 'SplFileInfo',
            'finfo_open', 'finfo_file', 'mime_content_type',
            'md5_file', 'sha1_file', 'hash_file', 'getimagesize',
            'simplexml_load_file', 'parse_ini_file', 'yaml_parse_file',
            'Storage::', 'File::', 'Http::', 'Yaml::parseFile', 'Yaml::parse',
            '->getContent(', '->getContents(', 'CommonMark', 'Parsedown',
        ];

        foreach ($forbidden as $token) {
            $this->assertStringNotContainsString($token, $source, "Dataset must not use {$token} at runtime.");
        }

        // Bare file(...) call, without matching an identifier suffix such as profile().
        $this->assertDoesNotMatchRegularExpression('/(?<![\w>])file\s*\(/', $source, 'Dataset must not call file() at runtime.');

        // No regex scanning over source documents.
        foreach (['preg_match', 'preg_replace', 'preg_split'] as $token) {
            $this->assertStringNotContainsString($token, $source, "Dataset must not use {$token} at runtime.");
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
