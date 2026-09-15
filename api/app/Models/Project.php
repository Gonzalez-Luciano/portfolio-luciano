<?php

namespace App\Models;

use App\Enums\ProjectDeliveryStatus;
use App\Enums\ProjectKind;
use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Project extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'key_locked', 'position', 'kind', 'client_name', 'title_es', 'title_en', 'role_es', 'role_en', 'delivery_status', 'summary_es', 'summary_en', 'problem_es', 'problem_en', 'solution_es', 'solution_en', 'result_es', 'result_en', 'featured', 'demo_url', 'repository_url', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['key_locked' => 'boolean', 'position' => 'integer', 'kind' => ProjectKind::class, 'delivery_status' => ProjectDeliveryStatus::class, 'featured' => 'boolean', 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class)->withPivot('position')->orderByPivot('position')->orderBy('technologies.key');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('position')->orderBy('id');
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true)->orderBy('position')->orderBy('key');
    }
}
