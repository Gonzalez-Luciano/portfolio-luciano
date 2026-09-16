<?php

namespace Database\Seeders;

use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Content\Actions\UpdateContentWithTechnologies;
use App\Domain\Content\Actions\UpdateExperienceAggregate;
use App\Enums\LanguageLevel;
use App\Enums\ProjectDeliveryStatus;
use App\Enums\ProjectKind;
use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use App\Enums\WorkMode;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Technology;
use App\Models\WorkCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Phase 6 draft content (spec 2026-09-14-phase-6-portfolio-redesign-design.md §9).
 *
 * Every row is created as draft, hidden, unpublished through the same domain
 * actions Filament uses, and nothing is ever overwritten: an existing key is
 * skipped, a work case keeps a link someone already set, and the profile keeps
 * a location someone already typed. Publication is a human review step.
 *
 * Sources: approved CVs (docs/content/approved-assets/cv-*.pdf), the LinkedIn
 * role names and dates recorded as decision D27, and the public ReservaHub
 * README. Trucks and Drinks carries only its approved facts.
 *
 * Run explicitly, never from DatabaseSeeder::run():
 *   php artisan db:seed --class=PhaseSixDraftContentSeeder
 */
final class PhaseSixDraftContentSeeder extends Seeder
{
    private const DRAFT = ['status' => PublicationStatus::Draft, 'is_visible' => false, 'published_at' => null, 'key_locked' => false];

    /** Work cases approved in Phase 1 that describe the current Coned role. */
    private const CURRENT_ROLE_CASES = ['integrations-synchronization', 'education-management', 'data-and-automation', 'cross-layer-integration'];

    public function run(): void
    {
        $this->technologies();
        $this->experiences();
        $this->linkWorkCases();
        $this->projects();
        $this->education();
        $this->languages();
        $this->profile();
    }

    private function technologies(): void
    {
        $rows = [
            ['sanctum', 'Sanctum', TechnologyCategory::Backend],
            ['composer', 'Composer', TechnologyCategory::Backend],
            ['java', 'Java', TechnologyCategory::Backend],
            ['python', 'Python', TechnologyCategory::Backend],
            ['c', 'C', TechnologyCategory::Backend],
            ['github-actions', 'GitHub Actions', TechnologyCategory::Integration],
            ['phpunit', 'PHPUnit', TechnologyCategory::Integration],
            ['git', 'Git', TechnologyCategory::Integration],
            ['inertia', 'Inertia', TechnologyCategory::Collaboration],
            ['typescript', 'TypeScript', TechnologyCategory::Collaboration],
            ['javascript', 'JavaScript', TechnologyCategory::Collaboration],
            ['html5', 'HTML5', TechnologyCategory::Collaboration],
            ['css', 'CSS', TechnologyCategory::Collaboration],
            ['tailwindcss', 'TailwindCSS', TechnologyCategory::Collaboration],
            ['bootstrap', 'Bootstrap', TechnologyCategory::Collaboration],
        ];

        foreach ($rows as [$key, $name, $category]) {
            $this->createDraft(Technology::class, $key, ['name' => $name, 'category' => $category]);
        }
    }

    private function experiences(): void
    {
        $current = $this->createDraft(Experience::class, 'coned-backend-engineer', [
            'organization_label_es' => 'Coned', 'organization_label_en' => 'Coned',
            'role_es' => 'Backend Engineer', 'role_en' => 'Backend Engineer',
            'start_year' => 2025, 'start_month' => 9, 'end_year' => null, 'end_month' => null,
            'summary_es' => 'Desarrollo backend en PHP y Laravel sobre una plataforma de gestión educativa multiinstitución: APIs REST, lógica de negocio y módulos financieros y académicos.',
            'summary_en' => 'Backend development in PHP and Laravel on a multi-institution education management platform: REST APIs, business logic, and financial and academic modules.',
        ]);
        if ($current !== null) {
            app(UpdateExperienceAggregate::class)($current, [], $this->highlights([
                ['Integré los medios de pago SIRO, CES y Mercado Pago sobre Laravel, implementando sincronización de pagos, actualización automática de comprobantes y habilitación de alumnos según su estado de cuenta.', 'Integrated the SIRO, CES, and Mercado Pago payment gateways using Laravel, implementing payment synchronization, automatic receipt updates, and student account activation based on balance status.'],
                ['Desarrollé endpoints REST, validaciones y lógica de negocio en PHP/Laravel para módulos financieros: facturación, deudas, cuentas corrientes y comprobantes.', 'Developed REST endpoints, validations, and business logic in PHP/Laravel for financial modules: invoicing, outstanding debt, current accounts, and receipts.'],
                ['Mantengo y modernizo módulos académicos (mesas de examen, alumnos, materias, cursos, comisiones e inscripciones) dentro de una plataforma multiinstitución.', 'Maintain and modernize academic modules (exam boards, students, subjects, courses, class groups, and enrollments) within a multi-institution platform.'],
                ['Optimicé consultas SQL sobre MySQL y refactoricé código legacy, mejorando el rendimiento de procesos con alto volumen de datos.', 'Optimized SQL queries on MySQL and refactored legacy code, improving performance on high-volume data processes.'],
                ['Automaticé procesos internos mediante comandos Artisan y tareas programadas, e implementé control de acceso por roles y permisos.', 'Automated internal processes through Artisan commands and scheduled tasks, and implemented role- and permission-based access control.'],
                ['Integré endpoints backend con el frontend Angular y resolví incidencias de producción mediante debugging y análisis de errores.', 'Integrated backend endpoints with the Angular frontend and resolved production incidents through debugging and error analysis.'],
            ]), $this->technologyPivot(['php', 'laravel', 'mysql', 'rest-apis', 'angular']));
        }

        $intern = $this->createDraft(Experience::class, 'coned-intern', [
            'organization_label_es' => 'Coned', 'organization_label_en' => 'Coned',
            'role_es' => 'Pasante', 'role_en' => 'Intern',
            'start_year' => 2025, 'start_month' => 5, 'end_year' => 2025, 'end_month' => 9,
            'summary_es' => 'Pasantía como desarrollador full stack en productos internos de la plataforma educativa.',
            'summary_en' => 'Full stack developer internship on internal products of the education platform.',
        ]);
        if ($intern !== null) {
            app(UpdateExperienceAggregate::class)($intern, [], $this->highlights([
                ['Desarrollé una plataforma de onboarding para la creación y configuración de nuevas instituciones educativas, simplificando el proceso de alta en el sistema.', 'Built an onboarding platform for creating and configuring new educational institutions, streamlining the system setup process.'],
                ['Participé en la construcción de un sistema de soporte con gestión de tickets, CRM y estadísticas de uso de la aplicación.', 'Contributed to a support system featuring ticket management, CRM, and application usage statistics.'],
            ]), $this->technologyPivot(['php', 'laravel']));
        }
    }

    private function linkWorkCases(): void
    {
        $experience = Experience::query()->where('key', 'coned-backend-engineer')->first();
        if ($experience === null) {
            return;
        }

        WorkCase::query()->whereIn('key', self::CURRENT_ROLE_CASES)->whereNull('experience_id')->with('technologies')->get()
            ->each(function (WorkCase $workCase) use ($experience): void {
                $technologies = $workCase->technologies->values()->map(static fn (Technology $technology, int $position): array => [
                    'technology_id' => $technology->getKey(),
                    'position' => $position,
                ])->all();

                app(UpdateContentWithTechnologies::class)($workCase, ['experience_id' => $experience->getKey()], $technologies);
            });
    }

    private function projects(): void
    {
        $this->createDraft(Project::class, 'trucks-and-drinks', [
            'kind' => ProjectKind::Client, 'client_name' => 'Trucks and Drinks',
            'title_es' => 'Trucks and Drinks', 'title_en' => 'Trucks and Drinks',
            'role_es' => 'Backend', 'role_en' => 'Backend',
            'delivery_status' => ProjectDeliveryStatus::InUse,
        ]);

        $reservaHub = $this->createDraft(Project::class, 'reservahub', [
            'kind' => ProjectKind::Personal, 'client_name' => null,
            'title_es' => 'ReservaHub', 'title_en' => 'ReservaHub',
            'role_es' => 'Full stack', 'role_en' => 'Full stack',
            'delivery_status' => ProjectDeliveryStatus::PublicDemo,
            'summary_es' => 'SaaS de reservas por turnos multi-tenant.',
            'summary_en' => 'Multi-tenant appointment booking SaaS.',
            'problem_es' => 'Cualquier negocio que vende tiempo en franjas horarias necesita una regla simple y difícil de romper: que dos personas no puedan reservar el mismo turno.',
            'problem_en' => 'Any business that sells time in slots needs a simple rule that is hard to break: two people must never book the same slot.',
            'solution_es' => 'Motor de disponibilidad que combina horarios de empleados, pausas, licencias y feriados; la prevención de solapamientos se re-valida dentro de la transacción con advisory locks de PostgreSQL. Pasarela de pagos simulada con webhooks idempotentes y un comando de reconciliación, multi-tenancy por global scope y policies, tiempo real con Laravel Reverb y API REST con Sanctum.',
            'solution_en' => 'An availability engine that combines employee schedules, breaks, time off, and holidays; overlap prevention is re-validated inside the transaction with PostgreSQL advisory locks. A simulated payment gateway with idempotent webhooks and a reconciliation command, multi-tenancy through global scopes and policies, real-time updates with Laravel Reverb, and a REST API authenticated with Sanctum.',
            'result_es' => 'Demo pública y reiniciable, con suite de PHPUnit que incluye tests de concurrencia para reservas simultáneas y CI en GitHub Actions.',
            'result_en' => 'A public, resettable demo with a PHPUnit suite that includes concurrency tests for simultaneous bookings, and CI on GitHub Actions.',
            'demo_url' => 'https://reservahub.lucianogonzalez.dev',
            'repository_url' => 'https://github.com/Gonzalez-Luciano/ReservaHub',
        ]);
        if ($reservaHub !== null) {
            app(UpdateContentWithTechnologies::class)($reservaHub, [], $this->technologyPivot([
                'laravel', 'php', 'postgresql', 'redis', 'react', 'inertia', 'docker', 'phpunit', 'github-actions',
            ]));
        }
    }

    private function education(): void
    {
        $this->createDraft(EducationEntry::class, 'instituto-argentino-modelo', [
            'institution' => 'Instituto Argentino Modelo',
            'program_es' => 'Bachiller en Economía y Administración',
            'program_en' => 'High School Diploma in Economics and Administration',
            'end_year' => 2021,
        ]);
        $this->createDraft(EducationEntry::class, 'cfp-401', [
            'institution' => 'CFP N°401',
            'program_es' => 'Curso de Programación',
            'program_en' => 'Programming Course',
            'detail_es' => 'Introducción a C, fundamentos de Python y MySQL',
            'detail_en' => 'Introduction to C, Python and MySQL fundamentals',
            'start_year' => 2022,
            'end_year' => 2022,
        ]);
    }

    private function languages(): void
    {
        $this->createDraft(Language::class, 'spanish', ['name_es' => 'Español', 'name_en' => 'Spanish', 'level' => LanguageLevel::Native]);
        $this->createDraft(Language::class, 'english', ['name_es' => 'Inglés', 'name_en' => 'English', 'level' => LanguageLevel::B2]);
    }

    private function profile(): void
    {
        $profile = Profile::query()->where('singleton_key', 'default')->firstOrFail();
        if ($profile->location !== null) {
            return;
        }

        app(UpdateContent::class)($profile, [
            'location' => 'Mar del Plata, Argentina',
            'work_modes' => array_map(static fn (WorkMode $mode): string => $mode->value, WorkMode::cases()),
        ]);
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel|null the new draft, or null when the key already exists
     */
    private function createDraft(string $model, string $key, array $attributes): ?Model
    {
        if ($model::query()->where('key', $key)->exists()) {
            return null;
        }

        return $model::query()->create([
            ...$attributes,
            ...self::DRAFT,
            'key' => $key,
            'position' => (int) ($model::query()->max('position') ?? -1) + 1,
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pairs
     * @return list<array{content_es: string, content_en: string, position: int}>
     */
    private function highlights(array $pairs): array
    {
        return array_map(
            static fn (array $pair, int $position): array => ['content_es' => $pair[0], 'content_en' => $pair[1], 'position' => $position],
            $pairs,
            array_keys($pairs),
        );
    }

    /**
     * Existing technologies only, in the given order.
     *
     * @param  list<string>  $keys
     * @return list<array{technology_id: int, position: int}>
     */
    private function technologyPivot(array $keys): array
    {
        $ids = Technology::query()->whereIn('key', $keys)->pluck('id', 'key');

        return array_values(array_map(
            static fn (string $key, int $position): array => ['technology_id' => (int) $ids[$key], 'position' => $position],
            array_values(array_filter($keys, static fn (string $key): bool => $ids->has($key))),
            array_keys(array_values(array_filter($keys, static fn (string $key): bool => $ids->has($key)))),
        ));
    }
}
