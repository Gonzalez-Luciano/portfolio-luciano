<?php

namespace App\Filament\Resources\WorkCases\Pages;

use App\Filament\Resources\WorkCases\WorkCaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkCases extends ListRecords
{
    protected static string $resource = WorkCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
