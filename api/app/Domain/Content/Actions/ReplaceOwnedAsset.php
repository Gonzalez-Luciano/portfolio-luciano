<?php

namespace App\Domain\Content\Actions;

use App\Domain\Assets\AssetLifecycleService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class ReplaceOwnedAsset
{
    public function __construct(private readonly AssetLifecycleService $assets) {}

    public function __invoke(Model $content, UploadedFile $upload): Model
    {
        return $this->assets->replace($content, $upload);
    }
}
