<?php

namespace App\Domain\Assets;

use App\Models\Project;
use Throwable;

/**
 * Thrown by ProjectGalleryService::sync() when the gallery's row changes
 * (and any public copies) were already committed, but the post-commit
 * cleanup of a replaced or removed private file failed. Distinct from a
 * plain AssetOperationException so a caller can tell this apart from a real
 * save failure and treat the save as successful instead of reporting the
 * gallery as lost. Carries the already-saved project (with its images
 * loaded) so a caller does not need to re-fetch it to recover.
 */
final class GalleryRetireFailed extends AssetOperationException
{
    public function __construct(public readonly Project $project, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
