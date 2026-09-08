<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    /**
     * Plain Eloquent create is permitted here: the editorial mutation guard
     * only forbids creating already-published content, and a brand-new
     * record has no sensitive editorial state to protect yet. The `key`
     * identity is chosen once here; it changes afterward only through the
     * dedicated "Change key" action.
     *
     * The image upload, its alt text, and technologies are hidden (and not
     * dehydrated) on the create form since the owned-asset actions and the
     * technology domain action both require an existing Project row; all
     * three are defensively stripped here too, and are managed afterward
     * on the edit page.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        unset($data['image'], $data['image_alt_es'], $data['image_alt_en'], $data['technologies']);
        $data['status'] = PublicationStatus::Draft;
        $data['is_visible'] = false;
        $data['published_at'] = null;
        $data['key_locked'] = false;
        $data['position'] = (int) (Project::query()->max('position') ?? -1) + 1;

        return Project::query()->create($data);
    }
}
