<?php

namespace App\Domain\Content\Actions;

use App\Domain\Assets\AssetLifecycleService;
use Illuminate\Database\Eloquent\Model;

final class ReturnContentToDraft
{
    public function __construct(private readonly AssetLifecycleService $assets) {}

    public function __invoke(Model $content): Model
    {
        return $this->assets->returnToDraft($content);
    }
}
