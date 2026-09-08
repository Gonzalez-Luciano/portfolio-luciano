<?php

namespace App\Filament\Resources\ProfessionalLinks\Pages;

use App\Filament\Resources\ProfessionalLinks\ProfessionalLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProfessionalLinks extends ListRecords
{
    protected static string $resource = ProfessionalLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
