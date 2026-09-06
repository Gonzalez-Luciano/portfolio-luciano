<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Technology extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'key_locked', 'position', 'name', 'category', 'icon_private_path', 'icon_public_path', 'icon_mime', 'icon_size', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['key_locked' => 'boolean', 'position' => 'integer', 'category' => TechnologyCategory::class, 'icon_size' => 'integer', 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function experiences(): BelongsToMany
    {
        return $this->belongsToMany(Experience::class)->withPivot('position');
    }

    public function workCases(): BelongsToMany
    {
        return $this->belongsToMany(WorkCase::class)->withPivot('position');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withPivot('position');
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true)->orderBy('position')->orderBy('key');
    }
}
