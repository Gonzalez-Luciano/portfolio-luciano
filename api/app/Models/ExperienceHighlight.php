<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ExperienceHighlight extends Model
{
    use HasFactory;

    protected $fillable = ['experience_id', 'content_es', 'content_en', 'position'];

    protected function casts(): array
    {
        return ['experience_id' => 'integer', 'position' => 'integer'];
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }
}
