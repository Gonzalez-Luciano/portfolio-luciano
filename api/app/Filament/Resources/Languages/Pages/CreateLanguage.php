<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Languages\LanguageResource;
use App\Models\Language;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLanguage extends CreateRecord
{
    protected static string $resource = LanguageResource::class;

    /**
     * Plain Eloquent create is permitted: the editorial mutation guard only
     * forbids creating already-published content. New languages are appended
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
        $data['position'] = (int) (Language::query()->max('position') ?? -1) + 1;

        return Language::query()->create($data);
    }
}
