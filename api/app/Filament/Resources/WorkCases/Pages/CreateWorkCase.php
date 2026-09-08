<?php

namespace App\Filament\Resources\WorkCases\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\WorkCases\WorkCaseResource;
use App\Models\WorkCase;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWorkCase extends CreateRecord
{
    protected static string $resource = WorkCaseResource::class;

    /**
     * Plain Eloquent create is permitted here: the editorial mutation guard
     * only forbids creating already-published content, and a brand-new
     * record has no sensitive editorial state to protect yet. The `key`
     * identity is chosen once here; it changes afterward only through the
     * dedicated "Change key" action.
     *
     * Technologies are hidden (and not dehydrated) on the create form since
     * the domain action they are committed through requires an existing
     * WorkCase row; the key is also defensively stripped here too, and
     * technologies are managed afterward on the edit page.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        unset($data['technologies']);
        $data['status'] = PublicationStatus::Draft;
        $data['is_visible'] = false;
        $data['published_at'] = null;
        $data['key_locked'] = false;
        $data['position'] = (int) (WorkCase::query()->max('position') ?? -1) + 1;

        return WorkCase::query()->create($data);
    }
}
