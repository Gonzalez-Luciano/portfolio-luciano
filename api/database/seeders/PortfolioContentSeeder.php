<?php

namespace Database\Seeders;

use App\Domain\Content\InitialPortfolioContent;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * Loads approved Phase 1/2 public portfolio content as draft, hidden,
 * unpublished rows for development and editorial review inside the admin panel.
 *
 * The approved bilingual literals are no longer defined here: they live once in
 * {@see InitialPortfolioContent}, which also cites every
 * source and explains every null. This seeder consumes only the NON-ASSET
 * slice of that dataset (profile, site, professional links, technologies,
 * expertise areas, work cases, work principles). The dataset's `assets`,
 * `experiences`, and `projects` slices are intentionally ignored here.
 * Production and this development seeder therefore cannot drift apart.
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
        $data = InitialPortfolioContent::data();

        $this->seedSingleton(Profile::class, $data['profile']);
        $this->seedSingleton(SiteConfiguration::class, $data['site']);
        $this->seedKeyed(ProfessionalLink::class, 'type', $data['professional_links']);
        $this->seedKeyed(Technology::class, 'key', $data['technologies']);
        $this->seedKeyed(ExpertiseArea::class, 'key', $data['expertise_areas']);
        $this->seedKeyed(WorkCase::class, 'key', $data['work_cases']);
        $this->seedKeyed(WorkPrinciple::class, 'key', $data['work_principles']);
    }

    /**
     * Fill the existing structural singleton row, matched on `singleton_key`,
     * without ever creating a second row.
     *
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $row
     */
    private function seedSingleton(string $model, array $row): void
    {
        $model::query()->updateOrCreate(
            ['singleton_key' => $row['singleton_key']],
            Arr::except($row, ['singleton_key']),
        );
    }

    /**
     * Idempotently upsert each row, matched on its approved stable identifier
     * column ($match), so re-running never deletes or duplicates rows.
     *
     * @param  class-string<Model>  $model
     * @param  list<array<string, mixed>>  $rows
     */
    private function seedKeyed(string $model, string $match, array $rows): void
    {
        foreach ($rows as $row) {
            $model::query()->updateOrCreate(
                [$match => $row[$match]],
                Arr::except($row, [$match]),
            );
        }
    }
}
