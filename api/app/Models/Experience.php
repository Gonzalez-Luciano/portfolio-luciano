<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Experience extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'key_locked', 'position', 'role_es', 'role_en', 'summary_es', 'summary_en', 'organization_label_es', 'organization_label_en', 'start_year', 'start_month', 'end_year', 'end_month', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['key_locked' => 'boolean', 'position' => 'integer', 'start_year' => 'integer', 'start_month' => 'integer', 'end_year' => 'integer', 'end_month' => 'integer', 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(ExperienceHighlight::class)->orderBy('position')->orderBy('id');
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class)->withPivot('position')->orderByPivot('position')->orderBy('technologies.key');
    }

    public function isCurrent(): bool
    {
        return $this->end_year === null && $this->end_month === null;
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true)->orderBy('position')->orderBy('key');
    }
}
