<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\SupportedLocale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CvDocument extends Model
{
    use HasFactory;

    protected $fillable = ['locale', 'label', 'private_path', 'mime', 'size', 'status', 'is_visible', 'published_at'];

    protected function casts(): array
    {
        return ['locale' => SupportedLocale::class, 'size' => 'integer', 'status' => PublicationStatus::class, 'is_visible' => 'boolean', 'published_at' => 'datetime'];
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published)->where('is_visible', true);
    }
}
