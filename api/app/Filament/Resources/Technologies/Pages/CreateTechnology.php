<?php

namespace App\Filament\Resources\Technologies\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Technologies\TechnologyResource;
use App\Models\Technology;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTechnology extends CreateRecord
{
    protected static string $resource = TechnologyResource::class;

    /**
     * Plain Eloquent create is permitted here: the editorial mutation guard
     * only forbids creating already-published content, and a brand-new
     * record has no sensitive editorial state to protect yet. The `key`
     * identity is chosen once here; it changes afterward only through the
     * dedicated "Change key" action.
     *
     * The icon upload is discarded here (the owned-asset action requires
     * an existing Technology row) and is instead managed afterward on the
     * edit page.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        unset($data['icon']);
        $data['status'] = PublicationStatus::Draft;
        $data['is_visible'] = false;
        $data['published_at'] = null;
        $data['key_locked'] = false;
        $data['position'] = (int) (Technology::query()->max('position') ?? -1) + 1;

        return Technology::query()->create($data);
    }
}
