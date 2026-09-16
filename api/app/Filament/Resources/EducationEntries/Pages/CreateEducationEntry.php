<?php

namespace App\Filament\Resources\EducationEntries\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\EducationEntries\EducationEntryResource;
use App\Models\EducationEntry;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEducationEntry extends CreateRecord
{
    protected static string $resource = EducationEntryResource::class;

    /**
     * Plain Eloquent create is permitted: the editorial mutation guard only
     * forbids creating already-published content. New entries are appended
     * after the current last position.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['status'] = PublicationStatus::Draft;
        $data['is_visible'] = false;
        $data['published_at'] = null;
        $data['key_locked'] = false;
        $data['position'] = (int) (EducationEntry::query()->max('position') ?? -1) + 1;

        // A cleared numeric input submits an empty string, which MySQL rejects for SMALLINT.
        foreach (['start_year', 'end_year'] as $year) {
            if (($data[$year] ?? null) === '') {
                $data[$year] = null;
            }
        }

        return EducationEntry::query()->create($data);
    }
}
