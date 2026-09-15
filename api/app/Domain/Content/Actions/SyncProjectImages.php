<?php

namespace App\Domain\Content\Actions;

use App\Domain\Assets\ProjectGalleryService;
use App\Models\Project;
use Illuminate\Http\UploadedFile;

final class SyncProjectImages
{
    public function __construct(private readonly ProjectGalleryService $gallery) {}

    /** @param list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}> $items */
    public function __invoke(Project $project, array $items): Project
    {
        return $this->gallery->sync($project, $items);
    }
}
