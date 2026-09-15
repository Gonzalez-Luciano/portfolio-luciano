<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One ordered screenshot of a Project. Rows are only ever written through
 * ProjectGalleryService or AssetLifecycleService (EditorialMutationGuard
 * rejects any change outside an EditorialMutationContext).
 */
final class ProjectImage extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'position', 'private_path', 'public_path', 'mime', 'size', 'alt_es', 'alt_en'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'size' => 'integer'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
