<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EducationEntry extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'key_locked', 'position', 'institution', 'program_es', 'program_en', 'detail_es', 'detail_en', 'start_year', 'end_year', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['key_locked' => 'boolean', 'position' => 'integer', 'start_year' => 'integer', 'end_year' => 'integer', 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true)->orderBy('position')->orderBy('key');
    }
}
