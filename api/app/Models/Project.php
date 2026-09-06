<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Project extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'key_locked', 'position', 'title_es', 'title_en', 'summary_es', 'summary_en', 'problem_es', 'problem_en', 'solution_es', 'solution_en', 'featured', 'demo_url', 'repository_url', 'image_private_path', 'image_public_path', 'image_mime', 'image_size', 'image_alt_es', 'image_alt_en', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['key_locked' => 'boolean', 'position' => 'integer', 'featured' => 'boolean', 'image_size' => 'integer', 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class)->withPivot('position')->orderByPivot('position')->orderBy('technologies.key');
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true)->orderBy('position')->orderBy('key');
    }
}
