<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SiteConfiguration extends Model
{
    use HasFactory;

    protected $fillable = ['singleton_key', 'projects_empty_message_es', 'projects_empty_message_en', 'contact_intro_es', 'contact_intro_en', 'technology_backend_label_es', 'technology_backend_label_en', 'technology_data_label_es', 'technology_data_label_en', 'technology_integration_label_es', 'technology_integration_label_en', 'technology_collaboration_label_es', 'technology_collaboration_label_en', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true);
    }
}
