<?php

namespace Tests\Feature\Domain;

use App\Domain\Content\Actions\ChangePublicKey;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ReorderContent;
use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Models\CvDocument;
use App\Models\Project;
use App\Support\PublicContentCache;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

final class EditorialMutationGuardTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_rejects_a_direct_publication_state_transition(): void
    {
        $project = Project::factory()->draft()->create();
        $project->forceFill([
            'status' => PublicationStatus::Published,
            'published_at' => now(),
            'key_locked' => true,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Sensitive editorial mutation');

        $project->save();
    }

    public function test_it_rejects_direct_creation_of_already_published_content(): void
    {
        $project = Project::factory()->publishedHidden()->make();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must be created as a draft');

        $project->save();
    }

    public function test_publish_rechecks_the_locked_state_before_performing_its_transition(): void
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
        $staleDraft = $project->fresh();

        DB::table('projects')->where('id', $project->id)->update([
            'status' => PublicationStatus::Published->value,
            'published_at' => now(),
            'key_locked' => true,
            'updated_at' => now(),
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only draft content can be published');

        app(PublishContent::class)($staleDraft);
    }

    public function test_it_rejects_a_direct_key_change_and_direct_delete(): void
    {
        $project = $this->publishedProject();
        $project->key = 'different-key';

        try {
            $project->save();
            $this->fail('A locked public key cannot be changed outside its action.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('Sensitive editorial mutation', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        $project->delete();
    }

    public function test_a_published_hidden_record_cannot_be_saved_with_incomplete_content(): void
    {
        $project = $this->publishedProject();
        $project->title_en = ' ';

        $this->expectException(PublicationValidationException::class);
        $project->save();
    }

    public function test_update_content_keeps_the_key_stable_and_invalidates_both_locales_after_commit(): void
    {
        $project = Project::factory()->draft()->create(['title_es' => 'Antes']);
        $cache = app(PublicContentCache::class);
        Cache::forever($cache->key(SupportedLocale::Spanish, PublicEndpoint::Projects), ['old-es']);
        Cache::forever($cache->key(SupportedLocale::English, PublicEndpoint::Projects), ['old-en']);

        $updated = app(UpdateContent::class)($project, ['title_es' => 'Después']);

        $this->assertSame('Después', $updated->title_es);
        $this->assertSame($project->key, $updated->key);
        $this->assertNull(Cache::get($cache->key(SupportedLocale::Spanish, PublicEndpoint::Projects)));
        $this->assertNull(Cache::get($cache->key(SupportedLocale::English, PublicEndpoint::Projects)));
    }

    public function test_update_content_rejects_a_cv_locale_change(): void
    {
        $cv = CvDocument::factory()->draft()->create();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('locale is immutable');

        app(UpdateContent::class)($cv, ['locale' => SupportedLocale::Spanish]);
    }

    public function test_a_key_change_is_explicitly_confirmed_and_validated_even_after_the_key_is_locked(): void
    {
        $project = $this->publishedProject();

        $this->expectException(LogicException::class);
        app(ChangePublicKey::class)($project, 'new-key', false);
    }

    public function test_key_change_rechecks_the_locked_record_before_accepting_confirmation(): void
    {
        $project = Project::factory()->draft()->create();
        $staleUnlocked = $project->fresh();

        DB::table('projects')->where('id', $project->id)->update([
            'status' => PublicationStatus::Published->value,
            'published_at' => now(),
            'key_locked' => true,
            'updated_at' => now(),
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('requires explicit confirmation');

        app(ChangePublicKey::class)($staleUnlocked, 'renamed-project', false);
    }

    public function test_a_confirmed_key_change_invalidates_the_owner_endpoint_for_both_locales(): void
    {
        $project = $this->publishedProject();
        $cache = app(PublicContentCache::class);
        Cache::forever($cache->key(SupportedLocale::Spanish, PublicEndpoint::Projects), ['old-es']);
        Cache::forever($cache->key(SupportedLocale::English, PublicEndpoint::Projects), ['old-en']);

        $renamed = app(ChangePublicKey::class)($project, 'renamed-project', true);

        $this->assertSame('renamed-project', $renamed->key);
        $this->assertNull(Cache::get($cache->key(SupportedLocale::Spanish, PublicEndpoint::Projects)));
        $this->assertNull(Cache::get($cache->key(SupportedLocale::English, PublicEndpoint::Projects)));
    }

    public function test_a_key_change_rejects_invalid_or_duplicate_slugs_before_writing(): void
    {
        $project = Project::factory()->draft()->create(['key' => 'first-project']);
        Project::factory()->draft()->create(['key' => 'taken-project']);

        try {
            app(ChangePublicKey::class)($project, 'Not a slug', true);
            $this->fail('An invalid slug must not be saved.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('invalid_key', $exception->issues()[0]->code);
        }

        $this->expectException(PublicationValidationException::class);
        app(ChangePublicKey::class)($project, 'taken-project', true);
    }

    public function test_it_rejects_direct_owned_asset_reference_changes(): void
    {
        $project = Project::factory()->draft()->create();
        $project->forceFill([
            'image_private_path' => 'projects/private.webp',
            'image_mime' => 'image/webp',
            'image_size' => 12,
        ]);

        $this->expectException(LogicException::class);
        $project->save();
    }

    public function test_reorder_updates_only_position_inside_its_action(): void
    {
        $project = Project::factory()->draft()->create(['position' => 0]);

        $reordered = app(ReorderContent::class)($project, 12);

        $this->assertSame(12, $reordered->position);
    }

    private function publishedProject(): Project
    {
        $project = Project::factory()->draft()->create([
            'title_es' => 'Proyecto técnico sintético',
            'title_en' => 'Synthetic technical project',
            'summary_es' => 'Resumen técnico sintético.',
            'summary_en' => 'Synthetic technical summary.',
            'problem_es' => 'Problema técnico sintético.',
            'problem_en' => 'Synthetic technical problem.',
            'solution_es' => 'Solución técnica sintética.',
            'solution_en' => 'Synthetic technical solution.',
            'role_es' => 'Rol técnico sintético', 'role_en' => 'Synthetic technical role',
            'result_es' => 'Resultado técnico sintético.', 'result_en' => 'Synthetic technical result.',
            'delivery_status' => 'in_development',
        ]);

        return app(PublishContent::class)($project);
    }
}
