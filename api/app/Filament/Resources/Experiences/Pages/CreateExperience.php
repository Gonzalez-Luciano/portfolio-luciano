<?php

namespace App\Filament\Resources\Experiences\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Experiences\ExperienceResource;
use App\Models\Experience;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateExperience extends CreateRecord
{
    protected static string $resource = ExperienceResource::class;

    /**
     * Plain Eloquent create is permitted here: the editorial mutation guard
     * only forbids creating already-published content, and a brand-new
     * record has no sensitive editorial state to protect yet. The `key`
     * identity is chosen once here; it changes afterward only through the
     * dedicated "Change key" action.
     *
     * Highlights and technologies are hidden (and not dehydrated) on the
     * create form since the aggregate action they are committed through
     * requires an existing Experience row; they are always defensively
     * stripped here too, and are managed afterward on the edit page.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        unset($data['highlights'], $data['technologies']);
        $data['status'] = PublicationStatus::Draft;
        $data['is_visible'] = false;
        $data['published_at'] = null;
        $data['key_locked'] = false;
        $data['position'] = (int) (Experience::query()->max('position') ?? -1) + 1;

        return Experience::query()->create($data);
    }
}
