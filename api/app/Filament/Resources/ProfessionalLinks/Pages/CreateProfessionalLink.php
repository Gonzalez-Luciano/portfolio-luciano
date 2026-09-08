<?php

namespace App\Filament\Resources\ProfessionalLinks\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\ProfessionalLinks\ProfessionalLinkResource;
use App\Models\ProfessionalLink;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProfessionalLink extends CreateRecord
{
    protected static string $resource = ProfessionalLinkResource::class;

    /**
     * Plain Eloquent create is permitted here: the editorial mutation guard
     * only forbids creating already-published content, and a brand-new
     * record has no sensitive editorial state to protect yet. The `type`
     * identity is chosen once here and never editable again.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['status'] = PublicationStatus::Draft;
        $data['is_visible'] = false;
        $data['published_at'] = null;
        $data['position'] = (int) (ProfessionalLink::query()->max('position') ?? -1) + 1;

        return ProfessionalLink::query()->create($data);
    }
}
