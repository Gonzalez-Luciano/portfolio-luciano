<?php

namespace App\Domain\Assets;

final readonly class StagedAsset
{
    public function __construct(
        public string $privatePath,
        public string $mime,
        public int $size,
        public ?string $publicPath = null,
    ) {}
}
