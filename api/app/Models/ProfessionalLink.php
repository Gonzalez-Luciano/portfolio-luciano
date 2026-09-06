<?php

namespace App\Models;

use App\Enums\ProfessionalLinkType;
use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProfessionalLink extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'position', 'destination', 'label_es', 'label_en', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['type' => ProfessionalLinkType::class, 'position' => 'integer', 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true)->orderBy('position')->orderBy('type');
    }
}
