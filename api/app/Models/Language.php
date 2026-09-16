<?php

namespace App\Models;

use App\Enums\LanguageLevel;
use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Language extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'key_locked', 'position', 'name_es', 'name_en', 'level', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['key_locked' => 'boolean', 'position' => 'integer', 'level' => LanguageLevel::class, 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true)->orderBy('position')->orderBy('key');
    }
}
