<?php

namespace App\Domain\Content;

use App\Enums\ProfessionalLinkType;
use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;

/**
 * The single code-level representation of the approved initial portfolio
 * content and asset inputs (spec 2026-09-09-phase-5-public-site-design.md §29).
 *
 * Human editors review the approved Markdown authorities:
 *
 * - docs/content/CONTENT.es.md, docs/content/CONTENT.en.md;
 * - docs/content/CONFIDENTIALITY_MATRIX.md;
 * - docs/content/ASSET_INVENTORY.md;
 * - docs/content/SOURCE_INVENTORY.md;
 * - later explicit documented approvals.
 *
 * Their approved values are transcribed here as plain literal arrays. This
 * class NEVER parses those documents at runtime: no Markdown parser, no
 * file_get_contents / fopen over CONTENT.*.md or any PDF, no regex over docs,
 * no CV text extraction, no NLP, no translation call, no docs-to-CMS sync.
 * It is a deterministic import representation, not a second editorial authority.
 *
 * Consumers:
 *
 * - the one-time `php artisan portfolio:import-initial-content` command
 *   (Task 12) consumes the full dataset, including the `assets` slice;
 * - `Database\Seeders\PortfolioContentSeeder` consumes only the non-asset
 *   slice for repeatable development/editorial seeding. There is exactly one
 *   copy of the bilingual professional text; the two consumers cannot drift.
 *
 * Known editorial gaps are represented as explicit `null` / `[]`, never
 * invented (spec §29.5):
 *
 * - `experiences` and `projects` stay `[]` here regardless of approved
 *   content: `InitialPortfolioImporter::assertDatasetShape()` hard-rejects a
 *   non-empty value for either (this one-time import only fills the two
 *   pristine structural singletons and the small reference collections).
 *   Real Experience/Project rows — sourced from the approved bilingual CVs
 *   (`docs/content/approved-assets/cv-{es,en}.pdf`) and the public GitHub
 *   project they reference — are created afterward through the normal
 *   content-authoring actions (`UpdateExperienceAggregate`,
 *   `UpdateContentWithTechnologies`), the same path any future editorial
 *   addition uses;
 * - every ExpertiseArea `description_{es,en}` is `null`: each bullet is a
 *   single approved sentence with no separate approved description.
 *
 * Work Case `problem` / `contribution` / `technical_approach` / `outcome`
 * and the SiteConfiguration `technology_*_label_{es,en}` pairs below are
 * filled from the same approved CVs (structured, not fabricated: each field
 * transcribes a real CV bullet into its required slot).
 *
 * Every publishable entity carries the draft-gap markers `status = Draft`,
 * `is_visible = false`, `published_at = null` (spec §29.1 / §29.3).
 *
 * @phpstan-type PublishableState array{status: PublicationStatus, is_visible: false, published_at: null}
 */
final class InitialPortfolioContent
{
    /**
     * @return array{
     *     profile: array{
     *         singleton_key: 'default',
     *         name: string,
     *         headline_es: string, headline_en: string,
     *         short_summary_es: string, short_summary_en: string,
     *         introduction_es: string, introduction_en: string,
     *         availability_es: string, availability_en: string,
     *         cta_es: string, cta_en: string,
     *         status: PublicationStatus, is_visible: false, published_at: null
     *     },
     *     site: array{
     *         singleton_key: 'default',
     *         projects_empty_message_es: string, projects_empty_message_en: string,
     *         contact_intro_es: string, contact_intro_en: string,
     *         technology_backend_label_es: null, technology_backend_label_en: null,
     *         technology_data_label_es: null, technology_data_label_en: null,
     *         technology_integration_label_es: null, technology_integration_label_en: null,
     *         technology_collaboration_label_es: null, technology_collaboration_label_en: null,
     *         status: PublicationStatus, is_visible: false, published_at: null
     *     },
     *     professional_links: list<array{
     *         type: ProfessionalLinkType, position: int,
     *         destination: string, label_es: string, label_en: string,
     *         status: PublicationStatus, is_visible: false, published_at: null
     *     }>,
     *     technologies: list<array{
     *         key: string, name: string, category: TechnologyCategory, position: int,
     *         status: PublicationStatus, is_visible: false, published_at: null
     *     }>,
     *     expertise_areas: list<array{
     *         key: string, position: int,
     *         title_es: string, title_en: string,
     *         description_es: null, description_en: null,
     *         status: PublicationStatus, is_visible: false, published_at: null
     *     }>,
     *     work_cases: list<array{
     *         key: string, position: int,
     *         title_es: string, title_en: string,
     *         context_es: string, context_en: string,
     *         problem_es: null, problem_en: null,
     *         contribution_es: null, contribution_en: null,
     *         technical_approach_es: null, technical_approach_en: null,
     *         outcome_es: null, outcome_en: null,
     *         status: PublicationStatus, is_visible: false, published_at: null
     *     }>,
     *     work_principles: list<array{
     *         key: string, position: int,
     *         statement_es: string, statement_en: string,
     *         status: PublicationStatus, is_visible: false, published_at: null
     *     }>,
     *     experiences: list<never>,
     *     projects: list<never>,
     *     assets: array{
     *         photo: string, cv_es: string, cv_en: string,
     *         photo_alt_es: ?string, photo_alt_en: ?string,
     *         cv_es_label: ?string, cv_en_label: ?string
     *     }
     * }
     */
    public static function data(): array
    {
        return [
            'profile' => self::profile(),
            'site' => self::site(),
            'professional_links' => self::professionalLinks(),
            'technologies' => self::technologies(),
            'expertise_areas' => self::expertiseAreas(),
            'work_cases' => self::workCases(),
            'work_principles' => self::workPrinciples(),
            'experiences' => [],
            'projects' => [],
            'assets' => self::assets(),
        ];
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Hero" and "Presentación
     * profesional" / "Professional introduction" sections.
     *
     * @return array<string, mixed>
     */
    private static function profile(): array
    {
        return [
            'singleton_key' => 'default',
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
        ];
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Proyectos" / "Projects" (zero
     * published-project state, still the correct fallback copy when the
     * Projects collection is ever empty again) and "Contacto y CV" / "Contact
     * and CV" sections. `technology_*_label_*` are plain bilingual category
     * headers for the four fixed `TechnologyCategory` groups, not sourced
     * from CONTENT.*.md.
     *
     * @return array<string, mixed>
     */
    private static function site(): array
    {
        return [
            'singleton_key' => 'default',
            'projects_empty_message_es' => 'Actualmente no hay proyectos publicados en el portfolio. Los próximos proyectos se incorporarán cuando cuenten con una presentación técnica y pública adecuada.',
            'projects_empty_message_en' => 'There are currently no published projects in the portfolio. Future projects will be added when they have an appropriate technical and public presentation.',
            'contact_intro_es' => 'Podés encontrarme en LinkedIn y GitHub, o escribirme por correo electrónico. El CV estará disponible para descarga desde el portfolio.',
            'contact_intro_en' => 'You can find me on LinkedIn and GitHub, or contact me by email. The CV will be available to download from the portfolio.',
            'technology_backend_label_es' => 'Backend',
            'technology_backend_label_en' => 'Backend',
            'technology_data_label_es' => 'Datos',
            'technology_data_label_en' => 'Data',
            'technology_integration_label_es' => 'Integraciones',
            'technology_integration_label_en' => 'Integrations',
            'technology_collaboration_label_es' => 'Frontend y colaboración',
            'technology_collaboration_label_en' => 'Frontend & collaboration',
            'status' => PublicationStatus::Draft,
            'is_visible' => false,
            'published_at' => null,
        ];
    }

    /**
     * Source: ASSET_INVENTORY.md (AST-CONTACT-LINKEDIN, AST-CONTACT-GITHUB,
     * AST-CONTACT-EMAIL) and CONFIDENTIALITY_MATRIX.md (PUB-027..PUB-029).
     * Destinations are the exact approved canonical URLs/email; labels reuse
     * the approved bilingual accessibility text. Order follows SITEMAP.md's
     * contact section order (LinkedIn, GitHub, email).
     *
     * @return list<array<string, mixed>>
     */
    private static function professionalLinks(): array
    {
        return [
            self::publishable([
                'type' => ProfessionalLinkType::LinkedIn,
                'position' => 0,
                'destination' => 'https://www.linkedin.com/in/luciano-gonz%C3%A1lez-590350294',
                'label_es' => 'LinkedIn',
                'label_en' => 'LinkedIn',
            ]),
            self::publishable([
                'type' => ProfessionalLinkType::GitHub,
                'position' => 1,
                'destination' => 'https://github.com/Gonzalez-Luciano',
                'label_es' => 'GitHub',
                'label_en' => 'GitHub',
            ]),
            self::publishable([
                'type' => ProfessionalLinkType::Email,
                'position' => 2,
                'destination' => 'lucianogonzalez12004@gmail.com',
                'label_es' => 'Enviame un correo',
                'label_en' => 'Email me',
            ]),
        ];
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Tecnologías" / "Technologies"
     * section ("PHP · Laravel · MySQL · APIs REST · Angular"). Category
     * mapping is the exact closed mapping required by the plan/spec:
     * PHP and Laravel -> backend, MySQL -> data, REST APIs -> integration,
     * Angular -> collaboration.
     *
     * @return list<array<string, mixed>>
     */
    private static function technologies(): array
    {
        return [
            self::publishable(['key' => 'php', 'name' => 'PHP', 'category' => TechnologyCategory::Backend, 'position' => 0]),
            self::publishable(['key' => 'laravel', 'name' => 'Laravel', 'category' => TechnologyCategory::Backend, 'position' => 1]),
            self::publishable(['key' => 'mysql', 'name' => 'MySQL', 'category' => TechnologyCategory::Data, 'position' => 2]),
            self::publishable(['key' => 'rest-apis', 'name' => 'REST APIs', 'category' => TechnologyCategory::Integration, 'position' => 3]),
            self::publishable(['key' => 'angular', 'name' => 'Angular', 'category' => TechnologyCategory::Collaboration, 'position' => 4]),
            self::publishable(['key' => 'postgresql', 'name' => 'PostgreSQL', 'category' => TechnologyCategory::Data, 'position' => 5]),
            self::publishable(['key' => 'redis', 'name' => 'Redis', 'category' => TechnologyCategory::Data, 'position' => 6]),
            self::publishable(['key' => 'react', 'name' => 'React', 'category' => TechnologyCategory::Collaboration, 'position' => 7]),
            self::publishable(['key' => 'docker', 'name' => 'Docker', 'category' => TechnologyCategory::Integration, 'position' => 8]),
        ];
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Áreas de especialización" /
     * "Areas of specialization" bullet list. Each bullet is a single approved
     * sentence stored as `title`; there is no separate approved description,
     * so `description_es` / `description_en` stay null.
     *
     * @return list<array<string, mixed>>
     */
    private static function expertiseAreas(): array
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

        $rows = [];

        foreach (array_values($areas) as $position => $area) {
            $rows[] = self::publishable([
                'key' => $area['key'],
                'position' => $position,
                'title_es' => $area['title_es'],
                'title_en' => $area['title_en'],
                'description_es' => null,
                'description_en' => null,
            ]);
        }

        return $rows;
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Casos de trabajo" / "Work cases"
     * section for `title`/`context`. `problem`/`contribution`/
     * `technical_approach`/`outcome` are transcribed from the matching real
     * bullets in the approved bilingual CVs
     * (`docs/content/approved-assets/cv-{es,en}.pdf`) for the same CONED
     * work each case describes — restructured into the four required slots,
     * not invented.
     *
     * @return list<array<string, mixed>>
     */
    private static function workCases(): array
    {
        $cases = [
            [
                'key' => 'integrations-synchronization',
                'title_es' => 'Integraciones y sincronización',
                'title_en' => 'Integrations and synchronization',
                'context_es' => 'Trabajo backend para integrar y mantener comunicación con APIs externas, con atención a la consistencia de los flujos y al mantenimiento responsable de la integración.',
                'context_en' => 'Backend work to integrate and maintain communication with external APIs, with attention to flow consistency and responsible integration maintenance.',
                'problem_es' => 'El sistema necesitaba conciliar el estado de cuenta de los alumnos con tres medios de pago externos (SIRO, CES y Mercado Pago), evitando habilitaciones incorrectas por pagos no reflejados a tiempo.',
                'problem_en' => 'The system needed to reconcile student account status against three external payment providers (SIRO, CES, and Mercado Pago), avoiding incorrect enrollment status caused by payments not reflected in time.',
                'contribution_es' => 'Integré los tres medios de pago sobre Laravel, implementando la sincronización de pagos, la actualización automática de comprobantes y la habilitación de alumnos según su estado de cuenta.',
                'contribution_en' => 'I integrated the three payment providers on Laravel, implementing payment synchronization, automatic receipt updates, and student enrollment activation based on account status.',
                'technical_approach_es' => 'Cada proveedor se integró detrás de su propio flujo de sincronización en PHP/Laravel, con procesos programados que consultan el estado de los pagos y actualizan los comprobantes y el estado de cuenta de forma automática.',
                'technical_approach_en' => 'Each provider was integrated behind its own PHP/Laravel synchronization flow, with scheduled processes that check payment status and automatically update receipts and account state.',
                'outcome_es' => 'Los alumnos quedan habilitados o inhabilitados de forma automática según su estado de cuenta real, sin intervención manual sobre los tres medios de pago.',
                'outcome_en' => 'Students are automatically enabled or disabled based on their real account status, with no manual work required across the three payment providers.',
            ],
            [
                'key' => 'education-management',
                'title_es' => 'Gestión educativa',
                'title_en' => 'Education management',
                'context_es' => 'Desarrollo y mantenimiento de funcionalidades para una plataforma de gestión educativa, con una mirada centrada en los procesos backend y en la evolución del código existente.',
                'context_en' => 'Development and maintenance of features for an education management platform, with a focus on backend processes and the evolution of existing code.',
                'problem_es' => 'La plataforma educativa multiinstitución necesitaba módulos financieros y académicos confiables (facturación, deudas, cuentas corrientes, mesas de examen, materias, cursos e inscripciones), además de un proceso simple para dar de alta nuevas instituciones.',
                'problem_en' => 'The multi-institution education platform needed reliable financial and academic modules (invoicing, outstanding debt, current accounts, exam boards, subjects, courses, and enrollments), plus a simple process for onboarding new institutions.',
                'contribution_es' => 'Desarrollé endpoints REST, validaciones y lógica de negocio en PHP/Laravel para los módulos financieros, y mantengo y modernizo los módulos académicos existentes. También construí la plataforma de onboarding para la creación y configuración de nuevas instituciones.',
                'contribution_en' => 'I developed REST endpoints, validations, and business logic in PHP/Laravel for the financial modules, and I maintain and modernize the existing academic modules. I also built the onboarding platform for creating and configuring new institutions.',
                'technical_approach_es' => 'Trabajo sobre una base de código existente en Laravel, priorizando consistencia con los módulos ya en producción, control de acceso por roles y permisos, e integración con el frontend Angular.',
                'technical_approach_en' => 'I work on an existing Laravel codebase, prioritizing consistency with modules already in production, role- and permission-based access control, and integration with the Angular frontend.',
                'outcome_es' => 'La plataforma sostiene la operación financiera y académica de múltiples instituciones, y el alta de una institución nueva se simplificó gracias a la plataforma de onboarding.',
                'outcome_en' => 'The platform sustains the financial and academic operation of multiple institutions, and onboarding a new institution was simplified through the dedicated onboarding platform.',
            ],
            [
                'key' => 'data-and-automation',
                'title_es' => 'Datos y automatización',
                'title_en' => 'Data and automation',
                'context_es' => 'Mejora de consultas MySQL y desarrollo de procesos backend programados para apoyar la continuidad operativa de aplicaciones.',
                'context_en' => 'Improving MySQL queries and developing scheduled backend processes to support the operational continuity of applications.',
                'problem_es' => 'Procesos con alto volumen de datos sobre MySQL y código legacy afectaban el rendimiento, mientras que tareas internas repetitivas se realizaban de forma manual.',
                'problem_en' => 'High-volume MySQL processes and legacy code were affecting performance, while repetitive internal tasks were still being handled manually.',
                'contribution_es' => 'Optimicé consultas SQL sobre MySQL y refactoricé código legacy para mejorar el rendimiento, y automaticé procesos internos mediante comandos Artisan y tareas programadas.',
                'contribution_en' => 'I optimized SQL queries on MySQL and refactored legacy code to improve performance, and automated internal processes through Artisan commands and scheduled tasks.',
                'technical_approach_es' => 'Analicé las consultas con mayor impacto en el rendimiento, ajusté índices y estructura de las queries, y encapsulé los procesos recurrentes en comandos Artisan programados.',
                'technical_approach_en' => 'I analyzed the queries with the greatest performance impact, adjusted indexes and query structure, and encapsulated recurring processes into scheduled Artisan commands.',
                'outcome_es' => 'Los procesos de alto volumen de datos mejoraron su rendimiento y las tareas internas que antes eran manuales ahora corren de forma automática y programada.',
                'outcome_en' => 'High-volume data processes improved their performance, and internal tasks that used to be manual now run automatically on a schedule.',
            ],
            [
                'key' => 'cross-layer-integration',
                'title_es' => 'Integración entre capas',
                'title_en' => 'Integration across layers',
                'context_es' => 'Participación en integraciones entre una API Laravel y una interfaz Angular, manteniendo el alcance en el nivel de tecnologías y colaboración técnica.',
                'context_en' => 'Participation in integrations between a Laravel API and an Angular interface, keeping the scope at the level of technologies and technical collaboration.',
                'problem_es' => 'La API en Laravel y la interfaz en Angular necesitaban mantenerse integradas de forma consistente a medida que se resolvían incidencias en producción.',
                'problem_en' => 'The Laravel API and the Angular interface needed to stay consistently integrated while production incidents were being resolved.',
                'contribution_es' => 'Integré endpoints backend con el frontend Angular y resolví incidencias de producción mediante debugging y análisis de errores.',
                'contribution_en' => 'I integrated backend endpoints with the Angular frontend and resolved production incidents through debugging and error analysis.',
                'technical_approach_es' => 'Trabajé desde el lado del backend, validando los contratos de los endpoints consumidos por Angular y diagnosticando errores de producción hasta su causa raíz antes de aplicar una corrección.',
                'technical_approach_en' => 'I worked from the backend side, validating the endpoint contracts consumed by Angular and diagnosing production errors down to their root cause before applying a fix.',
                'outcome_es' => 'La integración entre la API Laravel y la interfaz Angular se mantuvo estable, con incidencias de producción resueltas de forma directa.',
                'outcome_en' => 'The integration between the Laravel API and the Angular interface stayed stable, with production incidents resolved directly.',
            ],
        ];

        $rows = [];

        foreach (array_values($cases) as $position => $case) {
            $rows[] = self::publishable([
                'key' => $case['key'],
                'position' => $position,
                'title_es' => $case['title_es'],
                'title_en' => $case['title_en'],
                'context_es' => $case['context_es'],
                'context_en' => $case['context_en'],
                'problem_es' => $case['problem_es'],
                'problem_en' => $case['problem_en'],
                'contribution_es' => $case['contribution_es'],
                'contribution_en' => $case['contribution_en'],
                'technical_approach_es' => $case['technical_approach_es'],
                'technical_approach_en' => $case['technical_approach_en'],
                'outcome_es' => $case['outcome_es'],
                'outcome_en' => $case['outcome_en'],
            ]);
        }

        return $rows;
    }

    /**
     * Source: CONTENT.es.md / CONTENT.en.md "Forma de trabajo" / "Working
     * approach" section — a single approved paragraph, represented as one
     * principle rather than split into invented separate statements.
     *
     * @return list<array<string, mixed>>
     */
    private static function workPrinciples(): array
    {
        return [
            self::publishable([
                'key' => 'working-approach',
                'position' => 0,
                'statement_es' => 'Priorizo entender el problema, cuidar la consistencia de la lógica y dejar soluciones mantenibles. El trabajo se comunica con claridad y se adapta al contexto técnico de cada aplicación.',
                'statement_en' => 'I prioritize understanding the problem, maintaining logic consistency, and leaving maintainable solutions. Work is communicated clearly and adapted to the technical context of each application.',
            ]),
        ];
    }

    /**
     * Source asset inputs (spec §29.1) and ASSET_INVENTORY.md.
     *
     * `photo` / `cv_es` / `cv_en` are the exact repository-relative tracked
     * source files. This class only names them; it never opens or reads them.
     * The seeder ignores this slice entirely; only Task 12's import command
     * consumes it.
     *
     * `photo_alt_es` / `photo_alt_en` are the approved bilingual portrait alt
     * text recorded for AST-PHOTO-PROFILE. `cv_es_label` / `cv_en_label` are
     * the approved per-language download accessibility text recorded for
     * AST-CV-ES / AST-CV-EN (ASSET_INVENTORY.md labels this column
     * "Accessibility text"; Task 12 must confirm the CvDocument.label mapping).
     *
     * @return array{
     *     photo: string, cv_es: string, cv_en: string,
     *     photo_alt_es: ?string, photo_alt_en: ?string,
     *     cv_es_label: ?string, cv_en_label: ?string
     * }
     */
    private static function assets(): array
    {
        return [
            'photo' => 'docs/content/approved-assets/professional-photo.jpg',
            'cv_es' => 'docs/content/approved-assets/cv-es.pdf',
            'cv_en' => 'docs/content/approved-assets/cv-en.pdf',
            'photo_alt_es' => 'Retrato profesional de Luciano González sobre fondo naranja',
            'photo_alt_en' => 'Professional portrait of Luciano González against an orange background',
            'cv_es_label' => 'Descargar CV',
            'cv_en_label' => 'Download CV',
        ];
    }

    /**
     * Stamps the mandatory draft-gap markers onto a publishable entity row
     * (spec §29.1 / §29.3): status = Draft, is_visible = false,
     * published_at = null, without exception.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function publishable(array $attributes): array
    {
        return $attributes + [
            'status' => PublicationStatus::Draft,
            'is_visible' => false,
            'published_at' => null,
        ];
    }
}
