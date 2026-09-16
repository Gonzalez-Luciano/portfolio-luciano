<?php

namespace App\Filament\Resources\EducationEntries\Pages;

use App\Filament\Resources\EducationEntries\EducationEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEducationEntries extends ListRecords
{
    protected static string $resource = EducationEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
