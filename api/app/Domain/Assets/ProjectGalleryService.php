<?php

namespace App\Domain\Assets;

use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationIssue;
use App\Domain\Publishing\PublicationValidator;
use App\Enums\PublicationStatus;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Support\PublicContentCache;
use App\Support\PublicContentDependencies;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Replaces a project's ordered screenshot gallery as one validated unit.
 *
 * Order of operations: validate the proposed list, stage new private
 * originals, validate publication rules against the proposed gallery (alt
 * text is therefore always checked together with its file), commit every row
 * change in one transaction, copy public files only for a published and
 * visible project, then retire replaced or removed files. A failed public copy
 * restores the previous rows and discards the staged uploads, so the old
 * gallery stays intact.
 */
final class ProjectGalleryService
{
    public function __construct(
        private readonly AssetLifecycleService $assets,
        private readonly EditorialMutationContext $context,
        private readonly PublicationValidator $validator,
        private readonly PublicContentCache $cache,
        private readonly PublicContentDependencies $dependencies,
    ) {}

    /**
     * @param  list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>  $items
     */
    public function sync(Project $project, array $items): Project
    {
        $items = array_values($items);
        /** @var Collection<int, ProjectImage> $existing */
        $existing = $project->images()->get();
        $this->assertItems($existing->keyBy('id'), $items);

        if ($this->unchanged($existing, $items)) {
            return $project->fresh('images');
        }

        $staged = $this->stageUploads($items);
        $visible = $project->status === PublicationStatus::Published && $project->is_visible;
        $plan = $this->plan($existing->keyBy('id'), $items, $staged, $visible);

        try {
            $this->assertPublishable($project, $plan);
        } catch (\Throwable $exception) {
            $this->deleteStaged($staged);

            throw $exception;
        }

        $keptIds = array_values(array_filter(array_column($plan, 'id')));
        $retired = $this->retiredFiles($existing, $plan);

        try {
            DB::transaction(function () use ($project, $plan, $keptIds): void {
                Project::query()->lockForUpdate()->findOrFail($project->getKey());

                $this->context->run(function () use ($project, $plan, $keptIds): void {
                    ProjectImage::query()->where('project_id', $project->getKey())->whereNotIn('id', $keptIds)->delete();

                    foreach ($plan as $row) {
                        $attributes = Arr::except($row, ['id']);
                        if ($row['id'] === null) {
                            $project->images()->create($attributes);
                        } else {
                            ProjectImage::query()->findOrFail($row['id'])->forceFill($attributes)->save();
                        }
                    }
                });
            });
        } catch (\Throwable $exception) {
            $this->deleteStaged($staged);

            throw new AssetOperationException('The project gallery could not be saved.', 0, $exception);
        }

        if ($visible) {
            $copied = [];
            try {
                foreach ($plan as $index => $row) {
                    if (isset($staged[$index])) {
                        $this->copyPublic($row['private_path'], (string) $row['public_path']);
                        $copied[] = (string) $row['public_path'];
                    }
                }
            } catch (\Throwable $exception) {
                foreach ($copied as $path) {
                    Storage::disk('public')->delete($path);
                }
                $this->restore($project, $existing);
                $this->deleteStaged($staged);
                $this->cache->invalidate($this->dependencies->for($project));

                throw new AssetOperationException('The project gallery could not be saved.', 0, $exception);
            }
        }

        $this->cache->invalidate($this->dependencies->for($project));
        $this->retire($project, $retired);

        return $project->fresh('images');
    }

    /**
     * @param  Collection<int, ProjectImage>  $byId
     * @param  list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>  $items
     */
    private function assertItems(Collection $byId, array $items): void
    {
        $issues = [];
        if (count($items) > PublicationValidator::PROJECT_IMAGE_LIMIT) {
            $issues[] = new PublicationIssue('too_many_images', 'images', 'A project can have at most 12 images.');
        }

        $seen = [];
        foreach ($items as $index => $item) {
            $id = $item['id'];
            if ($id !== null && (! $byId->has($id) || isset($seen[$id]))) {
                $issues[] = new PublicationIssue('invalid_relation', "images.{$index}.id", 'The image does not belong to this project.');
            }
            if ($id !== null) {
                $seen[$id] = true;
            }
            if ($id === null && ! $item['upload'] instanceof UploadedFile) {
                $issues[] = new PublicationIssue('asset_required', "images.{$index}.file", 'A new screenshot requires an image file.');
            }
            foreach (['alt_es', 'alt_en'] as $field) {
                if (is_string($item[$field]) && mb_strlen($item[$field]) > 500) {
                    $issues[] = new PublicationIssue('max_length_exceeded', "images.{$index}.{$field}", 'This field must not exceed 500 characters.');
                }
            }
        }

        $this->validator->assertIssues($issues);
    }

    /**
     * @param  Collection<int, ProjectImage>  $existing
     * @param  list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>  $items
     */
    private function unchanged(Collection $existing, array $items): bool
    {
        if (count($items) !== $existing->count()) {
            return false;
        }

        foreach ($existing->values() as $index => $image) {
            $item = $items[$index];
            if ($item['upload'] !== null
                || $item['id'] !== $image->getKey()
                || $this->blankToNull($item['alt_es']) !== $image->alt_es
                || $this->blankToNull($item['alt_en']) !== $image->alt_en) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>  $items
     * @return array<int, StagedAsset>
     */
    private function stageUploads(array $items): array
    {
        $staged = [];
        try {
            foreach ($items as $index => $item) {
                if ($item['upload'] instanceof UploadedFile) {
                    $staged[$index] = $this->assets->stage(new ProjectImage, $item['upload']);
                }
            }
        } catch (\Throwable $exception) {
            $this->deleteStaged($staged);

            throw $exception;
        }

        return $staged;
    }

    /**
     * @param  Collection<int, ProjectImage>  $byId
     * @param  list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>  $items
     * @param  array<int, StagedAsset>  $staged
     * @return list<array{id: ?int, position: int, private_path: string, public_path: ?string, mime: string, size: int, alt_es: ?string, alt_en: ?string}>
     */
    private function plan(Collection $byId, array $items, array $staged, bool $visible): array
    {
        $plan = [];
        foreach ($items as $index => $item) {
            $current = $item['id'] === null ? null : $byId->get($item['id']);
            $new = $staged[$index] ?? null;
            $privatePath = $new !== null ? $new->privatePath : $current->private_path;

            $plan[] = [
                'id' => $current?->getKey(),
                'position' => $index,
                'private_path' => $privatePath,
                'public_path' => match (true) {
                    ! $visible => null,
                    $new !== null => 'projects/'.Str::uuid().'.'.pathinfo($privatePath, PATHINFO_EXTENSION),
                    default => $current->public_path,
                },
                'mime' => $new !== null ? $new->mime : $current->mime,
                'size' => $new !== null ? $new->size : $current->size,
                'alt_es' => $this->blankToNull($item['alt_es']),
                'alt_en' => $this->blankToNull($item['alt_en']),
            ];
        }

        return $plan;
    }

    /** @param list<array<string, mixed>> $plan */
    private function assertPublishable(Project $project, array $plan): void
    {
        if ($project->status !== PublicationStatus::Published) {
            return;
        }

        $candidate = clone $project;
        $candidate->setRelation('images', new Collection(array_map(
            static fn (array $row): ProjectImage => (new ProjectImage)->forceFill(Arr::except($row, ['id'])),
            $plan,
        )));
        $this->validator->assertPublishable($candidate);
    }

    /**
     * @param  Collection<int, ProjectImage>  $existing
     * @param  list<array<string, mixed>>  $plan
     * @return list<array{private: string, public: ?string}>
     */
    private function retiredFiles(Collection $existing, array $plan): array
    {
        $rows = collect($plan)->keyBy('id');
        $retired = [];
        foreach ($existing as $image) {
            $row = $rows->get($image->getKey());
            if ($row === null || $row['private_path'] !== $image->private_path) {
                $retired[] = ['private' => $image->private_path, 'public' => $image->public_path];
            }
        }

        return $retired;
    }

    /** @param Collection<int, ProjectImage> $snapshot */
    private function restore(Project $project, Collection $snapshot): void
    {
        DB::transaction(function () use ($project, $snapshot): void {
            $this->context->run(function () use ($project, $snapshot): void {
                ProjectImage::query()->where('project_id', $project->getKey())->delete();
                if ($snapshot->isNotEmpty()) {
                    DB::table('project_images')->insert(
                        $snapshot->map(static fn (ProjectImage $image): array => $image->getRawOriginal())->values()->all(),
                    );
                }
            });
        });
    }

    /** @param list<array{private: string, public: ?string}> $files */
    private function retire(Project $project, array $files): void
    {
        $failed = false;
        foreach ($files as $file) {
            if ($file['public'] !== null) {
                Storage::disk('public')->delete($file['public']);
                $failed = $failed || Storage::disk('public')->exists($file['public']);
            }
            Storage::disk('local')->delete($file['private']);
            $failed = $failed || Storage::disk('local')->exists($file['private']);
        }

        if ($failed) {
            Log::warning('Project gallery file retirement failed.', ['entity_type' => Project::class, 'entity_id' => $project->getKey()]);

            throw new AssetOperationException('The gallery was saved, but a retired file could not be removed.');
        }
    }

    private function copyPublic(string $privatePath, string $publicPath): void
    {
        $contents = Storage::disk('local')->get($privatePath);
        if (! Storage::disk('public')->put($publicPath, $contents) || ! Storage::disk('public')->exists($publicPath)) {
            throw new AssetOperationException('The public copy could not be verified.');
        }
    }

    /** @param array<int, StagedAsset> $staged */
    private function deleteStaged(array $staged): void
    {
        foreach ($staged as $asset) {
            Storage::disk('local')->delete($asset->privatePath);
        }
    }

    private function blankToNull(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : $value;
    }
}
