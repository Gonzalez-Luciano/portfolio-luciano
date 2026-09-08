<?php

namespace App\Domain\Assets;

use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationValidator;
use App\Enums\PublicationStatus;
use App\Models\CvDocument;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Support\PublicContentCache;
use App\Support\PublicContentDependencies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AssetLifecycleService
{
    private const CLEANUP_ATTEMPTS = 3;

    public function __construct(
        private readonly EditorialMutationContext $context,
        private readonly PublicationValidator $validator,
        private readonly PublicContentCache $cache,
        private readonly PublicContentDependencies $dependencies,
    ) {}

    public function stage(Model $owner, UploadedFile $upload): StagedAsset
    {
        $definition = $this->definition($owner);
        $mime = (string) $upload->getMimeType();
        $size = (int) $upload->getSize();
        $extension = strtolower($upload->getClientOriginalExtension());

        if (! in_array($mime, $definition['mimes'], true) || $size < 1 || $size > $definition['maximum'] || ($definition['cv'] && $extension !== 'pdf')) {
            throw new AssetValidationException('The uploaded file does not satisfy the owned-asset policy.');
        }

        $generatedExtension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        };
        $path = $definition['namespace'].'/'.Str::uuid().'.'.$generatedExtension;

        if (! Storage::disk('local')->putFileAs($definition['namespace'], $upload, basename($path))) {
            throw new AssetOperationException('The private original could not be stored.');
        }

        return new StagedAsset($path, $mime, $size);
    }

    public function replace(Model $owner, UploadedFile $upload): Model
    {
        $operationId = (string) Str::uuid();
        $staged = $this->stage($owner, $upload);
        $definition = $this->definition($owner);
        $old = $this->snapshot($owner, $definition);
        $newPublicPath = $definition['public'] && $this->isVisible($owner)
            ? $definition['publicNamespace'].'/'.Str::uuid().'.'.pathinfo($staged->privatePath, PATHINFO_EXTENSION)
            : null;

        try {
            $updated = DB::transaction(function () use ($owner, $definition, $staged, $newPublicPath): Model {
                $locked = $this->locked($owner);

                return $this->context->run(function () use ($locked, $definition, $staged, $newPublicPath): Model {
                    $this->fillAsset($locked, $definition, $staged, $newPublicPath);
                    $locked->save();

                    return $locked->fresh();
                });
            });
        } catch (\Throwable $exception) {
            $this->deletePrivate($staged->privatePath, $owner, $operationId, 'replace_stage_compensation');

            throw $this->operationFailure('replace', $owner, $operationId, $exception);
        }

        if ($newPublicPath !== null) {
            try {
                $this->copyPublic($staged->privatePath, $newPublicPath);
            } catch (\Throwable $exception) {
                $this->restoreSnapshot($updated, $definition, $old);
                $this->deletePublic($newPublicPath, $owner, $operationId, 'replace_public_compensation');
                $this->deletePrivate($staged->privatePath, $owner, $operationId, 'replace_stage_compensation');
                $this->cache->invalidate($this->dependencies->for($owner));

                throw $this->operationFailure('replace', $owner, $operationId, $exception);
            }
        }

        $this->cache->invalidate($this->dependencies->for($owner));
        $this->deletePublic($old['public'], $owner, $operationId, 'replace_old_public_cleanup');
        try {
            if ($old['private'] !== null) {
                $this->removePrivateOrFail($old['private']);
            }
        } catch (\Throwable $exception) {
            throw $this->operationFailure('replace_old_private_cleanup', $owner, $operationId, $exception);
        }

        return $updated->fresh();
    }

    public function show(Model $owner): Model
    {
        if ($owner->getAttribute('status') !== PublicationStatus::Published || (bool) $owner->getAttribute('is_visible')) {
            throw new AssetOperationException('Only published hidden content can be shown.');
        }

        $definition = $this->definition($owner, false);
        if ($definition === null) {
            return $this->changeState($owner, PublicationStatus::Published, true, false);
        }

        $private = (string) $owner->getAttribute($definition['private']);
        if ($private !== '' && ! Storage::disk('local')->exists($private)) {
            throw new AssetOperationException('The private original is unavailable.');
        }
        $this->validator->assertPublishable($owner);

        if ($private === '') {
            return $this->changeState($owner, PublicationStatus::Published, true, false);
        }

        if (! $definition['public']) {
            return $this->changeState($owner, PublicationStatus::Published, true, false);
        }

        $publicPath = $definition['publicNamespace'].'/'.Str::uuid().'.'.pathinfo($private, PATHINFO_EXTENSION);
        $updated = DB::transaction(function () use ($owner, $definition, $publicPath): Model {
            $locked = $this->locked($owner);

            return $this->context->run(function () use ($locked, $definition, $publicPath): Model {
                $locked->forceFill(['is_visible' => true, $definition['publicPath'] => $publicPath])->save();

                return $locked->fresh();
            });
        });

        try {
            $this->copyPublic($private, $publicPath);
        } catch (\Throwable $exception) {
            $this->restoreSnapshot($updated, $definition, ['private' => $private, 'public' => null, 'mime' => $owner->getAttribute($definition['mime']), 'size' => $owner->getAttribute($definition['size'])], false);
            $this->cache->invalidate($this->dependencies->for($owner));

            throw $this->operationFailure('show', $owner, (string) Str::uuid(), $exception);
        }

        $this->cache->invalidate($this->dependencies->for($owner));

        return $updated->fresh();
    }

    public function hide(Model $owner): Model
    {
        return $this->reduce($owner, PublicationStatus::Published, false, false);
    }

    public function returnToDraft(Model $owner): Model
    {
        return $this->reduce($owner, PublicationStatus::Draft, false, false);
    }

    public function remove(Model $owner): Model
    {
        $definition = $this->definition($owner);
        $old = $this->snapshot($owner, $definition);
        if ($old['private'] === null) {
            return $owner;
        }

        if ($old['public'] !== null) {
            return $this->reduce($owner, null, null, true);
        }

        $updated = DB::transaction(function () use ($owner, $definition): Model {
            $locked = $this->locked($owner);

            return $this->context->run(function () use ($locked, $definition): Model {
                $this->clearAsset($locked, $definition);
                $locked->save();

                return $locked->fresh();
            });
        });
        try {
            $this->removePrivateOrFail($old['private']);
        } catch (\Throwable $exception) {
            $operationId = (string) Str::uuid();
            $this->restoreSnapshot($updated, $definition, $old);
            $this->cache->invalidate($this->dependencies->for($owner));

            throw $this->operationFailure('remove_private_cleanup', $owner, $operationId, $exception);
        }
        $this->cache->invalidate($this->dependencies->for($owner));

        return $updated;
    }

    public function delete(Model $owner): void
    {
        if ($owner instanceof Profile || $owner instanceof SiteConfiguration) {
            throw new AssetOperationException('Singleton content cannot be deleted.');
        }

        $this->reduce($owner, null, null, false, true);
    }

    private function reduce(Model $owner, ?PublicationStatus $status, ?bool $visible, bool $clearAsset = false, bool $delete = false): Model
    {
        $definition = $this->definition($owner, false);
        $old = $definition === null ? null : $this->snapshot($owner, $definition);
        $operationId = (string) Str::uuid();
        $endpoints = $this->dependencies->for($owner);

        return $this->cache->withMutationLocks($endpoints, function () use ($owner, $status, $visible, $clearAsset, $delete, $definition, $old, $operationId, $endpoints): Model {
            if ($old !== null) {
                $this->deletePublic($old['public'], $owner, $operationId, 'withdraw_public_copy');
            }
            $this->cache->invalidate($endpoints);

            $mutationPersisted = false;

            try {
                $result = DB::transaction(function () use ($owner, $status, $visible, $clearAsset, $delete, $definition, &$mutationPersisted): Model {
                    $locked = $this->locked($owner);

                    return $this->context->run(function () use ($locked, $status, $visible, $clearAsset, $delete, $definition, &$mutationPersisted): Model {
                        if ($delete) {
                            $locked->delete();
                            $mutationPersisted = true;

                            return $locked;
                        }
                        $attributes = [];
                        if ($status !== null) {
                            $attributes['status'] = $status;
                            $attributes['published_at'] = $status === PublicationStatus::Draft ? null : $locked->published_at;
                        }
                        if ($visible !== null) {
                            $attributes['is_visible'] = $visible;
                        }
                        if ($definition !== null && ($old = $this->snapshot($locked, $definition))['public'] !== null) {
                            $attributes[$definition['publicPath']] = null;
                        }
                        if ($clearAsset && $definition !== null) {
                            $this->clearAsset($locked, $definition);
                        }
                        $locked->forceFill($attributes)->save();
                        $mutationPersisted = true;

                        return $locked->fresh();
                    });
                });
            } catch (\Throwable $exception) {
                if (! $mutationPersisted && $old !== null && $old['public'] !== null && $old['private'] !== null) {
                    try {
                        $this->copyPublic($old['private'], $old['public']);
                    } catch (\Throwable $restoreException) {
                        $this->log($owner, $operationId, 'restore_public_copy_failed');
                    }
                }
                try {
                    $this->cache->invalidate($endpoints);
                } catch (\Throwable) {
                    $this->log($owner, $operationId, 'cache_invalidation_after_failed_mutation');
                }

                throw $this->operationFailure('visibility_reduction', $owner, $operationId, $exception);
            }

            try {
                $this->cache->invalidate($endpoints);
            } catch (\Throwable $exception) {
                throw $this->operationFailure('post_commit_cache_invalidation', $owner, $operationId, $exception);
            }
            if ($delete && $old !== null) {
                try {
                    if ($old['private'] !== null) {
                        $this->removePrivateOrFail($old['private']);
                    }
                } catch (\Throwable $exception) {
                    throw $this->operationFailure('delete_private_cleanup', $owner, $operationId, $exception);
                }
            }
            if ($clearAsset && $old !== null) {
                try {
                    if ($old['private'] !== null) {
                        $this->removePrivateOrFail($old['private']);
                    }
                } catch (\Throwable $exception) {
                    throw $this->operationFailure('remove_private_cleanup', $owner, $operationId, $exception);
                }
            }

            return $result;
        });
    }

    private function changeState(Model $owner, PublicationStatus $status, bool $visible, bool $clearPublishedAt): Model
    {
        return DB::transaction(function () use ($owner, $status, $visible, $clearPublishedAt): Model {
            $locked = $this->locked($owner);

            return $this->context->run(function () use ($locked, $status, $visible, $clearPublishedAt): Model {
                $locked->forceFill([
                    'status' => $status,
                    'is_visible' => $visible,
                    'published_at' => $clearPublishedAt ? null : $locked->published_at,
                ])->save();

                return $locked->fresh();
            });
        });
    }

    private function copyPublic(string $privatePath, string $publicPath): void
    {
        $contents = Storage::disk('local')->get($privatePath);
        if (! Storage::disk('public')->put($publicPath, $contents) || ! Storage::disk('public')->exists($publicPath)) {
            throw new AssetOperationException('The public copy could not be verified.');
        }
    }

    /** @return array{private:?string,public:?string,mime:mixed,size:mixed} */
    private function snapshot(Model $owner, array $definition): array
    {
        return [
            'private' => $owner->getAttribute($definition['private']),
            'public' => $definition['public'] ? $owner->getAttribute($definition['publicPath']) : null,
            'mime' => $owner->getAttribute($definition['mime']),
            'size' => $owner->getAttribute($definition['size']),
        ];
    }

    private function restoreSnapshot(Model $owner, array $definition, array $snapshot, bool $restorePublicPath = true): void
    {
        DB::transaction(function () use ($owner, $definition, $snapshot, $restorePublicPath): void {
            $locked = $this->locked($owner);
            $this->context->run(function () use ($locked, $definition, $snapshot, $restorePublicPath): void {
                $locked->forceFill([
                    $definition['private'] => $snapshot['private'],
                    $definition['mime'] => $snapshot['mime'],
                    $definition['size'] => $snapshot['size'],
                    $definition['publicPath'] => $restorePublicPath ? $snapshot['public'] : null,
                    'is_visible' => $restorePublicPath ? $locked->is_visible : false,
                ])->save();
            });
        });
    }

    private function fillAsset(Model $owner, array $definition, StagedAsset $staged, ?string $publicPath): void
    {
        $owner->forceFill([
            $definition['private'] => $staged->privatePath,
            $definition['mime'] => $staged->mime,
            $definition['size'] => $staged->size,
            ...($definition['public'] ? [$definition['publicPath'] => $publicPath] : []),
        ]);
    }

    private function clearAsset(Model $owner, array $definition): void
    {
        $owner->forceFill([
            $definition['private'] => null,
            $definition['mime'] => null,
            $definition['size'] => null,
            ...($definition['public'] ? [$definition['publicPath'] => null] : []),
        ]);
    }

    private function locked(Model $owner): Model
    {
        $class = $owner::class;

        return $class::query()->lockForUpdate()->findOrFail($owner->getKey());
    }

    private function isVisible(Model $owner): bool
    {
        return $owner->getAttribute('status') === PublicationStatus::Published && (bool) $owner->getAttribute('is_visible');
    }

    private function deletePrivate(?string $path, Model $owner, string $operationId, string $operation): void
    {
        if ($path === null) {
            return;
        }
        try {
            Storage::disk('local')->delete($path);
        } catch (\Throwable) {
            $this->log($owner, $operationId, $operation);
        }
    }

    private function removePrivateOrFail(string $path): void
    {
        for ($attempt = 1; $attempt <= self::CLEANUP_ATTEMPTS; $attempt++) {
            Storage::disk('local')->delete($path);
            if (! Storage::disk('local')->exists($path)) {
                return;
            }
        }

        throw new AssetOperationException('The private original could not be removed.');
    }

    private function deletePublic(?string $path, Model $owner, string $operationId, string $operation): void
    {
        if ($path === null) {
            return;
        }
        try {
            Storage::disk('public')->delete($path);
            if (Storage::disk('public')->exists($path)) {
                throw new AssetOperationException('The public copy could not be withdrawn.');
            }
        } catch (\Throwable $exception) {
            $this->log($owner, $operationId, $operation);
            throw $exception;
        }
    }

    private function operationFailure(string $operation, Model $owner, string $operationId, \Throwable $previous): AssetOperationException
    {
        $this->log($owner, $operationId, $operation);

        return new AssetOperationException('The asset operation could not be completed.', 0, $previous);
    }

    private function log(Model $owner, string $operationId, string $operation): void
    {
        Log::warning('Owned asset lifecycle operation failed.', [
            'operation_id' => $operationId,
            'entity_type' => $owner::class,
            'entity_id' => $owner->getKey(),
            'operation' => $operation,
        ]);
    }

    /** @return array{namespace:string,publicNamespace:string,private:string,publicPath:string,mime:string,size:string,mimes:list<string>,maximum:int,public:bool,cv:bool}|null */
    private function definition(Model $owner, bool $required = true): ?array
    {
        $definition = match ($owner::class) {
            Profile::class => ['namespace' => 'profiles', 'publicNamespace' => 'profiles', 'private' => 'photo_private_path', 'publicPath' => 'photo_public_path', 'mime' => 'photo_mime', 'size' => 'photo_size', 'mimes' => ['image/jpeg', 'image/png', 'image/webp'], 'maximum' => 5 * 1024 * 1024, 'public' => true, 'cv' => false],
            Project::class => ['namespace' => 'projects', 'publicNamespace' => 'projects', 'private' => 'image_private_path', 'publicPath' => 'image_public_path', 'mime' => 'image_mime', 'size' => 'image_size', 'mimes' => ['image/jpeg', 'image/png', 'image/webp'], 'maximum' => 8 * 1024 * 1024, 'public' => true, 'cv' => false],
            Technology::class => ['namespace' => 'technologies', 'publicNamespace' => 'technologies', 'private' => 'icon_private_path', 'publicPath' => 'icon_public_path', 'mime' => 'icon_mime', 'size' => 'icon_size', 'mimes' => ['image/png', 'image/webp'], 'maximum' => 1024 * 1024, 'public' => true, 'cv' => false],
            CvDocument::class => ['namespace' => 'cv', 'publicNamespace' => '', 'private' => 'private_path', 'publicPath' => '', 'mime' => 'mime', 'size' => 'size', 'mimes' => ['application/pdf'], 'maximum' => 5 * 1024 * 1024, 'public' => false, 'cv' => true],
            default => null,
        };

        if ($definition === null && $required) {
            throw new AssetOperationException('The content type does not own an asset.');
        }

        return $definition;
    }
}
