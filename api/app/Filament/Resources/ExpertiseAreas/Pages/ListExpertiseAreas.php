<?php

namespace App\Filament\Resources\ExpertiseAreas\Pages;

use App\Filament\Resources\ExpertiseAreas\ExpertiseAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpertiseAreas extends ListRecords
{
    protected static string $resource = ExpertiseAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
