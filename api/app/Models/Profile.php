<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Profile extends Model
{
    use HasFactory;

    protected $fillable = ['singleton_key', 'name', 'headline_es', 'headline_en', 'short_summary_es', 'short_summary_en', 'introduction_es', 'introduction_en', 'availability_es', 'availability_en', 'statement_lead_es', 'statement_lead_en', 'statement_emphasis_es', 'statement_emphasis_en', 'statement_tail_es', 'statement_tail_en', 'closing_line_one_es', 'closing_line_one_en', 'closing_line_two_es', 'closing_line_two_en', 'cta_es', 'cta_en', 'photo_private_path', 'photo_public_path', 'photo_mime', 'photo_size', 'photo_alt_es', 'photo_alt_en', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime', 'photo_size' => 'integer'];
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true);
    }
}
