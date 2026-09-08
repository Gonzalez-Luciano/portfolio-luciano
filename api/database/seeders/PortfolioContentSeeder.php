<?php

namespace Database\Seeders;

use App\Enums\ProfessionalLinkType;
use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Illuminate\Database\Seeder;

/**
 * Loads approved Phase 1/2 public portfolio content (see docs/content/CONTENT.es.md,
 * docs/content/CONTENT.en.md, docs/content/CONFIDENTIALITY_MATRIX.md, and
 * docs/content/ASSET_INVENTORY.md) as draft, hidden, unpublished rows for
 * development and editorial review inside the admin panel.
 *
 * This seeder is intentionally non-destructive and never a deployment or
 * migration prerequisite:
 *
 * - it never sets status=published, is_visible=true, or published_at;
 * - it never uploads, copies, or reads any asset/CV file to any disk;
 * - it never creates Project, CvDocument, or user/admin rows;
 * - it uses updateOrCreate against approved stable keys/types so re-running
 *   it is idempotent and never deletes or duplicates rows;
 * - it never invents employers, clients, dates, metrics, or technologies
 *   beyond what the approved content sources state.
 *
 * Experience/ExperienceHighlight are deliberately NOT seeded: the approved
 * content (CONTENT.es.md / CONTENT.en.md "Experiencia"/"Experience") and the
 * confidentiality matrix (PUB-002, PUB-003, ...) never approve a specific
 * employer, role title, or start/end date, while the `experiences` table
 * requires a non-null start_year/start_month. Inventing a date to satisfy
 * that constraint is prohibited, so this seeder leaves that table empty
 * rather than fabricate timeline facts.
 *
 * Run explicitly, never from DatabaseSeeder::run():
 *   php artisan db:seed --class=PortfolioContentSeeder
 */
final class PortfolioContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedProfile();
        $this->seedSiteConfiguration();
        $this->seedProfessionalLinks();
        $this->seedTechnologies();
        $this->seedExpertiseAreas();
        $this->seedWorkCases();
        $this->seedWorkPrinciples();
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Hero" and "Presentación
     * profesional" / "Professional introduction" sections.
     */
    private function seedProfile(): void
    {
        Profile::query()->updateOrCreate(
            ['singleton_key' => 'default'],
            [
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
                'status' => PublicationStatus::Draft,
                'is_visible' => false,
                'published_at' => null,
            ]
        );
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Proyectos" / "Projects" (zero
     * published-project state) and "Contacto y CV" / "Contact and CV"
     * sections. Technology group labels are not given approved exact
     * wording anywhere in the approved content, so they are left null
     * rather than invented.
     */
    private function seedSiteConfiguration(): void
    {
        SiteConfiguration::query()->updateOrCreate(
            ['singleton_key' => 'default'],
            [
                'projects_empty_message_es' => 'Actualmente no hay proyectos publicados en el portfolio. Los próximos proyectos se incorporarán cuando cuenten con una presentación técnica y pública adecuada.',
                'projects_empty_message_en' => 'There are currently no published projects in the portfolio. Future projects will be added when they have an appropriate technical and public presentation.',
                'contact_intro_es' => 'Podés encontrarme en LinkedIn y GitHub, o escribirme por correo electrónico. El CV estará disponible para descarga desde el portfolio.',
                'contact_intro_en' => 'You can find me on LinkedIn and GitHub, or contact me by email. The CV will be available to download from the portfolio.',
                'status' => PublicationStatus::Draft,
                'is_visible' => false,
                'published_at' => null,
            ]
        );
    }

    /**
     * Source: ASSET_INVENTORY.md (AST-CONTACT-LINKEDIN, AST-CONTACT-GITHUB,
     * AST-CONTACT-EMAIL) and CONFIDENTIALITY_MATRIX.md (PUB-027, PUB-028,
     * PUB-029). Destinations are the exact approved canonical URLs/email;
     * labels reuse the approved bilingual accessibility text. Order follows
     * SITEMAP.md's contact section order (LinkedIn, GitHub, email).
     */
    private function seedProfessionalLinks(): void
    {
        $links = [
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

        foreach ($links as $link) {
            ProfessionalLink::query()->updateOrCreate(
                ['type' => $link['type']],
                [
                    'position' => $link['position'],
                    'destination' => $link['destination'],
                    'label_es' => $link['label_es'],
                    'label_en' => $link['label_en'],
                    'status' => PublicationStatus::Draft,
                    'is_visible' => false,
                    'published_at' => null,
                ]
            );
        }
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Tecnologías" / "Technologies"
     * section ("PHP · Laravel · MySQL · APIs REST · Angular"). Category
     * mapping is the exact closed mapping required by the plan/spec:
     * PHP and Laravel -> backend, MySQL -> data, REST APIs -> integration,
     * Angular -> collaboration.
     */
    private function seedTechnologies(): void
    {
        $technologies = [
            ['key' => 'php', 'name' => 'PHP', 'category' => TechnologyCategory::Backend, 'position' => 0],
            ['key' => 'laravel', 'name' => 'Laravel', 'category' => TechnologyCategory::Backend, 'position' => 1],
            ['key' => 'mysql', 'name' => 'MySQL', 'category' => TechnologyCategory::Data, 'position' => 2],
            ['key' => 'rest-apis', 'name' => 'REST APIs', 'category' => TechnologyCategory::Integration, 'position' => 3],
            ['key' => 'angular', 'name' => 'Angular', 'category' => TechnologyCategory::Collaboration, 'position' => 4],
        ];

        foreach ($technologies as $technology) {
            Technology::query()->updateOrCreate(
                ['key' => $technology['key']],
                [
                    'position' => $technology['position'],
                    'name' => $technology['name'],
                    'category' => $technology['category'],
                    'status' => PublicationStatus::Draft,
                    'is_visible' => false,
                    'published_at' => null,
                ]
            );
        }
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Áreas de especialización" /
     * "Areas of specialization" bullet list. Each bullet is a single
     * approved sentence; there is no separate approved description beyond
     * the bullet itself, so description_es/en are left null.
     */
    private function seedExpertiseAreas(): void
    {
        $areas = [
            [
                'key' => 'php-laravel-development',
                'title_es' => 'Desarrollo y mantenimiento con PHP y Laravel.',
                'title_en' => 'Development and maintenance with PHP and Laravel.',
            ],
            [
                'key' => 'rest-apis-business-logic',
                'title_es' => 'APIs REST y lógica de negocio.',
                'title_en' => 'REST APIs and business logic.',
            ],
            [
                'key' => 'external-integrations',
                'title_es' => 'Integraciones con servicios externos.',
                'title_en' => 'Integrations with external services.',
            ],
            [
                'key' => 'mysql-query-improvement',
                'title_es' => 'MySQL y mejora de consultas.',
                'title_en' => 'MySQL and query improvement.',
            ],
            [
                'key' => 'scheduled-backend-automation',
                'title_es' => 'Procesos programados y automatización backend.',
                'title_en' => 'Scheduled processes and backend automation.',
            ],
            [
                'key' => 'application-legacy-maintenance',
                'title_es' => 'Mantenimiento de aplicaciones y código heredado.',
                'title_en' => 'Application and legacy code maintenance.',
            ],
        ];

        foreach ($areas as $position => $area) {
            ExpertiseArea::query()->updateOrCreate(
                ['key' => $area['key']],
                [
                    'position' => $position,
                    'title_es' => $area['title_es'],
                    'title_en' => $area['title_en'],
                    'description_es' => null,
                    'description_en' => null,
                    'status' => PublicationStatus::Draft,
                    'is_visible' => false,
                    'published_at' => null,
                ]
            );
        }
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Casos de trabajo" / "Work
     * cases" section. Each case is one approved descriptive paragraph, not
     * separately structured into problem/contribution/technical
     * approach/outcome, so the paragraph is placed in context_es/en and the
     * other descriptive fields are left null rather than invented.
     */
    private function seedWorkCases(): void
    {
        $cases = [
            [
                'key' => 'integrations-synchronization',
                'title_es' => 'Integraciones y sincronización',
                'title_en' => 'Integrations and synchronization',
                'context_es' => 'Trabajo backend para integrar y mantener comunicación con APIs externas, con atención a la consistencia de los flujos y al mantenimiento responsable de la integración.',
                'context_en' => 'Backend work to integrate and maintain communication with external APIs, with attention to flow consistency and responsible integration maintenance.',
            ],
            [
                'key' => 'education-management',
                'title_es' => 'Gestión educativa',
                'title_en' => 'Education management',
                'context_es' => 'Desarrollo y mantenimiento de funcionalidades para una plataforma de gestión educativa, con una mirada centrada en los procesos backend y en la evolución del código existente.',
                'context_en' => 'Development and maintenance of features for an education management platform, with a focus on backend processes and the evolution of existing code.',
            ],
            [
                'key' => 'data-and-automation',
                'title_es' => 'Datos y automatización',
                'title_en' => 'Data and automation',
                'context_es' => 'Mejora de consultas MySQL y desarrollo de procesos backend programados para apoyar la continuidad operativa de aplicaciones.',
                'context_en' => 'Improving MySQL queries and developing scheduled backend processes to support the operational continuity of applications.',
            ],
            [
                'key' => 'cross-layer-integration',
                'title_es' => 'Integración entre capas',
                'title_en' => 'Integration across layers',
                'context_es' => 'Participación en integraciones entre una API Laravel y una interfaz Angular, manteniendo el alcance en el nivel de tecnologías y colaboración técnica.',
                'context_en' => 'Participation in integrations between a Laravel API and an Angular interface, keeping the scope at the level of technologies and technical collaboration.',
            ],
        ];

        foreach ($cases as $position => $case) {
            WorkCase::query()->updateOrCreate(
                ['key' => $case['key']],
                [
                    'position' => $position,
                    'title_es' => $case['title_es'],
                    'title_en' => $case['title_en'],
                    'context_es' => $case['context_es'],
                    'context_en' => $case['context_en'],
                    'problem_es' => null,
                    'problem_en' => null,
                    'contribution_es' => null,
                    'contribution_en' => null,
                    'technical_approach_es' => null,
                    'technical_approach_en' => null,
                    'outcome_es' => null,
                    'outcome_en' => null,
                    'status' => PublicationStatus::Draft,
                    'is_visible' => false,
                    'published_at' => null,
                ]
            );
        }
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Forma de trabajo" / "Working
     * approach" section — a single approved paragraph, seeded as one
     * principle rather than split into invented separate statements.
     */
    private function seedWorkPrinciples(): void
    {
        WorkPrinciple::query()->updateOrCreate(
            ['key' => 'working-approach'],
            [
                'position' => 0,
                'statement_es' => 'Priorizo entender el problema, cuidar la consistencia de la lógica y dejar soluciones mantenibles. El trabajo se comunica con claridad y se adapta al contexto técnico de cada aplicación.',
                'statement_en' => 'I prioritize understanding the problem, maintaining logic consistency, and leaving maintainable solutions. Work is communicated clearly and adapted to the technical context of each application.',
                'status' => PublicationStatus::Draft,
                'is_visible' => false,
                'published_at' => null,
            ]
        );
    }
}
