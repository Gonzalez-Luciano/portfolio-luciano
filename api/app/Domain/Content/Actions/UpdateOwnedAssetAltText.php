<?php

namespace App\Domain\Content\Actions;

use App\Domain\Assets\AssetLifecycleService;
use Illuminate\Database\Eloquent\Model;

final class UpdateOwnedAssetAltText
{
    public function __construct(private readonly AssetLifecycleService $assets) {}

    public function __invoke(Model $content, ?string $altEs, ?string $altEn): Model
    {
        return $this->assets->updateAltText($content, $altEs, $altEn);
    }
}
