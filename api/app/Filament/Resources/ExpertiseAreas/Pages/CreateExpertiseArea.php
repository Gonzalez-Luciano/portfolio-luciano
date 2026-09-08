<?php

namespace App\Filament\Resources\ExpertiseAreas\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\ExpertiseAreas\ExpertiseAreaResource;
use App\Models\ExpertiseArea;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateExpertiseArea extends CreateRecord
{
    protected static string $resource = ExpertiseAreaResource::class;

    /**
     * Plain Eloquent create is permitted here: the editorial mutation guard
     * only forbids creating already-published content, and a brand-new
     * record has no sensitive editorial state to protect yet. The `key`
     * identity is chosen once here; it changes afterward only through the
     * dedicated "Change key" action.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['status'] = PublicationStatus::Draft;
        $data['is_visible'] = false;
        $data['published_at'] = null;
        $data['key_locked'] = false;
        $data['position'] = (int) (ExpertiseArea::query()->max('position') ?? -1) + 1;

        return ExpertiseArea::query()->create($data);
    }
}
