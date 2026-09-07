<?php

namespace App\Domain\Content\Actions;

use App\Domain\Assets\AssetLifecycleService;
use Illuminate\Database\Eloquent\Model;

final class DeleteContent
{
    public function __construct(private readonly AssetLifecycleService $assets) {}

    public function __invoke(Model $content): void
    {
        $this->assets->delete($content);
    }
}
