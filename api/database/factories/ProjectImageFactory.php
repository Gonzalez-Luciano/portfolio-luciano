<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProjectImage> */
final class ProjectImageFactory extends Factory
{
    protected $model = ProjectImage::class;

    public function definition(): array
    {
        return ['project_id' => Project::factory(), 'position' => 0, 'private_path' => 'projects/'.Str::uuid().'.png', 'public_path' => null, 'mime' => 'image/png', 'size' => 68, 'alt_es' => 'Captura técnica sintética', 'alt_en' => 'Synthetic technical screenshot'];
    }
}
